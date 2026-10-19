<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\UpdateWebsiteSettings;
use App\Enums\Permission;
use App\Enums\WebsitePreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\ApplyWebsitePresetRequest;
use App\Http\Requests\Website\UpdateWebsiteSettingsRequest;
use App\Models\User;
use App\Settings\WebsiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

final class WebsiteSettingsController extends Controller
{
    public function edit(Request $request, WebsiteSettings $settings): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can(Permission::ConfigureWebsite), 403);
        $settings->refresh();

        return Inertia::render('website/Settings', [
            'settings' => Arr::only($settings->toArray(), ['identity', 'seo', 'contact', 'navigation', 'footer']),
            'preset' => $settings->preset,
            'presetOptions' => [...array_map(static fn (WebsitePreset $preset): array => ['value' => $preset->value, 'label' => $preset->label()], WebsitePreset::cases())],
        ]);
    }

    public function update(UpdateWebsiteSettingsRequest $request, UpdateWebsiteSettings $update): RedirectResponse
    {
        $update->handle($request->user(), $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.settings_updated')]);

        return to_route('website.settings.edit');
    }

    public function applyPreset(ApplyWebsitePresetRequest $request, UpdateWebsiteSettings $update): RedirectResponse
    {
        $update->handle($request->user(), ['preset' => $request->validated('preset')]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.preset_applied')]);

        return to_route('website.index');
    }
}
