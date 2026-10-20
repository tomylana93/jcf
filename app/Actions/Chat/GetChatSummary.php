<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\ChatMessage;
use App\Models\User;

final class GetChatSummary
{
    /** @return array{unread_count: int} */
    public function handle(?User $actor): array
    {
        if ($actor === null || $actor->status !== UserStatus::Active || ! $actor->can(Permission::UseChat)) {
            return ['unread_count' => 0];
        }

        $unread = ChatMessage::query()
            ->join('chat_participants', 'chat_participants.conversation_id', '=', 'chat_messages.conversation_id')
            ->where('chat_participants.user_id', $actor->id)
            ->where('chat_messages.sender_id', '!=', $actor->id)
            ->whereColumn('chat_messages.sequence', '>', 'chat_participants.last_read_sequence')
            ->count();

        return ['unread_count' => $unread];
    }
}
