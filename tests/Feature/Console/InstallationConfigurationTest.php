<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

describe('Installation database configuration', function (): void {
    it('resolves a separate testing database without carrying the development URL', function (): void {
        $process = new Process([PHP_BINARY, '-r', <<<'PHP'
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            echo json_encode([
                config("database.connections.pgsql.database"),
                config("database.connections.pgsql.url"),
                config("backup.backup.name"),
                config("backup.monitor_backups.0.name"),
                config("cache.prefix"),
                config("session.cookie"),
                config("database.redis.options.prefix"),
            ]);
            PHP], base_path(), [
            'APP_ENV' => 'testing',
            'APP_INSTANCE' => 'parcel-app',
            'BACKUP_NAME' => false,
            'CACHE_PREFIX' => false,
            'SESSION_COOKIE' => false,
            'REDIS_PREFIX' => false,
            'DB_DATABASE' => 'ignored_development',
            'DB_TEST_DATABASE' => 'parcel_testing',
            'DB_URL' => 'postgres://user:secret@127.0.0.1/parcel',
        ]);

        $process->run();

        expect($process->isSuccessful())->toBeTrue();
        expect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR))->toBe([
            'parcel_testing', null, 'parcel-app', 'parcel-app', 'parcel-app-cache-', 'parcel-app-session', 'parcel-app-database-',
        ]);
    });

    it('refuses a testing target that matches the effective development database', function (): void {
        $process = new Process([PHP_BINARY, 'artisan', 'list', '--raw'], base_path(), [
            'APP_ENV' => 'testing',
            'DB_DATABASE' => 'different_name',
            'DB_TEST_DATABASE' => 'parcel',
            'DB_URL' => 'postgres://user:secret@127.0.0.1/parcel',
        ]);

        $process->run();

        expect($process->isSuccessful())->toBeFalse();
        expect($process->getOutput())->toContain('DB_TEST_DATABASE distinct from DB_DATABASE');
    });
});
