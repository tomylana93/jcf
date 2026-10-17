<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\GetWebsitePageEditor;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UnpublishWebsiteContent;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Enums\WebsitePage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\SaveWebsitePageRequest;
use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final class WebsitePageController extends Controller
{
    public function edit(Request $request, WebsitePage $page, GetWebsitePageEditor $editor): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can(Permission::EditWebsiteContent) || $user->can(Permission::PublishWebsiteContent), 403);

        return Inertia::render('website/Content', ['editor' => $editor->handle($page)]);
    }

    public function saveDraft(WebsitePage $page, SaveWebsitePageRequest $request, SaveWebsiteDraft $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $record = WebsiteContent::query()->where('page_key', $page->value)->first();
        $save->handle($user, $request->validated(), $record, $record === null ? null : $request->integer('version'), $page->value);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.draft_saved')]);

        return to_route('website.page.edit', ['page' => $page->value]);
    }

    public function publish(Request $request, WebsitePage $page, PublishWebsiteContent $publish): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $record = WebsiteContent::query()->where('page_key', $page->value)->firstOrFail();
        $publish->handle($user, $record, $record->draft_version);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.published_toast')]);

        return to_route('website.page.edit', ['page' => $page->value]);
    }

    public function unpublish(Request $request, WebsitePage $page, UnpublishWebsiteContent $unpublish): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $record = WebsiteContent::query()->where('page_key', $page->value)->firstOrFail();
        $unpublish->handle($user, $record);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.unpublished_toast')]);

        return to_route('website.page.edit', ['page' => $page->value]);
    }

    public function storeImage(Request $request, WebsitePage $page, UploadWebsiteImage $upload): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $record = WebsiteContent::query()->where('page_key', $page->value)->firstOrFail();
        Validator::make(['image' => $request->file('image')], ['image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000']])->validate();
        $upload->handle($user, $record, $request->file('image'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.image_uploaded')]);

        return back();
    }
}
