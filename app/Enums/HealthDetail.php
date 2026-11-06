<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Outcome of a system health probe; its value is the translation key shown on the system page.
 */
enum HealthDetail: string
{
    case Reachable = 'reachable';
    case Writable = 'writable';
    case Recent = 'recent';
    case Configured = 'configured';
    case Synchronous = 'synchronous';
    case StaleQueue = 'stale_queue';
    case StaleScheduler = 'stale_scheduler';
    case StaleBackup = 'stale_backup';
    case NotConfigured = 'not_configured';
    case Disabled = 'disabled';
    case Unavailable = 'unavailable';

    public function status(): string
    {
        return match ($this) {
            self::Reachable, self::Writable, self::Recent, self::Configured => 'ok',
            self::Unavailable => 'failed',
            self::Synchronous, self::StaleQueue, self::StaleScheduler, self::StaleBackup, self::NotConfigured, self::Disabled => 'warning',
        };
    }
}
