<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Actions\Identity\User\CreateUser;
use App\Actions\Identity\User\GetUserOptions;
use App\Actions\Identity\User\SendUserInvitation;
use App\Actions\Identity\User\UpdateUser;
use App\Actions\Identity\User\UpdateUserStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\User\SendUserInvitationRequest;
use App\Http\Requests\Identity\User\StoreUserRequest;
use App\Http\Requests\Identity\User\UpdateUserRequest;
use App\Http\Requests\Identity\User\UpdateUserStatusRequest;
use App\Http\Resources\Identity\UserResource;
use App\Models\User;
use App\Tables\Identity\UserTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function index(Request $request, UserTable $table, GetUserOptions $getUserOptions): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('master-data/users/Index', [
            'users' => $table->paginateResource($request, UserResource::class),
            ...$getUserOptions->handle($request->user()),
        ]);
    }

    public function export(Request $request, UserTable $table): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection($table->exportRecords($request));
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $createUser->handle($request->validated(), $request->user());
        $this->toastSuccess(__('user.status.created'));

        return to_route('master-data.users.index');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->validated(), $request->user());
        $this->toastSuccess(__('user.status.updated'));

        return back(fallback: route('master-data.users.index'));
    }

    public function sendInvitation(SendUserInvitationRequest $request, User $user, SendUserInvitation $sendInvitation): RedirectResponse
    {
        $sendInvitation->handle($user, $request->user());
        $this->toastSuccess(__('user.status.invitation_queued'));

        return back();
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user, UpdateUserStatus $updateUserStatus): RedirectResponse
    {
        $status = UserStatus::from($request->string('status')->toString());
        $updateUserStatus->handle($user, $status, $request->user());
        $this->toastSuccess(__($status === UserStatus::Active ? 'user.status.activated' : 'user.status.disabled'));

        return back();
    }
}
