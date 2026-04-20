<?php

declare(strict_types=1);

namespace Wizdam\Handlers\PublicWeb;

use Wizdam\Database\DBConnector;
use Wizdam\Database\Models\ResearcherModel;
use Wizdam\Database\Models\ImpactScoreModel;
use Wizdam\Services\Core\AuthManager;
use Wizdam\Services\Core\ProfileManager;
use Wizdam\Services\SangiaApi\ImpactScoreClient;
use Wizdam\Services\SangiaApi\SdgIntegrator;
use Wizdam\Http\Request;
use Wizdam\Http\Response;

class ResearcherProfileHandler
{
    private ResearcherModel  $researcherModel;
    private ImpactScoreModel $scoreModel;

    public function __construct(
        private DBConnector    $db,
        private \Twig\Environment $twig,
        private AuthManager    $auth
    ) {
        $this->researcherModel = new ResearcherModel();
        $this->scoreModel      = new ImpactScoreModel();
    }

    /** Halaman utama: daftar peneliti dengan impact tertinggi. */
    public function index(): void
    {
        $field       = $_GET['field'] ?? 'all';
        $search      = trim($_GET['q'] ?? '');
        $researchers = $search
            ? $this->researcherModel->search($search)
            : $this->researcherModel->getTopByImpact(50, $field);

        $avgPillars = $this->scoreModel->getAveragePillars('researcher');

        echo $this->twig->render('pages/public/researcher_list.twig', [
            'researchers' => $researchers,
            'avgPillars'  => $avgPillars,
            'field'       => $field,
            'search'      => $search,
            'pageTitle'   => 'Peneliti Terdampak – Wizdam AI-Sikola',
        ]);
    }

    /** Versi Response object untuk index() - digunakan oleh router baru */
    public function indexWithResponse(Request $request): Response
    {
        $field       = $request->getQuery('field', 'all');
        $search      = trim($request->getQuery('q', ''));
        $researchers = $search
            ? $this->researcherModel->search($search)
            : $this->researcherModel->getTopByImpact(50, $field);

        $avgPillars = $this->scoreModel->getAveragePillars('researcher');

        $html = $this->twig->render('pages/public/researcher_list.twig', [
            'researchers' => $researchers,
            'avgPillars'  => $avgPillars,
            'field'       => $field,
            'search'      => $search,
            'pageTitle'   => 'Peneliti Terdampak – Wizdam AI-Sikola',
        ]);

        return Response::html($html);
    }

    /** Profil detail peneliti berdasarkan ORCID. */
    public function show(string $orcid): void
    {
        $researcher = $this->researcherModel->findByOrcid($orcid);

        // Jika belum ada di DB, coba ambil dari ORCID API
        if (!$researcher) {
            $profileManager = new ProfileManager();
            try {
                $id         = $profileManager->syncFromOrcid($orcid);
                $researcher = $this->researcherModel->findById((int) $id);
            } catch (\Throwable) {
                http_response_code(404);
                echo $this->twig->render('pages/error.twig', [
                    'code'    => 404,
                    'message' => "Peneliti dengan ORCID $orcid tidak ditemukan.",
                ]);
                return;
            }
        }

        // Ambil atau hitung impact score
        $scoreClient = new ImpactScoreClient();
        $score       = $scoreClient->getLatest('researcher', (int) $researcher['id']);
        $scoreHistory = $this->scoreModel->getHistory('researcher', (int) $researcher['id']);

        // Ambil SDG tags
        $sdgTags = [];
        if ($score && !empty($score['sdg_tags'])) {
            $sdgTags = json_decode($score['sdg_tags'], true) ?? [];
        }

        // Artikel terbaru peneliti
        $recentArticles = $this->db->fetchAll(
            'SELECT a.id, a.title, a.year, a.citations, a.impact_score, j.title AS journal_name
             FROM articles a
             LEFT JOIN article_authors aa ON aa.article_id = a.id
             LEFT JOIN journals j ON a.journal_id = j.id
             WHERE aa.researcher_id = ?
             ORDER BY a.year DESC, a.citations DESC
             LIMIT 10',
            [$researcher['id']]
        );

        echo $this->twig->render('pages/public/researcher_profile.twig', [
            'researcher'    => $researcher,
            'score'         => $score,
            'scoreHistory'  => $scoreHistory,
            'sdgTags'       => $sdgTags,
            'recentArticles' => $recentArticles,
            'pageTitle'     => ($researcher['name'] ?? $orcid) . ' – Wizdam AI-Sikola',
        ]);
    }

    /** Versi Response object untuk show() - digunakan oleh router baru */
    public function showWithResponse(string $orcid): Response
    {
        $researcher = $this->researcherModel->findByOrcid($orcid);

        // Jika belum ada di DB, coba ambil dari ORCID API
        if (!$researcher) {
            $profileManager = new ProfileManager();
            try {
                $id         = $profileManager->syncFromOrcid($orcid);
                $researcher = $this->researcherModel->findById((int) $id);
            } catch (\Throwable) {
                return Response::error("Peneliti dengan ORCID $orcid tidak ditemukan.", 404);
            }
        }

        // Ambil atau hitung impact score
        $scoreClient = new ImpactScoreClient();
        $score       = $scoreClient->getLatest('researcher', (int) $researcher['id']);
        $scoreHistory = $this->scoreModel->getHistory('researcher', (int) $researcher['id']);

        // Ambil SDG tags
        $sdgTags = [];
        if ($score && !empty($score['sdg_tags'])) {
            $sdgTags = json_decode($score['sdg_tags'], true) ?? [];
        }

        // Artikel terbaru peneliti
        $recentArticles = $this->db->fetchAll(
            'SELECT a.id, a.title, a.year, a.citations, a.impact_score, j.title AS journal_name
             FROM articles a
             LEFT JOIN article_authors aa ON aa.article_id = a.id
             LEFT JOIN journals j ON a.journal_id = j.id
             WHERE aa.researcher_id = ?
             ORDER BY a.year DESC, a.citations DESC
             LIMIT 10',
            [$researcher['id']]
        );

        $html = $this->twig->render('pages/public/researcher_profile.twig', [
            'researcher'    => $researcher,
            'score'         => $score,
            'scoreHistory'  => $scoreHistory,
            'sdgTags'       => $sdgTags,
            'recentArticles' => $recentArticles,
            'pageTitle'     => ($researcher['name'] ?? $orcid) . ' – Wizdam AI-Sikola',
        ]);

        return Response::html($html);
    }
}
