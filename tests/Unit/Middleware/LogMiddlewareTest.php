<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Middleware\LogMiddleware;

class LogMiddlewareTest extends TestCase
{
    private string $testLogDir;
    private string $accessLogFile;
    private string $errorLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testLogDir = sys_get_temp_dir() . '/test_logs_' . uniqid();
        $this->accessLogFile = $this->testLogDir . '/access.log';
        $this->errorLogFile = $this->testLogDir . '/error.log';
        
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'TestAgent/1.0'
        ];
        $_SESSION = ['user_id' => 123];
        
        $this->mockLogPaths();
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
        $_SESSION = [];
        
        if (is_dir($this->testLogDir)) {
            $this->removeDirectory($this->testLogDir);
        }
        
        parent::tearDown();
    }

    public function testLogRequestCreatesLogEntry()
    {
        LogMiddleware::logRequest();

        $this->assertFileExists($this->accessLogFile);
        
        $logContent = file_get_contents($this->accessLogFile);
        $this->assertStringContains('GET /test', $logContent);
        $this->assertStringContains('IP: 127.0.0.1', $logContent);
        $this->assertStringContains('User: 123', $logContent);
        $this->assertStringContains('TestAgent/1.0', $logContent);
    }

    public function testLogRequestWithGuestUser()
    {
        unset($_SESSION['user_id']);

        LogMiddleware::logRequest();

        $logContent = file_get_contents($this->accessLogFile);
        $this->assertStringContains('User: guest', $logContent);
    }

    public function testLogRequestWithMissingUserAgent()
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        LogMiddleware::logRequest();

        $logContent = file_get_contents($this->accessLogFile);
        $this->assertStringContains('Agent: Unknown', $logContent);
    }

    public function testLogRequestCreatesDirectoryIfNotExists()
    {
        $this->assertDirectoryDoesNotExist($this->testLogDir);

        LogMiddleware::logRequest();

        $this->assertDirectoryExists($this->testLogDir);
        $this->assertFileExists($this->accessLogFile);
    }

    public function testLogErrorCreatesErrorLogEntry()
    {
        $message = 'Test error message';
        $context = ['error_code' => 500, 'file' => 'test.php'];

        LogMiddleware::logError($message, $context);

        $this->assertFileExists($this->errorLogFile);
        
        $logContent = file_get_contents($this->errorLogFile);
        $this->assertStringContains('ERROR: Test error message', $logContent);
        $this->assertStringContains('"error_code":500', $logContent);
        $this->assertStringContains('"file":"test.php"', $logContent);
    }

    public function testLogErrorWithEmptyContext()
    {
        $message = 'Simple error';

        LogMiddleware::logError($message);

        $logContent = file_get_contents($this->errorLogFile);
        $this->assertStringContains('ERROR: Simple error', $logContent);
        $this->assertStringContains('Context: []', $logContent);
    }

    public function testLogErrorCreatesDirectoryIfNotExists()
    {
        $this->assertDirectoryDoesNotExist($this->testLogDir);

        LogMiddleware::logError('Test error');

        $this->assertDirectoryExists($this->testLogDir);
        $this->assertFileExists($this->errorLogFile);
    }

    public function testMultipleLogEntriesAppend()
    {
        LogMiddleware::logRequest();
        LogMiddleware::logRequest();

        $logContent = file_get_contents($this->accessLogFile);
        $this->assertEquals(2, substr_count($logContent, 'GET /test'));
    }

    public function testLogEntryTimestampFormat()
    {
        LogMiddleware::logRequest();

        $logContent = file_get_contents($this->accessLogFile);
        
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function testLogErrorTimestampFormat()
    {
        LogMiddleware::logError('Test error');

        $logContent = file_get_contents($this->errorLogFile);
        
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function testComplexContextLogging()
    {
        $complexContext = [
            'user' => ['id' => 123, 'name' => 'Test User'],
            'request' => ['method' => 'POST', 'data' => ['key' => 'value']],
            'debug' => true
        ];

        LogMiddleware::logError('Complex error', $complexContext);

        $logContent = file_get_contents($this->errorLogFile);
        $this->assertStringContains('"user":{"id":123,"name":"Test User"}', $logContent);
        $this->assertStringContains('"debug":true', $logContent);
    }

    private function mockLogPaths(): void
    {
        $reflection = new \ReflectionClass(LogMiddleware::class);
        
        $writeLogMethod = $reflection->getMethod('writeLog');
        $writeLogMethod->setAccessible(true);
        
        $writeErrorLogMethod = $reflection->getMethod('writeErrorLog');
        $writeErrorLogMethod->setAccessible(true);
        
        LogMiddleware::$accessLogPath = $this->accessLogFile;
        LogMiddleware::$errorLogPath = $this->errorLogFile;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}