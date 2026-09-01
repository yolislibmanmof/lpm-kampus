<?php
class Mailer {

    public static function send($to, $subject, $html) {
        $db = Database::getInstance();
        $s = $db->query("SELECT * FROM mail_settings WHERE id = 1")->fetch();
        if (!$s || !$s['aktif']) return ['success' => false, 'message' => 'Fitur email tidak aktif.'];
        $host = $s['smtp_host']; $port = (int)$s['smtp_port'];
        $from = $s['smtp_from'] ?: $s['smtp_user'];
        $name = $s['smtp_name'] ?: 'SIM-Mutu';
        try {
            $remote = ($port === 465) ? 'ssl://' . $host : $host;
            $fp = @fsockopen($remote, $port, $errno, $errstr, 15);
            if (!$fp) throw new Exception("Koneksi SMTP gagal: $errstr ($errno)");
            self::expect($fp, 220);
            fwrite($fp, "EHLO simmutu\r\n"); self::expect($fp, 250);
            if ($port === 587) {
                fwrite($fp, "STARTTLS\r\n"); self::expect($fp, 220);
                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fwrite($fp, "EHLO simmutu\r\n"); self::expect($fp, 250);
            }
            if ($s['smtp_user']) {
                fwrite($fp, "AUTH LOGIN\r\n"); self::expect($fp, 334);
                fwrite($fp, base64_encode($s['smtp_user']) . "\r\n"); self::expect($fp, 334);
                fwrite($fp, base64_encode($s['smtp_pass']) . "\r\n"); self::expect($fp, 235);
            }
            fwrite($fp, "MAIL FROM:<$from>\r\n"); self::expect($fp, 250);
            foreach ((array)$to as $rcpt) { fwrite($fp, "RCPT TO:<$rcpt>\r\n"); self::expect($fp, 250); }
            fwrite($fp, "DATA\r\n"); self::expect($fp, 354);
            $head = "From: $name <$from>\r\nTo: " . implode(',', (array)$to) .
                "\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=" .
                "\r\nDate: " . date('r') . "\r\nMIME-Version: 1.0" .
                "\r\nContent-Type: text/html; charset=UTF-8" .
                "\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            fwrite($fp, $head . chunk_split(base64_encode($html)) . "\r\n.\r\n");
            self::expect($fp, 250);
            fwrite($fp, "QUIT\r\n"); fclose($fp);
            return ['success' => true, 'message' => 'Email terkirim.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function expect($fp, $code) {
        $data = '';
        while ($line = fgets($fp, 512)) { $data .= $line; if (preg_match('/^\d{3} /', $line)) break; }
        if ((int)substr($data, 0, 3) !== $code) throw new Exception('SMTP ' . trim($data));
        return $data;
    }

    public static function template($title, $lines) {
        $rows = '';
        foreach ((array)$lines as $l) $rows .= '<p style="margin:6px 0;">' . $l . '</p>';
        return '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
            <div style="background:#0F3D5C;color:#fff;padding:18px 24px;"><h2 style="margin:0;font-size:18px;">🎓 ' . $title . '</h2></div>
            <div style="padding:20px 24px;color:#334155;font-size:14px;">' . $rows . '</div>
            <div style="background:#F1F5F9;color:#64748B;padding:12px 24px;font-size:12px;">SIM-Mutu LPM — dikirim otomatis, mohon tidak membalas.</div></div>';
    }
}