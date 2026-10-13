<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use App\Settings\AuthSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

describe('Authentication settings', function (): void {
    it('stores secure defaults', function (): void {
        $settings = resolve(AuthSettings::class);

        expect($settings->registration_enabled)->toBeTrue()
            ->and($settings->email_verification_required)->toBeTrue()
            ->and($settings->password_min_length)->toBe(8)
            ->and($settings->login_max_attempts)->toBe(5)
            ->and($settings->login_lockout_minutes)->toBe(1);
    });

    it('redirects guests and unverified users', function (): void {
        get(route('settings.auth.edit'))->assertRedirect(route('login'));
        patch(route('settings.auth.update'), [])->assertRedirect(route('login'));

        $user = User::factory()->unverified()->create();
        $user->syncRoles(RoleModel::findOrCreate(Role::SuperAdmin->value, 'web'));

        actingAs($user)->get(route('settings.auth.edit'))->assertRedirect(route('verification.notice'));

        patch(route('settings.auth.update'), [])->assertRedirect(route('verification.notice'));
    });

    it('allows readers to view settings but only managers to update them', function (): void {

        $reader = User::factory()->create();
        assignRolePermissions($reader, Permission::ViewSettings);

        actingAs($reader)->get(route('settings.auth.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->component('settings/Auth')
            ->where('settings.password_min_length', 8));

        patch(route('settings.auth.update'), [])->assertForbidden();

        $user = User::factory()->create();
        actingAs($user)->get(route('settings.auth.edit'))->assertForbidden();

        patch(route('settings.auth.update'), [])->assertForbidden();
    });

    it('saves settings for an administrator', function (): void {

        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->patch(route('settings.auth.update'), [
            'registration_enabled' => false,
            'email_verification_required' => false,
            'password_min_length' => 16,
            'login_max_attempts' => 3,
            'login_lockout_minutes' => 4,
        ])->assertSessionHasNoErrors()->assertRedirect(route('settings.auth.edit'));

        assertDatabaseHas('settings', ['group' => 'auth', 'name' => 'password_min_length', 'payload' => '16']);

        get(route('settings.auth.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('settings.registration_enabled', false)
            ->where('settings.email_verification_required', false)
            ->where('settings.password_min_length', 16)
            ->where('settings.login_max_attempts', 3)
            ->where('settings.login_lockout_minutes', 4));
    });

    it('rejects invalid settings without persisting them', function (string $field, mixed $value): void {

        $user = User::factory()->create();
        $user->syncRoles(RoleModel::findOrCreate(Role::SuperAdmin->value, 'web'));
        $data = [
            'registration_enabled' => true,
            'email_verification_required' => true,
            'password_min_length' => 12,
            'login_max_attempts' => 5,
            'login_lockout_minutes' => 1,
        ];
        $data[$field] = $value;

        actingAs($user)->patch(route('settings.auth.update'), $data)->assertInvalid([$field]);

        expect(resolve(AuthSettings::class)->refresh()->password_min_length)->toBe(8);
    })->with([
        'registration flag' => ['registration_enabled', 'invalid'],
        'verification flag' => ['email_verification_required', 'invalid'],
        'short password minimum' => ['password_min_length', 7],
        'long password minimum' => ['password_min_length', 129],
        'zero attempts' => ['login_max_attempts', 0],
        'too many attempts' => ['login_max_attempts', 21],
        'zero lockout' => ['login_lockout_minutes', 0],
        'long lockout' => ['login_lockout_minutes', 61],
    ]);

    it('validates precognitive requests without saving changes', function (): void {

        $user = User::factory()->create();
        $user->syncRoles(RoleModel::findOrCreate(Role::SuperAdmin->value, 'web'));

        actingAs($user)->withHeaders(['Precognition' => 'true'])->patch(route('settings.auth.update'), [
            'registration_enabled' => false,
            'email_verification_required' => true,
            'password_min_length' => 16,
            'login_max_attempts' => 3,
            'login_lockout_minutes' => 2,
        ])->assertSuccessfulPrecognition();

        expect(resolve(AuthSettings::class)->refresh()->registration_enabled)->toBeTrue();
    });
});

describe('Authentication settings enforcement', function (): void {
    it('closes both registration endpoints and hides registration from login', function (): void {
        $settings = resolve(AuthSettings::class);
        $settings->registration_enabled = false;
        $settings->save();

        get(route('register'))->assertNotFound();
        post(route('register.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',

        ])->assertNotFound();

        assertDatabaseMissing('users', ['email' => 'blocked@example.com']);

        get(route('login'))->assertInertia(fn (Assert $page): Assert => $page->where('canRegister', false));
    });

    it('allows unverified access when verification is optional', function (): void {
        $settings = resolve(AuthSettings::class);
        $settings->email_verification_required = false;
        $settings->save();
        $user = User::factory()->unverified()->create();
        $user->syncRoles(RoleModel::findOrCreate(Role::SuperAdmin->value, 'web'));

        actingAs($user)->get(route('dashboard'))->assertOk();

        get(route('profile.edit'))->assertInertia(fn (Assert $page): Assert => $page->where('mustVerifyEmail', false));
        get(route('settings.auth.edit'))->assertOk();
    });

    it('applies the configured password minimum to new passwords', function (): void {
        $settings = resolve(AuthSettings::class);
        $settings->password_min_length = 16;
        $settings->save();

        get(route('register'))->assertInertia(fn (Assert $page): Assert => $page->where('passwordRules', Password::defaults()->toPasswordRulesString()));
        post(route('register.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',

        ])->assertInvalid(['password']);

        assertDatabaseMissing('users', ['email' => 'new@example.com']);
    });

    it('applies the configured password minimum to password changes and resets', function (): void {
        $settings = resolve(AuthSettings::class);
        $settings->password_min_length = 16;
        $settings->save();
        $user = User::factory()->create();

        actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertInvalid(['password']);

        $token = PasswordBroker::createToken($user);
        Auth::logout();

        post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',

        ])->assertInvalid(['password']);
    });

    it('applies the configured login attempt count and lockout duration', function (): void {
        $settings = resolve(AuthSettings::class);
        $settings->login_max_attempts = 2;
        $settings->login_lockout_minutes = 3;
        $settings->save();

        $request = Request::create(route('login.store'), 'POST', ['email' => 'blocked@example.com']);
        $limit = RateLimiter::limiter('login')($request);

        expect($limit)->toBeInstanceOf(Limit::class)
            ->and($limit->maxAttempts)->toBe(2)
            ->and($limit->decaySeconds)->toBe(180);

        post(route('login.store'), ['email' => 'blocked@example.com', 'password' => 'wrong-password']);
        post(route('login.store'), ['email' => 'blocked@example.com', 'password' => 'wrong-password']);
        post(route('login.store'), ['email' => 'blocked@example.com', 'password' => 'wrong-password'])->assertTooManyRequests();
    });
});
