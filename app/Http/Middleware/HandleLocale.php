<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class HandleLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        $localeCookie = $request->cookie('locale');
        $storedLocale = is_string($localeCookie) ? Locale::tryFrom($localeCookie) : null;
        $locale = $storedLocale ?? Locale::tryFrom($previousLocale) ?? Locale::English;

        app()->setLocale($locale->value);

        try {
            $response = $next($request);

            if ($localeCookie !== null && $storedLocale === null) {
                $response->headers->setCookie(Cookie::forget('locale'));
            }

            return $response;
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
