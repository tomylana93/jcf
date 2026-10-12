<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class WriteBackup
{
    public function handle(): string
    {
        $filename = 'backup-'.now()->format('Ymd-Hisu').'.zip';
        Cache::put('system.backup_status', 'running', 86400);
        try {
            $exit = Artisan::call('backup:run', ['--filename' => $filename, '--no-interaction' => true]);
            $path = config('backup.backup.name').'/'.$filename;
            if ($exit !== 0 || ! Storage::disk('backups')->exists($path)) {
                throw new RuntimeException(__('backup.error.backup_failed'));
            }
            Cache::put('system.backup_status', 'completed', 86400);

            return $filename;
        } catch (Throwable $exception) {
            Cache::put('system.backup_status', 'failed', 86400);
            throw $exception;
        }
    }
}
