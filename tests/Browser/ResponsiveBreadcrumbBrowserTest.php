<?php

declare(strict_types=1);

use App\Enums\AppLayout;
use App\Enums\Permission;
use App\Models\Documentation;
use App\Settings\LayoutSettings;

describe('Responsive breadcrumbs', function (): void {
    it('keeps long titles within the header and exposes parent navigation on mobile', function (AppLayout $layout): void {
        actingAsUserWithPermissions(Permission::ViewDocumentation);
        $settings = resolve(LayoutSettings::class);
        $settings->app_layout = $layout;
        $settings->save();
        $document = Documentation::factory()->published()->create([
            'title' => str_repeat('Long documentation title ', 8),
        ]);

        $page = visit(route('documentation.show', $document->slug))->resize(1440, 900)
            ->assertVisible('nav[aria-label="breadcrumb"] a')
            ->assertScript('document.querySelector("nav[aria-label=breadcrumb] button").getClientRects().length === 0', true)
            ->assertAttribute('[aria-current="page"]:visible', 'title', $document->title)
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
            ->screenshot(filename: 'breadcrumb-'.$layout->value.'-desktop');

        $page->resize(320, 844)
            ->assertVisible('[aria-label="'.__('navigation.label.breadcrumb_ancestors').'"]')
            ->assertScript('[...document.querySelectorAll("[aria-current=page]")].some(item => item.getBoundingClientRect().width >= 48)', true)
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
            ->screenshot(filename: 'breadcrumb-'.$layout->value.'-mobile-title')
            ->assertScript('Math.abs(document.querySelector("[data-sidebar=trigger]").getBoundingClientRect().top - document.querySelector("[data-test=header-preferences]").getBoundingClientRect().top) < 12', true)
            ->click('@header-preferences')
            ->click('@header-theme-dark')
            ->assertScript('document.documentElement.classList.contains("dark")', true)
            ->click('@header-preferences')
            ->click('@header-theme-light')
            ->click('@header-preferences')
            ->click('@header-locale-id')
            ->assertScript('document.documentElement.lang', 'id')
            ->click('@header-preferences')
            ->click('@header-locale-en')
            ->assertScript('document.documentElement.lang', 'en')
            ->click('[aria-label="'.__('navigation.label.breadcrumb_ancestors').'"]')
            ->assertVisible('[role="menu"] a')
            ->click('[role="menu"] a')
            ->assertPathIs('/documentation')
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'breadcrumb-'.$layout->value.'-mobile');
    })->with([AppLayout::Sidebar, AppLayout::Header]);
});

describe('Datatable toolbar sizing', function (): void {
    it('aligns create, export, columns, search, and filter controls across viewports', function (): void {
        actingAsUserWithPermissions(Permission::ViewUsers, Permission::ManageUsers);

        $page = visit(route('master-data.users.index'))->resize(1440, 900);

        foreach ([1440, 390] as $width) {
            $page->resize($width, 900)
                ->assertScript(<<<'JS'
                    (() => {
                        const toolbar = document.querySelector('.datatable-toolbar');
                        const controls = [...toolbar.querySelectorAll('button, a[data-size], [data-slot="input-group"], [data-slot="select-trigger"]')];
                        const expected = matchMedia('(width < 40rem), (pointer: coarse)').matches ? 44 : 36;
                        return controls.length >= 5 && controls.every(control => Math.abs(control.getBoundingClientRect().height - expected) < 1);
                    })()
                    JS, true)
                ->assertNoJavaScriptErrors();
        }

        $page->screenshot(filename: 'datatable-toolbar-mobile');
    });
});
