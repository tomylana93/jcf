<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;
use Throwable;

final readonly class RestoreBackup
{
    public function __construct(private ExtractBackup $extractBackup, private GetDatabaseDumpInput $getDatabaseDumpInput) {}

    public function handle(string $filename, string $checksum, string $targetConnection): string
    {
        $sourceName = config('database.default');
        throw_if($targetConnection === $sourceName || ! config('database.connections.'.$targetConnection), RuntimeException::class, 'Restore requires a configured, isolated target connection.');
        $source = DB::connection($sourceName);
        $target = DB::connection($targetConnection);
        throw_if($source->getDriverName() !== 'pgsql' || $target->getDriverName() !== 'pgsql'
            || ! $target->getDatabaseName() || $source->getDatabaseName() === $target->getDatabaseName(), RuntimeException::class, 'Restore requires a separate PostgreSQL database.');
        throw_if($target->getSchemaBuilder()->getTables() !== [], RuntimeException::class, 'Restore target must be empty.');
        $directory = $this->extractBackup->handle($filename, $checksum);
        try {
            $sqlPath = $directory->path('db-dumps/postgresql-'.$sourceName.'.sql');
            throw_unless(is_file($sqlPath), RuntimeException::class, 'The source database dump is missing.');
            $stream = fopen($sqlPath, 'rb');
            throw_if($stream === false, RuntimeException::class, 'Cannot read database dump.');
            try {
                $result = Process::timeout(600)->env([
                    'PGPASSWORD' => (string) $target->getConfig('password'),
                    'PGSSLMODE' => (string) $target->getConfig('sslmode'),
                ])->input($this->getDatabaseDumpInput->handle($sqlPath, version_compare((string) $target->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), '17', '>=')))->run([
                    config('jalara.operations.psql_binary'), '--no-psqlrc', '--no-password',
                    '--host='.(string) $target->getConfig('host'),
                    '--port='.(string) $target->getConfig('port'),
                    '--username='.(string) $target->getConfig('username'),
                    '--dbname='.$target->getDatabaseName(),
                    '--single-transaction', '--set=ON_ERROR_STOP=1', '--file=-',
                ]);
            } finally {
                fclose($stream);
            }
            throw_unless($result->successful(), RuntimeException::class, 'Database restore failed; the target transaction was rolled back.');

            return $directory->path();
        } catch (Throwable $exception) {
            $directory->delete();
            throw $exception;
        }
    }
}
