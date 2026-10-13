<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\Settings\CleanOrphanedBrandImages;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

final readonly class CleanTemporaryAvatars
{
    public function __construct(private CleanOrphanedBrandImages $cleanOrphanedBrandImages) {}

    /** @return array{media: list<int>, directories: list<string>, conversions: list<string>, brand: list<string>} */
    public function handle(bool $dryRun, int $graceMinutes): array
    {
        $result = [
            'media' => [],
            'directories' => [],
            'conversions' => $this->cleanConversionDirectories($dryRun, $graceMinutes),
            'brand' => $this->cleanOrphanedBrandImages->handle($dryRun, $graceMinutes),
        ];
        $cutoff = now()->subMinutes($graceMinutes);

        Media::query()->where('model_type', (new User)->getMorphClass())
            ->whereIn('collection_name', ['avatar_pending', 'avatar_prepared'])
            ->where('created_at', '<=', $cutoff)
            ->with('model')
            ->eachById(function (Media $media) use ($dryRun, &$result): void {
                if (! $this->isInvalid($media)) {
                    return;
                }

                $result['media'][] = $media->id;
                if (! $dryRun) {
                    $media->delete();
                }
            });

        if (config('media-library.path_generator') !== DefaultPathGenerator::class
            || config('media-library.custom_path_generators') !== []
            || PathGeneratorFactory::create(new Media(['model_type' => (new User)->getMorphClass()]))::class !== DefaultPathGenerator::class) {
            return $result;
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            $prefix = mb_trim(config('media-library.prefix', ''), '/');
            $candidates = [];
            foreach ($disk->directories($prefix) as $directory) {
                $id = basename($directory);
                if (ctype_digit($id)) {
                    $candidates[$id] = $directory;
                }
            }

            $existingIds = $candidates === [] ? [] : Media::query()
                ->whereIn('id', array_keys($candidates))
                ->pluck('id')
                ->map(static fn (int|string $id): string => (string) $id)
                ->all();

            foreach ($candidates as $id => $directory) {
                if (in_array((string) $id, $existingIds, true)) {
                    continue;
                }

                $files = $disk->allFiles($directory);
                if ($files === [] || collect($files)->contains(function (string $file) use ($directory, $disk, $cutoff, $graceMinutes): bool {
                    $relative = mb_substr($file, mb_strlen($directory) + 1);

                    return ! preg_match('/^(?:conversions\/)?[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}(?:-(?:thumb|preview))?\.(?:jpg|jpeg|png|webp)$/i', $relative)
                        || ($graceMinutes > 0 && $disk->lastModified($file) > $cutoff->getTimestamp());
                })) {
                    continue;
                }

                $result['directories'][] = $diskName.':'.$directory;
                throw_if(! $dryRun && ! $disk->deleteDirectory($directory), RuntimeException::class, "Failed to delete orphaned avatar directory: {$directory}");
            }
        }

        return $result;
    }

    /** @return list<string> */
    private function cleanConversionDirectories(bool $dryRun, int $graceMinutes): array
    {
        $configuredRoot = config('media-library.temporary_directory_path');
        $root = $configuredRoot ?? storage_path('media-library/temp');
        $result = [];
        if (! File::isDirectory($root)) {
            return $result;
        }

        $cutoff = now()->subMinutes($graceMinutes)->getTimestamp();
        foreach (File::directories($root) as $directory) {
            $latestModification = File::lastModified($directory);
            foreach (File::allFiles($directory) as $file) {
                $latestModification = max($latestModification, $file->getMTime());
            }
            if ($graceMinutes > 0 && $latestModification > $cutoff) {
                continue;
            }

            $result[] = basename($directory);
            throw_if(! $dryRun && ! File::deleteDirectory($directory), RuntimeException::class, "Failed to delete conversion temp directory: {$directory}");
        }

        return $result;
    }

    private function isInvalid(Media $media): bool
    {
        return $media->model === null
            || $media->collection_name === 'avatar_prepared'
            || $media->created_at->lte(now()->subDay())
            || ! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())
            || ! $media->hasGeneratedConversion('thumb')
            || ! $media->hasGeneratedConversion('preview')
            || ! Storage::disk($media->conversions_disk)->exists($media->getPathRelativeToRoot('thumb'))
            || ! Storage::disk($media->conversions_disk)->exists($media->getPathRelativeToRoot('preview'));
    }
}
