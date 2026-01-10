<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\LoadData;
use App\Models\Database;
use App\Exceptions\AppException;
use App\Middleware\LogMiddleware;
use Mockery;
use PDO;
use PDOStatement;
use PDOException;

class LoadDataTest extends TestCase
{
    private LoadData $loadData;
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
            ->with('mysql')
            ->andReturn($this->mockPdo);
        
        $this->loadData = new LoadData();
    }

    public function testGetAllSuccess()
    {
        $expectedData = [
            ['id' => 1, 'customer' => 'CUST001', 'status2' => 'active', 'date_arv' => '2024-01-01'],
            ['id' => 2, 'customer' => 'CUST002', 'status2' => 'approved', 'date_arv' => '2024-01-02']
        ];

        $this->mockPdo
            ->shouldReceive('prepare')
            ->with('SELECT * FROM ldata ORDER BY date_arv DESC')
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

        $result = $this->loadData->getAll();

        $this->assertEquals($expectedData, $result);
    }

    public function testGetAllWithPDOException()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Failed to fetch load data');

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andThrow(new PDOException('Connection failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->loadData->getAll();
    }

    public function testGetActiveSuccess()
    {
        $expectedData = [
            ['id' => 1, 'customer' => 'CUST001', 'status2' => 'active', 'date_arv' => '2024-01-01'],
            ['id' => 3, 'customer' => 'CUST003', 'status2' => 'manual', 'date_arv' => '2024-01-03'],
            ['id' => 4, 'customer' => 'CUST004', 'status2' => 'approved', 'date_arv' => '2024-01-04']
        ];

        $this->mockPdo
            ->shouldReceive('prepare')
            ->with("SELECT * FROM ldata WHERE status2 IN ('active', 'manual', 'approved') ORDER BY date_arv DESC")
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

        $result = $this->loadData->getActive();

        $this->assertEquals($expectedData, $result);
    }

    public function testGetActiveWithPDOException()
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Failed to fetch active loads');

        $this->mockPdo
            ->shouldReceive('prepare')
            ->once()
            ->andThrow(new PDOException('Query failed'));

        Mockery::mock('alias:' . LogMiddleware::class)
            ->shouldReceive('logError')
            ->once();

        $this->loadData->getActive();
    }

    public function testGetActiveEmptyResult()
    {
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

        $result = $this->loadData->getActive();

        $this->assertEquals([], $result);
    }

    public function testGetAllLargeDataset()
    {
        $largeDataset = [];
        for ($i = 1; $i <= 100; $i++) {
            $largeDataset[] = [
                'id' => $i,
                'customer' => "CUST" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status2' => $i % 2 === 0 ? 'active' : 'approved',
                'date_arv' => '2024-01-' . str_pad($i % 31 + 1, 2, '0', STR_PAD_LEFT)
            ];
        }

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
            ->andReturn($largeDataset);

        $result = $this->loadData->getAll();

        $this->assertCount(100, $result);
        $this->assertEquals($largeDataset, $result);
    }

    public function testGetActiveFilteringLogic()
    {
        $mixedStatusData = [
            ['id' => 1, 'customer' => 'CUST001', 'status2' => 'active', 'date_arv' => '2024-01-01'],
            ['id' => 2, 'customer' => 'CUST002', 'status2' => 'manual', 'date_arv' => '2024-01-02'],
            ['id' => 3, 'customer' => 'CUST003', 'status2' => 'approved', 'date_arv' => '2024-01-03']
        ];

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
            ->andReturn($mixedStatusData);

        $result = $this->loadData->getActive();

        $this->assertCount(3, $result);
        
        foreach ($result as $load) {
            $this->assertContains($load['status2'], ['active', 'manual', 'approved']);
        }
    }
}