<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\GetWebsiteArea;
use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class WebsiteAreaController extends Controller
{
    public function __invoke(Request $request, GetWebsiteArea $area): Response
    {
        Gate::forUser($request->user())->authorize('view', WebsiteContent::class);

        return Inertia::render('website/Index', ['area' => $area->handle()]);
    }
}
