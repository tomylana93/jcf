<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audit\RecordSettingsChange;
use App\Enums\Module;
use App\Enums\Permission;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateModuleSettings
{
    public function __construct(private ValidateModuleSettings $validate, private RecordSettingsChange $audit, private FeatureManager $features) {}

    /**
     * Toggle modules for the actor; it re-authorizes and re-validates because it is also called outside HTTP.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): void
    {
        Gate::forUser($actor)->authorize(Permission::ViewSettings->value);
        Gate::forUser($actor)->authorize(Permission::ManageSettings->value);
        $validated = $this->validate->handle($data);
        DB::transaction(function () use ($actor, $validated): void {
            foreach ($validated as $module => $enabled) {
                DB::table('settings')->where('group', $module)->orderBy('id')->lockForUpdate()->get();
                $settings = resolve(Module::settingsClasses()[$module]);
                $settings->refresh();
                $this->audit->handle($settings, $actor, function () use ($settings, $enabled): void {
                    $settings->enabled = $enabled;
                    $settings->save();
                });
            }
        });
        $this->features->reset();
    }
}
