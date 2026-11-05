<?php

declare(strict_types=1);

namespace App\Actions\Identity\User;

use App\Enums\UserStatus;
use App\Models\Role as RoleModel;
use App\Models\User;

final readonly class GetUserOptions
{
    public function __construct(private GetAssignableUserRoles $getAssignableUserRoles) {}

    /** @return array{roles: list<array{value: int|string, label: string, name: string, color?: string}>, statuses: list<array{value: int|string, label: string, name: string, color?: string}>} */
    public function handle(User $actor): array
    {
        return [
            'roles' => array_values(RoleModel::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $this->getAssignableUserRoles->handle($actor))
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
