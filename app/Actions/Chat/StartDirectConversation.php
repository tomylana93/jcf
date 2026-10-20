<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class StartDirectConversation
{
    public function handle(User $actor, User $recipient): ChatConversation
    {
        return DB::transaction(function () use ($actor, $recipient): ChatConversation {
            $ids = [$actor->id, $recipient->id];
            sort($ids, SORT_STRING);

            $users = User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sender = $users->get($actor->id) ?? abort(403);
            $receiver = $users->get($recipient->id) ?? abort(403);
            Gate::forUser($sender)->authorize('create', ChatConversation::class);
            Gate::forUser($receiver)->authorize('create', ChatConversation::class);

            if ($sender->is($receiver)) {
                throw ValidationException::withMessages(['recipient_id' => __('chat.error.self_conversation')]);
            }

            $conversation = ChatConversation::query()->firstOrCreate(
                ['direct_pair_key' => implode(':', $ids)],
                ['type' => 'direct', 'last_message_sequence' => 0],
            );

            foreach ($ids as $id) {
                $conversation->participants()->firstOrCreate(['user_id' => $id], ['last_read_sequence' => 0]);
            }

            return $conversation;
        }, attempts: 3);
    }
}
