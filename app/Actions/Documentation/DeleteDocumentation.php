<?php

declare(strict_types=1);

namespace App\Actions\Documentation;

use App\Models\Documentation;

final class DeleteDocumentation
{
    public function handle(Documentation $documentation): void
    {
        $documentation->delete();
    }
}
