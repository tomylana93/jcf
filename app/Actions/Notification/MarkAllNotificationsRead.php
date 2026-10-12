<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Models\User;

final class MarkAllNotificationsRead
{
    public function handle(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
