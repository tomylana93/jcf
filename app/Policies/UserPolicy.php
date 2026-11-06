<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Permission as PermissionModel;
use App\Models\User;

final class UserPolicy
{
    public function sendInvitation(User $user, User $target): bool
    {
        return $this->canManage($user, $target);
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
        return $this->canManage($user, $target)
            && (! $target->is($user) || $status !== UserStatus::Disabled->value);
    }

    public function updateStatus(User $user, User $target): bool
    {
        return $this->canManage($user, $target)
            && ! $target->is($user);
    }

    public function viewPermissions(User $user): bool
    {
        return $user->can(Permission::AssignUserPermissions);
    }

    public function assignPermissions(User $user, User $target): bool
    {
        return $user->can(Permission::AssignUserPermissions)
            && ! $target->hasRole(Role::SuperAdmin);
    }

    /**
     * Managers may act only on non-superadmin accounts whose effective permissions they already hold,
     * so changing an account's email or status can never hand over privileges the manager lacks.
     */
    private function canManage(User $user, User $target): bool
    {
        return $user->can(Permission::ManageUsers)
            && ! $target->hasRole(Role::SuperAdmin)
            && $target->getAllPermissions()->every(fn (PermissionModel $permission): bool => $user->can($permission->name));
    }
}
