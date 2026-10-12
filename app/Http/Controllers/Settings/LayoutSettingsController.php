<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetLayoutSettings;
use App\Actions\Settings\UpdateLayoutSettings;
use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateLayoutSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LayoutSettingsController extends Controller
{
    public function edit(GetLayoutSettings $getSettings): Response
    {
        return Inertia::render('settings/Layout', [
            'settings' => $getSettings->handle(),
            'appLayoutOptions' => AppLayout::options(),
            'authLayoutOptions' => AuthLayout::options(),
        ]);
    }

    public function update(UpdateLayoutSettingsRequest $request, UpdateLayoutSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.layout.status.updated')]);

        return to_route('settings.layout.edit');
    }
}
