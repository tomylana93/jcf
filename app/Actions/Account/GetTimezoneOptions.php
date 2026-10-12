<?php

declare(strict_types=1);

namespace App\Actions\Account;

use DateTimeZone;

final class GetTimezoneOptions
{
    /** @return list<string> */
    public function handle(): array
    {
        return array_values(array_filter(
            DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),
            fn (string $timezone): bool => $timezone === 'UTC' || str_contains($timezone, '/'),
        ));
    }
}
