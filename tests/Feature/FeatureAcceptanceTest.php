<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Support\FeatureManager;
use Illuminate\Support\Facades\DB;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

describe('Feature deployment acceptance', function (): void {
    it('serves core requests with only installed module schemas', function (string $module, bool $hard, bool $soft): void {
        withIsolatedRestoreDatabase(function (string $connection) use ($module, $hard, $soft): void {
            $db = DB::connection($connection);
            $directory = new TemporaryDirectory(storage_path('framework/testing/feature-acceptance-'.getmypid()));
            $directory->create();
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => $db->getDatabaseName(), 'DB_URL' => '',
                'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'),
                'DB_USERNAME' => $db->getConfig('username'), 'DB_PASSWORD' => $db->getConfig('password'),
                'APP_CONFIG_CACHE' => $directory->path('config.php'), 'APP_ROUTES_CACHE' => $directory->path('routes.php'),
                'FEATURE_WEBSITE' => 'false', 'FEATURE_CHAT' => 'false', 'FEATURE_DOCUMENTATION' => 'false', 'FEATURE_BACKUP' => 'false',
                'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            ];
            if ($module !== 'all') {
                $environment['FEATURE_'.mb_strtoupper($module)] = $hard ? 'true' : 'false';
            }
            try {
                new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment)->mustRun();
                $process = new Process([PHP_BINARY, 'tests/Fixtures/feature-runtime.php', $module, $soft ? '1' : '0'], base_path(), $environment);
                $process->mustRun();
                $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
                expect($result['core'])->toBe(['/dashboard' => 200, '/settings' => 200, '/permissions' => 200, '/search?query=manual' => 200, '/system' => 200]);
                foreach (array_keys(FeatureManager::SETTINGS) as $name) {
                    expect($result['features'][$name]['enabled'])->toBe($name === $module && $hard && $soft);
                }
                expect($result['module_status'])->toBe($module === 'all' || ! $hard || ! $soft ? 404 : 200)
                    ->and($result['forbidden_queries'])->toBe([]);
                foreach (Permission::cases() as $permission) {
                    if ($permission->module() !== null && ! $result['features'][$permission->module()]['enabled']) {
                        expect($result['permissions'])->not->toContain($permission->value);
                    }
                }
                expect($result['cleanup_exit'])->toBe(0)
                    ->and($result['backup_schedules'])->toBe($module === 'backup' && $hard && $soft ? [false, true, true] : [false, false, false])
                    ->and($result['backup_detail'])->toBe($module === 'backup' && $hard && $soft ? 'not_configured' : 'disabled');
            } finally {
                $directory->delete();
            }
        });
    })->with([
        'website hard=1 soft=1' => ['website', true, true],
        'website hard=1 soft=0' => ['website', true, false],
        'website hard=0 soft=1' => ['website', false, true],
        'website hard=0 soft=0' => ['website', false, false],
        'chat hard=1 soft=1' => ['chat', true, true],
        'chat hard=1 soft=0' => ['chat', true, false],
        'chat hard=0 soft=1' => ['chat', false, true],
        'chat hard=0 soft=0' => ['chat', false, false],
        'documentation hard=1 soft=1' => ['documentation', true, true],
        'documentation hard=1 soft=0' => ['documentation', true, false],
        'documentation hard=0 soft=1' => ['documentation', false, true],
        'documentation hard=0 soft=0' => ['documentation', false, false],
        'backup hard=1 soft=1' => ['backup', true, true],
        'backup hard=1 soft=0' => ['backup', true, false],
        'backup hard=0 soft=1' => ['backup', false, true],
        'backup hard=0 soft=0' => ['backup', false, false],
        'all off' => ['all', false, false],
    ]);
});
