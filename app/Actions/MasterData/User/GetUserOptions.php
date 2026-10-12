<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Role as RoleModel;

final class GetUserOptions
{
    /** @return array{roles: list<array{value: int|string, label: string, name: string, color?: string}>, statuses: list<array{value: int|string, label: string, name: string, color?: string}>} */
    public function handle(): array
    {
        return [
            'roles' => array_values(RoleModel::query()
                ->where('guard_name', 'web')
                ->where('name', '!=', Role::SuperAdmin->value)
                ->orderBy('name')
                ->get()
                ->map(fn (RoleModel $role): array => [
                    'value' => $role->name,
                    'label' => $role->name,
                    'name' => $role->name,
                    'color' => 'neutral',
                ])->all()),
            'statuses' => UserStatus::options(),
        ];
    }
}
