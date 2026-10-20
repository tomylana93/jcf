<?php

declare(strict_types=1);

namespace App\Http\Resources\Chat;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChatMessage */
final class ChatMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id, 'sequence' => $this->sequence,
            'client_message_id' => $this->client_message_id, 'body' => $this->body,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
