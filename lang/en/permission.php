<?php

declare(strict_types=1);

return [
    'title' => 'Permission',

    'card' => [
        'title' => 'Permission',
        'description' => 'Manage additional permissions assigned directly to users.',
        'manage_title' => 'Manage Permissions',
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
        'updated' => 'User permissions updated successfully.',
    ],
];
