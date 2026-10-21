<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\AuthSettings;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;

use function Pest\Laravel\actingAs;

describe('Public home and error recovery', function (): void {
    it('renders home metadata and a content CTA without guest dashboard links', function (): void {
        $settings = resolve(GeneralSettings::class);
        resolve(WebsiteSettings::class)->fill(['public_pages_enabled' => true])->save();
        $settings->site_name = 'JCF Public';
        $settings->site_description = 'Shipping and tracking';
        $settings->save();

        $page = visit('/')->assertSee('JCF Public')->assertSee('Shipping and tracking')
            ->assertDontSee(__('navigation.link.dashboard'))
            ->assertMissing('a[href="'.route('login', absolute: false).'"]')
            ->assertAttribute('main [data-slot="button"]', 'href', '#public-introduction')
            ->assertNoJavaScriptErrors()->screenshot(filename: 'session-six-home-desktop');

        expect($page->script('document.querySelectorAll(\'meta[name="description"]\').length'))->toBe(1)
            ->and($page->script('document.querySelector(\'meta[property="og:title"]\').content'))->toBe('JCF Public');

        $page->click('main [data-slot="button"]')->assertPathIs('/')->assertNoJavaScriptErrors();
    });

    it('shows dashboard only when the signed-in account can enter it', function (bool $verified, bool $required, bool $allowed): void {
        $settings = resolve(GeneralSettings::class);
        resolve(WebsiteSettings::class)->fill(['public_pages_enabled' => true])->save();
        $settings->save();
        $auth = resolve(AuthSettings::class);
        $auth->email_verification_required = $required;
        $auth->save();
        actingAs(User::factory()->create(['email_verified_at' => $verified ? now() : null]));

        $page = visit('/')->assertNoJavaScriptErrors();
        if ($allowed) {
            $page->assertAttribute('nav [data-slot="button"]', 'href', route('dashboard', absolute: false))
                ->click('nav [data-slot="button"]')->assertPathIs('/dashboard')->assertNoJavaScriptErrors();
        } else {
            $page->assertDontSee(__('navigation.link.dashboard'));
        }
    })->with([[true, true, true], [false, true, false], [false, false, true]]);

    it('renders a standalone mobile error page and recovers to home', function (): void {
        app()->instance('env', 'local');

        visit('/missing-public-page')->on()->mobile()
            ->assertSee(__('error_page.404.title'))
            ->assertDontSee(__('navigation.link.dashboard'))
            ->assertNoJavaScriptErrors()->screenshot(filename: 'session-six-error-mobile')
            ->click(__('error_page.action.home'))->assertPathIs('/')->assertNoJavaScriptErrors();
    });
});
