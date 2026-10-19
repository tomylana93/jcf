<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\BuildWebsitePreview;
use App\Enums\Permission;
use App\Enums\WebsitePage;
use App\Enums\WebsitePreset;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class WebsitePreviewController extends Controller
{
    public function __invoke(Request $request, BuildWebsitePreview $preview): Response
    {
        /** @var User $user */
        $user = $request->user();
        /** @var WebsitePage $page */
        $page = WebsitePage::from((string) $request->route('page'));
        Gate::forUser($user)->authorize('preview', WebsiteContent::class);
        Validator::make($request->query(), ['preset' => ['sometimes', Rule::enum(WebsitePreset::class)]])->validate();
        $preset = $request->query('preset') !== null ? WebsitePreset::from((string) $request->query('preset')) : null;
        $withDraft = $user->can(Permission::EditWebsiteContent) || $user->can(Permission::PublishWebsiteContent);

        return Inertia::render('public/Preview', ['website' => $preview->handle($page, $preset, $withDraft)]);
    }
}
