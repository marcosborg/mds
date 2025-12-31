<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DatabaseCopyCommand extends Command
{
    protected $signature = 'db:copy
                            {--source=production : Source database (production|sandbox)}
                            {--target=sandbox : Target database (production|sandbox)}
                            {--force : Skip confirmation when target is production}';

    protected $description = 'Copy a database between production and sandbox using .env settings.';

    public function handle()
    {
        $sourceName = $this->normalizeRole($this->option('source'));
        $targetName = $this->normalizeRole($this->option('target'));

        if (!$sourceName || !$targetName) {
            $this->error('Source/target must be production or sandbox.');
            return 1;
        }

        if ($sourceName === $targetName) {
            $this->error('Source and target must be different.');
            return 1;
        }

        if ($targetName === 'production' && !$this->option('force')) {
            if (!$this->confirm('Target is production. This will REPLACE data. Continue?')) {
                $this->info('Aborted.');
                return 1;
            }
        }

        $sourceConfig = $this->buildConnectionConfig($sourceName);
        $targetConfig = $this->buildConnectionConfig($targetName);

        if (!$sourceConfig['database'] || !$targetConfig['database']) {
            $this->error('Missing DB database name for source or target. Check .env.');
            return 1;
        }

        if ($sourceConfig['database'] === $targetConfig['database']) {
            $this->error('Source and target databases cannot be the same.');
            return 1;
        }

        $sourceConnection = "copy_source_{$sourceName}";
        $targetConnection = "copy_target_{$targetName}";

        config([
            "database.connections.{$sourceConnection}" => $sourceConfig,
            "database.connections.{$targetConnection}" => $targetConfig,
        ]);

        DB::purge($sourceConnection);
        DB::purge($targetConnection);

        $this->ensureDatabaseExists($targetName, $targetConfig);

        $source = DB::connection($sourceConnection);
        $target = DB::connection($targetConnection);

        $this->info("Copying {$sourceConfig['database']} -> {$targetConfig['database']} ...");

        $tableKey = 'Tables_in_' . $source->getDatabaseName();
        $tables = collect($source->select('SHOW FULL TABLES'))
            ->map(function ($row) use ($tableKey) {
                $rowArray = (array) $row;
                $values = array_values($rowArray);

                return [
                    'name' => $rowArray[$tableKey] ?? ($values[0] ?? null),
                    'type' => $rowArray['Table_type'] ?? ($values[1] ?? null),
                ];
            })
            ->filter(fn($row) => !empty($row['name']))
            ->values();

        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $tableName = $table['name'];
            $tableType = strtoupper((string) $table['type']);

            try {
                $create = (array) $source->selectOne("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $create['Create Table'] ?? $create['Create View'] ?? null;
                if (!$createSql) {
                    throw new \RuntimeException("Unable to get CREATE statement for {$tableName}");
                }

                if ($tableType === 'VIEW') {
                    $target->statement("DROP VIEW IF EXISTS `{$tableName}`");
                } else {
                    $target->statement("DROP TABLE IF EXISTS `{$tableName}`");
                }
                $target->statement($createSql);

                if ($tableType === 'VIEW') {
                    continue;
                }

                $orderColumn = $this->resolveOrderColumn($source, $tableName);
                if (!$orderColumn) {
                    throw new \RuntimeException("No columns found for {$tableName}");
                }

                $source->table($tableName)
                    ->orderBy($orderColumn)
                    ->chunk(500, function ($rows) use ($target, $tableName) {
                        $payload = $rows->map(fn($row) => (array) $row)->toArray();
                        if (!empty($payload)) {
                            $target->table($tableName)->insert($payload);
                        }
                    });
            } catch (\Throwable $e) {
                $this->warn("{$tableName}: {$e->getMessage()}");
            }
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Done.');

        return 0;
    }

    private function normalizeRole(?string $value): ?string
    {
        $value = strtolower((string) $value);
        if ($value === 'production' || $value === 'prod') {
            return 'production';
        }

        if ($value === 'sandbox' || $value === 'local') {
            return 'sandbox';
        }

        return null;
    }

    private function buildConnectionConfig(string $role): array
    {
        $baseConfig = config('database.connections.mysql');
        $prefix = $role === 'production' ? 'DB_PROD_' : 'DB_SANDBOX_';

        $database = env($prefix . 'DATABASE');
        if ($role === 'production' && !$database) {
            $database = env('DB_DATABASE');
        }

        $host = env($prefix . 'HOST', env('DB_HOST'));
        $port = env($prefix . 'PORT', env('DB_PORT'));
        $username = env($prefix . 'USERNAME', env('DB_USERNAME'));
        $password = env($prefix . 'PASSWORD', env('DB_PASSWORD'));

        return array_merge($baseConfig, [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => env($prefix . 'CHARSET', 'utf8mb4'),
            'collation' => env($prefix . 'COLLATION', 'utf8mb4_unicode_ci'),
        ]);
    }

    private function ensureDatabaseExists(string $role, array $config): void
    {
        $adminConnection = "copy_admin_{$role}";
        $adminConfig = $config;
        $adminConfig['database'] = 'information_schema';

        config([
            "database.connections.{$adminConnection}" => $adminConfig,
        ]);

        DB::purge($adminConnection);

        $database = $config['database'];
        DB::connection($adminConnection)
            ->statement("CREATE DATABASE IF NOT EXISTS `{$database}`");
    }

    private function resolveOrderColumn($connection, string $table): ?string
    {
        $hasId = !empty($connection->select("SHOW COLUMNS FROM `{$table}` LIKE 'id'"));
        if ($hasId) {
            return 'id';
        }

        $hasCreated = !empty($connection->select("SHOW COLUMNS FROM `{$table}` LIKE 'created_at'"));
        if ($hasCreated) {
            return 'created_at';
        }

        $columns = $connection->select("SHOW COLUMNS FROM `{$table}`");
        $first = Arr::first($columns);
        if ($first && isset($first->Field)) {
            return $first->Field;
        }

        return null;
    }
}
