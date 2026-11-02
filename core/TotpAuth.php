<?php
declare(strict_types=1);

/**
 * TOTP (Time-based One-Time Password) - RFC 6238 Implementation
 * Compatible with Google Authenticator, Authy, Microsoft Authenticator
 */
final class TotpAuth
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGORITHM = 'sha1';
    private const WINDOW = 1; // Tolerance ±1 step

    private static function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        
        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }

    private static function base32Encode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        
        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($input[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $output .= $map[($buffer >> $bitsLeft) & 31];
            }
        }
        if ($bitsLeft > 0) {
            $output .= $map[($buffer << (5 - $bitsLeft)) & 31];
        }
        return $output;
    }

    /**
     * Generate random base32-encoded secret (20 bytes = 32 chars base32)
     */
    public static function generateSecret(int $length = 20): string
    {
        return self::base32Encode(random_bytes($length));
    }

    /**
     * Generate OTP code untuk timestamp tertentu
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        $time = $timestamp ?? time();
        $counter = intdiv($time, self::PERIOD);
        
        $secretKey = self::base32Decode($secret);
        $timeBytes = pack('N*', 0) . pack('N*', $counter);
        
        $hash = hash_hmac(self::ALGORITHM, $timeBytes, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verifikasi kode OTP dengan window tolerance
     */
    public static function verifyCode(string $secret, string $code): bool
    {
        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) return false;
        
        $now = time();
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $testTime = $now + ($i * self::PERIOD);
            if (hash_equals(self::generateCode($secret, $testTime), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate provisioning URI (untuk QR Code Google Authenticator)
     */
    public static function getProvisioningUri(string $secret, string $accountName, string $issuer = 'SIM-Mutu'): string
    {
        $params = [
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD
        ];
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountName) . '?' . http_build_query($params);
    }

    /**
     * Generate recovery codes (10 kode, 8 karakter alfanumerik)
     */
    public static function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4) . '-' . substr(bin2hex(random_bytes(4)), 0, 4));
        }
        return $codes;
    }

    /**
     * Hash recovery code dengan SHA-256
     */
    public static function hashRecoveryCode(string $code): string
    {
        return hash('sha256', strtolower(str_replace('-', '', $code)));
    }

    /**
     * Verifikasi dan consume recovery code
     */
    public static function consumeRecoveryCode(int $idUser, string $code): bool
    {
        try {
            $db = Database::getInstance();
            $hash = self::hashRecoveryCode($code);
            $q = $db->prepare("SELECT id_recovery FROM totp_recovery_codes 
                               WHERE id_user = ? AND code_hash = ? AND used_at IS NULL");
            $q->execute([$idUser, $hash]);
            $row = $q->fetch();
            if (!$row) return false;
            
            $db->prepare("UPDATE totp_recovery_codes SET used_at = NOW() WHERE id_recovery = ?")
               ->execute([$row['id_recovery']]);
            return true;
        } catch (Throwable $e) {
            error_log('TotpAuth::consumeRecoveryCode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cek apakah user punya 2FA aktif
     */
    public static function is2faEnabled(int $idUser): bool
    {
        try {
            $db = Database::getInstance();
            $q = $db->prepare("SELECT totp_enabled FROM users WHERE id_user = ?");
            $q->execute([$idUser]);
            return (bool)$q->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Aktifkan 2FA untuk user (setelah verifikasi sukses)
     */
    public static function enable2fa(int $idUser, string $secret): bool
    {
        try {
            $db = Database::getInstance();
            $db->prepare("UPDATE users SET totp_secret = ?, totp_enabled = 1, totp_verified_at = NOW() WHERE id_user = ?")
               ->execute([$secret, $idUser]);
            return true;
        } catch (Throwable $e) {
            error_log('TotpAuth::enable2fa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Nonaktifkan 2FA
     */
    public static function disable2fa(int $idUser): bool
    {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            $db->prepare("UPDATE users SET totp_secret = NULL, totp_enabled = 0, totp_verified_at = NULL WHERE id_user = ?")
               ->execute([$idUser]);
            $db->prepare("DELETE FROM totp_recovery_codes WHERE id_user = ?")
               ->execute([$idUser]);
            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return false;
        }
    }

    /**
     * Ambil secret user (untuk verifikasi)
     */
    public static function getUserSecret(int $idUser): ?string
    {
        try {
            $db = Database::getInstance();
            $q = $db->prepare("SELECT totp_secret FROM users WHERE id_user = ? AND totp_enabled = 1");
            $q->execute([$idUser]);
            $secret = $q->fetchColumn();
            return $secret ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Hitung jumlah recovery codes tersisa
     */
    public static function getRemainingRecoveryCodes(int $idUser): int
    {
        try {
            $db = Database::getInstance();
            $q = $db->prepare("SELECT COUNT(*) FROM totp_recovery_codes 
                              WHERE id_user = ? AND used_at IS NULL");
            $q->execute([$idUser]);
            return (int)$q->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}