<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects bearer tokens that belong to a disabled account.
 */
final class EnsureApiUserIsActive
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user instanceof User && $user->status === UserStatus::Disabled, 403, __('auth.disabled'));

        return $next($request);
    }
}
