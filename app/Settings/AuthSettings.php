<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class AuthSettings extends Settings
{
    public bool $registration_enabled;

    public bool $email_verification_required;

    public int $password_min_length;

    public int $login_max_attempts;

    public int $login_lockout_minutes;

    public static function group(): string
    {
        return 'auth';
    }
}
