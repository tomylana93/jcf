<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\DiscardTemporaryAvatar;
use App\Actions\Account\GetTemporaryAvatar;
use App\Actions\Account\SaveAvatar;
use App\Actions\Account\UploadTemporaryAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarSaveRequest;
use App\Http\Requests\Account\AvatarUploadRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProfileAvatarController extends Controller
{
    public function upload(AvatarUploadRequest $request, UploadTemporaryAvatar $uploadAvatar): RedirectResponse
    {
        $uploadAvatar->handle($request->user(), $request->file('avatar'));

        return to_route('profile.edit');
    }

    public function update(AvatarSaveRequest $request, SaveAvatar $saveAvatar): RedirectResponse
    {
        $saveAvatar->handle($request->user(), $request->validated('temporary_avatar'), (bool) $request->validated('remove_avatar', false));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.avatar.status.saved')]);

        return to_route('profile.edit');
    }

    public function preview(Request $request, string $temporaryAvatar, GetTemporaryAvatar $getTemporaryAvatar): BinaryFileResponse
    {
        $media = $getTemporaryAvatar->handle($request->user(), $temporaryAvatar);

        return response()->file($media->getPath('preview'), ['Cache-Control' => 'no-store'])->setPrivate();
    }

    public function discard(AvatarSaveRequest $request, DiscardTemporaryAvatar $discardAvatar): RedirectResponse
    {
        $discardAvatar->handle($request->user(), $request->validated('temporary_avatar'));

        return to_route('profile.edit');
    }
}
