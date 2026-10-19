<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\Permission;
use App\Models\User;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final readonly class UpdateWebsiteSettings
{
    public function __construct(private WebsiteSettings $settings, private ValidateWebsiteSettings $validateSettings, private InvalidateWebsiteCache $invalidate, private RecordAuditLog $audit) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): WebsiteSettings
    {
        Gate::forUser($actor)->authorize(Permission::ConfigureWebsite->value);
        $validated = $this->validateSettings->handle($data);

        return DB::transaction(function () use ($actor, $validated): WebsiteSettings {
            DB::table('settings')->where('group', 'website')->orderBy('id')->lockForUpdate()->get();
            $this->settings->refresh();
            $before = $this->settings->toArray();
            if (isset($validated['preferences'])) {
                $validated['preferences'] = array_replace($this->settings->preferences, $validated['preferences']);
            }
            $this->settings->fill([...$validated, 'public_version' => (string) Str::uuid()])->save();
            $this->audit->handle($actor, 'website.settings.updated', WebsiteSettings::class, 'website', 'Website', $before, $this->settings->toArray());
            $this->invalidate->handle(settingsVersion: $before['public_version']);

            return $this->settings;
        });
    }
}
