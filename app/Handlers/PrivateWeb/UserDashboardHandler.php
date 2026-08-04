<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PrivateWeb/UserDashboardHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserDashboardHandler
 * @ingroup private_web
 *
 * @brief Handler for managing user dashboard functionality.
 */

namespace Sangia\Handlers\PrivateWeb;

use Sangia\Database\DBConnector;
use Sangia\Database\Models\ResearcherModel;
use Sangia\Database\Models\ImpactScoreModel;
use Sangia\Services\Core\AuthManager;
use Sangia\Services\SangiaApi\ImpactScoreClient;
use Sangia\Http\Request;
use Sangia\Http\Response;

class UserDashboardHandler
{
    public function __construct(
        private DBConnector $db,
        private AuthManager $auth
    ) {
    }

    public function index(): void
    {
        $response = $this->indexWithResponse(request()); // Undefined function 'Sangia\Handlers\PrivateWeb\request'.
        $response->send();
    }

    /** Versi Response object untuk index() - digunakan oleh router baru */
    public function indexWithResponse(Request $request): Response
    {
        $this->auth->requireLogin();
        $userId = $this->auth->getUserId();

        // Profil peneliti milik user ini
        $researcher = $this->db->fetchOne(
            'SELECT r.* FROM researchers r
             JOIN user_researcher_links url ON url.researcher_id = r.id
             WHERE url.user_id = ?
             LIMIT 1',
            [$userId]
        );

        $score        = null;
        $scoreHistory = [];
        $recentWork   = [];

        if ($researcher) {
            $scoreClient  = new ImpactScoreClient();
            $score        = $scoreClient->getLatest('researcher', (int) $researcher['id']);
            $scoreHistory = (new ImpactScoreModel())->getHistory('researcher', (int) $researcher['id']);

            $recentWork = $this->db->fetchAll(
                'SELECT a.title, a.year, a.citations, a.impact_score, j.title AS journal_name
                 FROM articles a
                 JOIN article_authors aa ON aa.article_id = a.id
                 LEFT JOIN journals j ON a.journal_id = j.id
                 WHERE aa.researcher_id = ?
                 ORDER BY a.year DESC LIMIT 5',
                [$researcher['id']]
            );
        }

        // Notifikasi user
        $notifications = $this->db->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10',
            [$userId]
        );

        return Response::react('DashboardPage', [ // Undefined method 'react'.
            'researcher'    => $researcher,
            'score'         => $score,
            'scoreHistory'  => $scoreHistory,
            'recentWork'    => $recentWork,
            'notifications' => $notifications,
            'pageTitle'     => 'Dashboard Saya – Sangia Scieco',
        ]);
    }
}
