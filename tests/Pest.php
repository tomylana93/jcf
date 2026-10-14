<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Assert;
use Spatie\TemporaryDirectory\TemporaryDirectory;
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
