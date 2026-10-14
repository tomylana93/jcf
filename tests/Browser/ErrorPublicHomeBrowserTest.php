<?php

declare(strict_types=1);

use App\Settings\GeneralSettings;

describe('Public home and error recovery', function (): void {
    it('renders home metadata and dashboard links that send guests through login', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = true;
        $settings->site_name = 'JCF Public';
        $settings->site_description = 'Shipping and tracking';
        $settings->save();

        $page = visit('/')->assertSee('JCF Public')->assertSee('Shipping and tracking')
            ->assertSee(__('navigation.link.dashboard'))
            ->assertMissing('a[href="'.route('login', absolute: false).'"]')
            ->assertAttribute('nav [data-slot="button"]', 'href', route('dashboard', absolute: false))
            ->assertAttribute('main [data-slot="button"]', 'href', route('dashboard', absolute: false))
            ->assertNoJavaScriptErrors()->screenshot(filename: 'session-six-home-desktop');

        expect($page->script('document.querySelectorAll(\'meta[name="description"]\').length'))->toBe(1)
            ->and($page->script('document.querySelector(\'meta[property="og:title"]\').content'))->toBe('JCF Public');

        $page->click('main [data-slot="button"]')->assertPathIs('/login')->assertNoJavaScriptErrors();

        expect($page->script('document.querySelectorAll(\'link[rel="canonical"]\').length'))->toBe(0);
    });

    it('renders a standalone mobile error page and recovers to home', function (): void {
        app()->instance('env', 'local');

        visit('/missing-public-page')->on()->mobile()
            ->assertSee(__('error_page.404.title'))
            ->assertDontSee(__('navigation.link.dashboard'))
            ->assertNoJavaScriptErrors()->screenshot(filename: 'session-six-error-mobile')
            ->click(__('error_page.action.home'))->assertPathIs('/')->assertNoJavaScriptErrors();
    });
});
