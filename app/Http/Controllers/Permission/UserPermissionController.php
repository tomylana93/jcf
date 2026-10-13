<?php

declare(strict_types=1);

namespace App\Http\Controllers\Permission;

use App\Actions\Permission\GetUserPermissionOptions;
use App\Actions\Permission\UpdateUserPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\UpdateUserPermissionsRequest;
use App\Http\Resources\Permission\UserPermissionResource;
use App\Models\User;
use App\Tables\Permission\UserPermissionTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class UserPermissionController extends Controller
{
    public function index(Request $request, UserPermissionTable $table): Response
    {
        Gate::authorize('viewPermissions', User::class);

        return Inertia::render('user-permissions/Index', [
            'users' => $table->paginate($request)->through(fn (User $user): array => new UserPermissionResource($user)->resolve($request)),
        ]);
    }

    public function edit(Request $request, User $user, GetUserPermissionOptions $options): Response
    {
        Gate::authorize('viewPermissions', User::class);
        $user->load(['roles.permissions', 'permissions', 'media']);

        return Inertia::render('user-permissions/Edit', [
            'user' => new UserPermissionResource($user)->resolve($request),
            'permissions' => $options->handle($user),
        ]);
    }

    public function update(UpdateUserPermissionsRequest $request, User $user, UpdateUserPermissions $updatePermissions): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        /** @var list<string> $permissions */
        $permissions = $request->validated('permissions');
        $updatePermissions->handle($user, $actor, $permissions);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('permission.status.updated')]);

        return back();
    }
}
