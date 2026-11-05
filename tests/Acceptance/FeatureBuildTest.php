<?php

declare(strict_types=1);

use App\Enums\Module;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

describe('Clean deployment builds', function (): void {
    it('builds client and SSR without inheriting runtime feature routes', function (): void {
        withIsolatedRestoreDatabase(function (string $connection): void {
            $db = DB::connection($connection);
            $directory = new TemporaryDirectory(storage_path('framework/testing/feature-build-'.getmypid()));
            $directory->create();
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => $db->getDatabaseName().'_development', 'DB_TEST_DATABASE' => $db->getDatabaseName(), 'DB_URL' => '',
                'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'),
                'DB_USERNAME' => $db->getConfig('username'), 'DB_PASSWORD' => $db->getConfig('password'),
                'APP_CONFIG_CACHE' => $directory->path('config.php'), 'APP_ROUTES_CACHE' => $directory->path('routes.php'),
                'FEATURE_CHAT' => 'true', 'FEATURE_DOCUMENTATION' => 'true', 'FEATURE_BACKUP' => 'true',
                'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
            ];
            foreach (array_keys(Module::settingsClasses()) as $module) {
                $environment['FEATURE_'.mb_strtoupper($module)] = 'false';
            }
            $run = static fn (array $args): Process => new Process($args, base_path(), $environment, timeout: 180)->mustRun();
            try {
                $run([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction']);
                $uncached = $run([PHP_BINARY, 'artisan', 'route:list', '--json'])->getOutput();
                $run([PHP_BINARY, 'artisan', 'config:cache']);
                $run([PHP_BINARY, 'artisan', 'route:cache']);
                $before = $run([PHP_BINARY, 'artisan', 'route:list', '--json'])->getOutput();
                $cacheHash = hash_file('sha256', $directory->path('routes.php'));
                foreach (['actions', 'routes', 'wayfinder'] as $generated) {
                    File::deleteDirectory(resource_path('js/'.$generated));
                }
                $run(['composer', 'build:ssr']);
                expect(public_path('build/manifest.json'))->toBeFile()
                    ->and(base_path('bootstrap/ssr/app.js'))->toBeFile();
                foreach (['chat', 'documentation', 'master-data/backups'] as $route) {
                    expect(resource_path('js/routes/'.$route.'/index.ts'))->toBeFile();
                }
                expect($run([PHP_BINARY, 'artisan', 'route:list', '--json'])->getOutput())->toBe($before)
                    ->and(hash_file('sha256', $directory->path('routes.php')))->toBe($cacheHash);
                $run([PHP_BINARY, 'artisan', 'config:clear']);
                $run([PHP_BINARY, 'artisan', 'route:clear']);
                expect($run([PHP_BINARY, 'artisan', 'route:list', '--json'])->getOutput())->toBe($uncached);
                foreach (array_keys(Module::settingsClasses()) as $module) {
                    expect($db->table('settings')->where('group', $module)->count())->toBe(0);
                }
            } finally {
                $directory->delete();
            }
        });
    });
});
