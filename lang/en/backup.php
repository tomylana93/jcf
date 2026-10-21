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
        'upload' => 'Upload a trusted backup ZIP encrypted with the configured backup password. SQL in the archive will execute during restore.',
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
        'queued' => 'Backup queued. A notification will report the result.',
        'uploaded' => 'Backup uploaded and validated.',
        'restore_queued' => 'Restore queued. The application will enter maintenance mode.',
    ],

    'badge' => [
        'skipped' => 'Skipped',
        'idle' => 'Idle',
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
        'delete' => 'Delete backup',
        'restore' => 'Restore active application',
    ],

];
