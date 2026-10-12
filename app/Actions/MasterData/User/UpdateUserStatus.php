<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Enums\UserStatus;
use App\Models\User;

final class UpdateUserStatus
{
    public function handle(User $user, UserStatus $status): void
    {
        $user->status = $status;
        $user->save();
    }
}
