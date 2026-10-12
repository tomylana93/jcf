<?php

declare(strict_types=1);

use App\Actions\MasterData\Backup\CreateBackup;
use App\Actions\MasterData\Backup\GetBackupLock;
use App\Actions\System\GetSystemHealth;
use App\Enums\Permission;
use App\Enums\Role;
use App\Jobs\CreateSystemBackup;
use App\Jobs\SystemHeartbeat;
use App\Models\User;
use App\Settings\MailSettings;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Backup\Config\Config;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

describe('System operations authorization', function (): void {
    beforeEach(fn () => artisan('init:permission')->assertSuccessful());
    it('protects system pages and backup actions from guests', function (): void {
        get(route('system.index'))->assertRedirect(route('login'));
        post(route('master-data.backups.store'))->assertRedirect(route('login'));
        get(route('master-data.backups.download', 'test.zip'))->assertRedirect(route('login'));
    });
    it('separates health viewing from access to full backups', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSystem);
        expect($user->can(Permission::ViewSystem))->toBeTrue()
            ->and($user->can(Permission::ManageBackups))->toBeFalse();
    });
    it('forbids ordinary users and backup access by administrators', function (): void {
        actingAs(User::factory()->create());
        get(route('system.index'))->assertForbidden();
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSystem);
        actingAs($admin);
        withSession(['auth.password_confirmed_at' => time()]);
        post(route('master-data.backups.store'))->assertForbidden();
        get(route('master-data.backups.download', 'test.zip'))->assertForbidden();
    });
    it('shows health without leaking archive names or operation status to administrators', function (): void {
        Storage::fake('backups')->put('jcf/secret.zip', 'data');
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSystem);
        Cache::put('system.backup_status', 'failed');
        actingAs($user)->get(route('system.index'))->assertInertia(fn (Assert $page) => $page
            ->component('system/Index')->has('checks', 7)->missing('backups')->missing('backupStatus'));
    });
    it('lists valid archives and requires confirmation before downloading', function (): void {
        $disk = Storage::fake('backups');
        $disk->put('jcf/2026-10-06.zip', 'archive');
        $disk->put('jcf/readme.txt', 'ignored');
        $user = User::factory()->create();
        $user->syncRoles(Role::SuperAdmin);
        actingAs($user)->get(route('master-data.backups.index'))->assertInertia(fn (Assert $page) => $page
            ->component('master-data/backups/Index')->has('backups.data', 1)->where('backups.data.0.filename', '2026-10-06.zip')->where('backups.data.0.size', 7));
        get(route('master-data.backups.download', '2026-10-06.zip'))->assertRedirect(route('password.confirm'));
        withSession(['auth.password_confirmed_at' => time()]);
        get(route('master-data.backups.download', '2026-10-06.zip'))->assertDownload('2026-10-06.zip')->assertStreamedContent('archive');
        get(route('master-data.backups.download', 'missing.zip'))->assertNotFound();
        get('/master-data/backups/invalid.txt')->assertNotFound();
    });
    it('validates configuration and queues a backup on the isolated worker connection', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(Role::SuperAdmin);
        actingAs($user);
        withSession(['auth.password_confirmed_at' => time()]);
        config(['operations.backup_enabled' => false]);
        post(route('master-data.backups.store'))->assertSessionHasErrors(['backup' => __('backup.error.not_configured')]);
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        Queue::fake([CreateSystemBackup::class]);
        post(route('master-data.backups.store'))->assertSessionHasNoErrors()->assertRedirect();
        Queue::assertPushed(CreateSystemBackup::class, fn (CreateSystemBackup $job): bool => $job->actorId === $user->id && $job->connection === 'backups');
    });
});

