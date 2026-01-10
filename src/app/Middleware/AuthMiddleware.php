<?php
namespace App\Middleware;

use RuntimeException;

class AuthMiddleware {
    /**
     * Check if user is authenticated
     * @throws RuntimeException
     */
    public static function checkAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check if user is authenticated and has admin privileges
     * @throws RuntimeException
     */
    public static function checkAdmin(): void {
        self::checkAuth();

        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            throw new RuntimeException('Access denied. Admin privileges required.');
        }
    }

    /**
     * Check API authentication using token
     * @throws RuntimeException
     */
    public static function checkApiAuth(): void {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? null;

        if (!$token) {
            http_response_code(401);
            throw new RuntimeException('No authentication token provided.');
        }

        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Validate token
        if (!self::validateApiToken($token)) {
            http_response_code(401);
            throw new RuntimeException('Invalid authentication token.');
        }
    }

    /**
     * Validate API token
     * @param string $token
     * @return bool
     */
    private static function validateApiToken(string $token): bool {
        // TODO: Implement proper token validation
        // This could involve:
        // 1. Checking token format
        // 2. Verifying token signature
        // 3. Checking token expiration
        // 4. Validating against database
        return true;
    }
} 