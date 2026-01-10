<?php
namespace App\Models;
use PDO;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;

// LoadData class is used to get all the data from the ldata table
// SQL queries are executed here, used to get the data from the database 

class LoadData {
    private PDO $pdo;

    /**
     * LoadData constructor.
     * Initializes the database connection using the Database singleton.
     */
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection("mysql");
    }

    /**
     * Execution of SQL query to getAll data from the ldata table in the database
     * @return array
     * @throws AppException
     */
    public function getAll(): array {
        try {
            $sql = "SELECT * FROM ldata ORDER BY date_arv DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in LoadData@getAll', [
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to fetch load data');
        }
    }

    /**
     * Get active data from the ldata table
     * @return array
     * @throws AppException
     */
    public function getActive(): array {
        try {
            $sql = "SELECT * FROM ldata WHERE status2 IN ('active', 'manual', 'approved') ORDER BY date_arv DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            LogMiddleware::logError('Error in LoadData@getActive', [
                'error' => $e->getMessage()
            ]);
            throw AppException::serverError('Failed to fetch active loads');
        }
    }

    // /**
    //  * Get loads by invoice number
    //  * @param string $invoiceNumber
    //  * @return array
    //  * @throws AppException
    //  */
    // public function getByInvoice(string $invoiceNumber): array {
    //     try {
    //         $sql = "SELECT * FROM ldata WHERE invoice = :invoice ORDER BY date_arv DESC";
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute(['invoice' => $invoiceNumber]);
    //         $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
    //         if (empty($result)) {
    //             throw AppException::notFound('No loads found for invoice', [
    //                 'invoice' => $invoiceNumber
    //             ]);
    //         }

    //         return $result;
    //     } catch (AppException $e) {
    //         throw $e;
    //     } catch (\PDOException $e) {
    //         LogMiddleware::logError('Error in LoadData@getByInvoice', [
    //             'invoice' => $invoiceNumber,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to fetch loads by invoice');
    //     }
    // }

    // /**
    //  * Get loads by invoice number with additional filters
    //  * @param string $invoiceNumber
    //  * @param array $filters
    //  * @return array
    //  * @throws AppException
    //  */
    // public function getByInvoiceWithFilters(string $invoiceNumber, array $filters = []): array {
    //     try {
    //         $sql = "SELECT * FROM ldata WHERE invoice = :invoice";
    //         $params = ['invoice' => $invoiceNumber];

    //         // Add status filter if provided
    //         if (!empty($filters['status'])) {
    //             $sql .= " AND status2 = :status";
    //             $params['status'] = $filters['status'];
    //         }

    //         // Add date range filter if provided
    //         if (!empty($filters['start_date'])) {
    //             $sql .= " AND date_arv >= :start_date";
    //             $params['start_date'] = $filters['start_date'];
    //         }
    //         if (!empty($filters['end_date'])) {
    //             $sql .= " AND date_arv <= :end_date";
    //             $params['end_date'] = $filters['end_date'];
    //         }

    //         $sql .= " ORDER BY date_arv DESC";

    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute($params);
    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } catch (\PDOException $e) {
    //         LogMiddleware::logError('Error in LoadData@getByInvoiceWithFilters', [
    //             'invoice' => $invoiceNumber,
    //             'filters' => $filters,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to fetch filtered loads');
    //     }
    // }

    // /**
    //  * Update invoice number for multiple loads
    //  * @param array $loadIds
    //  * @param string $invoiceNumber
    //  * @return bool
    //  * @throws AppException
    //  */
    // public function updateLoadsInvoice(array $loadIds, string $invoiceNumber): bool {
    //     try {
    //         $this->pdo->beginTransaction();

    //         $placeholders = str_repeat('?,', count($loadIds) - 1) . '?';
    //         $sql = "UPDATE ldata SET invoice = ? WHERE id IN ($placeholders)";
            
    //         $params = array_merge([$invoiceNumber], $loadIds);
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute($params);

    //         $this->pdo->commit();
    //         return true;
    //     } catch (\PDOException $e) {
    //         $this->pdo->rollBack();
    //         LogMiddleware::logError('Error in LoadData@updateLoadsInvoice', [
    //             'load_ids' => $loadIds,
    //             'invoice' => $invoiceNumber,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to update loads invoice');
    //     }
    // }

    // /**
    //  * Remove invoice number from loads
    //  * @param array $loadIds
    //  * @return bool
    //  * @throws AppException
    //  */
    // public function removeLoadsInvoice(array $loadIds): bool {
    //     try {
    //         $this->pdo->beginTransaction();

    //         $placeholders = str_repeat('?,', count($loadIds) - 1) . '?';
    //         $sql = "UPDATE ldata SET invoice = NULL WHERE id IN ($placeholders)";
            
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute($loadIds);

    //         $this->pdo->commit();
    //         return true;
    //     } catch (\PDOException $e) {
    //         $this->pdo->rollBack();
    //         LogMiddleware::logError('Error in LoadData@removeLoadsInvoice', [
    //             'load_ids' => $loadIds,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to remove invoice from loads');
    //     }
    // }

    // /**
    //  * Get unique invoice numbers
    //  * @return array
    //  * @throws AppException
    //  */
    // public function getUniqueInvoices(): array {
    //     try {
    //         $sql = "SELECT DISTINCT invoice FROM ldata WHERE invoice IS NOT NULL ORDER BY invoice";
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute();
    //         return $stmt->fetchAll(PDO::FETCH_COLUMN);
    //     } catch (\PDOException $e) {
    //         LogMiddleware::logError('Error in LoadData@getUniqueInvoices', [
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to fetch unique invoices');
    //     }
    // }

    // /**
    //  * Get loads without invoice
    //  * @return array
    //  * @throws AppException
    //  */
    // public function getLoadsWithoutInvoice(): array {
    //     try {
    //         $sql = "SELECT * FROM ldata WHERE invoice IS NULL ORDER BY date_arv DESC";
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute();
    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } catch (\PDOException $e) {
    //         LogMiddleware::logError('Error in LoadData@getLoadsWithoutInvoice', [
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to fetch loads without invoice');
    //     }
    // }

    // /**
    //  * Update invoice number for a single load
    //  * @param int $loadId
    //  * @param string|null $invoiceNumber
    //  * @return bool
    //  * @throws AppException
    //  */
    // public function updateInvoice(int $loadId, ?string $invoiceNumber): bool {
    //     try {
    //         $this->pdo->beginTransaction();

    //         // Validate load exists
    //         $checkSql = "SELECT id FROM ldata WHERE id = :id";
    //         $checkStmt = $this->pdo->prepare($checkSql);
    //         $checkStmt->execute(['id' => $loadId]);
            
    //         if (!$checkStmt->fetch()) {
    //             throw AppException::notFound('Load not found', [
    //                 'load_id' => $loadId
    //             ]);
    //         }

    //         // Update invoice
    //         $sql = "UPDATE ldata SET invoice = :invoice WHERE id = :id";
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute([
    //             'id' => $loadId,
    //             'invoice' => $invoiceNumber
    //         ]);

    //         $this->pdo->commit();
    //         return true;
    //     } catch (AppException $e) {
    //         $this->pdo->rollBack();
    //         throw $e;
    //     } catch (\PDOException $e) {
    //         $this->pdo->rollBack();
    //         LogMiddleware::logError('Error in LoadData@updateInvoice', [
    //             'load_id' => $loadId,
    //             'invoice' => $invoiceNumber,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to update invoice');
    //     }
    // }

    // /**
    //  * Delete records from ldata table by invoice number
    //  * @param string $invoiceNumber
    //  * @return int Number of records deleted
    //  * @throws AppException
    //  */
    // public function deleteInvoice(string $invoiceNumber): int {
    //     try {
    //         $this->pdo->beginTransaction();

    //         // First check if any records exist with this invoice
    //         $checkSql = "SELECT COUNT(*) FROM ldata WHERE invoice = :invoice";
    //         $checkStmt = $this->pdo->prepare($checkSql);
    //         $checkStmt->execute(['invoice' => $invoiceNumber]);
    //         $count = $checkStmt->fetchColumn();

    //         if ($count === 0) {
    //             throw AppException::notFound('No records found with invoice number', [
    //                 'invoice' => $invoiceNumber
    //             ]);
    //         }

    //         // Delete the records
    //         $sql = "DELETE FROM ldata WHERE invoice = :invoice";
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute(['invoice' => $invoiceNumber]);

    //         $this->pdo->commit();
    //         return $stmt->rowCount();
    //     } catch (AppException $e) {
    //         $this->pdo->rollBack();
    //         throw $e;
    //     } catch (\PDOException $e) {
    //         $this->pdo->rollBack();
    //         LogMiddleware::logError('Error in LoadData@deleteInvoice', [
    //             'invoice' => $invoiceNumber,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw AppException::serverError('Failed to delete invoice records');
    //     }
    // }
} 