<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\UserStatus;
use App\Models\User;

final class DisableAccount
{
    public function handle(User $user): void
    {
        $user->status = UserStatus::Disabled;
        $user->save();
    }
}
