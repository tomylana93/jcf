<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Module;
use App\Support\Exceptions\ModuleUnavailableException;

final class FeatureManager
{
    /** @var array<string, bool> */
    private array $enabled = [];

    public function available(Module|string $module): bool
    {
        return $this->resolveModule($module)?->available() ?? false;
    }

    public function enabled(Module|string $module): bool
    {
        $module = $this->resolveModule($module);

        if ($module === null || ! $module->available()) {
            return false;
        }

        // Console services may survive multiple jobs or scheduler evaluations.
        if (app()->runningInConsole()) {
            $settings = resolve($module->settingsClass());
            $settings->refresh();

            return $settings->enabled;
        }

        return $this->enabled[$module->value] ??= resolve($module->settingsClass())->enabled;
    }

    /**
     * @throws ModuleUnavailableException
     */
    public function ensureEnabled(Module $module): void
    {
        throw_unless($this->enabled($module), ModuleUnavailableException::for($module));
    }

    public function moduleEnabled(?Module $module): bool
    {
        return $module === null || $this->enabled($module);
    }

    /** @return array<string, array{available: bool, enabled: bool}|bool> */
    public function state(): array
    {
        $state = [];
        foreach (Module::cases() as $module) {
            $state[$module->value] = [
                'available' => $module->available(),
                'enabled' => $this->enabled($module),
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

    private function resolveModule(Module|string $module): ?Module
    {
        return $module instanceof Module ? $module : Module::tryFrom($module);
    }
}
