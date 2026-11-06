<?php

declare(strict_types=1);

return [
    'title' => 'Permission',

    'card' => [
        'title' => 'Permission',
        'description' => 'Extra permissions granted directly to a user, on top of the role.',
        'manage_title' => 'Manage permissions',
        'summary_title' => 'Change summary',
    ],

    'label' => [
        'search' => 'Search',
        'direct' => 'Direct permissions',
        'inherited' => 'Permissions inherited from role',
        'added' => 'Added',
        'removed' => 'Removed',
        'module' => 'Module',
        'selected' => 'Selected direct permissions',
    ],

    'placeholder' => [
        'search' => 'Search permission name or code',
        'all_modules' => 'All modules',
    ],

    'helper' => [
        'all_access' => 'Has full access',
        'inherited' => 'Role permissions remain active and cannot be removed here.',
        'unchanged' => 'No changes yet.',
        'no_results' => 'No permissions found.',
    ],

    'button' => [
        'select_visible' => 'Select search results',
        'clear_visible' => 'Clear search results',
        'reset' => 'Discard changes',
        'back' => 'Back to user permissions',
    ],

    'status' => [
        'updated' => 'User permissions updated.',
    ],

    'group' => [
        'chat' => 'Chat',
        'system' => 'System health',
        'backups' => 'Backups',
        'audit' => 'Audit log',
        'settings' => 'Settings',
        'users' => 'Users',
        'documentation' => 'Documentation',
        'api_clients' => 'API clients',
    ],

    'error' => [
        'module_disabled' => 'This permission belongs to a module that is currently disabled.',
    ],
];
