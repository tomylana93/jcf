<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Account\PruneExpiredAvatarUploads;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('avatars:prune')]
#[Description('Delete expired temporary avatar uploads')]
final class PruneTemporaryAvatars extends Command
{
    public function handle(PruneExpiredAvatarUploads $pruneExpiredAvatarUploads): int
    {
        $pruneExpiredAvatarUploads->handle();

        return self::SUCCESS;
    }
}
