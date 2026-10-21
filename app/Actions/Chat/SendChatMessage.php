<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\ChatMessageCreated;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class SendChatMessage
{
    public function __construct(private ValidateChatMessage $validateMessage) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, ChatConversation $conversation, array $data): ChatMessage
    {
        abort_unless(resolve(FeatureManager::class)->enabled('chat'), 404);

        return DB::transaction(function () use ($actor, $conversation, $data): ChatMessage {
            $ids = $conversation->participants()->pluck('user_id')->push($actor->id)->unique()->sort()->values();
            $users = User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sender = $users->get($actor->id) ?? abort(403);
            $record = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            Gate::forUser($sender)->authorize('sendMessage', $record);

            $validated = $this->validateMessage->handle($data);
            $existing = ChatMessage::query()->where('sender_id', $sender->id)
                ->where('client_message_id', $validated['client_message_id'])->first();

            if ($existing !== null) {
                if ($existing->conversation_id !== $record->id || $existing->body !== $validated['body']) {
                    throw ValidationException::withMessages(['client_message_id' => __('chat.error.idempotency_conflict')]);
                }

                return $existing;
            }

            $message = $record->messages()->create([
                ...$validated,
                'sender_id' => $sender->id,
                'sequence' => $record->last_message_sequence + 1,
            ]);
            $record->update(['last_message_sequence' => $message->sequence]);

            event(new ChatMessageCreated($message, array_values($users->keys()
                ->map(fn (mixed $id): string => (string) $id)
                ->all())));

            return $message;
        }, attempts: 3);
    }
}
