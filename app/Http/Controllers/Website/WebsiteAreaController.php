<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\GetWebsiteArea;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class WebsiteAreaController extends Controller
{
    public function __invoke(Request $request, GetWebsiteArea $area): Response|RedirectResponse
    {
        if (! Gate::forUser($request->user())->allows('view', WebsiteContent::class)) {
            Gate::forUser($request->user())->authorize(Permission::ViewContactInbox->value);

            return to_route('website.inbox.index');
        }

        return Inertia::render('website/Index', ['area' => $area->handle()]);
    }
}
