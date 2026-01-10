<?php

namespace App\Controllers;

use App\Services\DatastarService;
use App\Models\LoadData;
use App\Models\LoadCustomers;

/**
 * DatastarController - Example controller implementing Datastar best practices
 * 
 * This controller demonstrates proper Datastar patterns:
 * - SSE responses for real-time updates
 * - Fragment-based UI updates
 * - Signal management for state
 * - Form handling with validation
 * - Error handling and user feedback
 */
class DatastarController
{
    private DatastarService $datastar;
    private LoadData $loadData;
    private LoadCustomers $loadCustomers;

    public function __construct()
    {
        $this->datastar = new DatastarService();
        // Initialize models (you'll need to inject PDO instance)
        // $this->loadData = new LoadData($pdo);
        // $this->loadCustomers = new LoadCustomers($pdo);
    }

    /**
     * Home page with Datastar-enabled UI
     */
    public function home(): void
    {
        include __DIR__ . '/../Views/datastar/home.php';
    }

    /**
     * Load data table endpoint - returns HTML fragments
     */
    public function loadDataTable(): void
    {
        try {
            // Show loading indicator first
            $this->datastar
                ->addFragment('#data-table', $this->datastar->generateLoadingHtml('Loading data...'))
                ->sendEvent();

            // Simulate data loading delay
            sleep(1);

            // Get data (mock data for example)
            $data = $this->getMockLoadData();

            // Define table columns
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'pickup', 'label' => 'Pickup'],
                ['key' => 'delivery', 'label' => 'Delivery'],
                ['key' => 'status', 'label' => 'Status', 'format' => [$this, 'formatStatus']],
                ['key' => 'amount', 'label' => 'Amount', 'format' => [$this, 'formatCurrency']]
            ];

            // Generate table HTML
            $tableHtml = $this->datastar->generateTableHtml($data, $columns);

