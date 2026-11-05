<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations\Backup;

use Illuminate\Foundation\Http\FormRequest;

final class RestoreBackupRequest extends FormRequest
{
    use EnsuresBackupConfigured;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['confirmation' => ['required', 'accepted']];
    }

    protected function backupErrorField(): string
    {
        return 'backup';
    }
}
