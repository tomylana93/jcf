<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations\Backup;

use App\Actions\Operations\Backup\IsBackupConfigured;
use Illuminate\Validation\ValidationException;

/**
 * Reports an unconfigured backup module before field validation, so the cause is shown instead of field errors.
 */
trait EnsuresBackupConfigured
{
    abstract protected function backupErrorField(): string;

    protected function prepareForValidation(): void
    {
        throw_unless(resolve(IsBackupConfigured::class)->handle(), ValidationException::withMessages([$this->backupErrorField() => __('backup.error.not_configured')]));
    }
}
