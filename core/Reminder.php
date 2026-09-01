<?php
require_once __DIR__ . '/Mailer.php';

class Reminder {

    public static function run() {
        try {
            $db = Database::getInstance();
            $today = strtotime(date('Y-m-d'));
            $rows = $db->query("SELECT * FROM pengingat_tenggat WHERE selesai = 0 AND tanggal IS NOT NULL")->fetchAll();
            foreach ($rows as $t) {
                $h = (int)round((strtotime($t['tanggal']) - $today) / 86400);
                if ($h < 0 || $h > 90) continue;
                foreach ([0, 1, 7, 30, 60, 90] as $b) {          // ambang terdekat
                    if ($h <= $b) {
                        $chk = $db->prepare("SELECT id_log FROM reminder_log WHERE id_tenggat = ? AND bucket = ?");
                        $chk->execute([$t['id_tenggat'], $b]);
                        if (!$chk->fetch()) {
                            $label = ($b === 0) ? '🔴 HARI INI' : '⏰ H-' . $b;
                            $msg = $label . ' — ' . $t['judul'] . ' (' . date('d M Y', strtotime($t['tanggal'])) . ')';
                            if (class_exists('Notifier')) {
                                Notifier::sendRole(1, '⏰ Reminder Tenggat', $msg, '/sim/admin/tenggat.php', '⏰');
                                Notifier::sendRole(2, '⏰ Reminder Tenggat', $msg, '/sim/admin/tenggat.php', '⏰');
                            }
                            $ms = $db->query("SELECT * FROM mail_settings WHERE id = 1")->fetch();
                            if ($ms && $ms['aktif'] && $ms['kirim_ke']) {
                                Mailer::send(array_map('trim', explode(',', $ms['kirim_ke'])),
                                    'Reminder: ' . $msg,
                                    Mailer::template('Reminder Tenggat', [$msg, 'Segera siapkan kelengkapan terkait agar tidak ada tahapan terlewat.']));
                            }
                            $db->prepare("INSERT INTO reminder_log (id_tenggat, bucket, tanggal_kirim) VALUES (?,?,?)")
                               ->execute([$t['id_tenggat'], $b, date('Y-m-d')]);
                        }
                        break;
                    }
                }
            }
        } catch (Exception $e) { /* jangan ganggu halaman */ }
    }
}