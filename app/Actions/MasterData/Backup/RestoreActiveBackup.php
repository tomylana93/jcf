<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

final readonly class RestoreActiveBackup
{
    public function __construct(private ExtractBackup $extractBackup, private WriteBackup $writeBackup, private ReplaceBackupFiles $replaceBackupFiles, private ImportActiveDatabase $importActiveDatabase, private PermissionRegistrar $permissionRegistrar, private GetBackupLock $getBackupLock) {}

    public function handle(string $filename, string $checksum): void
    {
        throw_unless(config('operations.backup_enabled') && config('backup.backup.password'), RuntimeException::class, __('backup.error.not_configured'));
        $lock = $this->getBackupLock->handle();
        throw_unless($lock->get(), RuntimeException::class, __('backup.error.busy'));
        try {
            throw_if(app()->isDownForMaintenance(), RuntimeException::class, 'The application is already in maintenance mode.');
            throw_if(DB::connection()->getDriverName() !== 'pgsql' || DB::connection()->getConfig('search_path') !== 'public', RuntimeException::class, 'Active restore requires PostgreSQL with the public schema.');
            $directory = $this->extractBackup->handle($filename, $checksum, true);
            try {
                $sqlPath = $directory->path('db-dumps/postgresql-'.config('database.default').'.sql');
                throw_unless(is_file($sqlPath), RuntimeException::class, 'The source database dump is missing.');
                app()->maintenanceMode()->activate(['time' => now()->timestamp, 'retry' => 60, 'status' => 503]);
                Cache::store('file')->put('system.restore_status', 'running', 86400);
                $this->writeBackup->handle();
                $this->replaceBackupFiles->handle($directory, fn () => $this->importActiveDatabase->handle($sqlPath));
                Cache::flush();
                $this->permissionRegistrar->forgetCachedPermissions();
                Cache::store('file')->put('system.restore_status', 'completed', 86400);
                app()->maintenanceMode()->deactivate();
                $directory->delete();
            } catch (Throwable $exception) {
                Cache::store('file')->put('system.restore_status', 'failed', 86400);
                throw $exception;
            }
        } finally {
            $lock->release();
        }
    }
}
