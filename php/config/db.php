<?php
/**
 * StudentHub Portal - Database Connection (Practical 8)
 * PDO Connectivity with Prepared Statements and Error Handling
 */

class Database {
    private static $host = '127.0.0.1';
    private static $db_name = 'studenthub_db';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';
    private static $conn = null;

    /**
     * Get singleton PDO connection
     * @return PDO|null
     */
    public static function getConnection() {
        if (self::$conn === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // True prepared statements (Practical 8 & 9)
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::$charset
            ];

            try {
                self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Log exception error securely
                error_log("Database connection error: " . $e->getMessage());
                return null;
            }
        }
        return self::$conn;
    }

    /**
     * Helper to log administrative and user actions into audit_logs (Practical 14)
     */
    public static function logAudit($userId, $action, $details) {
        $pdo = self::getConnection();
        if (!$pdo) return false;

        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
            return $stmt->execute([$userId, $action, $details, $ip, $ua]);
        } catch (PDOException $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
}
