<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;

final class GetUserPermissionOptions
{
    /** @return list<array{value: string, label: string, group: string}> */
    public function handle(User $user): array
    {
        $inherited = $user->getPermissionsViaRoles()->pluck('name')->all();
        $initialized = PermissionModel::query()->where('guard_name', 'web')->pluck('name')->all();

        return array_values(array_map(
            fn (Permission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'group' => explode('.', $permission->value)[0],
            ],
            array_filter(Permission::cases(), fn (Permission $permission): bool => $permission !== Permission::AssignUserPermissions && in_array($permission->value, $initialized, true) && ! in_array($permission->value, $inherited, true)),
        ));
    }
}
