<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\GetUserSessions;
use App\Actions\Account\RevokeOtherSessions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RevokeSessionsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class SessionController extends Controller
{
    public function index(Request $request, GetUserSessions $getUserSessions): Response
    {
        return Inertia::render('account/Sessions', [
            'sessions' => $getUserSessions->handle($request->user(), $request->session()->getId()),
            'canListSessions' => config('session.driver') === 'database',
        ]);
    }

    public function destroy(RevokeSessionsRequest $request, RevokeOtherSessions $revokeOtherSessions): RedirectResponse
    {
        Auth::logoutOtherDevices($request->validated('password'));
        $revokeOtherSessions->handle($request->user(), $request->session()->getId());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('sessions.status.revoked')]);

        return back();
    }
}
