<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\CreateApiToken;
use App\Actions\Account\GetApiTokens;
use App\Actions\Account\RevokeApiToken;
use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ApiTokenController extends Controller
{
    public function index(Request $request, GetApiTokens $getApiTokens): Response
    {
        return Inertia::render('account/ApiTokens', [
            'tokens' => $getApiTokens->handle($request->user()),
            'abilityOptions' => ApiTokenAbility::options(),
            'expirationOptions' => ApiTokenExpiration::options(),
        ]);
    }

    public function store(StoreApiTokenRequest $request, CreateApiToken $createApiToken): RedirectResponse
    {
        $plainTextToken = $createApiToken->handle(
            $request->user(),
            (string) $request->validated('name'),
            $request->abilities(),
            $request->expiration(),
        );

        Inertia::flash('apiToken', $plainTextToken);
        $this->toastSuccess(__('account.api_tokens.status.created'));

        return to_route('api-tokens.index');
    }

    public function destroy(Request $request, int $token, RevokeApiToken $revokeApiToken): RedirectResponse
    {
        $revokeApiToken->handle($request->user(), $token);
        $this->toastSuccess(__('account.api_tokens.status.revoked'));

        return to_route('api-tokens.index');
    }
}
