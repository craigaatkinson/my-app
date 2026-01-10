<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\HomeController;
use App\Models\LoadData;
use App\Exceptions\AppException;
use App\Middleware\AuthMiddleware;
use App\Middleware\LogMiddleware;
use Mockery;

class HomeControllerTest extends TestCase
{
    private HomeController $controller;
    private $mockLoadData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockLoadData = Mockery::mock(LoadData::class);
        $this->controller = new HomeController($this->mockLoadData);
    }

    public function testIndexWithAuthenticationSuccess()
    {
        $expectedData = [
            ['id' => 1, 'customer' => 'CUST001', 'status2' => 'active', 'date_arv' => '2024-01-01'],
            ['id' => 2, 'customer' => 'CUST002', 'status2' => 'approved', 'date_arv' => '2024-01-02']
        ];

        Mockery::mock('alias:' . AuthMiddleware::class)
            ->shouldReceive('checkAuth')
            ->once()
            ->andReturnNull();

        $this->mockLoadData
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($expectedData);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertTrue(true);
    }

    public function testIndexWithAuthenticationFailure()
    {
        $this->expectException(AppException::class);

        Mockery::mock('alias:' . AuthMiddleware::class)
            ->shouldReceive('checkAuth')
            ->once()
            ->andThrow(AppException::authError('Unauthorized'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->controller->index();
    }

    public function testActiveWithAuthenticationSuccess()
    {
        $expectedData = [
            ['id' => 1, 'customer' => 'CUST001', 'status2' => 'active', 'date_arv' => '2024-01-01'],
            ['id' => 3, 'customer' => 'CUST003', 'status2' => 'manual', 'date_arv' => '2024-01-03']
        ];

        Mockery::mock('alias:' . AuthMiddleware::class)
            ->shouldReceive('checkAuth')
            ->once()
            ->andReturnNull();

        $this->mockLoadData
            ->shouldReceive('getActive')
            ->once()
            ->andReturn($expectedData);

        ob_start();
        $this->controller->active();
        $output = ob_get_clean();

        $this->assertTrue(true);
    }

    public function testActiveWithDatabaseError()
    {
        $this->expectException(AppException::class);

        Mockery::mock('alias:' . AuthMiddleware::class)
            ->shouldReceive('checkAuth')
            ->once()
            ->andReturnNull();

        $this->mockLoadData
            ->shouldReceive('getActive')
            ->once()
            ->andThrow(AppException::serverError('Database connection failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->controller->active();
    }

    public function testGetLoadDataApiSuccess()
    {
        $expectedData = [
            ['id' => 1, 'customer' => 'CUST001', 'status2' => 'active', 'date_arv' => '2024-01-01'],
            ['id' => 2, 'customer' => 'CUST002', 'status2' => 'approved', 'date_arv' => '2024-01-02']
        ];

        $this->mockLoadData
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($expectedData);

        ob_start();
        $this->controller->getLoadDataApi();
        $output = ob_get_clean();

        $result = json_decode($output, true);
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedData, $result['data']);
    }

    public function testGetLoadDataApiWithException()
    {
        $this->mockLoadData
            ->shouldReceive('getAll')
            ->once()
            ->andThrow(new \Exception('Database error'));

        ob_start();
        $this->controller->getLoadDataApi();
        $output = ob_get_clean();

        $result = json_decode($output, true);
        $this->assertFalse($result['success']);
        $this->assertEquals('Database error', $result['error']);
    }

    public function testAbout()
    {
        $result = $this->controller->about();

        $this->assertEquals('home/about', $result['view']);
        $this->assertEquals('About ATransport', $result['data']['title']);
    }

    public function testContact()
    {
        $result = $this->controller->contact();

        $this->assertEquals('home/contact', $result['view']);
        $this->assertEquals('Contact Us', $result['data']['title']);
    }

    public function testNotFound()
    {
        $result = $this->controller->notFound();

        $this->assertEquals('errors/404', $result['view']);
        $this->assertEquals('Page Not Found', $result['data']['title']);
    }

    public function testServerError()
    {
        $result = $this->controller->serverError();

        $this->assertEquals('errors/500', $result['view']);
        $this->assertEquals('Server Error', $result['data']['title']);
    }

    public function testValidateInvoiceDataSuccess()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateInvoiceData');
        $method->setAccessible(true);

        $validData = [
            'load_ids' => [1, 2, 3],
            'invoice_number' => 'INV-2024-001'
        ];

        $method->invoke($this->controller, $validData);
        $this->assertTrue(true);
    }

    public function testValidateInvoiceDataMissingFields()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Missing required fields');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateInvoiceData');
        $method->setAccessible(true);

        $invalidData = [
            'load_ids' => [1, 2, 3]
        ];

        $method->invoke($this->controller, $invalidData);
    }

    public function testValidateInvoiceDataInvalidLoadId()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid load ID');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateInvoiceData');
        $method->setAccessible(true);

        $invalidData = [
            'load_ids' => [1, 'invalid', 3],
            'invoice_number' => 'INV-2024-001'
        ];

        $method->invoke($this->controller, $invalidData);
    }

    public function testValidateInvoiceDataInvalidInvoiceFormat()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid invoice number format');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateInvoiceData');
        $method->setAccessible(true);

        $invalidData = [
            'load_ids' => [1, 2, 3],
            'invoice_number' => 'invalid invoice!'
        ];

        $method->invoke($this->controller, $invalidData);
    }
}