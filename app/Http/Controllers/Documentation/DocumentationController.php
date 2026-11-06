<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\SearchDocumentation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\SearchDocumentationRequest;
use App\Http\Resources\Documentation\DocumentationResource;
use App\Models\Documentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentationController extends Controller
{
    public function index(SearchDocumentationRequest $request, SearchDocumentation $search): Response
    {
        Gate::authorize('viewAny', Documentation::class);

        return Inertia::render('documentation/Index', [
            'documents' => $search->handle($request->search())->through(fn (Documentation $document): array => new DocumentationResource($document)->resolve($request)),
            'search' => $request->search(),
            'manage' => false,
        ]);
    }

    public function show(Request $request, Documentation $documentation): Response
    {
        Gate::authorize('view', $documentation);

        return Inertia::render('documentation/Show', ['document' => DocumentationResource::readable($documentation)->resolve($request)]);
    }
}
