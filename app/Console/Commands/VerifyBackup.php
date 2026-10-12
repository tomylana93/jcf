<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\MasterData\Backup\GetBackupChecksum;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Print the SHA-256 checksum of a stored backup for a restore drill')]
#[Signature('system:backup-checksum {backup}')]
final class VerifyBackup extends Command
{
    public function handle(GetBackupChecksum $getBackupChecksum): int
    {
        try {
            $this->info($getBackupChecksum->handle($this->argument('backup')));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
