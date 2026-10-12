<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\MasterData\Backup\RestoreBackup as RestoreBackupAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Restore a trusted backup into an empty PostgreSQL database and isolated files directory')]
#[Signature('system:restore {backup} {--sha256=} {--connection=backup_restore} {--confirm : Confirm restoration into an empty isolated database}')]
final class RestoreBackup extends Command
{
    public function handle(RestoreBackupAction $restoreBackup): int
    {
        if (! $this->option('confirm') || ! is_string($this->option('sha256')) || preg_match('/^[a-f0-9]{64}$/D', $this->option('sha256')) !== 1) {
            $this->error('Provide --confirm and the trusted archive --sha256 checksum. Restore only targets an empty isolated database.');

            return self::FAILURE;
        }
        try {
            $path = $restoreBackup->handle($this->argument('backup'), $this->option('sha256'), $this->option('connection'));
            $this->info('Restore completed. Application files are staged at: '.$path);
            $this->warn('Verify restored data and file references before a separate maintenance-window cutover. Retain the original APP_KEY and archive password.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
