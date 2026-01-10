<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\CustomerController;
use App\Models\LoadCustomers;
use App\Exceptions\AppException;
use Mockery;

class CustomerControllerTest extends TestCase
{
    private CustomerController $controller;
    private $mockLoadCustomers;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockLoadCustomers = Mockery::mock(LoadCustomers::class);
        $this->controller = new CustomerController();
        
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('loadCustomers');
        $property->setAccessible(true);
        $property->setValue($this->controller, $this->mockLoadCustomers);
    }

    public function testGetAllCustomersSuccess()
    {
        $expectedData = [
            ['customer' => 'CUST001', 'name' => 'Test Customer 1', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50],
            ['customer' => 'CUST002', 'name' => 'Test Customer 2', 'location' => 'California', 'miles' => 200, 'rate' => 3.00]
        ];

        $this->mockLoadCustomers
            ->shouldReceive('getAllCustomers')
            ->once()
            ->andReturn($expectedData);

        $result = $this->controller->getAllCustomers();

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($expectedData, $result['data']);
    }

    public function testGetAllCustomersWithException()
    {
        $this->mockLoadCustomers
            ->shouldReceive('getAllCustomers')
            ->once()
            ->andThrow(new AppException('Database connection failed', 500));

        $result = $this->controller->getAllCustomers();

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Database connection failed', $result['message']);
    }

    public function testGetCustomerByIdSuccess()
    {
        $customerId = 'CUST001';
        $expectedData = ['customer' => 'CUST001', 'name' => 'Test Customer', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50];

        $this->mockLoadCustomers
            ->shouldReceive('getCustomerById')
            ->once()
            ->with($customerId)
            ->andReturn($expectedData);

        $result = $this->controller->getCustomerById($customerId);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($expectedData, $result['data']);
    }

    public function testGetCustomerByIdNotFound()
    {
        $customerId = 'NONEXISTENT';

        $this->mockLoadCustomers
            ->shouldReceive('getCustomerById')
            ->once()
            ->with($customerId)
            ->andReturn(null);

        $result = $this->controller->getCustomerById($customerId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Customer not found', $result['message']);
    }

    public function testGetCustomerByIdWithException()
    {
        $customerId = 'CUST001';

        $this->mockLoadCustomers
            ->shouldReceive('getCustomerById')
            ->once()
            ->with($customerId)
            ->andThrow(new AppException('Database error', 500));

        $result = $this->controller->getCustomerById($customerId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Database error', $result['message']);
    }

    public function testGetCustomersByLocationSuccess()
    {
        $location = 'New York';
        $expectedData = [
            ['customer' => 'CUST001', 'name' => 'Test Customer 1', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50],
            ['customer' => 'CUST003', 'name' => 'Test Customer 3', 'location' => 'New York', 'miles' => 150, 'rate' => 2.75]
        ];

        $this->mockLoadCustomers
            ->shouldReceive('getCustomersByLocation')
            ->once()
            ->with($location)
            ->andReturn($expectedData);

        $result = $this->controller->getCustomersByLocation($location);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($expectedData, $result['data']);
    }

    public function testGetCustomersByLocationWithException()
    {
        $location = 'New York';

        $this->mockLoadCustomers
            ->shouldReceive('getCustomersByLocation')
            ->once()
            ->with($location)
            ->andThrow(new AppException('Query failed', 500));

        $result = $this->controller->getCustomersByLocation($location);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Query failed', $result['message']);
    }

    public function testSearchCustomersByNameSuccess()
    {
        $searchTerm = 'Test';
        $expectedData = [
            ['customer' => 'CUST001', 'name' => 'Test Customer 1', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50],
            ['customer' => 'CUST002', 'name' => 'Test Customer 2', 'location' => 'California', 'miles' => 200, 'rate' => 3.00]
        ];

        $this->mockLoadCustomers
            ->shouldReceive('searchCustomersByName')
            ->once()
            ->with($searchTerm)
            ->andReturn($expectedData);

        $result = $this->controller->searchCustomersByName($searchTerm);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($expectedData, $result['data']);
    }

    public function testSearchCustomersByNameWithException()
    {
        $searchTerm = 'Test';

        $this->mockLoadCustomers
            ->shouldReceive('searchCustomersByName')
            ->once()
            ->with($searchTerm)
            ->andThrow(new AppException('Search failed', 500));

        $result = $this->controller->searchCustomersByName($searchTerm);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Search failed', $result['message']);
    }

    public function testSearchCustomersByNameEmptyResult()
    {
        $searchTerm = 'NonExistentCustomer';

        $this->mockLoadCustomers
            ->shouldReceive('searchCustomersByName')
            ->once()
            ->with($searchTerm)
            ->andReturn([]);

        $result = $this->controller->searchCustomersByName($searchTerm);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals([], $result['data']);
    }
}