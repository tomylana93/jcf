<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApiDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the first-party web application off the API host.
 */
final class RejectApiDomain
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(ApiDomain::matches($request), 404);

        return $next($request);
    }
}
