<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildPublicArticleIndex;
use App\Actions\Website\BuildPublicArticlePage;
use App\Actions\Website\GetPublicWebsiteContent;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ArticleController extends Controller
{
    public function index(Request $request, BuildPublicArticleIndex $page): Response
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000']]);

        $website = $page->handle($request->integer('page', 1));
        abort_if($website === null, 404);

        return Inertia::render('public/Articles', ['website' => $website]);
    }

    public function show(string $slug, GetPublicWebsiteContent $content, BuildPublicArticlePage $page): Response|RedirectResponse
    {
        $result = $content->handle($slug, true);
        abort_if($result === null, 404);
        if ($result['redirect_slug'] !== null) {
            return to_route('articles.show', ['slug' => $result['redirect_slug']], 301);
        }

        $website = $page->handle($result['content']);
        abort_if($website === null, 404);

        return Inertia::render('public/Articles', ['website' => $website]);
    }
}
