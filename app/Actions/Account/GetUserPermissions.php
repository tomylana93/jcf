<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\Permission;
use Illuminate\Contracts\Auth\Access\Authorizable;

final class GetUserPermissions
{
    /** @return list<string> */
    public function handle(?Authorizable $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_map(
            fn (Permission $permission): string => $permission->value,
            array_filter(Permission::cases(), fn (Permission $permission): bool => $user->can($permission)),
        ));
    }
}
