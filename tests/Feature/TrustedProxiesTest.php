<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\withHeaders;

const TRUSTED_PROXY_ADDRESS = '10.88.0.1';

/**
 * Exhaust the login limiter for one email and client address.
 */
function exhaustLoginAttemptsFor(string $email, string $address): void
{
    RateLimiter::increment(md5('login'.implode('|', [$email, $address])), amount: 5);
}

describe('Trusted proxies', function (): void {
    it('throttles logins per forwarded client behind a configured proxy', function (): void {
        config(['trustedproxy.proxies' => TRUSTED_PROXY_ADDRESS]);
        $user = User::factory()->create();
        exhaustLoginAttemptsFor($user->email, '203.0.113.10');

        $otherClient = withHeaders(['X-Forwarded-For' => '203.0.113.20'])
            ->withServerVariables(['REMOTE_ADDR' => TRUSTED_PROXY_ADDRESS])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
        $limitedClient = withHeaders(['X-Forwarded-For' => '203.0.113.10'])
            ->withServerVariables(['REMOTE_ADDR' => TRUSTED_PROXY_ADDRESS])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

        expect($otherClient->getStatusCode())->toBe(302)
            ->and($limitedClient->getStatusCode())->toBe(429);
    });

    it('ignores forwarded client addresses when no proxy is configured', function (): void {
        $user = User::factory()->create();
        exhaustLoginAttemptsFor($user->email, TRUSTED_PROXY_ADDRESS);

        $response = withHeaders(['X-Forwarded-For' => '203.0.113.20'])
            ->withServerVariables(['REMOTE_ADDR' => TRUSTED_PROXY_ADDRESS])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertTooManyRequests();
    });

    it('treats forwarded HTTPS as secure only from a configured proxy', function (?string $proxies, bool $expected): void {
        app()->instance('env', 'production');
        config(['trustedproxy.proxies' => $proxies]);

        $response = withHeaders(['X-Forwarded-Proto' => 'https'])
            ->withServerVariables(['REMOTE_ADDR' => TRUSTED_PROXY_ADDRESS])
            ->get('http://'.parse_url((string) config('app.url'), PHP_URL_HOST).route('login', absolute: false));

        $expected
            ? $response->assertHeader('Strict-Transport-Security')
            : $response->assertHeaderMissing('Strict-Transport-Security');
    })->with([
        'configured proxy' => [TRUSTED_PROXY_ADDRESS, true],
        'no proxy configured' => [null, false],
    ]);
});
