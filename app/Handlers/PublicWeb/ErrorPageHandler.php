<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PublicWeb/ErrorPageHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ErrorPageHandler
 * @ingroup public_web
 *
 * @brief Handler for managing error page functionality.
 */

namespace Sangia\Handlers\PublicWeb;

use Sangia\Http\Request;
use Sangia\Http\Response;

class ErrorPageHandler
{
    public function show(int $code = 404, string $message = ''): Response
    {
        $messages = [
            404 => 'Halaman yang Anda cari tidak ditemukan.',
            403 => 'Akses ditolak.',
            500 => 'Terjadi kesalahan pada server.',
            503 => 'Layanan sedang tidak tersedia.',
        ];

        $message = $message ?: ($messages[$code] ?? 'Terjadi kesalahan.');

        return Response::react('ErrorPage', [ // Undefined method 'react'.
            'code'    => $code,
            'message' => $message,
            'pageTitle' => "Error $code – Sangia Scieco",
        ]);
    }
}
