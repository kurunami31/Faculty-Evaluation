<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

$config = require __DIR__ . "/../config/database.php";
$driver = $config["driver"];

/**
 * Split a multi-statement SQL string into individual statements.
 * Safe for our migrations: no semicolons inside string literals.
 */
function splitStatements(string $sql): array
{
    $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    return array_values(array_filter(array_map('trim', $parts), fn (string $s) => $s !== '' && !str_starts_with($s, '--')));
}

function connect(array $config, ?string $database): PDO
{
    if ($config["driver"] === "sqlite") {
        $dir = dirname($config["database"]);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return new PDO("sqlite:" . $config["database"], null, null, $config["options"]);
    }

    $dsn = $database !== null
        ? sprintf("mysql:host=%s;port=%d;dbname=%s;charset=%s", $config["host"], $config["port"], $database, $config["charset"])
        : sprintf("mysql:host=%s;port=%d;charset=%s", $config["host"], $config["port"], $config["charset"]);

    return new PDO($dsn, $config["username"], $config["password"], $config["options"]);
}

echo "Running migrations (driver: $driver)...\n";

try {
    if ($driver === "mysql") {
        // Create the database if it does not exist yet
        $server = connect($config, null);
        $dbName = $config["database"];
        $server->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  Database `$dbName` ensured.\n";
        $pdo = connect($config, $dbName);
    } else {
        $pdo = connect($config, null);
    }

    // Migrations tracking table
    if ($driver === "mysql") {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    $applied = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    $migrationFiles = glob(__DIR__ . "/migrations/*.php");
    sort($migrationFiles);

    foreach ($migrationFiles as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            echo "Skipping $name (already applied)\n";
            continue;
        }

        echo "Applying $name...\n";
        $migration = require $file;

        $up = $migration["up"] ?? null;
        if (is_array($up)) {
            $up = $up[$driver] ?? reset($up);
        }

        // Note: no transaction wrapper — MySQL DDL causes implicit commits.
        // Statements are idempotent (IF NOT EXISTS / INSERT IGNORE), so re-runs are safe.
        foreach (splitStatements((string)$up) as $statement) {
            $pdo->exec($statement);
        }
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:m)");
        $stmt->execute(["m" => $name]);
        echo "  OK\n";
    }

    echo "All migrations applied successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
