<?php
declare(strict_types=1);

namespace App\Database;

class MigrationRunner
{
    private \PDO $pdo;
    private string $migrationsPath;

    public function __construct()
    {
        $this->pdo = Connection::getInstance()->getPdo();
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations';
    }

    public function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                version INT UNSIGNED PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT version FROM schema_migrations ORDER BY version ASC");
        return array_column($stmt->fetchAll(), 'version');
    }

    public function getPendingMigrations(): array
    {
        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        $pending = [];

        foreach ($files as $version => $file) {
            if (!in_array($version, $applied)) {
                $pending[$version] = $file;
            }
        }

        return $pending;
    }

    public function run(): array
    {
        $this->ensureMigrationsTable();
        $pending = $this->getPendingMigrations();
        $results = [];

        foreach ($pending as $version => $file) {
            $results[] = $this->runMigration($version, $file);
        }

        return $results;
    }

    public function runSingle(int $version): ?array
    {
        $this->ensureMigrationsTable();
        $files = $this->getMigrationFiles();

        if (!isset($files[$version])) {
            return null;
        }

        return $this->runMigration($version, $files[$version]);
    }

    public function rollback(int $steps = 1): array
    {
        $applied = $this->getAppliedMigrations();
        $applied = array_reverse($applied);
        $results = [];

        for ($i = 0; $i < $steps && $i < count($applied); $i++) {
            $version = $applied[$i];
            $rollbackFile = dirname($this->migrationsPath) . "/rollback/{$version}_rollback.sql";

            if (is_file($rollbackFile)) {
                $sql = file_get_contents($rollbackFile);
                $this->pdo->exec($sql);
                $this->pdo->prepare("DELETE FROM schema_migrations WHERE version = ?")->execute([$version]);
                $results[] = ['version' => $version, 'status' => 'rolled_back'];
            }
        }

        return $results;
    }

    private function runMigration(int $version, string $file): array
    {
        $fullPath = $this->migrationsPath . '/' . $file;

        try {
            $this->pdo->beginTransaction();

            if (str_ends_with($file, '.sql')) {
                $sql = file_get_contents($fullPath);
                $this->pdo->exec($sql);
            } elseif (str_ends_with($file, '.php')) {
                require $fullPath;
            }

            $this->pdo->prepare(
                "INSERT INTO schema_migrations (version, name) VALUES (?, ?)"
            )->execute([$version, $file]);

            $this->pdo->commit();

            return ['version' => $version, 'file' => $file, 'status' => 'applied'];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['version' => $version, 'file' => $file, 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function getMigrationFiles(): array
    {
        $files = [];

        if (!is_dir($this->migrationsPath)) {
            return $files;
        }

        foreach (scandir($this->migrationsPath) as $file) {
            if ($file === '.' || $file === '..') continue;
            if (!preg_match('/^(\d+)[_\.](.+)\.(sql|php)$/', $file, $matches)) continue;

            $version = (int) $matches[1];
            $files[$version] = $file;
        }

        ksort($files);
        return $files;
    }

    public function getStatus(): array
    {
        $this->ensureMigrationsTable();
        $applied = $this->getAppliedMigrations();
        $all = $this->getMigrationFiles();

        return [
            'total'    => count($all),
            'applied'  => count($applied),
            'pending'  => count($all) - count($applied),
            'migrations' => array_map(function ($version, $file) use ($applied) {
                return [
                    'version'  => $version,
                    'file'     => $file,
                    'applied'  => in_array($version, $applied),
                ];
            }, array_keys($all), $all),
        ];
    }
}
