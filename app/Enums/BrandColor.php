<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum BrandColor: string
{
    use HasLabel, HasOptions;

    case Neutral = 'neutral';
    case Blue = 'blue';
    case Green = 'green';
    case Purple = 'purple';
    case Rose = 'rose';
    case Orange = 'orange';

    /** @return list<array{value: string, label: string, name: string, swatch: string}> */
    public static function presets(): array
    {
        return array_map(fn (self $color): array => [
            'value' => $color->value,
            'label' => $color->label(),
            'name' => $color->name,
            'swatch' => $color->swatch(),
        ], self::cases());
    }

    public function swatch(): string
    {
        return match ($this) {
            self::Neutral => 'hsl(0 0% 9%)',
            self::Blue => 'hsl(221 83% 53%)',
            self::Green => 'hsl(142 71% 34%)',
            self::Purple => 'hsl(262 83% 58%)',
            self::Rose => 'hsl(346 77% 50%)',
            self::Orange => 'hsl(25 95% 43%)',
        };
    }
}
