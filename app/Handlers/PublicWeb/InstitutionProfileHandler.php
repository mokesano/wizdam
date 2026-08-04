<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PublicWeb/InstitutionProfileHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionProfileHandler
 * @ingroup public_web
 *
 * @brief Handler for managing institution profile functionality.
 */

namespace Sangia\Handlers\PublicWeb;

use Sangia\Database\DBConnector;
use Sangia\Database\Models\InstitutionModel;
use Sangia\Database\Models\ImpactScoreModel;
use Sangia\Services\Core\AuthManager;
use Sangia\Services\SangiaApi\ImpactScoreClient;
use Sangia\Http\Request;
use Sangia\Http\Response;

class InstitutionProfileHandler
{
    private InstitutionModel $institutionModel;
    private ImpactScoreModel $scoreModel;

    public function __construct(
        private DBConnector $db,
        private AuthManager $auth
    ) {
        $this->institutionModel = new InstitutionModel();
        $this->scoreModel       = new ImpactScoreModel();
    }

    public function show(int $id): void
    {
        $response = $this->showWithResponse($id);
        $response->send();
    }

    /** Versi Response object untuk show() - digunakan oleh router baru */
    public function showWithResponse(int $id): Response
    {
        $institution = $this->institutionModel->findWithResearcherCount($id);

        if (!$institution) {
            return Response::error("Institusi dengan ID $id tidak ditemukan.", 404);
        }

        $researchers  = $this->institutionModel->getResearchers($id);

        $scoreClient  = new ImpactScoreClient();
        $score        = $scoreClient->getLatest('institution', $id);
        $scoreHistory = $this->scoreModel->getHistory('institution', $id);

        // Tren publikasi per tahun
        $pubTrend = $this->db->fetchAll(
            'SELECT a.year, COUNT(a.id) AS total_articles, SUM(a.citations) AS total_citations
             FROM articles a
             JOIN article_authors aa ON aa.article_id = a.id
             JOIN researchers r ON aa.researcher_id = r.id
             WHERE r.affiliation_id = ?
             GROUP BY a.year
             ORDER BY a.year ASC',
            [$id]
        );

        return Response::react('InstitutionProfilePage', [ // Undefined method 'react'.
            'institution'  => $institution,
            'researchers'  => $researchers,
            'score'        => $score,
            'scoreHistory' => $scoreHistory,
            'pubTrend'     => $pubTrend,
            'pageTitle'    => ($institution['name'] ?? "Institusi #$id") . ' – Sangia Scieco',
        ]);
    }
}
