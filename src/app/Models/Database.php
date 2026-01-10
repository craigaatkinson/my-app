<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;

// Database class is used to create a new PDO instance and connect to the database
// It is a singleton class, so it can only be instantiated once
// It is used to get a database connection and execute SQL queries


class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;
    private array $connections = [];
    private bool $inTransaction = false;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->config = $this->loadConfig();
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Get the singleton instance (needed for the LoadData class)
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load the database configurations ($this->loadConfig) in constructor
     * @return array
     * @throws AppException
     */
    private function loadConfig(): array {
        try {
            $configFile = __DIR__ . '/../Config/database.php';
            if (!file_exists($configFile)) {
                throw AppException::configError('Database configuration file not found');
            }

            $config = require $configFile;
            if (!is_array($config)) {
                throw AppException::configError('Invalid database configuration format');
            }

            return $config;
        } catch (AppException $e) {
            throw $e;
        } catch (\Exception $e) {
            LogMiddleware::logError('Error loading database config', [
                'error' => $e->getMessage()
            ]);
            throw AppException::configError('Failed to load database configuration');
        }
    }

    /**
     * Get a database connection
     * @param string $name Connection name (default: 'default')
     * @return PDO
     * @throws AppException
     */
    //the 'default' parameter is defined in the Config/database.php file
    //Needed for the LoadData class
    public function getConnection(string $name = 'db'): PDO {
        try {
            if (!isset($this->connections[$name])) {
                $this->connections[$name] = $this->createConnection($name);
            }

            // Test the connection
            if (!$this->connections[$name]->query('SELECT 1')) {
                $this->connections[$name] = $this->createConnection($name);
            }

            return $this->connections[$name];
        } catch (PDOException $e) {
            LogMiddleware::logError('Database connection error', [
                'connection' => $name,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Database connection failed');
        }
    }

    /**
     * Create a new database connection
     * @param string $name Connection name
     * @return PDO
     * @throws AppException
     */
    private function createConnection(string $name): PDO {
        try {
            if (!isset($this->config[$name])) {
                throw AppException::configError("Database configuration for '$name' not found");
            }

            $config = $this->config[$name];
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'] ?? 3307,
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => $config['persistent'] ?? false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . ($config['charset'] ?? 'utf8mb4')
            ];

            return new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            LogMiddleware::logError('Failed to create database connection', [
                'connection' => $name,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to create database connection');
        }
    }

    /**
     * Begin a transaction
     * @param string $name Connection name
     * @return bool
     * @throws AppException
     */
    public function beginTransaction(string $name = 'default'): bool {
        try {
            if ($this->inTransaction) {
                throw AppException::serverError('Transaction already in progress');
            }

            $this->inTransaction = $this->getConnection($name)->beginTransaction();
            return $this->inTransaction;
        } catch (PDOException $e) {
            LogMiddleware::logError('Failed to begin transaction', [
                'connection' => $name,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to begin transaction');
        }
    }

    /**
     * Commit a transaction
     * @param string $name Connection name
     * @return bool
     * @throws AppException
     */
    public function commit(string $name = 'default'): bool {
        try {
            if (!$this->inTransaction) {
                throw AppException::serverError('No transaction in progress');
            }

            $result = $this->getConnection($name)->commit();
            $this->inTransaction = false;
            return $result;
        } catch (PDOException $e) {
            LogMiddleware::logError('Failed to commit transaction', [
                'connection' => $name,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to commit transaction');
        }
    }

    /**
     * Rollback a transaction
     * @param string $name Connection name
     * @return bool
     * @throws AppException
     */
    public function rollBack(string $name = 'default'): bool {
        try {
            if (!$this->inTransaction) {
                throw AppException::serverError('No transaction in progress');
            }

            $result = $this->getConnection($name)->rollBack();
            $this->inTransaction = false;
            return $result;
        } catch (PDOException $e) {
            LogMiddleware::logError('Failed to rollback transaction', [
                'connection' => $name,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to rollback transaction');
        }
    }

    /**
     * Close a database connection
     * @param string $name Connection name
     */
    public function closeConnection(string $name = 'default'): void {
        if (isset($this->connections[$name])) {
            $this->connections[$name] = null;
            unset($this->connections[$name]);
        }
    }

    /**
     * Close all database connections
     */
    public function closeAllConnections(): void {
        foreach ($this->connections as $name => $connection) {
            $this->closeConnection($name);
        }
    }

    /**
     * Get the current transaction status
     * @return bool
     */
    public function inTransaction(): bool {
        return $this->inTransaction;
    }

    /**
     * Execute a query and return the statement
     * @param string $sql
     * @param array $params
     * @param string $name Connection name
     * @return \PDOStatement
     * @throws AppException
     */
    public function query(string $sql, array $params = [], string $name = 'default'): \PDOStatement {
        try {
            $stmt = $this->getConnection($name)->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            LogMiddleware::logError('Query execution failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Query execution failed');
        }
    }

    /**
     * Get the last inserted ID
     * @param string $name Connection name
     * @return string
     */
    public function lastInsertId(string $name = 'default'): string {
        return $this->getConnection($name)->lastInsertId();
    }
} 