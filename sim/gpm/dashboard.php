<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([5]);
$db = Database::getInstance();

// Load helper class
require_once dirname(__DIR__, 2) . '/core/GpmAnalytics.php';

$simTitle = 'Dashboard GPM Fakultas';
$activeMenu = 'dashboard';

// Ambil data user GPM
$me = $db->prepare("SELECT id_fakultas, nama_lengkap FROM users WHERE id_user = ?");
$me->execute([Auth::id()]);
$userData = $me->fetch();

$fakId = $userData['id_fakultas'] ?? null;
$namaUser = $userData['nama_lengkap'] ?? 'GPM';

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';

// Jika user belum ditugaskan ke fakultas
if (!$fakId): ?>
    <div class="alert alert-danger">
        ⚠️ Akun Anda belum ditugaskan ke fakultas. Hubungi Admin LPM (menu 🏢 Penugasan GPM).
    </div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; exit; 
endif;

// Ambil nama fakultas
$fn = $db->prepare("SELECT nama_fakultas FROM fakultas WHERE id_fakultas = ?");
$fn->execute([$fakId]);
$namaFak = $fn->fetch()['nama_fakultas'] ?? 'Fakultas';

$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Handle POST: Keputusan Review EDP
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keputusan'])) {
    Security::verifyCsrf();
    $pid = (int)$_POST['id_prodi'];
    $st = $_POST['keputusan'];
    $cat = trim($_POST['catatan'] ?? '');
    
    try {
        $db->prepare("REPLACE INTO review_gpm (id_prodi, tahun, status, catatan) VALUES (?,?,?,?)")
           ->execute([$pid, $tahun, $st, $cat]);
        Logger::log('UPDATE', "Review GPM prodi #$pid ($tahun): $st");
        
        // Notifikasi ke Kaprodi
        $k = $db->prepare("SELECT id_user FROM users WHERE id_role = 3 AND id_prodi = ? LIMIT 1");
        $k->execute([$pid]);
        $kap = $k->fetch();
        if ($kap) {
            Notifier::send((int)$kap['id_user'],
                $st === 'Disetujui' ? '✅ EDP Disetujui GPM' : '↩️ EDP Dikembalikan GPM',
                $cat ?: ('EDP tahun ' . $tahun . ' telah direview GPM fakultas.'), 
                '/sim/prodi/edp.php', 
                $st === 'Disetujui' ? '✅' : '↩️'
            );
        }
        $pesan = '✅ Keputusan review tersimpan & kaprodi dinotifikasi.';
    } catch (PDOException $e) {
        $pesan = '❌ Gagal menyimpan keputusan: ' . $e->getMessage();
    }
}

// Ambil semua data menggunakan GpmAnalytics
$stats = GpmAnalytics::getFakultasStats($fakId, $tahun);
$risikoData = GpmAnalytics::getFakultasRisiko($fakId);
$mockAuditData = GpmAnalytics::getFakultasMockAudit($fakId, $tahun);
$temuanData = GpmAnalytics::getFakultasTemuan($fakId);
$edpAntrean = GpmAnalytics::getEdpAntrean($fakId, $tahun);

// Hitung statistik risiko
$totalRisiko = array_sum(array_column($risikoData, 'total_risiko'));
$totalRisikoEkstrem = array_sum(array_column($risikoData, 'risiko_ekstrem'));
$totalRisikoTinggi = array_sum(array_column($risikoData, 'risiko_tinggi'));

// Hitung rata-rata skor mock audit
$skorList = array_filter(array_column($mockAuditData, 'total_skor'));
$rataSkor = count($skorList) > 0 ? round(array_sum($skorList) / count($skorList), 2) : 0;
$mockAuditSelesai = count(array_filter($mockAuditData, fn($m) => $m['total_skor'] !== null));
?>

<?php if ($pesan): ?>
    <div class="alert <?= strpos($pesan, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
        <?= Security::e($pesan) ?>
    </div>
<?php endif; ?>

