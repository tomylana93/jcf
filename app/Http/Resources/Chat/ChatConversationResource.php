<?php

declare(strict_types=1);

namespace App\Http\Resources\Chat;

use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChatConversation */
final class ChatConversationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $self = $this->participants->firstWhere('user_id', $actor->id);
        $other = $this->participants->first(fn (ChatParticipant $participant): bool => $participant->user_id !== $actor->id);
        $last = $this->messages->first();

        return [
            'id' => $this->id,
            'recipient' => new ChatUserResource($other?->user),
            'last_message' => $last === null ? null : new ChatMessageResource($last),
            'last_message_sequence' => $this->last_message_sequence,
            'last_read_sequence' => $self->last_read_sequence ?? 0,
            'recipient_last_read_sequence' => $other->last_read_sequence ?? 0,
            'unread_count' => (int) $this->getAttribute('unread_count'),
            'can_send' => $this->participants->count() === 2 && $this->participants->every(
                fn (ChatParticipant $participant): bool => $participant->user !== null && $participant->user->can('viewAny', ChatConversation::class),
            ),
        ];
    }
}
