<?php

declare(strict_types=1);

namespace App\Http\Requests\Permission;

use App\Enums\Permission;
use App\Models\User;
use App\Support\FeatureManager;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('assignPermissions', $target) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array', 'list'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::enum(Permission::class),
                Rule::notIn([Permission::AssignUserPermissions->value]),
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $module = is_string($value) ? Permission::tryFrom($value)?->module() : null;

                    if (! resolve(FeatureManager::class)->moduleEnabled($module)) {
                        $fail(__('permission.error.module_disabled'));
                    }
                },
            ],
        ];
    }
}
