<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/** @method array{app_layout: string, auth_layout: string} validated() */
final class UpdateLayoutSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<Enum|string>> */
    public function rules(): array
    {
        return [
            'app_layout' => ['required', 'string', Rule::enum(AppLayout::class)],
            'auth_layout' => ['required', 'string', Rule::enum(AuthLayout::class)],
        ];
    }
}
