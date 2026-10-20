<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

final class GetChatHistory
{
    /** @return array{messages: Collection<int, ChatMessage>, has_more: bool} */
    public function handle(User $actor, ChatConversation $conversation, ?int $before, ?int $after): array
    {
        Gate::forUser($actor)->authorize('view', $conversation);
        $query = $conversation->messages();
        if ($before !== null) {
            $query->where('sequence', '<', $before);
        }
        if ($after !== null) {
            $query->where('sequence', '>', $after)->orderBy('sequence');
        } else {
            $query->orderByDesc('sequence');
        }
        $messages = $query->limit(51)->get();
        $hasMore = $messages->count() > 50;
        $messages = $messages->take(50);

        return ['messages' => $after === null ? $messages->reverse()->values() : $messages, 'has_more' => $hasMore];
    }
}
