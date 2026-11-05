<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationFilter: string
{
    case All = 'all';
    case Unread = 'unread';
    case Read = 'read';
}
