<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SystemHeartbeat as HeartbeatJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Description('Record scheduler heartbeat and enqueue a worker heartbeat')]
#[Signature('system:heartbeat')]
final class SystemHeartbeat extends Command
{
    public function handle(): int
    {
        Cache::put('system.scheduler_heartbeat', now()->timestamp, 86400);
        dispatch(new HeartbeatJob());

        return self::SUCCESS;
    }
}
