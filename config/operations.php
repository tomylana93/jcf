<?php

declare(strict_types=1);

return [
    'heartbeat_seconds' => (int) env('SYSTEM_HEARTBEAT_SECONDS', 300),
    'backup_enabled' => (bool) env('BACKUP_ENABLED', false),
    'backup_upload_max_kilobytes' => (int) env('BACKUP_UPLOAD_MAX_KILOBYTES', 102400),
    'backup_lock_directory' => storage_path('framework/backup-locks'),
    'restore_files_directory' => storage_path('app'),
    'restore_directory' => storage_path('restore-drills'),
    'restore_max_bytes' => (int) env('BACKUP_RESTORE_MAX_BYTES', 10737418240),
    'psql_binary' => env('PSQL_BINARY', 'psql'),
];
