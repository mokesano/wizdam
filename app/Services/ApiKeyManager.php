<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Wizdam\Database\DBConnector;

/**
 * Mengelola API key pengguna dengan format HMAC sesuai kontrak Sangia API.
 *
 * Format key : wz_{user_id}_{unix_timestamp}_{hmac16}
 * hmac16     : 16 karakter pertama HMAC-SHA256(user_id:timestamp, WIZDAM_SHARED_SECRET)
 * TTL        : 1 tahun sejak timestamp
 *
 * Key ini digunakan langsung oleh user untuk memanggil Sangia API
 * dan juga disimpan di DB untuk revoke/audit.
 */
class ApiKeyManager
{
    private DBConnector $db;

    public function __construct()
    {
        $this->db = DBConnector::getInstance();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generate & revoke
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate API key baru dan simpan ke tabel users (kolom sangia_api_key).
     * Key lama secara otomatis digantikan.
     *
     * @return string Key baru yang hanya ditampilkan sekali ke user
     */
    public function generateKey(int $userId, string $name = 'default'): string
    {
        $key = self::buildKey($userId);

        $this->db->query(
            'UPDATE users SET sangia_api_key = ?, api_key_name = ?, api_key_created_at = NOW() WHERE id = ?',
            [$key, $name, $userId]
        );

        return $key;
    }

    /**
     * Cabut key dari DB lokal dan dari Sangia API.
     */
    public function revokeKey(int $userId): void
    {
        $row = $this->db->fetchOne('SELECT sangia_api_key FROM users WHERE id = ?', [$userId]);

        if ($row && $row['sangia_api_key']) {
            // Beri tahu Sangia API untuk blacklist key ini
            try {
                $cfg     = require BASE_PATH . '/config/api.php';
                $service = $_ENV['SANGIA_SERVICE_KEY'] ?? '';
                $client  = new \GuzzleHttp\Client(['base_uri' => $cfg['sangia']['base_url'], 'http_errors' => false]);
                $client->post('/api/v1/admin/keys/revoke', [
                    'headers' => ['X-API-Key' => $service, 'Content-Type' => 'application/json'],
                    'json'    => ['key' => $row['sangia_api_key']],
                ]);
            } catch (\Throwable) {}
        }

        $this->db->query(
            'UPDATE users SET sangia_api_key = NULL, api_key_name = NULL, api_key_created_at = NULL WHERE id = ?',
            [$userId]
        );
    }

    /**
     * Ambil API key aktif milik user.
     */
    public function getKey(int $userId): ?string
    {
        $row = $this->db->fetchOne('SELECT sangia_api_key FROM users WHERE id = ?', [$userId]);
        return $row['sangia_api_key'] ?? null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validasi lokal (tanpa roundtrip ke Sangia API)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifikasi bahwa key belum expire (TTL 1 tahun) dan HMAC valid.
     */
    public static function validateKey(string $key): bool
    {
        $parts = explode('_', $key);
        // format: wz _ {userId} _ {timestamp} _ {hmac16}  → 4 parts
        if (count($parts) !== 4 || $parts[0] !== 'wz') {
            return false;
        }

        [, $userId, $timestamp, $hmac16] = $parts;

        // Cek TTL (1 tahun)
        if ((int) $timestamp + 365 * 86400 < time()) {
            return false;
        }

        $expected = self::computeHmac((int) $userId, (int) $timestamp);
        return hash_equals($expected, $hmac16);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal
    // ─────────────────────────────────────────────────────────────────────────

    public static function buildKey(int $userId): string
    {
        $timestamp = time();
        $hmac16    = self::computeHmac($userId, $timestamp);
        return "wz_{$userId}_{$timestamp}_{$hmac16}";
    }

    private static function computeHmac(int $userId, int $timestamp): string
    {
        $secret  = $_ENV['WIZDAM_SHARED_SECRET'] ?? '';
        $message = "{$userId}:{$timestamp}";
        $full    = hash_hmac('sha256', $message, $secret);
        return substr($full, 0, 16);
    }
}
