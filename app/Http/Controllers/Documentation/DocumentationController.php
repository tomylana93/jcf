<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\SearchDocumentation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\SearchDocumentationRequest;
use App\Models\Documentation;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentationController extends Controller
{
    public function index(SearchDocumentationRequest $request, SearchDocumentation $search): Response
    {
        Gate::authorize('viewAny', Documentation::class);

        return Inertia::render('documentation/Index', [
            'documents' => $search->handle($request->string('search')->toString()),
            'search' => $request->string('search')->toString(),
            'manage' => false,
        ]);
    }

    public function show(Documentation $documentation): Response
    {
        Gate::authorize('view', $documentation);

        return Inertia::render('documentation/Show', ['document' => $documentation->only(['id', 'title', 'slug', 'summary', 'content_html', 'status'])]);
    }
}
