<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\SaveDocumentation;
use App\Actions\Documentation\SearchDocumentation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\SaveDocumentationRequest;
use App\Http\Requests\Documentation\SearchDocumentationRequest;
use App\Models\Documentation;
use App\Models\User;
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
            'documents' => $search->handle($request->string('search')->toString(), true),
            'search' => $request->string('search')->toString(),
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
        /** @var User $user */
        $user = $request->user();
        $documentation = $save->handle($user, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('documentation.status.saved')]);

        return to_route('documentation.manage.edit', $documentation);
    }

    public function edit(Documentation $documentation): Response
    {
        Gate::authorize('update', $documentation);

        return Inertia::render('documentation/Edit', ['document' => $documentation->only(['id', 'title', 'slug', 'summary', 'content_json', 'status'])]);
    }

    public function update(SaveDocumentationRequest $request, Documentation $documentation, SaveDocumentation $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $save->handle($user, $request->validated(), $documentation);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('documentation.status.saved')]);

        return to_route('documentation.manage.edit', $documentation);
    }

    public function destroy(Documentation $documentation): RedirectResponse
    {
        Gate::authorize('delete', $documentation);
        $documentation->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('documentation.status.deleted')]);

        return to_route('documentation.manage.index');
    }
}
