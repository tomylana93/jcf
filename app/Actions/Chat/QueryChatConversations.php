<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

final class QueryChatConversations
{
    /** @return Builder<ChatConversation> */
    public function handle(User $actor): Builder
    {
        Gate::forUser($actor)->authorize('viewAny', ChatConversation::class);

        return ChatConversation::query()
            ->whereHas('participants', fn (Builder $query): Builder => $query->where('user_id', $actor->id))
            ->with([
                'participants.user.media', 'participants.user.roles.permissions', 'participants.user.permissions',
                'messages' => fn (HasMany $query): HasMany => $query->orderByDesc('sequence')->limit(1),
            ])
            ->withCount(['messages as unread_count' => fn (Builder $query): Builder => $query
                ->where('sender_id', '!=', $actor->id)
                ->where('sequence', '>', ChatParticipant::query()->select('last_read_sequence')
                    ->whereColumn('conversation_id', 'chat_conversations.id')->where('user_id', $actor->id))]);
    }
}
