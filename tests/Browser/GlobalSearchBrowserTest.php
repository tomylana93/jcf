<?php

declare(strict_types=1);

use App\Enums\AppLayout;
use App\Enums\Permission;
use App\Models\Documentation;
use App\Models\User;
use App\Settings\LayoutSettings;

use function Pest\Laravel\actingAs;

describe('Global search browser', function (): void {
    it('searches document content and navigates from both layouts', function (AppLayout $layout): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewUsers, Permission::ManageDocumentation);
        $settings = resolve(LayoutSettings::class);
        $settings->app_layout = $layout;
        $settings->save();
        $document = Documentation::factory()->published()->create(['title' => 'Mail setup', 'content_text' => 'configuration guide']);
        actingAs($user);
        visit(route('dashboard'))->click('[data-test="global-search-trigger"]')->type('[data-test="global-search-input"]', 'configuration')->assertSee('Mail setup')->click('Mail setup')->assertUrlIs(route('documentation.show', $document->slug))->assertNoJavaScriptErrors();
    })->with([AppLayout::Header, AppLayout::Sidebar]);
    it('opens by shortcut, selects by keyboard and works on mobile', function (): void {
        actingAs(tap(User::factory()->create(), fn (User $user) => assignRolePermissions($user, Permission::ViewDocumentation)));
        $page = visit(route('dashboard'));
        $page->assertVisible('[data-test="global-search-trigger"]');
        $page->click('[data-test="global-search-trigger"]')->keys('[data-test="global-search-input"]', 'Escape')->keys('[data-test="global-search-trigger"]', 'Control+k')
            ->assertVisible('[data-test="global-search-input"]');
        $page->type('[data-test="global-search-input"]', 'Documentation')
            ->assertScript('() => document.querySelectorAll("[data-slot=command-item]").length === 1 && document.querySelector("[data-slot=command-item]").textContent.trim() === "Documentation"')
            ->keys('[data-test="global-search-input"]', 'ArrowDown')
            ->keys('[data-test="global-search-input"]', 'Enter')
            ->assertUrlIs(route('documentation.index'))
            ->resize(390, 844)
            ->click('[data-test="global-search-trigger"]')
            ->assertVisible('[data-test="global-search-input"]')
            ->keys('[data-test="global-search-input"]', 'Escape')
            ->assertMissing('[data-test="global-search-input"]')
            ->assertNoJavaScriptErrors();
    });

    it('shows empty results without leaking protected records', function (): void {
        Documentation::factory()->published()->create(['title' => 'Confidentialneedle guide']);
        User::factory()->create(['name' => 'Confidentialneedle Person']);
        actingAs(User::factory()->create());

        visit(route('dashboard'))->click('[data-test="global-search-trigger"]')
            ->fill('[data-test="global-search-input"]', 'confidentialneedle')
            ->assertSee(__('search.status.empty'))
            ->assertDontSee('Confidentialneedle guide')->assertDontSee('Confidentialneedle Person')
            ->assertNoJavaScriptErrors();
    });

    it('navigates user results to the filtered user table', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewUsers);
        $target = User::factory()->create(['name' => 'Needle Person', 'email' => 'needle@example.com']);
        actingAs($user);

        visit(route('dashboard'))->click('[data-test="global-search-trigger"]')
            ->fill('[data-test="global-search-input"]', 'needle@')
            ->assertSee($target->name)->click($target->name)
            ->assertUrlIs(route('master-data.users.index'))
            ->assertScript('() => new URLSearchParams(location.search).get("filter[search]") === "needle@example.com"')
            ->assertMissing('[data-test="global-search-input"]')->assertNoJavaScriptErrors();
    });

    it('retries a failed request without leaving stale results', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewDocumentation);
        Documentation::factory()->published()->create(['title' => 'Needle guide']);
        actingAs($user);
        $page = visit(route('dashboard'))->click('[data-test="global-search-trigger"]');
        $page->script(<<<'JS'
            () => {
            window.searchOriginalOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method, url, ...args) {
                if (new URL(url, location.href).pathname === '/search') {
                    url = '/missing-search-endpoint';
                }
                return window.searchOriginalOpen.call(this, method, url, ...args);
            };
            }
        JS);
        $page->fill('[data-test="global-search-input"]', 'needle')
            ->assertSee(__('search.error.failed'))->assertDontSee('Needle guide');
        $page->script('() => { XMLHttpRequest.prototype.open = window.searchOriginalOpen; }');
        $page->click(__('search.button.retry'))->assertSee('Needle guide')
            ->assertDontSee(__('search.error.failed'))->assertNoJavaScriptErrors();
    });

    it('debounces typing and cancels pending searches when the dialog closes', function (): void {
        actingAs(User::factory()->create());
        $page = visit(route('dashboard'))->click('[data-test="global-search-trigger"]');
        $page->script(<<<'JS'
            async () => {
            window.searchRequests = [];
            window.searchAborts = 0;
            const originalOpen = XMLHttpRequest.prototype.open;
            const originalSend = XMLHttpRequest.prototype.send;
            const originalAbort = XMLHttpRequest.prototype.abort;
            XMLHttpRequest.prototype.open = function(method, url, ...args) {
                this.isSearch = new URL(url, location.href).pathname === '/search';
                this.searchUrl = url;
                return originalOpen.call(this, method, url, ...args);
            };
            XMLHttpRequest.prototype.send = function(...args) {
                if (!this.isSearch) return originalSend.apply(this, args);
                window.searchRequests.push(this.searchUrl);
                // Keep transport pending until the test closes the dialog.
            };
            XMLHttpRequest.prototype.abort = function() {
                if (this.isSearch) {
                    window.searchAborts++;
                    this.dispatchEvent(new Event('abort'));
                }
                return originalAbort.call(this);
            };
            const input = document.querySelector('[data-test="global-search-input"]');
            for (const value of ['ne', 'nee', 'needle']) {
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                await new Promise(resolve => setTimeout(resolve, 50));
            }
            }
        JS);
        $page->assertScript('() => window.searchRequests.length === 1')
            ->assertScript('() => new URL(window.searchRequests[0], location.href).searchParams.get("query") === "needle"')
            ->fill('[data-test="global-search-input"]', 'updatedneedle')
            ->assertScript('() => window.searchRequests.length === 2 && window.searchAborts === 1')
            ->keys('[data-test="global-search-input"]', 'Escape')
            ->assertMissing('[data-test="global-search-input"]')
            ->assertScript('() => window.searchAborts === 2')
            ->click('[data-test="global-search-trigger"]')
            ->assertDontSee(__('search.error.failed'))->assertNoJavaScriptErrors();
    });
});
