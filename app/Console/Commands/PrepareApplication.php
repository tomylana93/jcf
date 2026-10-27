<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('init:prepare {--installer : Reject automatic Laravel Installer setup until its installation path is validated}')]
#[Description('Prepare an application key without connecting to a database or provisioning accounts')]
final class PrepareApplication extends Command
{
    public function handle(): int
    {
        if ($this->option('installer')) {
            $this->error('Automatic Laravel Installer setup is not supported yet. Use Composer create-project, configure separate PostgreSQL databases, then run composer setup.');

            return self::FAILURE;
        }

        if (app()->configurationIsCached()) {
            $this->error('Clear cached configuration with php artisan config:clear before preparing this application.');

            return self::FAILURE;
        }

        if (blank(config('app.key')) && $this->call('key:generate', ['--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('Preparation complete. Configure PostgreSQL DB_DATABASE and a separate DB_TEST_DATABASE, application identity, and SUPERADMIN credentials before running composer setup in local/testing.');

        return self::SUCCESS;
    }
}
