<?php

declare(strict_types=1);

return [
    'title' => 'Backups',

    'card' => [
        'description' => 'Create, upload, download, and restore database and application files.',
    ],

    'label' => [
        'filename' => 'Filename',
        'size' => 'Size',
        'created_at' => 'Created at',
        'archive' => 'Backup archive',
        'backup' => 'Backup',
        'restore' => 'Restore',
    ],

    'helper' => [
        'delete' => 'Permanently delete backup :filename? This action cannot be undone.',
        'setup' => 'Backups are not configured. Contact the system operator.',
        'upload' => 'Upload a backup ZIP from a trusted source, encrypted with the backup password. Restoring it replaces the current database.',
        'restore' => 'Restore :filename? The active database and uploaded files will be replaced. A safety backup is created first. The application will be unavailable during restore and login is required afterward.',
    ],

    'button' => [
        'backup' => 'Create backup',
        'download' => 'Download',
        'upload' => 'Upload backup',
        'restore' => 'Restore',
    ],

    'status' => [
        'deleted' => 'Backup deleted.',
        'queued' => 'Backup started. A notification will follow when it finishes.',
        'uploaded' => 'Backup uploaded and validated.',
        'restore_queued' => 'Restore started. The application will be unavailable until it finishes.',
    ],

    'badge' => [
        'skipped' => 'Skipped',
        'running' => 'Running',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'error' => [
        'delete_failed' => 'Backup could not be deleted. Try again.',
        'backup_failed' => 'Backup failed. Check the application logs for details.',
        'not_configured' => 'Backups are disabled or the archive password is missing.',
        'invalid_archive' => 'The archive is invalid or cannot be opened with the configured backup password.',
        'busy' => 'Another backup or restore is running.',
        'restore_failed' => 'Restore failed. Contact the system operator to check logs and recover from maintenance mode.',
    ],
    'dialog' => [
        'upload' => 'Upload backup',
        'delete' => 'Delete backup',
        'restore' => 'Restore active application',
    ],

];
