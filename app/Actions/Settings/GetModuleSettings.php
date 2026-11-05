<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\Module;
use App\Support\FeatureManager;

final readonly class GetModuleSettings
{
    public function __construct(private FeatureManager $features) {}

    /** @return array<string, bool> */
    public function handle(): array
    {
        $modules = [];
        foreach (Module::settingsClasses() as $module => $class) {
            if ($this->features->available($module)) {
                $modules[$module] = $this->features->enabled($module);
            }
        }
        abort_if($modules === [], 404);

        return $modules;
    }
}
