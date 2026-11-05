<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\ApiSettings;
use App\Settings\BackupSettings;
use App\Settings\ChatSettings;
use App\Settings\DocumentationSettings;

final class FeatureManager
{
    /** @var array<string, class-string<ApiSettings|BackupSettings|ChatSettings|DocumentationSettings>> */
    public const array SETTINGS = [
        'chat' => ChatSettings::class,
        'documentation' => DocumentationSettings::class,
        'backup' => BackupSettings::class,
        'api' => ApiSettings::class,
    ];

    /** @var array<string, bool> */
    private array $enabled = [];

    public function available(string $module): bool
    {
        return isset(self::SETTINGS[$module]) && config('features.'.$module) === true
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
     *     chat: array{available: bool, enabled: bool},
     *     documentation: array{available: bool, enabled: bool},
     *     backup: array{available: bool, enabled: bool},
     *     api: array{available: bool, enabled: bool},
     *     realtime: bool
     * }
     */
    public function state(): array
    {
        return [
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
            'api' => [
                'available' => $this->available('api'),
                'enabled' => $this->enabled('api'),
            ],
            'realtime' => ! in_array(config('broadcasting.default'), [null, 'null'], true),
        ];
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
