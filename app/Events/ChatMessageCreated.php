<?php

declare(strict_types=1);

namespace App\Events;

use App\Events\Concerns\BroadcastsToChatParticipants;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChatMessageCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use BroadcastsToChatParticipants;
    use Dispatchable;
    use SerializesModels;

    /** @param list<string> $userIds */
    public function __construct(public ChatMessage $message, public array $userIds) {}

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return new ChatMessageResource($this->message)->resolve();
    }

    protected function broadcastConversationId(): string
    {
        return (string) $this->message->conversation_id;
    }
}
