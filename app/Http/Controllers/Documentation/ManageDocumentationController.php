<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\DeleteDocumentation;
use App\Actions\Documentation\SaveDocumentation;
use App\Actions\Documentation\SearchDocumentation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\SaveDocumentationRequest;
use App\Http\Requests\Documentation\SearchDocumentationRequest;
use App\Models\Documentation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ManageDocumentationController extends Controller
{
    public function index(SearchDocumentationRequest $request, SearchDocumentation $search): Response
    {
        Gate::authorize('create', Documentation::class);

        return Inertia::render('documentation/Index', [
            'documents' => $search->handle($request->search(), true),
            'search' => $request->search(),
            'manage' => true,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Documentation::class);

        return Inertia::render('documentation/Edit', ['document' => null]);
    }

    public function store(SaveDocumentationRequest $request, SaveDocumentation $save): RedirectResponse
    {
        $documentation = $save->handle($request->user(), $request->validated());
        $this->toastSuccess(__('documentation.status.saved'));

        return to_route('documentation.manage.edit', $documentation);
    }

    public function edit(Documentation $documentation): Response
    {
        Gate::authorize('update', $documentation);

        return Inertia::render('documentation/Edit', ['document' => $documentation->only(['id', 'title', 'slug', 'summary', 'content_json', 'status'])]);
    }

    public function update(SaveDocumentationRequest $request, Documentation $documentation, SaveDocumentation $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated(), $documentation);
        $this->toastSuccess(__('documentation.status.saved'));

        return to_route('documentation.manage.edit', $documentation);
    }

    public function destroy(Documentation $documentation, DeleteDocumentation $deleteDocumentation): RedirectResponse
    {
        Gate::authorize('delete', $documentation);
        $deleteDocumentation->handle($documentation);
        $this->toastSuccess(__('documentation.status.deleted'));

        return to_route('documentation.manage.index');
    }
}
