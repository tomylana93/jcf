<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Documentation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

describe('Documentation', function (): void {
    it('requires authentication', function (): void {
        get(route('documentation.index'))->assertRedirect(route('login'));
    });

    it('searches published content and hides drafts from readers', function (): void {
        $document = Documentation::factory()->published()->create(['content_text' => 'Petunjuk konfigurasi email']);
        Documentation::factory()->create(['content_text' => 'Petunjuk konfigurasi email']);
        actingAs(tap(User::factory()->create(), fn (User $user) => assignRolePermissions($user, Permission::ViewDocumentation)))->get(route('documentation.index', ['search' => 'konfigurasi']))
            ->assertInertia(fn (Assert $page): Assert => $page->component('documentation/Index')->has('documents.data', 1)->where('documents.data.0.id', $document->id)->where('manage', false));
        get(route('documentation.show', $document->slug))->assertInertia(fn (Assert $page): Assert => $page->component('documentation/Show')->where('document.content_html', $document->content_html));
    });

    it('refuses every management endpoint and draft detail to readers', function (): void {
        $document = Documentation::factory()->create();
        actingAs(tap(User::factory()->create(), fn (User $user) => assignRolePermissions($user, Permission::ViewDocumentation)));
        get(route('documentation.manage.index'))->assertForbidden();
        get(route('documentation.manage.create'))->assertForbidden();
        get(route('documentation.manage.edit', $document))->assertForbidden();
        get(route('documentation.show', $document->slug))->assertForbidden();
        post(route('documentation.manage.store'), [])->assertForbidden();
        patch(route('documentation.manage.update', $document), [])->assertForbidden();
        delete(route('documentation.manage.destroy', $document))->assertForbidden();
    });

    it('refuses reading when view and manage permissions are absent', function (): void {
        $user = User::factory()->create();
        $user->roles()->detach();
        actingAs($user)->get(route('documentation.index'))->assertForbidden();
        get(route('documentation.show', Documentation::factory()->published()->create()->slug))->assertForbidden();
    });

    it('creates, publishes, edits, unpublishes and deletes with server generated content', function (): void {
        $author = User::factory()->create();
        assignRolePermissions($author, Permission::ManageDocumentation);
        actingAs($author)->get(route('documentation.manage.create'))->assertInertia(fn (Assert $page): Assert => $page->component('documentation/Edit')->where('document', null));
        $data = ['title' => 'Getting started', 'slug' => 'getting-started', 'summary' => 'A guide', 'status' => 'draft', 'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '  <script>example</script>  ']]]]]];
        post(route('documentation.manage.store'), [...$data, 'content_html' => '<script>attack</script>', 'author_id' => 'spoofed'])->assertSessionHasNoErrors();
        $document = Documentation::query()->sole();
        expect($document->content_html)->toBe('<p>  &lt;script&gt;example&lt;/script&gt;  </p>')->and($document->content_text)->toBe('<script>example</script>')->and($document->author_id)->toBe($author->id)->and($document->published_at)->toBeNull()->and($document->schema_version)->toBe(1);
        get(route('documentation.manage.edit', $document))->assertInertia(fn (Assert $page): Assert => $page->component('documentation/Edit')->where('document.content_json', $data['content_json']));
        get(route('documentation.manage.index'))->assertInertia(fn (Assert $page): Assert => $page->where('manage', true)->has('documents.data', 1));
        get(route('documentation.show', $document->slug))->assertOk();
        $editor = User::factory()->create();
        assignRolePermissions($editor, Permission::ManageDocumentation);
        actingAs($editor);
        patch(route('documentation.manage.update', $document), [...$data, 'status' => 'published'])->assertRedirect(route('documentation.manage.edit', $document));
        expect($document->fresh()->author_id)->toBe($author->id)->and($document->fresh()->updated_by)->toBe($editor->id)->and($document->fresh()->published_at)->not->toBeNull();
        $publishedAt = $document->fresh()->published_at;
        patch(route('documentation.manage.update', $document), [...$data, 'status' => 'published', 'title' => 'Updated'])->assertSessionHasNoErrors();
        expect($document->fresh()->published_at->equalTo($publishedAt))->toBeTrue();
        patch(route('documentation.manage.update', $document), $data)->assertSessionHasNoErrors();
        expect($document->fresh()->published_at)->toBeNull();
        delete(route('documentation.manage.destroy', $document))->assertRedirect(route('documentation.manage.index'));
        expect(Documentation::query()->count())->toBe(0);
    });

    it('generates a slug when empty and uses a supplied slug', function (array $slugData, string $expectedSlug): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);

        post(route('documentation.manage.store'), [
            'title' => 'Getting Started!',
            'status' => 'draft',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Guide content']]]]],
            ...$slugData,
        ])->assertSessionHasNoErrors();

        expect(Documentation::query()->sole()->slug)->toBe($expectedSlug);
    })->with([
        'omitted' => [[], 'getting-started'],
        'empty' => [['slug' => ''], 'getting-started'],
        'null' => [['slug' => null], 'getting-started'],
        'whitespace' => [['slug' => '   '], 'getting-started'],
        'custom' => [['slug' => 'custom-guide'], 'custom-guide'],
    ]);

    it('regenerates an empty slug on update and preserves a supplied slug', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);
        $document = Documentation::factory()->create();
        $data = ['title' => 'Updated Guide', 'status' => 'draft', 'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Guide content']]]]]];

        patch(route('documentation.manage.update', $document), [...$data, 'slug' => ''])->assertSessionHasNoErrors();
        expect($document->fresh()->slug)->toBe('updated-guide');

        patch(route('documentation.manage.update', $document), [...$data, 'slug' => 'custom-guide'])->assertSessionHasNoErrors();
        expect($document->fresh()->slug)->toBe('custom-guide');

        patch(route('documentation.manage.update', $document), [...$data, 'slug' => 'custom-guide'])->assertSessionHasNoErrors();
    });

    it('rejects invalid supplied slugs and unusable or duplicate generated slugs', function (mixed $title, array $slugData): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);
        Documentation::factory()->create(['slug' => 'existing-guide']);

        post(route('documentation.manage.store'), [
            'title' => $title,
            'status' => 'draft',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Guide content']]]]],
            ...$slugData,
        ])->assertSessionHasErrors('slug');

        expect(Documentation::query()->count())->toBe(1);
    })->with([
        'invalid custom format' => ['Valid Title', ['slug' => 'Invalid Slug']],
        'reserved supplied slug' => ['Valid Title', ['slug' => 'manage']],
        'reserved generated slug' => ['Manage', []],
        'duplicate generated slug' => ['Existing Guide', []],
        'empty generated slug' => ['!!!', []],
        'invalid title type' => [['invalid'], []],
    ]);

    it('rejects a reserved slug when updating documentation', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        $document = Documentation::factory()->published()->create(['slug' => 'existing-guide']);

        actingAs($admin)->patch(route('documentation.manage.update', $document), [
            'title' => 'Manage',
            'slug' => '',
            'status' => 'published',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
        ])->assertSessionHasErrors('slug');

        expect($document->fresh()->slug)->toBe('existing-guide');
        get(route('documentation.show', 'existing-guide'))->assertOk();
    });

    it('returns field errors for invalid metadata and content', function (): void {
        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ManageDocumentation);
        actingAs($admin);
        post(route('documentation.manage.store'), [])->assertSessionHasErrors(['title', 'slug', 'content_json', 'status']);
        $existing = Documentation::factory()->create();
        post(route('documentation.manage.store'), ['title' => 'Title', 'slug' => $existing->slug, 'status' => 'published', 'summary' => str_repeat('x', 2001), 'content_json' => ['type' => 'script']])->assertSessionHasErrors(['slug', 'summary']);
        post(route('documentation.manage.store'), ['title' => 'Title', 'slug' => 'valid-slug', 'status' => 'draft', 'content_json' => ['type' => 'script']])->assertSessionHasErrors('content_json');
        get(route('documentation.index', ['search' => str_repeat('x', 201), 'page' => 0]))->assertSessionHasErrors(['search', 'page']);
    });

    it('ranks title matches above body matches and updates the search index after editing', function (): void {
        $body = Documentation::factory()->published()->create(['title' => 'First guide', 'content_text' => 'konfigurasi']);
        $title = Documentation::factory()->published()->create(['title' => 'Konfigurasi']);
        actingAs(tap(User::factory()->create(), fn (User $user) => assignRolePermissions($user, Permission::ViewDocumentation)))->get(route('documentation.index', ['search' => 'konfigurasi']))->assertInertia(fn (Assert $page): Assert => $page->has('documents.data', 2)->where('documents.data.0.id', $title->id));
        $body->update(['content_text' => 'different']);
        get(route('documentation.index', ['search' => 'konfigurasi']))->assertInertia(fn (Assert $page): Assert => $page->has('documents.data', 1));
        get(route('documentation.index', ['search' => "' OR 1=1 --"]))->assertInertia(fn (Assert $page): Assert => $page->has('documents.data', 0));
    });

    it('evaluates read and management access independently', function (array $permissions, bool $published, bool $read, bool $manage): void {
        $user = User::factory()->create();
        assignRolePermissions($user, ...$permissions);
        $document = Documentation::factory()->create(['status' => $published ? 'published' : 'draft']);

        expect($user->can('viewAny', Documentation::class))->toBe($permissions !== [])
            ->and($user->can('view', $document))->toBe($read)
            ->and($user->can('create', Documentation::class))->toBe($manage)
            ->and($user->can('update', $document))->toBe($manage)
            ->and($user->can('delete', $document))->toBe($manage);
    })->with([
        'no access' => [[], true, false, false],
        'published reader' => [[Permission::ViewDocumentation], true, true, false],
        'draft reader' => [[Permission::ViewDocumentation], false, false, false],
        'draft manager' => [[Permission::ManageDocumentation], false, true, true],
        'published manager' => [[Permission::ManageDocumentation], true, true, true],
    ]);

    it('paginates matching documents and searches drafts only in management', function (): void {
        $manager = User::factory()->create();
        assignRolePermissions($manager, Permission::ManageDocumentation);
        Documentation::factory()->published()->count(13)->create(['title' => 'Matching guide']);
        $draft = Documentation::factory()->create(['title' => 'Private draft']);

        actingAs($manager)->get(route('documentation.index', ['search' => 'Matching', 'page' => 2]))
            ->assertInertia(fn (Assert $page): Assert => $page->has('documents.data', 1)
                ->where('documents.total', 13)->where('documents.current_page', 2));
        get(route('documentation.index', ['search' => 'Private']))
            ->assertInertia(fn (Assert $page): Assert => $page->has('documents.data', 0));
        get(route('documentation.manage.index', ['search' => 'Private']))
            ->assertInertia(fn (Assert $page): Assert => $page->has('documents.data', 1)->where('documents.data.0.id', $draft->id));
    });

});
