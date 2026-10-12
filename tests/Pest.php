<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

use function Pest\Laravel\freezeTime;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Inertia::disableSsr();
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        config([
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
