<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PublicWeb/LoginHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LoginHandler
 * @ingroup public_web
 *
 * @brief Handler for managing login functionality.
 */

namespace Sangia\Handlers\PublicWeb;

use Sangia\Services\Core\AuthManager;
use Sangia\Http\Request;
use Sangia\Http\Response;

class LoginHandler
{
    public function __construct(
        private AuthManager $auth
    ) {
    }

    public function show(Request $request): Response
    {
        // Jika sudah login, redirect ke dashboard
        if ($this->auth->isLoggedIn()) {
            return Response::redirect('/dashboard');
        }

        return Response::react('LoginPage', [ // Undefined method 'react'.
            'pageTitle' => 'Masuk – Sangia Scieco',
        ]);
    }

    public function authenticate(Request $request): Response
    {
        $email    = $request->getBody('email', '');
        $password = $request->getBody('password', '');

        if (empty($email) || empty($password)) {
            return Response::json(['error' => 'Email dan password harus diisi.'], 400);
        }

        try {
            $this->auth->login($email, $password); // Undefined method 'login'.
            return Response::json(['success' => true, 'redirect' => '/dashboard']);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect('/');
    }
}
