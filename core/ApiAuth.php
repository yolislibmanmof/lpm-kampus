<?php
declare(strict_types=1);

final class ApiAuth
{
    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Ambil token dari header Authorization: Bearer <token>
     */
    public static function getTokenFromHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            return trim($m[1]);
        }
        // Fallback ke query param
        return $_GET['token'] ?? null;
    }

    /**
     * Validasi token dan kembalikan data user + permissions
     */
    public static function validate(?string $token): ?array
    {
        if (!$token) return null;
        try {
            $db = self::db();
            $q = $db->prepare("SELECT t.*, u.nama_lengkap, u.id_role, u.email
                               FROM api_tokens t
                               JOIN users u ON t.id_user = u.id_user
                               WHERE t.token = ? AND t.is_active = 1");
            $q->execute([$token]);
            $row = $q->fetch();

            if (!$row) return null;
            if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) return null;

            // Update last_used (async, non-blocking)
            try {
                $db->prepare("UPDATE api_tokens SET last_used = NOW() WHERE id_token = ?")
                   ->execute([$row['id_token']]);
            } catch (Throwable $e) {}

            return [
                'id_token' => (int)$row['id_token'],
                'id_user' => (int)$row['id_user'],
                'id_role' => (int)$row['id_role'],
                'nama' => $row['nama_lengkap'],
                'permissions' => $row['permissions'] ? json_decode($row['permissions'], true) : [],
                'ip_whitelist' => $row['ip_whitelist'] ? json_decode($row['ip_whitelist'], true) : [],
                'rate_limit' => (int)$row['rate_limit']
            ];
        } catch (Throwable $e) {
            error_log('ApiAuth::validate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cek apakah token punya permission tertentu
     */
    public static function hasPermission(array $tokenData, string $permission): bool
    {
        if (empty($tokenData['permissions'])) return true; // No restriction = all allowed
        return in_array($permission, $tokenData['permissions'], true) || in_array('*', $tokenData['permissions'], true);
    }

    /**
     * Validasi IP whitelist
     */
    public static function isIpAllowed(array $tokenData): bool
    {
        if (empty($tokenData['ip_whitelist'])) return true;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($ip, $tokenData['ip_whitelist'], true);
    }

    /**
     * Verifikasi HMAC signature untuk webhook
     */
    public static function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $sourceName): bool
    {
        try {
            $db = self::db();
            $q = $db->prepare("SELECT secret_key FROM webhook_secrets WHERE nama = ? AND is_active = 1");
            $q->execute([$sourceName]);
            $secret = $q->fetchColumn();
            if (!$secret) {
                // Fallback ke DEFAULT
                $q = $db->prepare("SELECT secret_key FROM webhook_secrets WHERE nama = 'DEFAULT' AND is_active = 1");
                $q->execute();
                $secret = $q->fetchColumn();
            }
            if (!$secret) return false;

            $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
            return hash_equals($expected, $signatureHeader);
        } catch (Throwable $e) {
            error_log('ApiAuth::verifySignature error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate token baru (aman, random 64 karakter hex)
     */
    public static function generateToken(int $idUser, string $nama, ?string $deskripsi = null, ?array $permissions = null, int $daysValid = 365): array
    {
        $db = self::db();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+' . $daysValid . ' days'));

        $q = $db->prepare("INSERT INTO api_tokens (id_user, token, nama_aplikasi, deskripsi, permissions, expires_at)
                          VALUES (?, ?, ?, ?, ?, ?)");
        $q->execute([$idUser, $token, $nama, $deskripsi, $permissions ? json_encode($permissions) : null, $expires]);

        return [
            'id_token' => (int)$db->lastInsertId(),
            'token' => $token,
            'expires_at' => $expires
        ];
    }

    /**
     * Catat audit log
     */
    public static function log(string $endpoint, string $method, ?int $idToken, int $status, ?string $error = null, ?string $body = null, int $msTime = 0): void
    {
        try {
            $db = self::db();
            $q = $db->prepare("INSERT INTO api_audit_log
                (id_token, endpoint, method, ip_address, user_agent, request_body, response_status, response_time_ms, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $q->execute([
                $idToken, $endpoint, $method,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                $body ? substr($body, 0, 2000) : null,
                $status, $msTime, $error
            ]);
        } catch (Throwable $e) {
            error_log('ApiAuth::log error: ' . $e->getMessage());
        }
    }

    /**
     * Kirim JSON response + CORS headers
     */
    public static function respond(array $data, int $httpStatus = 200): void
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Webhook-Source, X-Webhook-Signature');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
        $data['timestamp'] = date('c');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}