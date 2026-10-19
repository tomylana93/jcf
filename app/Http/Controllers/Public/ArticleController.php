<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildPublicArticlePage;
use App\Actions\Website\GetPublicWebsiteContent;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ArticleController extends Controller
{
    public function index(Request $request, BuildPublicArticlePage $page): Response
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000']]);

        return Inertia::render('public/Articles', ['website' => $page->index($request->integer('page', 1))]);
    }

    public function show(string $slug, GetPublicWebsiteContent $content, BuildPublicArticlePage $page): Response|RedirectResponse
    {
        $result = $content->handle($slug, true);
        abort_if($result === null, 404);
        if ($result['redirect_slug'] !== null) {
            return to_route('articles.show', ['slug' => $result['redirect_slug']], 301);
        }

        return Inertia::render('public/Articles', ['website' => $page->show($result['content'])]);
    }
}
