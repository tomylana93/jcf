<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\WebsiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePublicPagesEnabled
{
    public function __construct(public WebsiteSettings $settings) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->public_pages_enabled, 404);

        return $next($request);
    }
}
