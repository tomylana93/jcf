<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Support\FeatureManager;
use Illuminate\Support\Facades\Validator;

final readonly class ValidateModuleSettings
{
    public function __construct(private FeatureManager $features) {}

    /** @param array<string, mixed> $data
     * @return array<string, bool>
     */
    public function handle(array $data): array
    {
        $available = array_keys(array_filter(FeatureManager::SETTINGS, fn (string $class, string $module): bool => $this->features->available($module), ARRAY_FILTER_USE_BOTH));
        abort_if($available === [], 404);
        $rules = ['modules' => ['required', 'array:'.implode(',', $available)]];
        foreach ($available as $module) {
            $rules['modules.'.$module] = ['required', 'boolean'];
        }

        return array_map(static fn (mixed $enabled): bool => (bool) $enabled, Validator::make(['modules' => $data], $rules)->validate()['modules']);
    }
}
