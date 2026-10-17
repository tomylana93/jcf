<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildPublicHome;
use App\Http\Controllers\Controller;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(Request $request, GeneralSettings $settings, BuildPublicHome $home): Response|RedirectResponse
    {
        if ($settings->public_pages_enabled) {
            return Inertia::render('public/Home', [
                'website' => $home->handle(),
                'canonicalUrl' => mb_rtrim((string) config('app.url'), '/').'/',
            ]);
        }

        if ($request->user()) {
            return redirect()->intended(route('dashboard'));
        }

        return to_route('login');
    }
}
