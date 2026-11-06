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

    public function __construct(public string $monitoredQueue = 'queue') {}

    public static function cacheKey(string $monitoredQueue): string
    {
        return 'system.queue_heartbeat.'.$monitoredQueue;
    }

    public function handle(): void
    {
        Cache::put(self::cacheKey($this->monitoredQueue), now()->timestamp, 86400);
    }
}
