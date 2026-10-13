<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

final readonly class SaveAvatar
{
    public function __construct(private PrepareAvatar $prepareAvatar) {}

    public function handle(User $user, ?string $temporaryUuid, bool $remove): void
    {
        if ($remove) {
            $user->clearMediaCollection('avatar');
            $user->clearMediaCollection('avatar_pending');

            return;
        }

        $prepared = null;

        try {
            $temporary = $user->media()->where('collection_name', 'avatar_pending')
                ->where('uuid', $temporaryUuid)->where('created_at', '>', now()->subDay())->firstOrFail();
            $prepared = $this->prepareAvatar->handle($user, $temporary->getPath(), 'avatar_prepared', 'temporary_avatar');

            $previous = DB::transaction(function () use ($user, $temporary, $prepared) {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $user->media()->whereKey($temporary->id)->where('collection_name', 'avatar_pending')
                    ->where('created_at', '>', now()->subDay())->lockForUpdate()->firstOrFail();
                $previous = $user->media()->where('collection_name', 'avatar')->get();
                $prepared->collection_name = 'avatar';
                $prepared->save();

                return $previous;
            });
        } catch (ModelNotFoundException) {
            $prepared?->delete();

            throw ValidationException::withMessages(['temporary_avatar' => __('account.avatar.status.expired')]);
        } catch (Throwable $exception) {
            $prepared?->delete();

            throw $exception;
        }

        $previous->each(fn (Media $media) => $media->delete());
        $temporary->delete();
        $user->unsetRelation('media');
    }
}
