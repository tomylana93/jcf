<?php

declare(strict_types=1);

return [
    'title' => 'System health',

    'card' => [
        'description' => 'Infrastructure checks and production readiness.',
    ],

    'helper' => [
        'mail' => 'Email checks validate configuration only. Use the test email action in mail settings to verify delivery.',
    ],

    'check' => [
        'database' => 'Database',
        'storage' => 'Storage',
        'cache' => 'Cache',
        'queue' => 'Queue worker',
        'scheduler' => 'Scheduler',
        'mail' => 'Email configuration',
        'backup' => 'Backup freshness',
    ],

    'health' => [
        'ok' => 'Healthy',
        'warning' => 'Attention needed',
        'failed' => 'Failed',
    ],

    'detail' => [
        'reachable' => 'Connection available.',
        'writable' => 'Read and write probe passed.',
        'recent' => 'Recent activity detected.',
        'configured' => 'SMTP configuration is present.',
        'unavailable' => 'The check failed. Inspect the service configuration.',
        'synchronous' => 'Queue runs synchronously; no background worker is verified.',
        'stale' => 'Recent activity is missing. Check the worker, scheduler, or backup configuration.',
        'disabled' => 'Module disabled.',
        'not_configured' => 'Production configuration is incomplete.',
    ],
];
