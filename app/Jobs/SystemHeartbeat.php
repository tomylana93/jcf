<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Cache;

#[Tries(1)]
final class SystemHeartbeat implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('system.queue_heartbeat', now()->timestamp, 86400);
    }
}
