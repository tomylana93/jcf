<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final class UpdatePassword
{
    /**
     * Replace the password; the User model revokes the account's API tokens when it changes.
     */
    public function handle(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }
}
