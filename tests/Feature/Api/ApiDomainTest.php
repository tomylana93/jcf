<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\AuthSettings;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

describe('API domain routing', function (): void {
    it('serves versioned API routes on the API host', function (): void {
        expect(parse_url(route('api.v1.ping'), PHP_URL_HOST))->toBe('api.jcf.test')
            ->and(parse_url(route('api.v1.ping'), PHP_URL_PATH))->toBe('/v1/ping');

        getJson(route('api.v1.ping'))->assertOk()->assertExactJson(['status' => 'ok', 'version' => 'v1']);
    });

    it('returns 404 for API routes on the application host', function (): void {
        getJson('/v1/ping')->assertNotFound();
    });

    it('returns 404 JSON for web routes on the API host without starting a session', function (): void {
        get('https://api.jcf.test/login')->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertCookieMissing(config('session.cookie'));
    });

    it('serves web routes on the API host when the API feature is off', function (): void {
        config(['features.api' => false]);

        get('https://api.jcf.test/login')->assertOk();
    });

    it('does not register API routes when the API feature is off', function (string $variable, string $value): void {
        $original = getenv($variable);
        $originalApplication = app();
        putenv("{$variable}={$value}");
        $_ENV[$variable] = $_SERVER[$variable] = $value;

        try {
            $application = require base_path('bootstrap/app.php');
            $application->make(Kernel::class)->bootstrap();

            expect($application['router']->has('api.v1.ping'))->toBeFalse();
        } finally {
            putenv("{$variable}={$original}");
            $_ENV[$variable] = $_SERVER[$variable] = (string) $original;
            Container::setInstance($originalApplication);
        }
    })->with([['FEATURE_API', 'false'], ['API_DOMAIN', '']]);

    it('throttles API requests with 429', function (): void {
        config(['api.rate_limit' => 2]);

        getJson(route('api.v1.ping'))->assertOk();
        getJson(route('api.v1.ping'))->assertOk();
        getJson(route('api.v1.ping'))->assertTooManyRequests();
    });
});

describe('API authentication', function (): void {
    it('returns 401 JSON when no token is provided', function (): void {
        get(route('api.v1.user'))->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    });

    it('returns 401 when only a web session is present', function (): void {
        actingAs(User::factory()->create(), 'web');

        getJson(route('api.v1.user'))->assertUnauthorized();
    });

    it('returns the token owner', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        getJson(route('api.v1.user'))->assertOk()
            ->assertExactJson(['data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'timezone' => $user->timezone,
            ]]);
    });

    it('returns 403 for a disabled account', function (): void {
        Sanctum::actingAs(User::factory()->disabled()->create());

        getJson(route('api.v1.user'))->assertForbidden()->assertJsonPath('message', __('auth.disabled'));
    });

    it('returns 403 for an unverified email when verification is required', function (bool $required, int $status): void {
        resolve(AuthSettings::class)->fill(['email_verification_required' => $required])->save();
        Sanctum::actingAs(User::factory()->unverified()->create());

        get(route('api.v1.user'))->assertStatus($status);
    })->with([[true, 403], [false, 200]]);
});
