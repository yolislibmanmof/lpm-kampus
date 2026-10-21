<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

/* Helper: hapus file sertifikat lama dengan aman */
function unlinkSertifikat(?string $rel): void {
    if (!$rel) return;
    $root = realpath(PATH_UPLOAD);
    $f    = realpath(PATH_UPLOAD . $rel);
    if ($root && $f && str_starts_with($f, $root . DIRECTORY_SEPARATOR) && is_file($f)) @unlink($f);
}

/* Helper: validasi inti isian kompetensi */
function validasiKompetensi(array $post, Database $db, bool $requireUser = true): array {
    $out = ['ok' => true, 'error' => '', 'id_user' => 0, 'jenis' => '', 'penyelenggara' => '', 'tahun' => 0];
    $jenis = mb_substr(trim($post['jenis_pelatihan'] ?? ''), 0, 150);
    $penyelenggara = mb_substr(trim($post['penyelenggara'] ?? ''), 0, 150);
    $tahun = (int)($post['tahun'] ?? 0);
    $idUser = (int)($post['id_user'] ?? 0);

    if ($requireUser && $idUser <= 0) { $out['ok'] = false; $out['error'] = '❌ Pilih auditor terlebih dahulu.'; return $out; }
    if ($requireUser) {
        $chk = $db->prepare("SELECT id_user FROM users WHERE id_user = ? AND id_role = 4");
        $chk->execute([$idUser]);
        if (!$chk->fetch()) { $out['ok'] = false; $out['error'] = '❌ Pengguna terpilih bukan ber-peran Auditor.'; return $out; }
    }
    if ($jenis === '') { $out['ok'] = false; $out['error'] = '❌ Jenis pelatihan wajib diisi.'; return $out; }
    if ($tahun < 1950 || $tahun > (int)date('Y') + 1) { $out['ok'] = false; $out['error'] = '❌ Tahun tidak masuk akal (1950–' . ((int)date('Y') + 1) . ').'; return $out; }

    $out['id_user'] = $idUser; $out['jenis'] = $jenis;
    $out['penyelenggara'] = $penyelenggara; $out['tahun'] = $tahun;
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ================= ADD =================
    if ($action === 'add') {
        $v = validasiKompetensi($_POST, $db);
        if (!$v['ok']) { $error = $v['error']; }
        else {
            $sert = null;
            if (!empty($_FILES['sertifikat']['name'])) {
                $up = Security::secureUpload($_FILES['sertifikat'], 'sertifikat');
                if ($up['success']) $sert = $up['path']; else $error = $up['message'];
            }
            if (!$error) {
                try {
                    $db->prepare("INSERT INTO auditor_kompetensi (id_user, jenis_pelatihan, penyelenggara, tahun, sertifikat) VALUES (?,?,?,?,?)")
                       ->execute([$v['id_user'], $v['jenis'], $v['penyelenggara'], $v['tahun'], $sert]);
                    Logger::log('CREATE', 'Mencatat kompetensi auditor #' . $v['id_user'] . ': ' . $v['jenis'] . ' (' . $v['tahun'] . ')');
                    $pesan = '✅ Kompetensi tercatat.';
                } catch (PDOException $e) { $error = '❌ Gagal menyimpan: ' . $e->getMessage(); }
            }
        }
    }

    // ================= EDIT =================
    if ($action === 'edit') {
        $id = (int)($_POST['id_kompetensi'] ?? 0);
        $v = validasiKompetensi($_POST, $db);
        if ($id <= 0) { $error = '❌ Data tidak valid.'; }
        elseif (!$v['ok']) { $error = $v['error']; }
        else {
            try {
                $st = $db->prepare("SELECT sertifikat FROM auditor_kompetensi WHERE id_kompetensi = ?");
                $st->execute([$id]);
                $old = $st->fetch();
                if (!$old) { $error = '❌ Rekaman tidak ditemukan.'; }
                else {
                    $sert = $old['sertifikat'];
                    if (!empty($_FILES['sertifikat']['name'])) {
                        $up = Security::secureUpload($_FILES['sertifikat'], 'sertifikat');
                        if ($up['success']) { unlinkSertifikat($sert); $sert = $up['path']; }
                        else $error = $up['message'];
                    }
                    if (!$error) {
                        $db->prepare("UPDATE auditor_kompetensi SET id_user=?, jenis_pelatihan=?, penyelenggara=?, tahun=?, sertifikat=? WHERE id_kompetensi=?")
                           ->execute([$v['id_user'], $v['jenis'], $v['penyelenggara'], $v['tahun'], $sert, $id]);
                        Logger::log('UPDATE', 'Memperbarui kompetensi #' . $id . ': ' . $v['jenis']);
                        $pesan = '✅ Rekaman kompetensi diperbarui.';
                    }
                }
            } catch (PDOException $e) { $error = '❌ Gagal memperbarui: ' . $e->getMessage(); }
        }
    }

    // ================= DELETE =================
    if ($action === 'delete') {
        try {
            $id = (int)($_POST['id_kompetensi'] ?? 0);
            $st = $db->prepare("SELECT sertifikat FROM auditor_kompetensi WHERE id_kompetensi = ?");
            $st->execute([$id]);
            $old = $st->fetch();
            if ($old) {
                unlinkSertifikat($old['sertifikat']);
                $db->prepare("DELETE FROM auditor_kompetensi WHERE id_kompetensi = ?")->execute([$id]);
                Logger::log('DELETE', 'Menghapus rekaman kompetensi #' . $id);
                $pesan = '✅ Rekaman beserta file sertifikatnya dihapus.';
            } else { $error = '❌ Rekaman tidak ditemukan.'; }
        } catch (PDOException $e) { $error = '❌ Gagal menghapus: ' . $e->getMessage(); }
    }
}

