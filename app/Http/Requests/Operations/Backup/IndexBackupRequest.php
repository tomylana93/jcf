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

    public function search(): string
    {
        return (string) $this->validated('filter.search');
    }

    public function sort(): string
    {
        return (string) $this->validated('sort', '-created_at');
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 10);
    }

    public function page(): int
    {
        return (int) $this->validated('page', 1);
    }
}
