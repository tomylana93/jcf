<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

final class ApiClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewApiClients);
    }

    public function view(User $user): bool
    {
        return $user->can(Permission::ViewApiClients);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageApiClients);
    }

    public function update(User $user): bool
    {
        return $user->can(Permission::ManageApiClients);
    }

    public function manageTokens(User $user): bool
    {
        return $user->can(Permission::ManageApiClients);
    }
}
