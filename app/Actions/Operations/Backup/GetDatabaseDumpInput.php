<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Generator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final readonly class GetDatabaseDumpInput
{
    /**
     * Stream a plain SQL dump to psql in restricted mode, so the dump cannot run psql meta-commands such as \! or \o.
     *
     * The input starts with \restrict and a random key that the dump cannot know; psql then rejects every
     * meta-command (also mid-line) for the rest of the input and the restore fails inside its transaction.
     * The dump's own \restrict and \unrestrict markers, written by current pg_dump versions, are dropped.
     *
     * @return Generator<int, string, void, void>
     */
    public function handle(string $sqlPath, bool $supportsTransactionTimeout): Generator
    {
        yield '\\restrict '.Str::random(48)."\n";

        $header = true;
        foreach (File::lines($sqlPath) as $line) {
            if ($header && preg_match('/^\\\\(?:un)?restrict [A-Za-z0-9]+$/D', $line) === 1) {
                continue;
            }
            if (str_starts_with($line, '-- Name:') || preg_match('/^(CREATE|ALTER|COPY|INSERT|DO|BEGIN)\b/', $line) === 1) {
                $header = false;
            }
            if ($header && ! $supportsTransactionTimeout && $line === 'SET transaction_timeout = 0;') {
                continue;
            }
            if (! $header && preg_match('/^\\\\unrestrict [A-Za-z0-9]+$/D', $line) === 1) {
                continue;
            }
            yield $line."\n";
        }
    }
}
