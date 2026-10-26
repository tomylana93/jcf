<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/** @method array{site_name: string, site_description: ?string, support_email: ?string} validated() */
final class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:100'],
            'site_description' => ['present', 'nullable', 'string', 'max:500'],
            'support_email' => ['present', 'nullable', 'string', 'email', 'max:254'],
        ];
    }
}
