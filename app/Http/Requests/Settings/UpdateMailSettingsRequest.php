<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Actions\Settings\IsSmtpHostAllowed;
use App\Enums\Mailer;
use App\Settings\MailSettings;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @method array{mailer: string, smtp_host: ?string, smtp_port: ?int, smtp_scheme: ?string, smtp_username: ?string, smtp_password: ?string, from_address: string, from_name: string, show_logo: bool, footer_text: ?string} validated() */
final class UpdateMailSettingsRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'mailer' => ['required', Rule::enum(Mailer::class)],
            'smtp_host' => ['present', Rule::requiredIf($this->input('mailer') === Mailer::Smtp->value), 'nullable', 'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && $value !== '' && ! resolve(IsSmtpHostAllowed::class)->handle($value)) {
                        $fail(__('settings.mail.error.smtp_host_blocked'));
                    }
                },
            ],
            'smtp_port' => ['present', Rule::requiredIf($this->input('mailer') === Mailer::Smtp->value), 'nullable', 'integer', 'between:1,65535'],
            'smtp_scheme' => ['present', Rule::requiredIf($this->input('mailer') === Mailer::Smtp->value), 'nullable', Rule::in(['none', 'smtp', 'smtps'])],
            'smtp_username' => ['present', 'nullable', 'string', 'max:255'],
            'smtp_password' => ['present', Rule::requiredIf(
                $this->input('mailer') === Mailer::Smtp->value && filled($this->input('smtp_username')) && blank(resolve(MailSettings::class)->smtp_password),
            ), 'nullable', 'string', 'max:1024'],
            'from_address' => ['required', 'string', 'email', 'max:254'],
            'from_name' => ['required', 'string', 'max:100'],
            'show_logo' => ['required', 'boolean'],
            'footer_text' => ['present', 'nullable', 'string', 'max:500'],
        ];
    }
}
