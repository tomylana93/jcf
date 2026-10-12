<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetNotifications
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function handle(User $user, string $status): LengthAwarePaginator
    {
        $query = $user->notifications()->reorder()->latest()->orderByDesc('id');

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate(20)->withQueryString();
    }
}
