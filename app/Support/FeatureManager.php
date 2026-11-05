<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Module;

final class FeatureManager
{
    /** @var array<string, bool> */
    private array $enabled = [];

    public function available(string $module): bool
    {
        return Module::tryFrom($module) !== null && config('features.'.$module) === true
            && ($module !== 'api' || ApiDomain::domain() !== null);
    }

    public function envEnabled(string $module): bool
    {
        return $this->available($module);
    }

    public function enabled(string $module): bool
    {
        if (! $this->available($module)) {
            return false;
        }

        // Console services may survive multiple jobs or scheduler evaluations.
        if (app()->runningInConsole()) {
            $settings = resolve(Module::settingsClasses()[$module]);
            $settings->refresh();

            return $settings->enabled;
        }

        return $this->enabled[$module] ??= resolve(Module::settingsClasses()[$module])->enabled;
    }

    public function moduleEnabled(?string $module): bool
    {
        return $module === null || $this->enabled($module);
    }

    /** @return array<string, array{available: bool, enabled: bool}|bool> */
    public function state(): array
    {
        $state = [];
        foreach (Module::cases() as $module) {
            $state[$module->value] = [
                'available' => $this->available($module->value),
                'enabled' => $this->enabled($module->value),
            ];
        }
        $state['realtime'] = ! in_array(config('broadcasting.default'), [null, 'null'], true);

        return $state;
    }

    public function reset(): void
    {
        $this->enabled = [];
        foreach (Module::settingsClasses() as $class) {
            app()->forgetInstance($class);
        }

        $store = config('settings.cache.store');
        $store ??= config('cache.default');
        app()->forgetInstance('cache.__memoized:'.$store);
    }
}
