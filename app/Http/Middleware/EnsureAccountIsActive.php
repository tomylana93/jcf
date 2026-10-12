<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($response = $this->rejectDisabledAccount($request)) {
            return $response;
        }

        $response = $next($request);

        return $this->rejectDisabledAccount($request) ?? $response;
    }

    private function rejectDisabledAccount(Request $request): ?RedirectResponse
    {
        if ($request->user()?->status !== UserStatus::Disabled) {
            return null;
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->withErrors(['email' => __('auth.disabled')]);
    }
}
