<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetGeneralSettings;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class GeneralSettingsController extends Controller
{
    public function edit(GetGeneralSettings $getSettings): Response
    {
        return Inertia::render('settings/General', [
            'settings' => $getSettings->handle(),
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request, UpdateGeneralSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.general.status.updated')]);

        return to_route('settings.general.edit');
    }
}
