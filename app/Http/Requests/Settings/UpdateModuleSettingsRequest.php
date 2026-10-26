<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Actions\Settings\ValidateModuleSettings;
use App\Enums\Permission;
use App\Support\FeatureManager;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateModuleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(collect(array_keys(FeatureManager::SETTINGS))->contains(fn (string $module): bool => resolve(FeatureManager::class)->available($module)), 404);

        return $this->user()?->can(Permission::ViewSettings) === true && $this->user()->can(Permission::ManageSettings);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['modules' => ['required', 'array']];
    }

    /** @return list<Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty()) {
                resolve(ValidateModuleSettings::class)->handle($this->array('modules'));
            }
        }];
    }
}
