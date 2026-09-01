<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ========== FAVICON (BARU v3.1) ==========
    if ($action === 'save_favicon') {
        $old = Site::setting('favicon_path');
        if (!empty($_FILES['favicon']['name'])) {
            $up = Security::secureUpload($_FILES['favicon'], 'logo');
            if ($up['success']) {
                if ($old && file_exists(PATH_UPLOAD . $old)) unlink(PATH_UPLOAD . $old);
                $db->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES ('favicon_path', ?)")->execute([$up['path']]);
                Site::flush();
                $pesan = '✅ Favicon berhasil diganti. Tekan Ctrl+Shift+R di browser untuk memuat ikon baru.';
            } else { $error = $up['message']; }
        }
        if (($_POST['reset_favicon'] ?? '') === '1') {
            if ($old && file_exists(PATH_UPLOAD . $old)) unlink(PATH_UPLOAD . $old);
            $db->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES ('favicon_path', '')")->execute(['']);
            Site::flush();
            $pesan = '✅ Favicon dikembalikan ke inisial gradient default.';
        }
    }

    // ========== BRAND & LOGO ==========
    if ($action === 'save_brand') {
        $logoPath = Site::setting('logo_path');
        if (!empty($_FILES['logo']['name'])) {
            $up = Security::secureUpload($_FILES['logo'], 'logo');
            if ($up['success']) {
                if ($logoPath && file_exists(PATH_UPLOAD . $logoPath)) unlink(PATH_UPLOAD . $logoPath);
                $logoPath = $up['path'];
            } else { $error = $up['message']; }
        }
        if (($_POST['hapus_logo'] ?? '') === '1') {
            if ($logoPath && file_exists(PATH_UPLOAD . $logoPath)) unlink(PATH_UPLOAD . $logoPath);
            $logoPath = '';
        }
        foreach (['brand_utama' => trim($_POST['brand_utama'] ?? 'LPM'), 'brand_aksen' => trim($_POST['brand_aksen'] ?? 'Kampus'), 'logo_path' => $logoPath] as $k => $v) {
            $db->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES (?,?)")->execute([$k, $v]);
        }
        Site::flush();
        $pesan = '✅ Brand & logo diperbarui.';
    }

    // ========== KONTEN TEKS ==========
    if ($action === 'save_konten') {
        foreach (['visi', 'misi', 'tupoksi'] as $key) {
            $db->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES (?,?)")->execute([$key, trim($_POST[$key] ?? '')]);
        }
        Site::flush();
        $pesan = '✅ Konten profil diperbarui.';
    }

    // ========== STRUKTUR ==========
    if ($action === 'add_struktur' || $action === 'edit_struktur') {
        $foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $up = Security::secureUpload($_FILES['foto'], 'struktur');
            if ($up['success']) { $foto = $up['path']; } else { $error = $up['message']; }
        }
        if ($action === 'add_struktur') {
            $db->prepare("INSERT INTO struktur_org (jabatan, nama, bidang, foto, urutan) VALUES (?,?,?,?,?)")
               ->execute([trim($_POST['jabatan']), trim($_POST['nama']), trim($_POST['bidang']), $foto, (int)($_POST['urutan'] ?? 0)]);
            $pesan = '✅ Personalia ditambahkan.';
        } else {
            $id = (int)$_POST['id_struktur'];
            $st = $db->prepare("SELECT foto FROM struktur_org WHERE id_struktur=?"); $st->execute([$id]);
            $old = $st->fetch()['foto'];
            if (!$foto) {
                $foto = $old;
            } elseif ($old && file_exists(PATH_UPLOAD . $old)) {
                unlink(PATH_UPLOAD . $old);
            }
            $db->prepare("UPDATE struktur_org SET jabatan=?, nama=?, bidang=?, foto=?, urutan=? WHERE id_struktur=?")
               ->execute([trim($_POST['jabatan']), trim($_POST['nama']), trim($_POST['bidang']), $foto, (int)($_POST['urutan'] ?? 0), $id]);
            $pesan = '✅ Personalia diperbarui.';
        }
    }

    if ($action === 'delete_struktur') {
        $st = $db->prepare("SELECT foto FROM struktur_org WHERE id_struktur=?"); $st->execute([(int)$_POST['id_struktur']]);
        $old = $st->fetch()['foto'] ?? null;
        if ($old && file_exists(PATH_UPLOAD . $old)) unlink(PATH_UPLOAD . $old);
        $db->prepare("DELETE FROM struktur_org WHERE id_struktur=?")->execute([(int)$_POST['id_struktur']]);
        $pesan = '✅ Personalia dihapus.';
    }

    // ========== LEGALITAS ==========
    if ($action === 'add_legal') {
        $file = null;
        if (!empty($_FILES['file']['name'])) {
            $up = Security::secureUpload($_FILES['file'], 'legal');
            if ($up['success']) { $file = $up['path']; } else { $error = $up['message']; }
        }
        $db->prepare("INSERT INTO legalitas (nomor_sk, tentang, tahun, urutan, file_path) VALUES (?,?,?,?,?)")
           ->execute([trim($_POST['nomor_sk']), trim($_POST['tentang']), trim($_POST['tahun']), (int)($_POST['urutan'] ?? 0), $file]);
        $pesan = '✅ SK legalitas ditambahkan.';
    }

    if ($action === 'delete_legal') {
        $st = $db->prepare("SELECT file_path FROM legalitas WHERE id_legalitas=?"); $st->execute([(int)$_POST['id_legalitas']]);
        $old = $st->fetch()['file_path'] ?? null;
        if ($old && file_exists(PATH_UPLOAD . $old)) unlink(PATH_UPLOAD . $old);
        $db->prepare("DELETE FROM legalitas WHERE id_legalitas=?")->execute([(int)$_POST['id_legalitas']]);
        $pesan = '✅ SK dihapus.';
    }
}

