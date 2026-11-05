<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\GetPasskeys;
use App\Actions\Account\UpdatePassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\PasswordUpdateRequest;
use App\Http\Requests\Account\TwoFactorAuthenticationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request, GetPasskeys $getPasskeys): Response
    {
        $props = [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys() ? $getPasskeys->handle($request->user()) : [],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('account/Security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request, UpdatePassword $updatePassword): RedirectResponse
    {
        $updatePassword->handle($request->user(), (string) $request->validated('password'));

        $this->toastSuccess(__('account.security.status.password_updated'));

        return back();
    }
}
