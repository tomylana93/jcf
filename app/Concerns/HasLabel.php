<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;
use UnitEnum;

/** @phpstan-require-implements UnitEnum */
trait HasLabel
{
    public function label(): string
    {
        return __('enums.'.Str::snake(class_basename(static::class)).'.'.$this->name);
    }
}
