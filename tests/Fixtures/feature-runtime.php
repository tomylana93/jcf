<?php

declare(strict_types=1);

use App\Actions\System\GetSystemHealth;
use App\Enums\Role;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();
throw_unless($app->environment('testing') && str_contains(DB::connection()->getDatabaseName(), 'test'), RuntimeException::class, 'Disposable test database required.');
Artisan::call('init:permission', ['--no-interaction' => true]);
$user = User::factory()->create();
$user->assignRole(Role::SuperAdmin);
auth()->guard()->setUser($user);
Inertia::disableSsr();
$features = resolve(FeatureManager::class);
if ($features->available('backup')) {
    config(['backup.backup.password' => null]);
}
$module = $argv[1];
if ($module !== 'all' && $features->available($module)) {
    resolve(FeatureManager::SETTINGS[$module])->fill(['enabled' => $argv[2] === '1'])->save();
}
$state = $features->state();
$queries = [];
DB::listen(function (QueryExecuted $event) use (&$queries, $state): void {
    foreach (['website' => 'website_', 'chat' => 'chat_', 'documentation' => 'documentations'] as $name => $table) {
        if (! $state[$name]['enabled'] && str_contains($event->sql, '"'.$table)) {
            $queries[] = $event->sql;
        }
    }
});
$core = [];
$permissions = [];
$kernel = resolve(Kernel::class);
foreach (['/dashboard', '/settings', '/permissions', '/search?query=manual', '/system'] as $path) {
    $request = Request::create($path, 'GET', server: ['HTTP_ACCEPT' => 'text/html']);
    $response = $kernel->handle($request);
    $core[$path] = $response->getStatusCode();
    if ($path === '/dashboard' && $response->getStatusCode() === 200) {
        $permissions = TestResponse::fromBaseResponse($response)->viewData('page')['props']['auth']['permissions'];
    }
    $kernel->terminate($request, $response);
}
$paths = ['website' => '/website/settings', 'chat' => '/chat', 'documentation' => '/documentation', 'backup' => '/master-data/backups', 'all' => '/settings/modules'];
$request = Request::create($paths[$module], 'GET', server: ['HTTP_ACCEPT' => 'text/html']);
$response = $kernel->handle($request);
$kernel->terminate($request, $response);
$cleanup = Artisan::call('avatars:clean', ['--dry-run' => true, '--no-interaction' => true]);
$schedules = collect(resolve(Schedule::class)->events())->filter(fn ($event): bool => str_contains($event->command ?? '', 'backup'))->map(fn ($event): bool => $event->filtersPass($app))->values()->all();
$health = collect(resolve(GetSystemHealth::class)->handle())->keyBy('name');
echo json_encode(['core' => $core, 'cleanup_exit' => $cleanup, 'backup_schedules' => $schedules, 'permissions' => $permissions, 'features' => $features->state(), 'module_status' => $response->getStatusCode(), 'forbidden_queries' => $queries, 'backup_detail' => $health['backup']['detail']], JSON_THROW_ON_ERROR);
