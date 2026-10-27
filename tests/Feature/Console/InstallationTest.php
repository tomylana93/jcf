<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

describe('Explicit application installation', function (): void {
    it('migrates a disposable PostgreSQL database and preserves the key and administrator on rerun', function (): void {
        withIsolatedRestoreDatabase(function (string $connection): void {
            $directory = (new TemporaryDirectory)->create();
            $database = DB::connection($connection)->getConfig();
            $process = new Process([PHP_BINARY, '-r', <<<'PHP'
                require "vendor/autoload.php";
                $app = require "bootstrap/app.php";
                $app->usePublicPath(getenv("INSTALL_PUBLIC_PATH"));
                $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                $key = config("app.key");
                $first = Illuminate\Support\Facades\Artisan::call("init:app", ["--no-interaction" => true]);
                if ($first !== 0) {
                    echo Illuminate\Support\Facades\Artisan::output();
                    exit($first);
                }
                $original = App\Models\User::query()->sole()->getRawOriginal();
                $migrations = Illuminate\Support\Facades\DB::table("migrations")->count();
                $second = Illuminate\Support\Facades\Artisan::call("init:app", ["--no-interaction" => true]);
                echo json_encode([
                    "rerun" => $second,
                    "same_user" => App\Models\User::query()->sole()->getRawOriginal() === $original,
                    "same_key" => config("app.key") === $key,
                    "migrated" => $migrations > 0,
                    "same_migrations" => Illuminate\Support\Facades\DB::table("migrations")->count() === $migrations,
                    "storage_link" => is_link(public_path("storage")),
                ]);
                PHP], base_path(), [
                'APP_ENV' => 'local',
                'APP_KEY' => config('app.key'),
                'INSTALL_PUBLIC_PATH' => $directory->path(),
                'DB_CONNECTION' => 'pgsql',
                'DB_URL' => '',
                'DB_HOST' => $database['host'],
                'DB_PORT' => (string) $database['port'],
                'DB_DATABASE' => $database['database'],
                'DB_TEST_DATABASE' => $database['database'].'_testing',
                'DB_USERNAME' => $database['username'],
                'DB_PASSWORD' => $database['password'],
                'SUPERADMIN_NAME' => 'Application Admin',
                'SUPERADMIN_EMAIL' => 'owner@example.com',
                'SUPERADMIN_PASSWORD' => 'Application-password-123!',
                'CACHE_STORE' => 'array',
            ]);

            try {
                $process->run();

                expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput())
                    ->and(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR))->toBe([
                        'rerun' => 0, 'same_user' => true, 'same_key' => true,
                        'migrated' => true, 'same_migrations' => true, 'storage_link' => true,
                    ]);
            } finally {
                File::delete($directory->path('storage'));
                $directory->delete();
            }
        });
    });
});
