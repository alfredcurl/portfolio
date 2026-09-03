<?php
// =============================================
// Database Configuration — MySQL
// =============================================
// define('DB_HOST',    'sql313.infinityfree.com'); 
// define('DB_NAME',    'if0_41440545_alfred'); 
// define('DB_USER',    'if0_41440545');         
// define('DB_PASS',    'YSW6pHennPea0E');       
// define('DB_CHARSET', 'utf8mb4');
// define('DB_PORT',    3306);
// define('DB_HOST',    'mysql'); // Docker service hostname
define('DB_HOST',    'localhost');
define('DB_NAME',    'alfred'); 
define('DB_USER',    'root');         
define('DB_PASS',    '');       
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    3306);

// Upload paths
define('UPLOAD_DIR',  ROOT_PATH . '/uploads/');
define('UPLOAD_URL',  '/uploads/');

class DB
{
    private static $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$pdo;
    }

    /** Run a query, return PDOStatement */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch single row */
    public static function row(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Fetch all rows */
    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }
}
