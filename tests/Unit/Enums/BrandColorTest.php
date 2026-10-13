<?php

declare(strict_types=1);

use App\Enums\BrandColor;

describe('Brand color swatches', function (): void {
    test('maps each color to its swatch', function (BrandColor $color, string $swatch): void {
        expect($color->swatch())->toBe($swatch);
    })->with([
        'neutral' => [BrandColor::Neutral, 'hsl(0 0% 9%)'],
        'blue' => [BrandColor::Blue, 'hsl(221 83% 53%)'],
        'green' => [BrandColor::Green, 'hsl(142 71% 34%)'],
        'purple' => [BrandColor::Purple, 'hsl(262 83% 58%)'],
        'rose' => [BrandColor::Rose, 'hsl(346 77% 50%)'],
        'orange' => [BrandColor::Orange, 'hsl(25 95% 43%)'],
    ]);
});
