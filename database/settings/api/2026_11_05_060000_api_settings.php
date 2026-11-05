<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('api.enabled');
    }
};
