<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\Module;
use App\Events\ChatTypingUpdated;
use App\Models\ChatConversation;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Support\Facades\Gate;

final readonly class SendChatTyping
{
    public function __construct(private FeatureManager $features) {}

    public function handle(User $actor, ChatConversation $conversation, bool $typing): void
    {
        $this->features->ensureEnabled(Module::Chat);

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
            now()->addSeconds(4)->toIso8601String(),
            $recipients,
        ));
    }
}
