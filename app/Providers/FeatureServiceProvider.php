<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Identity\User\SearchUsers;
use App\Actions\Search\SearchGlobal;
use App\Actions\System\GetSystemHealth;
use App\Enums\Module;
use App\Settings\MailSettings;
use App\Support\Contracts\HealthCheck;
use App\Support\Contracts\SearchProvider;
use App\Support\FeatureManager;
use Illuminate\Contracts\Foundation\Application;
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
        foreach (Module::cases() as $module) {
            $this->app->register($module->provider());
        }
        $this->app->tag(SearchUsers::class, SearchProvider::class);
        $this->app->bind(SearchGlobal::class, fn (Application $app): SearchGlobal => new SearchGlobal($app->tagged(SearchProvider::class)));
        $this->app->bind(GetSystemHealth::class, fn (Application $app): GetSystemHealth => new GetSystemHealth($app->make(MailSettings::class), $app->tagged(HealthCheck::class)));

        $classes = config('settings.settings');
        $paths = config('settings.migrations_paths');
        foreach (Module::cases() as $module) {
            if (! $module->flagged()) {
                continue;
            }

            $classes[] = $module->settingsClass();
            $paths[] = $module->settingsMigrationsPath();
        }
        config([
            'settings.settings' => array_values(array_unique($classes)),
            'settings.migrations_paths' => array_values(array_unique($paths)),
            'settings.discovered_settings_cache_path' => base_path('bootstrap/cache/settings-'.md5(json_encode(config('features'), JSON_THROW_ON_ERROR))),
        ]);

        // Explicit registration is authoritative, including after a cached discovery run.
        $container = $this->app->make(SettingsContainer::class);
        $container->clearCache()->registerBindings();
        foreach (Module::cases() as $module) {
            $class = $module->settingsClass();
            if (! $module->flagged()) {
                app()->offsetUnset($class);
            }
            $this->app->beforeResolving($class, static function () use ($module): void {
                throw_unless($module->flagged(), LogicException::class, 'Settings unavailable for disabled module: '.$module->value);
            });
        }
    }

    public function boot(): void
    {
        foreach (Module::cases() as $module) {
            if ($module->flagged() && is_dir($module->migrationsPath())) {
                $this->loadMigrationsFrom($module->migrationsPath());
            }
        }

        Event::listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if (in_array($event->settings::class, Module::settingsClasses(), true)) {
                $this->app->make(FeatureManager::class)->reset();
                $this->app->instance($event->settings::class, $event->settings);
            }
        });
        Event::listen(JobProcessing::class, fn () => $this->app->make(FeatureManager::class)->reset());
    }
}
