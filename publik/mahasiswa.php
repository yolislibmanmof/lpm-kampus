<?php
$pageTitle = 'Portal Mahasiswa — SIM-Mutu';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
$db = Database::getInstance();

/* ===== IDENTITAS: Mahasiswa memasukkan NIM ===== */
$nimInput = trim($_POST['nim'] ?? $_GET['nim'] ?? '');
$mahasiswa = null;
$errorNim = '';

if ($nimInput !== '') {
    // Validasi format NIM (alphanumeric, 8-15 karakter)
    if (!preg_match('/^[A-Z0-9]{8,15}$/i', $nimInput)) {
        $errorNim = 'Format NIM tidak valid. Gunakan huruf dan angka, 8–15 karakter.';
    } else {
        try {
            // Cari mahasiswa di tabel yang ada (asumsi ada tabel mahasiswa / pakai tracer_alumni sebagai sumber)
            // Fallback 1: tabel mahasiswa (jika ada)
            $q = $db->prepare("SELECT nim, nama_mahasiswa, id_prodi, tahun_masuk FROM mahasiswa WHERE nim = ?");
            $q->execute([strtoupper($nimInput)]);
            $mahasiswa = $q->fetch();

            // Fallback 2: tracer_alumni
            if (!$mahasiswa) {
                $q2 = $db->prepare("SELECT nim, nama_lengkap AS nama_mahasiswa, id_prodi, tahun_lulus AS tahun_masuk FROM tracer_alumni WHERE nim = ?");
                $q2->execute([strtoupper($nimInput)]);
                $mahasiswa = $q2->fetch();
            }

            if (!$mahasiswa) {
                $errorNim = 'NIM tidak ditemukan dalam sistem. Hubungi bagian akademik kampus Anda.';
            } else {
                // Ambil nama prodi
                $qp = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi = ?");
                $qp->execute([$mahasiswa['id_prodi']]);
                $mahasiswa['nama_prodi'] = $qp->fetchColumn() ?: '—';

                // Ambil nama fakultas
                $qf = $db->prepare("SELECT f.nama_fakultas FROM fakultas f JOIN prodi p ON p.id_fakultas = f.id_fakultas WHERE p.id_prodi = ?");
                $qf->execute([$mahasiswa['id_prodi']]);
                $mahasiswa['nama_fakultas'] = $qf->fetchColumn() ?: '—';
            }
        } catch (Throwable $e) {
            $errorNim = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log('Portal mahasiswa: ' . $e->getMessage());
        }
    }
}

