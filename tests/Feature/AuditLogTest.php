<?php

declare(strict_types=1);

use App\Actions\Audit\RecordAuditLog;
use App\Actions\MasterData\User\UpdateUser;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Jobs\MasterData\User\SendUserInvitation;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('Audit access and filtering', function (): void {
    it('requires authentication and the audit permission', function (): void {
        get(route('audit.index'))->assertRedirect(route('login'));
        actingAs(User::factory()->create())->get(route('audit.index'))->assertForbidden();
    });

    it('shows changed values and filters by actor subject and event', function (): void {
        $actor = User::factory()->create(['name' => 'Audit reader']);
        assignRolePermissions($actor, Permission::ViewAuditLogs);
        $log = AuditLog::factory()->create(['actor_id' => $actor->id, 'actor_name' => $actor->name, 'subject_label' => 'Search Target']);
        AuditLog::factory()->create(['event' => 'settings.updated', 'subject_type' => 'settings', 'subject_label' => 'general']);

        actingAs($actor)->get(route('audit.index', ['filter' => ['search' => 'search target', 'event' => 'user.updated'], 'sort' => '-created_at']))
            ->assertInertia(fn (Assert $page): Assert => $page->component('audit/Index')->has('logs.data', 1)
                ->where('logs.data.0.id', $log->id)->where('logs.data.0.before.name', 'Previous name')->where('logs.data.0.after.name', 'Updated name'));
        get(route('audit.index', ['filter' => ['search' => $actor->name]]))->assertInertia(fn (Assert $page): Assert => $page->has('logs.data', 1));
        get(route('audit.index', ['filter' => ['event' => 'settings.updated']]))
            ->assertInertia(fn (Assert $page): Assert => $page->where('logs.data.0.subject_label', 'General settings')->where('logs.data.0.actor_name', 'System'));
    });

    it('rejects invalid filters', function (array $query, string $error): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewAuditLogs);
        actingAs($actor)->get(route('audit.index', $query))->assertSessionHasErrors($error);
    })->with([
        'event' => [['filter' => ['event' => 'unknown']], 'filter.event'],
        'search type' => [['filter' => ['search' => ['invalid']]], 'filter.search'],
        'search length' => [['filter' => ['search' => str_repeat('a', 201)]], 'filter.search'],
        'filter shape' => [['filter' => ['unknown' => 'value']], 'filter'],
    ]);
});

