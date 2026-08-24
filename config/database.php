<?php
/**
 * Database Connection via PDO Singleton
 * Uses prepared statements and proper error handling
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        $host = env('DB_HOST', '68.178.237.250');
        $dbName = env('DB_NAME', 'bestdealcrm');
        $username = env('DB_USER', 'sayali');
        $password = env('DB_PASS', 'sayali@1234');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Log error but dont die - let the class be defined
            // Connection will fail when actually used
            $this->pdo = null;
        }
    }

    // Prevent cloning
    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared query
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        if ($this->pdo === null) {
            throw new \RuntimeException('Database connection not available. Check DB credentials.');
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public function fetchOne(string $sql, array $params = [])
    {
        $result = $this->query($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Insert and return last insert ID
     */
    public function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Update rows and return affected count
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $values = array_merge($values, $whereParams);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->query($sql, $values);
        
        return $stmt->rowCount();
    }

    /**
     * Delete rows and return affected count
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Count rows
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }
}

// Convenience function
function db(): Database
{
    return Database::getInstance();
}

function pdo(): PDO
{
    return Database::getInstance()->getConnection();
}
