<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SystemHeartbeat as HeartbeatJob;
use App\Support\Contracts\MonitoredQueue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Description('Record scheduler heartbeat and enqueue a heartbeat on every monitored queue')]
#[Signature('system:heartbeat')]
final class SystemHeartbeat extends Command
{
    public function handle(): int
    {
        Cache::put('system.scheduler_heartbeat', now()->timestamp, 86400);

        /** @var iterable<MonitoredQueue> $queues */
        $queues = $this->laravel->tagged(MonitoredQueue::class);
        foreach ($queues as $queue) {
            if ($queue->monitored()) {
                dispatch(new HeartbeatJob($queue->name()))->onConnection($queue->connection())->onQueue($queue->queue());
            }
        }

        return self::SUCCESS;
    }
}
