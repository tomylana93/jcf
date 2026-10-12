<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final readonly class ResolveTimezone
{
    public function __construct(private GetTimezoneOptions $getTimezoneOptions) {}

    /** @return array{current: string, source: 'profile'|'device'|'default'} */
    public function handle(?User $user, mixed $cookie): array
    {
        $options = $this->getTimezoneOptions->handle();

        if ($user?->timezone !== null && in_array($user->timezone, $options, true)) {
            return ['current' => $user->timezone, 'source' => 'profile'];
        }

        if (is_string($cookie) && in_array($cookie, $options, true)) {
            return ['current' => $cookie, 'source' => 'device'];
        }

        return ['current' => 'UTC', 'source' => 'default'];
    }
}
