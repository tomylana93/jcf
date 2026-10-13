<?php

declare(strict_types=1);

use App\Actions\Account\SaveAvatar;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Conversions\Events\ConversionWillStartEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

describe('Account avatar', function (): void {
    it('keeps temporary uploads private and saves the selected avatar', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        actingAs($user)->post(route('profile.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('photo.png', 600, 400),
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
        $temporary = $user->getFirstMedia('avatar_pending');

        expect($temporary)->not->toBeNull()
            ->and($temporary->disk)->toBe('local')
            ->and($user->getFirstMedia('avatar'))->toBeNull();

        get(route('profile.avatar.preview', $temporary->uuid))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        patch(route('profile.avatar.update'), ['temporary_avatar' => $temporary->uuid, 'remove_avatar' => false])
            ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        expect($user->fresh()->getFirstMedia('avatar'))->not->toBeNull()
            ->and($user->fresh()->getFirstMedia('avatar_pending'))->toBeNull();
    });

    it('rejects another account temporary upload and expired selections', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $owner = User::factory()->create();
        actingAs($owner)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('photo.png')])->assertSessionHasNoErrors();
        $temporary = $owner->getFirstMedia('avatar_pending');
        actingAs(User::factory()->create())->get(route('profile.avatar.preview', $temporary->uuid))->assertNotFound();

        patch(route('profile.avatar.update'), ['temporary_avatar' => $temporary->uuid])->assertInvalid('temporary_avatar');

        actingAs($owner);

        travel(25)->hours();
        patch(route('profile.avatar.update'), ['temporary_avatar' => $temporary->uuid])->assertInvalid('temporary_avatar');

        expect($owner->fresh()->getFirstMedia('avatar'))->toBeNull();
    });

    it('rejects unsupported uploads and protects endpoints from guests', function (): void {
        post(route('profile.avatar.upload'))->assertRedirect(route('login'));

        actingAs(User::factory()->create())->post(route('profile.avatar.upload'), [
            'avatar' => UploadedFile::fake()->create('active.svg', 10, 'image/svg+xml'),
        ])->assertInvalid('avatar');
    });

    it('prunes expired temporary uploads while preserving saved and recent media', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $saved = $user->addMedia(UploadedFile::fake()->image('saved.png'))->toMediaCollection('avatar');
        $expired = $user->addMedia(UploadedFile::fake()->image('expired.png'))->toMediaCollection('avatar_prepared');
        $expired->update(['created_at' => now()->subDays(2)]);
        $recent = $user->addMedia(UploadedFile::fake()->image('recent.png'))->toMediaCollection('avatar_pending');

        artisan('avatars:prune')->assertSuccessful();

        expect($expired->fresh())->toBeNull()
            ->and($saved->fresh())->not->toBeNull()
            ->and($recent->fresh())->not->toBeNull();

        Storage::disk('public')->assertExists($saved->getPathRelativeToRoot());
        Storage::disk('local')->assertExists($recent->getPathRelativeToRoot());
    });
    it('discards the draft and retains the active photo', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $active = $user->addMedia(UploadedFile::fake()->image('active.png'))->toMediaCollection('avatar');
        actingAs($user)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('draft.png')]);
        $temporary = $user->refresh()->getFirstMedia('avatar_pending');

        actingAs($user)->delete(route('profile.avatar.discard'), ['temporary_avatar' => $temporary->uuid])
            ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        expect($user->refresh()->getFirstMedia('avatar')->id)->toBe($active->id)
            ->and($user->getFirstMedia('avatar_pending'))->toBeNull();

        Storage::disk('local')->assertMissing($temporary->getPathRelativeToRoot());
    });

    it('removes the active photo and any temporary upload when removal is saved', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $active = $user->addMedia(UploadedFile::fake()->image('active.png'))->toMediaCollection('avatar');
        actingAs($user)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('draft.png')]);
        $temporary = $user->refresh()->getFirstMedia('avatar_pending');

        actingAs($user)->patch(route('profile.avatar.update'), ['remove_avatar' => true])->assertSessionHasNoErrors();

        expect($user->refresh()->getMedia('avatar'))->toBeEmpty()
            ->and($user->getMedia('avatar_pending'))->toBeEmpty();

        Storage::disk('public')->assertMissing($active->getPathRelativeToRoot());
        Storage::disk('local')->assertMissing($temporary->getPathRelativeToRoot());
    });

    it('rejects saving a missing temporary upload with an expiry message', function (): void {
        $user = User::factory()->create();

        try {
            resolve(SaveAvatar::class)->handle($user, Str::uuid()->toString(), false);
        } catch (ValidationException $exception) {
            expect($exception->errors())->toMatchArray([
                'temporary_avatar' => [__('account.avatar.expired')],
            ]);

            return;
        }

        expect(false)->toBeTrue('Saving a missing temporary upload should fail.');
    });

    it('retains the previous draft when a new conversion fails and cleans incomplete files', function (): void {
        Storage::fake('local');
        $user = User::factory()->create();
        actingAs($user)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('first.png')]);
        $previous = $user->refresh()->getFirstMedia('avatar_pending');
        Exceptions::fake();
        Event::listen(ConversionWillStartEvent::class, function (): void {
            throw new RuntimeException('Conversion unavailable');
        });

        actingAs($user)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('second.png')])->assertSessionHasErrors('avatar');

        Exceptions::assertReported(RuntimeException::class);
        expect($user->refresh()->getMedia('avatar_pending'))->toHaveCount(1)
            ->and($user->getFirstMedia('avatar_pending')->id)->toBe($previous->id)
            ->and(Storage::disk('local')->allFiles())->toHaveCount(3);
    });

    it('retains the active photo and draft when permanent conversion fails', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $active = $user->addMedia(UploadedFile::fake()->image('active.png'))->toMediaCollection('avatar');
        actingAs($user)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('draft.png')]);
        $temporary = $user->refresh()->getFirstMedia('avatar_pending');
        Exceptions::fake();
        Event::listen(ConversionWillStartEvent::class, function (): void {
            throw new RuntimeException('Conversion unavailable');
        });

        actingAs($user)->patch(route('profile.avatar.update'), ['temporary_avatar' => $temporary->uuid])->assertSessionHasErrors('temporary_avatar');

        Exceptions::assertReported(RuntimeException::class);
        expect($user->refresh()->getFirstMedia('avatar')->id)->toBe($active->id)
            ->and($user->getFirstMedia('avatar_pending')->id)->toBe($temporary->id)
            ->and($user->getMedia('avatar_prepared'))->toBeEmpty()
            ->and(Storage::disk('public')->allFiles())->toHaveCount(3);
    });

    it('cleans the prepared photo and retains the draft when activation fails', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $active = $user->addMedia(UploadedFile::fake()->image('active.png'))->toMediaCollection('avatar');
        actingAs($user)->post(route('profile.avatar.upload'), ['avatar' => UploadedFile::fake()->image('draft.png')]);
        $temporary = $user->refresh()->getFirstMedia('avatar_pending');
        Exceptions::fake();
        Event::listen('eloquent.updating: '.Media::class, function (Media $media): void {
            throw_if($media->collection_name === 'avatar', RuntimeException::class, 'Activation unavailable');
        });

        actingAs($user)->patch(route('profile.avatar.update'), ['temporary_avatar' => $temporary->uuid])->assertServerError();

        Exceptions::assertReported(RuntimeException::class);
        expect($user->refresh()->getFirstMedia('avatar')->id)->toBe($active->id)
            ->and($user->getFirstMedia('avatar_pending')->id)->toBe($temporary->id)
            ->and($user->getMedia('avatar_prepared'))->toBeEmpty()
            ->and(Storage::disk('public')->allFiles())->toHaveCount(3);
    });

});
