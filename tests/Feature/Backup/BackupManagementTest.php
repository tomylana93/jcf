<?php

declare(strict_types=1);

use App\Actions\MasterData\Backup\CreateBackup;
use App\Actions\MasterData\Backup\DeleteBackup;
use App\Actions\MasterData\Backup\GetBackupLock;
use App\Actions\MasterData\Backup\GetDatabaseDumpInput;
use App\Actions\MasterData\Backup\ImportActiveDatabase;
use App\Actions\MasterData\Backup\RestoreActiveBackup;
use App\Enums\Role;
use App\Jobs\CreateSystemBackup;
use App\Jobs\RestoreSystemBackup;
use App\Models\User;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\Factory;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Backup\Config\Config;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;
use function Pest\Laravel\withSession;

describe('Backup management', function (): void {
    it('requires authentication and backup permission for browsing upload and restore', function (): void {
        get(route('master-data.backups.index'))->assertRedirect(route('login'));
        post(route('master-data.backups.upload'))->assertRedirect(route('login'));
        post(route('master-data.backups.restore', 'test.zip'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        actingAsWithConfirmedPassword($user);

        get(route('master-data.backups.index'))->assertForbidden();
        post(route('master-data.backups.upload'))->assertForbidden();
        post(route('master-data.backups.restore', 'test.zip'))->assertForbidden();
    });
    it('protects deletion with authentication permission and password confirmation', function (): void {
        $disk = Storage::fake('backups');
        $disk->put('jcf/test.zip', 'archive');
        $url = route('master-data.backups.destroy', 'test.zip');

        delete($url)->assertRedirect(route('login'));

        $user = User::factory()->create();
        actingAsWithConfirmedPassword($user);

        delete($url)->assertForbidden();

        $user->syncRoles(Role::SuperAdmin);
        withSession(['auth.password_confirmed_at' => 0]);

        delete($url)->assertRedirect(route('password.confirm'));

        $disk->assertExists('jcf/test.zip');
    });

    it('deletes only the selected archive even when backup creation is disabled', function (): void {
        $disk = Storage::fake('backups');
        $disk->put('jcf/test.zip', 'archive');
        $disk->put('jcf/keep.zip', 'archive');
        config(['operations.backup_enabled' => false]);
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);

        delete(route('master-data.backups.destroy', 'test.zip'))->assertRedirect()->assertSessionHasNoErrors();

        $disk->assertMissing('jcf/test.zip');
        $disk->assertExists('jcf/keep.zip');

        delete(route('master-data.backups.destroy', 'test.zip'))->assertNotFound();
        delete(route('master-data.backups.destroy', 'keep.txt'))->assertNotFound();
    });

    it('preserves archives while a backup or restore holds the lock', function (): void {
        $disk = Storage::fake('backups');
        $disk->put('jcf/test.zip', 'archive');
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);

        $lock = resolve(GetBackupLock::class)->handle();

        $lock->get();
        try {
            delete(route('master-data.backups.destroy', 'test.zip'))->assertSessionHasErrors(['backup' => __('backup.error.busy')]);
            $disk->assertExists('jcf/test.zip');
        } finally {
            $lock->release();
        }
    });

    it('reports storage deletion failures and releases the backup lock', function (): void {
        $disk = Mockery::mock(FilesystemAdapter::class);
        Storage::shouldReceive('disk')->with('backups')->once()->andReturn($disk);
        $disk->shouldReceive('exists')->with('jcf/test.zip')->once()->andReturnTrue();
        $disk->shouldReceive('delete')->with('jcf/test.zip')->once()->andReturnFalse();
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);

        delete(route('master-data.backups.destroy', 'test.zip'))->assertSessionHasErrors(['backup' => __('backup.error.delete_failed')]);

        $lock = resolve(GetBackupLock::class)->handle();

        expect($lock->get())->toBeTrue();

        $lock->release();
    });

    it('rejects unsafe filenames before accessing backup storage', function (): void {
        expect(fn () => resolve(DeleteBackup::class)->handle('../test.zip'))
            ->toThrow(HttpException::class);
    });

    it('lets a backup manager enter master data without user-view permission', function (): void {
        artisan('init:permission')->assertSuccessful();

        $user = User::factory()->create();
        $user->syncRoles([]);
        $user->givePermissionTo('backups.manage');
        actingAs($user)->get(route('master-data.index'))->assertOk();

        get(route('master-data.backups.index'))->assertOk();

        $user->revokePermissionTo('backups.manage');

        get(route('master-data.index'))->assertForbidden();
    });
    it('filters sorts and paginates backup archives', function (): void {
        $disk = Storage::fake('backups');
        foreach (range(1, 12) as $index) {
            $disk->put('jcf/archive-'.sprintf('%02d', $index).'.zip', str_repeat('x', $index));
        }
        $user = User::factory()->create();
        $user->syncRoles(Role::SuperAdmin);
        actingAs($user)->get(route('master-data.backups.index', ['sort' => '-size', 'page' => 2, 'per_page' => 10]))->assertInertia(fn (Assert $page) => $page->where('backups.total', 12)->has('backups.data', 2)->where('backups.data.0.filename', 'archive-02.zip'));

        get(route('master-data.backups.index', ['filter' => ['search' => 'ARCHIVE-12'], 'sort' => 'filename']))->assertInertia(fn (Assert $page) => $page->where('backups.total', 1)->where('backups.data.0.filename', 'archive-12.zip'));
        get(route('master-data.backups.index', ['sort' => 'untrusted']))->assertSessionHasErrors('sort');
    });
    it('requires password confirmation before uploading or restoring', function (): void {
        $user = actingAsSuperAdmin();

        post(route('master-data.backups.upload'))->assertRedirect(route('password.confirm'));
        post(route('master-data.backups.restore', 'test.zip'))->assertRedirect(route('password.confirm'));
    });
    it('validates backup upload configuration and required files', function (): void {
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);
        config(['operations.backup_enabled' => false]);

        post(route('master-data.backups.upload'))->assertSessionHasErrors(['archive' => __('backup.error.not_configured')]);

        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);

        post(route('master-data.backups.upload'))->assertSessionHasErrors('archive');
        post(route('master-data.backups.upload'), ['archive' => UploadedFile::fake()->create('image.txt', 1, 'text/plain')])->assertSessionHasErrors('archive');
    });
    it('accepts an encrypted archive under a generated private filename', function (): void {
        $disk = Storage::fake('backups');
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;', 'storage/app/public/avatar.txt' => 'image'], 'test-secret');
        $upload = new UploadedFile($disk->path('jcf/test.zip'), 'external.zip', test: true);
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);

        post(route('master-data.backups.upload'), ['archive' => $upload])->assertSessionHasNoErrors()->assertRedirect();

        $files = $disk->files('jcf');

        expect($files)->toHaveCount(2)
            ->and(collect($files)->filter(fn (string $path): bool => str_starts_with(basename($path), 'upload-')))->toHaveCount(1);
    });
    it('rejects invalid unsafe incomplete unencrypted and wrong-password uploads without retaining them', function (array $entries, ?string $password): void {
        $disk = Storage::fake('backups');
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        createBackupTestArchive($entries, $password);
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);

        post(route('master-data.backups.upload'), ['archive' => new UploadedFile($disk->path('jcf/test.zip'), 'test.zip', test: true)])->assertSessionHasErrors(['archive' => __('backup.error.invalid_archive')]);

        expect($disk->files('jcf'))->toBe(['jcf/test.zip']);
    })->with([
        'unsafe' => [['storage/app/public/../unsafe.txt' => 'bad'], 'test-secret'],
        'missing dump' => [['storage/app/public/image.txt' => 'image'], 'test-secret'],
        'wrong password' => [['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'wrong'],
        'unencrypted' => [['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], null],
    ]);
    it('validates restore configuration confirmation and archive existence before queuing', function (): void {
        $disk = Storage::fake('backups');
        $user = actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);
        config(['operations.backup_enabled' => false]);

        post(route('master-data.backups.restore', 'test.zip'))->assertSessionHasErrors(['backup' => __('backup.error.not_configured')]);

        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);

        post(route('master-data.backups.restore', 'test.zip'), ['confirmation' => false])->assertSessionHasErrors('confirmation');
        post(route('master-data.backups.restore', 'test.zip'), ['confirmation' => true])->assertNotFound();
        travel(61)->seconds();

        $disk->put('jcf/test.zip', 'archive');
        Queue::fake([RestoreSystemBackup::class]);

        post(route('master-data.backups.restore', 'test.zip'), ['confirmation' => true])->assertSessionHasNoErrors()->assertRedirect();

        Queue::assertPushed(RestoreSystemBackup::class, fn (RestoreSystemBackup $job): bool => $job->filename === 'test.zip' && $job->checksum === hash('sha256', 'archive') && $job->actorId === $user->id && $job->connection === 'backups');
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

describe('Active application restore', function (): void {
    it('creates a safety archive replaces files imports the database and resumes the application', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        File::ensureDirectoryExists($workspace->path('active').'/private');
        File::put($workspace->path('active/private/old.txt'), 'old');
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;', 'storage/app/public/new.txt' => 'new'], 'test-secret');
        fakeSafetyBackup();
        Process::fake(['*psql*' => Process::result()]);
        $user = User::factory()->create();
        try {
            new RestoreSystemBackup($archive['filename'], $archive['checksum'], $user->id)->handle(resolve(RestoreActiveBackup::class));
            expect(File::get($workspace->path('active/public/new.txt')))->toBe('new')
                ->and(File::exists($workspace->path('active/private/old.txt')))->toBeFalse()
                ->and(app()->isDownForMaintenance())->toBeFalse()
                ->and(Cache::store('file')->get('system.restore_status'))->toBe('completed')
                ->and($user->notifications()->sole()->data['kind'])->toBe('restore_ready');
            Process::assertRan(fn (PendingProcess $process): bool => in_array('--single-transaction', $process->command, true) && in_array('--command=DROP SCHEMA public CASCADE; CREATE SCHEMA public;', $process->command, true));
        } finally {
            $workspace->delete();
        }
    });
    it('rolls back files and keeps maintenance enabled when database restore fails', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        foreach (['private', 'public'] as $name) {
            File::ensureDirectoryExists($workspace->path('active/'.$name));
            File::put($workspace->path('active/'.$name.'/old.txt'), 'original');
        }
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'invalid SQL;', 'storage/app/public/new.txt' => 'new'], 'test-secret');
        fakeSafetyBackup();
        Process::fake(['*psql*' => Process::result(exitCode: 1)]);
        try {
            expect(fn () => resolve(RestoreActiveBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'active database transaction was rolled back')
                ->and(File::get($workspace->path('active/public/old.txt')))->toBe('original')
                ->and(File::get($workspace->path('active/private/old.txt')))->toBe('original')
                ->and(File::exists($workspace->path('active/public/new.txt')))->toBeFalse()
                ->and(app()->isDownForMaintenance())->toBeTrue()
                ->and(Cache::store('file')->get('system.restore_status'))->toBe('failed')
                ->and(File::directories($workspace->path('staging')))->toBeEmpty();
            Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command) && $process->command[0] === 'psql');
        } finally {
            $workspace->delete();
        }
    });
    it('refuses disabled backups concurrent operations existing maintenance and unsupported databases', function (): void {
        $workspace = isolateBackupOperations();
        $action = resolve(RestoreActiveBackup::class);
        try {
            config(['operations.backup_enabled' => false]);
            expect(fn () => $action->handle('test.zip', 'checksum'))->toThrow(RuntimeException::class, __('backup.error.not_configured'));
            config(['operations.backup_enabled' => true]);
            $lock = resolve(GetBackupLock::class)->handle();
            $lock->get();
            try {
                expect(fn () => $action->handle('test.zip', 'checksum'))->toThrow(RuntimeException::class, __('backup.error.busy'));
            } finally {
                $lock->release();
            }
            app()->maintenanceMode()->activate([]);
            expect(fn () => $action->handle('test.zip', 'checksum'))->toThrow(RuntimeException::class, 'already in maintenance');
            app()->maintenanceMode()->deactivate();
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
            expect(fn () => $action->handle('test.zip', 'checksum'))->toThrow(RuntimeException::class, 'public schema');
        } finally {
            $workspace->delete();
        }
    });
    it('refuses archives without the configured source dump before maintenance or safety backup', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        $archive = createBackupTestArchive(['storage/app/public/image.txt' => 'image'], 'test-secret');
        try {
            expect(fn () => resolve(RestoreActiveBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'source database dump is missing')
                ->and(app()->isDownForMaintenance())->toBeFalse();
        } finally {
            $workspace->delete();
        }
    });
    it('leaves active files untouched when the safety backup fails', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'test-secret');
        Artisan::shouldReceive('call')->once()->andReturn(1);
        try {
            expect(fn () => resolve(RestoreActiveBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, __('backup.error.backup_failed'))
                ->and(app()->isDownForMaintenance())->toBeTrue();
            Process::assertNothingRan();
        } finally {
            $workspace->delete();
        }
    });
    it('reports failed restore jobs to existing requesters and handles removed requesters', function (): void {
        $user = User::factory()->create();
        new RestoreSystemBackup('test.zip', 'checksum', $user->id)->failed(new RuntimeException('failure'));

        expect($user->notifications()->sole()->data['kind'])->toBe('restore_failed')
            ->and(Cache::store('file')->get('system.restore_status'))->toBe('failed');

        new RestoreSystemBackup('test.zip', 'checksum', '00000000-0000-0000-0000-000000000000')->failed(null);
    });
    it('records queued restore failures notifies the requester and permits another restore', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        config(['queue.connections.backups.after_commit' => false]);
        $user = User::factory()->create();

        try {
            dispatch(new RestoreSystemBackup('missing.zip', 'checksum', $user->id));
            expect(DB::table('jobs')->where('queue', 'backups')->count())->toBe(1);

            artisan('queue:work', ['connection' => 'backups', '--queue' => 'backups', '--once' => true, '--force' => true, '--tries' => 1, '--sleep' => 0])->assertSuccessful();

            expect(DB::table('jobs')->where('queue', 'backups')->count())->toBe(0)
                ->and(DB::table('failed_jobs')->count())->toBe(1)
                ->and($user->notifications()->sole()->data['kind'])->toBe('restore_failed')
                ->and(Cache::store('file')->get('system.restore_status'))->toBe('failed');

            dispatch(new RestoreSystemBackup('missing.zip', 'checksum', $user->id));
            expect(DB::table('jobs')->where('queue', 'backups')->count())->toBe(1);
        } finally {
            $workspace->delete();
        }
    });
    it('keeps the completion notification queued when restore resets queue identifiers', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        config(['queue.connections.backups.after_commit' => false]);
        $user = User::factory()->create();
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'test-secret');
        $console = Artisan::getFacadeRoot();
        fakeSafetyBackup();
        Process::fake(['*psql*' => function (): ProcessResult {
            DB::statement('TRUNCATE TABLE jobs RESTART IDENTITY');

            return Process::result();
        }]);
        DB::statement('TRUNCATE TABLE jobs RESTART IDENTITY');

        try {
            dispatch(new RestoreSystemBackup($archive['filename'], $archive['checksum'], $user->id));
            expect($console->call('queue:work', ['connection' => 'backups', '--queue' => 'backups', '--once' => true, '--force' => true, '--tries' => 1, '--sleep' => 0]))->toBe(0)
                ->and(DB::table('jobs')->where('queue', 'backups')->count())->toBe(0)
                ->and(DB::table('jobs')->where('queue', 'notifications')->count())->toBe(1)
                ->and($user->notifications()->sole()->data['kind'])->toBe('restore_ready');
            Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command) && $process->command[0] === 'psql');
        } finally {
            $workspace->delete();
        }
    });
    it('preserves staged original files when file rollback fails', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        File::ensureDirectoryExists($workspace->path('active/private'));
        File::put($workspace->path('active/private/old.txt'), 'original');
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'invalid SQL;'], 'test-secret');
        fakeSafetyBackup();
        Process::fake(['*psql*' => Process::result(exitCode: 1)]);
        $filesystem = File::getFacadeRoot();
        File::partialMock()->shouldReceive('moveDirectory')->andReturnUsing(
            fn (string $from, string $to): bool => str_contains($from, '/previous/private') ? false : $filesystem->moveDirectory($from, $to),
        );

        try {
            expect(fn () => resolve(RestoreActiveBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'File rollback failed');
            $staging = File::directories($workspace->path('staging'));
            expect($staging)->toHaveCount(1)
                ->and(File::get($staging[0].'/previous/private/old.txt'))->toBe('original')
                ->and(app()->isDownForMaintenance())->toBeTrue();
            Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command) && $process->command[0] === 'psql');
        } finally {
            $workspace->delete();
        }
    });
    it('rejects symlinked active storage without replacing it', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        File::ensureDirectoryExists($workspace->path('external'));
        symlink($workspace->path('external'), $workspace->path('active').'/private');
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'test-secret');
        fakeSafetyBackup();
        try {
            expect(fn () => resolve(RestoreActiveBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'symlinked application storage')
                ->and(is_link($workspace->path('active').'/private'))->toBeTrue();
            Process::assertNothingRan();
        } finally {
            $workspace->delete();
        }
    });
});

