<?php
namespace App\Routes;

use App\Routes\Router;
use App\Controllers\HomeController;
use App\Controllers\CustomerController;
use App\Controllers\DatastarController;
use App\Controllers\LoadDashboardController;
use App\Controllers\DebugController;
use App\Controllers\AuthController;
// use App\Controllers\FinController;
use App\Middleware\AuthMiddleware;
use App\Middleware\LogMiddleware;

/**
 * Register all application routes
 * @param Router $router
 * @return void
 */
// registerRoutes function takes a Router object as a parameter and returns void
// the function is called in the index.php file; the main function and pupose for this file
// the function calls multiple Router class - $router object - methods used to register all the routes used for the application
function registerRoutes(Router $router): void
{
    // Global middleware for all routes
    $router->middleware([
        // Log all requests
        function() {
            LogMiddleware::logRequest();
        },
        // Start session if not already started
        function() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }
    ]);

    // Authentication routes (no auth required)
    $router->group('', [], function($router) {
        $router->get('/login', [AuthController::class, 'showLogin'])
        ->post('/login', [AuthController::class, 'login'])
        ->get('/register', [AuthController::class, 'showRegister'])
        ->post('/register', [AuthController::class, 'register'])
        ->get('/logout', [AuthController::class, 'logout']);
    });

    // Public routes group (no authentication required)
    $router->group('', [], function($router) {
        // Here is where the routes are registered
        $router->get('/', [HomeController::class, 'index'])
        ->get('/active', [HomeController::class, 'active'])
        ->get('/about', [HomeController::class, 'about'])
        ->get('/contact', [HomeController::class, 'contact'])
        ->get('/load-data', [HomeController::class, 'getLoadDataApi'])

        // Customer page routes (specific routes before parameterized ones)
        ->get('/customer-list', [CustomerController::class, 'customersPage'])
        ->get('/customers/location/{location}', [CustomerController::class, 'getCustomersByLocation'])
        ->get('/customers/search/{term}', [CustomerController::class,'searchCustomersByName'])
        ->get('/customers', [CustomerController::class, 'getAllCustomers'])
        ->get('/customers/{id}', [CustomerController::class, 'getCustomerById'])
        
        // Datastar demo routes
        ->get('/datastar', [DatastarController::class, 'home'])
        ->get('/datastar/load-data-table', [DatastarController::class, 'loadDataTable'])
        ->get('/datastar/search-customers', [DatastarController::class, 'searchCustomers'])
        ->post('/datastar/submit-form', [DatastarController::class, 'submitForm'])
        ->get('/datastar/notifications', [DatastarController::class, 'notifications'])
        ->get('/datastar/form-fields', [DatastarController::class, 'getFormFields'])

        // Load Dashboard routes
        ->get('/dashboard', [LoadDashboardController::class, 'index'])
        ->get('/dashboard/statistics', [LoadDashboardController::class, 'getStatistics'])
        ->get('/dashboard/load-table', [LoadDashboardController::class, 'loadTable'])
        ->get('/dashboard/search', [LoadDashboardController::class, 'search'])
        ->get('/dashboard/load-details', [LoadDashboardController::class, 'getLoadDetails'])
        ->get('/dashboard/recent-loads', [LoadDashboardController::class, 'getRecentLoads'])
        ->get('/dashboard/top-customers', [LoadDashboardController::class, 'getTopCustomers'])
        ->get('/dashboard/filter-status', [LoadDashboardController::class, 'filterByStatus'])
        ->get('/dashboard/live-stream', [LoadDashboardController::class, 'liveStream'])

        // Loads page routes
        ->get('/loads', [LoadDashboardController::class, 'loadsPage'])
        ->get('/loads/new', [LoadDashboardController::class, 'newLoadPage'])

        // Debug routes
        ->get('/debug/test-sse', [DebugController::class, 'testSSE']);


        // Invoice routes
        // $router->group('invoice', [], function($router) {
        //     $router->get('/', [HomeController::class, 'getByInvoice'])
        //         ->get('/{id}', [HomeController::class, 'getInvoiceById'])
        //         ->post('/create', [HomeController::class, 'createInvoice'])
        //         ->put('/{id}', [HomeController::class, 'updateInvoice'])
        //         ->delete('/{id}', [HomeController::class, 'deleteInvoice']);
        // });
    });

    // Protected routes group (requires authentication)
    // $router->group('', [
    //     function() {
    //         AuthMiddleware::checkAuth();
    //     }
    // ], function($router) {
        // // Financial routes
        // $router->group('fin', [], function($router) {
        //     $router->get('/', [FinController::class, 'fin'])
        //         ->get('/dashboard', [FinController::class, 'dashboard'])
        //         ->get('/reports', [FinController::class, 'reports'])
        //         ->post('/generate-report', [FinController::class, 'generateReport']);
        // });

        // Admin routes
    //     $router->group('admin', [
    //         function() {
    //             AuthMiddleware::checkAdmin();
    //         }
    //     ], function($router) {
    //         $router->get('/', [HomeController::class, 'adminDashboard'])
    //             ->get('/users', [HomeController::class, 'manageUsers'])
    //             ->get('/settings', [HomeController::class, 'systemSettings']);
    //     });
    // });

    // API routes group
    // $router->group('api', [
    //     function() {
    //         // API authentication middleware
    //         AuthMiddleware::checkApiAuth();
    //     }
    // ], function($router) {
    //     $router->group('v1', [], function($router) {
    //         // Invoice API endpoints
    //         $router->get('/invoices', [HomeController::class, 'getInvoicesApi'])
    //             ->get('/invoices/{id}', [HomeController::class, 'getInvoiceApi'])
    //             ->post('/invoices', [HomeController::class, 'createInvoiceApi'])
    //             ->put('/invoices/{id}', [HomeController::class, 'updateInvoiceApi'])
    //             ->delete('/invoices/{id}', [HomeController::class, 'deleteInvoiceApi']);

    //         // Financial API endpoints
    //         // $router->get('/financial', [FinController::class, 'getFinancialData'])
    //         //     ->get('/reports', [FinController::class, 'getReports'])
    //         //     ->post('/reports', [FinController::class, 'createReport']);
    //     });
    // });

    // Error handling routes
    $router->group('', [], function($router) {
        $router->get('/404', [HomeController::class, 'notFound'])
            ->get('/500', [HomeController::class, 'serverError']);
    });
}
