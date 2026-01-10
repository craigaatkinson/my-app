<?php
namespace App\Routes;

use App\Routes\Router;
use App\Controllers\HomeController;
use App\Controllers\CustomerController;
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

    // Public routes group (no authentication required)
    $router->group('', [], function($router) {
        // Here is where the routes are registered
        $router->get('/', [HomeController::class, 'index'])
        ->get('/active', [HomeController::class, 'active'])
        ->get('/about', [HomeController::class, 'about'])
        ->get('/contact', [HomeController::class, 'contact'])
        ->get('/load-data', [HomeController::class, 'getLoadDataApi'])
        ->get('/customers', [CustomerController::class, 'getAllCustomers'])
        ->get('/customers/{id}', [CustomerController::class, 'getCustomerById'])
        ->get('/customers/location/{location}', [CustomerController::class, 'getCustomersByLocation'])
        ->get('/customers/search/{term}', [CustomerController::class,'searchCustomersByName']);
        
        
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
