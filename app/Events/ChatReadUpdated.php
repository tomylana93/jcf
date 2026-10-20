<?php

declare(strict_types=1);

namespace App\Events;

use App\Actions\Chat\GetChatBroadcastChannels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ChatReadUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public string $connection = 'realtime';

    public string $queue = 'chat';

    /** @param list<string> $userIds */
    public function __construct(
        public string $conversationId,
        public string $userId,
        public int $sequence,
        public array $userIds,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return resolve(GetChatBroadcastChannels::class)->handle(
            $this->conversationId,
            $this->userIds,
        );
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'last_read_sequence' => $this->sequence,
        ];
    }
}
