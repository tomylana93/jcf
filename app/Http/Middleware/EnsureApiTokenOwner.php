<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\ApiClient;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limits a route to tokens of one owner type, user or client, and rejects disabled owners.
 */
final class EnsureApiTokenOwner
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $owner): Response
    {
        $tokenable = Auth::guard('sanctum')->user();

        $allowed = match ($owner) {
            'user' => $tokenable instanceof User,
            'client' => $tokenable instanceof ApiClient,
            default => throw new InvalidArgumentException('Unknown API token owner: '.$owner),
        };
        abort_unless($allowed, 403, __('api_client.error.wrong_owner'));

        abort_if($tokenable instanceof User && $tokenable->status === UserStatus::Disabled, 403, __('auth.disabled'));
        abort_if($tokenable instanceof ApiClient && ! $tokenable->isActive(), 403, __('api_client.error.disabled'));

        return $next($request);
    }
}
