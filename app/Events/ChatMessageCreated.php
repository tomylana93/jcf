<?php

declare(strict_types=1);

namespace App\Events;

use App\Actions\Chat\GetChatBroadcastChannels;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChatMessageCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public string $connection = 'realtime';

    public string $queue = 'chat';

    /** @param list<string> $userIds */
    public function __construct(public ChatMessage $message, public array $userIds) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return resolve(GetChatBroadcastChannels::class)->handle(
            (string) $this->message->conversation_id,
            $this->userIds,
        );
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sequence' => $this->message->sequence,
            'client_message_id' => $this->message->client_message_id,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }
}
