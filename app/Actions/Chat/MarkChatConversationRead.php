<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class MarkChatConversationRead
{
    /** @return array<string, array<int, mixed>> */
    public static function rules(ChatConversation $conversation): array
    {
        return ['sequence' => [
            'required', 'integer', 'min:1',
            Rule::exists('chat_messages', 'sequence')->where('conversation_id', $conversation->id),
        ]];
    }

    public function handle(User $actor, ChatConversation $conversation, int $sequence): ChatParticipant
    {
        return DB::transaction(function () use ($actor, $conversation, $sequence): ChatParticipant {
            $user = User::query()->lockForUpdate()->findOrFail($actor->id);
            $record = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            Gate::forUser($user)->authorize('markRead', $record);
            Validator::make(['sequence' => $sequence], self::rules($record))->validate();

            $participant = $record->participants()->where('user_id', $user->id)->firstOrFail();

            if ($sequence > $participant->last_read_sequence) {
                $participant->update(['last_read_sequence' => $sequence]);
            }

            return $participant;
        }, attempts: 3);
    }
}
