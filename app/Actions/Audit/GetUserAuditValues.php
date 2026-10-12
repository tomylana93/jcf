<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\Role;
use App\Models\User;

final class GetUserAuditValues
{
    /** @return array{name: string, email: string, status: string, role: list<string>} */
    public function handle(User $user): array
    {
        $user->load('roles');

        return [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'role' => array_values($user->roles->map(fn (Role $role): string => $role->name)->sort()->all()),
        ];
    }
}
