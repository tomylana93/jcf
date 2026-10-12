<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Models\User;

final class MarkNotificationRead
{
    public function handle(User $user, string $notificationId): void
    {
        $user->notifications()->findOrFail($notificationId);
        $user->unreadNotifications()->whereKey($notificationId)->update(['read_at' => now()]);
    }
}
