<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PrivateWeb/AdminAnalyticsHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AdminAnalyticsHandler
 * @ingroup private_web
 *
 * @brief Handler for managing admin analytics functionality.
 */

namespace Sangia\Handlers\PrivateWeb;

use Sangia\Database\DBConnector;
use Sangia\Database\Models\ImpactScoreModel;
use Sangia\Services\Core\AuthManager;
use Sangia\Http\Request;
use Sangia\Http\Response;

class AdminAnalyticsHandler
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

    /** 
     * Versi Response object untuk index() - digunakan oleh router baru
     * @param \Sangia\Http\Request $request 
     */
    public function indexWithResponse(Request $request): Response
    {
        $this->auth->requireAdmin();

        // Ringkasan statistik platform
        $stats = [
            'total_researchers'   => $this->db->fetchOne('SELECT COUNT(*) AS n FROM researchers')['n'] ?? 0,
            'total_institutions'  => $this->db->fetchOne('SELECT COUNT(*) AS n FROM institutions')['n'] ?? 0,
            'total_journals'      => $this->db->fetchOne('SELECT COUNT(*) AS n FROM journals')['n'] ?? 0,
            'total_articles'      => $this->db->fetchOne('SELECT COUNT(*) AS n FROM articles')['n'] ?? 0,
            'total_users'         => $this->db->fetchOne('SELECT COUNT(*) AS n FROM users')['n'] ?? 0,
        ];

        // Tren harvesting per hari (7 hari terakhir)
        $harvestTrend = $this->db->fetchAll(
            'SELECT DATE(created_at) AS date, COUNT(*) AS inserted
             FROM articles
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC'
        );

        // Distribusi skor dampak
        $scoreDistribution = $this->db->fetchAll(
            'SELECT
               FLOOR(composite_score / 10) * 10 AS bucket,
               COUNT(*) AS count
             FROM impact_scores
             WHERE entity_type = "researcher"
               AND calculated_at = (
                   SELECT MAX(calculated_at) FROM impact_scores i2
                   WHERE i2.entity_type = impact_scores.entity_type
                     AND i2.entity_id   = impact_scores.entity_id
               )
             GROUP BY bucket
             ORDER BY bucket ASC'
        );

        // Top institusi berdasarkan rata-rata skor
        $topInstitutions = $this->db->fetchAll(
            'SELECT i.name, AVG(r.impact_score) AS avg_score, COUNT(r.id) AS researcher_count
             FROM institutions i
             JOIN researchers r ON r.affiliation_id = i.id
             GROUP BY i.id
             ORDER BY avg_score DESC
             LIMIT 10'
        );

        // Log aktivitas terbaru
        $recentLogs = $this->db->fetchAll(
            'SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 20'
        );

        return Response::react('AdminPage', [ // Undefined method 'react'.
            'stats'             => $stats,
            'harvestTrend'      => $harvestTrend,
            'scoreDistribution' => $scoreDistribution,
            'topInstitutions'   => $topInstitutions,
            'recentLogs'        => $recentLogs,
            'pageTitle'         => 'Admin Analytics – Sangia Scieco',
        ]);
    }
}
