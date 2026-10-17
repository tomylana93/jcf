<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WebsiteContent;

final class WebsiteContentPolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::EditWebsiteContent);
    }

    public function update(User $user, WebsiteContent $content): bool
    {
        return $user->can(Permission::EditWebsiteContent);
    }

    public function preview(User $user, WebsiteContent $content): bool
    {
        return $user->can(Permission::EditWebsiteContent) || $user->can(Permission::PublishWebsiteContent);
    }

    public function publish(User $user, WebsiteContent $content): bool
    {
        return $user->can(Permission::PublishWebsiteContent);
    }
}
