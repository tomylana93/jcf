<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class TwoFactorChallengeRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'challenge' => ['required', 'string', 'size:64'],
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'max:10'],
            'recovery_code' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function challenge(): string
    {
        return (string) $this->validated('challenge');
    }

    public function code(): ?string
    {
        return $this->filled('code') ? (string) $this->validated('code') : null;
    }

    public function recoveryCode(): ?string
    {
        return $this->filled('recovery_code') ? (string) $this->validated('recovery_code') : null;
    }
}
