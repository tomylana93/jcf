<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

describe('User avatars', function (): void {
    it('stores one public avatar with webp thumbnail and preview', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        $original = $user->addMedia(UploadedFile::fake()->image('original.png', 600, 400))->toMediaCollection('avatar');

        $replacement = $user->addMedia(UploadedFile::fake()->image('replacement.png', 600, 400))->toMediaCollection('avatar');

        assertModelMissing($original);
        expect($user->refresh()->getMedia('avatar'))->toHaveCount(1)
            ->and($replacement->model_id)->toBe($user->id)
            ->and($replacement->disk)->toBe('public')
            ->and(getimagesize($replacement->getPath('thumb')))->toMatchArray([0 => 128, 1 => 128, 'mime' => 'image/webp'])
            ->and(getimagesize($replacement->getPath('preview')))->toMatchArray([0 => 512, 1 => 512, 'mime' => 'image/webp']);

        Storage::disk('public')->assertMissing($original->getPathRelativeToRoot());
        actingAs($user)->get(route('profile.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('auth.user.avatar', $replacement->getUrl('thumb'))
            ->missing('auth.user.media'));
    });

    it('stores pending avatars privately without changing the active avatar', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $active = $user->addMedia(UploadedFile::fake()->image('active.png'))->toMediaCollection('avatar');

        $pending = $user->addMedia(UploadedFile::fake()->image('pending.png'))->toMediaCollection('avatar_pending');

        expect($pending->disk)->toBe('local')
            ->and($user->refresh()->avatar)->toBe($active->getUrl('thumb'));

        Storage::disk('local')->assertExists($pending->getPathRelativeToRoot());
    });

    it('rejects unsupported avatar files', function (string $collection): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();

        expect(fn () => $user->addMedia(UploadedFile::fake()->create('document.pdf', 1, 'application/pdf'))
            ->toMediaCollection($collection))->toThrow(FileUnacceptableForCollection::class)
            ->and($user->refresh()->media)->toBeEmpty();
    })->with(['avatar', 'avatar_pending', 'avatar_prepared']);

    it('deletes avatar files when the account is deleted', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $user->addMedia(UploadedFile::fake()->image('avatar.png'))->toMediaCollection('avatar');

        $user->delete();

        assertModelMissing($avatar);

        Storage::disk('public')->assertMissing($avatar->getPathRelativeToRoot());
    });
});
