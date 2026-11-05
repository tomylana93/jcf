<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\Module;
use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Broadcasting\PrivateChannel;
use Spatie\Permission\PermissionRegistrar;

final readonly class GetChatBroadcastChannels
{
    public function __construct(private PermissionRegistrar $permissions, private FeatureManager $features) {}

    /**
     * @param  list<string>  $userIds
     * @return list<PrivateChannel>
     */
    public function handle(string $conversationId, array $userIds): array
    {
        if (! $this->features->enabled(Module::Chat)) {
            return [];
        }

        $this->permissions->clearPermissionsCollection();

        return array_values(ChatParticipant::query()
            ->where('conversation_id', $conversationId)
            ->whereIn('user_id', $userIds)
            ->with('user.roles.permissions', 'user.permissions')
            ->get()
            ->filter(fn (ChatParticipant $participant): bool => $participant->user !== null
                && $participant->user->can('viewAny', ChatConversation::class))
            ->map(fn (ChatParticipant $participant): PrivateChannel => User::privateChannel((string) $participant->user_id))
            ->all());
    }
}
