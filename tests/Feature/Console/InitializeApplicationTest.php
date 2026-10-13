<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    config(['setup.calls' => [], 'setup.failure' => null]);

    foreach (['key:generate', 'migrate', 'init:permission', 'init:superadmin', 'storage:link'] as $name) {
        Artisan::command($name.' {--force}', function () use ($name): int {
            config(['setup.calls' => [...config('setup.calls'), $name]]);

            return config('setup.failure') === $name ? 1 : 0;
        });
    }
});

describe('Application initialization', function (): void {
    it('rejects environments outside local and testing', function (string $environment): void {
        app()->instance('env', $environment);

        artisan('init:app')->expectsOutput('Application setup may only run in local or testing environments.')->assertFailed();

        expect(config('setup.calls'))->toBe([]);
    })->with(['production', 'staging']);

    it('preserves an existing key while initializing in order', function (string $environment): void {
        app()->instance('env', $environment);
        config(['app.key' => 'existing-key']);

        artisan('init:app')->assertSuccessful();

        expect(config('setup.calls'))->toBe(['migrate', 'init:permission', 'init:superadmin', 'storage:link'])
            ->and(config('app.key'))->toBe('existing-key');
    })->with(['local', 'testing']);

    it('generates a missing key before initializing the application', function (): void {
        config(['app.key' => null]);

        artisan('init:app')->assertSuccessful();

        expect(config('setup.calls'))->toBe(['key:generate', 'migrate', 'init:permission', 'init:superadmin', 'storage:link']);
    });

    it('stops immediately when a setup command fails', function (string $failed, array $expected): void {
        config(['app.key' => null, 'setup.failure' => $failed]);

        artisan('init:app')->assertFailed();

        expect(config('setup.calls'))->toBe($expected);
    })->with([
        'key failure' => ['key:generate', ['key:generate']],
        'migration failure' => ['migrate', ['key:generate', 'migrate']],
        'permission failure' => ['init:permission', ['key:generate', 'migrate', 'init:permission']],
        'administrator failure' => ['init:superadmin', ['key:generate', 'migrate', 'init:permission', 'init:superadmin']],
        'storage failure' => ['storage:link', ['key:generate', 'migrate', 'init:permission', 'init:superadmin', 'storage:link']],
    ]);
});
