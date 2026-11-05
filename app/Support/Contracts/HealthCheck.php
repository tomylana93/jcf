<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use App\Enums\HealthDetail;

interface HealthCheck
{
    /**
     * Stable check name used as the translation key and result identifier.
     */
    public function name(): string;

    /**
     * Probe the dependency; HealthDetail::status() maps the detail to ok, warning or failed.
     */
    public function handle(): HealthDetail;
}
