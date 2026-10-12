<?php

declare(strict_types=1);

return [
    'title' => 'Sessions and devices',

    'card' => [
        'description' => 'Active browser sessions associated with this account.',
    ],

    'label' => [
        'current' => 'Current device',
        'unknown' => 'Unknown device',
    ],

    'helper' => [
        'unsupported' => 'Session listing requires the database session driver. Other sessions can still be revoked.',
        'empty' => 'No stored active sessions.',
        'revoke' => 'Enter the current password to sign out other devices. The current session remains active.',
    ],

    'button' => [
        'revoke' => 'Sign out other devices',
    ],

    'status' => [
        'revoked' => 'Other sessions revoked.',
    ],
];
