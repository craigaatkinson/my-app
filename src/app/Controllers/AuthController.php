<?php
namespace App\Controllers;

use App\Models\User;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;

class AuthController {
    private User $user;

    public function __construct() {
        $this->user = new User();
    }

    /**
     * Show the login form
     */
    public function showLogin(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }
        $error = $_SESSION['auth_error'] ?? null;
        $success = $_SESSION['auth_success'] ?? null;
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Process login form submission
     */
    public function login(): void {
        try {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $_SESSION['auth_error'] = 'Email and password are required.';
                header('Location: /login');
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['auth_error'] = 'Please enter a valid email address.';
                header('Location: /login');
                exit;
            }

            $user = $this->user->findByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $_SESSION['auth_error'] = 'Invalid email or password.';
                header('Location: /login');
                exit;
            }

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;

        } catch (AppException $e) {
            LogMiddleware::logError('Error in AuthController@login', [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            $_SESSION['auth_error'] = 'An error occurred. Please try again.';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Show the registration form
     */
    public function showRegister(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }
        $error = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);
        require_once __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Process registration form submission
     */
    public function register(): void {
        try {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            $errors = [];
            if (empty($name)) {
                $errors[] = 'Name is required.';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (!empty($errors)) {
                $_SESSION['auth_error'] = implode(' ', $errors);
                header('Location: /register');
                exit;
            }

            $existing = $this->user->findByEmail($email);
            if ($existing) {
                $_SESSION['auth_error'] = 'An account with this email already exists.';
                header('Location: /register');
                exit;
            }

            $this->user->create([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
            ]);

            $_SESSION['auth_success'] = 'Account created successfully. Please log in.';
            header('Location: /login');
            exit;

        } catch (AppException $e) {
            LogMiddleware::logError('Error in AuthController@register', [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            $_SESSION['auth_error'] = 'Registration failed. Please try again.';
            header('Location: /register');
            exit;
        }
    }

    /**
     * Log the user out
     */
    public function logout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: /login');
        exit;
    }
}
