<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

final readonly class GetBackupPage
{
    public function __construct(private GetBackups $getBackups) {}

    /** @return array{data: list<array{filename: string, size: int, created_at: string}>, total: int, current_page: int, per_page: int} */
    public function handle(string $search, string $sort, int $perPage, int $page): array
    {
        $items = collect($this->getBackups->handle())->filter(fn (array $backup): bool => str_contains(mb_strtolower($backup['filename']), mb_strtolower($search)));
        $items = $items->sortBy(mb_ltrim($sort, '-'), SORT_REGULAR, str_starts_with($sort, '-'))->values();

        return ['data' => array_values($items->forPage($page, $perPage)->all()), 'total' => $items->count(), 'current_page' => $page, 'per_page' => $perPage];
    }
}
