<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\ChatTypingUpdated;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class SendChatTyping
{
    public function handle(User $actor, ChatConversation $conversation, bool $typing): void
    {
        Gate::forUser($actor)->authorize('sendMessage', $conversation);

        $recipients = array_values(
            $conversation->participants()
                ->pluck('user_id')
                ->map(fn (mixed $id): string => (string) $id)
                ->reject(fn (string $id): bool => $id === (string) $actor->getKey())
                ->all(),
        );

        event(new ChatTypingUpdated(
            (string) $conversation->getKey(),
            (string) $actor->getKey(),
            $typing,
            now()->addSeconds(4)->toISOString(),
            $recipients,
        ));
    }
}
