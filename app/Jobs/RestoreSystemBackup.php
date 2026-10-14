<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\MasterData\Backup\RestoreActiveBackup;
use App\Models\User;
use App\Notifications\ApplicationNotification;
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
final class RestoreSystemBackup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public string $filename, public string $checksum, public string $actorId)
    {
        $this->onConnection('backups');
    }

    public function handle(RestoreActiveBackup $restoreBackup): void
    {
        $restoreBackup->handle($this->filename, $this->checksum);
        $this->delete();
        User::query()->find($this->actorId)?->notify(new ApplicationNotification('restore_ready', $this->filename));
    }

    public function failed(?Throwable $exception): void
    {
        Cache::store('file')->put('system.restore_status', 'failed', 86400);
        User::query()->find($this->actorId)?->notify(new ApplicationNotification('restore_failed'));
    }
}
