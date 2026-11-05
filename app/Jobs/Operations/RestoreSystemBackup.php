<?php

declare(strict_types=1);

namespace App\Jobs\Operations;

use App\Actions\Operations\Backup\RestoreActiveBackup;
use App\Enums\BackupOperation;
use App\Enums\Module;
use App\Enums\OperationStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Support\FeatureManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Throwable;

#[Timeout(1800)]
#[Tries(1)]
#[UniqueFor(1900)]
final class RestoreSystemBackup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public string $filename, public string $checksum, public string $actorId)
    {
        $this->onConnection('backups');
    }

    public function handle(RestoreActiveBackup $restoreBackup): void
    {
        if (! resolve(FeatureManager::class)->enabled(Module::Backup)) {
            BackupOperation::Restore->mark(OperationStatus::Skipped);

            return;
        }

        $restoreBackup->handle($this->filename, $this->checksum);
        $this->delete();
        User::query()->find($this->actorId)?->notify(new ApplicationNotification('restore_ready', $this->filename));
    }

    public function failed(?Throwable $exception): void
    {
        BackupOperation::Restore->mark(OperationStatus::Failed);
        User::query()->find($this->actorId)?->notify(new ApplicationNotification('restore_failed'));
    }
}
