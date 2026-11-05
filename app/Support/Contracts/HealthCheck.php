<?php

declare(strict_types=1);

namespace App\Support\Contracts;

interface HealthCheck
{
    /**
     * Stable check name used as the translation key and result identifier.
     */
    public function name(): string;

    /**
     * Probe the dependency and return its detail; ok details are reachable, writable, recent and configured, and unavailable marks a failure.
     */
    public function handle(): string;
}
