<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([2]);
$db = Database::getInstance();
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Standar belum tercapai
$belum = $db->prepare("SELECT s.kode_standar, s.nama_standar, ROUND(AVG(e.capaian),1) c, ROUND(AVG(e.target),1) t
    FROM standar_mutu s JOIN evaluasi_diri e ON s.id_standar = e.id_standar AND e.tahun = ?
    GROUP BY s.id_standar HAVING c < t ORDER BY c ASC");
$belum->execute([$tahun]);
$belumList = $belum->fetchAll();

// Temuan mayor terbuka
$mayor = $db->query("SELECT t.*, p.nama_prodi, s.kode_standar FROM temuan_audit t
    JOIN penugasan_audit pa ON t.id_tugas = pa.id_tugas
    LEFT JOIN prodi p ON pa.id_prodi = p.id_prodi
    LEFT JOIN standar_mutu s ON t.id_standar = s.id_standar
    WHERE t.kategori = 'Mayor' AND t.status_verifikasi != 'Diterima' ORDER BY t.id_temuan DESC")->fetchAll();

$simTitle = 'Laporan RTM';
$activeMenu = 'rtm';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<style>
@media print { .sim-sidebar, .sim-topbar, .no-print { display:none !important; } .sim-content { margin-left:0 !important; padding:0 !important; } }
</style>

<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label class="form-label" style="margin:0;">Tahun:</label>
        <select name="tahun" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?><option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
        </select>
    </form>
    <button onclick="window.print()" class="btn btn-gold">🖨️ Cetak untuk Rapat</button>
</div>

<div style="text-align:center;margin-bottom:32px;">
    <h2 style="color:var(--primary-dark);">BAHAN RAPAT TINJAUAN MANAJEMEN</h2>
    <p class="text-muted">Siklus PPEPP Tahun <?= $tahun ?> — Lembaga Penjaminan Mutu</p>
</div>

<div class="card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;"> Standar Belum Tercapai (Prioritas Perbaikan)</h3>
    <?php if (empty($belumList)): ?>
        <p class="text-muted">🎉 Seluruh standar tercapai pada tahun <?= $tahun ?>.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Standar</th><th>Capaian</th><th>Target</th><th>Rekomendasi</th></tr></thead>
                <tbody>
                <?php foreach ($belumList as $b): ?>
                    <tr>
                        <td><strong><?= Security::e($b['kode_standar']) ?></strong><br><small class="text-muted"><?= Security::e($b['nama_standar']) ?></small></td>
                        <td style="color:var(--danger);font-weight:700;"><?= $b['c'] ?>%</td>
                        <td><?= $b['t'] ?>%</td>
                        <td style="font-size:13px;">Perlu tindakan perbaikan terstruktur, evaluasi akar masalah, dan alokasi sumber daya pada siklus berikutnya.</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">🚨 Temuan Mayor yang Belum Tertutup</h3>
    <?php if (empty($mayor)): ?>
        <p class="text-muted">✅ Tidak ada temuan mayor terbuka.</p>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($mayor as $m): ?>
            <div style="padding:16px;background:#FEF2F2;border-radius:10px;border-left:4px solid var(--danger);">
                <strong><?= Security::e($m['nama_prodi']) ?> · <?= Security::e($m['kode_standar']) ?></strong>
                <p style="font-size:14px;margin-top:6px;"><?= Security::e($m['deskripsi_temuan']) ?></p>
                <small class="text-muted">Status koreksi: <?= Security::e($m['status_verifikasi']) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="text-muted" style="margin-top:16px;font-size:13px;">💡 <strong>Keputusan RTM:</strong> _______________________________________________</p>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>