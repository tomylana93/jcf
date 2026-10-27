<?php

declare(strict_types=1);

use App\Support\Jalara;

return [
    'name' => Jalara::Name,
    'slug' => Jalara::Slug,
    'email' => Jalara::Email,
    'description' => Jalara::Description,

    'audit' => [
        'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
    ],

    'operations' => [
        'heartbeat_seconds' => (int) env('SYSTEM_HEARTBEAT_SECONDS', 300),
        'backup_upload_max_kilobytes' => (int) env('BACKUP_UPLOAD_MAX_KILOBYTES', 102400),
        'backup_lock_directory' => storage_path('framework/backup-locks'),
        'restore_files_directory' => storage_path('app'),
        'restore_directory' => storage_path('restore-drills'),
        'restore_max_bytes' => (int) env('BACKUP_RESTORE_MAX_BYTES', 10737418240),
        'psql_binary' => env('PSQL_BINARY', 'psql'),
    ],

    'superadmin' => [
        'name' => env('SUPERADMIN_NAME', 'Super Admin'),
        'email' => env('SUPERADMIN_EMAIL'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],
];
