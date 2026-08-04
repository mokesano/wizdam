<?php

declare(strict_types=1);

/**
 * @file app/Handlers/PublicWeb/ResearcherProfileHandler.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ResearcherProfileHandler
 * @ingroup public_web
 *
 * @brief Handler for managing researcher profile functionality.
 */

namespace Sangia\Handlers\PublicWeb;

use Sangia\Database\DBConnector;
use Sangia\Database\Models\ResearcherModel;
use Sangia\Database\Models\ImpactScoreModel;
use Sangia\Services\Core\AuthManager;
use Sangia\Services\Core\ProfileManager;
use Sangia\Services\SangiaApi\ImpactScoreClient;
use Sangia\Http\Request;
use Sangia\Http\Response;

class ResearcherProfileHandler
{
    private ResearcherModel $researcherModel;
    private ImpactScoreModel $scoreModel;

    public function __construct(
        private DBConnector $db,
        private AuthManager $auth
    ) {
        $this->researcherModel = new ResearcherModel();
        $this->scoreModel      = new ImpactScoreModel();
    }

    /** Halaman utama: daftar peneliti dengan impact tertinggi. */
    public function indexWithResponse(Request $request): Response
    {
        $field       = $request->getQuery('field', 'all');
        $search      = trim($request->getQuery('q', ''));
        $researchers = $search
            ? $this->researcherModel->search($search)
            : $this->researcherModel->getTopByImpact(50, $field);

        $avgPillars = $this->scoreModel->getAveragePillars('researcher');

        return Response::react('ResearcherListPage', [ // Undefined method 'react'.
            'researchers' => $researchers,
            'avgPillars'  => $avgPillars,
            'field'       => $field,
            'search'      => $search,
            'pageTitle'   => 'Peneliti Terdampak – Sangia Scieco',
        ]);
    }

    /** Profil detail peneliti berdasarkan ORCID. */
    public function showWithResponse(string $orcid): Response
    {
        // Cari di DB dulu — field name sesuai schema full: orcid_id, full_name
        $researcher = $this->db->fetchOne(
            'SELECT r.*, i.name AS institution_name, i.province, i.city
             FROM researchers r
             LEFT JOIN institutions i ON r.institution_id = i.id
             WHERE r.orcid_id = ?',
            [$orcid]
        );

        // Jika belum ada di DB, sync dari ORCID via ProfileManager
        if (!$researcher) {
            try {
                $profileManager = new ProfileManager();
                $id             = $profileManager->syncFromOrcid($orcid);
                $researcher     = $this->db->fetchOne(
                    'SELECT r.*, i.name AS institution_name, i.province, i.city
                     FROM researchers r
                     LEFT JOIN institutions i ON r.institution_id = i.id
                     WHERE r.id = ?',
                    [(int) $id]
                );
            } catch (\Throwable) {
                return Response::error("Peneliti dengan ORCID {$orcid} tidak ditemukan.", 404);
            }
        }

        if (!$researcher) {
            return Response::error('Peneliti tidak ditemukan.', 404);
        }

        $researcherId = (int) $researcher['id'];

        // Impact score dari DB
        $scoreClient  = new ImpactScoreClient();
        $score        = $scoreClient->getLatest('researcher', $researcherId);
        $scoreHistory = $this->scoreModel->getHistory('researcher', $researcherId);

        // SDG tags
        $sdgTags = [];
        if ($score && !empty($score['sdg_tags'])) {
            $sdgTags = is_string($score['sdg_tags'])
                ? (json_decode($score['sdg_tags'], true) ?? [])
                : $score['sdg_tags'];
        }

        // Artikel terbaru — tabel publications, field sesuai schema full
        $recentArticles = $this->db->fetchAll(
            'SELECT p.id, p.doi, p.title, p.publication_year AS year,
                    p.cited_by_count AS citations, p.sangia_score AS impact_score,
                    p.journal_title AS journal_name
             FROM publications p
             JOIN publication_authors pa ON pa.publication_id = p.id
             WHERE pa.researcher_id = ?
             ORDER BY p.publication_year DESC, p.cited_by_count DESC
             LIMIT 10',
            [$researcherId]
        );

        return Response::react('ResearcherProfilePage', [ // Undefined method 'react'.
            'researcher'     => $researcher,
            'score'          => $score,
            'scoreHistory'   => $scoreHistory,
            'sdgTags'        => $sdgTags,
            'recentArticles' => $recentArticles,
            'pageTitle'      => ($researcher['full_name'] ?? $orcid) . ' – Sangia Scieco',
        ]);
    }
}
