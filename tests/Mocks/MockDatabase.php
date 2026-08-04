<?php

declare(strict_types=1);

/**
 * @file library/Database/DatabaseManager.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DatabaseManager
 * @ingroup Database
 *
 * @brief Manager for handling database connections and operations.
 */

namespace Sangia\Tests\Mocks;

use PDO;
use PDOStatement;

/**
 * Mock PDO untuk testing tanpa database sungguhan
 */
class MockPDO extends PDO
{
    private array $data = [];
    private int $lastInsertId = 0;

    public function __construct(array $initialData = [])
    {
        // Constructor kosong - tidak terhubung ke database sungguhan
        $this->data = $initialData;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new MockPDOStatement($this, $query); // Cannot access private constructor Sangia\Tests\Mocks\MockPDOStatement::__construct() from MockPDO scope.
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return (string) $this->lastInsertId;
    }

    public function setLastInsertId(int $id): void
    {
        $this->lastInsertId = $id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

/**
 * Mock PDOStatement untuk testing
 */
class MockPDOStatement extends PDOStatement
{
    private MockPDO $pdo;
    private string $query;
    private array $params = [];
    private array $resultData = [];
    private int $currentIndex = 0;

    private function __construct() {}

    public static function create(MockPDO $pdo, string $query): self
    {
        $stmt = new self();
        $stmt->pdo = $pdo;
        $stmt->query = $query;
        return $stmt;
    }

    public function execute(?array $params = null): bool
    {
        $this->params = $params;
        
        // Simulasi hasil berdasarkan query
        if (stripos($this->query, 'SELECT') === 0) {
            $this->resultData = $this->pdo->getData();
        } elseif (stripos($this->query, 'INSERT') === 0) {
            $this->pdo->setLastInsertId($this->pdo->lastInsertId() + 1);
            $this->resultData = [];
        } else {
            $this->resultData = [];
        }
        
        $this->currentIndex = 0;
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_ASSOC, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->currentIndex >= count($this->resultData)) {
            return false;
        }
        return $this->resultData[$this->currentIndex++];
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        $this->currentIndex = count($this->resultData);
        return $this->resultData;
    }

    public function bindParam(int|string $param, mixed &$var, int $type = PDO::PARAM_STRING, int $maxLength = 0, mixed $driverOptions = null): bool // Undefined class constant 'PARAM_STRING'.
    {
        return true;
    }

    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STRING): bool // Undefined class constant 'PARAM_STRING'.
    {
        $this->params[$param] = $value;
        return true;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
