<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PublicWeb/JournalProfileHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalProfileHandler
 * @ingroup public_web
 *
 * @brief Handler for managing journal profile functionality.
 */

namespace Sangia\Handlers\PublicWeb;

use Sangia\Database\DBConnector;
use Sangia\Database\Models\JournalModel;
use Sangia\Database\Models\ImpactScoreModel;
use Sangia\Services\Core\AuthManager;
use Sangia\Services\SangiaApi\IndexingIntegrator;
use Sangia\Services\SangiaApi\ImpactScoreClient;
use Sangia\Http\Request;
use Sangia\Http\Response;

class JournalProfileHandler
{
    private JournalModel $journalModel;
    private ImpactScoreModel $scoreModel;

    public function __construct(
        private DBConnector $db,
        private AuthManager $auth
    ) {
        $this->journalModel = new JournalModel();
        $this->scoreModel   = new ImpactScoreModel();
    }

    public function show(string $issn): void
    {
        $response = $this->showWithResponse($issn);
        $response->send();
    }

    /** Versi Response object untuk show() - digunakan oleh router baru */
    public function showWithResponse(string $issn): Response
    {
        $journal = $this->journalModel->findByIssn($issn);

        if (!$journal) {
            return Response::error("Jurnal dengan ISSN $issn tidak ditemukan.", 404);
        }

        $articles     = $this->journalModel->getRecentArticles((int) $journal['id']);
        $indexing     = $this->journalModel->getIndexingMetrics((int) $journal['id']);

        $scoreClient  = new ImpactScoreClient();
        $score        = $scoreClient->getLatest('journal', (int) $journal['id']);
        $scoreHistory = $this->scoreModel->getHistory('journal', (int) $journal['id']);

        // Cek indeksasi real-time jika diminta
        $liveIndexing = null;
        if (isset($_GET['check_indexing'])) {
            $integrator   = new IndexingIntegrator();
            $liveIndexing = $integrator->checkByIssn($journal['issn']);
        }

        return Response::react('JournalProfilePage', [ // Undefined method 'react'.
            'journal'      => $journal,
            'articles'     => $articles,
            'indexing'     => $indexing,
            'liveIndexing' => $liveIndexing,
            'score'        => $score,
            'scoreHistory' => $scoreHistory,
            'pageTitle'    => ($journal['title'] ?? "Jurnal ISSN $issn") . ' – Sangia Scieco',
        ]);
    }
}
