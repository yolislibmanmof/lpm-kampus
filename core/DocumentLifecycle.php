<?php
declare(strict_types=1);

final class DocumentLifecycle
{
    /**
     * Hitung tanggal review berikutnya otomatis berdasarkan periode
     */
    public static function hitungReviewBerikutnya(DateTime $tanggalMulai, int $periodeBulan): DateTime
    {
        $next = clone $tanggalMulai;
        $next->modify("+{$periodeBulan} months");
        return $next;
    }

    /**
     * Ambil semua dokumen yang perlu review (untuk dashboard & cron)
     * @return array
     */
    public static function getDokumenPerluReview(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM v_dokumen_perlu_review");
        return $stmt->fetchAll();
    }

    /**
     * Kirim notifikasi review (Email + Internal Notification)
     */
    public static function kirimNotifikasiReview(int $idDokumen, string $jenisNotif): bool
    {
        $db = Database::getInstance();
        
        // Ambil detail dokumen
        $stmt = $db->prepare("SELECT d.*, u.email, u.nama_lengkap, u.id_user 
                              FROM dokumen_mutu d 
                              LEFT JOIN users u ON d.id_user = u.id_user 
                              WHERE d.id_dokumen = :id");
        $stmt->execute([':id' => $idDokumen]);
        $doc = $stmt->fetch();
        
        if (!$doc) return false;

        // Cek apakah notifikasi jenis ini sudah pernah dikirim (hindari spam)
        $check = $db->prepare("SELECT id_log FROM review_notification_log 
                               WHERE id_dokumen = :id AND jenis_notif = :jenis 
                               AND DATE(created_at) = CURDATE()");
        $check->execute([':id' => $idDokumen, ':jenis' => $jenisNotif]);
        if ($check->fetch()) return true; // Sudah dikirim hari ini

        // 1. Kirim notifikasi internal (tabel notifications)
        $pesan = self::generatePesanNotif($jenisNotif, $doc);
        $notifStmt = $db->prepare("INSERT INTO notifications (id_user, judul, isi, url, icon) 
                                   VALUES (:uid, :judul, :isi, :url, '📑')");
        $notifStmt->execute([
            ':uid'    => $doc['id_user'],
            ':judul'  => "⚠️ Review Dokumen: {$doc['judul_dokumen']}",
            ':isi'    => $pesan,
            ':url'    => "/sim/admin/kendali-dokumen.php?id={$idDokumen}"
        ]);

        // 2. Kirim email jika SMTP aktif
        $mailActive = $db->query("SELECT aktif FROM mail_settings WHERE id=1")->fetchColumn();
        if ($mailActive && !empty($doc['email'])) {
            Mailer::send($doc['email'], "[SIM-Mutu] {$jenisNotif}: {$doc['judul_dokumen']}", $pesan);
        }

        // 3. Log pengiriman
        $logStmt = $db->prepare("INSERT INTO review_notification_log (id_dokumen, jenis_notif, id_user_penerima, status_kirim) 
                                 VALUES (:id, :jenis, :uid, 'Terkirim')");
        $logStmt->execute([':id' => $idDokumen, ':jenis' => $jenisNotif, ':uid' => $doc['id_user']]);

        Logger::log('REVIEW_NOTIF', "Notifikasi {$jenisNotif} untuk dokumen #{$idDokumen}");
        return true;
    }

    /**
     * Generate pesan notifikasi berdasarkan jenis
     */
    private static function generatePesanNotif(string $jenis, array $doc): string
    {
        $tgl = date('d M Y', strtotime($doc['review_berikutnya']));
        return match($jenis) {
            'H-30'       => "Dokumen <b>{$doc['judul_dokumen']}</b> akan memasuki masa review pada <b>{$tgl}</b>. Silakan mulai proses telaah.",
            'H-7'        => "⚠️ PERINGATAN: Review dokumen <b>{$doc['judul_dokumen']}</b> tinggal 7 hari lagi ({$tgl}).",
            'H-1'        => "🚨 URGENT: Besok adalah batas review dokumen <b>{$doc['judul_dokumen']}</b>!",
            'Kedaluwarsa'=> "❌ Dokumen <b>{$doc['judul_dokumen']}</b> telah KEDALUWARSA. Status otomatis diubah menjadi 'Perlu Revisi'.",
            default      => "Dokumen <b>{$doc['judul_dokumen']}</b> telah selesai direview."
        };
    }

    /**
     * Cron Job: Jalankan setiap hari via cron (0 6 * * *)
     * Cek dokumen dan kirim notifikasi sesuai ambang batas
     */
    public static function runDailyCheck(): array
    {
        $results = ['h30' => 0, 'h7' => 0, 'h1' => 0, 'expired' => 0];
        $dokumen = self::getDokumenPerluReview();

        foreach ($dokumen as $doc) {
            $hariSisa = (int)$doc['hari_sisa'];
            
            if ($hariSisa == 30) {
                self::kirimNotifikasiReview($doc['id_dokumen'], 'H-30');
                $results['h30']++;
            } elseif ($hariSisa == 7) {
                self::kirimNotifikasiReview($doc['id_dokumen'], 'H-7');
                $results['h7']++;
            } elseif ($hariSisa == 1) {
                self::kirimNotifikasiReview($doc['id_dokumen'], 'H-1');
                $results['h1']++;
            } elseif ($hariSisa < 0) {
                // Auto-update status jadi "Perlu Revisi"
                $db = Database::getInstance();
                $db->prepare("UPDATE dokumen_mutu SET alur_status='Perlu Revisi', status='Revisi' WHERE id_dokumen=:id")
                   ->execute([':id' => $doc['id_dokumen']]);
                self::kirimNotifikasiReview($doc['id_dokumen'], 'Kedaluwarsa');
                $results['expired']++;
            }
        }

        Logger::log('CRON_REVIEW', "Daily check selesai. Hasil: " . json_encode($results));
        return $results;
    }
}