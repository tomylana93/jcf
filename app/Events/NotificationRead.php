<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class NotificationRead implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public string $connection = 'realtime';

    public function __construct(public string $userId) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->userId)];
    }

    /** @return array<string, never> */
    public function broadcastWith(): array
    {
        return [];
    }
}
