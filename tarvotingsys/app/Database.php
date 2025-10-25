<?php
final class Database {
    private static $host = "127.0.0.1";
    private static $dbName = "tarumtvs";
    private static $username = "root";
    private static $password = "";
    private static $connection = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=utf8",
                    self::$username,
                    self::$password
                );
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    public static function closeConnection()
    {
        self::$connection = null;
    }
}