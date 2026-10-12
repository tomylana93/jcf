<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final readonly class DiscardTemporaryAvatar
{
    public function __construct(private GetTemporaryAvatar $getTemporaryAvatar) {}

    public function handle(User $user, string $uuid): void
    {
        $this->getTemporaryAvatar->handle($user, $uuid)->delete();
    }
}
