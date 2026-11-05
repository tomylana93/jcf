<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use App\Models\User;

interface SearchProvider
{
    public function key(): string;

    /** @return list<array<string, mixed>> */
    public function handle(User $user, string $query): array;
}
