<?php

class Database {
    private static $instance = null;
    /** @var PDO */
    private $pdo;
    private $driver = 'sqlite';

    private function __construct() {
        $settings = file_exists(__DIR__ . '/../config/settings.php') ? require __DIR__ . '/../config/settings.php' : [];

        // Check environment or config
        $this->driver = strtolower(getenv('DB_DRIVER') ?: ($settings['db_driver'] ?? 'sqlite'));

        try {
            if ($this->driver === 'pgsql' || $this->driver === 'postgres' || $this->driver === 'postgresql') {
                $this->driver = 'pgsql';
                $host = getenv('DB_HOST') ?: ($settings['db_host'] ?? '127.0.0.1');
                $port = getenv('DB_PORT') ?: ($settings['db_port'] ?? '5432');
                $dbname = getenv('DB_NAME') ?: ($settings['db_name'] ?? 'rteg_db');
                $user = getenv('DB_USER') ?: ($settings['db_user'] ?? 'postgres');
                $pass = getenv('DB_PASS') ?: ($settings['db_pass'] ?? '');
                $sslmode = getenv('DB_SSLMODE') ?: ($settings['db_sslmode'] ?? 'prefer');

                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } elseif ($this->driver === 'mysql' || $this->driver === 'mariadb') {
                $this->driver = 'mysql';
                $host = getenv('DB_HOST') ?: ($settings['db_host'] ?? '127.0.0.1');
                $port = getenv('DB_PORT') ?: ($settings['db_port'] ?? '3306');
                $dbname = getenv('DB_NAME') ?: ($settings['db_name'] ?? 'rteg_db');
                $user = getenv('DB_USER') ?: ($settings['db_user'] ?? 'root');
                $pass = getenv('DB_PASS') ?: ($settings['db_pass'] ?? '');
                $charset = $settings['db_charset'] ?? 'utf8mb4';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } else {
                $this->driver = 'sqlite';
                $dbPath = getenv('DB_PATH') ?: ($settings['db_path'] ?? (__DIR__ . '/../database/events.db'));
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $this->pdo = new PDO("sqlite:" . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                // Optimize SQLite journal and foreign keys
                $this->pdo->exec("PRAGMA journal_mode = WAL;");
                $this->pdo->exec("PRAGMA synchronous = NORMAL;");
            }
        } catch (PDOException $e) {
            die("Database Connection Error (" . htmlspecialchars($this->driver) . "): " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function getDriver(): string {
        return $this->driver;
    }

    public function isPostgres(): bool {
        return $this->driver === 'pgsql';
    }

    public function isMysql(): bool {
        return $this->driver === 'mysql';
    }

    public function isSqlite(): bool {
        return $this->driver === 'sqlite';
    }
}
