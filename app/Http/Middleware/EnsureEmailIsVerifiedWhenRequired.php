<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\AuthSettings;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailIsVerifiedWhenRequired
{
    public function __construct(public AuthSettings $settings, public EnsureEmailIsVerified $verification) {}

    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (! $this->settings->email_verification_required) {
            return $next($request);
        }

        return $this->verification->handle($request, $next, $redirectToRoute);
    }
}
