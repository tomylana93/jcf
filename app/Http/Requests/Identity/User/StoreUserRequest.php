<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity\User;

use App\Actions\Identity\User\GetAssignableUserRoles;
use App\Concerns\NormalizesEmail;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @method array{name: string, email: string, password?: ?string, role?: ?string, send_invitation?: bool} validated() */
final class StoreUserRequest extends FormRequest
{
    use NormalizesEmail, PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'send_invitation' => ['sometimes', 'boolean'],
            'password' => ['exclude_if:send_invitation,true', ...$this->passwordRules()],
            'role' => ['nullable', 'string', Rule::in(resolve(GetAssignableUserRoles::class)->handle($this->user()))],
        ];
    }
}
