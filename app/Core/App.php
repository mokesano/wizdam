<?php

declare(strict_types=1);

namespace Wizdam\Core;

use PDO;
use Twig\Environment;
use Wizdam\Database\DBConnector;
use Wizdam\Services\Core\AuthManager;
use Dotenv\Dotenv;

/**
 * Application Container - Mengelola dependency injection dan bootstrap aplikasi.
 */
class App
{
    private static ?App $instance = null;

    private array $config = [];
    private ?PDO $db = null;
    private ?Environment $twig = null;
    private ?AuthManager $auth = null;

    private function __construct() {}

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
     * Bootstrap aplikasi: load env, config, database, twig, auth
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

        // Init Twig
        $loader = new \Twig\Loader\FilesystemLoader($basePath . '/views');
        $this->twig = new Environment($loader, [
            'cache' => $this->config['twig_cache'] ? $basePath . '/library/cache/twig' : false,
            'debug' => $this->config['debug'],
        ]);

        if ($this->config['debug']) {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        $this->twig->addGlobal('app', $this->config);

        // Init Auth
        $this->auth = new AuthManager($this->db);
        $this->twig->addGlobal('currentUser', $this->auth->isLoggedIn() ? $this->auth->getUserId() : null);

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

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getAuth(): AuthManager
    {
        return $this->auth;
    }

    /**
     * Helper untuk membuat handler dengan dependency injection otomatis
     */
    public function makeHandler(string $className): object
    {
        return new $className($this->getDb(), $this->getTwig(), $this->getAuth());
    }
}
