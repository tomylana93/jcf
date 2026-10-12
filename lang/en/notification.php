<?php

declare(strict_types=1);

return [
    'title' => 'Notifications',

    'card' => [
        'title' => 'Notifications',
        'description' => 'Account updates and application activity.',
    ],

    'label' => [
        'open' => 'Open notifications ({count} unread)',
        'filter' => 'Filter notifications',
        'pagination' => 'Notification pages',
    ],

    'helper' => [
        'empty' => 'No notifications',
        'empty_description' => 'New account updates and activity will appear here.',
    ],

    'button' => [
        'read' => 'Mark as read',
        'read_all' => 'Mark all as read',
        'view_account' => 'View account',
    ],

    'status' => [
        'all' => 'All',
        'unread' => 'Unread',
        'read' => 'Read',
    ],

    'mail' => [
        'greeting' => 'Hello!',
        'error_greeting' => 'Whoops!',
        'salutation' => 'Regards,',
        'action_fallback' => 'If the :actionText button cannot be opened, copy and paste the following URL into a web browser:',
    ],

    'message' => [
        'account_created' => 'Account created. Profile and security settings are ready.',
        'account_updated' => 'Account details or access have been updated.',
        'permissions_updated' => 'Additional account permissions have been updated.',
        'invitation_queued' => 'An invitation for :subject has been queued for delivery.',
        'backup_ready' => 'Backup :subject is ready for download.',
        'restore_ready' => 'Restore of :subject completed.',
        'restore_failed' => 'Restore failed. Check application logs and maintenance mode.',
        'backup_failed' => 'Backup failed. Check system health and application logs.',
        'generic' => 'New application notification.',
    ],
];
