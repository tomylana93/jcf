<?php

declare(strict_types=1);

namespace App\Events\Concerns;

use App\Actions\Chat\GetChatBroadcastChannels;
use App\Enums\Module;
use App\Support\FeatureManager;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Realtime delivery shared by chat events: the chat queue, and only participants who may still use chat.
 *
 * @property list<string> $userIds
 */
trait BroadcastsToChatParticipants
{
    public string $connection = 'realtime';

    public string $queue = 'chat';

    abstract protected function broadcastConversationId(): string;

    public function broadcastWhen(): bool
    {
        return resolve(FeatureManager::class)->enabled(Module::Chat);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return resolve(GetChatBroadcastChannels::class)->handle($this->broadcastConversationId(), $this->userIds);
    }
}
