<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/** @method array{registration_enabled: bool, email_verification_required: bool, password_min_length: int, login_max_attempts: int, login_lockout_minutes: int} validated() */
final class UpdateAuthSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'registration_enabled' => ['required', 'boolean'],
            'email_verification_required' => ['required', 'boolean'],
            'password_min_length' => ['required', 'integer', 'between:8,128'],
            'login_max_attempts' => ['required', 'integer', 'between:1,20'],
            'login_lockout_minutes' => ['required', 'integer', 'between:1,60'],
        ];
    }
}
