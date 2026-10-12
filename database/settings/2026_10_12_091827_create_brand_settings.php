<?php

declare(strict_types=1);

use App\Enums\BrandColor;
use App\Enums\LogoType;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('brand.logo_path', null);
        $this->migrator->add('brand.logo_text_path', null);
        $this->migrator->add('brand.logo_type', LogoType::LogoText);
        $this->migrator->add('brand.favicon_path', null);
        $this->migrator->add('brand.auth_background_path', null);
        $this->migrator->add('brand.color', BrandColor::Neutral);
    }

    public function down(): void
    {
        $this->migrator->delete('brand.logo_path');
        $this->migrator->delete('brand.logo_text_path');
        $this->migrator->delete('brand.logo_type');
        $this->migrator->delete('brand.favicon_path');
        $this->migrator->delete('brand.auth_background_path');
        $this->migrator->delete('brand.color');
    }
};
