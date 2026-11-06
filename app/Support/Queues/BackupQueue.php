<?php

declare(strict_types=1);

namespace App\Support\Queues;

use App\Enums\Module;
use App\Support\Contracts\MonitoredQueue;
use App\Support\FeatureManager;

/**
 * Backup and restore jobs, expected only while the backup module is enabled.
 */
final readonly class BackupQueue implements MonitoredQueue
{
    public function __construct(private FeatureManager $features) {}

    public function name(): string
    {
        return 'queue_backups';
    }

    public function connection(): string
    {
        return 'backups';
    }

    public function queue(): string
    {
        return 'backups';
    }

    public function monitored(): bool
    {
        return $this->features->enabled(Module::Backup);
    }
}
