<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;

final readonly class ImportActiveDatabase
{
    public function __construct(private GetDatabaseDumpInput $getDatabaseDumpInput) {}

    public function handle(string $sqlPath): void
    {
        $connection = DB::connection();
        throw_if($connection->getDriverName() !== 'pgsql' || $connection->getConfig('search_path') !== 'public', RuntimeException::class, 'Active restore requires PostgreSQL with the public schema.');
        $stream = fopen($sqlPath, 'rb');
        throw_if($stream === false, RuntimeException::class, 'Cannot read database dump.');
        try {
            $createsPublicSchema = false;
            while (($line = fgets($stream)) !== false) {
                if (preg_match('/^CREATE SCHEMA "?public"?;/D', mb_rtrim($line)) === 1) {
                    $createsPublicSchema = true;
                    break;
                }
            }
            $reset = 'DROP SCHEMA public CASCADE;'.($createsPublicSchema ? '' : ' CREATE SCHEMA public;');
            $cleanup = <<<'SQL'
DO $$ DECLARE name text; BEGIN
    FOREACH name IN ARRAY ARRAY['jobs', 'failed_jobs', 'job_batches', 'sessions', 'cache', 'cache_locks'] LOOP
        IF to_regclass('public.' || name) IS NOT NULL THEN
            EXECUTE format('TRUNCATE TABLE public.%I', name);
        END IF;
    END LOOP;
END $$;
SQL;
            rewind($stream);
            $result = Process::timeout(600)->input($this->getDatabaseDumpInput->handle($sqlPath, version_compare((string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), '17', '>=')))->env([
                'PGPASSWORD' => (string) $connection->getConfig('password'),
                'PGSSLMODE' => (string) $connection->getConfig('sslmode'),
            ])->run([
                config('jalara.operations.psql_binary'), '--no-psqlrc', '--no-password',
                '--host='.(string) $connection->getConfig('host'),
                '--port='.(string) $connection->getConfig('port'),
                '--username='.(string) $connection->getConfig('username'),
                '--dbname='.$connection->getDatabaseName(),
                '--single-transaction', '--set=ON_ERROR_STOP=1',
                '--command='.$reset, '--file=-', '--command='.$cleanup,
            ]);
            throw_unless($result->successful(), RuntimeException::class, 'Database restore failed; the active database transaction was rolled back.');
        } finally {
            fclose($stream);
        }
    }
}
