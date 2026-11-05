<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\ApiClient;
use App\Models\User;

final class RevokeApiToken
{
    /**
     * Revoke one of the owner's tokens. Tokens of other owners are reported as missing.
     */
    public function handle(User|ApiClient $owner, int $tokenId): void
    {
        $owner->tokens()->whereKey($tokenId)->firstOrFail()->delete();
    }
}
