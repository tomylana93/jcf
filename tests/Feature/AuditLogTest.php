<?php

declare(strict_types=1);

use App\Actions\Audit\DescribeAuditChanges;
use App\Actions\Audit\RecordAuditLog;
use App\Actions\Identity\User\UpdateUser;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\AuditEvent;
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
                ->where('logs.data.0.id', $log->id)->where('logs.data.0.changes', [['field' => 'name', 'label' => 'Full name', 'before' => 'Previous name', 'after' => 'Updated name']]));

        get(route('audit.index', ['filter' => ['search' => $actor->name]]))->assertInertia(fn (Assert $page): Assert => $page->has('logs.data', 1));
        get(route('audit.index', ['filter' => ['event' => 'settings.updated']]))
            ->assertInertia(fn (Assert $page): Assert => $page->where('logs.data.0.subject_label', 'General settings')->where('logs.data.0.actor_name', 'System'));
    });

    it('labels and filters API client events', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewAuditLogs);
        AuditLog::factory()->create(['event' => AuditEvent::ApiClientTokenIssued->value, 'subject_type' => 'api_client']);
        AuditLog::factory()->create();

        actingAs($actor)->get(route('audit.index', ['filter' => ['event' => AuditEvent::ApiClientTokenIssued->value]]))
            ->assertSessionHasNoErrors()
            ->assertInertia(fn (Assert $page): Assert => $page->has('logs.data', 1)
                ->where('logs.data.0.event_label', 'API client token issued')
                ->where('eventOptions', fn ($options): bool => collect($options)->pluck('value')->contains(AuditEvent::ApiClientTokenIssued->value)));
    });

    it('translates every audit event in each locale', function (string $locale): void {
        foreach (AuditEvent::cases() as $event) {
            $key = 'enums.audit_event.'.$event->name;

            expect(__($key, locale: $locale))->not->toBe($key);
        }
    })->with(['en', 'id']);

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
        $log = resolve(RecordAuditLog::class)->handle(null, AuditEvent::SettingsUpdated, 'settings', 'mail', 'mail', [], [
            'nested' => ['api_key' => 'private-key', 'token' => 'private-token', 'password' => 'private-password', 'name' => 'Safe'],
            'secret' => 'private-secret',
            'password_min_length' => 12,

        ]);

        expect($log->after)->toBe(['nested' => ['name' => 'Safe'], 'password_min_length' => 12])
            ->and(resolve(RecordAuditLog::class)->handle(null, AuditEvent::SettingsUpdated, 'settings', 'mail', 'mail', ['token' => 'old'], ['token' => 'new']))->toBeNull();
    });

    it('rolls back user audit and notifications together', function (): void {
        $target = User::factory()->create(['name' => 'Before']);
        DB::beginTransaction();

        resolve(UpdateUser::class)->handle(null, $target, ['name' => 'Rolled back', 'email' => $target->email, 'status' => UserStatus::Active->value]);

        DB::rollBack();

        expect(AuditLog::query()->count())->toBe(0)->and($target->notifications()->count())->toBe(0)->and($target->refresh()->name)->toBe('Before');
    });

    it('rolls back settings and their audit records together', function (): void {
        $settings = resolve(GeneralSettings::class);
        $before = $settings->site_name;
        DB::beginTransaction();

        resolve(UpdateGeneralSettings::class)->handle(null, ['site_name' => 'Rolled back', 'site_description' => '', 'support_email' => null]);

        DB::rollBack();

        expect(AuditLog::query()->count())->toBe(0)->and($settings->refresh()->site_name)->toBe($before);
    });

    it('retains the actor snapshot after deletion and prunes only expired records', function (): void {
        config(['jalara.audit.retention_days' => 30]);
        $actor = User::factory()->create(['name' => 'Former administrator']);
        $old = AuditLog::factory()->create(['actor_id' => $actor->id, 'actor_name' => $actor->name, 'created_at' => now()->subDays(31)]);
        $recent = AuditLog::factory()->create(['created_at' => now()->subDays(29)]);
        $actor->delete();

        expect($old->refresh()->actor_id)->toBeNull()->and($old->actor_name)->toBe('Former administrator');

        artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

        expect(AuditLog::query()->find($old->id))->toBeNull()->and($recent->fresh())->not->toBeNull();
    });
});

