<?php

declare(strict_types=1);

use App\Actions\MasterData\Backup\CreateBackup;
use App\Actions\MasterData\Backup\ExtractBackup;
use App\Actions\MasterData\Backup\GetBackupChecksum;
use App\Actions\MasterData\Backup\RestoreBackup;
use Illuminate\Process\Factory;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function Pest\Laravel\artisan;

describe('Backup archive validation', function (): void {
    it('rejects a non-string checksum filename', function (): void {
        Storage::fake('backups');

        artisan('system:backup-checksum', ['backup' => ['test.zip']])
            ->expectsOutput('The backup filename must be a string.')
            ->assertFailed();
    });

    it('extracts encrypted application files and reports the stored checksum', function (): void {
        Storage::fake('backups');
        config(['backup.backup.password' => 'test-secret']);
        $archive = createBackupTestArchive(['storage/app/public/' => '', 'storage/app/public/avatar.jpg' => 'image', 'db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'test-secret');

        $directory = resolve(ExtractBackup::class)->handle(...array_values($archive));

        try {
            expect(File::get($directory->path('storage/app/public/avatar.jpg')))->toBe('image');
            artisan('system:backup-checksum', ['backup' => $archive['filename']])->expectsOutput($archive['checksum'])->assertSuccessful();
        } finally {
            $directory->delete();
        }
    });
    it('rejects invalid filenames and missing or mismatched archives', function (): void {
        Storage::fake('backups');

        expect(fn () => resolve(ExtractBackup::class)->handle('../test.zip', str_repeat('0', 64)))->toThrow(RuntimeException::class, 'Invalid backup filename.')
            ->and(fn () => resolve(GetBackupChecksum::class)->handle('../test.zip'))->toThrow(RuntimeException::class, 'Invalid backup filename.');

        artisan('system:backup-checksum', ['backup' => 'missing.zip'])->assertFailed();

        expect(fn () => resolve(ExtractBackup::class)->handle('missing.zip', str_repeat('0', 64)))->toThrow(RuntimeException::class, 'Backup checksum mismatch');

        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;']);

        expect(fn () => resolve(ExtractBackup::class)->handle($archive['filename'], str_repeat('0', 64)))->toThrow(RuntimeException::class, 'Backup checksum mismatch');
    });
    it('rejects files that are not zip archives', function (): void {
        Storage::fake('backups')->put('jcf/test.zip', 'not a zip');

        expect(fn () => resolve(ExtractBackup::class)->handle('test.zip', hash('sha256', 'not a zip')))->toThrow(RuntimeException::class, 'Invalid backup archive.');
    });
    it('rejects unsafe archive paths before restoring a database', function (string $path): void {
        Storage::fake('backups');
        $archive = createBackupTestArchive([$path => 'unsafe']);

        expect(fn () => resolve(ExtractBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'Unsafe or unsupported backup entry.');
    })->with(['storage/app/../../outside.txt', '/storage/app/private/file.txt', 'storage\\app\\file.txt', 'storage/app/C:drive', '.env', 'db-dumps/other.dump']);
    it('rejects excessive expanded size and symlinks', function (): void {
        $disk = Storage::fake('backups');
        config(['jalara.operations.restore_max_bytes' => 3]);
        $archive = createBackupTestArchive(['storage/app/public/test.txt' => 'four']);

        expect(fn () => resolve(ExtractBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'Unsafe or unsupported backup entry.');

        config(['jalara.operations.restore_max_bytes' => 10000]);
        $zip = new ZipArchive;
        $path = $disk->path('jcf/test.zip');
        $zip->open($path);
        $zip->setExternalAttributesName('storage/app/public/test.txt', ZipArchive::OPSYS_UNIX, 0120777 << 16);
        $zip->close();

        expect(fn () => resolve(ExtractBackup::class)->handle('test.zip', hash_file('sha256', $path)))->toThrow(RuntimeException::class, 'Archive symlinks are not allowed.');
    });
    it('rejects an incorrect archive password', function (): void {
        Storage::fake('backups');
        config(['backup.backup.password' => 'wrong']);
        $archive = createBackupTestArchive(['storage/app/public/file.txt' => 'secret'], 'correct');

        expect(fn () => resolve(ExtractBackup::class)->handle(...array_values($archive)))->toThrow(RuntimeException::class, 'Archive password or content is invalid.');
    });
});

describe('Isolated restore', function (): void {
    it('rejects non-string restore filenames and connections', function (array $parameters): void {
        Storage::fake('backups');

        artisan('system:restore', [...$parameters, '--confirm' => true, '--sha256' => str_repeat('0', 64)])
            ->expectsOutput('The backup filename and target connection must be strings.')
            ->assertFailed();
    })->with([
        'filename' => [['backup' => ['test.zip'], '--connection' => 'backup_restore']],
        'connection' => [['backup' => 'test.zip', '--connection' => ['backup_restore']]],
    ]);

    it('requires explicit confirmation and a checksum', function (): void {
        artisan('system:restore', ['backup' => 'test.zip'])->assertFailed();
        artisan('system:restore', ['backup' => 'test.zip', '--confirm' => true, '--sha256' => 'invalid'])->assertFailed();
    });
    it('refuses the active connection, aliases and unsupported database engines', function (): void {
        $action = resolve(RestoreBackup::class);

        expect(fn () => $action->handle('test.zip', 'checksum', config('database.default')))->toThrow(RuntimeException::class, 'isolated target connection')
            ->and(fn () => $action->handle('test.zip', 'checksum', 'missing'))->toThrow(RuntimeException::class, 'isolated target connection');

        config(['database.connections.source_alias' => DB::connection()->getConfig()]);

        expect(fn () => $action->handle('test.zip', 'checksum', 'source_alias'))->toThrow(RuntimeException::class, 'separate PostgreSQL')
            ->and(fn () => $action->handle('test.zip', 'checksum', 'sqlite'))->toThrow(RuntimeException::class, 'separate PostgreSQL');
    });
    it('refuses nonempty restore targets', function (): void {
        withIsolatedRestoreDatabase(function (string $connection): void {
            DB::connection($connection)->statement('CREATE TABLE protected_data (id INTEGER)');
            expect(fn () => resolve(RestoreBackup::class)->handle('test.zip', 'checksum', $connection))->toThrow(RuntimeException::class, 'Restore target must be empty.')
                ->and(DB::connection($connection)->getSchemaBuilder()->hasTable('protected_data'))->toBeTrue();
        });
    });
    it('stages files and imports a dump through a transactional password-free command', function (): void {
        Storage::fake('backups');
        $archive = createBackupTestArchive(['storage/app/public/image.txt' => 'image', 'db-dumps/postgresql-pgsql.sql' => 'SELECT 1;']);
        Process::fake(['*psql*' => Process::result()]);
        withIsolatedRestoreDatabase(function (string $connection) use ($archive): void {
            $directory = resolve(RestoreBackup::class)->handle($archive['filename'], $archive['checksum'], $connection);
            try {
                expect(File::get($directory.'/storage/app/public/image.txt'))->toBe('image');
                Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command) && $process->command[0] === 'psql' && in_array('--single-transaction', $process->command, true) && in_array('--set=ON_ERROR_STOP=1', $process->command, true) && in_array('--file=-', $process->command, true));
            } finally {
                File::deleteDirectory($directory);
            }
        });
    });
    it('discards staging when a dump is missing or database import fails', function (): void {
        Storage::fake('backups');
        withIsolatedRestoreDatabase(function (string $connection): void {
            $archive = createBackupTestArchive(['storage/app/public/image.txt' => 'image']);
            expect(fn () => resolve(RestoreBackup::class)->handle($archive['filename'], $archive['checksum'], $connection))->toThrow(RuntimeException::class, 'source database dump is missing');
            $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'invalid SQL;']);
            Process::fake(['*psql*' => Process::result(exitCode: 1)]);
            artisan('system:restore', ['backup' => $archive['filename'], '--sha256' => $archive['checksum'], '--confirm' => true, '--connection' => $connection])->assertFailed();
            Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command) && $process->command[0] === 'psql');
        });
    });
    it('returns a successful CLI result and the isolated staging path', function (): void {
        Storage::fake('backups');
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;']);
        Process::fake(['*psql*' => Process::result()]);
        withIsolatedRestoreDatabase(function (string $connection) use ($archive): void {
            artisan('system:restore', ['backup' => $archive['filename'], '--sha256' => $archive['checksum'], '--confirm' => true, '--connection' => $connection])->assertSuccessful();
            Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command) && $process->command[0] === 'psql');
        });
    });
});