<!-- HERO SECTION -->
<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:var(--radius-lg);padding:32px 36px;color:#fff;margin-bottom:28px;position:relative;overflow:hidden;">
    <h2 style="font-size:24px;position:relative;">🏢 Dashboard GPM <?= Security::e($namaFak) ?></h2>
    <p style="opacity:.8;position:relative;">Selamat datang, <strong><?= Security::e($namaUser) ?></strong>. Monitor kinerja penjaminan mutu di fakultas Anda secara real-time.</p>
</div>

<!-- STATISTIK CARDS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card">
        <div class="stat-icon blue">🏫</div>
        <div>
            <h3 style="font-size:28px;" data-count="<?= $stats['total_prodi'] ?>"><?= $stats['total_prodi'] ?></h3>
            <p class="text-muted">Total Prodi</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold">⏳</div>
        <div>
            <h3 style="font-size:28px;" data-count="<?= $stats['edp_menunggu'] ?>"><?= $stats['edp_menunggu'] ?></h3>
            <p class="text-muted">EDP Menunggu Review</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
            <h3 style="font-size:28px;" data-count="<?= $stats['edp_disetujui'] ?>"><?= $stats['edp_disetujui'] ?></h3>
            <p class="text-muted">EDP Disetujui</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⚠️</div>
        <div>
            <h3 style="font-size:28px;" data-count="<?= $stats['temuan_pending'] ?>"><?= $stats['temuan_pending'] ?></h3>
            <p class="text-muted">Temuan Pending</p>
        </div>
    </div>
</div>

