<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\GetUserSessions;
use App\Actions\Account\RevokeOtherSessions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RevokeSessionsRequest;
use App\Http\Resources\Account\UserSessionResource;
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
            'sessions' => UserSessionResource::collection($getUserSessions->handle($request->user(), $request->session()->getId()))->resolve($request),
            'canListSessions' => config('session.driver') === 'database',
        ]);
    }

    public function destroy(RevokeSessionsRequest $request, RevokeOtherSessions $revokeOtherSessions): RedirectResponse
    {
        Auth::logoutOtherDevices($request->validated('password'));
        $revokeOtherSessions->handle($request->user(), $request->session()->getId());
        $this->toastSuccess(__('account.sessions.status.revoked'));

        return back();
    }
}
