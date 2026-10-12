<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AvatarUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'avatar' => ['bail', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                Rule::dimensions()->maxWidth(4096)->maxHeight(4096)],
        ];
    }
}
