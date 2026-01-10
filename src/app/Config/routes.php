<?php
namespace App\Config;

class Routes {
    private static ?array $routes = null;

    /**
     * Get all registered routes
     * @return array
     */
    public static function getRoutes(): array {
        if (self::$routes === null) {
            self::$routes = require __DIR__ . '/routes.php';
        }
        return self::$routes;
    }
} 