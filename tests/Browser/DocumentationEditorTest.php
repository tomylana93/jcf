<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Documentation;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('Documentation editor', function (): void {
    it('searches application pages in the link combobox and navigates internal links in the same tab', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);

        $browser = visit(route('dashboard'))
            ->click(__('navigation.link.manage_documentation'))->click(__('common.button.create'))
            ->fill('#document-title', 'Application links')->fill('#document-slug', 'application-links')
            ->fill('.tiptap', 'Open dashboard')->keys('.tiptap', 'ControlOrMeta+a')
            ->click('@editor-link-menu')->click('@editor-application-link')
            ->fill('@editor-application-link-search', __('navigation.link.dashboard'))
            ->click('[role="option"]')
            ->click(__('editor.button.apply'))
            ->click('@save-documentation')->assertSee(__('documentation.status.saved'))->assertNoJavaScriptErrors();
        $document = Documentation::query()->sole();
        expect($document->content_html)->toContain('href="'.route('dashboard', absolute: false).'"', 'target="_self"', '>Open dashboard</a>');
        $browser->navigate(route('documentation.show', $document->slug))->click('Open dashboard')->assertUrlIs(route('dashboard'))->assertNoJavaScriptErrors();
    });

    it('creates and edits a table through its toolbar and context menu then preserves HTML table content', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);

        $browser = visit(route('dashboard'))
            ->click(__('navigation.link.manage_documentation'))->click(__('common.button.create'))
            ->fill('#document-title', 'Table guide')->fill('#document-slug', 'table-guide')
            ->click('@editor-table-menu')->click('@editor-table-insertTable')
            ->fill('.tiptap th:first-child p', 'Column A')
            ->click('@editor-table-menu')->click('@editor-table-addRowAfter')
            ->rightClick('.tiptap th:first-child p')->hover('@editor-context-table')->click('@editor-context-addColumnAfter')
            ->click('@save-documentation')->assertSee(__('documentation.status.saved'))->assertNoJavaScriptErrors();
        $document = Documentation::query()->sole();
        expect($document->content_html)->toContain('<table>', '<th>', 'Column A')
            ->and($document->content_json['content'][0]['content'])->toHaveCount(4)
            ->and($document->content_json['content'][0]['content'][0]['content'])->toHaveCount(4);
        $browser->click(__('editor.label.html'))->fill('textarea[aria-label="'.__('editor.label.html').'"]', '<table><tbody><tr><th colspan="2"><p>Merged header</p></th></tr><tr><td><p>First</p></td><td><p>Second</p></td></tr></tbody></table>')
            ->click(__('editor.button.apply'))->assertSee('Merged header')
            ->click('@save-documentation')->assertSee(__('documentation.status.saved'))->assertNoJavaScriptErrors();
        expect($document->fresh()->content_html)->toContain('<th colspan="2">', 'Merged header', 'First', 'Second');
    });

    it('formats a selection through the toolbar and context menu and publishes it for readers', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);

        $browser = visit(route('dashboard'))
            ->click(__('navigation.link.manage_documentation'))->click(__('common.button.create'))
            ->fill('#document-title', 'Published guide')->fill('#document-slug', 'published-guide')
            ->fill('.tiptap', 'Formatted content')->keys('.tiptap', 'ControlOrMeta+a')
            ->click('@editor-action-bold')->rightClick('.tiptap')->click('@editor-context-italic')
            ->click('[aria-label="'.__('editor.label.block').'"]')->assertSee('Heading 2')->assertSee('Heading 3')->assertSee('Heading 4')->assertDontSee('Heading {level}')->click('Heading 2')
            ->click('#document-status')->click(__('documentation.status.published'))
            ->click('@save-documentation')->assertSee(__('documentation.status.saved'))->assertNoJavaScriptErrors();
        $document = Documentation::query()->sole();
        expect($document->status)->toBe('published')->and($document->content_html)->toContain('<h2>', '<strong>', '<em>', 'Formatted content');
        $browser->navigate(route('documentation.show', $document->slug))->assertSee('Formatted content')->assertNoJavaScriptErrors();
    });

    it('creates formatted HTML and previews it before saving', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);

        $browser = visit(route('dashboard'))
            ->click(__('navigation.link.manage_documentation'))->click(__('common.button.create'))
            ->fill('#document-title', 'Editor pilot')->fill('#document-slug', 'editor-pilot')
            ->click(__('editor.label.html'))->fill('textarea[aria-label="'.__('editor.label.html').'"]', '<h2>Getting started</h2><p><strong>Hello</strong> documentation</p>')
            ->click(__('editor.button.apply'))->assertSee('Getting started')
            ->click(__('documentation.button.preview'))->assertSee('Hello documentation')->keys('[role="dialog"]', 'Escape')
            ->click('@save-documentation')->assertSee(__('documentation.status.saved'))->assertNoJavaScriptErrors();
        $document = Documentation::query()->sole();
        expect($document->content_html)->toContain('<h2>Getting started</h2>', '<strong>Hello</strong>')->and($document->content_text)->toContain('Hello documentation');
        $browser->click(__('editor.label.html'))->fill('textarea[aria-label="'.__('editor.label.html').'"]', '<p style="color:red">Updated</p><iframe src="https://example.com"></iframe>')
            ->click(__('editor.button.apply'))->assertSee(__('editor.label.normalized'))->click(__('editor.button.confirm_html'))
            ->click('@save-documentation')->assertSee(__('documentation.status.saved'))->assertNoJavaScriptErrors();
        expect($document->fresh()->content_html)->toBe('<p>Updated</p>');
    });

    it('searches published guides from reader navigation without exposing management or drafts', function (): void {
        $reader = User::factory()->create();
        assignRolePermissions($reader, Permission::ViewDocumentation);
        Documentation::factory()->published()->create(['title' => 'Email setup guide']);
        Documentation::factory()->published()->create(['title' => 'Backup guide']);
        Documentation::factory()->create(['title' => 'Private email guide']);
        actingAs($reader);

        $browser = visit(route('dashboard'))->click(__('navigation.link.documentation'))
            ->assertSee('Email setup guide')->assertSee('Backup guide')
            ->assertDontSee('Private email guide')->assertDontSee(__('navigation.link.manage_documentation'))
            ->fill('input[type="search"]', 'Email')->click('form button[type="submit"]')
            ->assertQueryStringHas('search', 'Email')
            ->assertSee('Email setup guide')->assertDontSee('Backup guide')->assertNoJavaScriptErrors();
        $browser->click(__('documentation.button.read'))->assertSee('Email setup guide')->assertNoJavaScriptErrors();
    });

});
