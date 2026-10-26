<?php

declare(strict_types=1);

use App\Support\FeatureManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

describe('Clean deployment builds', function (): void {
    it('builds client and SSR without inheriting runtime feature routes', function (string $disabled, bool $cached): void {
        withIsolatedRestoreDatabase(function (string $connection) use ($disabled, $cached): void {
            $db = DB::connection($connection);
            $directory = new TemporaryDirectory(storage_path('framework/testing/feature-build-'.getmypid()));
            $directory->create();
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => $db->getDatabaseName(), 'DB_URL' => '',
                'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'),
                'DB_USERNAME' => $db->getConfig('username'), 'DB_PASSWORD' => $db->getConfig('password'),
                'APP_CONFIG_CACHE' => $directory->path('config.php'), 'APP_ROUTES_CACHE' => $directory->path('routes.php'),
                'FEATURE_WEBSITE' => 'true', 'FEATURE_CHAT' => 'true', 'FEATURE_DOCUMENTATION' => 'true', 'FEATURE_BACKUP' => 'true',
                'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
            ];
            foreach ($disabled === 'all' ? array_keys(FeatureManager::SETTINGS) : [$disabled] as $module) {
                $environment['FEATURE_'.mb_strtoupper($module)] = 'false';
            }
            $run = static fn (array $args): Process => new Process($args, base_path(), $environment, timeout: 180)->mustRun();
            try {
                $run([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction']);
                if ($cached) {
                    $run([PHP_BINARY, 'artisan', 'config:cache']);
                    $run([PHP_BINARY, 'artisan', 'route:cache']);
                }
                $before = $run([PHP_BINARY, 'artisan', 'route:list', '--json'])->getOutput();
                $cacheHash = $cached ? hash_file('sha256', $directory->path('routes.php')) : null;
                foreach (['actions', 'routes', 'wayfinder'] as $generated) {
                    File::deleteDirectory(resource_path('js/'.$generated));
                }
                $run(['composer', 'build:ssr']);
                expect(public_path('build/manifest.json'))->toBeFile()
                    ->and(base_path('bootstrap/ssr/app.js'))->toBeFile();
                foreach (['chat', 'website/settings', 'documentation', 'master-data/backups'] as $route) {
                    expect(resource_path('js/routes/'.$route.'/index.ts'))->toBeFile();
                }
                expect($run([PHP_BINARY, 'artisan', 'route:list', '--json'])->getOutput())->toBe($before);
                if ($cached) {
                    expect(hash_file('sha256', $directory->path('routes.php')))->toBe($cacheHash);
                }
                foreach ($disabled === 'all' ? array_keys(FeatureManager::SETTINGS) : [$disabled] as $module) {
                    expect($db->table('settings')->where('group', $module)->count())->toBe(0);
                }
            } finally {
                $directory->delete();
            }
        });
    })->with(['website', 'chat', 'documentation', 'backup', 'all'])->with([false, true]);
});
