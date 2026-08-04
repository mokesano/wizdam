<?php

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

declare(strict_types=1);

namespace Sangia\Library\Database;

use PDO;
use PDOException;
use Delight\Db\PdoDatabase;
use Delight\Db\TableGateway\TableGateway;

/**
 * Database Manager untuk koneksi MariaDB menggunakan delight-im
 * Mendukung multiple connections dan query builder sederhana
 */
class DatabaseManager
{
    private static ?DatabaseManager $instance = null;
    private array $connections = [];
    private string $defaultConnection = 'default';

    private function __construct()
    {
    }

    public static function getInstance(): DatabaseManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Setup koneksi database dari config
     */
    public function connect(array $config): void
    {
        $connectionName = $config['name'] ?? $this->defaultConnection;

        if (isset($this->connections[$connectionName])) {
            return; // Sudah terhubung
        }

        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                $config['host'],
                $config['port'] ?? 3306,
                $config['database']
            );

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $this->connections[$connectionName] = new PdoDatabase($pdo); // Cannot access private constructor Delight\Db\PdoDatabase::__construct() from DatabaseManager scope.
        } catch (PDOException $e) {
            throw new \RuntimeException("Gagal koneksi ke database: " . $e->getMessage());
        }
    }

    /**
     * Dapatkan instance PdoDatabase
     */
    public function getConnection(string $name = 'default'): PdoDatabase
    {
        if (!isset($this->connections[$name])) {
            throw new \RuntimeException("Koneksi '{$name}' tidak ditemukan");
        }

        return $this->connections[$name];
    }

    /**
     * Buat TableGateway untuk tabel tertentu
     */
    public function table(string $tableName, string $connection = 'default'): TableGateway // Undefined type 'Delight\Db\TableGateway\TableGateway'.
    {
        return new TableGateway($tableName, $this->getConnection($connection)); // Undefined type 'Delight\Db\TableGateway\TableGateway'.
    }

    /**
     * Mulai transaksi
     */
    public function beginTransaction(string $connection = 'default'): void
    {
        $this->getConnection($connection)->getPdo()->beginTransaction(); // Undefined method 'getPdo'.
    }

    /**
     * Commit transaksi
     */
    public function commit(string $connection = 'default'): void
    {
        $this->getConnection($connection)->getPdo()->commit(); // Undefined method 'getPdo'.
    }

    /**
     * Rollback transaksi
     */
    public function rollback(string $connection = 'default'): void
    {
        $this->getConnection($connection)->getPdo()->rollBack(); // Undefined method 'getPdo'.
    }

    /**
     * Jalankan query raw
     */
    public function query(string $sql, array $params = [], string $connection = 'default'): \PDOStatement
    {
        $stmt = $this->getConnection($connection)->getPdo()->prepare($sql); // Undefined method 'getPdo'.
        $stmt->execute($params);
        return $stmt;
    }
}
