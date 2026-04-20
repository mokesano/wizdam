<?php

declare(strict_types=1);

namespace Wizdam\Http\Middleware;

use Wizdam\Http\Request;
use Wizdam\Http\Response;
use Wizdam\Services\Core\AuthManager;

/**
 * Middleware untuk memastikan user adalah admin.
 */
class AdminMiddleware
{
    public function __construct(
        private AuthManager $auth
    ) {}

    public function handle(Request $request, array $params): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect('/auth/login');
        }

        if (!$this->auth->isAdmin()) {
            return Response::error('Akses ditolak. Diperlukan hak akses admin.', 403);
        }

        return null; // Lanjut ke handler
    }
}
