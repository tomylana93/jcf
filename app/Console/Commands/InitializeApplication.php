<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

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
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
