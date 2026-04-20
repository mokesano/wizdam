<?php

declare(strict_types=1);

use Wizdam\Http\Router;
use Wizdam\Http\Request;
use Wizdam\Http\Response;
use Wizdam\Core\App;
use Wizdam\Http\Middleware\AuthMiddleware;
use Wizdam\Http\Middleware\AdminMiddleware;

/**
 * Definisi semua route aplikasi.
 * 
 * @param App $app Application container
 * @return Router Configured router instance
 */
return function (App $app): Router {
    $router = new Router();

    // Middleware instances
    $authMiddleware = new AuthMiddleware($app->getAuth());
    $adminMiddleware = new AdminMiddleware($app->getAuth());

    // ─── PUBLIC ROUTES ────────────────────────────────────────────────────────

    // Halaman Utama - Daftar Peneliti
    $router->get('/', function (Request $request) use ($app) {
        $handler = $app->makeHandler(\Wizdam\Handlers\PublicWeb\ResearcherProfileHandler::class);
        return $handler->indexWithResponse($request);
    });

    // Profil Peneliti: /researcher/{orcid}
    $router->get('/researcher/{orcid}', function (Request $request, string $orcid) use ($app) {
        $handler = $app->makeHandler(\Wizdam\Handlers\PublicWeb\ResearcherProfileHandler::class);
        return $handler->showWithResponse($orcid);
    });

    // Profil Institusi: /institution/{id}
    $router->get('/institution/{id:\d+}', function (Request $request, int $id) use ($app) {
        $handler = $app->makeHandler(\Wizdam\Handlers\PublicWeb\InstitutionProfileHandler::class);
        return $handler->showWithResponse($id);
    });

    // Profil Jurnal: /journal/{issn}
    $router->get('/journal/{issn}', function (Request $request, string $issn) use ($app) {
        $handler = $app->makeHandler(\Wizdam\Handlers\PublicWeb\JournalProfileHandler::class);
        return $handler->showWithResponse($issn);
    });

    // ─── AUTH ROUTES ──────────────────────────────────────────────────────────

    // Login page & handle
    $router->any('/auth/login', function (Request $request) use ($app) {
        $authManager = $app->getAuth();
        return $authManager->handleLoginPageWithResponse($app->getTwig(), $request->method);
    });

    // Logout
    $router->get('/auth/logout', function (Request $request) use ($app) {
        $app->getAuth()->logout();
        return Response::redirect('/');
    });

    // ORCID Callback
    $router->get('/auth/orcid-callback', function (Request $request) use ($app) {
        $app->getAuth()->handleOrcidCallback();
        return Response::redirect('/dashboard');
    });

    // ─── PROTECTED ROUTES (Requires Login) ────────────────────────────────────

    // Dashboard User
    $router->get('/dashboard', function (Request $request) use ($app, $authMiddleware) {
        if ($response = $authMiddleware->handle($request, [])) {
            return $response;
        }
        $handler = $app->makeHandler(\Wizdam\Handlers\PrivateWeb\UserDashboardHandler::class);
        return $handler->indexWithResponse($request);
    });

    // Admin Analytics (Admin only)
    $router->get('/admin', function (Request $request) use ($app, $adminMiddleware) {
        if ($response = $adminMiddleware->handle($request, [])) {
            return $response;
        }
        $handler = $app->makeHandler(\Wizdam\Handlers\PrivateWeb\AdminAnalyticsHandler::class);
        return $handler->indexWithResponse($request);
    });

    $router->get('/admin/{path}', function (Request $request, string $path) use ($app, $adminMiddleware) {
        if ($response = $adminMiddleware->handle($request, [])) {
            return $response;
        }
        $handler = $app->makeHandler(\Wizdam\Handlers\PrivateWeb\AdminAnalyticsHandler::class);
        return $handler->indexWithResponse($request);
    });

    // ─── TOOLS ROUTES ─────────────────────────────────────────────────────────

    // Image Resizer
    $router->any('/tools/image-resizer', function (Request $request) use ($app) {
        $handler = $app->makeHandler(\Wizdam\Handlers\Tools\ImageResizerHandler::class);
        return $handler->handleWithResponse($request);
    });

    // PDF Compress
    $router->any('/tools/pdf-compress', function (Request $request) use ($app) {
        $handler = $app->makeHandler(\Wizdam\Handlers\Tools\PdfCompressHandler::class);
        return $handler->handleWithResponse($request);
    });

    // ─── API ROUTES ───────────────────────────────────────────────────────────

    // Crawler Receiver
    $router->any('/api/crawler', function (Request $request) use ($app) {
        $handler = new \Wizdam\Services\Harvesting\CrawlerReceiver();
        return $handler->receiveWithResponse($request);
    });

    return $router;
};