// ===== DATA =====
$auditors = $db->query("SELECT u.id_user, u.nama_lengkap,
    (SELECT COUNT(*) FROM auditor_kompetensi k WHERE k.id_user = u.id_user) jml,
    (SELECT MAX(tahun) FROM auditor_kompetensi k WHERE k.id_user = u.id_user) terakhir,
    (SELECT COUNT(*) FROM penugasan_audit pa WHERE pa.id_auditor = u.id_user AND pa.status <> 'Selesai') tugas_aktif
    FROM users u WHERE u.id_role = 4 ORDER BY u.nama_lengkap")->fetchAll();

$riwayat = $db->query("SELECT k.*, u.nama_lengkap FROM auditor_kompetensi k JOIN users u ON k.id_user = u.id_user ORDER BY k.created_at DESC LIMIT 100")->fetchAll();

$perTahun = [];
try { $perTahun = $db->query("SELECT tahun, COUNT(*) c FROM auditor_kompetensi WHERE tahun IS NOT NULL GROUP BY tahun ORDER BY tahun ASC")->fetchAll(); } catch (Throwable $e) {}
$tahunLabels = array_map('strval', array_column($perTahun, 'tahun'));
$tahunData   = array_map('intval', array_column($perTahun, 'c'));

$totalRekam  = (int)$db->query("SELECT COUNT(*) t FROM auditor_kompetensi")->fetch()['t'];
$jmlKompeten = count(array_filter($auditors, function ($a) { return (int)$a['jml'] > 0; }));
$pctKompeten = count($auditors) > 0 ? (int)round(($jmlKompeten / count($auditors)) * 100) : 0;

$simTitle = 'Kompetensi Auditor';
$activeMenu = 'komp';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- ===== STATISTIK SINGKAT ===== -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">👥</div><div><h3 style="font-size:26px;"><?= count($auditors) ?></h3><p class="text-muted">Auditor Terdaftar</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🎖️</div><div><h3 style="font-size:26px;"><?= $totalRekam ?></h3><p class="text-muted">Rekam Pelatihan</p></div></div>
    <div class="stat-card"><div class="stat-icon gold">✅</div><div><h3 style="font-size:26px;"><?= $pctKompeten ?>%</h3><p class="text-muted">Auditor Kompeten</p></div></div>
</div>

<?php if (empty($auditors)): ?>
    <div class="alert alert-danger">⚠️ Belum ada pengguna dengan peran <strong>Auditor</strong>. Tambahkan terlebih dahulu melalui menu Manajemen Pengguna sebelum mencatat kompetensi.</div>
<?php else: ?>
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">🎖️ Catat Pelatihan / Sertifikasi Auditor</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1.4fr 2fr 1.4fr .7fr 1fr auto;gap:12px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;"><label class="form-label">Auditor *</label>
            <select name="id_user" class="form-control" required>
                <?php foreach ($auditors as $a): ?><option value="<?= $a['id_user'] ?>"><?= Security::e($a['nama_lengkap']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis Pelatihan *</label>
            <input type="text" name="jenis_pelatihan" class="form-control" maxlength="150" placeholder="cth: Pelatihan Auditor AMI Tingkat Nasional" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Penyelenggara</label>
            <input type="text" name="penyelenggara" class="form-control" maxlength="150" placeholder="cth: LLDikti / Kemdikbud"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Tahun *</label>
            <input type="number" name="tahun" class="form-control" min="1950" max="<?= date('Y') + 1 ?>" value="<?= date('Y') ?>" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Sertifikat (PDF)</label>
            <input type="file" name="sertifikat" class="form-control" accept="application/pdf"></div>
        <button class="btn btn-primary">➕</button>
    </form>
</div>
<?php endif; ?>

<!-- ===== GRAFIK PELATIHAN PER TAHUN ===== -->
<?php if (!empty($perTahun)): ?>
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;">📊 Intensitas Pelatihan per Tahun</h3>
    <canvas id="cTahun" height="70"></canvas>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;">
    <div class="card">
        <h3 style="margin-bottom:16px;">👥 Peta Kompetensi Auditor</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php if (empty($auditors)): ?><p class="text-muted" style="text-align:center;padding:20px 0;">Belum ada auditor.</p><?php endif; ?>
            <?php foreach ($auditors as $a): ?>
            <div style="padding:14px;background:var(--bg-light);border-radius:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;">
                <div>
                    <strong style="font-size:14px;"><?= Security::e($a['nama_lengkap']) ?></strong>
                    <p class="text-muted" style="font-size:12.5px;margin:2px 0 0;"><?= $a['jml'] ?> pelatihan • terakhir <?= $a['terakhir'] ?? '—' ?></p>
                    <?php if ((int)$a['tugas_aktif'] > 0): ?><p style="font-size:11.5px;color:var(--info);font-weight:700;margin:4px 0 0;">📌 <?= $a['tugas_aktif'] ?> penugasan AMI aktif</p><?php endif; ?>
                </div>
                <span class="badge <?= $a['jml'] > 0 ? 'badge-unggul' : 'badge-baik' ?>"><?= $a['jml'] > 0 ? '✅ Kompeten' : '⚠️ Perlu Pelatihan' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-muted" style="font-size:12.5px;margin-top:14px;">💡 Saat menjadwalkan AMI, prioritaskan auditor bersertifikat & hindari konflik kepentingan (jangan mengaudit prodi asalnya).</p>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <h3 style="margin:0;">📜 Riwayat Pelatihan</h3>
            <input type="text" id="cariRiwayat" class="form-control" placeholder="🔍 Cari auditor / pelatihan..." style="max-width:240px;">
        </div>
        <div class="table-wrapper">
            <table id="tabelRiwayat">
                <thead><tr><th>Auditor</th><th>Pelatihan</th><th>Tahun</th><th>Sertifikat</th><th style="width:90px;">Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($riwayat)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada rekaman.</td></tr><?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr class="riwayat-row">
                        <td><strong><?= Security::e($r['nama_lengkap']) ?></strong></td>
                        <td><?= Security::e($r['jenis_pelatihan']) ?><br><small class="text-muted"><?= Security::e($r['penyelenggara'] ?? '') ?></small></td>
                        <td><?= (int)$r['tahun'] ?></td>
                        <td>
                            <?php if (!empty($r['sertifikat'])): ?>
                                <a href="/sim/admin/sertifikat.php?id=<?= (int)$r['id_kompetensi'] ?>" target="_blank" rel="noopener" style="color:var(--primary);font-weight:700;">📄 Lihat</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn btn-outline btn-edit-komp" style="padding:4px 10px;font-size:11px;"
                                    data-id="<?= $r['id_kompetensi'] ?>"
                                    data-user="<?= (int)$r['id_user'] ?>"
                                    data-jenis="<?= Security::e($r['jenis_pelatihan']) ?>"
                                    data-penyelenggara="<?= Security::e($r['penyelenggara'] ?? '') ?>"
                                    data-tahun="<?= (int)$r['tahun'] ?>">✏️</button>
                                <form method="POST" onsubmit="return confirm('Hapus rekaman beserta sertifikatnya?');">
                                    <input type="hidden" name="id_kompetensi" value="<?= $r['id_kompetensi'] ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== MODAL EDIT ===== -->
<div id="modalEditKomp" style="display:none;position:fixed;inset:0;background:rgba(4,10,20,.62);backdrop-filter:blur(5px);z-index:4000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--bg-card);border-radius:18px;box-shadow:var(--shadow-lg);border:1px solid var(--border);width:min(560px,100%);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">✏️ Edit Rekaman Kompetensi</h3>
            <button type="button" onclick="document.getElementById('modalEditKomp').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding:22px;display:grid;gap:14px;">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_kompetensi" id="ek_id">
            <div class="form-group" style="margin:0;"><label class="form-label">Auditor *</label>
                <select name="id_user" id="ek_user" class="form-control" required>
                    <?php foreach ($auditors as $a): ?><option value="<?= $a['id_user'] ?>"><?= Security::e($a['nama_lengkap']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Jenis Pelatihan *</label>
                <input type="text" name="jenis_pelatihan" id="ek_jenis" class="form-control" maxlength="150" required></div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
                <div class="form-group" style="margin:0;"><label class="form-label">Penyelenggara</label>
                    <input type="text" name="penyelenggara" id="ek_penyelenggara" class="form-control" maxlength="150"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Tahun *</label>
                    <input type="number" name="tahun" id="ek_tahun" class="form-control" min="1950" max="<?= date('Y') + 1 ?>" required></div>
            </div>
            <div class="form-group" style="margin:0;"><label class="form-label">Ganti Sertifikat <small class="text-muted">(kosongkan jika tetap)</small></label>
                <input type="file" name="sertifikat" class="form-control" accept="application/pdf"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditKomp').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
try {
    var elTahun = document.getElementById('cTahun');
    if (elTahun) new Chart(elTahun, {
        type: 'bar',
        data: { labels: <?= json_encode($tahunLabels) ?>,
                datasets: [{ label: 'Jumlah Pelatihan', data: <?= json_encode($tahunData) ?>, backgroundColor: '#C9A227', borderRadius: 8 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
} catch (e) {}

// Pencarian riwayat
(function () {
    var q = document.getElementById('cariRiwayat');
    if (!q) return;
    q.addEventListener('input', function () {
        var term = q.value.toLowerCase();
        document.querySelectorAll('#tabelRiwayat .riwayat-row').forEach(function (tr) {
            tr.style.display = tr.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
        });
    });
})();

// Modal edit
(function () {
    var modal = document.getElementById('modalEditKomp');
    if (!modal) return;
    document.querySelectorAll('.btn-edit-komp').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('ek_id').value = btn.dataset.id;
            document.getElementById('ek_user').value = btn.dataset.user;
            document.getElementById('ek_jenis').value = btn.dataset.jenis;
            document.getElementById('ek_penyelenggara').value = btn.dataset.penyelenggara;
            document.getElementById('ek_tahun').value = btn.dataset.tahun;
            modal.style.display = 'flex';
        });
    });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>