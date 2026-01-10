<?php
namespace App\Exceptions;

use Exception;

class AppException extends Exception {
    private array $context;

    /**
     * Create a new configuration error exception
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function configError(string $message, array $context = []): static {
        return new static($message, 500, $context);
    }

    /**
     * Create a new not found exception
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function notFound(string $message, array $context = []): static {
        return new static($message, 404, $context);
    }

    /**
     * Create a new server error exception
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function serverError(string $message, array $context = []): static {
        return new static($message, 500, $context);
    }

    /**
     * Create a new validation error exception
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function validationError(string $message, array $context = []): static {
        return new static($message, 422, $context);
    }

    /**
     * Create a new authentication error exception
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function authError(string $message, array $context = []): static {
        return new static($message, 401, $context);
    }

    /**
     * Create a new authorization error exception
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function forbiddenError(string $message, array $context = []): static {
        return new static($message, 403, $context);
    }

    /**
     * Create a new exception instance
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(string $message, int $code = 0, array $context = []) {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    /**
     * Get the context data
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * Get the exception as an array
     * @return array
     */
    public function toArray(): array {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext()
        ];
    }
} 