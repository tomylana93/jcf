<?php

declare(strict_types=1);

use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Actions\MasterData\Backup\CreateBackup;
use App\Actions\MasterData\Backup\GetBackupLock;
use App\Actions\MasterData\Backup\RestoreActiveBackup;
use App\Actions\System\GetSystemHealth;
use App\Actions\Website\BuildPublicHome;
use App\Events\ChatMessageCreated;
use App\Events\ChatReadUpdated;
use App\Events\ChatTypingUpdated;
use App\Jobs\CreateSystemBackup;
use App\Jobs\RestoreSystemBackup;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Settings\BackupSettings;
use App\Settings\ChatSettings;
use App\Settings\WebsiteSettings;
use App\Support\FeatureManager;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

describe('Backend feature gates', function (): void {
    it('rejects disabled modules before binding or resolving domain settings', function (string $module, string $path, bool $hard): void {
        if ($hard) {
            config(['features.'.$module => false]);
            app()->beforeResolving(FeatureManager::SETTINGS[$module], fn () => throw new LogicException('Disabled settings resolved'));
        } else {
            resolve(FeatureManager::SETTINGS[$module])->fill(['enabled' => false])->save();
        }
        actingAsSuperAdmin();

        get($path)->assertNotFound();
    })->with([
        ['website', '/website/pages/home'],
        ['website', '/articles'],
        ['website', '/sitemap.xml'],
        ['chat', '/chat'],
        ['chat', '/chat/00000000-0000-4000-8000-000000000000/messages'],
        ['documentation', '/documentation/missing-document'],
        ['backup', '/master-data/backups'],
    ])->with([true, false]);

    it('redirects root and serves robots without resolving CMS while hard off', function (): void {
        config(['features.website' => false]);
        app()->beforeResolving(WebsiteSettings::class, fn () => throw new LogicException('CMS settings resolved'));
        app()->beforeResolving(BuildPublicHome::class, fn () => throw new LogicException('CMS action resolved'));

        get('/')->assertRedirect(route('login'));
        get('/robots.txt')->assertOk()->assertSee("Disallow: /\n")->assertDontSee('Sitemap:');
        actingAs(User::factory()->create());
        get('/')->assertRedirect(route('dashboard'));
    });

    it('keeps CMS administration available when only public pages are off', function (): void {
        actingAsSuperAdmin();
        resolve(WebsiteSettings::class)->fill(['public_pages_enabled' => false])->save();

        get('/website/settings')->assertOk();
        get('/articles')->assertNotFound();
        get('/')->assertRedirect(route('dashboard'));
        resolve(WebsiteSettings::class)->fill(['enabled' => false])->save();
        get('/website/settings')->assertNotFound();
    });

    it('rejects direct chat mutations before touching domain data', function (bool $hard): void {
        if ($hard) {
            config(['features.chat' => false]);
        } else {
            resolve(ChatSettings::class)->fill(['enabled' => false])->save();
        }
        $user = User::factory()->make();
        $conversation = new ChatConversation;
        Event::fake([ChatMessageCreated::class]);
        DB::shouldReceive('transaction')->never();

        expect(fn () => resolve(SendChatMessage::class)->handle($user, $conversation, []))->toThrow(NotFoundHttpException::class)
            ->and(fn () => resolve(StartDirectConversation::class)->handle($user, $user))->toThrow(NotFoundHttpException::class);
        Event::assertNotDispatched(ChatMessageCreated::class);
    })->with([true, false]);

    it('skips both dispatch and queued delivery of chat events after disabling chat', function (bool $hard): void {
        $events = [
            new ChatMessageCreated(new ChatMessage(['conversation_id' => 'missing']), []),
            new ChatReadUpdated('missing', 'user', 1, []),
            new ChatTypingUpdated('missing', 'user', true, now()->toISOString(), []),
        ];
        if ($hard) {
            config(['features.chat' => false]);
        } else {
            resolve(ChatSettings::class)->fill(['enabled' => false])->save();
        }
        Queue::fake();
        $broadcaster = Mockery::mock(Factory::class);
        $broadcaster->shouldNotReceive('connection');
        foreach ($events as $event) {
            event($event);
            new BroadcastEvent($event)->handle($broadcaster);
        }
        Queue::assertNothingPushed();
    })->with([true, false]);

    it('does not dispatch backup or restore operations after soft off', function (): void {
        actingAsSuperAdmin();
        withSession(['auth.password_confirmed_at' => time()]);
        resolve(BackupSettings::class)->fill(['enabled' => false])->save();
        Queue::fake([CreateSystemBackup::class, RestoreSystemBackup::class]);

        post('/master-data/backups')->assertNotFound();
        post('/master-data/backups/test.zip/restore', ['confirmation' => true])->assertNotFound();
        Queue::assertNothingPushed();
    });

    it('finalizes queued backup and restore as skipped without acquiring operation locks', function (bool $hard): void {
        if ($hard) {
            config(['features.backup' => false]);
            app()->beforeResolving(BackupSettings::class, fn () => throw new LogicException('Backup settings resolved'));
        } else {
            resolve(BackupSettings::class)->fill(['enabled' => false])->save();
        }
        Artisan::shouldReceive('call')->never();
        new CreateSystemBackup('missing')->handle(resolve(CreateBackup::class));
        new RestoreSystemBackup('missing.zip', 'checksum', 'missing')->handle(resolve(RestoreActiveBackup::class));

        expect(Cache::get('system.backup_status'))->toBe('skipped')
            ->and(Cache::store('file')->get('system.restore_status'))->toBe('skipped');
        $lock = resolve(GetBackupLock::class)->handle();
        expect($lock->get())->toBeTrue();
        $lock->release();
    })->with([true, false]);

    it('finishes a backup already running when the soft toggle changes', function (): void {
        config(['backup.backup.password' => 'test-secret']);
        Storage::fake('backups');
        Artisan::shouldReceive('call')->once()->with('backup:run', Mockery::on(function (array $arguments): bool {
            resolve(BackupSettings::class)->fill(['enabled' => false])->save();
            Storage::disk('backups')->put(config('backup.backup.name').'/'.$arguments['--filename'], 'archive');

            return true;
        }))->andReturn(0);

        $filename = resolve(CreateBackup::class)->handle();

        Storage::disk('backups')->assertExists(config('backup.backup.name').'/'.$filename);
        expect(Cache::get('system.backup_status'))->toBe('completed')
            ->and(fn () => resolve(CreateBackup::class)->handle())->toThrow(RuntimeException::class);
    });

    it('reevaluates backup schedules and health in the same process', function (): void {
        config(['backup.backup.password' => 'test-secret']);
        $events = collect(resolve(Schedule::class)->events())->filter(fn ($event): bool => str_contains($event->command ?? '', 'backup'));
        expect($events)->toHaveCount(3);
        foreach ([false, true, false] as $enabled) {
            resolve(BackupSettings::class)->fill(['enabled' => $enabled])->save();
            foreach ($events as $event) {
                expect($event->filtersPass(app()))->toBe($enabled);
            }
        }
        config(['features.backup' => false]);
        app()->beforeResolving(BackupSettings::class, fn () => throw new LogicException('Backup settings resolved'));
        foreach ($events as $event) {
            expect($event->filtersPass(app()))->toBeFalse();
        }
        expect(collect(resolve(GetSystemHealth::class)->handle())->keyBy('name')['backup']['detail'])->toBe('disabled');
    });

    it('generates selectable catalog outputs through the CLI entry point', function (string $mode): void {
        $directory = new TemporaryDirectory(storage_path('framework/testing/catalog-command-'.getmypid()));
        $directory->create();
        try {
            if ($mode === 'default') {
                artisan('wayfinder:catalog', ['--with-form' => true])->assertSuccessful();
                expect(resource_path('js/routes/chat/index.ts'))->toBeFile();
            } else {
                artisan('wayfinder:catalog', ['--with-form' => $mode === 'actions', '--path' => $directory->path(), '--skip-'.$mode => true])->assertSuccessful();
                expect($directory->path('wayfinder/index.ts'))->toBeFile()
                    ->and($directory->path($mode === 'actions' ? 'actions/App/Http/Controllers/Chat/ChatController.ts' : 'routes/chat/index.ts'))->not->toBeFile()
                    ->and($directory->path($mode === 'actions' ? 'routes' : 'actions'))->toBeDirectory();
            }
        } finally {
            $directory->delete();
        }
    })->with(['default', 'actions', 'routes']);

    it('generates the build catalog on a database with only core schema', function (): void {
        withIsolatedRestoreDatabase(function (string $connection): void {
            $directory = new TemporaryDirectory(storage_path('framework/testing/core-catalog-'.getmypid()));
            $directory->create();
            $db = DB::connection($connection);
            $environment = [
                'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => $db->getDatabaseName().'_development', 'DB_TEST_DATABASE' => $db->getDatabaseName(), 'DB_URL' => '',
                'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'),
                'DB_USERNAME' => $db->getConfig('username'), 'DB_PASSWORD' => $db->getConfig('password'),
                'APP_CONFIG_CACHE' => $directory->path('config.php'), 'APP_ROUTES_CACHE' => $directory->path('routes.php'),
                'FEATURE_WEBSITE' => 'false', 'FEATURE_CHAT' => 'false',
                'FEATURE_DOCUMENTATION' => 'false', 'FEATURE_BACKUP' => 'false',
            ];
            try {
                new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment)->mustRun();
                new Process([PHP_BINARY, 'artisan', 'wayfinder:catalog', '--with-form', '--path='.$directory->path('generated')], base_path(), $environment)->mustRun();
                expect($db->table('settings')->whereIn('group', array_keys(FeatureManager::SETTINGS))->count())->toBe(0);
                foreach (['chat_messages', 'website_contents', 'documentations'] as $table) {
                    expect($db->getSchemaBuilder()->hasTable($table))->toBeFalse();
                }
                expect($directory->path('generated/routes/chat/index.ts'))->toBeFile();
            } finally {
                $directory->delete();
            }
        });
    });

    it('keeps cached runtime routes separate from the complete build catalog', function (string $disabled): void {
        $directory = new TemporaryDirectory(storage_path('framework/testing/route-catalog-'.getmypid()));
        $directory->create();
        $environment = [
            'APP_CONFIG_CACHE' => $directory->path('config.php'),
            'APP_ROUTES_CACHE' => $directory->path('routes.php'),
            'FEATURE_WEBSITE' => 'true', 'FEATURE_CHAT' => 'true',
            'FEATURE_DOCUMENTATION' => 'true', 'FEATURE_BACKUP' => 'true',
        ];
        foreach ($disabled === 'all' ? array_keys(FeatureManager::SETTINGS) : [$disabled] as $module) {
            $environment['FEATURE_'.mb_strtoupper($module)] = 'false';
        }
        $run = static fn (array $arguments): Process => new Process([PHP_BINARY, 'artisan', ...$arguments], base_path(), $environment)->mustRun();
        try {
            $run(['config:cache']);
            $run(['route:cache']);
            $routes = json_decode($run(['route:list', '--json'])->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $names = array_column($routes, 'name');
            foreach (['website' => 'website.settings.edit', 'chat' => 'chat.index', 'documentation' => 'documentation.index', 'backup' => 'master-data.backups.index'] as $module => $name) {
                expect(in_array($name, $names, true))->toBe($disabled !== 'all' && $disabled !== $module);
            }
            $hash = hash_file('sha256', $directory->path('routes.php'));
            $run(['wayfinder:catalog', '--with-form', '--path='.$directory->path('generated')]);
            foreach (['website/settings', 'chat', 'documentation', 'master-data/backups'] as $route) {
                expect($directory->path('generated/routes/'.$route.'/index.ts'))->toBeFile();
            }
            expect(hash_file('sha256', $directory->path('routes.php')))->toBe($hash)
                ->and(json_decode($run(['route:list', '--json'])->getOutput(), true, flags: JSON_THROW_ON_ERROR))->toBe($routes);
        } finally {
            $directory->delete();
        }
    })->with(['website', 'chat', 'documentation', 'backup', 'all']);
});
