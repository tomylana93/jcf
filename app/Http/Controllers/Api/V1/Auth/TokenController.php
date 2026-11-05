<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\AuthenticateApiUser;
use App\Actions\Api\IssueDeviceToken;
use App\Actions\Api\RevokeCurrentToken;
use App\Actions\Api\StartTwoFactorChallenge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class TokenController extends Controller
{
    /**
     * Exchange credentials for a device token, or start a two-factor challenge when 2FA is enabled.
     */
    public function store(IssueTokenRequest $request, AuthenticateApiUser $authenticate, StartTwoFactorChallenge $startChallenge, IssueDeviceToken $issueToken): JsonResponse
    {
        $user = $authenticate->handle($request->string('email')->toString(), $request->string('password')->toString());
        $deviceName = $request->string('device_name')->toString();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json($startChallenge->handle($user, $deviceName));
        }

        return response()->json(['data' => $issueToken->handle($user, $deviceName)], 201);
    }

    /**
     * Revoke the token used for the current request, logging the device out.
     */
    public function destroy(Request $request, RevokeCurrentToken $revokeCurrentToken): Response
    {
        $revokeCurrentToken->handle($request->user());

        return response()->noContent();
    }
}
