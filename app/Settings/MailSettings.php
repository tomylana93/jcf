<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class MailSettings extends Settings
{
    public string $mailer;

    public string $smtp_host;

    public int $smtp_port;

    public string $smtp_scheme;

    public string $smtp_username;

    public ?string $smtp_password = null;

    public string $from_address;

    public string $from_name;

    public bool $show_logo;

    public string $footer_text;

    public static function group(): string
    {
        return 'mail';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return ['smtp_password'];
    }
}
