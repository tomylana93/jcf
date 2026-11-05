<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use RuntimeException;

final readonly class CreateBackup
{
    public function __construct(private WriteBackup $writeBackup, private GetBackupLock $getBackupLock, private IsBackupConfigured $isBackupConfigured) {}

    public function handle(): string
    {
        if (! $this->isBackupConfigured->handle()) {
            throw new RuntimeException(__('backup.error.not_configured'));
        }

        return $this->getBackupLock->handle()->get(fn (): string => $this->writeBackup->handle()) ?: throw new RuntimeException(__('backup.error.busy'));
    }
}
