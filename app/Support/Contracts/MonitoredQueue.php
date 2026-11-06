<?php

declare(strict_types=1);

namespace App\Support\Contracts;

/**
 * A queue whose worker the scheduler probes with a heartbeat job and the system page reports on.
 */
interface MonitoredQueue
{
    /**
     * Stable health check name used as the translation key and heartbeat identity.
     */
    public function name(): string;

    public function connection(): string;

    public function queue(): string;

    /**
     * Whether a worker is expected on this queue, so a missing heartbeat signals a fault rather than a disabled feature.
     */
    public function monitored(): bool;
}
