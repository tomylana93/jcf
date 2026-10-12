<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\BrandColor;
use App\Enums\LogoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/** @method array{color: string, logo?: UploadedFile|null, favicon?: UploadedFile|null, remove_logo?: bool, remove_favicon?: bool} validated() */
final class UpdateBrandSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'color' => ['required', Rule::enum(BrandColor::class)],
            'logo_type' => ['sometimes', 'required', Rule::enum(LogoType::class)],
            'logo_text' => ['nullable', File::image()->max('2mb')],
            'remove_logo_text' => ['sometimes', 'boolean'],
            'logo' => ['nullable', File::image()->max('2mb')],
            'favicon' => ['nullable', File::image()->max('1mb')],
            'auth_background' => ['nullable', File::image()->max('5mb')],
            'remove_auth_background' => ['sometimes', 'boolean'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
        ];
    }
}
