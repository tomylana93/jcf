<?php

declare(strict_types=1);

return [
    'title' => 'Audit log',

    'card' => [
        'title' => 'Audit log',
        'description' => 'Review changes to users, permissions, and application settings.',
        'detail_title' => 'Change details',
    ],

    'label' => [
        'time' => 'Time',
        'actor' => 'Actor',
        'event' => 'Event',
        'subject' => 'Subject',
        'before' => 'Before',
        'after' => 'After',
        'system' => 'System',
    ],

    'button' => [
        'detail' => 'View changes',
    ],

    'event' => [
        'user' => [
            'created' => 'User created',
            'updated' => 'User updated',
            'status_updated' => 'Account status changed',
            'permissions_updated' => 'Permissions changed',
            'invitation_queued' => 'Invitation queued',
        ],
        'settings' => [
            'updated' => 'Settings updated',
        ],
    ],

    'settings' => [
        'general' => 'General settings',
        'auth' => 'Authentication settings',
        'brand' => 'Brand settings',
        'layout' => 'Layout settings',
        'mail' => 'Mail settings',
    ],
];
