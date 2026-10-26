<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetModuleSettings;
use App\Actions\Settings\UpdateModuleSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateModuleSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ModuleSettingsController extends Controller
{
    public function edit(GetModuleSettings $settings): Response
    {
        return Inertia::render('settings/Modules', ['modules' => $settings->handle()]);
    }

    public function update(UpdateModuleSettingsRequest $request, UpdateModuleSettings $update): RedirectResponse
    {
        $update->handle($request->user(), $request->array('modules'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.modules.status.updated')]);

        return to_route('settings.modules.edit');
    }
}
