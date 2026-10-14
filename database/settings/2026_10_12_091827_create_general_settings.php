<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', config('app.name'));
        $this->migrator->add('general.site_description', config('app.description'));
        $this->migrator->add('general.support_email', config('jalara.superadmin.email'));
        $this->migrator->add('general.public_pages_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('general.site_description');
        $this->migrator->delete('general.support_email');
        $this->migrator->delete('general.public_pages_enabled');
    }
};
