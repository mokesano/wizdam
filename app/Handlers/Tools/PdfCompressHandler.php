<?php

declare(strict_types=1);

namespace Wizdam\Handlers\Tools;

use Wizdam\Database\DBConnector;
use Wizdam\Services\Core\AuthManager;
use Wizdam\Http\Request;
use Wizdam\Http\Response;

/**
 * Kompresi PDF menggunakan Ghostscript (gs) yang terinstal di server.
 * Ghostscript dipilih karena gratis, andal, dan tidak membutuhkan API eksternal.
 */
class PdfCompressHandler
{
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50 MB
    private const OUTPUT_DIR    = BASE_PATH . '/public/assets/pdf/compressed/';

    private const QUALITY_PRESETS = [
        'screen'  => '/screen',   // 72 dpi – terkecil
        'ebook'   => '/ebook',    // 150 dpi – sedang
        'printer' => '/printer',  // 300 dpi – cetak
        'prepress'=> '/prepress', // 300 dpi + warna CMYK
    ];

    public function __construct(
        private DBConnector       $db,
        private \Twig\Environment $twig,
        private AuthManager       $auth
    ) {}

    public function handle(string $method): void
    {
        if ($method === 'POST') {
            $this->process();
            return;
        }

        echo $this->twig->render('pages/tools/pdf_compress.twig', [
            'pageTitle'       => 'PDF Compressor – Wizdam Tools',
            'qualityPresets'  => array_keys(self::QUALITY_PRESETS),
        ]);
    }

    /** Versi Response object untuk handle() - digunakan oleh router baru */
    public function handleWithResponse(Request $request): Response
    {
        if ($request->method === 'POST') {
            return $this->processWithResponse($request);
        }

        $html = $this->twig->render('pages/tools/pdf_compress.twig', [
            'pageTitle'       => 'PDF Compressor – Wizdam Tools',
            'qualityPresets'  => array_keys(self::QUALITY_PRESETS),
        ]);
        return Response::html($html);
    }

    private function process(): void
    {
        header('Content-Type: application/json');

        if (!$this->isGhostscriptAvailable()) {
            http_response_code(503);
            echo json_encode(['error' => 'Ghostscript tidak tersedia di server ini.']);
            return;
        }

        $file = $_FILES['pdf'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'File tidak valid atau tidak diunggah.']);
            return;
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            http_response_code(413);
            echo json_encode(['error' => 'Ukuran file melebihi 50 MB.']);
            return;
        }

        $mime = mime_content_type($file['tmp_name']);
        if ($mime !== 'application/pdf') {
            http_response_code(415);
            echo json_encode(['error' => 'File harus berformat PDF.']);
            return;
        }

        $preset  = $_POST['quality'] ?? 'ebook';
        $dpi     = self::QUALITY_PRESETS[$preset] ?? '/ebook';

        if (!is_dir(self::OUTPUT_DIR)) {
            mkdir(self::OUTPUT_DIR, 0755, true);
        }

        $inputPath  = $file['tmp_name'];
        $outputName = uniqid('pdf_', true) . '.pdf';
        $outputPath = self::OUTPUT_DIR . $outputName;

        // Bangun command Ghostscript – semua argumen di-escape
        $cmd = sprintf(
            'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=%s '
            . '-dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellarg($dpi),
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Kompresi gagal: ' . implode(' ', $output)]);
            return;
        }

        $originalSize   = $file['size'];
        $compressedSize = filesize($outputPath);
        $reduction      = round((1 - $compressedSize / $originalSize) * 100, 1);

        echo json_encode([
            'url'             => '/assets/pdf/compressed/' . $outputName,
            'original_kb'     => round($originalSize   / 1024, 1),
            'compressed_kb'   => round($compressedSize / 1024, 1),
            'reduction_pct'   => $reduction,
            'quality_preset'  => $preset,
        ]);
    }

    private function processWithResponse(Request $request): Response
    {
        if (!$this->isGhostscriptAvailable()) {
            return Response::json(['error' => 'Ghostscript tidak tersedia di server ini.'], 503);
        }

        $files = $request->server['FILES'] ?? $_FILES;
        $file = $files['pdf'] ?? null;
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['error' => 'File tidak valid atau tidak diunggah.'], 400);
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return Response::json(['error' => 'Ukuran file melebihi 50 MB.'], 413);
        }

        $mime = mime_content_type($file['tmp_name']);
        if ($mime !== 'application/pdf') {
            return Response::json(['error' => 'File harus berformat PDF.'], 415);
        }

        $preset  = $request->getBody('quality') ?? 'ebook';
        $dpi     = self::QUALITY_PRESETS[$preset] ?? '/ebook';

        if (!is_dir(self::OUTPUT_DIR)) {
            mkdir(self::OUTPUT_DIR, 0755, true);
        }

        $inputPath  = $file['tmp_name'];
        $outputName = uniqid('pdf_', true) . '.pdf';
        $outputPath = self::OUTPUT_DIR . $outputName;

        // Bangun command Ghostscript – semua argumen di-escape
        $cmd = sprintf(
            'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=%s '
            . '-dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellarg($dpi),
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            return Response::json(['error' => 'Kompresi gagal: ' . implode(' ', $output)], 500);
        }

        $originalSize   = $file['size'];
        $compressedSize = filesize($outputPath);
        $reduction      = round((1 - $compressedSize / $originalSize) * 100, 1);

        return Response::json([
            'url'             => '/assets/pdf/compressed/' . $outputName,
            'original_kb'     => round($originalSize   / 1024, 1),
            'compressed_kb'   => round($compressedSize / 1024, 1),
            'reduction_pct'   => $reduction,
            'quality_preset'  => $preset,
        ]);
    }

    private function isGhostscriptAvailable(): bool
    {
        exec('gs --version 2>&1', $out, $code);
        return $code === 0;
    }
}
