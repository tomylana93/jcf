<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\FeatureManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureFeatureEnabled
{
    public function __construct(private FeatureManager $features) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($this->features->enabled($module), 404);

        return $next($request);
    }
}
