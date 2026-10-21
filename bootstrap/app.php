<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenRequired;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleLocale;
use App\Support\FeatureConfiguration;
use App\Support\FeaturePackageManifest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['content_json.*']);

        $middleware->alias(['verified' => EnsureEmailIsVerifiedWhenRequired::class, 'feature' => EnsureFeatureEnabled::class]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'timezone', 'locale']);

        $middleware->web(append: [
            EnsureAccountIsActive::class,
            HandleLocale::class,
            HandleAppearance::class,
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if (app()->environment('testing') || $request->is('api/*') || $request->expectsJson() || ! in_array($response->getStatusCode(), [403, 404, 419, 500, 503], true)) {
                return $response;
            }

            Inertia::flushShared();
            Inertia::disableSsr();
            Inertia::setRootView('error');

            $errorResponse = Inertia::render('ErrorPage', [
                'status' => $response->getStatusCode(),
                'general' => ['site_name' => config('app.name')],
                'locale' => [
                    'current' => app()->getLocale(),
                    'fallback' => config('app.fallback_locale'),
                ],
            ])->toResponse($request)
                ->setStatusCode($response->getStatusCode())
                ->setPrivate();

            $errorResponse->headers->set('Cache-Control', 'no-store, private');
            if ($response->headers->has('Retry-After')) {
                $errorResponse->headers->set('Retry-After', $response->headers->get('Retry-After'));
            }

            return $errorResponse;
        });

        $exceptions->dontFlash(['smtp_password']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

$app->bind(LoadConfiguration::class, FeatureConfiguration::class);
$app->singleton(PackageManifest::class, fn (Application $app): FeaturePackageManifest => new FeaturePackageManifest(
    new Filesystem,
    $app->basePath(),
    $app->getCachedPackagesPath(),
));

return $app;
