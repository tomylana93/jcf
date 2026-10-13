<?php

declare(strict_types=1);

use App\Settings\GeneralSettings;

describe('public home and error recovery', function (): void {
    it('renders home metadata without duplicates after hydration and navigates to login', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = true;
        $settings->site_name = 'JCF Public';
        $settings->site_description = 'Shipping and tracking';
        $settings->save();

        $page = visit('/')->assertSee('JCF Public')->assertSee('Shipping and tracking')
            ->assertNoJavaScriptErrors()->screenshot(filename: 'session-six-home-desktop');
        expect($page->script('document.querySelectorAll(\'meta[name="description"]\').length'))->toBe(1)
            ->and($page->script('document.querySelector(\'meta[property="og:title"]\').content'))->toBe('JCF Public');
        $page->click('main [data-slot="button"]')->assertPathIs('/login')->assertNoJavaScriptErrors();
        expect($page->script('document.querySelectorAll(\'link[rel="canonical"]\').length'))->toBe(0);
    });

    it('renders a standalone mobile error page and recovers to login', function (): void {
        app()->instance('env', 'local');

        visit('/missing-public-page')->on()->mobile()
            ->assertSee(__('error_page.404.title'))
            ->assertDontSee(__('navigation.link.dashboard'))
            ->assertNoJavaScriptErrors()->screenshot(filename: 'session-six-error-mobile')
            ->click(__('error_page.action.home'))->assertPathIs('/login')->assertNoJavaScriptErrors();
    });
});
