<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Description('Generate the complete build route catalog without using runtime caches')]
#[Signature('wayfinder:catalog {--with-form} {--skip-actions} {--skip-routes} {--path=}')]
final class GenerateWayfinderCatalog extends Command
{
    public function handle(): int
    {
        $arguments = [PHP_BINARY, 'artisan', 'wayfinder:generate', '--no-interaction'];
        foreach (['with-form', 'skip-actions', 'skip-routes'] as $option) {
            if ($this->option($option)) {
                $arguments[] = '--'.$option;
            }
        }
        $path = $this->option('path');
        if (is_string($path) && $path !== '') {
            $arguments[] = '--path='.$path;
        }

        $cache = storage_path('framework/cache/wayfinder-catalog-'.getmypid());
        $process = new Process($arguments, base_path(), [
            'FEATURE_WEBSITE' => 'true',
            'FEATURE_CHAT' => 'true',
            'FEATURE_DOCUMENTATION' => 'true',
            'FEATURE_BACKUP' => 'true',
            'APP_CONFIG_CACHE' => $cache.'-config.php',
            'APP_ROUTES_CACHE' => $cache.'-routes.php',
        ]);
        $process->setTimeout(null);

        return $process->run(function (string $type, string $output): void {
            $this->output->write($output);
        });
    }
}
