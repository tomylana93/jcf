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

    public function available(string $module): bool
    {
        return isset(self::SETTINGS[$module]) && config('features.'.$module) === true;
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
            $settings = resolve(self::SETTINGS[$module]);
            $settings->refresh();

            return $settings->enabled;
        }

        return $this->enabled[$module] ??= resolve(self::SETTINGS[$module])->enabled;
    }

    public function moduleEnabled(?string $module): bool
    {
        return $module === null || $this->enabled($module);
    }

    /**
     * @return array{
     *     website: array{available: bool, enabled: bool, public: bool},
     *     chat: array{available: bool, enabled: bool},
     *     documentation: array{available: bool, enabled: bool},
     *     backup: array{available: bool, enabled: bool},
     *     realtime: bool
     * }
     */
    public function state(): array
    {
        return [
            'website' => [
                'available' => $this->available('website'),
                'enabled' => $this->enabled('website'),
                'public' => $this->publicWebsiteEnabled(),
            ],
            'chat' => [
                'available' => $this->available('chat'),
                'enabled' => $this->enabled('chat'),
            ],
            'documentation' => [
                'available' => $this->available('documentation'),
                'enabled' => $this->enabled('documentation'),
            ],
            'backup' => [
                'available' => $this->available('backup'),
                'enabled' => $this->enabled('backup'),
            ],
            'realtime' => ! in_array(config('broadcasting.default'), [null, 'null'], true),
        ];
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
