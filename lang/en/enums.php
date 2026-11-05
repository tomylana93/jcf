<?php

declare(strict_types=1);

return [
    'logo_type' => [
        'LogoOnly' => 'Logo only',
        'LogoText' => 'Logo + text',
    ],

    'brand_color' => [
        'Neutral' => 'Neutral',
        'Blue' => 'Blue',
        'Green' => 'Green',
        'Purple' => 'Purple',
        'Rose' => 'Rose',
        'Orange' => 'Orange',
    ],

    'app_layout' => [
        'Sidebar' => 'Sidebar',
        'Header' => 'Header',
    ],

    'auth_layout' => [
        'Split' => 'Split screen',
        'Card' => 'Card',
        'Simple' => 'Simple',
    ],

    'user_status' => [
        'Active' => 'Active',
        'Disabled' => 'Disabled',
    ],

    'role' => [
        'SuperAdmin' => 'Super Admin',
    ],

    'permission' => [
        'UseChat' => 'Use chat',
        'ViewDocumentation' => 'View documentation',
        'ManageDocumentation' => 'Manage documentation',
        'ViewSystem' => 'View system health',
        'ManageBackups' => 'Manage backups',
        'ViewAuditLogs' => 'View audit log',
        'ViewSettings' => 'View settings',
        'ManageSettings' => 'Manage settings',
        'ViewUsers' => 'View users',
        'ManageUsers' => 'Manage users',
        'AssignUserPermissions' => 'Assign user permissions',
        'ViewApiClients' => 'View API clients',
        'ManageApiClients' => 'Manage API clients',
    ],

    'api_client_status' => [
        'Active' => 'Active',
        'Disabled' => 'Disabled',
    ],

    'api_token_ability' => [
        'Read' => 'Read',
        'Write' => 'Write',
    ],

    'api_token_expiration' => [
        'ThirtyDays' => '30 days',
        'NinetyDays' => '90 days',
        'OneYear' => '1 year',
    ],

    'locale' => [
        'English' => 'English',
        'Indonesian' => 'Indonesian',
    ],

    'audit_event' => [
        'UserCreated' => 'User created',
        'UserUpdated' => 'User updated',
        'UserStatusUpdated' => 'Account status changed',
        'UserPermissionsUpdated' => 'Permissions changed',
        'UserInvitationQueued' => 'Invitation queued',
        'SettingsUpdated' => 'Settings updated',
        'ApiClientCreated' => 'API client created',
        'ApiClientUpdated' => 'API client updated',
        'ApiClientTokenIssued' => 'API client token issued',
        'ApiClientTokenRevoked' => 'API client token revoked',
    ],
];