<!-- GRID: RISIKO + MOCK AUDIT -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    
    <!-- HEATMAP RISIKO PER PRODI -->
    <div class="card">
        <h3 style="margin-bottom:16px;">⚠️ Risiko per Program Studi</h3>
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
            Total: <strong><?= $totalRisiko ?></strong> risiko | 
            Ekstrem: <strong style="color:var(--danger);"><?= $totalRisikoEkstrem ?></strong> | 
            Tinggi: <strong style="color:var(--warning);"><?= $totalRisikoTinggi ?></strong>
        </p>
        
        <?php if (empty($risikoData)): ?>
            <p class="text-muted" style="text-align:center;padding:20px;">Belum ada data risiko.</p>
        <?php else: ?>
            <div style="max-height:400px;overflow-y:auto;">
                <table style="width:100%;font-size:13px;">
                    <thead style="background:var(--bg-light);position:sticky;top:0;">
                        <tr>
                            <th style="padding:10px;">Prodi</th>
                            <th style="padding:10px;text-align:center;">Total</th>
                            <th style="padding:10px;text-align:center;">Ekstrem</th>
                            <th style="padding:10px;text-align:center;">Tinggi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($risikoData as $r): ?>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:10px;">
                                    <strong><?= Security::e($r['nama_prodi']) ?></strong>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <?= $r['total_risiko'] ?>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <?php if ($r['risiko_ekstrem'] > 0): ?>
                                        <span class="badge badge-danger"><?= $r['risiko_ekstrem'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <?php if ($r['risiko_tinggi'] > 0): ?>
                                        <span class="badge badge-warning"><?= $r['risiko_tinggi'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div style="margin-top:16px;text-align:right;">
            <a href="/sim/gpm/risk-register.php" class="btn btn-outline" style="font-size:13px;padding:8px 16px;">
                Lihat Detail Risiko →
            </a>
        </div>
    </div>

    <!-- MOCK AUDIT PER PRODI -->
    <div class="card">
        <h3 style="margin-bottom:16px;">🎯 Simulasi Akreditasi (<?= $tahun ?>)</h3>
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
            Rata-rata skor: <strong><?= $rataSkor ?>/4.00</strong> | 
            Selesai: <strong><?= $mockAuditSelesai ?>/<?= $stats['total_prodi'] ?></strong> prodi
        </p>
        
        <?php if (empty($mockAuditData)): ?>
            <p class="text-muted" style="text-align:center;padding:20px;">Belum ada data simulasi.</p>
        <?php else: ?>
            <div style="max-height:400px;overflow-y:auto;">
                <table style="width:100%;font-size:13px;">
                    <thead style="background:var(--bg-light);position:sticky;top:0;">
                        <tr>
                            <th style="padding:10px;">Prodi</th>
                            <th style="padding:10px;text-align:center;">Skor</th>
                            <th style="padding:10px;">Prediksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mockAuditData as $m): ?>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:10px;">
                                    <strong><?= Security::e($m['nama_prodi']) ?></strong>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <?php if ($m['total_skor'] !== null): ?>
                                        <strong style="font-size:16px;color:var(--primary);">
                                            <?= number_format((float)$m['total_skor'], 2) ?>
                                        </strong>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px;">
                                    <?php if ($m['prediksi_peringkat']): ?>
                                        <?php
                                        $warna = match($m['prediksi_peringkat']) {
                                            'Unggul' => 'badge-unggul',
                                            'Baik Sekali' => 'badge-a',
                                            'Baik' => 'badge-baik',
                                            default => 'badge-b'
                                        };
                                        ?>
                                        <span class="badge <?= $warna ?>"><?= Security::e($m['prediksi_peringkat']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Belum dinilai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div style="margin-top:16px;text-align:right;">
            <a href="/sim/gpm/mock-audit.php" class="btn btn-outline" style="font-size:13px;padding:8px 16px;">
                Lihat Detail Simulasi →
            </a>
        </div>
    </div>
</div>

<!-- TEMUAN AUDIT TERBARU -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">🔍 Temuan Audit Terbaru</h3>
    
    <?php if (empty($temuanData)): ?>
        <p class="text-muted" style="text-align:center;padding:20px;">Belum ada temuan audit.</p>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach (array_slice($temuanData, 0, 10) as $t): ?>
                <div style="padding:16px;background:var(--bg-light);border-radius:12px;border-left:4px solid <?= $t['kategori'] === 'Mayor' ? 'var(--danger)' : ($t['kategori'] === 'Minor' ? 'var(--warning)' : 'var(--info)') ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="badge <?= $t['kategori'] === 'Mayor' ? 'badge-danger' : ($t['kategori'] === 'Minor' ? 'badge-warning' : 'badge-b') ?>">
                                    <?= Security::e($t['kategori']) ?>
                                </span>
                                <strong><?= Security::e($t['nama_prodi']) ?></strong>
                            </div>
                            <p style="font-size:13px;margin:0;color:var(--text-muted);">
                                <?= Security::e(mb_strimwidth($t['deskripsi_temuan'] ?? '', 0, 150, '...')) ?>
                            </p>
                        </div>
                        <div style="text-align:right;font-size:11px;">
                            <div class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></div>
                            <div style="margin-top:4px;">
                                <?php if ($t['status_verifikasi'] === 'Menunggu'): ?>
                                    <span class="badge badge-warning">Menunggu Verifikasi</span>
                                <?php elseif ($t['status_verifikasi'] === 'Diterima'): ?>
                                    <span class="badge badge-success">Diterima</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Ditolak</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($temuanData) > 10): ?>
            <div style="margin-top:16px;text-align:right;">
                <a href="/sim/gpm/temuan-audit.php" class="btn btn-outline" style="font-size:13px;padding:8px 16px;">
                    Lihat Semua Temuan (<?= count($temuanData) ?>) →
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ANTREAN REVIEW EDP -->
<div class="card">
    <h3 style="margin-bottom:16px;">📝 Antrean Review EDP <?= $tahun ?></h3>
    
    <?php if (empty($edpAntrean)): ?>
        <p class="text-muted" style="text-align:center;padding:20px;">Belum ada data EDP.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Prodi</th>
                        <th>Progres EDP</th>
                        <th>Status Review</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($edpAntrean as $a): ?>
                        <tr>
                            <td><strong><?= Security::e($a['nama_prodi']) ?></strong></td>
                            <td>
                                <?= $a['terkunci'] ?>/<?= $a['total_standar'] ?> standar terkunci
                                <div style="background:var(--border);border-radius:10px;height:6px;margin-top:6px;overflow:hidden;">
                                    <?php 
                                    $progress = $a['total_standar'] > 0 ? round(($a['terkunci'] / $a['total_standar']) * 100) : 0;
                                    ?>
                                    <div style="background:linear-gradient(90deg,var(--primary),var(--accent));height:100%;width:<?= $progress ?>%;border-radius:10px;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if (!$a['review_status']): ?>
                                    <?php if ($a['terkunci'] > 0): ?>
                                        <span class="badge badge-warning">⏳ Menunggu Review</span>
                                    <?php else: ?>
                                        <span class="badge badge-b">Belum dikunci</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge <?= $a['review_status'] === 'Disetujui' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $a['review_status'] === 'Disetujui' ? '✅ Disetujui' : '↩️ Dikembalikan' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['terkunci'] > 0): ?>
                                    <a href="?detail=<?= $a['id_prodi'] ?>&tahun=<?= $tahun ?>" class="btn btn-primary" style="padding:6px 16px;font-size:12px;">
                                        🔍 Review
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
// Detail EDP untuk review
$detail = null; 
$edpRows = [];
$detailId = (int)($_GET['detail'] ?? 0);

