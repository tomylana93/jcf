<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\DocumentationStatus;
use App\Enums\Permission;
use App\Models\Documentation;
use App\Models\User;

final class DocumentationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewDocumentation) || $user->can(Permission::ManageDocumentation);
    }

    public function view(User $user, Documentation $documentation): bool
    {
        return $user->can(Permission::ManageDocumentation) || ($user->can(Permission::ViewDocumentation) && $documentation->status === DocumentationStatus::Published);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageDocumentation);
    }

    public function update(User $user, Documentation $documentation): bool
    {
        return $user->can(Permission::ManageDocumentation);
    }

    public function delete(User $user, Documentation $documentation): bool
    {
        return $user->can(Permission::ManageDocumentation);
    }
}
