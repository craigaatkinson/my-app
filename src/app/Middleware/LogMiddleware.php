<?php
namespace App\Middleware;

class LogMiddleware {
    /**
     * Log the current request
     * @return void
     */
    public static function logRequest(): void {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'status' => http_response_code()
        ];

        self::writeLog($logData);
    }

    /**
     * Write log entry to file
     * @param array $data
     * @return void
     */
    private static function writeLog(array $data): void {
        $logFile = __DIR__ . '/../../logs/access.log';
        $logDir = dirname($logFile);

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Format log entry
        $logEntry = sprintf(
            "[%s] %s %s - IP: %s - User: %s - Status: %d - Agent: %s\n",
            $data['timestamp'],
            $data['method'],
            $data['uri'],
            $data['ip'],
            $data['user_id'],
            $data['status'],
            $data['user_agent']
        );

        // Write to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Log an error
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function logError(string $message, array $context = []): void {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'message' => $message,
            'context' => $context
        ];

        self::writeErrorLog($logData);
    }

    /**
     * Write error log entry to file
     * @param array $data
     * @return void
     */
    private static function writeErrorLog(array $data): void {
        $logFile = __DIR__ . '/../../logs/error.log';
        $logDir = dirname($logFile);

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Format error log entry
        $logEntry = sprintf(
            "[%s] %s: %s - Context: %s\n",
            $data['timestamp'],
            $data['level'],
            $data['message'],
            json_encode($data['context'])
        );

        // Write to error log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
} 