describe('Restore cutover boundaries', function (): void {
    it('uses the schema declaration from the dump when present', function (): void {
        $workspace = isolateBackupOperations();
        $sql = $workspace->path('schema.sql');
        File::put($sql, "CREATE SCHEMA public;\nSELECT 1;\n");
        Process::fake(['*psql*' => Process::result()]);
        try {
            resolve(ImportActiveDatabase::class)->handle($sql);
            Process::assertRan(fn (PendingProcess $process): bool => in_array('--command=DROP SCHEMA public CASCADE;', $process->command, true));
        } finally {
            $workspace->delete();
        }
    });
    it('restores an already-swapped folder when another folder cannot be staged', function (): void {
        Storage::fake('backups');
        $workspace = isolateBackupOperations();
        foreach (['private', 'public'] as $name) {
            File::ensureDirectoryExists($workspace->path('active').'/'.$name);
            File::put($workspace->path('active/'.$name.'/old.txt'), 'original');
        }
        $filesystem = File::getFacadeRoot();
        File::partialMock()->shouldReceive('moveDirectory')->andReturnUsing(fn (string $from, string $to): bool => $from === $workspace->path('active').'/public' ? false : $filesystem->moveDirectory($from, $to));
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;', 'storage/app/private/new.txt' => 'new'], 'test-secret');
        fakeSafetyBackup();
        try {
            expect(fn () => resolve(RestoreActiveBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'Cannot stage the current application files.')
                ->and(File::get($workspace->path('active/private/old.txt')))->toBe('original')
                ->and(File::get($workspace->path('active/public/old.txt')))->toBe('original');
            Process::assertNothingRan();
        } finally {
            $workspace->delete();
        }
    });
    it('replaces a real PostgreSQL schema and removes historical queue and session records', function (): void {
        if (PHP_OS_FAMILY === 'Windows' || getenv('BACKUP_INTEGRATION') !== '1') {
            test()->markTestSkipped('Native restore integration runs in Linux CI with PostgreSQL tools.');
        }
        $original = config('database.default');
        $workspace = isolateBackupOperations();
        Process::swap(new Factory);
        try {
            withIsolatedRestoreDatabase(function (string $connection) use ($workspace, $original): void {
                DB::connection($connection)->statement('CREATE TABLE old_data (id INTEGER)');
                $sql = $workspace->path('source.sql');
                File::put($sql, "CREATE SCHEMA public;\nCREATE TABLE public.restored_data (id INTEGER, label TEXT);\nINSERT INTO public.restored_data VALUES (42, 'restored');\nCREATE TABLE public.jobs (id INTEGER); INSERT INTO public.jobs VALUES (1);\nCREATE TABLE public.sessions (id TEXT); INSERT INTO public.sessions VALUES ('old-session');");
                config(['database.default' => $connection]);
                try {
                    resolve(ImportActiveDatabase::class)->handle($sql);
                    expect(DB::connection($connection)->getSchemaBuilder()->hasTable('old_data'))->toBeFalse()
                        ->and(DB::connection($connection)->table('restored_data')->sole()->label)->toBe('restored')
                        ->and(DB::connection($connection)->table('jobs')->count())->toBe(0)
                        ->and(DB::connection($connection)->table('sessions')->count())->toBe(0);
                } finally {
                    config(['database.default' => $original]);
                }
            });
        } finally {
            config(['database.default' => $original]);
            $workspace->delete();
        }
    });
    it('preserves the original PostgreSQL schema when native import fails', function (): void {
        if (PHP_OS_FAMILY === 'Windows' || getenv('BACKUP_INTEGRATION') !== '1') {
            test()->markTestSkipped('Native restore integration runs in Linux CI with PostgreSQL tools.');
        }
        $original = config('database.default');
        $workspace = isolateBackupOperations();
        Process::swap(new Factory);
        try {
            withIsolatedRestoreDatabase(function (string $connection) use ($workspace, $original): void {
                DB::connection($connection)->statement('CREATE TABLE protected_data (id INTEGER)');
                DB::connection($connection)->table('protected_data')->insert(['id' => 7]);
                $sql = $workspace->path('bad.sql');
                File::put($sql, 'CREATE TABLE partial_restore (id INTEGER); SELECT * FROM missing_restore_table;');
                config(['database.default' => $connection]);
                try {
                    expect(fn () => resolve(ImportActiveDatabase::class)->handle($sql))->toThrow(RuntimeException::class, 'active database transaction was rolled back')
                        ->and(DB::connection($connection)->table('protected_data')->sole()->id)->toBe(7)
                        ->and(DB::connection($connection)->getSchemaBuilder()->hasTable('partial_restore'))->toBeFalse();
                } finally {
                    config(['database.default' => $original]);
                }
            });
        } finally {
            config(['database.default' => $original]);
            $workspace->delete();
        }
    });
});

describe('PostgreSQL dump compatibility', function (): void {
    it('omits an unsupported header setting while preserving SQL and COPY data', function (): void {
        $workspace = isolateBackupOperations();
        $sql = $workspace->path('dump.sql');
        File::put($sql, "SET transaction_timeout = 0;\nCREATE TABLE demo (value text);\nCOPY demo (value) FROM stdin;\nSET transaction_timeout = 0;\n\\.\n");
        try {
            $input = resolve(GetDatabaseDumpInput::class);
            expect(implode('', iterator_to_array($input->handle($sql, false))))->toStartWith("CREATE TABLE demo (value text);\n")
                ->toContain("COPY demo (value) FROM stdin;\nSET transaction_timeout = 0;\n\\.\n")
                ->and(implode('', iterator_to_array($input->handle($sql, true))))->toStartWith("SET transaction_timeout = 0;\n");
        } finally {
            $workspace->delete();
        }
    });
});