describe('System readiness checks', function (): void {
    it('records scheduler and asynchronous worker heartbeats', function (): void {
        Queue::fake([SystemHeartbeat::class]);
        artisan('system:heartbeat')->assertSuccessful();
        expect(Cache::get('system.scheduler_heartbeat'))->toBe(now()->timestamp);
        Queue::assertPushed(SystemHeartbeat::class);
        resolve(SystemHeartbeat::class)->handle();
        config(['queue.default' => 'database', 'operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret', 'mail.default' => 'smtp']);
        Storage::fake('backups')->put('jcf/test.zip', 'backup');
        $checks = collect(resolve(GetSystemHealth::class)->handle())->keyBy('name');
        expect($checks['queue']['detail'])->toBe('recent')
            ->and($checks['scheduler']['detail'])->toBe('recent')
            ->and($checks['backup']['detail'])->toBe('recent')
            ->and($checks['mail']['detail'])->toBe('configured')
            ->and($checks['storage']['status'])->toBe('ok');
    });
    it('distinguishes stale workers and settings mail from a delivery test', function (): void {
        config(['queue.default' => 'database', 'mail.default' => 'settings', 'operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        Storage::fake('backups');
        Cache::put('system.queue_heartbeat', now()->subMinutes(6)->timestamp);
        $mail = resolve(MailSettings::class);
        $mail->mailer = 'smtp';
        $mail->smtp_host = 'smtp.example.com';
        $checks = collect(resolve(GetSystemHealth::class)->handle())->keyBy('name');
        expect($checks['queue']['detail'])->toBe('stale')
            ->and($checks['scheduler']['detail'])->toBe('stale')
            ->and($checks['backup']['detail'])->toBe('stale')
            ->and($checks['mail']['detail'])->toBe('configured');
    });
    it('reports failures without returning service exception details', function (): void {
        $action = resolve(GetSystemHealth::class);
        $original = config('database.default');
        config(['database.default' => 'missing', 'filesystems.default' => 'missing']);
        try {
            $checks = collect($action->handle())->keyBy('name');
        } finally {
            config(['database.default' => $original]);
        }
        expect($checks['database'])->toBe(['name' => 'database', 'status' => 'failed', 'detail' => 'unavailable'])
            ->and($checks['storage']['status'])->toBe('failed');
    });
});

describe('Backup execution', function (): void {
    it('refuses an unconfigured backup', function (): void {
        config(['operations.backup_enabled' => false]);
        expect(fn () => resolve(CreateBackup::class)->handle())->toThrow(RuntimeException::class, __('backup.error.not_configured'));
    });
    it('records a successful backup and notifies its requester', function (): void {
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        Storage::fake('backups');
        Artisan::shouldReceive('call')->once()->with('backup:run', Mockery::on(function (array $arguments): bool {
            Storage::disk('backups')->put('jcf/'.$arguments['--filename'], 'encrypted-archive');

            return $arguments['--no-interaction'] === true;
        }))->andReturn(0);
        $user = User::factory()->create();
        resolve(CreateSystemBackup::class, ['actorId' => $user->id])->handle(resolve(CreateBackup::class));
        expect(Cache::get('system.backup_status'))->toBe('completed')
            ->and($user->notifications()->sole()->data['kind'])->toBe('backup_ready')
            ->and($user->notifications()->sole()->data['subject'])->toMatch('/^backup-\d{8}-\d{12}\.zip$/');
    });
    it('marks terminal job failure and sends a private result notification', function (): void {
        $user = User::factory()->create();
        new CreateSystemBackup($user->id)->failed(new RuntimeException('private internal error'));
        expect(Cache::get('system.backup_status'))->toBe('failed')
            ->and($user->notifications()->sole()->data)->toBe(['kind' => 'backup_failed', 'subject' => '']);
        new CreateSystemBackup('00000000-0000-0000-0000-000000000000')->failed(null);
    });
    it('marks failed commands and missing output as failure', function (): void {
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        Storage::fake('backups');
        Artisan::shouldReceive('call')->once()->andReturn(1);
        expect(fn () => resolve(CreateBackup::class)->handle())->toThrow(RuntimeException::class, __('backup.error.backup_failed'))
            ->and(Cache::get('system.backup_status'))->toBe('failed');
    });
    it('refuses concurrent backups without changing their status', function (): void {
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        $lock = resolve(GetBackupLock::class)->handle();
        $lock->get();
        try {
            expect(fn () => resolve(CreateBackup::class)->handle())->toThrow(RuntimeException::class, __('backup.error.busy'));
        } finally {
            $lock->release();
        }
    });
    it('returns a nonzero CLI status when configuration is incomplete', function (): void {
        config(['operations.backup_enabled' => false]);
        artisan('system:backup')->assertFailed();
    });
});

describe('Backup CLI completion', function (): void {
    it('produces an encrypted files archive through the real package', function (): void {
        $disk = Storage::fake('backups');
        $files = Storage::fake('backup-test-files');
        $files->put('example.txt', 'roundtrip-file');
        config([
            'operations.backup_enabled' => true,
            'backup.backup.password' => 'test-secret',
            'backup.backup.source.databases' => [],
            'backup.backup.source.files.include' => [$files->path('example.txt')],
            'backup.backup.source.files.exclude' => [],
        ]);
        Config::rebind();
        artisan('system:backup')->assertSuccessful();
        expect($disk->files('jcf'))->toHaveCount(1)
            ->and(Cache::get('system.backup_status'))->toBe('completed');
    });
});

describe('Storage readiness failure', function (): void {
    it('reports a failed health check when a storage write returns false', function (): void {
        $action = resolve(GetSystemHealth::class);
        config(['operations.backup_enabled' => false]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->andReturn(true);
        Storage::partialMock()->shouldReceive('disk')->withNoArgs()->andReturn($disk);
        $checks = collect($action->handle())->keyBy('name');
        expect($checks['storage'])->toBe(['name' => 'storage', 'status' => 'failed', 'detail' => 'unavailable']);
    });
});
