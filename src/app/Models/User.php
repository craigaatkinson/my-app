<?php
namespace App\Models;

use PDO;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;

class User {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection("mysql");
    }

    /**
     * Find a user by email address
     * @param string $email
     * @return array|null
     * @throws AppException
     */
    public function findByEmail(string $email): ?array {
        try {
            $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in User@findByEmail', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to find user by email');
        }
    }

    /**
     * Find a user by ID
     * @param int $id
     * @return array|null
     * @throws AppException
     */
    public function findById(int $id): ?array {
        try {
            $sql = "SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in User@findById', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to find user by ID');
        }
    }

    /**
     * Create a new user
     * @param array $data Must contain: name, email, password (already hashed), role
     * @return int The new user's ID
     * @throws AppException
     */
    public function create(array $data): int {
        try {
            $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'] ?? 'user',
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw AppException::validationError('A user with this email already exists', [
                    'email' => $data['email']
                ]);
            }
            LogMiddleware::logError('Error in User@create', [
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to create user');
        }
    }
}
