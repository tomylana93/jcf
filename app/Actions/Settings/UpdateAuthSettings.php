<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\AuthSettings;

final class UpdateAuthSettings
{
    public function __construct(public AuthSettings $settings) {}

    /** @param array{registration_enabled: bool, email_verification_required: bool, password_min_length: int, login_max_attempts: int, login_lockout_minutes: int} $data */
    public function handle(array $data): void
    {
        $this->settings->registration_enabled = $data['registration_enabled'];
        $this->settings->email_verification_required = $data['email_verification_required'];
        $this->settings->password_min_length = $data['password_min_length'];
        $this->settings->login_max_attempts = $data['login_max_attempts'];
        $this->settings->login_lockout_minutes = $data['login_lockout_minutes'];
        $this->settings->save();
    }
}
