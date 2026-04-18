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
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!isset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'])) {
    error_log('Missing REQUEST_URI or REQUEST_METHOD (ip=' . $remoteAddr . ')');
    http_response_code(400);
    echo '400 Bad Request';
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($requestUri, PHP_URL_PATH);

if ($uri === false) {
    error_log('Invalid URI path parsed from request URI (ip=' . $remoteAddr . ', uri_hash=' . hash('sha256', $requestUri) . ')');
    http_response_code(400);
    echo '400 Bad Request';
    exit;
}

if ($uri === null) {
    $uri = '/';
}

if ($uri === '/stream') {
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
    $router->dispatch($method, $uri);
} catch (\Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    http_response_code(404);
    require_once __DIR__ . '/../src/app/Views/errors/404.php';
}
