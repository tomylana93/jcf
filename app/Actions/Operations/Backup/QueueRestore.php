<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use App\Jobs\Operations\RestoreSystemBackup;
use App\Models\User;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Validation\ValidationException;

final readonly class QueueRestore
{
    public function __construct(
        private IsBackupConfigured $isBackupConfigured,
        private GetBackupPath $getBackupPath,
        private GetBackupChecksum $getBackupChecksum,
    ) {}

    /**
     * Pin the archive checksum and queue its restore into the active installation.
     *
     * @throws ValidationException
     * @throws RecordsNotFoundException
     */
    public function handle(string $filename, User $actor): void
    {
        throw_unless($this->isBackupConfigured->handle(), ValidationException::withMessages(['backup' => __('backup.error.not_configured')]));
        $this->getBackupPath->handle($filename, mustExist: true);

        dispatch(new RestoreSystemBackup($filename, $this->getBackupChecksum->handle($filename), $actor->id));
    }
}
