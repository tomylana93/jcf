<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Events\NotificationRead;
use App\Models\User;

final class MarkNotificationRead
{
    public function handle(User $user, string $notificationId): void
    {
        $user->notifications()->findOrFail($notificationId);
        $updated = $user->unreadNotifications()->whereKey($notificationId)->update(['read_at' => now()]);

        if ($updated > 0) {
            event(new NotificationRead((string) $user->getKey()));
        }
    }
}
