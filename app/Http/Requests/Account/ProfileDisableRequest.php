<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ProfileDisableRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return ! $this->user()->hasRole(Role::SuperAdmin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }
}
