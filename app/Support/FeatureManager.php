<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\BackupSettings;
use App\Settings\ChatSettings;
use App\Settings\DocumentationSettings;
use App\Settings\WebsiteSettings;

final class FeatureManager
{
    /** @var array<string, class-string<BackupSettings|ChatSettings|DocumentationSettings|WebsiteSettings>> */
    public const array SETTINGS = [
        'website' => WebsiteSettings::class,
        'chat' => ChatSettings::class,
        'documentation' => DocumentationSettings::class,
        'backup' => BackupSettings::class,
    ];

    /** @var array<string, bool> */
    private array $enabled = [];

    public function envEnabled(string $module): bool
    {
        return isset(self::SETTINGS[$module]) && config('features.'.$module) === true;
    }

    public function enabled(string $module): bool
    {
        if (! $this->envEnabled($module)) {
            return false;
        }

        // Console services may survive multiple jobs or scheduler evaluations.
        if (app()->runningInConsole()) {
            $settings = resolve(self::SETTINGS[$module]);
            $settings->refresh();

            return $settings->enabled;
        }

        return $this->enabled[$module] ??= resolve(self::SETTINGS[$module])->enabled;
    }

    public function publicWebsiteEnabled(): bool
    {
        return $this->enabled('website') && resolve(WebsiteSettings::class)->public_pages_enabled;
    }

    public function reset(): void
    {
        $this->enabled = [];
        foreach (self::SETTINGS as $class) {
            app()->forgetInstance($class);
        }

        $store = config('settings.cache.store');
        $store ??= config('cache.default');
        app()->forgetInstance('cache.__memoized:'.$store);
    }
}
