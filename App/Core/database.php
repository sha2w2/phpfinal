<?php
// File: Database.php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private $connection;
    private static $instance = null;

    /**
     * Database constructor (private to enforce singleton pattern)
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Get singleton instance of Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect()
    {
        $config = [
            'host' => 'localhost',
            'dbname' => 'password_manager',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ];

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the PDO connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Execute a query with parameters
     */
    public function query(string $sql, array $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {}
}