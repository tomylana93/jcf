<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations\Backup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class UploadBackupRequest extends FormRequest
{
    use EnsuresBackupConfigured;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'archive' => ['required', 'file', 'mimes:zip', 'extensions:zip', 'max:'.config('jalara.operations.backup_upload_max_kilobytes')],
        ];
    }

    public function archive(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('archive');
    }

    protected function backupErrorField(): string
    {
        return 'archive';
    }
}
