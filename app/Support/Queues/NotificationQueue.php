<?php

declare(strict_types=1);

namespace App\Support\Queues;

use App\Support\Contracts\MonitoredQueue;

/**
 * Realtime notification broadcasts, which only queue work while a broadcaster is configured.
 */
final readonly class NotificationQueue implements MonitoredQueue
{
    public function name(): string
    {
        return 'queue_notifications';
    }

    public function connection(): string
    {
        return 'realtime';
    }

    public function queue(): string
    {
        return 'notifications';
    }

    public function monitored(): bool
    {
        return ! in_array(config('broadcasting.default'), [null, 'null'], true);
    }
}
