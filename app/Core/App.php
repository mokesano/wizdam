<?php

declare(strict_types=1);

/**
 * @file app/Core/App.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class App
 * @ingroup core
 *
 * @brief Application container for managing dependencies and bootstrap process.
 */

namespace Sangia\Core;

use PDO;
use Sangia\Database\DBConnector;
use Sangia\Services\Core\AuthManager;
use Sangia\Services\SangiaApi\SangiaGateway;
use Sangia\Services\ApiKeyManager;
use Sangia\Jobs\QueueManager;
use Dotenv\Dotenv;

/**
 * Application Container - Mengelola dependency injection dan bootstrap aplikasi.
 */
class App
{
    private static ?App $instance = null;

    private array $config = [];
    private ?PDO $db = null;
    private ?AuthManager $auth = null;
    private ?SangiaGateway $apiClient = null;
    private ?ApiKeyManager $apiKeyManager = null;
    private ?QueueManager $queueManager = null;

    private function __construct()
    {
    }

    /**
     * Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bootstrap aplikasi: load env, config, database, auth, services
     */
    public function bootstrap(string $basePath): self
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->safeLoad();

        // Load config
        $this->config = require $basePath . '/config/app.php';

        // Setup timezone
        date_default_timezone_set($this->config['timezone'] ?? 'Asia/Makassar');

        // Init database
        $this->db = DBConnector::getInstance()->getPdo();

        // Init Auth
        $this->auth = new AuthManager($this->db);

        // Init services (lazy loading)
        // Services akan diinisialisasi saat pertama kali diakses

        return $this;
    }

    /**
     * Getters untuk dependencies
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    public function getAuth(): AuthManager
    {
        return $this->auth;
    }

    public function getAuthService(): AuthManager
    {
        return $this->auth;
    }

    public function getDatabase(): PDO
    {
        return $this->db;
    }

    public function getApiClient(): SangiaGateway
    {
        if ($this->apiClient === null) {
            $this->apiClient = new SangiaGateway();
        }
        return $this->apiClient;
    }

    public function getApiKeyManager(): ApiKeyManager
    {
        if ($this->apiKeyManager === null) {
            $this->apiKeyManager = new ApiKeyManager($this->db); // Too many arguments. Expected 0. Found 1.
        }
        return $this->apiKeyManager;
    }

    public function getQueueManager(): QueueManager
    {
        if ($this->queueManager === null) {
            $this->queueManager = new QueueManager($this->db);
        }
        return $this->queueManager;
    }

    /**
     * Helper untuk membuat handler dengan dependency injection otomatis
     */
    public function makeHandler(string $className): object
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $param) {
            $type = $param->getType();

            if ($type === null) {
                continue;
            }

            $typeName = $type->getName();

            switch ($typeName) {
                case 'PDO':
                    $dependencies[] = $this->getDb();
                    break;
                case 'Sangia\Database\DBConnector':
                    $dependencies[] = DBConnector::getInstance();
                    break;
                case 'Sangia\Services\Core\AuthManager':
                    $dependencies[] = $this->getAuth();
                    break;
                case 'Sangia\Services\SangiaApi\SangiaGateway':
                    $dependencies[] = $this->getApiClient();
                    break;
                case 'Sangia\Services\ApiKeyManager':
                    $dependencies[] = $this->getApiKeyManager();
                    break;
                case 'Sangia\Jobs\QueueManager':
                    $dependencies[] = $this->getQueueManager();
                    break;
                default:
                    if ($param->isDefaultValueAvailable()) {
                        $dependencies[] = $param->getDefaultValue();
                    }
                    break;
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
