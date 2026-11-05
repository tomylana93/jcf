<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ConversationType;
use App\Enums\Module;
use App\Models\ChatConversation;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class StartDirectConversation
{
    public function __construct(private FeatureManager $features) {}

    public function handle(User $actor, User $recipient): ChatConversation
    {
        $this->features->ensureEnabled(Module::Chat);

        return DB::transaction(function () use ($actor, $recipient): ChatConversation {
            $ids = [$actor->id, $recipient->id];
            sort($ids, SORT_STRING);

            $users = User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sender = $users->get($actor->id) ?? throw new AuthorizationException;
            $receiver = $users->get($recipient->id) ?? throw new AuthorizationException;
            Gate::forUser($sender)->authorize('create', ChatConversation::class);
            Gate::forUser($receiver)->authorize('create', ChatConversation::class);

            if ($sender->is($receiver)) {
                throw ValidationException::withMessages(['recipient_id' => __('chat.error.self_conversation')]);
            }

            $conversation = ChatConversation::query()->firstOrCreate(
                ['direct_pair_key' => implode(':', $ids)],
                ['type' => ConversationType::Direct->value, 'last_message_sequence' => 0],
            );

            foreach ($ids as $id) {
                $conversation->participants()->firstOrCreate(['user_id' => $id], ['last_read_sequence' => 0]);
            }

            return $conversation;
        }, attempts: 3);
    }
}
