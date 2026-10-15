<?php
/**
 * StudentHub Portal - MySQLi Database Connection (Practical 11)
 * Standard MySQLi object-oriented connectivity with charset & error handling.
 */

class MySQLiDatabase {
    private static $host = '127.0.0.1';
    private static $username = 'root';
    private static $password = '';
    private static $database = 'studenthub_db';
    private static $port = 3306;
    private static $connection = null;

    /**
     * Get singleton MySQLi connection
     * @return mysqli|null
     */
    public static function getConnection() {
        if (self::$connection === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$connection = new mysqli(self::$host, self::$username, self::$password, self::$database, self::$port);
                self::$connection->set_charset("utf8mb4");
            } catch (mysqli_sql_exception $e) {
                error_log("MySQLi Connection failed: " . $e->getMessage());
                self::$connection = null;
            }
        }
        return self::$connection;
    }
}
