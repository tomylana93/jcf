<?php

declare(strict_types=1);

use App\Actions\Account\CleanTemporaryAvatars;
use App\Actions\Account\PrepareAvatar;
use App\Actions\Settings\CleanOrphanedBrandImages;
use App\Models\User;
use App\Settings\BrandSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

use function Pest\Laravel\artisan;

describe('Temporary avatar cleanup', function (): void {
    it('cleans orphaned brand uploads while retaining active images and unrelated files', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        $disk = Storage::disk('public');
        $settings = resolve(BrandSettings::class);
        $settings->logo_path = 'brand/'.Str::random(40).'.png';
        $settings->logo_text_path = 'brand/'.Str::random(40).'.png';
        $settings->favicon_path = 'brand/'.Str::random(40).'.webp';
        $settings->auth_background_path = 'brand/'.Str::random(40).'.webp';
        $settings->save();
        $orphan = 'brand/'.Str::random(40).'.png';
        $unrelated = 'brand/manual.png';
        foreach ([$settings->logo_path, $settings->logo_text_path, $settings->favicon_path, $settings->auth_background_path, $orphan, $unrelated] as $path) {
            $disk->put($path, 'image');
        }

        artisan('avatars:clean', ['--grace' => 0, '--dry-run' => true])
            ->expectsOutputToContain('Found 1 orphaned brand images.')
            ->assertSuccessful();

        $disk->assertExists($orphan);

        artisan('avatars:clean', ['--grace' => 0])
            ->expectsOutputToContain('Deleted 1 orphaned brand images.')
            ->assertSuccessful();

        $disk->assertMissing($orphan);
        $disk->assertExists([$settings->logo_path, $settings->logo_text_path, $settings->favicon_path, $settings->auth_background_path, $unrelated]);
    });

    it('immediately deletes invalid or orphaned temp media while retaining valid drafts and permanent photos', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        $user = User::factory()->create();

        $active = resolve(PrepareAvatar::class)->handle($user, UploadedFile::fake()->image('active.png'), 'avatar');
        $draft = resolve(PrepareAvatar::class)->handle($user, UploadedFile::fake()->image('draft.png'), 'avatar_pending');

        $other = User::factory()->create();

        $missing = resolve(PrepareAvatar::class)->handle($other, UploadedFile::fake()->image('missing.png'), 'avatar_pending');

        Storage::disk('local')->delete($missing->getPathRelativeToRoot());

        $orphan = resolve(PrepareAvatar::class)->handle(User::factory()->create(), UploadedFile::fake()->image('orphan.png'), 'avatar_pending');

        $orphan->update(['model_id' => Str::uuid()]);

        $prepared = resolve(PrepareAvatar::class)->handle($user, UploadedFile::fake()->image('prepared.png'), 'avatar_prepared');
        $expired = resolve(PrepareAvatar::class)->handle(User::factory()->create(), UploadedFile::fake()->image('expired.png'), 'avatar_pending');

        $expired->update(['created_at' => now()->subDay()]);

        artisan('avatars:clean', ['--grace' => 0])->assertSuccessful();

        expect($draft->fresh())->not->toBeNull()
            ->and($active->fresh())->not->toBeNull();

        foreach ([$missing, $orphan, $prepared, $expired] as $media) {
            expect($media->fresh())->toBeNull();
            Storage::disk($media->disk)->assertMissing($media->getPathRelativeToRoot());
        }
        Storage::disk('public')->assertExists($active->getPathRelativeToRoot());
    });

    it('removes temp avatars with missing or incomplete conversions', function (string $conversion, bool $missingFile): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');

        $media = resolve(PrepareAvatar::class)->handle(User::factory()->create(), UploadedFile::fake()->image('photo.png'), 'avatar_pending');

        if ($missingFile) {
            Storage::disk('local')->delete($media->getPathRelativeToRoot($conversion));
        } else {
            $media->update(['generated_conversions' => ['thumb' => $conversion !== 'thumb', 'preview' => $conversion !== 'preview']]);
        }

        artisan('avatars:clean', ['--grace' => 0])->assertSuccessful();

        expect($media->fresh())->toBeNull();

        Storage::disk('local')->assertMissing($media->getPathRelativeToRoot());
    })->with([['thumb', true], ['preview', true], ['thumb', false], ['preview', false]]);

    it('keeps uploads that are still within the processing grace period', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');

        $prepared = resolve(PrepareAvatar::class)->handle(User::factory()->create(), UploadedFile::fake()->image('photo.png'), 'avatar_prepared');

        $disk = Storage::disk('local');
        $path = '99999/'.Str::uuid().'.png';
        $disk->put($path, 'upload in progress');

        artisan('avatars:clean')->assertSuccessful();

        expect($prepared->fresh())->not->toBeNull();

        $disk->assertExists($path);
        Storage::disk('public')->assertExists($prepared->getPathRelativeToRoot());
    });

    it('lists candidates in dry run without deleting rows or files', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');

        $media = resolve(PrepareAvatar::class)->handle(User::factory()->create(), UploadedFile::fake()->image('photo.png'), 'avatar_pending');

        $media->update(['model_id' => Str::uuid()]);
        $orphanPath = '99999/'.Str::uuid().'.png';
        Storage::disk('local')->put($orphanPath, 'orphan');

        artisan('avatars:clean', ['--grace' => 0, '--dry-run' => true])
            ->expectsOutputToContain('Found 1 temporary media and 1 orphaned directories.')
            ->assertSuccessful();

        expect($media->fresh())->not->toBeNull();

        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        Storage::disk('local')->assertExists($orphanPath);
    });

    it('deletes identifiable orphan directories while retaining unrelated private files and directories used by media', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        $disk = Storage::disk('local');

        $media = resolve(PrepareAvatar::class)->handle(User::factory()->create(), UploadedFile::fake()->image('draft.png'), 'avatar_pending');

        $orphan = '99999/'.Str::uuid().'.png';
        $disk->put($orphan, 'orphan');
        $disk->put('99998/document.pdf', 'unrelated numeric directory');
        $disk->put('uploads/document.txt', 'unrelated directory');
        $disk->makeDirectory('99997');

        artisan('avatars:clean', ['--grace' => 0])
            ->expectsOutputToContain('Deleted 0 temporary media and 1 orphaned directories.')
            ->assertSuccessful();

        $disk->assertMissing($orphan);
        $disk->assertExists(['99998/document.pdf', 'uploads/document.txt', $media->getPathRelativeToRoot()]);

        expect($disk->directoryExists('99997'))->toBeTrue()
            ->and($media->fresh())->not->toBeNull();
    });

    it('cleans orphaned public staging files left after the media record disappears', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        $user = User::factory()->create();

        $active = resolve(PrepareAvatar::class)->handle($user, UploadedFile::fake()->image('active.png'), 'avatar');
        $prepared = resolve(PrepareAvatar::class)->handle($user, UploadedFile::fake()->image('prepared.png'), 'avatar_prepared');

        DB::table('media')->where('id', $prepared->id)->delete();

        artisan('avatars:clean', ['--grace' => 0])
            ->expectsOutputToContain('Deleted 0 temporary media and 1 orphaned directories.')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing($prepared->getPathRelativeToRoot());
        Storage::disk('public')->assertExists($active->getPathRelativeToRoot());

        expect($active->fresh())->not->toBeNull();
    });

    it('skips filesystem scanning when custom media paths are configured', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        config(['media-library.custom_path_generators' => [User::class => 'CustomPathGenerator']]);
        $path = '99999/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, 'orphan');

        artisan('avatars:clean', ['--grace' => 0])->assertSuccessful();

        Storage::disk('local')->assertExists($path);
    });

    it('reports a filesystem deletion failure instead of claiming cleanup succeeded', function (): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        $disk = Storage::disk('local');
        $path = '99999/'.Str::uuid().'.png';
        $disk->put($path, 'orphan');
        $failingDisk = Mockery::mock($disk);
        $failingDisk->shouldReceive('deleteDirectory')->with('99999')->once()->andReturn(false);
        $publicDisk = Storage::disk('public');
        Storage::shouldReceive('disk')->with('local')->andReturn($failingDisk);
        Storage::shouldReceive('disk')->with('public')->andReturn($publicDisk);

        expect(fn () => resolve(CleanTemporaryAvatars::class)->handle(false, 0))
            ->toThrow(RuntimeException::class, 'Failed to delete orphaned avatar directory: 99999');

        $disk->assertExists($path);
    });

    it('cleans conversion scratch directories immediately and supports dry run', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        $disk = Storage::disk('local');
        $path = 'media-library-temp/'.Str::random(32).'/original.png';
        $disk->put($path, 'failed conversion');

        artisan('avatars:clean', ['--grace' => 0, '--dry-run' => true])
            ->expectsOutputToContain('Found 1 conversion temp directories.')
            ->assertSuccessful();

        $disk->assertExists($path);

        artisan('avatars:clean', ['--grace' => 0])
            ->expectsOutputToContain('Deleted 1 conversion temp directories.')
            ->assertSuccessful();

        $disk->assertMissing($path);
    });

    it('protects recent conversion work and deletes stale conversion files and empty scratch directories', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        $disk = Storage::disk('local');
        $path = 'media-library-temp/'.Str::random(32).'/original.png';
        $disk->put($path, 'conversion in progress');

        artisan('avatars:clean')->assertSuccessful();

        $disk->assertExists($path);

        touch($disk->path($path), now()->subMinutes(6)->getTimestamp());
        touch(dirname($disk->path($path)), now()->subMinutes(6)->getTimestamp());
        $empty = 'media-library-temp/'.Str::random(32);
        $disk->makeDirectory($empty);
        touch($disk->path($empty), now()->subMinutes(6)->getTimestamp());

        artisan('avatars:clean')
            ->expectsOutputToContain('Deleted 2 conversion temp directories.')
            ->assertSuccessful();

        $disk->assertMissing($path);

        expect($disk->directoryExists($empty))->toBeFalse();
    });

    it('reports failure to delete conversion scratch files', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        $disk = Storage::disk('local');
        $path = 'media-library-temp/'.Str::random(32).'/original.png';
        $disk->put($path, 'failed conversion');
        File::partialMock()->shouldReceive('deleteDirectory')->once()->andReturn(false);

        expect(fn () => resolve(CleanTemporaryAvatars::class)->handle(false, 0))
            ->toThrow(RuntimeException::class, 'Failed to delete conversion temp directory:');

        $disk->assertExists($path);
    });

    it('rejects invalid grace values without removing any file', function (string $grace): void {
        Storage::fake('local');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('media-library-temp')]);
        Storage::fake('public');
        $path = '99999/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, 'orphan');

        artisan('avatars:clean', ['--grace' => $grace])->assertFailed();

        Storage::disk('local')->assertExists($path);
    })->with(['-1', 'invalid']);

    it('retains recent brand uploads until the grace period expires', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('scratch')]);
        $disk = Storage::disk('public');
        $recent = 'brand/'.Str::random(40).'.png';
        $stale = 'brand/'.Str::random(40).'.png';
        $disk->put($recent, 'new');
        $disk->put($stale, 'old');
        touch($disk->path($stale), now()->subMinutes(6)->getTimestamp());

        artisan('avatars:clean')->assertSuccessful();

        $disk->assertExists($recent);
        $disk->assertMissing($stale);
    });

    it('reports a brand deletion failure', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('scratch')]);
        $disk = Storage::disk('public');
        $path = 'brand/'.Str::random(40).'.png';
        $disk->put($path, 'orphan');
        $failingDisk = Mockery::mock($disk);
        $failingDisk->shouldReceive('delete')->with($path)->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingDisk);

        expect(fn () => resolve(CleanOrphanedBrandImages::class)->handle(false, 0))
            ->toThrow(RuntimeException::class, 'Failed to delete orphaned brand file:');

        $disk->assertExists($path);
    });

    it('respects the configured media prefix without scanning other directories', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config([
            'media-library.prefix' => 'avatars',
            'media-library.temporary_directory_path' => Storage::disk('local')->path('scratch'),
        ]);
        $disk = Storage::disk('local');
        $stale = 'avatars/99999/'.Str::uuid().'.png';
        $outside = '99998/'.Str::uuid().'.png';
        $disk->put($stale, 'orphan');
        $disk->put($outside, 'outside prefix');
        touch($disk->path($stale), now()->subMinutes(6)->getTimestamp());

        artisan('avatars:clean')->assertSuccessful();

        $disk->assertMissing($stale);
        $disk->assertExists($outside);
    });

    it('skips orphan directory inference with runtime custom path generators', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        config(['media-library.temporary_directory_path' => Storage::disk('local')->path('scratch')]);
        $path = '99999/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, 'custom storage');
        $generator = new class extends DefaultPathGenerator {};
        PathGeneratorFactory::setCustomPathGenerators(User::class, $generator::class);

        try {
            artisan('avatars:clean', ['--grace' => 0])->assertSuccessful();

            Storage::disk('local')->assertExists($path);
        } finally {
            PathGeneratorFactory::setCustomPathGenerators(User::class, DefaultPathGenerator::class);
        }
    });
});
