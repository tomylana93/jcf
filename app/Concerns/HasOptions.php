<?php

declare(strict_types=1);

namespace App\Concerns;

use BackedEnum;

/** @phpstan-require-implements BackedEnum */
trait HasOptions
{
    abstract public function label(): string;

    /** @return list<array{value: string|int, label: string, name: string, color?: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case): array => array_merge([
            'value' => $case->value,
            'label' => $case->label(),
            'name' => $case->name,
        ], $case->optionAttributes()), self::cases());
    }

    /** @return array{color?: string} */
    protected function optionAttributes(): array
    {
        return [];
    }
}
