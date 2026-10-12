<?php

declare(strict_types=1);

namespace App\Http\Resources\MasterData;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role as PermissionRole;

/** @mixin User */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $role = $this->roles->first();
        $roleEnum = $role === null ? null : Role::tryFrom($role->name);

        return [
            'role' => [
                'label' => $roleEnum?->label() ?? $role->name ?? '—',
                'color' => $roleEnum?->badgeColor() ?? 'neutral',
            ],
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->hasVerifiedEmail(),
            'invitation_pending' => $this->invited_at !== null && $this->invitation_accepted_at === null,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'status_variant' => $this->status->badgeVariant(),
            'created_at' => $this->created_at,
            'roles' => $this->roles->map(function (PermissionRole $role): array {
                $roleEnum = Role::tryFrom($role->name);

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $roleEnum?->label() ?? $role->name,
                    'color' => $roleEnum?->badgeColor() ?? 'neutral',
                ];
            })->all(),
        ];
    }
}
