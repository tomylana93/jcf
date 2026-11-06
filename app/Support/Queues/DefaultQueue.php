<?php

declare(strict_types=1);

namespace App\Support\Queues;

use App\Support\Contracts\MonitoredQueue;

final readonly class DefaultQueue implements MonitoredQueue
{
    public function name(): string
    {
        return 'queue';
    }

    public function connection(): string
    {
        return (string) config('queue.default');
    }

    public function queue(): string
    {
        return (string) config('queue.connections.'.$this->connection().'.queue', 'default');
    }

    public function monitored(): bool
    {
        return true;
    }
}
