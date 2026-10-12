<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Models\User;

final class GetNotificationSummary
{
    /** @return array{unread_count: int} */
    public function handle(?User $user): array
    {
        return ['unread_count' => $user?->unreadNotifications()->count() ?? 0];
    }
}
