<?php

declare(strict_types=1);

use App\Support\Jalara;
use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [
    'backup' => [
        'name' => env('BACKUP_NAME') ?: (env('APP_INSTANCE') ?: basename(base_path())),
        'source' => [
            'files' => [
                'include' => [storage_path('app/private'), storage_path('app/public')],
                'exclude' => [storage_path('app/private/temporary-avatars')],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ],
            'databases' => [env('DB_CONNECTION', 'pgsql')],
        ],
        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'connection',
        'database_dump_file_extension' => '',
        'destination' => [
            'compression_method' => ZipArchive::CM_DEFLATE,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => ['backups'],
            'continue_on_failure' => false,
        ],
        'temporary_directory' => storage_path('backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'aes256',
        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],
    'notifications' => [
        'notifications' => [
            BackupWasSuccessfulNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            BackupHasFailedNotification::class => env('BACKUP_NOTIFICATION_EMAIL') ? ['mail'] : [],
            UnhealthyBackupWasFoundNotification::class => env('BACKUP_NOTIFICATION_EMAIL') ? ['mail'] : [],
            CleanupHasFailedNotification::class => env('BACKUP_NOTIFICATION_EMAIL') ? ['mail'] : [],
        ],
        'notifiable' => Notifiable::class,
        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL') ?: 'no-reply@example.com',
            'from' => ['address' => env('MAIL_FROM_ADDRESS', Jalara::Email), 'name' => env('MAIL_FROM_NAME', env('APP_NAME', Jalara::Name))],
        ],
    ],
    'log_channel' => null,
    'monitor_backups' => [[
        'name' => env('BACKUP_NAME') ?: (env('APP_INSTANCE') ?: basename(base_path())),
        'disks' => ['backups'],
        'health_checks' => [MaximumAgeInDays::class => 2, MaximumStorageInMegabytes::class => 5000],
    ]],
    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
