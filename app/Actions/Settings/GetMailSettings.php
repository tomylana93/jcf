<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\MailSettings;

final class GetMailSettings
{
    public function __construct(public MailSettings $settings) {}

    /** @return array{mailer: string, smtp_host: string, smtp_port: int, smtp_scheme: string, smtp_username: string, from_address: string, from_name: string, show_logo: bool, footer_text: string, has_smtp_password: bool} */
    public function handle(): array
    {
        return [
            'mailer' => $this->settings->mailer,
            'smtp_host' => $this->settings->smtp_host,
            'smtp_port' => $this->settings->smtp_port,
            'smtp_scheme' => $this->settings->smtp_scheme,
            'smtp_username' => $this->settings->smtp_username,
            'from_address' => $this->settings->from_address,
            'from_name' => $this->settings->from_name,
            'show_logo' => $this->settings->show_logo,
            'footer_text' => $this->settings->footer_text,
            'has_smtp_password' => filled($this->settings->smtp_password),
        ];
    }
}
