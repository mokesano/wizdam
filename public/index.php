<?php

declare(strict_types=1);

/**
 * Application Entry Point
 * 
 * File ini hanya bertugas sebagai bootstrap aplikasi.
 * Semua logika routing dan bisnis ditangani oleh komponen terpisah.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_START', microtime(true));

// Autoload
require BASE_PATH . '/vendor/autoload.php';

// Bootstrap aplikasi
$app = \Wizdam\Core\App::getInstance()->bootstrap(BASE_PATH);

// Load routes
$routerFactory = require BASE_PATH . '/config/routes.php';
$router = $routerFactory($app);

// Dispatch request
$request = \Wizdam\Http\Request::fromGlobals();
$response = $router->dispatch($request);

// Send response
$response->send();