describe('Sensitive change recording', function (): void {
    it('records user creation and queued invitations without credentials', function (): void {
        Queue::fake();
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        $role = Role::factory()->create(['name' => 'operator']);

        actingAs($actor)->post(route('master-data.users.store'), ['name' => 'Invited User', 'email' => 'invited@example.test', 'role' => $role->name, 'send_invitation' => true])
            ->assertSessionHasNoErrors()->assertRedirect(route('master-data.users.index'));

        $target = User::query()->where('email', 'invited@example.test')->sole();
        $log = AuditLog::query()->where('event', 'user.created')->sole();
        expect($log->actor_id)->toBe($actor->id)
            ->and($log->after)->toBe(['name' => 'Invited User', 'email' => 'invited@example.test', 'status' => 'active', 'role' => ['operator']])
            ->and(json_encode(AuditLog::all()))->not->toContain('password', 'token', $target->password)
            ->and($target->unreadNotifications()->sole()->data['kind'])->toBe('account_created')
            ->and($actor->unreadNotifications()->sole()->data['kind'])->toBe('invitation_queued');
        Queue::assertPushed(SendUserInvitation::class);
    });

    it('records role and status changes and skips unchanged retries', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        $target = User::factory()->create(['name' => 'Before']);
        $role = Role::factory()->create(['name' => 'reviewer']);
        $data = ['name' => 'After', 'email' => $target->email, 'role' => $role->name, 'status' => 'active'];

        actingAs($actor)->patch(route('master-data.users.update', $target), $data)->assertSessionHasNoErrors();
        patch(route('master-data.users.update', $target), $data)->assertSessionHasNoErrors();
        $log = AuditLog::query()->sole();
        expect($log->before)->toBe(['name' => 'Before', 'role' => []])->and($log->after)->toBe(['name' => 'After', 'role' => ['reviewer']])
            ->and($target->unreadNotifications()->count())->toBe(1);
        patch(route('master-data.users.status', $target), ['status' => 'disabled'])->assertSessionHasNoErrors();
        patch(route('master-data.users.status', $target), ['status' => 'disabled'])->assertSessionHasNoErrors();
        expect(AuditLog::query()->where('event', 'user.status_updated')->sole()->after)->toBe(['status' => 'disabled'])
            ->and($target->unreadNotifications()->count())->toBe(2);
    });

    it('records settings actors and redacts SMTP passwords', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageSettings, Permission::ViewSettings, Permission::ViewAuditLogs);
        actingAs($actor)->patch(route('settings.mail.update'), [
            'mailer' => 'smtp', 'smtp_host' => 'localhost', 'smtp_port' => 1025, 'smtp_scheme' => 'smtp',
            'smtp_username' => '', 'smtp_password' => 'secret-smtp-value', 'from_address' => 'mail@example.test',
            'from_name' => 'Example', 'show_logo' => true, 'footer_text' => '',
        ])->assertSessionHasNoErrors();
        $log = AuditLog::query()->sole();
        expect($log->actor_id)->toBe($actor->id)->and($log->after['smtp_password_changed'])->toBeTrue()
            ->and(json_encode($log))->not->toContain('secret-smtp-value', '"smtp_password"');
        get(route('audit.index'))->assertDontSee('secret-smtp-value');
    });

    it('removes nested credentials before storing audit values', function (): void {
        $log = resolve(RecordAuditLog::class)->handle(null, 'settings.updated', 'settings', 'mail', 'mail', [], [
            'nested' => ['api_key' => 'private-key', 'token' => 'private-token', 'password' => 'private-password', 'name' => 'Safe'],
            'secret' => 'private-secret',
            'password_min_length' => 12,
        ]);
        expect($log->after)->toBe(['nested' => ['name' => 'Safe'], 'password_min_length' => 12])
            ->and(resolve(RecordAuditLog::class)->handle(null, 'settings.updated', 'settings', 'mail', 'mail', ['token' => 'old'], ['token' => 'new']))->toBeNull();
    });

    it('rolls back user audit and notifications together', function (): void {
        $target = User::factory()->create(['name' => 'Before']);
        DB::beginTransaction();
        resolve(UpdateUser::class)->handle($target, ['name' => 'Rolled back', 'email' => $target->email, 'status' => UserStatus::Active->value]);
        DB::rollBack();
        expect(AuditLog::query()->count())->toBe(0)->and($target->notifications()->count())->toBe(0)->and($target->refresh()->name)->toBe('Before');
    });

    it('rolls back settings and their audit records together', function (): void {
        $settings = resolve(GeneralSettings::class);
        $before = $settings->site_name;
        DB::beginTransaction();
        resolve(UpdateGeneralSettings::class)->handle(['site_name' => 'Rolled back', 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => false]);
        DB::rollBack();
        expect(AuditLog::query()->count())->toBe(0)->and($settings->refresh()->site_name)->toBe($before);
    });

    it('retains the actor snapshot after deletion and prunes only expired records', function (): void {
        config(['audit.retention_days' => 30]);
        $actor = User::factory()->create(['name' => 'Former administrator']);
        $old = AuditLog::factory()->create(['actor_id' => $actor->id, 'actor_name' => $actor->name, 'created_at' => now()->subDays(31)]);
        $recent = AuditLog::factory()->create(['created_at' => now()->subDays(29)]);
        $actor->delete();
        expect($old->refresh()->actor_id)->toBeNull()->and($old->actor_name)->toBe('Former administrator');
        artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();
        expect(AuditLog::query()->find($old->id))->toBeNull()->and($recent->fresh())->not->toBeNull();
    });
});
