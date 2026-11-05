<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\CompleteTwoFactorChallenge;
use App\Actions\Api\IssueDeviceToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TwoFactorChallengeRequest;
use Illuminate\Http\JsonResponse;

final class TwoFactorChallengeController extends Controller
{
    public function __invoke(TwoFactorChallengeRequest $request, CompleteTwoFactorChallenge $completeChallenge, IssueDeviceToken $issueToken): JsonResponse
    {
        $result = $completeChallenge->handle(
            $request->string('challenge')->toString(),
            $request->filled('code') ? $request->string('code')->toString() : null,
            $request->filled('recovery_code') ? $request->string('recovery_code')->toString() : null,
        );

        return response()->json(['data' => $issueToken->handle($result['user'], $result['device_name'])], 201);
    }
}
