<?php

declare(strict_types=1);

return [
    'title' => 'System health',

    'card' => [
        'description' => 'Infrastructure checks and production readiness.',
    ],

    'helper' => [
        'mail' => 'Configuration only. Send a test email from Mail settings to verify delivery.',
    ],

    'check' => [
        'database' => 'Database',
        'storage' => 'Storage',
        'cache' => 'Cache',
        'queue' => 'Queue worker',
        'queue_notifications' => 'Notification worker',
        'queue_chat' => 'Chat worker',
        'queue_backups' => 'Backup worker',
        'scheduler' => 'Scheduler',
        'mail' => 'Email configuration',
        'security' => 'Production security',
        'backup' => 'Backup freshness',
    ],

    'health' => [
        'ok' => 'Healthy',
        'warning' => 'Attention needed',
        'failed' => 'Failed',
    ],

    'detail' => [
        'reachable' => 'Connection available.',
        'writable' => 'Files can be read and written.',
        'recent' => 'Recent activity detected.',
        'configured' => 'Mail delivery is configured.',
        'secure' => 'Production safeguards are active.',
        'unavailable' => 'The check failed. Inspect the service configuration.',
        'synchronous' => 'Jobs run during the request. No queue worker is in use.',
        'stale_queue' => 'No recent activity from the queue worker. Check that the worker is running.',
        'stale_scheduler' => 'No recent scheduler run. Check that the scheduler is running.',
        'stale_backup' => 'No backup in the last 2 days. Check the backup schedule.',
        'disabled' => 'Module disabled.',
        'not_configured' => 'Required configuration is incomplete.',
        'insecure' => 'Debug mode is on, the session cookie is not limited to HTTPS, or the passkey secret reuses the application key.',
    ],
];
