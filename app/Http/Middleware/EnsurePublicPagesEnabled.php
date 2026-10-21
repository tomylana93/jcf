<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\FeatureManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePublicPagesEnabled
{
    public function __construct(public FeatureManager $features) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->features->publicWebsiteEnabled(), 404);

        return $next($request);
    }
}
