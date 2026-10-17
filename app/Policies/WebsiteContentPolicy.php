<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WebsiteContent;

final class WebsiteContentPolicy
{
    public function view(User $user): bool
    {
        return $user->can(Permission::EditWebsiteContent)
            || $user->can(Permission::PublishWebsiteContent)
            || $user->can(Permission::ConfigureWebsite);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::EditWebsiteContent);
    }

    public function update(User $user, WebsiteContent $content): bool
    {
        return $user->can(Permission::EditWebsiteContent);
    }

    public function preview(User $user, ?WebsiteContent $content = null): bool
    {
        return $user->can(Permission::EditWebsiteContent)
            || $user->can(Permission::PublishWebsiteContent)
            || $user->can(Permission::ConfigureWebsite);
    }

    public function publish(User $user, WebsiteContent $content): bool
    {
        return $user->can(Permission::PublishWebsiteContent);
    }
}
