<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Enums\Role as SystemRole;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;

final class GetAssignableUserRoles
{
    /** @return list<string> */
    public function handle(User $actor): array
    {
        return array_values(Role::query()->where('guard_name', 'web')->where('name', '!=', SystemRole::SuperAdmin->value)
            ->with('permissions')->get()
            ->filter(fn (Role $role): bool => $actor->hasRole(SystemRole::SuperAdmin)
                || $role->permissions->every(fn (Permission $permission): bool => $actor->can($permission->name)))
            ->map(fn (Role $role): string => $role->name)->all());
    }
}
