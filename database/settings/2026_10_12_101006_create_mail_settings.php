<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.mailer', 'environment');
        $this->migrator->add('mail.smtp_host', '');
        $this->migrator->add('mail.smtp_port', 587);
        $this->migrator->add('mail.smtp_scheme', 'smtp');
        $this->migrator->add('mail.smtp_username', '');
        $this->migrator->addEncrypted('mail.smtp_password', null);
        $this->migrator->add('mail.from_address', config('mail.from.address'));
        $this->migrator->add('mail.from_name', config('mail.from.name'));
        $this->migrator->add('mail.show_logo', true);
        $this->migrator->add('mail.footer_text', '');
    }
};
