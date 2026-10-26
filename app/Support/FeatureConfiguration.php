<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

final class FeatureConfiguration extends LoadConfiguration
{
    /**
     * @param  Application  $app
     * @return array<string, string>
     */
    protected function getConfigurationFiles(mixed $app): array
    {
        $files = parent::getConfigurationFiles($app);
        $features = require $files['features'];
        $app->make(Repository::class)->set('features', $features);
        unset($files['features']);

        foreach (['backup', 'website'] as $module) {
            if (! $features[$module]) {
                unset($files[$module]);
            }
        }

        return $files;
    }
}
