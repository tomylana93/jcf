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

    /**
     * Accent used in outgoing mail, where CSS color functions are not reliably supported.
     */
    public function mailAccent(): string
    {
        return match ($this) {
            self::Neutral => '#171717',
            self::Blue => '#2563eb',
            self::Green => '#198b46',
            self::Purple => '#7c3aed',
            self::Rose => '#e11d48',
            self::Orange => '#d96306',
        };
    }

    /** @return array{swatch: string} */
    private function optionAttributes(): array
    {
        return ['swatch' => $this->swatch()];
    }
}
