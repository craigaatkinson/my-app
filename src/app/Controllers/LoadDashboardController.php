<?php

namespace App\Controllers;

use App\Services\DatastarService;
use App\Repositories\LoadDataRepository;

/**
 * LoadDashboardController - Handles load dashboard operations with Datastar SSE
 */
class LoadDashboardController
{
    private DatastarService $datastar;
    private LoadDataRepository $loadRepo;

    public function __construct()
    {
        $this->datastar = new DatastarService();
        $this->loadRepo = new LoadDataRepository();
    }

    /**
     * Main dashboard view
     */
    public function index(): void
    {
        require_once __DIR__ . '/../Views/loads/dashboard.php';
    }

    /**
     * Loads list page
     */
    public function loadsPage(): void
    {
        require_once __DIR__ . '/../Views/loads/index.php';
    }

    /**
     * New load form page
     */
    public function newLoadPage(): void
    {
        require_once __DIR__ . '/../Views/loads/new.php';
    }

    /**
     * Get dashboard statistics via SSE
     */
    public function getStatistics(): void
    {
        try {
            $stats = $this->loadRepo->getStatistics();

            $html = $this->renderStatisticsCards($stats);

            $this->datastar
                ->addFragment('#statistics-cards', $html)
                ->addSignals([
                    'totalLoads' => $stats['total_loads'],
                    'completedLoads' => $stats['completed_loads'],
                    'pendingLoads' => $stats['pending_loads'],
                    'totalRevenue' => $stats['total_revenue'],
                    'statsLoaded' => true
                ])
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#statistics-cards', 'Failed to load statistics: ' . $e->getMessage());
        }
    }

    /**
     * Load table data with filters
     */
    public function loadTable(): void
    {
        try {
            $filters = [
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? '',
                'limit' => (int)($_GET['limit'] ?? 20),
                'offset' => (int)($_GET['offset'] ?? 0)
            ];

            $loads = $this->loadRepo->getAll($filters);
            $total = $this->loadRepo->count($filters);

            $html = $this->renderLoadTable($loads);

            $this->datastar
                ->addFragment('#load-table', $html)
                ->addSignals([
                    'tableLoaded' => true,
                    'totalRecords' => $total,
                    'displayedRecords' => count($loads),
                    'currentPage' => floor($filters['offset'] / $filters['limit']) + 1,
                    'loading' => false
                ])
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#load-table', 'Failed to load table: ' . $e->getMessage());
        }
    }

