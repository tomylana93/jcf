<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\CompleteTwoFactorChallenge;
use App\Actions\Api\IssueDeviceToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TwoFactorChallengeRequest;
use App\Http\Resources\Api\V1\DeviceTokenResource;
use Illuminate\Http\JsonResponse;

final class TwoFactorChallengeController extends Controller
{
    public function __invoke(TwoFactorChallengeRequest $request, CompleteTwoFactorChallenge $completeChallenge, IssueDeviceToken $issueToken): JsonResponse
    {
        $result = $completeChallenge->handle(
            $request->challenge(),
            $request->code(),
            $request->recoveryCode(),
        );

        return new DeviceTokenResource($issueToken->handle($result['user'], $result['device_name']))->response()->setStatusCode(201);
    }
}
