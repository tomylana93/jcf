<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use App\Enums\BackupOperation;
use App\Enums\OperationStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class WriteBackup
{
    public function handle(): string
    {
        $filename = 'backup-'.now()->format('Ymd-Hisu').'.zip';
        BackupOperation::Backup->mark(OperationStatus::Running);
        try {
            $exit = Artisan::call('backup:run', ['--filename' => $filename, '--no-interaction' => true]);
            $path = GetBackupPath::directory().'/'.$filename;
            if ($exit !== 0 || ! Storage::disk('backups')->exists($path)) {
                throw new RuntimeException(__('backup.error.backup_failed'));
            }
            BackupOperation::Backup->mark(OperationStatus::Completed);

            return $filename;
        } catch (Throwable $exception) {
            BackupOperation::Backup->mark(OperationStatus::Failed);
            throw $exception;
        }
    }
}
