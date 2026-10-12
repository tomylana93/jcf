<?php

declare(strict_types=1);

use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('layout.app_layout', AppLayout::Sidebar);
        $this->migrator->add('layout.auth_layout', AuthLayout::Split);
    }

    public function down(): void
    {
        $this->migrator->delete('layout.app_layout');
        $this->migrator->delete('layout.auth_layout');
    }
};
