<?php
namespace App\Models;
use PDO;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;

// LoadData class is used to get all the data from the ldata table
// SQL queries are executed here, used to get the data from the database

class LoadCustomers {
    private PDO $db;

    /**
     * LoadData constructor.
     * Initializes the database connection using the Database singleton.
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all customers
     * @return array
     * @throws AppException
     */
    public function getAllCustomers(): array {
        try {
            $sql = "SELECT customer, name, location, miles, rate FROM customers";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in LoadCustomers@getAllCustomers', [
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to fetch customers');
        }
    }

    /**
     * Get customer by ID
     * @param string $customerId
     * @return array|null
     * @throws AppException
     */
    public function getCustomerById(string $customerId): ?array {
        try {
            $sql = "SELECT comp_id, customer, name, location, miles, rate FROM customers WHERE customer = :customerId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['customerId' => $customerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in LoadCustomers@getCustomerById', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to fetch customer by ID');
        }
    }

    /**
     * Get customers by location
     * @param string $location
     * @return array
     * @throws AppException
     */
    public function getCustomersByLocation(string $location): array {
        try {
            $sql = "SELECT customer, name, location, miles, rate FROM customers WHERE location = :location";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['location' => $location]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in LoadCustomers@getCustomersByLocation', [
                'location' => $location,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to fetch customers by location');
        }
    }

    /**
     * Search customers by name
     * @param string $searchTerm
     * @return array
     * @throws AppException
     */
    public function searchCustomersByName(string $searchTerm): array {
        try {
            $sql = "SELECT customer, name, location, miles, rate FROM customers WHERE name LIKE :searchTerm";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['searchTerm' => "%{$searchTerm}%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in LoadCustomers@searchCustomersByName', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to search customers by name');
        }
    }
} 