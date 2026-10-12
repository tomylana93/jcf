<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetBrandSettings;
use App\Actions\Settings\UpdateBrandSettings;
use App\Enums\BrandColor;
use App\Enums\LogoType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class BrandSettingsController extends Controller
{
    public function edit(GetBrandSettings $getSettings): Response
    {
        return Inertia::render('settings/Brand', [
            'settings' => $getSettings->handle(),
            'logoTypeOptions' => LogoType::options(),
            'colorPresets' => BrandColor::presets(),
        ]);
    }

    public function update(UpdateBrandSettingsRequest $request, UpdateBrandSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.brand.status.updated')]);

        return to_route('settings.brand.edit');
    }
}