if ($detailId) {
    $chk = $db->prepare("SELECT id_prodi FROM prodi WHERE id_prodi = ? AND id_fakultas = ?");
    $chk->execute([$detailId, $fakId]);
    if ($chk->fetch()) {
        $q = $db->prepare("SELECT e.*, s.kode_standar, s.nama_standar FROM evaluasi_diri e LEFT JOIN standar_mutu s ON e.id_standar = s.id_standar WHERE e.id_prodi = ? AND e.tahun = ? ORDER BY s.kode_standar");
        $q->execute([$detailId, $tahun]);
        $edpRows = $q->fetchAll();
        $detail = $detailId;
        
        // Ambil nama prodi
        $prodiStmt = $db->prepare("SELECT nama_prodi FROM prodi WHERE id_prodi = ?");
        $prodiStmt->execute([$detailId]);
        $namaProdiDetail = $prodiStmt->fetch()['nama_prodi'] ?? 'Prodi';
    }
}
?>

<?php if ($detail): ?>
    <!-- MODAL DETAIL EDP -->
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:5000;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:var(--bg-card);border-radius:var(--radius-lg);max-width:900px;width:100%;max-height:90vh;overflow-y:auto;padding:32px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                <h3 style="margin:0;">📋 EDP <?= $tahun ?> — <?= Security::e($namaProdiDetail) ?></h3>
                <a href="/sim/gpm/dashboard.php" class="btn btn-outline" style="padding:8px 16px;">✕ Tutup</a>
            </div>
            
            <div class="table-wrapper" style="margin-bottom:24px;">
                <table>
                    <thead>
                        <tr>
                            <th>Standar</th>
                            <th>Capaian</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Bukti / Analisis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($edpRows as $e): ?>
                            <tr>
                                <td>
                                    <strong><?= Security::e($e['kode_standar']) ?></strong><br>
                                    <small class="text-muted"><?= Security::e($e['nama_standar']) ?></small>
                                </td>
                                <td><?= $e['capaian'] !== null ? $e['capaian'] . '%' : '—' ?></td>
                                <td><?= $e['target'] !== null ? $e['target'] . '%' : '—' ?></td>
                                <td>
                                    <span class="badge <?= $e['status'] === 'Terkunci' ? 'badge-success' : 'badge-b' ?>">
                                        <?= Security::e($e['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:13px;max-width:300px;">
                                    <?= Security::e(mb_strimwidth($e['bukti_fisik'] ?? '', 0, 120, '...')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card" style="background:var(--bg-light);">
                <h4 style="margin-bottom:16px;">⚖️ Keputusan GPM</h4>
                <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id_prodi" value="<?= $detail ?>">
                    <input type="text" name="catatan" class="form-control" style="flex:1;min-width:250px;" placeholder="Catatan untuk kaprodi (opsional)...">
                    <button name="keputusan" value="Disetujui" class="btn btn-success">
                        ✅ Setujui ke LPM
                    </button>
                    <button name="keputusan" value="Dikembalikan" class="btn btn-danger" onclick="return confirm('Kembalikan EDP ke prodi?');">
                        ↩️ Kembalikan
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>