<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData\User;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** @method array{name: string, email: string, password?: ?string, role?: ?string, send_invitation?: bool} validated() */
final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'send_invitation' => ['sometimes', 'boolean'],
            'password' => ['exclude_if:send_invitation,true', 'required', 'string', 'confirmed', Password::defaults()],
            'role' => ['nullable', 'string', Rule::exists(RoleModel::class, 'name')->where('guard_name', 'web')->whereNot('name', Role::SuperAdmin->value)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }
}
