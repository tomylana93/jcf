<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\BuildPublicArticlePage;
use App\Actions\Website\GetWebsiteArticleEditor;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UnpublishWebsiteContent;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\SaveWebsiteArticleRequest;
use App\Http\Resources\Website\WebsiteArticleResource;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Tables\Website\WebsiteArticleTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class WebsiteArticleController extends Controller
{
    public function index(Request $request, WebsiteArticleTable $table): Response
    {
        Gate::authorize('view', WebsiteContent::class);

        return Inertia::render('website/articles/Index', ['articles' => WebsiteArticleResource::collection($table->paginate($request))->response()->getData(true)]);
    }

    public function create(GetWebsiteArticleEditor $editor): Response
    {
        Gate::authorize('create', WebsiteContent::class);

        return Inertia::render('website/articles/Edit', ['editor' => $editor->handle(null)]);
    }

    public function edit(WebsiteContent $article, GetWebsiteArticleEditor $editor): Response
    {
        abort_unless($article->page_key === null, 404);
        abort_unless(auth()->user()?->can(Permission::EditWebsiteContent) || auth()->user()?->can(Permission::PublishWebsiteContent), 403);

        return Inertia::render('website/articles/Edit', ['editor' => $editor->handle($article)]);
    }

    public function save(SaveWebsiteArticleRequest $request, SaveWebsiteDraft $save, ?WebsiteContent $article = null): RedirectResponse
    {
        abort_if($article !== null && $article->page_key !== null, 404);
        /** @var User $user */
        $user = $request->user();
        $record = $save->handle($user, Arr::except($request->validated(), ['version', 'author_id']), $article, $article === null ? null : $request->integer('version'), authorId: $request->string('author_id')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.draft_saved')]);

        return to_route('website.articles.edit', ['article' => $record->id]);
    }

    public function publish(Request $request, WebsiteContent $article, PublishWebsiteContent $publish): RedirectResponse
    {
        abort_unless($article->page_key === null, 404);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'published_at' => ['nullable', 'date', 'before_or_equal:now']]);
        /** @var User $user */
        $user = $request->user();
        $publish->handle($user, $article, (int) $data['version'], $data['published_at'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.published_toast')]);

        return back();
    }

    public function unpublish(Request $request, WebsiteContent $article, UnpublishWebsiteContent $unpublish): RedirectResponse
    {
        abort_unless($article->page_key === null, 404);
        /** @var User $user */
        $user = $request->user();
        $unpublish->handle($user, $article);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('website.status.unpublished_toast')]);

        return back();
    }

    public function image(Request $request, WebsiteContent $article, UploadWebsiteImage $upload): RedirectResponse
    {
        abort_unless($article->page_key === null, 404);
        $request->validate(['image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000']]);
        /** @var User $user */
        $user = $request->user();
        $image = $request->file('image');
        if ($image === null) {
            abort(422);
        }
        $media = $upload->handle($user, $article, $image);
        Inertia::flash('image', $media->id);

        return back();
    }

    public function preview(Request $request, WebsiteContent $article, BuildPublicArticlePage $page): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($article->page_key === null, 404);
        abort_unless(auth()->user()?->can(Permission::EditWebsiteContent) || auth()->user()?->can(Permission::PublishWebsiteContent), 403);

        $response = Inertia::render('public/Preview', ['website' => $page->preview($article)])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
