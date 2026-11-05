<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations\Backup;

use Illuminate\Foundation\Http\FormRequest;

final class IndexBackupRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'filter.search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'sort' => ['nullable', 'in:filename,-filename,created_at,-created_at,size,-size'],
        ];
    }
}
