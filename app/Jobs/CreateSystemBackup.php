<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\MasterData\Backup\CreateBackup;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Support\FeatureManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Facades\Cache;
use Throwable;

#[Timeout(1800)]
#[Tries(1)]
#[UniqueFor(1900)]
final class CreateSystemBackup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public string $actorId)
    {
        $this->onConnection('backups');
    }

    public function handle(CreateBackup $createBackup): void
    {
        if (! resolve(FeatureManager::class)->enabled('backup')) {
            Cache::put('system.backup_status', 'skipped', 86400);

            return;
        }

        $filename = $createBackup->handle();
        User::query()->find($this->actorId)?->notify(new ApplicationNotification('backup_ready', $filename));
    }

    public function failed(?Throwable $exception): void
    {
        Cache::put('system.backup_status', 'failed', 86400);
        User::query()->find($this->actorId)?->notify(new ApplicationNotification('backup_failed'));
    }
}
