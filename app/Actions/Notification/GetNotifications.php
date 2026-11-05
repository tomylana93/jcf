<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Enums\NotificationFilter;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class GetNotifications
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function handle(User $user, NotificationFilter $filter): LengthAwarePaginator
    {
        $query = $user->notifications()->reorder()->latest()->orderByDesc('id');

        match ($filter) {
            NotificationFilter::Unread => $query->whereNull('read_at'),
            NotificationFilter::Read => $query->whereNotNull('read_at'),
            NotificationFilter::All => null,
        };

        return $query->paginate(20)->withQueryString();
    }
}
