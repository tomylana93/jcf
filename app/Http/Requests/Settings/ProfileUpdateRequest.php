<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Actions\Account\GetTimezoneOptions;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'timezone' => ['nullable', 'string', Rule::in(resolve(GetTimezoneOptions::class)->handle())],
        ];
    }
}
