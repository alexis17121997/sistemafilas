<?php

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                DB_HOST, DB_PORT, DB_NAME
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$instance;
    }

    // Convenience: run a query and return all rows
    public static function query(string $sql, array $params = []): array {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Convenience: run a query and return one row
    public static function queryOne(string $sql, array $params = []): ?array {
        $rows = self::query($sql, $params);
        return $rows[0] ?? null;
    }

    // Convenience: execute (INSERT / UPDATE / DELETE) and return last insert id
    public static function execute(string $sql, array $params = []): string {
        $db   = self::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $db->lastInsertId();
    }
}
