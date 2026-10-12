<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\MasterData\Backup\CreateBackup as CreateBackupAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Create an encrypted database and application files backup')]
#[Signature('system:backup')]
final class CreateBackup extends Command
{
    public function handle(CreateBackupAction $createBackup): int
    {
        try {
            $this->info($createBackup->handle());

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Backup failed. Check backup configuration, database tools, and application logs.');

            return self::FAILURE;
        }
    }
}