/* ===== POST: Pengaduan Mahasiswa ===== */
$pengaduanMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pengaduan']) && $mahasiswa) {
    Security::verifyCsrf();
    $subjek = trim($_POST['subjek'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    if ($subjek === '' || $isi === '') {
        $pengaduanMsg = '❌ Subjek dan isi pengaduan wajib diisi.';
    } else {
        try {
            $db->prepare("INSERT INTO pengaduan (nama, email, subjek, isi, status, created_at) VALUES (?, ?, ?, ?, 'Baru', NOW())")
               ->execute([
                   $mahasiswa['nama_mahasiswa'] . ' (' . strtoupper($mahasiswa['nim']) . ')',
                   strtolower($mahasiswa['nim']) . '@mahasiswa.kampus.ac.id',
                   '[Mahasiswa] ' . mb_substr($subjek, 0, 100),
                   mb_substr($isi, 0, 2000)
               ]);
            $pengaduanMsg = '✅ Pengaduan Anda telah tercatat. Tim LPM akan menindaklanjutinya.';
        } catch (Throwable $e) {
            $pengaduanMsg = '❌ Gagal menyimpan pengaduan.';
            error_log('Portal mahasiswa pengaduan: ' . $e->getMessage());
        }
    }
}

/* ===== DATA: Kelas EDOM aktif & belum diisi ===== */
$edomAktif = [];
$edomRiwayat = [];
$akreditasiProdi = null;
$edomStats = ['aktif' => 0, 'terisi' => 0, 'total' => 0];

if ($mahasiswa) {
    try {
        // Kelas EDOM aktif (periode terbuka) untuk prodi mahasiswa
        $q = $db->prepare("SELECT k.id_kelas, k.token, k.mata_kuliah, d.nama_dosen, per.nama AS periode_nama, per.tanggal_selesai
                           FROM edom_kelas k
                           JOIN edom_periode per ON k.id_periode = per.id_periode
                           JOIN dosen d ON k.id_dosen = d.id_dosen
                           LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
                           WHERE per.status = 'Terbuka'
                             AND (d.id_prodi = ? OR p.id_fakultas = (SELECT id_fakultas FROM prodi WHERE id_prodi = ?))
                           ORDER BY per.tanggal_selesai ASC");
        $q->execute([$mahasiswa['id_prodi'], $mahasiswa['id_prodi']]);
        $edomAktifRaw = $q->fetchAll();

        // Filter: hapus yang sudah diisi NIM ini
        foreach ($edomAktifRaw as $k) {
            $chk = $db->prepare("SELECT id_jawaban FROM edom_jawaban WHERE id_kelas = ? AND nim = ?");
            $chk->execute([$k['id_kelas'], $mahasiswa['nim']]);
            if (!$chk->fetch()) {
                $edomAktif[] = $k;
            }
        }

        // Riwayat EDOM yang sudah diisi (agregat, tanpa detail per pertanyaan)
        $q2 = $db->prepare("SELECT k.mata_kuliah, per.nama AS periode_nama, j.created_at,
                                   (j.q1+j.q2+j.q3+j.q4+j.q5+j.q6+j.q7+j.q8+j.q9+j.q10)/10 AS rata_skor
                            FROM edom_jawaban j
                            JOIN edom_kelas k ON j.id_kelas = k.id_kelas
                            JOIN edom_periode per ON k.id_periode = per.id_periode
                            WHERE j.nim = ?
                            ORDER BY j.created_at DESC
                            LIMIT 10");
        $q2->execute([$mahasiswa['nim']]);
        $edomRiwayat = $q2->fetchAll();

        // Statistik ringkas
        $edomStats['aktif'] = count($edomAktif);
        $edomStats['terisi'] = count($edomRiwayat);
        $edomStats['total'] = $edomStats['aktif'] + $edomStats['terisi'];

        // Akreditasi prodi mahasiswa
        $qa = $db->prepare("SELECT peringkat, lembaga, no_sk, masa_berlaku FROM akreditasi
                           WHERE id_prodi = ? AND tingkat = 'Prodi'
                           ORDER BY tanggal_sk DESC LIMIT 1");
        $qa->execute([$mahasiswa['id_prodi']]);
        $akreditasiProdi = $qa->fetch();
    } catch (Throwable $e) {
        error_log('Portal mahasiswa data: ' . $e->getMessage());
    }
}

/* ===== ICONS (mirip edom.php) ===== */
$ic = [
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'graduation' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'check_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'message_square' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'id_card' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'alert_circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
    'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'trophy' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
    'send' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
];

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ===== GLOBAL PORTAL MAHASISWA ===== */
body.ms-body { background: #F7F9FC; }
.ms-progress-global { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.ms-progress-global-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== DARK MODE ===== */
.ms-dark-toggle {
    position: fixed; bottom: 24px; left: 24px; z-index: 1000;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: #fff; color: #0F3D5C;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 8px 24px rgba(15,61,92,.15); transition: .3s;
}
.ms-dark-toggle:hover { transform: scale(1.08) rotate(15deg); }
body.ms-dark { background: #0F172A; color: #E2E8F0; }
body.ms-dark .ms-body { background: #0F172A; }
body.ms-dark .ms-card, body.ms-dark .ms-stats-card, body.ms-dark .ms-faq-item, body.ms-dark .ms-link-item {
    background: #1E293B; border-color: #334155; color: #E2E8F0;
}
body.ms-dark h1, body.ms-dark h2, body.ms-dark h3, body.ms-dark h4 { color: #E2E8F0; }
body.ms-dark p, body.ms-dark span, body.ms-dark small { color: #94A3B8; }
body.ms-dark .ms-nim-field input { background: #0F172A; border-color: #334155; color: #E2E8F0; }
body.ms-dark .ms-nim-field label { background: #1E293B; color: #94A3B8; }
body.ms-dark .ms-submit, body.ms-dark .ms-submit-edom {
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C;
}
body.ms-dark .ms-link-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F172A; }

/* ===== HERO ===== */
.ms-hero {
    min-height: 55vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 100px 0 60px;
    display: flex; align-items: center;
}
.ms-hero-aurora {
    position: absolute; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,162,39,.25), transparent 55%),
        radial-gradient(ellipse 60% 70% at 80% 20%, rgba(26,90,130,.45), transparent 55%),
        linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: msShift 16s ease-in-out infinite;
}
@keyframes msShift { 0%,100% { filter: hue-rotate(0deg); } 50% { filter: hue-rotate(12deg); } }
.ms-hero-inner { position: relative; z-index: 3; max-width: 820px; margin: 0 auto; text-align: center; padding: 0 24px; }
.ms-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 24px; position: relative; overflow: hidden;
}
.ms-hero-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.45), transparent);
    animation: msShimmer 3.5s ease-in-out infinite;
}
@keyframes msShimmer { to { left: 200%; } }
.ms-hero-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981; box-shadow: 0 0 14px #10B981;
    animation: msPulse 2s ease infinite;
}
@keyframes msPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.ms-hero h1 { font-size: clamp(34px, 5vw, 62px); font-weight: 800; line-height: 1.08; margin-bottom: 18px; letter-spacing: -.03em; color: #fff; }
.ms-hero h1 .ms-grad {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: msTextShine 4s linear infinite; font-style: italic;
}
@keyframes msTextShine { to { background-position: 200% center; } }
.ms-hero-lead { font-size: 17px; color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; line-height: 1.65; }

.ms-trust { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
.ms-trust-item {
    padding: 7px 14px; border-radius: 50px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
}
.ms-trust-item span { color: #10B981; display: inline-flex; }

/* ===== BODY ===== */
.ms-body { padding: 60px 0 100px; }
.ms-container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }

/* ===== IDENTITAS CARD (Login NIM) ===== */
.ms-login-card {
    max-width: 560px; margin: -60px auto 0; position: relative; z-index: 5;
    background: #fff; border-radius: 28px; overflow: hidden;
    border: 1px solid #E2E8F0;
    box-shadow: 0 24px 70px rgba(15,61,92,.12);
    padding: 36px;
}
.ms-login-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #C9A227);
    background-size: 200% 100%; animation: msGradLine 4s linear infinite;
}
@keyframes msGradLine { to { background-position: 200% 0; } }
.ms-login-card h2 { font-size: 22px; margin-bottom: 6px; color: #0F3D5C; letter-spacing: -.02em; }
.ms-login-card p { color: #64748B; font-size: 14px; margin-bottom: 24px; }

.ms-nim-field { position: relative; margin-bottom: 20px; }
.ms-nim-field input {
    width: 100%; padding: 18px 18px 18px 48px;
    border: 2px solid #E2E8F0; border-radius: 14px;
    font-size: 15px; font-family: inherit; outline: none;
    background: #fff; color: #1E293B; transition: .3s;
    text-transform: uppercase;
}
.ms-nim-field input:focus { border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
.ms-nim-field label {
    position: absolute; left: 48px; top: 18px;
    font-size: 14px; color: #64748B;
    pointer-events: none; transition: .25s cubic-bezier(.16,1,.3,1);
    background: #fff; padding: 0 6px; font-weight: 600;
}
.ms-nim-field input:focus ~ label,
.ms-nim-field input:not(:placeholder-shown) ~ label {
    top: -8px; font-size: 11px; color: #C9A227;
    letter-spacing: .8px; text-transform: uppercase; font-weight: 800;
}
.ms-nim-field-ic {
    position: absolute; left: 16px; top: 18px; font-size: 18px; color: #64748B; display: inline-flex;
}

.ms-submit {
    width: 100%; padding: 16px; border-radius: 14px; border: none;
    cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 800;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; box-shadow: 0 12px 36px rgba(15,61,92,.25);
    transition: .3s; position: relative; overflow: hidden;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
}
.ms-submit::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
    transition: left .6s;
}
.ms-submit:hover::before { left: 150%; }
.ms-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 48px rgba(15,61,92,.4); }

.ms-error {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;
    background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444;
    font-size: 13px; font-weight: 600; display: flex; gap: 10px; align-items: center;
}
.ms-success {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;
    background: #D1FAE5; color: #065F46; border-left: 4px solid #10B981;
    font-size: 13px; font-weight: 600; display: flex; gap: 10px; align-items: center;
}

/* ===== STATS ===== */
.ms-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px; margin: 32px 0;
}
.ms-stats-card {
    background: #fff; border-radius: 18px; padding: 22px 20px;
    border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 10px 30px rgba(15,61,92,.08); transition: .3s;
}
.ms-stats-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,61,92,.15); }
.ms-stats-ic {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 22px; color: #fff;
}
.ms-stats-card:nth-child(1) .ms-stats-ic { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #0F3D5C; }
.ms-stats-card:nth-child(2) .ms-stats-ic { background: linear-gradient(135deg, #EF4444, #F87171); }
.ms-stats-card:nth-child(3) .ms-stats-ic { background: linear-gradient(135deg, #10B981, #34D399); }
.ms-stats-card:nth-child(4) .ms-stats-ic { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.ms-stats-num { font-size: 24px; font-weight: 900; color: #0F3D5C; line-height: 1; letter-spacing: -.02em; }
body.ms-dark .ms-stats-num { color: #E2E8F0; }
.ms-stats-lbl { font-size: 11px; color: #64748B; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; margin-top: 3px; }

/* ===== PROFILE HEADER ===== */
.ms-profile {
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    background: linear-gradient(135deg, #061D2E, #0F3D5C 60%, #12365B);
    color: #fff; padding: 32px 36px; border-radius: 24px;
    margin-bottom: 32px; position: relative; overflow: hidden;
}
.ms-profile::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%);
}
.ms-profile-ava {
    width: 72px; height: 72px; border-radius: 20px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 28px; font-weight: 900;
    box-shadow: 0 10px 30px rgba(201,162,39,.4);
    position: relative; z-index: 1;
}
.ms-profile-info { flex: 1; min-width: 240px; position: relative; z-index: 1; }
.ms-profile-info small { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.ms-profile-info h2 { font-size: 24px; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; color: #fff; }
.ms-profile-info p { color: rgba(255,255,255,.85); font-size: 14px; margin: 0; }
.ms-profile-badge {
    padding: 6px 16px; border-radius: 50px;
    background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.35);
    color: #6EE7B7; font-size: 11px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 6px;
}
.ms-profile-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #6EE7B7; animation: msPulse 2s ease infinite; }

/* ===== CARDS GRID ===== */
.ms-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
    margin-bottom: 32px;
}
@media (max-width: 900px) { .ms-grid { grid-template-columns: 1fr; } }

.ms-card {
    background: #fff; border-radius: 20px; padding: 28px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 10px 30px rgba(15,61,92,.06);
    position: relative; overflow: hidden;
}
.ms-card h3 {
    font-size: 18px; margin-bottom: 16px; color: #0F3D5C;
    display: flex; align-items: center; gap: 10px; letter-spacing: -.01em;
}
.ms-card h3 span { color: #C9A227; }
.ms-card-full { grid-column: 1 / -1; }

/* ===== EDOM LIST ===== */
.ms-edom-item {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 14px 16px; border-radius: 12px;
    background: #F7F9FC; border: 1px solid #E2E8F0;
    margin-bottom: 10px; transition: .3s;
}
body.ms-dark .ms-edom-item { background: #0F172A; border-color: #334155; }
.ms-edom-item:hover { transform: translateX(4px); border-color: #C9A227; }
.ms-edom-info { flex: 1; min-width: 0; }
.ms-edom-info strong { font-size: 14px; display: block; color: #0F3D5C; margin-bottom: 2px; }
body.ms-dark .ms-edom-info strong { color: #E2E8F0; }
.ms-edom-info small { color: #64748B; font-size: 12px; }
.ms-submit-edom {
    padding: 8px 16px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; font-size: 12px; font-weight: 800;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    white-space: nowrap; transition: .3s;
}
.ms-submit-edom:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(201,162,39,.35); }

/* ===== RIWAYAT ===== */
.ms-riwayat-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0; border-bottom: 1px dashed #E2E8F0;
}
body.ms-dark .ms-riwayat-item { border-bottom-color: #334155; }
.ms-riwayat-item:last-child { border-bottom: none; }
.ms-riwayat-info strong { font-size: 14px; display: block; color: #0F3D5C; margin-bottom: 2px; }
body.ms-dark .ms-riwayat-info strong { color: #E2E8F0; }
.ms-riwayat-info small { color: #64748B; font-size: 12px; }
.ms-riwayat-skor {
    display: flex; align-items: center; gap: 4px;
    padding: 6px 12px; border-radius: 50px;
    background: rgba(201,162,39,.12); color: #C9A227;
    font-size: 13px; font-weight: 800;
}

/* ===== AKREDITASI BOX ===== */
.ms-akred-box {
    padding: 24px; border-radius: 16px;
    background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.05));
    border: 1px solid rgba(201,162,39,.2);
    text-align: center;
}
.ms-akred-predikat {
    font-size: 42px; font-weight: 900; margin: 8px 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    letter-spacing: -.02em;
}
.ms-akred-info { font-size: 13px; color: #64748B; line-height: 1.7; }
.ms-akred-info strong { color: #0F3D5C; }
body.ms-dark .ms-akred-info strong { color: #E2E8F0; }

/* ===== PENGADUAN FORM ===== */
.ms-form-group { margin-bottom: 16px; }
.ms-form-label { display: block; font-size: 12px; font-weight: 700; color: #0F3D5C; letter-spacing: .5px; text-transform: uppercase; margin-bottom: 6px; }
body.ms-dark .ms-form-label { color: #E2E8F0; }
.ms-form-control {
    width: 100%; padding: 12px 14px; border: 2px solid #E2E8F0; border-radius: 10px;
    font-size: 14px; font-family: inherit; outline: none;
    background: #fff; color: #1E293B; transition: .3s;
}
body.ms-dark .ms-form-control { background: #0F172A; border-color: #334155; color: #E2E8F0; }
.ms-form-control:focus { border-color: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.15); }
textarea.ms-form-control { resize: vertical; min-height: 90px; }

.ms-submit-pengaduan {
    padding: 14px 24px; border-radius: 12px; border: none;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    color: #fff; font-size: 14px; font-weight: 800;
    cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
    transition: .3s;
}
.ms-submit-pengaduan:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(15,61,92,.3); }

/* ===== FAQ ===== */
.ms-faq { max-width: 820px; margin: 40px auto 0; }
.ms-faq h4 { font-size: 18px; color: #0F3D5C; margin-bottom: 16px; font-weight: 800; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; }
body.ms-dark .ms-faq h4 { color: #E2E8F0; }
.ms-faq-item {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 12px;
    margin-bottom: 10px; overflow: hidden; transition: .3s;
}
.ms-faq-item:hover { border-color: rgba(201,162,39,.4); }
.ms-faq-q {
    padding: 16px 20px; cursor: pointer; font-size: 14px; font-weight: 700;
    color: #0F3D5C; display: flex; justify-content: space-between; align-items: center;
    transition: .3s;
}
body.ms-dark .ms-faq-q { color: #E2E8F0; }
.ms-faq-q:hover { background: #F7F9FC; }
body.ms-dark .ms-faq-q:hover { background: #0F172A; }
.ms-faq-q svg { font-size: 16px; color: #C9A227; transition: transform .3s; flex-shrink: 0; }
.ms-faq-item.open .ms-faq-q svg { transform: rotate(180deg); }
.ms-faq-a { max-height: 0; overflow: hidden; transition: max-height .3s ease; padding: 0 20px; }
.ms-faq-item.open .ms-faq-a { max-height: 320px; padding: 0 20px 16px; }
.ms-faq-a p { font-size: 13px; color: #64748B; line-height: 1.6; margin: 0; }

/* ===== LINKS ===== */
.ms-links {
    max-width: 820px; margin: 30px auto 0;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;
}
.ms-link-item {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
    border-radius: 14px; background: #fff; text-decoration: none;
    color: #0F3D5C; font-size: 13.5px; font-weight: 600;
    transition: .25s; border: 1px solid #E2E8F0;
    box-shadow: 0 4px 14px rgba(15,61,92,.05);
}
.ms-link-item:hover { background: rgba(201,162,39,.08); border-color: rgba(201,162,39,.4); transform: translateX(4px); }
.ms-link-ic {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82); color: #fff;
    display: grid; place-items: center; font-size: 16px;
}
.ms-link-item span { flex: 1; }
.ms-link-arrow { color: #C9A227; display: inline-flex; flex-shrink: 0; }

/* ===== BACK TO TOP ===== */
.ms-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #0F3D5C; display: grid; place-items: center;
    font-size: 22px; box-shadow: 0 10px 30px rgba(201,162,39,.4);
    cursor: pointer; border: none; z-index: 999;
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s cubic-bezier(.16,1,.3,1);
}
.ms-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.ms-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== TOAST ===== */
.ms-toast {
    position: fixed; top: 24px; right: 24px; z-index: 2000;
    padding: 14px 18px; border-radius: 12px;
    background: #fff; border-left: 4px solid #10B981;
    box-shadow: 0 12px 40px rgba(15,61,92,.2);
    font-size: 14px; font-weight: 600; color: #0F3D5C;
    display: flex; gap: 10px; align-items: center;
    max-width: 380px;
    animation: msToastIn .5s cubic-bezier(.16,1,.3,1);
}
@keyframes msToastIn { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: none; } }
.ms-toast.danger { border-left-color: #EF4444; color: #991B1B; }
.ms-toast.hide { animation: msToastOut .3s forwards; }
@keyframes msToastOut { to { opacity: 0; transform: translateX(40px); } }

@media (max-width: 640px) {
    .ms-login-card, .ms-card { padding: 24px; }
    .ms-profile { padding: 24px; }
    .ms-hero { min-height: 45vh; padding: 80px 0 50px; }
}

@media print {
    .ms-dark-toggle, .ms-back-top, .ms-progress-global { display: none !important; }
}
</style>

<body class="ms-body">

<div class="ms-progress-global"><div class="ms-progress-global-bar" id="msProg"></div></div>

<!-- HERO -->
<section class="ms-hero">
    <div class="ms-hero-aurora"></div>
    <div class="ms-container">
        <div class="ms-hero-inner">
            <span class="ms-hero-badge">
                <span class="ms-hero-badge-dot"></span>
                Portal Mahasiswa
            </span>
            <h1>Suara Anda, <span class="ms-grad">Masa Depan</span><br>Kampus Kami</h1>
            <p class="ms-hero-lead">
                Akses EDOM, lihat akreditasi prodi, dan berikan pengaduan layanan akademik
                dalam satu portal terpadu. Data Anda aman dan rahasia.
            </p>
            <div class="ms-trust">
                <div class="ms-trust-item"><span><?= $ic['lock'] ?></span> Rahasia</div>
                <div class="ms-trust-item"><span><?= $ic['shield'] ?></span> Terverifikasi</div>
                <div class="ms-trust-item"><span><?= $ic['graduation'] ?></span> Untuk Anda</div>
            </div>
        </div>
    </div>
</section>

<!-- BODY -->
<section class="ms-body">
    <div class="ms-container">

    <?php if (!$mahasiswa): ?>
        <!-- ===== MODE LOGIN ===== -->
        <div class="ms-login-card">
            <h2><?= $ic['id_card'] ?> Verifikasi Identitas</h2>
            <p>Masukkan NIM Anda untuk mengakses layanan mahasiswa. Data hanya digunakan untuk keperluan sistem.</p>

            <?php if ($errorNim !== ''): ?>
                <div class="ms-error"><?= $ic['alert_circle'] ?> <?= Security::e($errorNim) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="ms-nim-field">
                    <span class="ms-nim-field-ic"><?= $ic['id_card'] ?></span>
                    <input type="text" name="nim" placeholder=" " required autocomplete="off" value="<?= Security::e($nimInput) ?>">
                    <label>Nomor Induk Mahasiswa (NIM)</label>
                </div>
                <button type="submit" class="ms-submit">
                    <?= $ic['arrow_right'] ?> Masuk ke Portal
                </button>
            </form>
        </div>

    <?php else: ?>
        <!-- ===== MODE TERLOGIN ===== -->
        <div class="ms-profile">
            <div class="ms-profile-ava"><?= strtoupper(mb_substr($mahasiswa['nama_mahasiswa'], 0, 1)) ?></div>
            <div class="ms-profile-info">
                <small><?= $ic['graduation'] ?> Mahasiswa Aktif</small>
                <h2><?= Security::e($mahasiswa['nama_mahasiswa']) ?></h2>
                <p>NIM: <strong><?= Security::e(strtoupper($mahasiswa['nim'])) ?></strong> • <?= Security::e($mahasiswa['nama_prodi']) ?> • <?= Security::e($mahasiswa['nama_fakultas']) ?></p>
            </div>
            <span class="ms-profile-badge">
                <a href="?nim=" style="color:inherit;text-decoration:none;">Keluar</a>
            </span>
        </div>

        <!-- STATS -->
        <div class="ms-stats-row">
            <div class="ms-stats-card">
                <div class="ms-stats-ic"><?= $ic['trophy'] ?></div>
                <div>
                    <div class="ms-stats-num" data-count="<?= $edomStats['aktif'] ?>"><?= $edomStats['aktif'] ?></div>
                    <div class="ms-stats-lbl">EDOM Aktif</div>
                </div>
            </div>
            <div class="ms-stats-card">
                <div class="ms-stats-ic"><?= $ic['alert_circle'] ?></div>
                <div>
                    <div class="ms-stats-num" data-count="<?= $edomStats['aktif'] ?>"><?= $edomStats['aktif'] ?></div>
                    <div class="ms-stats-lbl">Perlu Diisi</div>
                </div>
            </div>
            <div class="ms-stats-card">
                <div class="ms-stats-ic"><?= $ic['check_circle'] ?></div>
                <div>
                    <div class="ms-stats-num" data-count="<?= $edomStats['terisi'] ?>"><?= $edomStats['terisi'] ?></div>
                    <div class="ms-stats-lbl">Sudah Diisi</div>
                </div>
            </div>
            <div class="ms-stats-card">
                <div class="ms-stats-ic"><?= $ic['award'] ?></div>
                <div>
                    <div class="ms-stats-num"><?= $akreditasiProdi ? Security::e($akreditasiProdi['peringkat']) : '—' ?></div>
                    <div class="ms-stats-lbl">Akreditasi Prodi</div>
                </div>
            </div>
        </div>

        <?php if ($pengaduanMsg !== ''): ?>
            <div class="<?= strpos($pengaduanMsg, '✅') !== false ? 'ms-success' : 'ms-error' ?>">
                <?= strpos($pengaduanMsg, '✅') !== false ? $ic['check_circle'] : $ic['alert_circle'] ?>
                <?= Security::e($pengaduanMsg) ?>
            </div>
        <?php endif; ?>

        <!-- GRID: EDOM AKTIF + AKREDITASI -->
        <div class="ms-grid">
            <!-- EDOM AKTIF -->
            <div class="ms-card">
                <h3><span><?= $ic['calendar'] ?></span> EDOM Menunggu Pengisian</h3>
                <?php if (empty($edomAktif)): ?>
                    <p style="text-align:center;padding:30px 0;color:#64748B;">
                        <?= $ic['check_circle'] ?><br>
                        <strong style="color:#065F46;">Semua EDOM Sudah Diisi!</strong><br>
                        <small>Terima kasih atas partisipasi Anda.</small>
                    </p>
                <?php else: ?>
                    <?php foreach ($edomAktif as $k): ?>
                        <div class="ms-edom-item">
                            <div class="ms-edom-info">
                                <strong><?= Security::e($k['mata_kuliah']) ?></strong>
                                <small><?= Security::e($k['nama_dosen']) ?> • <?= Security::e($k['periode_nama']) ?></small>
                            </div>
                            <a href="/publik/edom.php?token=<?= Security::e($k['token']) ?>" class="ms-submit-edom">
                                Isi <?= $ic['arrow_right'] ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- AKREDITASI PRODI -->
            <div class="ms-card">
                <h3><span><?= $ic['trophy'] ?></span> Akreditasi Prodi Anda</h3>
                <?php if ($akreditasiProdi): ?>
                    <div class="ms-akred-box">
                        <small style="color:#64748B;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Peringkat</small>
                        <div class="ms-akred-predikat"><?= Security::e($akreditasiProdi['peringkat']) ?></div>
                        <div class="ms-akred-info">
                            <strong><?= Security::e($akreditasiProdi['lembaga']) ?></strong><br>
                            No. SK: <?= Security::e($akreditasiProdi['no_sk'] ?? '—') ?><br>
                            <?php if ($akreditasiProdi['masa_berlaku']): ?>
                                Berlaku s.d. <strong><?= date('d M Y', strtotime($akreditasiProdi['masa_berlaku'])) ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;padding:30px 0;color:#64748B;">
                        <?= $ic['info'] ?><br>
                        <small>Belum ada data akreditasi untuk prodi Anda.</small>
                    </p>
                <?php endif; ?>
            </div>

            <!-- RIWAYAT EDOM -->
            <div class="ms-card ms-card-full">
                <h3><span><?= $ic['star'] ?></span> Riwayat EDOM yang Sudah Diisi</h3>
                <?php if (empty($edomRiwayat)): ?>
                    <p style="text-align:center;padding:20px 0;color:#64748B;">
                        <small>Belum ada riwayat EDOM.</small>
                    </p>
                <?php else: ?>
                    <?php foreach ($edomRiwayat as $r): ?>
                        <div class="ms-riwayat-item">
                            <div class="ms-riwayat-info">
                                <strong><?= Security::e($r['mata_kuliah']) ?></strong>
                                <small><?= Security::e($r['periode_nama']) ?> • <?= date('d M Y', strtotime($r['created_at'])) ?></small>
                            </div>
                            <div class="ms-riwayat-skor">
                                <?= $ic['star'] ?> <?= number_format((float)$r['rata_skor'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PENGADUAN LAYANAN -->
            <div class="ms-card ms-card-full">
                <h3><span><?= $ic['message_square'] ?></span> Pengaduan Layanan Akademik</h3>
                <p style="color:#64748B;font-size:13px;margin-bottom:16px;">
                    Sampaikan keluhan atau saran terkait layanan akademik. Tim LPM akan menindaklanjuti setiap pengaduan.
                </p>
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="submit_pengaduan" value="1">
                    <div class="ms-form-group">
                        <label class="ms-form-label">Subjek</label>
                        <input type="text" name="subjek" class="ms-form-control" placeholder="Contoh: Keterlambatan nilai mata kuliah..." required maxlength="100">
                    </div>
                    <div class="ms-form-group">
                        <label class="ms-form-label">Isi Pengaduan</label>
                        <textarea name="isi" class="ms-form-control" placeholder="Jelaskan secara rinci masalah atau saran Anda..." required maxlength="2000"></textarea>
                    </div>
                    <button type="submit" class="ms-submit-pengaduan">
                        <?= $ic['send'] ?> Kirim Pengaduan
                    </button>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <!-- FAQ -->
    <div class="ms-faq">
        <h4><?= $ic['info'] ?> Pertanyaan Umum</h4>
        <div class="ms-faq-item">
            <div class="ms-faq-q">Apa itu EDOM dan mengapa wajib diisi?<span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span></div>
            <div class="ms-faq-a"><p>EDOM (Evaluasi Dosen oleh Mahasiswa) adalah umpan balik dari mahasiswa terhadap kualitas pengajaran dosen. Hasilnya digunakan untuk peningkatan mutu pembelajaran secara berkelanjutan. Pengisian EDOM biasanya menjadi syarat melihat KHS.</p></div>
        </div>
        <div class="ms-faq-item">
            <div class="ms-faq-q">Apakah identitas saya dirahasiakan?<span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span></div>
            <div class="ms-faq-a"><p>Ya, 100%. Hasil EDOM hanya dilaporkan dalam bentuk agregat ke dosen dan LPM. Pengaduan juga ditangani secara rahasia oleh tim khusus.</p></div>
        </div>
        <div class="ms-faq-item">
            <div class="ms-faq-q">Bagaimana cara melihat akreditasi prodi saya?<span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span></div>
            <div class="ms-faq-a"><p>Setelah login dengan NIM, informasi akreditasi prodi Anda akan tampil otomatis di kartu "Akreditasi Prodi Anda" di halaman ini.</p></div>
        </div>
        <div class="ms-faq-item">
            <div class="ms-faq-q">Berapa lama pengaduan saya diproses?<span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span></div>
            <div class="ms-faq-a"><p>Tim LPM akan merespons dalam 3–7 hari kerja. Untuk kasus urgen, silakan hubungi langsung Bagian Akademik fakultas Anda.</p></div>
        </div>
    </div>

    <!-- LINKS -->
    <div class="ms-links">
        <a href="/publik/tracer.php" class="ms-link-item">
            <span class="ms-link-ic"><?= $ic['graduation'] ?></span>
            <span>Tracer Study Alumni</span>
            <span class="ms-link-arrow"><?= $ic['arrow_right'] ?></span>
        </a>
        <a href="/publik/survei-pengguna.php" class="ms-link-item">
            <span class="ms-link-ic"><?= $ic['shield'] ?></span>
            <span>Survei Pengguna</span>
            <span class="ms-link-arrow"><?= $ic['arrow_right'] ?></span>
        </a>
        <a href="/publik/berita.php" class="ms-link-item">
            <span class="ms-link-ic"><?= $ic['info'] ?></span>
            <span>Berita Kampus</span>
            <span class="ms-link-arrow"><?= $ic['arrow_right'] ?></span>
        </a>
    </div>
    </div>
</section>

<button class="ms-dark-toggle" id="msDarkToggle" aria-label="Toggle dark mode"><?= $ic['moon'] ?></button>
<button class="ms-back-top" id="msBackTop" aria-label="Kembali ke atas"><?= $ic['arrow_up'] ?></button>

<script>
(function () {
    'use strict';

    var ICON = {
        moon: <?= json_encode($ic['moon']) ?>,
        sun: <?= json_encode($ic['sun']) ?>
    };

    /* ===== FAQ ACCORDION ===== */
    document.querySelectorAll('.ms-faq-item').forEach(function (item) {
        var q = item.querySelector('.ms-faq-q');
        if (!q) return;
        q.setAttribute('role', 'button');
        q.setAttribute('tabindex', '0');
        q.setAttribute('aria-expanded', 'false');
        function toggle() {
            var open = item.classList.toggle('open');
            q.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        q.addEventListener('click', toggle);
        q.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        });
    });

    /* ===== SCROLL PROGRESS & BACK TO TOP ===== */
    var prog = document.getElementById('msProg');
    var backTop = document.getElementById('msBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== DARK MODE ===== */
    var darkBtn = document.getElementById('msDarkToggle');
    var savedDark = localStorage.getItem('msDark') === '1';
    if (savedDark) {
        document.body.classList.add('ms-dark');
        if (darkBtn) darkBtn.innerHTML = ICON.sun;
    }
    if (darkBtn) darkBtn.addEventListener('click', function () {
        var on = document.body.classList.toggle('ms-dark');
        localStorage.setItem('msDark', on ? '1' : '0');
        this.innerHTML = on ? ICON.sun : ICON.moon;
    });

    /* ===== COUNTER ANIMATION ===== */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var isInt = target === Math.floor(target);
        var start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1300, 1);
            var val = target * (1 - Math.pow(1 - p, 3));
            el.textContent = isInt ? Math.round(val).toLocaleString('id-ID') : val.toFixed(2);
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });

    /* ===== AUTO UPPERCASE NIM ===== */
    var nimInput = document.querySelector('input[name="nim"]');
    if (nimInput) {
        nimInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }

    /* ===== TOAST ===== */
    window.msToast = function (msg, type) {
        var t = document.createElement('div');
        t.className = 'ms-toast' + (type === 'danger' ? ' danger' : '');
        t.innerHTML = '<span>' + msg + '</span>';
        document.body.appendChild(t);
        setTimeout(function () {
            t.classList.add('hide');
            setTimeout(function () { t.remove(); }, 300);
        }, 4500);
    };
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>