<?php

declare(strict_types=1);

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withoutExceptionHandling;

function bearer(string $plainTextToken): array
{
    return ['Authorization' => 'Bearer '.$plainTextToken];
}

describe('Partner API', function (): void {
    it('returns the calling client for a client token', function (): void {
        $client = ApiClient::factory()->create(['name' => 'Logistics partner']);

        getJson(route('api.v1.partner.me'), bearer($client->createToken('Sync', ['read'])->plainTextToken))
            ->assertOk()->assertExactJson(['data' => ['id' => $client->id, 'name' => 'Logistics partner']]);
    });

    it('returns 401 without a token', function (): void {
        getJson(route('api.v1.partner.me'))->assertUnauthorized();
    });

    it('returns 403 when a user token calls a partner endpoint', function (): void {
        getJson(route('api.v1.partner.me'), bearer(User::factory()->create()->createToken('Script', ['read'])->plainTextToken))
            ->assertForbidden()->assertJsonPath('message', __('api_client.error.wrong_owner'));
    });

    it('returns 403 when a client token calls a user endpoint', function (): void {
        getJson(route('api.v1.user'), bearer(ApiClient::factory()->create()->createToken('Sync', ['read'])->plainTextToken))
            ->assertForbidden()->assertJsonPath('message', __('api_client.error.wrong_owner'));
    });

    it('returns 403 while the client is disabled and accepts the same token after reactivation', function (): void {
        $client = ApiClient::factory()->disabled()->create();
        $headers = bearer($client->createToken('Sync', ['read'])->plainTextToken);

        getJson(route('api.v1.partner.me'), $headers)->assertForbidden()->assertJsonPath('message', __('api_client.error.disabled'));

        $client->update(['status' => ApiClientStatus::Active]);
        resolve(Factory::class)->forgetGuards();

        getJson(route('api.v1.partner.me'), $headers)->assertOk();
    });

    it('fails loudly for an unknown owner type in a route definition', function (): void {
        Route::middleware(['api', 'auth:sanctum', 'api.owner:partner'])->get('/owner-typo', fn (): string => 'ok');

        $headers = bearer(ApiClient::factory()->create()->createToken('Sync')->plainTextToken);

        expect(fn () => withoutExceptionHandling()->getJson('/owner-typo', $headers))
            ->toThrow(InvalidArgumentException::class, 'Unknown API token owner: partner');
    });

    it('returns 403 when the token lacks the read ability', function (): void {
        getJson(route('api.v1.partner.me'), bearer(ApiClient::factory()->create()->createToken('Sync', ['write'])->plainTextToken))
            ->assertForbidden();
    });
});
