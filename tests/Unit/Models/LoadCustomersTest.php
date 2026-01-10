<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\LoadCustomers;
use App\Models\Database;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;
use Mockery;
use PDO;
use PDOStatement;
use PDOException;

class LoadCustomersTest extends TestCase
{
    private LoadCustomers $loadCustomers;
    private $mockDatabase;
    private $mockPdo;
    private $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockDatabase = Mockery::mock('alias:' . Database::class);
        $this->mockPdo = Mockery::mock(PDO::class);
        $this->mockStatement = Mockery::mock(PDOStatement::class);
        
        $this->mockDatabase
            ->shouldReceive('getInstance')
            ->andReturnSelf();
        
        $this->mockDatabase
            ->shouldReceive('getConnection')
            ->andReturn($this->mockPdo);
        
        $this->loadCustomers = new LoadCustomers();
    }

    public function testGetAllCustomersSuccess()
    {
        $expectedData = [
            ['customer' => 'CUST001', 'name' => 'Test Customer 1', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50],
            ['customer' => 'CUST002', 'name' => 'Test Customer 2', 'location' => 'California', 'miles' => 200, 'rate' => 3.00]
        ];

        $this->mockPdo
            ->shouldReceive('prepare')
            ->with('SELECT customer, name, location, miles, rate FROM customers')
            ->once()
            ->andReturn($this->mockStatement);

        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturnNull();

        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn($expectedData);

        $result = $this->loadCustomers->getAllCustomers();

        $this->assertEquals($expectedData, $result);
    }

    public function testGetAllCustomersWithPDOException()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Failed to fetch customers');

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andThrow(new PDOException('Connection failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->loadCustomers->getAllCustomers();
    }

    public function testGetCustomerByIdSuccess()
    {
        $customerId = 'CUST001';
        $expectedData = ['comp_id' => 1, 'customer' => 'CUST001', 'name' => 'Test Customer', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50];

        $this->mockPdo
            ->shouldReceive('prepare')
            ->with('SELECT comp_id, customer, name, location, miles, rate FROM customers WHERE customer = :customerId')
            ->once()
            ->andReturn($this->mockStatement);

        $this->mockStatement
            ->shouldReceive('execute')
            ->with(['customerId' => $customerId])
            ->once()
            ->andReturnNull();

        $this->mockStatement
            ->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn($expectedData);

        $result = $this->loadCustomers->getCustomerById($customerId);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetCustomerByIdNotFound()
    {
        $customerId = 'NONEXISTENT';

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andReturn($this->mockStatement);

        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturnNull();

        $this->mockStatement
            ->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn(false);

        $result = $this->loadCustomers->getCustomerById($customerId);

        $this->assertNull($result);
    }

    public function testGetCustomerByIdWithPDOException()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Failed to fetch customer by ID');

        $customerId = 'CUST001';

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andThrow(new PDOException('Query failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->loadCustomers->getCustomerById($customerId);
    }

    public function testGetCustomersByLocationSuccess()
    {
        $location = 'New York';
        $expectedData = [
            ['customer' => 'CUST001', 'name' => 'Test Customer 1', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50],
            ['customer' => 'CUST003', 'name' => 'Test Customer 3', 'location' => 'New York', 'miles' => 150, 'rate' => 2.75]
        ];

        $this->mockPdo
            ->shouldReceive('prepare')
            ->with('SELECT customer, name, location, miles, rate FROM customers WHERE location = :location')
            ->once()
            ->andReturn($this->mockStatement);

        $this->mockStatement
            ->shouldReceive('execute')
            ->with(['location' => $location])
            ->once()
            ->andReturnNull();

        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn($expectedData);

        $result = $this->loadCustomers->getCustomersByLocation($location);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetCustomersByLocationWithPDOException()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Failed to fetch customers by location');

        $location = 'New York';

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andThrow(new PDOException('Location query failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->loadCustomers->getCustomersByLocation($location);
    }

    public function testSearchCustomersByNameSuccess()
    {
        $searchTerm = 'Test';
        $expectedData = [
            ['customer' => 'CUST001', 'name' => 'Test Customer 1', 'location' => 'New York', 'miles' => 100, 'rate' => 2.50],
            ['customer' => 'CUST002', 'name' => 'Test Customer 2', 'location' => 'California', 'miles' => 200, 'rate' => 3.00]
        ];

        $this->mockPdo
            ->shouldReceive('prepare')
            ->with('SELECT customer, name, location, miles, rate FROM customers WHERE name LIKE :searchTerm')
            ->once()
            ->andReturn($this->mockStatement);

        $this->mockStatement
            ->shouldReceive('execute')
            ->with(['searchTerm' => "%{$searchTerm}%"])
            ->once()
            ->andReturnNull();

        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn($expectedData);

        $result = $this->loadCustomers->searchCustomersByName($searchTerm);

        $this->assertEquals($expectedData, $result);
    }

    public function testSearchCustomersByNameWithPDOException()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Failed to search customers by name');

        $searchTerm = 'Test';

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andThrow(new PDOException('Search query failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->loadCustomers->searchCustomersByName($searchTerm);
    }

    public function testSearchCustomersByNameEmptyResult()
    {
        $searchTerm = 'NonExistentCustomer';

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andReturn($this->mockStatement);

        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturnNull();

        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn([]);

        $result = $this->loadCustomers->searchCustomersByName($searchTerm);

        $this->assertEquals([], $result);
    }
}