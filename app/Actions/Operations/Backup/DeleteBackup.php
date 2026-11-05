<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class DeleteBackup
{
    public function __construct(private GetBackupLock $getBackupLock, private GetBackupPath $getBackupPath) {}

    public function handle(string $filename): void
    {
        $path = $this->getBackupPath->handle($filename);
        $lock = $this->getBackupLock->handle();
        throw_unless($lock->get(), ValidationException::withMessages(['backup' => __('backup.error.busy')]));

        try {
            $disk = Storage::disk('backups');
            throw_unless($disk->exists($path), RecordsNotFoundException::class, 'Backup archive missing.');
            throw_unless($disk->delete($path), ValidationException::withMessages(['backup' => __('backup.error.delete_failed')]));
        } finally {
            $lock->release();
        }
    }
}