            // Send table fragment
            $this->datastar
                ->addFragment('#data-table', $tableHtml)
                ->addSignal('dataLoaded', true)
                ->addSignal('totalRecords', count($data))
                ->sendEvent();

        } catch (\Exception $e) {
            $this->datastar->sendError('#data-table', 'Failed to load data: ' . $e->getMessage());
        }
    }

    /**
     * Customer search endpoint
     */
    public function searchCustomers(): void
    {
        try {
            $searchTerm = $_GET['search'] ?? '';

            if (strlen($searchTerm) < 2) {
                $this->datastar
                    ->addFragment('#customer-results', '<div class="text-gray-500 p-4">Enter at least 2 characters to search</div>')
                    ->sendEvent();
                return;
            }

            // Mock search results
            $customers = $this->getMockCustomers($searchTerm);

            $html = '<div class="space-y-2">';
            foreach ($customers as $customer) {
                $html .= '<div class="border rounded p-3 hover:bg-gray-50 cursor-pointer" data-on-click="@get(\'/customer/' . $customer['id'] . '\')">';
                $html .= '<div class="font-medium">' . htmlspecialchars($customer['name']) . '</div>';
                $html .= '<div class="text-sm text-gray-600">' . htmlspecialchars($customer['email']) . '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';

            $this->datastar
                ->addFragment('#customer-results', $html)
                ->addSignal('searchResults', count($customers))
                ->sendEvent();

        } catch (\Exception $e) {
            $this->datastar->sendError('#customer-results', 'Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Form submission endpoint
     */
    public function submitForm(): void
    {
        try {
            $data = $_POST;

            // Validation rules
            $rules = [
                'customer_name' => ['required', 'max:100'],
                'email' => ['required', 'email'],
                'pickup_location' => ['required', 'max:200'],
                'delivery_location' => ['required', 'max:200']
            ];

            $validation = $this->datastar->validateInput($data, $rules);

            if (!$validation['valid']) {
                $errorHtml = $this->datastar->generateValidationErrorsHtml($validation['errors']);
                $this->datastar
                    ->addFragment('#form-errors', $errorHtml)
                    ->addSignal('formValid', false)
                    ->sendEvent();
                return;
            }

            // Clear any previous errors
            $this->datastar->addFragment('#form-errors', '');

            // Simulate form processing
            sleep(1);

            // Success response
            $this->datastar
                ->addFragment('#form-container', $this->generateSuccessMessage($data))
                ->addSignal('formSubmitted', true)
                ->addSignal('submittedData', $data)
                ->sendEvent();

        } catch (\Exception $e) {
            $this->datastar->sendError('#form-errors', 'Form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Real-time notification endpoint - demonstrates enhanced SDK features
     */
    public function notifications(): void
    {
        $this->datastar->setSSEHeaders();

        // Send initial notification using enhanced SDK methods
        $this->datastar
            ->quickHtml('#notifications', $this->generateNotificationHtml('Connected to live updates'))
            ->mergeSignals(['connected' => true, 'loading' => false])
            ->sendEvent();

        // Simulate real-time updates
        for ($i = 1; $i <= 5; $i++) {
            sleep(2);
            
            $notification = "Update #{$i} - " . date('H:i:s');
            $this->datastar
                ->addFragment('#notifications', $this->generateNotificationHtml($notification), 'prepend')
                ->addSignal('lastUpdate', time())
                ->addSignal('updateCount', $i)
                ->sendEvent();
        }

        // Final message with view transition
        $this->datastar
            ->addFragment('#notifications', $this->generateNotificationHtml('Demo completed'), 'prepend')
            ->addSignals(['demoComplete' => true, 'connected' => false])
            ->withViewTransition()
            ->sendEvent();
    }

    /**
     * Example endpoint demonstrating script execution
     */
    public function executeScript(): void
    {
        $script = "
        console.log('Datastar SDK v{$this->datastar->getVersion()} script executed');
        alert('Hello from server-side JavaScript!');
        ";
        
        $this->datastar->addScript($script, ['defer' => true]);
        $this->datastar->sendEvent();
    }

    /**
     * Debug endpoint to show SDK capabilities
     */
    public function debug(): void
    {
        $info = $this->datastar->getRequestInfo();
        $this->datastar->sendJsonResponse($info);
    }

    /**
     * Dynamic form field endpoint
     */
    public function getFormFields(): void
    {
        try {
            $formType = $_GET['type'] ?? 'basic';

            $html = '<div class="space-y-4">';

            switch ($formType) {
                case 'customer':
                    $html .= $this->datastar->generateFormField('text', 'company_name', 'Company Name');
                    $html .= $this->datastar->generateFormField('text', 'contact_person', 'Contact Person');
                    $html .= $this->datastar->generateFormField('email', 'email', 'Email Address');
                    $html .= $this->datastar->generateFormField('text', 'phone', 'Phone Number');
                    break;

                case 'shipment':
                    $html .= $this->datastar->generateFormField('text', 'pickup_address', 'Pickup Address');
                    $html .= $this->datastar->generateFormField('text', 'delivery_address', 'Delivery Address');
                    $html .= $this->datastar->generateFormField('date', 'pickup_date', 'Pickup Date');
                    $html .= $this->datastar->generateFormField('select', 'priority', 'Priority', '', [
                        'options' => ['normal' => 'Normal', 'urgent' => 'Urgent', 'express' => 'Express']
                    ]);
                    break;

                default:
                    $html .= $this->datastar->generateFormField('text', 'name', 'Name');
                    $html .= $this->datastar->generateFormField('email', 'email', 'Email');
                    break;
            }

            $html .= '</div>';

            $this->datastar
                ->addFragment('#dynamic-fields', $html)
                ->addSignal('formType', $formType)
                ->sendEvent();

        } catch (\Exception $e) {
            $this->datastar->sendError('#dynamic-fields', 'Failed to load form fields: ' . $e->getMessage());
        }
    }

    // Helper methods

    private function getMockLoadData(): array
    {
        return [
            ['id' => 1, 'customer' => 'ABC Transport', 'pickup' => 'Atlanta, GA', 'delivery' => 'Miami, FL', 'status' => 'in_transit', 'amount' => 1250.00],
            ['id' => 2, 'customer' => 'XYZ Logistics', 'pickup' => 'Dallas, TX', 'delivery' => 'Houston, TX', 'status' => 'delivered', 'amount' => 875.50],
            ['id' => 3, 'customer' => 'Quick Ship Co', 'pickup' => 'Phoenix, AZ', 'delivery' => 'Los Angeles, CA', 'status' => 'pending', 'amount' => 950.75],
            ['id' => 4, 'customer' => 'Fast Freight', 'pickup' => 'Denver, CO', 'delivery' => 'Salt Lake City, UT', 'status' => 'in_transit', 'amount' => 675.25],
        ];
    }

    private function getMockCustomers(string $search): array
    {
        $allCustomers = [
            ['id' => 1, 'name' => 'ABC Transport Co', 'email' => 'contact@abctransport.com'],
            ['id' => 2, 'name' => 'XYZ Logistics', 'email' => 'info@xyzlogistics.com'],
            ['id' => 3, 'name' => 'Quick Ship Company', 'email' => 'orders@quickship.com'],
            ['id' => 4, 'name' => 'Fast Freight Solutions', 'email' => 'dispatch@fastfreight.com'],
        ];

        return array_filter($allCustomers, function($customer) use ($search) {
            return stripos($customer['name'], $search) !== false || 
                   stripos($customer['email'], $search) !== false;
        });
    }

    public function formatStatus($status, $row): string
    {
        $classes = match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'in_transit' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };

        return "<span class=\"px-2 py-1 rounded-full text-xs font-medium {$classes}\">" . 
               ucfirst(str_replace('_', ' ', $status)) . "</span>";
    }

    public function formatCurrency($amount, $row): string
    {
        return '$' . number_format($amount, 2);
    }

    private function generateSuccessMessage(array $data): string
    {
        return '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <h3 class="font-medium">Form submitted successfully!</h3>
                    <p>Thank you for your submission. We will process your request shortly.</p>
                </div>';
    }

    private function generateNotificationHtml(string $message): string
    {
        return '<div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-2">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">' . htmlspecialchars($message) . '</p>
                            <p class="text-xs text-blue-500">' . date('Y-m-d H:i:s') . '</p>
                        </div>
                    </div>
                </div>';
    }
}