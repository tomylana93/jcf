<?php

declare(strict_types=1);

namespace App\Http\Resources\Permission;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Resources\MasterData\UserResource;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserPermissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $features = resolve(FeatureManager::class);
        $available = fn (string $name): bool => $features->moduleEnabled(Permission::tryFrom($name)?->module());

        return [
            ...new UserResource($this->resource)->resolve($request),
            'super_admin' => $this->hasRole(Role::SuperAdmin),
            'direct_permissions' => $this->getDirectPermissions()->pluck('name')
                ->map(fn (mixed $name): string => (string) $name)
                ->filter($available)
                ->values()
                ->all(),
            'inherited_permissions' => $this->getPermissionsViaRoles()->pluck('name')
                ->map(fn (mixed $name): string => (string) $name)
                ->unique()
                ->filter($available)
                ->values()
                ->all(),
        ];
    }
}
