<?php

declare(strict_types=1);

use App\Actions\Website\UpdateWebsiteSettings;
use App\Enums\Permission;
use App\Providers\FeatureServiceProvider;
use App\Settings\ChatSettings;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use App\Support\FeatureConfiguration;
use App\Support\FeatureManager;
use App\Support\FeaturePackageManifest;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Backup\BackupServiceProvider;
use Spatie\LaravelSettings\SettingsContainer;
use Spatie\LaravelSettings\SettingsEventSubscriber;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('Feature flags', function (): void {
    it('loads only available domain config before evaluating its contents', function (bool $hard): void {
        $directory = new TemporaryDirectory(storage_path('framework/testing/feature-config-'.getmypid()));
        $directory->create();
        try {
            $path = $directory->path();
            file_put_contents($path.'/features.php', '<?php return '.var_export(['website' => $hard, 'backup' => $hard], true).';');
            foreach (['backup', 'website'] as $module) {
                file_put_contents($path.'/'.$module.'.php', $hard ? '<?php return ["loaded" => true];' : '<?php throw new LogicException("Disabled config must not load");');
            }
            $originalApplication = app();
            $application = new Application(base_path());
            $application->useConfigPath($path);
            $application->useBootstrapPath($directory->path('bootstrap'));

            (new FeatureConfiguration)->bootstrap($application);

            expect($application->make(ConfigRepository::class)->has('website'))->toBe($hard)
                ->and($application->make(ConfigRepository::class)->has('backup'))->toBe($hard)
                ->and($application->make(ConfigRepository::class)->get('features.website'))->toBe($hard);
        } finally {
            Container::setInstance($originalApplication);
            $directory->delete();
        }
    })->with([true, false]);

    it('keeps backup package discovery behind its hard flag', function (bool $hard): void {
        config(['features.backup' => $hard]);
        $manifest = new FeaturePackageManifest(new Filesystem, base_path(), app()->getCachedPackagesPath());

        expect(in_array(BackupServiceProvider::class, $manifest->providers(), true))->toBe($hard);
    })->with([true, false]);

    it('requires both the hard flag and the stored toggle', function (string $module, bool $hard, bool $soft): void {
        $class = FeatureManager::SETTINGS[$module];
        resolve($class)->fill(['enabled' => $soft])->save();
        config(['features.'.$module => $hard]);

        expect(resolve(FeatureManager::class)->envEnabled($module))->toBe($hard)
            ->and(resolve(FeatureManager::class)->enabled($module))->toBe($hard && $soft);
    })->with(['website', 'chat', 'documentation', 'backup'])->with([true, false])->with([true, false]);

    it('never resolves module settings or queries their groups while hard off', function (bool $cache): void {
        config(['settings.cache.enabled' => $cache]);
        $resolved = [];
        $queries = [];
        foreach (FeatureManager::SETTINGS as $module => $class) {
            config(['features.'.$module => false]);
            app()->beforeResolving($class, function () use (&$resolved, $module): void {
                $resolved[] = $module;
            });
        }
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $features = resolve(FeatureManager::class);
        foreach (array_keys(FeatureManager::SETTINGS) as $module) {
            expect($features->enabled($module))->toBeFalse();
        }
        expect($features->publicWebsiteEnabled())->toBeFalse()
            ->and($features->enabled('unknown'))->toBeFalse()
            ->and($resolved)->toBeEmpty()
            ->and($queries)->toBeEmpty();
    })->with([true, false]);

    it('observes saved toggles in the same long lived process', function (string $module): void {
        $features = resolve(FeatureManager::class);
        $class = FeatureManager::SETTINGS[$module];
        expect($features->enabled($module))->toBeTrue();

        (new $class)->fill(['enabled' => false])->save();
        expect($features->enabled($module))->toBeFalse();

        (new $class)->fill(['enabled' => true])->save();
        expect($features->enabled($module))->toBeTrue();
    })->with(['website', 'chat', 'documentation', 'backup']);

    it('memoizes HTTP state until a settings save or new request scope', function (bool $cache): void {
        config(['settings.cache.enabled' => $cache, 'settings.cache.memo' => true]);
        resolve(SettingsContainer::class)->registerBindings();
        Event::subscribe(new SettingsEventSubscriber(new SettingsCacheFactory(config('settings'))));
        $console = new ReflectionProperty(app(), 'isRunningInConsole');
        $console->setValue(app(), false);
        try {
            $features = resolve(FeatureManager::class);
            expect($features->enabled('chat'))->toBeTrue();
            $loaded = resolve(ChatSettings::class);
            DB::table('settings')->where('group', 'chat')->where('name', 'enabled')->update(['payload' => 'false']);
            expect($features->enabled('chat'))->toBeTrue();

            $loaded->refresh()->save();

            expect($features->enabled('chat'))->toBeFalse();
            app()->forgetScopedInstances();
            expect(resolve(FeatureManager::class)->enabled('chat'))->toBeFalse();
        } finally {
            $console->setValue(app(), true);
        }
    })->with([true, false]);

    it('refreshes retained settings between jobs and scheduler evaluations', function (bool $cache): void {
        config(['settings.cache.enabled' => $cache, 'settings.cache.memo' => true]);
        resolve(SettingsContainer::class)->registerBindings();
        $features = resolve(FeatureManager::class);
        expect($features->enabled('chat'))->toBeTrue();
        $loaded = resolve(ChatSettings::class);
        DB::table('settings')->where('group', 'chat')->where('name', 'enabled')->update(['payload' => 'false']);

        expect($features->enabled('chat'))->toBeFalse();
        Event::dispatch(new JobProcessing('sync', new SyncJob(app(), '{}', 'sync', 'default')));
        expect(resolve(ChatSettings::class))->not->toBe($loaded)
            ->and($features->enabled('chat'))->toBeFalse();
    })->with([true, false]);

    it('clears the settings memo store at lifecycle boundaries', function (): void {
        config(['settings.cache.store' => null]);
        Cache::store()->put('feature-lifecycle-marker', true);
        expect(Cache::memo()->get('feature-lifecycle-marker'))->toBeTrue();
        Cache::store()->put('feature-lifecycle-marker', false);

        resolve(FeatureManager::class)->reset();

        expect(Cache::memo()->get('feature-lifecycle-marker'))->toBeFalse();
    });

    it('prevents container autowiring from bypassing hard off settings registration', function (): void {
        foreach (FeatureManager::SETTINGS as $module => $class) {
            config(['features.'.$module => false]);
        }
        new FeatureServiceProvider(app())->register();
        foreach (FeatureManager::SETTINGS as $module => $class) {
            expect(app()->bound($class))->toBeFalse()
                ->and(fn () => resolve($class))->toThrow(LogicException::class, 'Settings unavailable for disabled module: '.$module);
        }

    });

    it('saves public visibility through Website settings while General stays core', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ConfigureWebsite);
        patch(route('website.settings.update'), ['public_pages_enabled' => false])->assertSessionHasNoErrors();
        expect(resolve(WebsiteSettings::class)->public_pages_enabled)->toBeFalse()
            ->and(resolve(GeneralSettings::class)->toArray())->not->toHaveKey('public_pages_enabled');
        get('/')->assertRedirect();
        expect(fn () => resolve(UpdateWebsiteSettings::class)->handle($actor, ['public_pages_enabled' => 'invalid']))
            ->toThrow(ValidationException::class);
    });

    it('gates middleware using effective state', function (bool $hard, bool $soft): void {
        resolve(WebsiteSettings::class)->fill(['enabled' => $soft])->save();
        config(['features.website' => $hard]);
        Route::middleware('feature:website')->get('/feature-probe', fn (): string => 'enabled');

        get('/feature-probe')->assertStatus($hard && $soft ? 200 : 404);
    })->with([true, false])->with([true, false]);

    it('keeps CMS public state out of core settings', function (): void {
        expect(resolve(GeneralSettings::class)->toArray())->not->toHaveKey('public_pages_enabled')
            ->and(resolve(WebsiteSettings::class)->enabled)->toBeTrue()
            ->and(resolve(WebsiteSettings::class)->public_pages_enabled)->toBeTrue();

        get(route('login'))->assertInertia(fn ($page) => $page
            ->missing('general.public_pages_enabled')->where('features.website.public', true));
    });

    it('installs only hard on modules and preserves core data when enabling pending migrations', function (string $module): void {
        withIsolatedRestoreDatabase(function (string $connection) use ($module): void {
            $db = DB::connection($connection);
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => $db->getDatabaseName(),
                'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'),
                'DB_USERNAME' => $db->getConfig('username'), 'DB_PASSWORD' => $db->getConfig('password'), 'DB_URL' => '',
                'FEATURE_WEBSITE' => 'false', 'FEATURE_CHAT' => 'false',
                'FEATURE_DOCUMENTATION' => 'false', 'FEATURE_BACKUP' => 'false',
                'APP_CONFIG_CACHE' => storage_path('framework/testing/feature-config-'.getmypid().'.php'),
            ];
            $migrate = static function (array $overrides = []) use ($environment): void {
                new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), [...$environment, ...$overrides])->mustRun();
            };
            $migrate();
            expect($db->table('settings')->whereIn('group', array_keys(FeatureManager::SETTINGS))->count())->toBe(0);
            $tables = ['website' => ['website_contents', 'website_slugs', 'contact_messages'], 'chat' => ['chat_conversations', 'chat_participants', 'chat_messages'], 'documentation' => ['documentations'], 'backup' => []];
            foreach ($tables as $names) {
                foreach ($names as $table) {
                    expect($db->getSchemaBuilder()->hasTable($table))->toBeFalse();
                }
            }
            $coreMigrations = $db->table('migrations')->pluck('migration')->all();
            $db->table('settings')->where('group', 'general')->where('name', 'site_name')->update(['payload' => '"Keep core"']);

            $migrate(['FEATURE_'.mb_strtoupper($module) => 'true']);

            expect($db->table('settings')->where('group', $module)->where('name', 'enabled')->value('payload'))->toBe('true')
                ->and($db->table('settings')->where('group', 'general')->where('name', 'site_name')->value('payload'))->toBe('"Keep core"');
            foreach ($tables as $name => $names) {
                foreach ($names as $table) {
                    expect($db->getSchemaBuilder()->hasTable($table))->toBe($name === $module);
                }
            }
            $installed = $db->table('migrations')->pluck('migration')->all();
            $expected = [...glob(database_path('settings/'.$module.'/*.php')), ...glob(database_path('migrations/'.$module.'/*.php'))];
            expect(array_values(array_diff($installed, $coreMigrations)))->toEqualCanonicalizing(array_map(static fn (string $path): string => basename($path, '.php'), $expected));

            $migrate();
            expect($db->table('settings')->where('group', $module)->where('name', 'enabled')->value('payload'))->toBe('true');

            $script = <<<'PHP_WORKER'
                require "vendor/autoload.php";
                $app = require "bootstrap/app.php";
                $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                config(["cache.stores.file.path" => getenv("FEATURE_TEST_CACHE"), "cache.stores.file.lock_path" => getenv("FEATURE_TEST_CACHE")]);
                $class = App\Support\FeatureManager::SETTINGS[$argv[1]];
                if ($argv[2] === "off") {
                    resolve($class)->fill(["enabled" => false])->save();
                    exit;
                }
                $features = resolve(App\Support\FeatureManager::class);
                echo json_encode($features->enabled($argv[1])).PHP_EOL;
                flush();
                fgets(STDIN);
                echo json_encode($features->enabled($argv[1])).PHP_EOL;
                PHP_WORKER;
            $cache = new TemporaryDirectory(storage_path('framework/testing/feature-cache-'.getmypid()));
            $cache->create();
            $runtime = [...$environment, 'FEATURE_'.mb_strtoupper($module) => 'true', 'SETTINGS_CACHE_ENABLED' => 'true', 'CACHE_STORE' => 'file', 'FEATURE_TEST_CACHE' => $cache->path()];
            $input = new InputStream;
            $worker = new Process([PHP_BINARY, '-r', $script, $module, 'watch'], base_path(), $runtime, $input);
            try {
                $worker->start();
                expect($worker->waitUntil(static fn (string $type, string $output): bool => str_contains($output, "true\n")))->toBeTrue();
                new Process([PHP_BINARY, '-r', $script, $module, 'off'], base_path(), $runtime)->mustRun();
                $input->write("continue\n");
                $input->close();
                $worker->wait();
                expect($worker->isSuccessful())->toBeTrue()
                    ->and($worker->getOutput())->toBe("true\nfalse\n");
            } finally {
                $input->close();
                if ($worker->isRunning()) {
                    $worker->stop();
                }
                $cache->delete();
            }

        });
    })->with(['website', 'chat', 'documentation', 'backup']);
});
