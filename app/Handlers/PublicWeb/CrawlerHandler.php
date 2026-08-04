<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PublicWeb/CrawlerHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SangiaCrawlerHandler
 * @ingroup public_web
 *
 * @brief Handler for Sangia Crawler tool page.
 */

namespace Sangia\Handlers\PublicWeb;

use Sangia\Http\Request;
use Sangia\Http\Response;

class CrawlerHandler
{
    public function index(Request $request): Response
    {
        return Response::react('SangiaCrawlerPage', [ // Undefined method 'react'.
            'pageTitle' => 'Sangia Crawler – Sangia Scieco',
        ]);
    }
}
