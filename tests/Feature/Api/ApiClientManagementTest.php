<?php

declare(strict_types=1);

use App\Enums\ApiClientStatus;
use App\Enums\Permission;
use App\Models\ApiClient;
use App\Models\User;
use App\Settings\ApiSettings;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

/** @return array{name: string, description: string, status: string} */
function apiClientPayload(array $overrides = []): array
{
    return [...['name' => 'Logistics partner', 'description' => 'Shipment sync', 'status' => 'active'], ...$overrides];
}

describe('API client access', function (): void {
    it('redirects guests to login', function (): void {
        $client = ApiClient::factory()->create();

        get(route('master-data.api-clients.index'))->assertRedirect(route('login'));
        get(route('master-data.api-clients.show', $client))->assertRedirect(route('login'));
    });

    it('returns 403 without the view permission', function (): void {
        $client = ApiClient::factory()->create();
        actingAs(User::factory()->create());

        get(route('master-data.api-clients.index'))->assertForbidden();
        get(route('master-data.api-clients.show', $client))->assertForbidden();
        getJson(route('master-data.api-clients.export'))->assertForbidden();
    });

    it('lets viewers read but not change clients or tokens', function (): void {
        $client = ApiClient::factory()->create();
        actingAsUserWithPermissions(Permission::ViewApiClients);

        get(route('master-data.api-clients.index'))->assertOk();
        get(route('master-data.api-clients.show', $client))->assertOk();
        post(route('master-data.api-clients.store'), apiClientPayload())->assertForbidden();
        patch(route('master-data.api-clients.update', $client), apiClientPayload())->assertForbidden();
        post(route('master-data.api-clients.tokens.store', $client), ['name' => 'Sync', 'abilities' => ['read'], 'expiration' => '30'])->assertForbidden();

        expect($client->tokens()->count())->toBe(0);
    });

    it('opens the master data index for API client viewers', function (): void {
        actingAsUserWithPermissions(Permission::ViewApiClients);

        get(route('master-data.index'))->assertOk();
    });

    it('returns 404 when the API module is switched off', function (): void {
        resolve(ApiSettings::class)->fill(['enabled' => false])->save();
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        get(route('master-data.api-clients.index'))->assertNotFound();
    });
});

describe('Managing API clients', function (): void {
    it('lists clients with token counts and filters by status', function (): void {
        $active = ApiClient::factory()->create(['name' => 'Active partner']);
        $active->createToken('Sync');
        ApiClient::factory()->disabled()->create();
        actingAsUserWithPermissions(Permission::ViewApiClients);

        get(route('master-data.api-clients.index', ['filter' => ['status' => 'active']]))->assertInertia(fn (Assert $page) => $page
            ->component('master-data/api-clients/Index')
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Active partner')
            ->where('clients.data.0.tokens_count', 1)
            ->has('statuses', 2));
        getJson(route('master-data.api-clients.export', ['filter' => ['search' => 'active partner']]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
    });

    it('creates a client, records the creator and audits it', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        $response = post(route('master-data.api-clients.store'), apiClientPayload());

        $client = ApiClient::query()->sole();
        $response->assertRedirect(route('master-data.api-clients.show', $client));
        expect($client->name)->toBe('Logistics partner')
            ->and($client->status)->toBe(ApiClientStatus::Active)
            ->and($client->created_by)->toBe($actor->id);
        assertDatabaseHas('audit_logs', ['event' => 'api_client.created', 'subject_id' => $client->id, 'actor_id' => $actor->id]);
    });

    it('rejects an empty payload', function (): void {
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        post(route('master-data.api-clients.store'), [])->assertSessionHasErrors([
            'name' => __('validation.required', ['attribute' => __('validation.attributes.client_name')]),
            'status' => __('validation.required', ['attribute' => 'status']),
        ]);
        expect(ApiClient::query()->count())->toBe(0);
    });

    it('rejects an unknown status', function (): void {
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        post(route('master-data.api-clients.store'), apiClientPayload(['status' => 'archived']))->assertSessionHasErrors('status');
    });

    it('updates a client and audits the change', function (): void {
        $client = ApiClient::factory()->create(['name' => 'Old name']);
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        patch(route('master-data.api-clients.update', $client), apiClientPayload(['name' => 'New name', 'status' => 'disabled']))
            ->assertSessionHasNoErrors();

        expect($client->fresh()->name)->toBe('New name')
            ->and($client->fresh()->status)->toBe(ApiClientStatus::Disabled);
        assertDatabaseHas('audit_logs', ['event' => 'api_client.updated', 'subject_id' => $client->id]);
    });
});

describe('API client tokens', function (): void {
    it('shows client tokens without exposing hashes', function (): void {
        $client = ApiClient::factory()->create();
        $client->createToken('Sync', ['read']);
        actingAsUserWithPermissions(Permission::ViewApiClients);

        get(route('master-data.api-clients.show', $client))->assertInertia(fn (Assert $page) => $page
            ->component('master-data/api-clients/Show')
            ->where('client.id', $client->id)
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Sync')
            ->missing('tokens.0.token'));
    });

    it('issues a token, flashes it once and audits it without the secret', function (): void {
        $client = ApiClient::factory()->create();
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        post(route('master-data.api-clients.tokens.store', $client), ['name' => 'Sync', 'abilities' => ['read', 'write'], 'expiration' => '90'])
            ->assertRedirect(route('master-data.api-clients.show', $client))
            ->assertInertiaFlash('apiToken');

        $token = $client->tokens()->sole();
        expect($token->abilities)->toBe(['read', 'write']);
        assertDatabaseHas('audit_logs', ['event' => 'api_client.token_issued', 'subject_id' => $client->id]);
        expect(json_encode(DB::table('audit_logs')->where('event', 'api_client.token_issued')->value('after')))->not->toContain('|');
    });

    it('revokes a client token and audits it', function (): void {
        $client = ApiClient::factory()->create();
        $client->createToken('Sync');
        $token = $client->tokens()->sole();
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        delete(route('master-data.api-clients.tokens.destroy', ['apiClient' => $client, 'token' => $token->id]))
            ->assertRedirect(route('master-data.api-clients.show', $client));

        assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
        assertDatabaseHas('audit_logs', ['event' => 'api_client.token_revoked', 'subject_id' => $client->id]);
    });

    it('returns 404 for a token owned by another client or a user', function (string $owner): void {
        $client = ApiClient::factory()->create();
        $other = $owner === 'client' ? ApiClient::factory()->create() : User::factory()->create();
        $other->createToken('Foreign');
        $token = $other->tokens()->sole();
        actingAsUserWithPermissions(Permission::ViewApiClients, Permission::ManageApiClients);

        delete(route('master-data.api-clients.tokens.destroy', ['apiClient' => $client, 'token' => $token->id]))->assertNotFound();

        assertDatabaseHas('personal_access_tokens', ['id' => $token->id]);
    })->with(['client', 'user']);

    it('deletes tokens together with the client', function (): void {
        $client = ApiClient::factory()->create();
        $client->createToken('Sync');

        $client->delete();

        expect(DB::table('personal_access_tokens')->count())->toBe(0);
    });
});
