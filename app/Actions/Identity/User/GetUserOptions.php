<?php

declare(strict_types=1);

namespace App\Actions\Identity\User;

use App\Enums\Role as SystemRole;
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
                ->sortBy(fn (RoleModel $role): int => $this->roleOrder($role))
                ->map(function (RoleModel $role): array {
                    $systemRole = SystemRole::tryFrom($role->name);

                    return [
                        'value' => $role->name,
                        'label' => $systemRole?->label() ?? $role->name,
                        'name' => $role->name,
                        'color' => $systemRole?->badgeColor() ?? 'neutral',
                    ];
                })->all()),
            'statuses' => UserStatus::options(),
        ];
    }

    /**
     * System roles follow their enum declaration order; custom roles come after them, alphabetically.
     */
    private function roleOrder(RoleModel $role): int
    {
        $systemRole = SystemRole::tryFrom($role->name);

        return $systemRole === null ? count(SystemRole::cases()) : (int) array_search($systemRole, SystemRole::cases(), true);
    }
}
