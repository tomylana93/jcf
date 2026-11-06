<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Actions\Account\GetTimezoneOptions;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @method array{name: string, email: string, timezone?: string|null, current_password?: string|null} validated() */
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
            'current_password' => [Rule::requiredIf($this->changesEmail()), 'nullable', 'string', 'current_password'],
        ];
    }

    /**
     * The validated profile attributes, without the password that only confirms an email change.
     *
     * @return array{name: string, email: string, timezone?: string|null}
     */
    public function profile(): array
    {
        $profile = $this->validated();
        unset($profile['current_password']);

        return $profile;
    }

    private function changesEmail(): bool
    {
        return is_string($this->input('email')) && $this->input('email') !== $this->user()->email;
    }
}
