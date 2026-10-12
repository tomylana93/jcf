<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum Permission: string
{
    use HasLabel, HasOptions;

    case ViewSettings = 'settings.view';
    case ManageSettings = 'settings.manage';
    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';
    case AssignUserPermissions = 'users.permissions.assign';
}
