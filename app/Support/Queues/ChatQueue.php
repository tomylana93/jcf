<?php

declare(strict_types=1);

namespace App\Support\Queues;

use App\Enums\Module;
use App\Support\Contracts\MonitoredQueue;
use App\Support\FeatureManager;

/**
 * Chat broadcasts, expected only while chat is enabled and a broadcaster is configured.
 */
final readonly class ChatQueue implements MonitoredQueue
{
    public function __construct(private FeatureManager $features, private NotificationQueue $realtime) {}

    public function name(): string
    {
        return 'queue_chat';
    }

    public function connection(): string
    {
        return 'realtime';
    }

    public function queue(): string
    {
        return 'chat';
    }

    public function monitored(): bool
    {
        return $this->realtime->monitored() && $this->features->enabled(Module::Chat);
    }
}
