<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;

final class UserPolicy
{
    public function sendInvitation(User $user, User $target): bool
    {
        return $user->can(Permission::ManageUsers) && ! $target->hasRole(Role::SuperAdmin);
    }

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewUsers);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageUsers);
    }

    public function update(User $user, User $target, mixed $status = null): bool
    {
        return $user->can(Permission::ManageUsers)
            && ! $target->hasRole(Role::SuperAdmin)
            && (! $target->is($user) || $status !== UserStatus::Disabled->value);
    }

    public function updateStatus(User $user, User $target): bool
    {
        return $user->can(Permission::ManageUsers)
            && ! $target->hasRole(Role::SuperAdmin)
            && ! $target->is($user);
    }
}
