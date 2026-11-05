<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\AuthenticateApiUser;
use App\Actions\Api\IssueDeviceToken;
use App\Actions\Api\RevokeCurrentToken;
use App\Actions\Api\StartTwoFactorChallenge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueTokenRequest;
use App\Http\Resources\Api\V1\DeviceTokenResource;
use App\Http\Resources\Api\V1\TwoFactorChallengeResource;
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
        $credentials = $request->validated();
        $user = $authenticate->handle($credentials['email'], $credentials['password']);
        $deviceName = $credentials['device_name'];

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return new TwoFactorChallengeResource($startChallenge->handle($user, $deviceName))->response();
        }

        return new DeviceTokenResource($issueToken->handle($user, $deviceName))->response()->setStatusCode(201);
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
