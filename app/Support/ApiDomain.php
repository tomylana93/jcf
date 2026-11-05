<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Module;
use Illuminate\Http\Request;

final class ApiDomain
{
    public static function enabled(): bool
    {
        return Module::Api->available();
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
