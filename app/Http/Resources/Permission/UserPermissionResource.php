<?php

declare(strict_types=1);

namespace App\Http\Resources\Permission;

use App\Enums\Role;
use App\Http\Resources\MasterData\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserPermissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...new UserResource($this->resource)->resolve($request),
            'super_admin' => $this->hasRole(Role::SuperAdmin),
            'direct_permissions' => $this->getDirectPermissions()->pluck('name')->values()->all(),
            'inherited_permissions' => $this->getPermissionsViaRoles()->pluck('name')->unique()->values()->all(),
        ];
    }
}
