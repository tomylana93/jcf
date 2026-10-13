<?php

declare(strict_types=1);

use App\Settings\BrandSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

describe('safe web error pages', function (): void {
    beforeEach(function (): void {
        app()->instance('env', 'production');
        config(['app.debug' => false]);
    });

    it('renders supported errors without sensitive shared data', function (int $status): void {
        Route::get('/error-test', function () use ($status): never {
            Inertia::share('secret', fn () => throw new RuntimeException('Shared data must not resolve'));
            abort($status, 'Sensitive exception detail');
        });

        get('/error-test')->assertStatus($status)
            ->assertInertia(fn (Assert $page) => $page->component('ErrorPage')
                ->where('status', $status)->missing('secret')->missing('auth')->missing('brand'))
            ->assertDontSee('Sensitive exception detail');
    })->with([403, 404, 419, 500, 503]);

    it('handles unknown routes even when settings cannot be resolved', function (): void {
        app()->bind(GeneralSettings::class, fn () => throw new RuntimeException('Database unavailable'));
        app()->bind(BrandSettings::class, fn () => throw new RuntimeException('Database unavailable'));

        get('/missing-page')->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('ErrorPage')->where('status', 404));
    });

    it('renders a server error when home settings fail', function (): void {
        app()->bind(GeneralSettings::class, fn () => throw new RuntimeException('Database unavailable'));
        app()->bind(BrandSettings::class, fn () => throw new RuntimeException('Database unavailable'));

        get('/')->assertInternalServerError()
            ->assertInertia(fn (Assert $page) => $page->component('ErrorPage')->where('status', 500))
            ->assertDontSee('Database unavailable');
    });

    it('returns the Inertia protocol for client visits', function (): void {
        get('/missing-page', ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
            ->assertNotFound()->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'ErrorPage')->assertJsonPath('props.status', 404);
    });

    it('preserves JSON negotiation for web and API errors', function (string $path, bool $json): void {
        Route::get($path, fn () => abort(503));

        ($json ? getJson($path) : get($path))->assertServiceUnavailable()
            ->assertHeader('Content-Type', 'application/json')->assertJsonMissing(['component' => 'ErrorPage']);
    })->with([['/error-json', true], ['/api/error-json', false]]);

    it('preserves retry instructions', function (): void {
        Route::get('/busy', fn () => abort(503, '', ['Retry-After' => '60']));

        get('/busy')->assertServiceUnavailable()->assertHeader('Retry-After', '60');
    });

    it('leaves unsupported errors to Laravel', function (): void {
        Route::get('/unauthorized-test', fn () => abort(401));

        get('/unauthorized-test')->assertUnauthorized()->assertDontSee('data-page');
    });
});
