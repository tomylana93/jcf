<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\GetAvatarSettings;
use App\Actions\Account\GetTimezoneOptions;
use App\Actions\Account\UpdateProfile;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ProfileDeleteRequest;
use App\Http\Requests\Account\ProfileUpdateRequest;
use App\Settings\AuthSettings;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request, GetAvatarSettings $getAvatarSettings, GetTimezoneOptions $getTimezoneOptions): Response
    {
        return Inertia::render('account/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail && resolve(AuthSettings::class)->email_verification_required,
            'status' => $request->session()->get('status'),
            'canDisableAccount' => ! $request->user()->hasRole(Role::SuperAdmin),
            'avatarSettings' => $getAvatarSettings->handle($request->user()),
            'timezoneOptions' => $getTimezoneOptions->handle(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.profile.status.updated')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
