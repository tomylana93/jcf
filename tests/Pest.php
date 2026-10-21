<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Events\ChatMessageCreated;
use App\Events\ChatReadUpdated;
use App\Models\Permission as PermissionModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\FileBasedMaintenanceMode;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Assert;
use Spatie\Permission\PermissionRegistrar;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\InputStream;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\withSession;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Inertia::disableSsr();
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        config([
            'jalara.operations.backup_lock_directory' => storage_path('framework/testing/backup-locks-'.getmypid()),
            'cache.stores.file.path' => storage_path('framework/testing/file-cache-'.getmypid()),
            'cache.stores.file.lock_path' => storage_path('framework/testing/file-cache-'.getmypid()),
        ]);
        Process::preventStrayProcesses();
        Sleep::fake();

        freezeTime();
    })
    ->in('Feature', 'Browser');

pest()->beforeEach(function (): void {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));

    Event::listen(RequestHandled::class, function (RequestHandled $event): void {
        $event->response->headers->set('Connection', 'close');
    });
})->in('Browser');

function skipUnlessFortifyHas(string $feature, ?string $message = null): void
{
    if (! Features::enabled($feature)) {
        Assert::markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
    }
}

function assignRolePermissions(User $user, Permission ...$permissions): void
{
    $role = Role::factory()->create();
    $role->syncPermissions(array_map(
        static fn (Permission $permission): PermissionModel => PermissionModel::findOrCreate($permission->value, 'web'),
        $permissions,
    ));

    $user->syncRoles($role);
}

function actingAsUserWithPermissions(Permission ...$permissions): User
{
    $user = User::factory()->create();
    assignRolePermissions($user, ...$permissions);
    actingAs($user);

    return $user;
}

function actingAsSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles(RoleEnum::SuperAdmin);
    actingAs($user);

    return $user;
}

function actingAsWithConfirmedPassword(User $user): void
{
    actingAs($user);
    withSession(['auth.password_confirmed_at' => time()]);
}

/** @param array<string, string> $files */
function createBackupTestArchive(array $files, ?string $password = null): array
{
    $disk = Storage::disk('backups');
    $disk->makeDirectory('jcf');
    $path = $disk->path('jcf/test.zip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $name => $contents) {
        if (str_ends_with($name, '/')) {
            $zip->addEmptyDir($name);

            continue;
        }
        $zip->addFromString($name, $contents);
        if ($password !== null) {
            $zip->setPassword($password);
            $zip->setEncryptionName($name, ZipArchive::EM_AES_256);
        }
    }
    $zip->close();

    return ['filename' => 'test.zip', 'checksum' => hash_file('sha256', $path)];
}

/** @param Closure(string): void $test */
function withIsolatedRestoreDatabase(Closure $test, string $connection = 'restore_test'): void
{
    $source = DB::connection();
    throw_if(! app()->environment('testing') || ! str_contains($source->getDatabaseName(), 'test'), RuntimeException::class, 'Restore tests require a disposable testing database.');
    $configuration = $source->getConfig();
    $configuration['url'] = null;
    $name = 'jcf_restore_test_'.mb_strtolower(Str::random(12));
    config(['database.connections.'.$connection.'_admin' => $configuration]);
    $admin = DB::connection($connection.'_admin');
    $admin->statement('CREATE DATABASE "'.$name.'"');
    config(['database.connections.'.$connection => [...$configuration, 'database' => $name]]);
    try {
        $test($connection);
    } finally {
        DB::purge($connection);
        $admin->statement('DROP DATABASE "'.$name.'"');
        DB::purge($connection.'_admin');
    }
}

