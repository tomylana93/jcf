<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('auth.registration_enabled', in_array('registration', config('fortify.features', []), true));
        $this->migrator->add('auth.email_verification_required', true);
        $this->migrator->add('auth.password_min_length', app()->isProduction() ? 12 : 8);
        $this->migrator->add('auth.login_max_attempts', 5);
        $this->migrator->add('auth.login_lockout_minutes', 1);

    }
};
