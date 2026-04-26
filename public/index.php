<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/app/Example.php';
require_once __DIR__ . '/../src/app/Routes/routes.php';

use App\Routes\Router;
use App\Models\LoadData;
use App\Models\LoadCustomers;
// use App\Models\Database;
use function App\Routes\registerRoutes;
use App\Controllers\QuestionController;
use App\Example;

// create a new $pdo instance; and a new $loaddata instance
// $pdo = Database::getInstance()->getConnection();
// $loadData = new LoadData($pdo);
// $loadCustomers = new LoadCustomers($pdo);
// ------------------------------------------------------------
// create a new $example instance
// $example = new Example();
// echo $example->sayHello();
// phpinfo();

// ------------------------------------------------------------
// Stream route set up using the QuestionController -> stream method
// ------------------------------------------------------------
$path = $_SERVER['REQUEST_URI'];

if ($path === '/stream') {
    $controller = new QuestionController();
    $controller->stream();
    exit;
}

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => getenv('APP_ENV') === 'production',
        'cookie_samesite' => 'Lax'
    ]);
}
// ------------------------------------------------------------ 
// Register routes using the newly created Router class 
// $router instance dispatch method, with $method and $uri parameters
// ------------------------------------------------------------
try {
    $router = new Router();
    registerRoutes($router);
} catch (\Exception $e) {
    error_log('Failed to register routes: ' . $e->getMessage());
    http_response_code(500);
    require_once __DIR__ . '/../src/app/Views/errors/500.php';
    exit;
}

// Handle the request using the Router class $router object with -> the Router 
// class dispatch method
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $router->dispatch($method, $uri);
} catch (\RuntimeException $e) {
    if ($e->getCode() === 404) {
        error_log('Route not found: ' . $e->getMessage());
        http_response_code(404);
        require_once __DIR__ . '/../src/app/Views/errors/404.php';
    } else {
        error_log('Runtime error: ' . $e->getMessage());
        http_response_code(500);
        require_once __DIR__ . '/../src/app/Views/errors/500.php';
    }
} catch (\Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    http_response_code(500);
    require_once __DIR__ . '/../src/app/Views/errors/500.php';
}
?>