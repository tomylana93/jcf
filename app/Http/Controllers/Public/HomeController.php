<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildPublicHome;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StartController;
use App\Support\FeatureManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(Request $request, FeatureManager $features): Response|RedirectResponse
    {
        if (! $features->publicWebsiteEnabled()) {
            return resolve(StartController::class)($request);
        }

        return Inertia::render('public/Home', [
            'website' => resolve(BuildPublicHome::class)->handle(),
            'canonicalUrl' => mb_rtrim((string) config('app.url'), '/').'/',
        ]);
    }
}
