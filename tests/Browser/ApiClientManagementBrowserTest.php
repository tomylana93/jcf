<?php

declare(strict_types=1);

use App\Enums\ApiClientStatus;
use App\Enums\Permission;
use App\Models\ApiClient;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('API client management browser flows', function (): void {
    it('edits and disables clients from the row actions', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewApiClients, Permission::ManageApiClients);
        $client = ApiClient::factory()->create(['name' => 'Row action partner']);
        actingAs($actor);
        $row = 'tr:has-text("Row action partner")';

        $page = visit(route('master-data.api-clients.index', absolute: false));

        $page->click($row.' [aria-label="'.__('table.label.actions').'"]')
            ->click(__('table.button.edit'))
            ->assertSee(__('api_client.card.edit_title'))
            ->assertScript('() => { const trigger = document.querySelector("#api-client-status"); return trigger.getBoundingClientRect().width === document.querySelector("#api-client-name").getBoundingClientRect().width; }')
            ->fill('#api-client-name', 'Renamed partner')
            ->click('[role="dialog"] button[type="submit"]')
            ->assertSee(__('api_client.status.updated'))
            ->click('tr:has-text("Renamed partner") [aria-label="'.__('table.label.actions').'"]')
            ->click(__('common.button.disable'))
            ->click('@confirm-row-action')
            ->assertSeeIn('tr:has-text("Renamed partner")', __('enums.api_client_status.Disabled'))
            ->assertNoJavaScriptErrors();

        expect($client->fresh())->name->toBe('Renamed partner')->status->toBe(ApiClientStatus::Disabled);
    });

    it('hides row actions from viewers', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewApiClients);
        ApiClient::factory()->create(['name' => 'Read only partner']);
        actingAs($actor);

        $page = visit(route('master-data.api-clients.index', absolute: false));

        $page->assertSee('Read only partner')
            ->assertMissing('[aria-label="'.__('table.label.actions').'"]')
            ->assertNoJavaScriptErrors();
    });
});
