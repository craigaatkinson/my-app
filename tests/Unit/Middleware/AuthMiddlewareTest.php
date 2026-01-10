<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Middleware\AuthMiddleware;
use RuntimeException;

class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $_SESSION = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        parent::tearDown();
    }

    public function testCheckAuthWithValidSession()
    {
        $_SESSION['user_id'] = 123;

        AuthMiddleware::checkAuth();
        
        $this->assertTrue(true);
    }

    public function testCheckAuthWithoutSession()
    {
        $_SERVER['REQUEST_URI'] = '/dashboard';

        $this->expectOutputString('');
        
        try {
            AuthMiddleware::checkAuth();
            $this->fail('Expected checkAuth to exit');
        } catch (\TypeError $e) {
            $this->assertStringContains('header', $e->getMessage());
        }
        
        $this->assertEquals('/dashboard', $_SESSION['redirect_after_login']);
    }

    public function testCheckAdminWithValidAdminSession()
    {
        $_SESSION['user_id'] = 123;
        $_SESSION['user_role'] = 'admin';

        AuthMiddleware::checkAdmin();
        
        $this->assertTrue(true);
    }

    public function testCheckAdminWithoutUserSession()
    {
        $this->expectOutputString('');
        
        try {
            AuthMiddleware::checkAdmin();
            $this->fail('Expected checkAdmin to exit via checkAuth');
        } catch (\TypeError $e) {
            $this->assertStringContains('header', $e->getMessage());
        }
    }

    public function testCheckAdminWithNonAdminRole()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Access denied. Admin privileges required.');

        $_SESSION['user_id'] = 123;
        $_SESSION['user_role'] = 'user';

        AuthMiddleware::checkAdmin();
    }

    public function testCheckAdminWithoutRole()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Access denied. Admin privileges required.');

        $_SESSION['user_id'] = 123;

        AuthMiddleware::checkAdmin();
    }

    public function testCheckApiAuthWithValidBearerToken()
    {
        $this->mockGetAllHeaders(['Authorization' => 'Bearer valid_token']);

        AuthMiddleware::checkApiAuth();
        
        $this->assertTrue(true);
    }

    public function testCheckApiAuthWithValidTokenWithoutBearer()
    {
        $this->mockGetAllHeaders(['Authorization' => 'valid_token']);

        AuthMiddleware::checkApiAuth();
        
        $this->assertTrue(true);
    }

    public function testCheckApiAuthWithoutToken()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No authentication token provided.');

        $this->mockGetAllHeaders([]);

        AuthMiddleware::checkApiAuth();
    }

    public function testCheckApiAuthWithEmptyToken()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No authentication token provided.');

        $this->mockGetAllHeaders(['Authorization' => '']);

        AuthMiddleware::checkApiAuth();
    }

    public function testValidateApiTokenReturnsTrue()
    {
        $reflection = new \ReflectionClass(AuthMiddleware::class);
        $method = $reflection->getMethod('validateApiToken');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'any_token');
        
        $this->assertTrue($result);
    }

    private function mockGetAllHeaders(array $headers)
    {
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                global $mockedHeaders;
                return $mockedHeaders;
            }
        }
        
        global $mockedHeaders;
        $mockedHeaders = $headers;
    }
}