describe('Audit change labels', function (): void {
    it('labels settings fields with form labels, change flags, booleans and options', function (): void {
        $log = AuditLog::factory()->create([
            'event' => 'settings.updated', 'subject_type' => 'settings', 'subject_label' => 'mail',
            'before' => ['mailer' => 'log', 'show_logo' => false, 'smtp_password_changed' => false],
            'after' => ['mailer' => 'smtp', 'show_logo' => true, 'smtp_password_changed' => true],
        ]);

        $changes = resolve(DescribeAuditChanges::class)->handle($log);

        expect($changes)->toBe([
            ['field' => 'mailer', 'label' => 'Delivery mode', 'before' => 'Do not send (record only)', 'after' => 'SMTP'],
            ['field' => 'show_logo', 'label' => 'Show brand logo', 'before' => 'No', 'after' => 'Yes'],
            ['field' => 'smtp_password_changed', 'label' => 'SMTP password', 'before' => null, 'after' => 'Changed'],
        ]);
    });

    it('translates enum values and module toggles in the active locale', function (): void {
        app()->setLocale('id');
        $brand = AuditLog::factory()->create([
            'event' => 'settings.updated', 'subject_type' => 'settings', 'subject_label' => 'brand',
            'before' => ['logo_type' => 'logo_only', 'favicon_path' => null], 'after' => ['logo_type' => 'logo_text', 'favicon_path' => 'brand/favicon.png'],
        ]);
        $module = AuditLog::factory()->create([
            'event' => 'settings.updated', 'subject_type' => 'settings', 'subject_label' => 'chat',
            'before' => ['enabled' => true], 'after' => ['enabled' => false],
        ]);

        $brandChanges = resolve(DescribeAuditChanges::class)->handle($brand);
        $moduleChanges = resolve(DescribeAuditChanges::class)->handle($module);

        expect($brandChanges)->toBe([
            ['field' => 'logo_type', 'label' => 'Tipe logo', 'before' => 'Logo lengkap', 'after' => 'Simbol + nama'],
            ['field' => 'favicon_path', 'label' => 'Favicon', 'before' => null, 'after' => 'brand/favicon.png'],
        ])->and($moduleChanges)->toBe([
            ['field' => 'enabled', 'label' => 'Chat', 'before' => 'Ya', 'after' => 'Tidak'],
        ]);
    });

    it('keeps raw field names and values for unknown subject types', function (): void {
        $log = AuditLog::factory()->create([
            'event' => 'legacy.event', 'subject_type' => 'legacy', 'subject_label' => 'Legacy record',
            'before' => ['site_name' => 'Old', 'limit' => 5], 'after' => ['site_name' => 'New', 'limit' => 10],
        ]);

        $changes = resolve(DescribeAuditChanges::class)->handle($log);

        expect($changes)->toBe([
            ['field' => 'site_name', 'label' => 'site_name', 'before' => 'Old', 'after' => 'New'],
            ['field' => 'limit', 'label' => 'limit', 'before' => '5', 'after' => '10'],
        ]);
    });

    it('labels user lists and falls back to raw names for unknown fields', function (): void {
        $log = AuditLog::factory()->create([
            'event' => 'user.permissions_updated', 'subject_type' => 'user', 'subject_label' => 'Target',
            'before' => ['permissions' => ['users.view'], 'legacy_flag' => ['nested' => 1]],
            'after' => ['permissions' => ['users.view', 'custom.permission'], 'legacy_flag' => null],
        ]);

        $changes = resolve(DescribeAuditChanges::class)->handle($log);

        expect($changes)->toBe([
            ['field' => 'permissions', 'label' => 'Direct permissions', 'before' => 'View users', 'after' => 'View users, custom.permission'],
            ['field' => 'legacy_flag', 'label' => 'legacy_flag', 'before' => '{"nested":1}', 'after' => null],
        ]);
    });
});
