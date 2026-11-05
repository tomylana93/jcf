<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Laravel\Passkeys\Passkey;

final readonly class GetPasskeys
{
    /** @return list<array{id: int|string, name: string, authenticator: mixed, created_at: string, last_used_at: string|null}> */
    public function handle(User $user): array
    {
        return array_values($user->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at' => $passkey->created_at->toIso8601String(),
                'last_used_at' => $passkey->last_used_at?->toIso8601String(),
            ])
            ->all());
    }
}
