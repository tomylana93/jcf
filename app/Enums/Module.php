<?php

declare(strict_types=1);

namespace App\Enums;

use App\Providers\ChatServiceProvider;
use App\Providers\DocumentationServiceProvider;
use App\Settings\ApiSettings;
use App\Settings\BackupSettings;
use App\Settings\ChatSettings;
use App\Settings\DocumentationSettings;
use App\Support\ApiDomain;
use Illuminate\Support\ServiceProvider;

enum Module: string
{
    case Chat = 'chat';
    case Documentation = 'documentation';
    case Backup = 'backup';
    case Api = 'api';

    /** @return array<string, class-string<ApiSettings|BackupSettings|ChatSettings|DocumentationSettings>> */
    public static function settingsClasses(): array
    {
        $classes = [];
        foreach (self::cases() as $module) {
            $classes[$module->value] = $module->settingsClass();
        }

        return $classes;
    }

    /**
     * Whether the environment hard flag enables this module's schema, settings and routes.
     */
    public function flagged(): bool
    {
        return config('features.'.$this->value) === true;
    }

    /**
     * Whether the module is flagged and its runtime requirements are met.
     */
    public function available(): bool
    {
        return $this->flagged() && match ($this) {
            self::Api => ApiDomain::domain() !== null,
            default => true,
        };
    }

    /** @return class-string<ApiSettings|BackupSettings|ChatSettings|DocumentationSettings> */
    public function settingsClass(): string
    {
        return match ($this) {
            self::Chat => ChatSettings::class,
            self::Documentation => DocumentationSettings::class,
            self::Backup => BackupSettings::class,
            self::Api => ApiSettings::class,
        };
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Chat => [Permission::UseChat],
            self::Documentation => [Permission::ViewDocumentation, Permission::ManageDocumentation],
            self::Backup => [Permission::ManageBackups],
            self::Api => [Permission::ViewApiClients, Permission::ManageApiClients],
        };
    }

    /** @return class-string<ServiceProvider>|null */
    public function provider(): ?string
    {
        return match ($this) {
            self::Chat => ChatServiceProvider::class,
            self::Documentation => DocumentationServiceProvider::class,
            default => null,
        };
    }

    public function migrationsPath(): string
    {
        return database_path('migrations/'.$this->value);
    }

    public function settingsMigrationsPath(): string
    {
        return database_path('settings/'.$this->value);
    }
}
