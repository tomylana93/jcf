<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\AuthSettings;

final class GetAuthSettings
{
    public function __construct(public AuthSettings $settings) {}

    /** @return array{registration_enabled: bool, email_verification_required: bool, password_min_length: int, login_max_attempts: int, login_lockout_minutes: int} */
    public function handle(): array
    {
        return [
            'registration_enabled' => $this->settings->registration_enabled,
            'email_verification_required' => $this->settings->email_verification_required,
            'password_min_length' => $this->settings->password_min_length,
            'login_max_attempts' => $this->settings->login_max_attempts,
            'login_lockout_minutes' => $this->settings->login_lockout_minutes,
        ];
    }
}
