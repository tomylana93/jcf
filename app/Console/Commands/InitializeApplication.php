<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('init:app')]
#[Description('Initialize a local starter kit without replacing an existing application key')]
final class InitializeApplication extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Application setup may only run in local or testing environments.');

            return self::FAILURE;
        }

        if (app()->configurationIsCached()) {
            $this->error('Clear cached configuration with php artisan config:clear before initializing this application.');

            return self::FAILURE;
        }

        try {
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            $testingDatabase = config('database.testing_database');

            if ($connection->getDriverName() !== 'pgsql' || blank($database) || blank($testingDatabase)
                || config('database.development_database') === $testingDatabase
                || (basename(base_path()) !== 'jcf' && in_array($database, ['jcf', 'jcf_testing'], true))) {
                $this->error('Configure PostgreSQL DB_DATABASE and a distinct DB_TEST_DATABASE for this application before running init:app. Do not use the JCF foundation database.');

                return self::FAILURE;
            }

            $connection->getPdo();
        } catch (Throwable) {
            $this->error('Cannot connect to PostgreSQL. Check DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_URL and credentials, then rerun init:app. No setup commands were run.');

            return self::FAILURE;
        }

        if (blank(config('app.key')) && $this->call('key:generate', ['--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $commands = [
            'migrate' => ['--force' => true],
            'init:permission' => [],
            'init:superadmin' => [],
            'storage:link' => [],
        ];

        foreach ($commands as $command => $arguments) {
            if ($this->call($command, [...$arguments, '--no-interaction' => true]) !== self::SUCCESS) {
                $this->error('Setup stopped at '.$command.'. Fix the reported problem, then rerun init:app.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