function isolateBackupOperations(): TemporaryDirectory
{
    $directory = new TemporaryDirectory(storage_path('framework/testing/active-restore'))->create();
    config(['jalara.operations.restore_files_directory' => $directory->path('active'), 'jalara.operations.restore_directory' => $directory->path('staging'), 'jalara.operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
    File::ensureDirectoryExists($directory->path('active'));
    app()->instance(MaintenanceMode::class, new class($directory->path('maintenance.json')) extends FileBasedMaintenanceMode
    {
        public function __construct(private readonly string $file) {}

        protected function path(): string
        {
            return $this->file;
        }
    });

    return $directory;
}

function fakeSafetyBackup(): void
{
    Artisan::shouldReceive('call')->once()->with('backup:run', Mockery::type('array'))->andReturnUsing(function (string $command, array $arguments): int {
        Storage::disk('backups')->put('jcf/'.$arguments['--filename'], 'safety-backup');

        return 0;
    });
}

/** @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function websiteDraft(array $overrides = []): array
{
    return array_replace([
        'title' => 'Company news',
        'slug' => 'company-news',
        'excerpt' => 'An approved summary',
        'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '<script>example</script>']]]]],
        'sections' => [],
        'seo' => ['title' => 'News', 'description' => 'Company updates', 'robots' => 'index,follow'],
        'cover' => null,
    ], $overrides);
}

function chatRealtimeSocketScript(): string
{
    return <<<'JS_WRAP'
        (() => {
            class TestSocket extends EventTarget {
                constructor(url) {
                    super(); this.url = url; this.readyState = 0;
                    window.chatSocket = this;
                    setTimeout(() => {
                        this.readyState = 1;
                        this.onopen?.(new Event('open'));
                        this.deliver('pusher:connection_established', {socket_id: '123.456', activity_timeout: 120});
                    }, 0);
                }
                deliver(event, data) {
                    this.onmessage?.({data: JSON.stringify({event, data: JSON.stringify(data), channel: this.channel})});
                }
                send(payload) {
                    const message = JSON.parse(payload);
                    if (message.event === 'pusher:subscribe') {
                        this.channel = message.data.channel;
                        this.deliver('pusher_internal:subscription_succeeded', {});
                    }
                }
                close() { this.readyState = 3; this.onclose?.({code: 1000, wasClean: true}); }
            }
            TestSocket.CONNECTING = 0; TestSocket.OPEN = 1; TestSocket.CLOSING = 2; TestSocket.CLOSED = 3;
            window.WebSocket = TestSocket;
        })();
        JS_WRAP;
}

/** @return array{User, User} */
function createChatUsers(): array
{
    $users = User::factory()->count(2)->create();
    foreach ($users as $user) {
        assignRolePermissions($user, Permission::UseChat);
    }

    return [$users[0], $users[1]];
}

function withChatConcurrencyDatabase(Closure $test): void
{
    withIsolatedRestoreDatabase(function (string $connection) use ($test): void {
        $default = DB::getDefaultConnection();
        try {
            config(['database.connections.'.$connection.'.name' => $connection]);
            DB::setDefaultConnection($connection);
            Schema::swap(DB::connection($connection)->getSchemaBuilder());
            resolve(PermissionRegistrar::class)->forgetCachedPermissions();
            $paths = [database_path('migrations/0001_01_01_000000_create_users_table.php'), ...glob(database_path('migrations/*create_permission_tables.php')), ...glob(database_path('migrations/chat/*create_chat_*_table.php'))];
            foreach ($paths as $path) {
                (require $path)->up();
            }
            Event::fake([
                ChatMessageCreated::class,
                ChatReadUpdated::class,
            ]);
            $test();
        } finally {
            DB::setDefaultConnection($default);
            Schema::swap(DB::connection($default)->getSchemaBuilder());
            resolve(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    });
}

/**
 * @param  list<array<string, mixed>>  $operations
 * @return list<string>
 */
function runConcurrentChatOperations(array $operations): array
{
    $code = <<<'PHP'
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $input["connection"]["name"] = "chat_worker";
    config(["database.connections.chat_worker" => $input["connection"], "database.default" => "chat_worker", "cache.default" => "array"]);
    Illuminate\Support\Facades\DB::purge("chat_worker");
    Illuminate\Support\Facades\Event::fake([
        App\Events\ChatMessageCreated::class,
        App\Events\ChatReadUpdated::class,
    ]);
    echo "READY\n";
    flush();
    fgets(STDIN);
    $operation = $input["operation"];
    $actor = App\Models\User::query()->findOrFail($operation["actor"]);
    $result = match ($operation["action"]) {
        "start" => app(App\Actions\Chat\StartDirectConversation::class)->handle($actor, App\Models\User::query()->findOrFail($operation["recipient"])),
        "send" => app(App\Actions\Chat\SendChatMessage::class)->handle($actor, App\Models\ChatConversation::query()->findOrFail($operation["conversation"]), ["body" => $operation["body"], "client_message_id" => $operation["token"]]),
        "read" => app(App\Actions\Chat\MarkChatConversationRead::class)->handle($actor, App\Models\ChatConversation::query()->findOrFail($operation["conversation"]), $operation["sequence"]),
    };
    echo json_encode($result->id, JSON_THROW_ON_ERROR);
    PHP;

    $workers = [];
    try {
        foreach ($operations as $operation) {
            $input = new InputStream;
            $input->write(json_encode(['connection' => DB::connection()->getConfig(), 'operation' => $operation], JSON_THROW_ON_ERROR)."\n");
            $process = new Symfony\Component\Process\Process([PHP_BINARY, '-r', $code], base_path(), ['APP_ENV' => 'testing'], timeout: 30);
            $process->setInput($input);
            $process->start();
            $workers[] = [$process, $input];
        }

        foreach ($workers as [$process]) {
            $ready = $process->waitUntil(fn (): bool => str_contains($process->getOutput(), "READY\n"));
            if (! $ready) {
                throw new RuntimeException('Chat worker did not become ready: '.$process->getErrorOutput().$process->getOutput());
            }
        }

        foreach ($workers as [, $input]) {
            $input->write("GO\n");
            $input->close();
        }

        $results = [];
        foreach ($workers as [$process]) {
            $process->wait();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('Chat worker failed: '.$process->getErrorOutput().$process->getOutput());
            }
            $results[] = json_decode(explode("\n", mb_trim($process->getOutput()), 2)[1], true, flags: JSON_THROW_ON_ERROR);
        }

        return $results;
    } finally {
        foreach ($workers as [$process, $input]) {
            $input->close();
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }
}
