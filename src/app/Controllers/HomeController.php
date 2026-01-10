<?php

namespace App\Controllers;

use App\Exceptions\AppException;
use App\Models\LoadData;
use App\Middleware\AuthMiddleware;
use App\Middleware\LogMiddleware;

// HomeController class is used to display the home page and the active loads page
// Contains business logic for the home page and the active loads page
class HomeController {
    private LoadData $loadData;

    /**
     * HomeController constructor.
     * Injects the LoadData class via the constructor in the HomeController class to access the getAll() method.
     * The getAll() method is used to get all data from the MYSQL ldata view in the database.
     * 
     * @param LoadData $loadData
     */
    public function __construct(LoadData $loadData) {
        $this->loadData = $loadData;
    }
    /**
     * Display the home page
     * @throws AppException
     */
    public function index(): void {
        try {
            AuthMiddleware::checkAuth();
            $data = $this->loadData->getAll();
            require_once __DIR__ . '/../Views/home.php';
        } catch (AppException $e) {
            LogMiddleware::logError('Error in HomeController@index', [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            throw $e;
        }
    }
    /**
     * Display the active loads page
     * @throws AppException
     */
    public function active(): void {
        try {
            AuthMiddleware::checkAuth();
            $data = $this->loadData->getActive();
            require_once __DIR__ . '/../Views/active.php';
        } catch (AppException $e) {
            LogMiddleware::logError('Error in HomeController@active', [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            throw $e;
        }
    }
//Access the load data api for local storage
    public function getLoadDataApi() {
        header('Content-Type: application/json');
        try {
            $data = $this->loadData->getAll();
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    /**
     * Display the invoice page
    //  * @param string $invoice
    //  * @throws AppException
    //  */
    // public function invoice(string $invoice): void {
    //     try {
    //         AuthMiddleware::checkAuth();
    //         $data = $this->loadData->getByInvoice($invoice);
    //         require_once __DIR__ . '/../Views/invoice.php';
    //     } catch (AppException $e) {
    //         LogMiddleware::logError('Error in HomeController@invoice', [
    //             'invoice' => $invoice,
    //             'error' => $e->getMessage(),
    //             'context' => $e->getContext()
    //         ]);
    //         throw $e;
    //     }
    // }

    /**
     * Display the filtered invoice page
    //  * @param string $invoice
    //  * @param array $filters
    //  * @throws AppException
    //  */
    // public function invoiceFiltered(string $invoice, array $filters): void {
    //     try {
    //         AuthMiddleware::checkAuth();
    //         $data = $this->loadData->getByInvoiceWithFilters($invoice, $filters);
    //         require_once __DIR__ . '/../Views/invoice.php';
    //     } catch (AppException $e) {
    //         LogMiddleware::logError('Error in HomeController@invoiceFiltered', [
    //             'invoice' => $invoice,
    //             'filters' => $filters,
    //             'error' => $e->getMessage(),
    //             'context' => $e->getContext()
    //         ]);
    //         throw $e;
    //     }
    // }

    /**
     * Update invoice for multiple loads
    //  * @param array $loadIds
    //  * @param string $invoiceNumber
    //  * @throws AppException
    //  */
    // public function updateInvoice(array $loadIds, string $invoiceNumber): void {
    //     try {
    //         AuthMiddleware::checkAuth();
    //         $this->validateInvoiceData([
    //             'load_ids' => $loadIds,
    //             'invoice_number' => $invoiceNumber
    //         ]);
    //         $this->loadData->updateLoadsInvoice($loadIds, $invoiceNumber);
    //         header('Location: /active');
    //     } catch (AppException $e) {
    //         LogMiddleware::logError('Error in HomeController@updateInvoice', [
    //             'load_ids' => $loadIds,
    //             'invoice' => $invoiceNumber,
    //             'error' => $e->getMessage(),
    //             'context' => $e->getContext()
    //         ]);
    //         throw $e;
    //     }
    // }

    /**
     * Remove invoice from loads
    //  * @param array $loadIds
    //  * @throws AppException
    //  */
    // public function removeInvoice(array $loadIds): void {
    //     try {
    //         AuthMiddleware::checkAuth();
    //         $this->loadData->removeLoadsInvoice($loadIds);
    //         header('Location: /active');
    //     } catch (AppException $e) {
    //         LogMiddleware::logError('Error in HomeController@removeInvoice', [
    //             'load_ids' => $loadIds,
    //             'error' => $e->getMessage(),
    //             'context' => $e->getContext()
    //         ]);
    //         throw $e;
    //     }
    // }

    /**
     * Display the uninvoiced loads page
    //  * @throws AppException
    //  */
    // public function uninvoiced(): void {
    //     try {
    //         AuthMiddleware::checkAuth();
    //         $data = $this->loadData->getLoadsWithoutInvoice();
    //         require_once __DIR__ . '/../Views/uninvoiced.php';
    //     } catch (AppException $e) {
    //         LogMiddleware::logError('Error in HomeController@uninvoiced', [
    //             'error' => $e->getMessage(),
    //             'context' => $e->getContext()
    //         ]);
    //         throw $e;
    //     }
    // }

    /**
     * Display the about page
    //  * @return array
     */
    public function about(): array {
        return [
            'view' => 'home/about',
            'data' => [
                'title' => 'About ATransport'
            ]
        ];
    }

    /**
     * Display the contact page
     * @return array
     */
    public function contact(): array {
        return [
            'view' => 'home/contact',
            'data' => [
                'title' => 'Contact Us'
            ]
        ];
    }

    /**
     * Display the 404 error page
     * @return array
     */
    public function notFound(): array {
        return [
            'view' => 'errors/404',
            'data' => [
                'title' => 'Page Not Found'
            ]
        ];
    }

    /**
     * Display the 500 error page
     * @return array
     */
    public function serverError(): array {
        return [
            'view' => 'errors/500',
            'data' => [
                'title' => 'Server Error'
            ]
        ];
    }

    /**
     * Validate invoice data
     * @param array $data
     * @throws AppException
     */
    private function validateInvoiceData(array $data): void {
        $requiredFields = ['load_ids', 'invoice_number'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw AppException::validationError('Missing required fields', [
                'fields' => $missingFields
            ]);
        }

        // Validate load IDs are numeric
        foreach ($data['load_ids'] as $id) {
            if (!is_numeric($id)) {
                throw AppException::validationError('Invalid load ID', [
                    'load_id' => $id
                ]);
            }
        }

        // Validate invoice number format
        if (!preg_match('/^[A-Z0-9-]+$/', $data['invoice_number'])) {
            throw AppException::validationError('Invalid invoice number format', [
                'invoice' => $data['invoice_number']
            ]);
        }
    }

   
}