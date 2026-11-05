<?php

declare(strict_types=1);

namespace App\Events;

use App\Events\Concerns\BroadcastsToChatParticipants;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ChatReadUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use BroadcastsToChatParticipants;
    use Dispatchable;

    /** @param list<string> $userIds */
    public function __construct(
        public string $conversationId,
        public string $userId,
        public int $sequence,
        public array $userIds,
    ) {}

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'last_read_sequence' => $this->sequence,
        ];
    }

    protected function broadcastConversationId(): string
    {
        return $this->conversationId;
    }
}
