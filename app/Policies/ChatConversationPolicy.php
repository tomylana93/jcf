<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\User;

final class ChatConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status === UserStatus::Active && $user->can(Permission::UseChat);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, ChatConversation $conversation): bool
    {
        return $this->viewAny($user) && $conversation->participants()->where('user_id', $user->id)->exists();
    }

    public function sendMessage(User $user, ChatConversation $conversation): bool
    {
        if (! $this->view($user, $conversation) || $conversation->type !== 'direct') {
            return false;
        }

        $participants = $conversation->participants()->with('user.roles.permissions', 'user.permissions')->get();

        return $participants->count() === 2
            && $participants->every(fn (ChatParticipant $participant): bool => $participant->user !== null && $this->viewAny($participant->user));
    }

    public function markRead(User $user, ChatConversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
