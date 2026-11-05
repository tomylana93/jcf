<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use App\Jobs\Operations\CreateSystemBackup;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class QueueBackup
{
    public function __construct(private IsBackupConfigured $isBackupConfigured) {}

    /**
     * Queue a backup that notifies the actor when it finishes.
     *
     * @throws ValidationException
     */
    public function handle(User $actor): void
    {
        throw_unless($this->isBackupConfigured->handle(), ValidationException::withMessages(['backup' => __('backup.error.not_configured')]));

        dispatch(new CreateSystemBackup($actor->id));
    }
}
