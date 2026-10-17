<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData\User;

use App\Actions\MasterData\User\GetAssignableUserRoles;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** @method array{name: string, email: string, role?: ?string, status: string, password?: ?string} validated() */
final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('update', [$target, $this->input('status')]) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['nullable', 'string', Rule::in(resolve(GetAssignableUserRoles::class)->handle($this->user()))],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }
}
