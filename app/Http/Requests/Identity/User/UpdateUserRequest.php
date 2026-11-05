<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity\User;

use App\Actions\Identity\User\GetAssignableUserRoles;
use App\Concerns\NormalizesEmail;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @method array{name: string, email: string, role?: ?string, status: string} validated() */
final class UpdateUserRequest extends FormRequest
{
    use NormalizesEmail, ProfileValidationRules;

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
            ...$this->profileRules($this->targetUserId()),
            'role' => ['nullable', 'string', Rule::in(resolve(GetAssignableUserRoles::class)->handle($this->user()))],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    private function targetUserId(): ?string
    {
        $target = $this->route('user');

        return $target instanceof User ? $target->id : null;
    }
}
