<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Actions\Account\GetApiTokens;
use App\Actions\Identity\ApiClient\IssueApiClientToken;
use App\Actions\Identity\ApiClient\RevokeApiClientToken;
use App\Actions\Identity\ApiClient\SaveApiClient;
use App\Enums\ApiClientStatus;
use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreApiTokenRequest;
use App\Http\Requests\Identity\ApiClient\SaveApiClientRequest;
use App\Http\Resources\Identity\ApiClientResource;
use App\Models\ApiClient;
use App\Tables\Identity\ApiClientTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ApiClientController extends Controller
{
    public function index(Request $request, ApiClientTable $table): Response
    {
        Gate::authorize('viewAny', ApiClient::class);

        return Inertia::render('master-data/api-clients/Index', [
            'clients' => $table->paginate($request)->through(fn (ApiClient $client): array => new ApiClientResource($client)->resolve($request)),
            'statuses' => ApiClientStatus::options(),
        ]);
    }

    public function export(Request $request, ApiClientTable $table): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ApiClient::class);

        return ApiClientResource::collection($table->exportRecords($request));
    }

    public function store(SaveApiClientRequest $request, SaveApiClient $saveApiClient): RedirectResponse
    {
        $client = $saveApiClient->handle($request->apiClientData(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('api_client.status.created')]);

        return to_route('master-data.api-clients.show', $client);
    }

    public function show(Request $request, ApiClient $apiClient, GetApiTokens $getApiTokens): Response
    {
        Gate::authorize('view', $apiClient);

        return Inertia::render('master-data/api-clients/Show', [
            'client' => new ApiClientResource($apiClient)->resolve($request),
            'tokens' => $getApiTokens->handle($apiClient),
            'statuses' => ApiClientStatus::options(),
            'abilityOptions' => ApiTokenAbility::options(),
            'expirationOptions' => ApiTokenExpiration::options(),
        ]);
    }

    public function update(SaveApiClientRequest $request, ApiClient $apiClient, SaveApiClient $saveApiClient): RedirectResponse
    {
        $saveApiClient->handle($request->apiClientData(), $request->user(), $apiClient);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('api_client.status.updated')]);

        return back(fallback: route('master-data.api-clients.show', $apiClient));
    }

    public function storeToken(StoreApiTokenRequest $request, ApiClient $apiClient, IssueApiClientToken $issueApiClientToken): RedirectResponse
    {
        Gate::authorize('manageTokens', $apiClient);
        $plainTextToken = $issueApiClientToken->handle($apiClient, $request->user(), (string) $request->validated('name'), $request->abilities(), $request->expiration());
        Inertia::flash([
            'apiToken' => $plainTextToken,
            'toast' => ['type' => 'success', 'message' => __('account.api_tokens.status.created')],
        ]);

        return to_route('master-data.api-clients.show', $apiClient);
    }

    public function destroyToken(Request $request, ApiClient $apiClient, int $token, RevokeApiClientToken $revokeApiClientToken): RedirectResponse
    {
        Gate::authorize('manageTokens', $apiClient);
        $revokeApiClientToken->handle($apiClient, $request->user(), $token);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.api_tokens.status.revoked')]);

        return to_route('master-data.api-clients.show', $apiClient);
    }
}
