<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\TemporaryDirectory\TemporaryDirectory;

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
    it('rejects unsupported or unsafe database configuration before any setup command', function (string $key, mixed $value): void {
        $default = config('database.default');
        config(['database.connections.setup_test' => config('database.connections.pgsql'), 'database.default' => 'setup_test']);
        $key = str_replace('database.connections.pgsql.', 'database.connections.setup_test.', $key);
        config([$key => $value, 'app.key' => null]);

        artisan('init:app')->expectsOutput('Configure PostgreSQL DB_DATABASE and a distinct DB_TEST_DATABASE for this application before running init:app. Do not use the JCF foundation database.')->assertFailed();

        config(['database.default' => $default]);
        DB::purge('setup_test');
        expect(config('setup.calls'))->toBe([]);
    })->with([
        'unsupported driver' => ['database.connections.pgsql.driver', 'sqlite'],
        'missing database' => ['database.connections.pgsql.database', ''],
        'missing testing database' => ['database.testing_database', ''],
        'shared development database' => ['database.testing_database', 'jcf'],
    ]);

    it('reports an unreachable database without running setup or exposing credentials', function (): void {
        $default = config('database.default');
        config(['database.connections.unreachable' => [
            ...config('database.connections.pgsql'),
            'url' => null,
            'host' => '127.0.0.1',
            'port' => 1,
            'password' => 'must-not-be-printed',
        ], 'database.default' => 'unreachable']);

        artisan('init:app')->expectsOutput('Cannot connect to PostgreSQL. Check DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_URL and credentials, then rerun init:app. No setup commands were run.')->assertFailed();

        config(['database.default' => $default]);
        DB::purge('unreachable');
        expect(config('setup.calls'))->toBe([]);
    });
    it('refuses the foundation database from a derived application directory', function (): void {
        $originalPath = base_path();
        app()->setBasePath(sys_get_temp_dir().'/parcel-app');
        config(['database.connections.foundation' => [
            ...config('database.connections.pgsql'), 'database' => 'jcf', 'url' => null,
        ], 'database.default' => 'foundation']);

        try {
            artisan('init:app')->expectsOutput('Configure PostgreSQL DB_DATABASE and a distinct DB_TEST_DATABASE for this application before running init:app. Do not use the JCF foundation database.')->assertFailed();

            expect(config('setup.calls'))->toBe([]);
        } finally {
            app()->setBasePath($originalPath);
            config(['database.default' => 'pgsql']);
            DB::purge('foundation');
        }
    });
    it('requires clearing cached configuration before setup', function (): void {
        $directory = (new TemporaryDirectory)->create();
        $cachePath = $directory->path('config.php');
        File::put($cachePath, '<?php return '.var_export(config()->all(), true).';');
        $process = new Symfony\Component\Process\Process([PHP_BINARY, 'artisan', 'init:app', '--no-interaction'], base_path(), [
            'APP_CONFIG_CACHE' => $cachePath,
        ]);

        try {
            $process->run();

            expect($process->isSuccessful())->toBeFalse();
            expect($process->getOutput())->toContain('Clear cached configuration');
        } finally {
            $directory->delete();
        }
    });
});
