<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

final class ApiDomain
{
    public static function enabled(): bool
    {
        return config('features.api') === true && self::domain() !== null;
    }

    public static function domain(): ?string
    {
        $domain = config('api.domain');

        return is_string($domain) && $domain !== '' ? mb_strtolower($domain) : null;
    }

    public static function matches(Request $request): bool
    {
        return self::enabled() && mb_strtolower($request->getHost()) === self::domain();
    }
}
