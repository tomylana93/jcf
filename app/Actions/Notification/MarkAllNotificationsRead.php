<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Events\NotificationRead;
use App\Models\User;

final class MarkAllNotificationsRead
{
    public function handle(User $user): void
    {
        $updated = $user->unreadNotifications()->update(['read_at' => now()]);

        if ($updated > 0) {
            event(new NotificationRead((string) $user->getKey()));
        }
    }
}
