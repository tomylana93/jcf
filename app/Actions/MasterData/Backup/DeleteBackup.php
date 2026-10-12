<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class DeleteBackup
{
    public function __construct(private GetBackupLock $getBackupLock) {}

    public function handle(string $filename): void
    {
        abort_unless(preg_match('/^[A-Za-z0-9-]+\.zip$/D', $filename) === 1, 404);
        $lock = $this->getBackupLock->handle();
        throw_unless($lock->get(), ValidationException::withMessages(['backup' => __('backup.error.busy')]));

        try {
            $disk = Storage::disk('backups');
            $path = config('backup.backup.name').'/'.$filename;
            abort_unless($disk->exists($path), 404);
            throw_unless($disk->delete($path), ValidationException::withMessages(['backup' => __('backup.error.delete_failed')]));
        } finally {
            $lock->release();
        }
    }
}
