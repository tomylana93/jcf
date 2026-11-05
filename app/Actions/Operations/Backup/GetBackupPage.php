<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

final readonly class GetBackupPage
{
    public function __construct(private GetBackups $getBackups) {}

    /**
     * Filter, sort and paginate the stored archives with the same paginator shape as database tables.
     *
     * @return LengthAwarePaginator<int, array{filename: string, size: int, created_at: string}>
     */
    public function handle(string $search, string $sort, int $perPage, int $page): LengthAwarePaginator
    {
        $items = collect($this->getBackups->handle())->filter(fn (array $backup): bool => str_contains(mb_strtolower($backup['filename']), mb_strtolower($search)));
        $items = $items->sortBy(mb_ltrim($sort, '-'), SORT_REGULAR, str_starts_with($sort, '-'))->values();

        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, ['path' => Paginator::resolveCurrentPath()]);
    }
}
