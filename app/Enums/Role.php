<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum Role: string
{
    use HasLabel, HasOptions;

    case SuperAdmin = 'superadmin';

    public function badgeColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'red',
        };
    }

    /** @return array{color: string} */
    private function optionAttributes(): array
    {
        return ['color' => $this->badgeColor()];
    }
}
