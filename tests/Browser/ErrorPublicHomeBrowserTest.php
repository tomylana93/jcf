<?php

declare(strict_types=1);

describe('Error recovery', function (): void {

    it('renders a standalone mobile error page and recovers to home', function (): void {
        app()->instance('env', 'local');

        visit('/missing-public-page')->on()->mobile()
            ->assertSee(__('error_page.404.title'))
            ->assertDontSee(__('navigation.link.dashboard'))
            ->assertNoJavaScriptErrors()
            ->click(__('error_page.action.home'))->assertPathIs('/login')->assertNoJavaScriptErrors();
    });
});
