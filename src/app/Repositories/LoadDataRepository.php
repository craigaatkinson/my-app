<?php

namespace App\Repositories;

use PDO;

/**
 * LoadDataRepository - Handles all database operations for load data
 */
class LoadDataRepository
{
    private PDO $db;

    public function __construct()
    {
        $dbPath = __DIR__ . '/../../../database/atransport.sqlite';
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Get all loads with optional filters
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM load_data WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (invoice LIKE :search OR company LIKE :search OR driver LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND date(load_dt) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND date(load_dt) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY load_dt DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $offset = $filters['offset'] ?? 0;
            $sql .= " OFFSET :offset";
        }

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if (!empty($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)($filters['offset'] ?? 0), PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get load by invoice number
     */
    public function getByInvoice(string $invoice): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM load_data WHERE invoice = :invoice LIMIT 1");
        $stmt->execute([':invoice' => $invoice]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get statistics summary
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_loads' => 0,
            'completed_loads' => 0,
            'pending_loads' => 0,
            'cancelled_loads' => 0,
            'total_revenue' => 0,
            'avg_revenue' => 0,
        ];

        // Total loads
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM load_data");
        $stats['total_loads'] = $stmt->fetch()['count'];

        // Completed loads
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM load_data WHERE status = 'completed'");
        $stats['completed_loads'] = $stmt->fetch()['count'];

        // Pending loads
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM load_data WHERE status IN ('pending', 'active')");
        $stats['pending_loads'] = $stmt->fetch()['count'];

        // Cancelled loads
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM load_data WHERE status = 'cancelled'");
        $stats['cancelled_loads'] = $stmt->fetch()['count'];

        // Revenue
        $stmt = $this->db->query("SELECT SUM(amount) as total, AVG(amount) as average FROM load_data WHERE status = 'completed'");
        $revenue = $stmt->fetch();
        $stats['total_revenue'] = round($revenue['total'] ?? 0, 2);
        $stats['avg_revenue'] = round($revenue['average'] ?? 0, 2);

        return $stats;
    }

    /**
     * Get recent loads
     */
    public function getRecent(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM load_data
            WHERE status != 'cancelled'
            ORDER BY load_dt DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get loads by status
     */
    public function getByStatus(string $status): array
    {
        $stmt = $this->db->prepare("SELECT * FROM load_data WHERE status = :status ORDER BY load_dt DESC");
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll();
    }

    /**
     * Get loads by date range
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM load_data
            WHERE date(load_dt) BETWEEN :start AND :end
            ORDER BY load_dt DESC
        ");
        $stmt->execute([
            ':start' => $startDate,
            ':end' => $endDate
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Search loads
     */
    public function search(string $query): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM load_data
            WHERE invoice LIKE :query
               OR company LIKE :query
               OR driver LIKE :query
               OR truck LIKE :query
            ORDER BY load_dt DESC
            LIMIT 50
        ");
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Get loads count
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM load_data WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (invoice LIKE :search OR company LIKE :search OR driver LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'];
    }

    /**
     * Get top customers by revenue
     */
    public function getTopCustomers(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                company,
                COUNT(*) as load_count,
                SUM(amount) as total_revenue,
                ROUND(AVG(amount), 2) as avg_revenue
            FROM load_data
            WHERE status = 'completed' AND company != ''
            GROUP BY company
            ORDER BY total_revenue DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get top drivers by load count
     */
    public function getTopDrivers(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                driver,
                COUNT(*) as load_count,
                SUM(amount) as total_revenue,
                ROUND(AVG(amount), 2) as avg_revenue
            FROM load_data
            WHERE status = 'completed' AND driver != '' AND driver != 'NA'
            GROUP BY driver
            ORDER BY load_count DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get monthly revenue data
     */
    public function getMonthlyRevenue(int $months = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT
                strftime('%Y-%m', load_dt) as month,
                COUNT(*) as load_count,
                SUM(amount) as revenue
            FROM load_data
            WHERE status = 'completed'
              AND load_dt >= date('now', '-{$months} months')
            GROUP BY month
            ORDER BY month DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
