<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\BrandColor;
use App\Enums\LogoType;
use Spatie\LaravelSettings\Settings;

final class BrandSettings extends Settings
{
    public ?string $logo_path = null;

    public ?string $logo_text_path = null;

    public LogoType $logo_type;

    public ?string $favicon_path = null;

    public ?string $auth_background_path = null;

    public BrandColor $color;

    public static function group(): string
    {
        return 'brand';
    }
}
