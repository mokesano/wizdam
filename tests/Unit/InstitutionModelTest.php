<?php

declare(strict_types=1);

/**
 * @file tests/Unit/InstitutionModelTest.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionModelTest
 * @ingroup Tests
 *
 * @brief Unit tests for the InstitutionModel.
 */

namespace Sangia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sangia\Database\Models\InstitutionModel;
use Sangia\Database\DBConnector;

/**
 * Unit Test untuk InstitutionModel
 */
class InstitutionModelTest extends TestCase
{
    protected function setUp(): void
    {
        DBConnector::resetInstance();
    }

    protected function tearDown(): void
    {
        DBConnector::resetInstance();
    }

    private function isDatabaseAvailable(): bool
    {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbPort = getenv('DB_PORT') ?: '3306';
        
        $connection = @fsockopen($dbHost, (int)$dbPort, $errno, $errstr, 1);
        if ($connection !== false) {
            fclose($connection);
            return true;
        }
        return false;
    }

    public function testModelInstantiation(): void
    {
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped("Database tidak tersedia. Test ini memerlukan koneksi database.");
        }
        
        $model = new InstitutionModel(DBConnector::getInstance()); // Too many arguments. Expected 0. Found 1.
        $this->assertInstanceOf(InstitutionModel::class, $model);
    }

    public function testModelHasRequiredMethods(): void
    {
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped("Database tidak tersedia. Test ini memerlukan koneksi database.");
        }
        
        $model = new InstitutionModel(DBConnector::getInstance()); // Too many arguments. Expected 0. Found 1.
        
        $this->assertTrue(method_exists($model, 'findById'));
        $this->assertTrue(method_exists($model, 'findAll'));
        $this->assertTrue(method_exists($model, 'create'));
        $this->assertTrue(method_exists($model, 'update'));
        $this->assertTrue(method_exists($model, 'delete'));
    }

    public function testFindByIdMethodSignature(): void
    {
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped("Database tidak tersedia. Test ini memerlukan koneksi database.");
        }
        
        $model = new InstitutionModel(DBConnector::getInstance()); // Too many arguments. Expected 0. Found 1.
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('findById');
        
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testFindAllReturnsArray(): void
    {
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped("Database tidak tersedia. Test ini memerlukan koneksi database.");
        }
        
        $model = new InstitutionModel(DBConnector::getInstance()); // Too many arguments. Expected 0. Found 1.
        $result = $model->findAll(); // Undefined method 'findAll'.
        
        $this->assertIsArray($result);
    }
}
