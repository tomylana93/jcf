<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Actions\Settings\ValidateModuleSettings;
use App\Enums\Module;
use App\Support\Exceptions\ModuleUnavailableException;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateModuleSettingsRequest extends FormRequest
{
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

    /** @return array<string, mixed> */
    public function modules(): array
    {
        return (array) $this->validated('modules');
    }

    /**
     * Report a missing module as 404 before field validation; permissions are enforced on the route.
     */
    protected function prepareForValidation(): void
    {
        throw_unless(collect(Module::cases())->contains(fn (Module $module): bool => $module->available()), ModuleUnavailableException::none());
    }
}
