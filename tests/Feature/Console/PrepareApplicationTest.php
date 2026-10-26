<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function Pest\Laravel\artisan;

describe('Database-free preparation', function (): void {
    it('creates a missing key once without connecting to a database or provisioning accounts', function (): void {
        $directory = (new TemporaryDirectory)->create();
        $originalPath = app()->environmentPath();
        File::put($directory->path('.env'), "APP_KEY=\n");
        app()->useEnvironmentPath($directory->path());
        config(['app.key' => null]);
        DB::shouldReceive('connection')->never();

        try {
            artisan('init:prepare')->assertSuccessful();
            $key = config('app.key');
            $contents = File::get($directory->path('.env'));

            artisan('init:prepare')->assertSuccessful();

            expect($key)->toStartWith('base64:');
            expect(config('app.key'))->toBe($key);
            expect(File::get($directory->path('.env')))->toBe($contents);
        } finally {
            app()->useEnvironmentPath($originalPath);
            $directory->delete();
        }
    });

    it('rejects automatic installer setup before database and package manager rewrites', function (): void {
        DB::shouldReceive('connection')->never();

        artisan('init:prepare', ['--installer' => true])
            ->expectsOutput('Automatic Laravel Installer setup is not supported yet. Use Composer create-project, configure separate PostgreSQL databases, then run composer setup.')
            ->assertFailed();
    });
    it('runs the Composer creation hook successfully with no database configured', function (): void {
        $process = new Symfony\Component\Process\Process(['composer', 'run', 'post-create-project-cmd', '--no-interaction'], base_path(), [
            'APP_ENV' => 'local',
            'APP_KEY' => config('app.key'),
            'DB_CONNECTION' => 'pgsql',
            'DB_URL' => '',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '1',
            'DB_DATABASE' => '',
            'DB_TEST_DATABASE' => '',
        ]);

        $process->run();

        expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
        expect($process->getOutput())->toContain('Preparation complete. Configure PostgreSQL');
    });
    it('requires clearing cached configuration before setup', function (): void {
        $directory = (new TemporaryDirectory)->create();
        $cachePath = $directory->path('config.php');
        File::put($cachePath, '<?php return '.var_export(config()->all(), true).';');
        $process = new Symfony\Component\Process\Process([PHP_BINARY, 'artisan', 'init:prepare', '--no-interaction'], base_path(), [
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
