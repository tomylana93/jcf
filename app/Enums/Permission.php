<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum Permission: string
{
    use HasLabel, HasOptions;

    case UseChat = 'chat.use';
    case ViewSystem = 'system.view';
    case ManageBackups = 'backups.manage';
    case ViewAuditLogs = 'audit.view';
    case ViewSettings = 'settings.view';
    case ManageSettings = 'settings.manage';
    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';
    case AssignUserPermissions = 'users.permissions.assign';
    case ViewDocumentation = 'documentation.view';
    case ManageDocumentation = 'documentation.manage';
    case ViewApiClients = 'api-clients.view';
    case ManageApiClients = 'api-clients.manage';

    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    public function groupLabel(): string
    {
        return __('permission.group.'.str_replace('-', '_', $this->group()));
    }

    public function module(): ?Module
    {
        foreach (Module::cases() as $module) {
            if (in_array($this, $module->permissions(), true)) {
                return $module;
            }
        }

        return null;
    }
}
