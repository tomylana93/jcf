<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final class RevokeApiToken
{
    /**
     * Revoke one of the user's tokens. Tokens of other owners are reported as missing.
     */
    public function handle(User $user, int $tokenId): void
    {
        $user->tokens()->whereKey($tokenId)->firstOrFail()->delete();
    }
}
