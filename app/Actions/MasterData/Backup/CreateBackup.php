<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use RuntimeException;

final readonly class CreateBackup
{
    public function __construct(private WriteBackup $writeBackup, private GetBackupLock $getBackupLock) {}

    public function handle(): string
    {
        if (! config('operations.backup_enabled') || ! config('backup.backup.password')) {
            throw new RuntimeException(__('backup.error.not_configured'));
        }

        return $this->getBackupLock->handle()->get(fn (): string => $this->writeBackup->handle()) ?: throw new RuntimeException(__('backup.error.busy'));
    }
}
