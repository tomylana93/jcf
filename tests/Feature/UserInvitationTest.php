<?php

declare(strict_types=1);

use App\Actions\MasterData\User\CreateUser;
use App\Enums\Permission;
use App\Enums\Role;
use App\Jobs\MasterData\User\SendUserInvitation;
use App\Models\Role as RoleModel;
use App\Models\User;
use App\Notifications\MasterData\User\UserInvitation;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

describe('Admin user invitations', function (): void {

    it('creates an unverified invited account without a password and queues delivery after commit', function (): void {
        Queue::fake([SendUserInvitation::class]);
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        actingAs($admin)->post(route('master-data.users.store'), [
            'name' => 'Invited User', 'email' => 'invited@example.test', 'send_invitation' => true,
        ])->assertRedirect(route('master-data.users.index'));

        $user = User::query()->where('email', 'invited@example.test')->firstOrFail();
        expect($user->invited_at)->not->toBeNull()
            ->and($user->email_verified_at)->toBeNull()
            ->and($user->invitation_accepted_at)->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeFalse();
        Queue::assertPushed(SendUserInvitation::class, fn (SendUserInvitation $job): bool => $job->userId === $user->id && $job->email === $user->email && $job->afterCommit === true);
        get(route('master-data.users.index', ['filter' => ['search' => $user->email]]))
            ->assertInertia(fn (Assert $page): Assert => $page->where('users.data.0.invitation_pending', true));
    });

    it('sends a usable invitation that sets the password and verifies the email once', function (): void {
        Notification::fake();
        $user = User::factory()->unverified()->create(['invited_at' => now()]);
        Event::fake([Verified::class]);
        $job = new SendUserInvitation($user->id, $user->email);
        $job->handle();
        $notification = Notification::sent($user, UserInvitation::class)->sole();
        $mail = $notification->toMail($user);
        expect($mail->subject)->toBe(__('user.invitation.subject'))
            ->and($mail->actionText)->toBe(__('user.invitation.action'))
            ->and($mail->actionUrl)->toContain(urlencode($user->email))
            ->and($mail->introLines)->toContain(__('user.invitation.description'))
            ->and($mail->outroLines)->toContain(__('user.invitation.verification'));

        post(route('password.update'), [
            'email' => $user->email, 'token' => $notification->token,
            'password' => 'New-secure-password123!', 'password_confirmation' => 'New-secure-password123!',
        ])->assertSessionHasNoErrors();

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
            ->and($user->fresh()->invitation_accepted_at)->not->toBeNull()
            ->and(Hash::check('New-secure-password123!', $user->fresh()->password))->toBeTrue();
        Event::assertDispatchedTimes(Verified::class, 1);
        post(route('password.update'), [
            'email' => $user->email, 'token' => $notification->token,
            'password' => 'Another-password123!', 'password_confirmation' => 'Another-password123!',
        ])->assertSessionHasErrors('email');
        post(route('login'), ['email' => $user->email, 'password' => 'New-secure-password123!'])->assertRedirect();
        get(route('dashboard'))->assertSuccessful();
    });

    it('rejects invalid and expired invitation tokens without verifying the account', function (bool $expired): void {
        $user = User::factory()->unverified()->create(['invited_at' => now()]);
        $token = $expired ? Password::broker()->createToken($user) : 'invalid-token';
        travel(61)->minutes();

        post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'New-secure-password123!', 'password_confirmation' => 'New-secure-password123!',
        ])->assertSessionHasErrors('email');
        expect($user->fresh()->email_verified_at)->toBeNull()
            ->and($user->fresh()->invitation_accepted_at)->toBeNull();
    })->with(['invalid' => false, 'expired' => true]);

    it('does not verify ordinary accounts on password reset', function (): void {
        $user = User::factory()->unverified()->create();
        $token = Password::broker()->createToken($user);
        post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'New-secure-password123!', 'password_confirmation' => 'New-secure-password123!',
        ])->assertSessionHasNoErrors();
        expect($user->fresh()->email_verified_at)->toBeNull()
            ->and($user->fresh()->invitation_accepted_at)->toBeNull();
    });

    it('accepts an invitation for an already verified account without repeating verification', function (): void {
        $user = User::factory()->create(['invited_at' => now()]);
        Event::fake([Verified::class]);
        post(route('password.update'), [
            'email' => $user->email, 'token' => Password::broker()->createToken($user),
            'password' => 'New-secure-password123!', 'password_confirmation' => 'New-secure-password123!',
        ])->assertSessionHasNoErrors();
        expect($user->fresh()->invitation_accepted_at)->not->toBeNull();
        Event::assertNotDispatched(Verified::class);
    });

    it('queues resends and throttles repeated requests for the same account', function (): void {
        Queue::fake([SendUserInvitation::class]);
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);
        $user = User::factory()->unverified()->create(['invited_at' => now()]);
        actingAs($admin)->post(route('master-data.users.invitation', $user))->assertRedirect();
        post(route('master-data.users.invitation', $user))->assertSessionHasErrors('invitation');
        Queue::assertPushed(SendUserInvitation::class, 1);
    });

    it('rejects resends for accounts without an active pending invitation', function (array $attributes): void {
        Queue::fake();
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);
        $user = User::factory()->create($attributes);
        actingAs($admin)->post(route('master-data.users.invitation', $user))->assertSessionHasErrors('invitation');
        Queue::assertNothingPushed();
    })->with([
        'ordinary' => [[]],
        'accepted' => [['invited_at' => now(), 'invitation_accepted_at' => now()]],
        'disabled' => [['invited_at' => now(), 'status' => 'disabled']],
    ]);

    it('protects resend access from guests and unauthorized users and protects superadmins', function (): void {
        Queue::fake();
        $target = User::factory()->create(['invited_at' => now()]);
        post(route('master-data.users.invitation', $target))->assertRedirect(route('login'));
        $ordinary = User::factory()->create();
        actingAs($ordinary)->post(route('master-data.users.invitation', $target))->assertForbidden();
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);
        $target->syncRoles(RoleModel::findOrCreate(Role::SuperAdmin->value, 'web'));
        actingAs($admin)->post(route('master-data.users.invitation', $target))->assertForbidden();
        Queue::assertNothingPushed();
    });

    it('skips stale deliveries', function (string $condition): void {
        Notification::fake();
        $user = User::factory()->create(['invited_at' => now()]);
        $email = $user->email;
        match ($condition) {
            'deleted' => $user->delete(),
            'email' => $user->forceFill(['email' => 'changed@example.test'])->save(),
            'ordinary' => $user->forceFill(['invited_at' => null])->save(),
            'accepted' => $user->forceFill(['invitation_accepted_at' => now()])->save(),
            'disabled' => $user->forceFill(['status' => 'disabled'])->save(),
        };
        new SendUserInvitation($user->id, $email)->handle();
        Notification::assertNothingSent();
    })->with(['deleted', 'email', 'ordinary', 'accepted', 'disabled']);

    it('invalidates the old link and queues a new invitation when the email changes', function (): void {
        Queue::fake([SendUserInvitation::class]);
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);
        $user = User::factory()->create(['invited_at' => now()]);
        $oldEmail = $user->email;
        $oldToken = Password::broker()->createToken($user);
        actingAs($admin)->patch(route('master-data.users.update', $user), [
            'name' => $user->name, 'email' => 'changed@example.test', 'send_invitation' => true, 'status' => 'active',
        ])->assertRedirect();
        expect($user->fresh()->hasVerifiedEmail())->toBeFalse()
            ->and(DB::table('password_reset_tokens')->where('email', $oldEmail)->exists())->toBeFalse();
        Queue::assertPushed(SendUserInvitation::class, fn (SendUserInvitation $job): bool => $job->email === 'changed@example.test');
        auth()->logout();
        post(route('password.update'), [
            'email' => $oldEmail, 'token' => $oldToken,
            'password' => 'New-secure-password123!', 'password_confirmation' => 'New-secure-password123!',
        ])->assertSessionHasErrors('email');
    });

    it('rolls back account creation without delivering an invitation', function (): void {
        Notification::fake();
        try {
            DB::transaction(function (): void {
                resolve(CreateUser::class)->handle(['name' => 'Rollback', 'email' => 'rollback@example.test', 'send_invitation' => true]);
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
            expect(User::query()->where('email', 'rollback@example.test')->exists())->toBeFalse();
        }
        Notification::assertSentTimes(UserInvitation::class, 0);
    });
});

describe('Invitation account boundaries', function (): void {
    it('refuses account creation without an invitation or initial password', function (): void {
        expect(fn () => resolve(CreateUser::class)->handle(['name' => 'Rejected', 'email' => 'missing-password@example.test']))
            ->toThrow(ValidationException::class)
            ->and(User::query()->where('email', 'missing-password@example.test')->exists())->toBeFalse();
    });

    it('refuses a previously issued invitation after the account is disabled', function (): void {
        $user = User::factory()->unverified()->create(['invited_at' => now()]);
        $token = Password::broker()->createToken($user);
        $user->forceFill(['status' => 'disabled'])->save();
        post(route('password.update'), ['email' => $user->email, 'token' => $token,
            'password' => 'New-secure-password123!', 'password_confirmation' => 'New-secure-password123!',
        ])->assertForbidden();
        expect($user->fresh()->invitation_accepted_at)->toBeNull()->and($user->fresh()->email_verified_at)->toBeNull();
    });
});
