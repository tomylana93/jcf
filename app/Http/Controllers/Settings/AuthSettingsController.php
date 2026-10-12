<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetAuthSettings;
use App\Actions\Settings\UpdateAuthSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAuthSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AuthSettingsController extends Controller
{
    public function edit(GetAuthSettings $getSettings): Response
    {
        return Inertia::render('settings/Auth', [
            'settings' => $getSettings->handle(),
        ]);
    }

    public function update(UpdateAuthSettingsRequest $request, UpdateAuthSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.auth.status.updated')]);

        return to_route('settings.auth.edit');
    }
}