    /**
     * Search loads
     */
    public function search(): void
    {
        try {
            $query = $_GET['q'] ?? '';

            if (empty($query)) {
                $this->loadTable();
                return;
            }

            $results = $this->loadRepo->search($query);
            $html = $this->renderLoadTable($results);

            $this->datastar
                ->addFragment('#load-table', $html)
                ->addSignals([
                    'searchResults' => count($results),
                    'loading' => false
                ])
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#load-table', 'Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Get load details
     */
    public function getLoadDetails(): void
    {
        try {
            $invoice = $_GET['invoice'] ?? '';

            if (empty($invoice)) {
                $this->datastar->sendError('#load-details', 'Invoice number required');
                return;
            }

            $load = $this->loadRepo->getByInvoice($invoice);

            if (!$load) {
                $this->datastar->sendError('#load-details', 'Load not found');
                return;
            }

            $html = $this->renderLoadDetails($load);

            $this->datastar
                ->addFragment('#load-details', $html)
                ->addSignals([
                    'selectedLoad' => $invoice,
                    'showDetails' => true
                ])
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#load-details', 'Failed to load details: ' . $e->getMessage());
        }
    }

    /**
     * Get recent loads
     */
    public function getRecentLoads(): void
    {
        try {
            $loads = $this->loadRepo->getRecent(10);
            $html = $this->renderRecentLoads($loads);

            $this->datastar
                ->addFragment('#recent-loads', $html)
                ->addSignal('recentLoaded', true)
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#recent-loads', 'Failed to load recent loads');
        }
    }

    /**
     * Get top customers
     */
    public function getTopCustomers(): void
    {
        try {
            $customers = $this->loadRepo->getTopCustomers(5);
            $html = $this->renderTopCustomers($customers);

            $this->datastar
                ->addFragment('#top-customers', $html)
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#top-customers', 'Failed to load top customers');
        }
    }

    /**
     * Filter by status
     */
    public function filterByStatus(): void
    {
        $this->loadTable();
    }

    /**
     * Live updates stream (for real-time dashboard)
     */
    public function liveStream(): void
    {
        $this->datastar->setSSEHeaders();

        // Send initial stats
        $stats = $this->loadRepo->getStatistics();
        $this->datastar
            ->addSignals([
                'totalLoads' => $stats['total_loads'],
                'completedLoads' => $stats['completed_loads'],
                'pendingLoads' => $stats['pending_loads'],
                'totalRevenue' => $stats['total_revenue'],
                'lastUpdate' => date('H:i:s')
            ])
            ->sendEvent();

        // Keep connection alive and send updates periodically
        $count = 0;
        while ($count < 60 && !connection_aborted()) {
            sleep(5);

            $stats = $this->loadRepo->getStatistics();
            $this->datastar
                ->addSignals([
                    'totalLoads' => $stats['total_loads'],
                    'lastUpdate' => date('H:i:s')
                ])
                ->sendEvent();

            $count++;
        }
    }

    // ========================================================================
    // RENDER METHODS
    // ========================================================================

    /**
     * Render statistics cards
     */
    private function renderStatisticsCards(array $stats): string
    {
        return '
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Loads -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium uppercase tracking-wide">Total Loads</p>
                        <p class="text-3xl font-bold mt-2">' . number_format($stats['total_loads']) . '</p>
                    </div>
                    <div class="bg-blue-400 bg-opacity-30 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Completed Loads -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium uppercase tracking-wide">Completed</p>
                        <p class="text-3xl font-bold mt-2">' . number_format($stats['completed_loads']) . '</p>
                    </div>
                    <div class="bg-green-400 bg-opacity-30 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Loads -->
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium uppercase tracking-wide">Pending</p>
                        <p class="text-3xl font-bold mt-2">' . number_format($stats['pending_loads']) . '</p>
                    </div>
                    <div class="bg-yellow-400 bg-opacity-30 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium uppercase tracking-wide">Total Revenue</p>
                        <p class="text-3xl font-bold mt-2">$' . number_format($stats['total_revenue'], 2) . '</p>
                    </div>
                    <div class="bg-purple-400 bg-opacity-30 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Render load table
     */
    private function renderLoadTable(array $loads): string
    {
        if (empty($loads)) {
            return '<div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="mt-4 text-lg font-medium">No loads found</p>
            </div>';
        }

        $html = '<div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

        foreach ($loads as $load) {
            $statusColor = match(strtolower($load['status'] ?? '')) {
                'completed' => 'bg-green-100 text-green-800',
                'pending' => 'bg-yellow-100 text-yellow-800',
                'cancelled' => 'bg-red-100 text-red-800',
                default => 'bg-gray-100 text-gray-800'
            };

            $invoice = htmlspecialchars($load['invoice'] ?? '');
            $loadDate = $load['load_dt'] ? date('M d, Y', strtotime($load['load_dt'])) : 'N/A';
            $company = htmlspecialchars($load['company'] ?? '');
            $driver = htmlspecialchars($load['driver'] ?? 'N/A');
            $amount = '$' . number_format($load['amount'] ?? 0, 2);
            $status = htmlspecialchars($load['status'] ?? 'unknown');

            $html .= "
                <tr class=\"hover:bg-gray-50 transition-colors\">
                    <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900\">{$invoice}</td>
                    <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">{$loadDate}</td>
                    <td class=\"px-6 py-4 text-sm text-gray-900\">{$company}</td>
                    <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">{$driver}</td>
                    <td class=\"px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900\">{$amount}</td>
                    <td class=\"px-6 py-4 whitespace-nowrap\">
                        <span class=\"px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {$statusColor}\">
                            {$status}
                        </span>
                    </td>
                    <td class=\"px-6 py-4 whitespace-nowrap text-right text-sm font-medium\">
                        <button
                            class=\"text-indigo-600 hover:text-indigo-900 transition-colors\"
                            data-on-click=\"@get('/dashboard/load-details?invoice={$invoice}')\">
                            View Details
                        </button>
                    </td>
                </tr>";
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Render load details
     */
    private function renderLoadDetails(array $load): string
    {
        $invoice = htmlspecialchars($load['invoice'] ?? 'N/A');
        $loadDate = $load['load_dt'] ? date('F d, Y', strtotime($load['load_dt'])) : 'N/A';
        $company = htmlspecialchars($load['company'] ?? 'N/A');
        $driver = htmlspecialchars($load['driver'] ?? 'N/A');
        $truck = htmlspecialchars($load['truck'] ?? 'N/A');
        $amount = '$' . number_format($load['amount'] ?? 0, 2);
        $status = htmlspecialchars($load['status'] ?? 'unknown');
        $container = htmlspecialchars($load['container'] ?? 'N/A');
        $chassis = htmlspecialchars($load['chassis'] ?? 'N/A');
        $bookingNbr = htmlspecialchars($load['booking_nbr'] ?? 'N/A');
        $notes = htmlspecialchars($load['notes'] ?? 'No notes available');

        return "
        <div class=\"bg-white rounded-lg shadow-lg p-6\">
            <div class=\"flex justify-between items-start mb-6\">
                <div>
                    <h3 class=\"text-2xl font-bold text-gray-900\">Load Details</h3>
                    <p class=\"text-sm text-gray-500 mt-1\">Invoice: {$invoice}</p>
                </div>
                <button
                    class=\"text-gray-400 hover:text-gray-600\"
                    data-on-click=\"\$showDetails = false\">
                    <svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"/>
                    </svg>
                </button>
            </div>

            <div class=\"grid grid-cols-1 md:grid-cols-2 gap-6\">
                <div class=\"space-y-4\">
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Company</label>
                        <p class=\"mt-1 text-lg font-semibold text-gray-900\">{$company}</p>
                    </div>
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Load Date</label>
                        <p class=\"mt-1 text-lg font-semibold text-gray-900\">{$loadDate}</p>
                    </div>
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Amount</label>
                        <p class=\"mt-1 text-lg font-semibold text-green-600\">{$amount}</p>
                    </div>
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Status</label>
                        <p class=\"mt-1\">
                            <span class=\"px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800\">
                                {$status}
                            </span>
                        </p>
                    </div>
                </div>

                <div class=\"space-y-4\">
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Driver</label>
                        <p class=\"mt-1 text-lg font-semibold text-gray-900\">{$driver}</p>
                    </div>
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Truck</label>
                        <p class=\"mt-1 text-lg font-semibold text-gray-900\">{$truck}</p>
                    </div>
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Container</label>
                        <p class=\"mt-1 text-lg font-semibold text-gray-900\">{$container}</p>
                    </div>
                    <div>
                        <label class=\"text-sm font-medium text-gray-500\">Booking Number</label>
                        <p class=\"mt-1 text-lg font-semibold text-gray-900\">{$bookingNbr}</p>
                    </div>
                </div>
            </div>

            <div class=\"mt-6 pt-6 border-t border-gray-200\">
                <label class=\"text-sm font-medium text-gray-500\">Notes</label>
                <p class=\"mt-2 text-gray-700 leading-relaxed\">{$notes}</p>
            </div>
        </div>";
    }

    /**
     * Render recent loads widget
     */
    private function renderRecentLoads(array $loads): string
    {
        $html = '<div class="space-y-3">';

        foreach ($loads as $load) {
            $invoice = htmlspecialchars($load['invoice'] ?? '');
            $company = htmlspecialchars($load['company'] ?? 'Unknown');
            $amount = '$' . number_format($load['amount'] ?? 0, 2);
            $date = $load['load_dt'] ? date('M d', strtotime($load['load_dt'])) : 'N/A';

            $html .= "
            <div class=\"flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors cursor-pointer\"
                 data-on-click=\"@get('/dashboard/load-details?invoice={$invoice}')\">
                <div class=\"flex-1\">
                    <p class=\"text-sm font-medium text-gray-900\">{$invoice}</p>
                    <p class=\"text-xs text-gray-500\">{$company}</p>
                </div>
                <div class=\"text-right\">
                    <p class=\"text-sm font-semibold text-gray-900\">{$amount}</p>
                    <p class=\"text-xs text-gray-500\">{$date}</p>
                </div>
            </div>";
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render top customers widget
     */
    private function renderTopCustomers(array $customers): string
    {
        $html = '<div class="space-y-4">';

        foreach ($customers as $customer) {
            $company = htmlspecialchars($customer['company'] ?? 'Unknown');
            $loadCount = number_format($customer['load_count'] ?? 0);
            $revenue = '$' . number_format($customer['total_revenue'] ?? 0, 2);
            $percentage = ($customer['load_count'] / 1731) * 100; // Total loads

            $html .= "
            <div>
                <div class=\"flex items-center justify-between mb-1\">
                    <span class=\"text-sm font-medium text-gray-700\">{$company}</span>
                    <span class=\"text-sm font-semibold text-green-600\">{$revenue}</span>
                </div>
                <div class=\"flex items-center space-x-2\">
                    <div class=\"flex-1 bg-gray-200 rounded-full h-2\">
                        <div class=\"bg-blue-600 h-2 rounded-full\" style=\"width: {$percentage}%\"></div>
                    </div>
                    <span class=\"text-xs text-gray-500\">{$loadCount} loads</span>
                </div>
            </div>";
        }

        $html .= '</div>';
        return $html;
    }
}
