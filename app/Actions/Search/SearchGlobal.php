<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\User;
use App\Support\Contracts\SearchProvider;

final readonly class SearchGlobal
{
    /** @param iterable<SearchProvider> $providers */
    public function __construct(private iterable $providers) {}

    /** @return array<string, list<array<string, mixed>>> */
    public function handle(User $user, string $query): array
    {
        $results = [];
        foreach ($this->providers as $provider) {
            $results[$provider->key()] = $provider->handle($user, $query);
        }

        return $results;
    }
}