$current = [];
foreach ($db->query("SELECT * FROM site_settings")->fetchAll() as $r) { $current[$r['setting_key']] = $r['setting_value']; }
$struktur = $db->query("SELECT * FROM struktur_org ORDER BY urutan ASC, id_struktur ASC")->fetchAll();
$legalitas = $db->query("SELECT * FROM legalitas ORDER BY urutan ASC, id_legalitas ASC")->fetchAll();
$favNow = $current['favicon_path'] ?? '';

$editStruktur = null;
if (!empty($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM struktur_org WHERE id_struktur=?"); $st->execute([(int)$_GET['edit']]);
    $editStruktur = $st->fetch();
}

$simTitle = 'Konten Profil Publik';
$activeMenu = 'profil';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<!-- ========== FAVICON (BARU v3.1) ========== -->
<div class="card" style="margin-bottom:28px;border:2px solid var(--accent);box-shadow:var(--shadow-gold);">
    <h3 style="margin-bottom:20px;">🖼️ Favicon (Ikon Tab Browser)</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save_favicon">
        <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
            <div style="text-align:center;">
                <?php if ($favNow && file_exists(PATH_UPLOAD . $favNow)): ?>
                    <img src="/uploads/<?= Security::e($favNow) ?>" style="width:64px;height:64px;object-fit:contain;border-radius:10px;border:1px solid var(--border);background:#fff;padding:6px;">
                <?php else:
                    $init = mb_substr($current['brand_utama'] ?? 'LPM', 0, 1); ?>
                    <div style="width:64px;height:64px;border-radius:14px;background:linear-gradient(135deg,var(--primary),var(--accent));display:grid;place-items:center;color:#fff;font-weight:900;font-size:26px;font-family:'Arial Black',sans-serif;"><?= Security::e($init) ?></div>
                <?php endif; ?>
                <p class="text-muted" style="font-size:12px;margin-top:8px;">Favicon saat ini</p>
            </div>
            <div style="flex:1;min-width:240px;">
                <p style="font-size:14px;margin-bottom:10px;">Ikon kecil di <strong>tab browser</strong>, bookmark, dan shortcut HP. Ukuran ideal: <strong>64×64 px PNG/ICO</strong> dengan latar transparan.</p>
                <div class="form-group" style="margin:0;"><label class="form-label">Upload Favicon Baru</label>
                    <input type="file" name="favicon" class="form-control" accept="image/png,image/x-icon,image/svg+xml"></div>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;margin-top:16px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-gold">💾 Ganti Favicon</button>
            <?php if ($favNow): ?>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;"><input type="checkbox" name="reset_favicon" value="1"> Kembalikan ke inisial gradient default</label>
            <?php endif; ?>
            <p class="text-muted" style="font-size:12px;margin:0;margin-left:auto;">💡 Setelah ganti, tekan <strong>Ctrl+Shift+R</strong> di browser.</p>
        </div>
    </form>
</div>

<!-- ========== BRAND & LOGO ========== -->
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">🎨 Logo & Nama Brand</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save_brand">
        <div style="display:flex;gap:24px;align-items:start;flex-wrap:wrap;">
            <div style="text-align:center;">
                <?php if (!empty($current['logo_path'])): ?>
                    <img src="/uploads/<?= Security::e($current['logo_path']) ?>" style="width:80px;height:80px;border-radius:12px;object-fit:cover;border:1px solid var(--border);">
                <?php else: ?>
                    <div class="brand-logo" style="width:80px;height:80px;font-size:32px;margin:0 auto;"><?= Security::e(mb_substr($current['brand_utama'] ?? 'LPM', 0, 1)) ?></div>
                <?php endif; ?>
                <p class="text-muted" style="font-size:12px;margin-top:8px;">Logo saat ini</p>
            </div>
            <div style="flex:1;min-width:250px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group"><label class="form-label">Teks Utama</label>
                    <input type="text" name="brand_utama" class="form-control" value="<?= Security::e($current['brand_utama'] ?? 'LPM') ?>"></div>
                <div class="form-group"><label class="form-label">Teks Aksen (emas)</label>
                    <input type="text" name="brand_aksen" class="form-control" value="<?= Security::e($current['brand_aksen'] ?? 'Kampus') ?>"></div>
                <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Upload Logo Baru (JPG/PNG)</label>
                    <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png"></div>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <button type="submit" class="btn btn-primary">💾 Simpan Brand</button>
            <?php if (!empty($current['logo_path'])): ?>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;"><input type="checkbox" name="hapus_logo" value="1"> Kembalikan ke logo default</label>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ========== KONTEN TEKS ========== -->
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">📝 Visi, Misi & Tupoksi</h3>
    <form method="POST">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save_konten">
        <div class="form-group"><label class="form-label">Visi</label>
            <textarea name="visi" class="form-control" rows="3"><?= Security::e($current['visi'] ?? '') ?></textarea></div>
        <div class="form-group"><label class="form-label">Misi (satu butir per baris)</label>
            <textarea name="misi" class="form-control" rows="6"><?= Security::e($current['misi'] ?? '') ?></textarea></div>
        <div class="form-group"><label class="form-label">Tupoksi (format: <code>Judul|Emoji|Deskripsi</code>, satu per baris)</label>
            <textarea name="tupoksi" class="form-control" rows="8"><?= Security::e($current['tupoksi'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-primary">💾 Simpan Konten</button>
        <a href="/publik/profil.php" target="_blank" class="btn btn-outline">👁️ Lihat Publik</a>
    </form>
</div>

<!-- ========== STRUKTUR ORGANISASI ========== -->
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:20px;">👥 Struktur Organisasi (dengan Foto)</h3>
    <form method="POST" enctype="multipart/form-data" style="padding:20px;background:var(--bg-light);border-radius:10px;margin-bottom:24px;<?= $editStruktur ? 'border:2px solid var(--accent);' : '' ?>">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="<?= $editStruktur ? 'edit_struktur' : 'add_struktur' ?>">
        <?php if ($editStruktur): ?><input type="hidden" name="id_struktur" value="<?= $editStruktur['id_struktur'] ?>"><?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div class="form-group"><label class="form-label">Jabatan</label>
                <input type="text" name="jabatan" class="form-control" value="<?= Security::e($editStruktur['jabatan'] ?? '') ?>" required></div>
            <div class="form-group"><label class="form-label">Nama</label>
                <input type="text" name="nama" class="form-control" value="<?= Security::e($editStruktur['nama'] ?? '') ?>" required></div>
            <div class="form-group"><label class="form-label">Bidang</label>
                <input type="text" name="bidang" class="form-control" value="<?= Security::e($editStruktur['bidang'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Urutan</label>
                <input type="number" name="urutan" class="form-control" value="<?= $editStruktur['urutan'] ?? 0 ?>"></div>
            <div class="form-group"><label class="form-label">Foto (JPG/PNG)</label>
                <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png"></div>
        </div>
        <button type="submit" class="btn <?= $editStruktur ? 'btn-gold' : 'btn-primary' ?>">💾 <?= $editStruktur ? 'Perbarui' : 'Tambah Personalia' ?></button>
        <?php if ($editStruktur): ?><a href="/sim/admin/profil.php" class="btn btn-outline">Batal</a><?php endif; ?>
    </form>

    <div class="table-wrapper">
        <table>
            <thead><tr><th>Foto</th><th>Nama / Jabatan</th><th>Bidang</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($struktur as $s): ?>
                <tr>
                    <td>
                        <?php if ($s['foto']): ?>
                            <img src="/uploads/<?= Security::e($s['foto']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:contain;background:#fff;padding:2px;border:1px solid var(--border);">
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--border);display:grid;place-items:center;">👤</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= Security::e($s['nama']) ?></strong><br><small style="color:var(--accent);"><?= Security::e($s['jabatan']) ?></small></td>
                    <td><?= Security::e($s['bidang']) ?></td>
                    <td>
                        <a href="?edit=<?= $s['id_struktur'] ?>" class="btn btn-outline" style="padding:5px 12px;font-size:12px;">✏️</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus personalia?');">
                            <input type="hidden" name="id_struktur" value="<?= $s['id_struktur'] ?>">
                            <?= Security::csrfField() ?><input type="hidden" name="action" value="delete_struktur">
                            <button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========== LEGALITAS ========== -->
<div class="card">
    <h3 style="margin-bottom:20px;">⚖️ Dasar Hukum / SK (dengan PDF)</h3>
    <form method="POST" enctype="multipart/form-data" style="padding:20px;background:var(--bg-light);border-radius:10px;margin-bottom:24px;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add_legal">
        <div style="display:grid;grid-template-columns:1fr 2fr 1fr 1fr auto;gap:12px;align-items:end;">
            <div class="form-group" style="margin:0;"><label class="form-label">Nomor SK</label>
                <input type="text" name="nomor_sk" class="form-control" placeholder="SK/001/UN/2026" required></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Tentang</label>
                <input type="text" name="tentang" class="form-control" required></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Tahun</label>
                <input type="text" name="tahun" class="form-control" placeholder="2026"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">File PDF</label>
                <input type="file" name="file" class="form-control" accept="application/pdf"></div>
            <button type="submit" class="btn btn-primary">➕ Tambah</button>
        </div>
    </form>

    <div class="table-wrapper">
        <table>
            <thead><tr><th>Nomor SK</th><th>Tentang</th><th>Tahun</th><th>File</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($legalitas as $l): ?>
                <tr>
                    <td><strong><?= Security::e($l['nomor_sk']) ?></strong></td>
                    <td><?= Security::e($l['tentang']) ?></td>
                    <td><?= Security::e($l['tahun']) ?></td>
                    <td><?= $l['file_path'] ? '📄 Terunggah' : '—' ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus SK?');">
                            <input type="hidden" name="id_legalitas" value="<?= $l['id_legalitas'] ?>">
                            <?= Security::csrfField() ?><input type="hidden" name="action" value="delete_legal">
                            <button class="btn btn-outline" style="padding:5px 12px;font-size:12px;color:var(--danger);">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>