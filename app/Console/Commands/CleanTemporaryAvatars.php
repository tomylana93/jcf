<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Account\CleanTemporaryAvatars as CleanAvatars;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('avatars:clean {--dry-run : List invalid or orphaned temporary avatars without deleting them} {--grace=5 : Protect uploads newer than this number of minutes; use 0 for immediate cleanup}')]
#[Description('Delete invalid temporary avatars, orphaned brand images, and conversion temp files')]
final class CleanTemporaryAvatars extends Command
{
    public function handle(CleanAvatars $cleanAvatars): int
    {
        $grace = filter_var($this->option('grace'), FILTER_VALIDATE_INT);
        if ($grace === false || $grace < 0) {
            $this->error('The grace period must be a non-negative integer.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $cleanAvatars->handle($dryRun, $grace);

        foreach ($result['media'] as $id) {
            $this->line("Temporary media: {$id}");
        }
        foreach ($result['directories'] as $directory) {
            $this->line("Orphaned directory: {$directory}");
        }

        foreach ($result['conversions'] as $directory) {
            $this->line("Conversion temp directory: {$directory}");
        }
        foreach ($result['brand'] as $path) {
            $this->line("Orphaned brand image: {$path}");
        }

        $operation = $dryRun ? 'Found' : 'Deleted';
        $this->info("{$operation} ".count($result['media']).' temporary media and '.count($result['directories']).' orphaned directories.');

        $this->info("{$operation} ".count($result['conversions']).' conversion temp directories.');
        $this->info("{$operation} ".count($result['brand']).' orphaned brand images.');

        return self::SUCCESS;
    }
}
