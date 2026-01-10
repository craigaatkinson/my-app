<?php
namespace App\Controllers;

use App\Models\LoadCustomers;
use App\Exceptions\AppException;

class CustomerController {
    private LoadCustomers $loadCustomers;

    public function __construct() {
        $this->loadCustomers = new LoadCustomers();
    }

    /**
     * Get all customers
     * @return array
     */
    public function getAllCustomers(): array {
        try {
            return [
                'status' => 'success',
                'data' => $this->loadCustomers->getAllCustomers()
            ];
        } catch (AppException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer by ID
     * @param string $customerId
     * @return array
     */
    public function getCustomerById(string $customerId): array {
        try {
            $customer = $this->loadCustomers->getCustomerById($customerId);
            if (!$customer) {
                return [
                    'status' => 'error',
                    'message' => 'Customer not found'
                ];
            }
            return [
                'status' => 'success',
                'data' => $customer
            ];
        } catch (AppException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customers by location
     * @param string $location
     * @return array
     */
    public function getCustomersByLocation(string $location): array {
        try {
            return [
                'status' => 'success',
                'data' => $this->loadCustomers->getCustomersByLocation($location)
            ];
        } catch (AppException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Search customers by name
     * @param string $searchTerm
     * @return array
     */
    public function searchCustomersByName(string $searchTerm): array {
        try {
            return [
                'status' => 'success',
                'data' => $this->loadCustomers->searchCustomersByName($searchTerm)
            ];
        } catch (AppException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Customers page view
     * @return void
     */
    public function customersPage(): void {
        require_once __DIR__ . '/../Views/customers/index.php';
    }
} 