describe('Linux backup and restore integration', function (): void {
    it('rolls back the entire import when SQL fails after creating a table', function (): void {
        if (PHP_OS_FAMILY === 'Windows' || getenv('BACKUP_INTEGRATION') !== '1') {
            test()->markTestSkipped('Native backup integration runs in Linux CI with PostgreSQL tools.');
        }
        Storage::fake('backups');
        Process::swap(new Factory);
        $archive = createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'CREATE TABLE partial_restore (id INTEGER); SELECT * FROM missing_restore_table;']);
        withIsolatedRestoreDatabase(function (string $connection) use ($archive): void {
            expect(fn () => resolve(RestoreBackup::class)->handle($archive['filename'], $archive['checksum'], $connection))->toThrow(RuntimeException::class, 'Database restore failed; the target transaction was rolled back.')
                ->and(DB::connection($connection)->getSchemaBuilder()->getTables())->toBeEmpty();
        });
    });
    it('round trips a real PostgreSQL dump and encrypted uploads', function (): void {
        if (PHP_OS_FAMILY === 'Windows' || getenv('BACKUP_INTEGRATION') !== '1') {
            test()->markTestSkipped('Native backup integration runs in Linux CI with PostgreSQL tools.');
        }
        Storage::fake('backups');
        Event::listen(BackupHasFailed::class, static function (BackupHasFailed $event): void {
            throw $event->exception;
        });
        $workspace = new TemporaryDirectory(storage_path('framework/testing/backup-source'))->create();
        File::ensureDirectoryExists($workspace->path('storage/app/public'));
        File::put($workspace->path('storage/app/public/roundtrip.txt'), 'verified-upload');
        $originalDefault = config('database.default');
        try {
            withIsolatedRestoreDatabase(function (string $source) use ($workspace, $originalDefault): void {
                DB::connection($source)->statement('CREATE TABLE settings (id SERIAL PRIMARY KEY, "group" VARCHAR(255), name VARCHAR(255), locked BOOLEAN DEFAULT FALSE, payload TEXT, created_at TIMESTAMP, updated_at TIMESTAMP)');
                DB::connection($source)->table('settings')->insert(['group' => 'backup', 'name' => 'enabled', 'payload' => 'true']);
                DB::connection($source)->statement('CREATE TABLE roundtrip_data (id INTEGER PRIMARY KEY, label TEXT NOT NULL)');
                DB::connection($source)->table('roundtrip_data')->insert(['id' => 42, 'label' => 'verified-database']);
                config([
                    'database.default' => $source,
                    'backup.backup.source.databases' => [$source],
                    'backup.backup.source.files.include' => [$workspace->path('storage/app/public')],
                    'backup.backup.source.files.exclude' => [],
                    'backup.backup.source.files.relative_path' => $workspace->path(),
                    'backup.backup.password' => 'integration-only-test-secret',
                    'features.backup' => true,
                ]);
                Config::rebind();
                Process::swap(new Factory);
                try {
                    $filename = resolve(CreateBackup::class)->handle();
                    $checksum = resolve(GetBackupChecksum::class)->handle($filename);
                    withIsolatedRestoreDatabase(function (string $target) use ($filename, $checksum): void {
                        $path = resolve(RestoreBackup::class)->handle($filename, $checksum, $target);
                        try {
                            expect(DB::connection($target)->table('roundtrip_data')->sole()->label)->toBe('verified-database')
                                ->and(File::get($path.'/storage/app/public/roundtrip.txt'))->toBe('verified-upload');
                        } finally {
                            File::deleteDirectory($path);
                        }
                    }, 'restore_target');
                } finally {
                    config(['database.default' => $originalDefault]);
                }
            }, 'restore_source');
        } finally {
            config(['database.default' => $originalDefault]);
            $workspace->delete();
        }
    });
});
