<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use BackedEnum;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Spatie\Permission\Traits\HasRoles;

/** @phpstan-require-extends Authenticatable */
trait HasSingleRole
{
    use HasRoles {
        assignRole as private assignSpatieRole;
    }

    public function assignRole(mixed ...$roles): static
    {
        return $this->syncRoles(...$roles);
    }

    public function syncRoles(mixed ...$roles): static
    {
        throw_unless($this->exists, LogicException::class, 'Save the user before assigning a role.');

        $resolvedRoles = collect($roles)->flatten()->map(function (mixed $role): mixed {
            if ($role instanceof BackedEnum) {
                $role = $role->value;
            }

            if ($role === Role::SuperAdmin->value) {
                return RoleModel::findOrCreate(Role::SuperAdmin->value, 'web');
            }

            return $role;
        })->all();
        $roleIds = $this->collectRoles($resolvedRoles);

        throw_if(count($roleIds) > 1, InvalidArgumentException::class, 'A user may have at most one role.');

        $role = $roleIds === [] ? null : $this->getStoredRole($roleIds[0]);

        DB::transaction(function () use ($role): void {
            static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->roles()->detach();
            $this->unsetRelation('roles');
            if ($role !== null) {
                $this->assignSpatieRole($role);
            }
        });

        return $this;
    }
}
