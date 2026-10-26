<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\FeatureManager;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Spatie\LaravelSettings\Events\SettingsSaved;
use Spatie\LaravelSettings\SettingsContainer;

final class FeatureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(FeatureManager::class);

        $classes = config('settings.settings');
        $paths = config('settings.migrations_paths');
        foreach (FeatureManager::SETTINGS as $module => $class) {
            if (! config('features.'.$module)) {
                continue;
            }

            $classes[] = $class;
            $paths[] = database_path('settings/'.$module);
        }
        config([
            'settings.settings' => array_values(array_unique($classes)),
            'settings.migrations_paths' => array_values(array_unique($paths)),
            'settings.discovered_settings_cache_path' => base_path('bootstrap/cache/settings-'.md5(json_encode(config('features'), JSON_THROW_ON_ERROR))),
        ]);

        // Explicit registration is authoritative, including after a cached discovery run.
        $container = $this->app->make(SettingsContainer::class);
        $container->clearCache()->registerBindings();
        foreach (FeatureManager::SETTINGS as $module => $class) {
            if (! config('features.'.$module)) {
                app()->offsetUnset($class);
            }
            $this->app->beforeResolving($class, static function () use ($module): void {
                throw_unless(config('features.'.$module), LogicException::class, 'Settings unavailable for disabled module: '.$module);
            });
        }
    }

    public function boot(): void
    {
        foreach (array_keys(FeatureManager::SETTINGS) as $module) {
            if (config('features.'.$module) && is_dir(database_path('migrations/'.$module))) {
                $this->loadMigrationsFrom(database_path('migrations/'.$module));
            }
        }

        Event::listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if (in_array($event->settings::class, FeatureManager::SETTINGS, true)) {
                $this->app->make(FeatureManager::class)->reset();
                $this->app->instance($event->settings::class, $event->settings);
            }
        });
        Event::listen(JobProcessing::class, fn () => $this->app->make(FeatureManager::class)->reset());
    }
}
