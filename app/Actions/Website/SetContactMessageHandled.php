<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Enums\Permission;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class SetContactMessageHandled
{
    public function handle(User $actor, ContactMessage $message, bool $handled): void
    {
        Gate::forUser($actor)->authorize(Permission::ViewContactInbox->value);
        $message->handled_at = $handled ? ($message->handled_at ?? now()) : null;
        $message->save();
    }
}
