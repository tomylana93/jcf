<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Generator;
use Illuminate\Support\Facades\File;

final class GetDatabaseDumpInput
{
    /** @return Generator<int, string, void, void> */
    public function handle(string $sqlPath, bool $supportsTransactionTimeout): Generator
    {
        $header = true;
        foreach (File::lines($sqlPath) as $line) {
            if (str_starts_with($line, '-- Name:') || preg_match('/^(CREATE|ALTER|COPY|INSERT|DO|BEGIN)\b/', $line) === 1) {
                $header = false;
            }
            if ($header && ! $supportsTransactionTimeout && $line === 'SET transaction_timeout = 0;') {
                continue;
            }
            yield $line."\n";
        }
    }
}
