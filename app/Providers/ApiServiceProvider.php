<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Contracts\HasApiTokens;

/**
 * Registration for the external API module; its routes are mounted on the API domain in bootstrap/app.php.
 */
final class ApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api-two-factor', fn (Request $request): Limit => Limit::perMinute(5)->by('challenge:'.hash('sha256', $request->string('challenge')->toString()).'|'.$request->ip()));

        RateLimiter::for('api', function (Request $request): Limit {
            $user = $request->user();
            $token = $user instanceof HasApiTokens ? $user->currentAccessToken() : null;

            return Limit::perMinute((int) config('api.rate_limit'))->by($token !== null ? 'token:'.$token->getKey() : 'ip:'.$request->ip());
        });
    }
}
