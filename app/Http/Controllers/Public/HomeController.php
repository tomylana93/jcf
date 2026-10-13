<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(Request $request, GeneralSettings $settings): Response|RedirectResponse
    {
        if ($settings->public_pages_enabled) {
            return Inertia::render('public/Home', [
                'canonicalUrl' => mb_rtrim((string) config('app.url'), '/').'/',
            ]);
        }

        if ($request->user()) {
            return redirect()->intended(route('dashboard'));
        }

        return to_route('login');
    }
}
