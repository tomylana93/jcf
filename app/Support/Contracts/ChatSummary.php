<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use App\Models\User;

interface ChatSummary
{
    /** @return array{unread_count: int} */
    public function handle(?User $actor): array;
}
