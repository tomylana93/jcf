<?php

declare(strict_types=1);

use App\Support\FeatureManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Inertia\Ssr\SsrState;

use function Pest\Laravel\get;

describe('Feature flags with production SSR', function (): void {
    it('hydrates core pages without the schemas of disabled modules', function (string $disabled): void {
        actingAsSuperAdmin();
        $tables = [
            'website' => ['website_contents', 'website_slugs', 'contact_messages'],
            'chat' => ['chat_conversations', 'chat_participants', 'chat_messages'],
            'documentation' => ['documentations'],
            'backup' => [],
        ];
        foreach ($disabled === 'all' ? array_keys($tables) : [$disabled] as $module) {
            config(['features.'.$module => false]);
            DB::table('settings')->where('group', $module)->delete();
            foreach ($tables[$module] as $table) {
                DB::statement('DROP TABLE IF EXISTS "'.$table.'" CASCADE');
            }
        }
        resolve(FeatureManager::class)->reset();
        Http::allowStrayRequests(['http://127.0.0.1:13719/*']);
        $server = Process::path(base_path())->start(['node', 'tests/Browser/public-seo-server.mjs']);
        try {
            $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'PUBLIC_SEO_SSR_READY'));
            Inertia::disableSsr(false);
            config(['inertia.ssr.url' => 'http://127.0.0.1:13719', 'inertia.ssr.throw_on_error' => true]);
            foreach (['/dashboard', '/settings'] as $path) {
                app()->forgetInstance(SsrState::class);
                get($path)->assertOk()->assertSeeHtml('data-server-rendered="true"');
                $page = visit($path)->assertNoJavaScriptErrors()->assertNoConsoleLogs();
                expect($page->script("Boolean(document.querySelector('#app').__vue_app__)"))->toBeTrue();
                foreach (['website' => '/website', 'chat' => '/chat', 'documentation' => '/documentation', 'backup' => '/master-data/backups'] as $module => $link) {
                    if ($disabled === 'all' || $disabled === $module) {
                        $page->assertMissing('a[href="'.$link.'"]');
                    }
                }
            }
            if ($disabled !== 'all' && $disabled !== 'website') {
                app()->forgetInstance(SsrState::class);
                get('/')->assertOk()->assertSeeHtml('data-server-rendered="true"');
                visit('/')->assertNoJavaScriptErrors()->assertNoConsoleLogs();
            }
        } finally {
            $server->stop();
        }
    })->with(['website', 'chat', 'documentation', 'backup', 'all']);
});
