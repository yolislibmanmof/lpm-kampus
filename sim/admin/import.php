<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]);
$db = Database::getInstance();
$pesan = ''; $error = '';
$preview = null;

/* ================= PARSER CSV & XLSX (native) ================= */
function colIdx($L) { $n = 0; for ($i = 0; $i < strlen($L); $i++) $n = $n * 26 + (ord($L[$i]) - 64); return $n - 1; }

function parseCsv($path) {
    $f = fopen($path, 'r');
    $first = fgets($f) ?: '';
    fclose($f);
    $first = preg_replace('/^\xEF\xBB\xBF/', '', $first); // strip BOM
    $delim = ',';
    $best = 0;
    foreach ([',', ';', "\t"] as $d) { $c = substr_count($first, $d); if ($c > $best) { $best = $c; $delim = $d; } }
    $rows = [];
    if (($h = fopen($path, 'r')) !== false) {
        while (($r = fgetcsv($h, 0, $delim)) !== false) $rows[] = $r;
        fclose($h);
    }
    if ($rows) $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$rows[0][0]);
    return $rows;
}

function parseXlsx($path) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];
    $shared = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss) {
        $x = simplexml_load_string($ss);
        foreach ($x->si as $si) {
            if (isset($si->t)) $shared[] = (string)$si->t;
            else { $t = ''; foreach ($si->r as $r) $t .= (string)$r->t; $shared[] = $t; }
        }
    }
    $sh = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sh) return [];
    $x = simplexml_load_string($sh);
    $rows = [];
    foreach ($x->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $col = colIdx(preg_replace('/[0-9]/', '', $ref) ?: 'A');
            $val = '';
            if (isset($c->v)) {
                $v = (string)$c->v;
                $val = ((string)$c['t'] === 's') ? ($shared[(int)$v] ?? '') : $v;
            } elseif (isset($c->is->t)) $val = (string)$c->is->t;
            $cells[$col] = $val;
        }
        if ($cells) {
            $max = max(array_keys($cells));
            $line = [];
            for ($i = 0; $i <= $max; $i++) $line[] = trim((string)($cells[$i] ?? ''));
            $rows[] = $line;
        }
    }
    return $rows;
}

function isHeaderRow($row) {
    $s = strtolower(implode(' ', $row));
    return strpos($s, 'kode') !== false || strpos($s, 'nama') !== false || strpos($s, 'nidn') !== false || strpos($s, 'fakultas') !== false;
}

/* ================= TEMPLATE CSV ================= */
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    if ($_GET['template'] === 'prodi') {
        header('Content-Disposition: attachment; filename="template_prodi.csv"');
        echo "\xEF\xBB\xBFnama_fakultas;kode_prodi;nama_prodi\n";
        echo "Fakultas Ilmu Komputer;IF;Informatika\nFakultas Ilmu Komputer;SI;Sistem Informasi\nFakultas Ekonomi;MN;Manajemen\n";
    } else {
        header('Content-Disposition: attachment; filename="template_dosen.csv"');
        echo "\xEF\xBB\xBFnidn;nama_dosen;kode_prodi;jabatan_fungsional\n";
        echo "0001019001;Dr. Yohanes Tua, M.Kom.;IF;Lektor\n0012058802;Maria Goreti, M.M.;MN;Asisten Ahli\n";
    }
    exit;
}

/* ================= PROSES UPLOAD ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    Security::verifyCsrf();
    $jenis = $_POST['jenis'] ?? 'prodi';
    $dry = isset($_POST['dry']);
    $file = $_FILES['file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) { $error = 'File gagal diunggah.'; }
    elseif ($file['size'] > 5 * 1024 * 1024) { $error = 'Ukuran maksimal 5MB.'; }
    else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'xls') {
            $error = 'File .xls (format lama) tidak didukung. Buka di Excel lalu Simpan sebagai .xlsx atau .csv.';
        } elseif (!in_array($ext, ['csv', 'xlsx'])) {
            $error = 'Format harus .csv atau .xlsx.';
        } else {
            $rows = $ext === 'csv' ? parseCsv($file['tmp_name']) : parseXlsx($file['tmp_name']);
            $rows = array_filter($rows, fn($r) => implode('', array_map('trim', $r)) !== '');
            $rows = array_values($rows);
            if (isset($rows[0]) && isHeaderRow($rows[0])) array_shift($rows);

            $stat = ['baru' => 0, 'update' => 0, 'gagal' => 0];
            $out = [];

            foreach ($rows as $i => $r) {
                $no = $i + 1;
                try {
                    if ($jenis === 'prodi') {
                        [$fak, $kode, $nama] = [trim($r[0] ?? ''), trim($r[1] ?? ''), trim($r[2] ?? '')];
                        if (!$kode || !$nama) throw new Exception('Kolom kode/nama prodi kosong');
                        $status = 'Baru';
                        if (!$dry) {
                            $st = $db->prepare("SELECT id_fakultas FROM fakultas WHERE nama_fakultas = ?"); $st->execute([$fak]);
                            $fid = $st->fetch()['id_fakultas'] ?? null;
                            if (!$fid) { $db->prepare("INSERT INTO fakultas (nama_fakultas) VALUES (?)")->execute([$fak]); $fid = (int)$db->lastInsertId(); }
                            $st = $db->prepare("SELECT id_prodi FROM prodi WHERE kode_prodi = ?"); $st->execute([$kode]);
                            $pid = $st->fetch()['id_prodi'] ?? null;
                            if ($pid) { $db->prepare("UPDATE prodi SET nama_prodi=?, id_fakultas=? WHERE id_prodi=?")->execute([$nama, $fid, $pid]); $status = 'Update'; }
                            else { $db->prepare("INSERT INTO prodi (id_fakultas, kode_prodi, nama_prodi) VALUES (?,?,?)")->execute([$fid, $kode, $nama]); }
                        }
                        $out[] = [$no, "$fak / $kode / $nama", $status];
                        $stat[strtolower($status)]++;
                    } else {
                        [$nidn, $nama, $kode, $jab] = [trim($r[0] ?? ''), trim($r[1] ?? ''), trim($r[2] ?? ''), trim($r[3] ?? '')];
                        if (!$nama) throw new Exception('Nama dosen kosong');
                        $pid = null;
                        if ($kode) {
                            $st = $db->prepare("SELECT id_prodi FROM prodi WHERE kode_prodi = ?"); $st->execute([$kode]);
                            $pid = $st->fetch()['id_prodi'] ?? null;
                            if (!$pid) throw new Exception("Kode prodi '$kode' tidak ditemukan — impor prodi dahulu");
                        }
                        $status = 'Baru';
                        if (!$dry) {
                            $st = $db->prepare("SELECT id_dosen FROM dosen WHERE nidn = ? AND nidn != ''"); $st->execute([$nidn]);
                            $did = $st->fetch()['id_dosen'] ?? null;
                            if ($did) { $db->prepare("UPDATE dosen SET nama_dosen=?, id_prodi=?, jabatan_fungsional=? WHERE id_dosen=?")->execute([$nama, $pid, $jab, $did]); $status = 'Update'; }
                            else { $db->prepare("INSERT INTO dosen (nidn, nama_dosen, id_prodi, jabatan_fungsional) VALUES (?,?,?,?)")->execute([$nidn, $nama, $pid, $jab]); }
                        }
                        $out[] = [$no, "$nidn / $nama / $kode / $jab", $status];
                        $stat[strtolower($status)]++;
                    }
                } catch (Throwable $e) {
                    $out[] = [$no, implode(' / ', array_map('trim', $r)), 'Gagal: ' . $e->getMessage()];
                    $stat['gagal']++;
                }
            }
            $preview = ['rows' => $out, 'stat' => $stat, 'jenis' => $jenis, 'dry' => $dry];
            if (!$dry) {
                Logger::log('UPLOAD', "Import $jenis: {$stat['baru']} baru, {$stat['update']} update, {$stat['gagal']} gagal");
                $pesan = "✅ Import selesai: {$stat['baru']} baru, {$stat['update']} update, {$stat['gagal']} gagal.";
            } else {
                $pesan = '👁️ Pratinjau selesai — belum ada data tersimpan. Hapus centang pratinjau untuk menyimpan.';
            }
        }
    }
}

if (($_POST['action'] ?? '') === 'del_dosen') {
    Security::verifyCsrf();
    $db->prepare("DELETE FROM dosen WHERE id_dosen=?")->execute([(int)$_POST['id_dosen']]);
    $pesan = '✅ Dosen dihapus.';
}

$dosenList = $db->query("SELECT d.*, p.nama_prodi FROM dosen d LEFT JOIN prodi p ON d.id_prodi = p.id_prodi ORDER BY d.id_dosen DESC LIMIT 100")->fetchAll();
$totalDosen = $db->query("SELECT COUNT(*) t FROM dosen")->fetch()['t'];
$totalProdi = $db->query("SELECT COUNT(*) t FROM prodi")->fetch()['t'];

$simTitle = 'Import Data PDDikti';
$activeMenu = 'import';
require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if ($pesan): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;margin-bottom:28px;">
    <div class="stat-card"><div class="stat-icon blue">🏫</div><div><h3 style="font-size:28px;"><?= $totalProdi ?></h3><p class="text-muted">Total Prodi</p></div></div>
    <div class="stat-card"><div class="stat-icon green">🎓</div><div><h3 style="font-size:28px;"><?= $totalDosen ?></h3><p class="text-muted">Total Dosen</p></div></div>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:8px;">📥 Import dari File CSV / Excel (.xlsx)</h3>
    <p class="text-muted" style="font-size:13px;margin-bottom:20px;">
        Ekspor data dari <strong>Feeder PDDikti</strong> (atau ketik manual di Excel), lalu unggah di sini.
        Unduh template: <a href="?template=prodi" style="color:var(--primary);font-weight:700;">📄 template_prodi.csv</a> •
        <a href="?template=dosen" style="color:var(--primary);font-weight:700;">📄 template_dosen.csv</a>
    </p>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:16px;align-items:end;">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="import">
        <div class="form-group" style="margin:0;"><label class="form-label">Jenis Data</label>
            <select name="jenis" class="form-control"><option value="prodi">🏫 Prodi & Fakultas</option><option value="dosen">🧑‍ Dosen</option></select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">File (.csv / .xlsx)</label>
            <input type="file" name="file" class="form-control" accept=".csv,.xlsx" required></div>
        <label style="display:flex;gap:8px;align-items:center;font-size:13.5px;padding-bottom:10px;"><input type="checkbox" name="dry" checked> 👁️ Pratinjau saja</label>
        <button class="btn btn-gold">⚡ Proses</button>
    </form>
</div>

<?php if ($preview): ?>
<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:16px;">📋 Hasil <?= $preview['dry'] ? 'Pratinjau' : 'Import' ?> — <?= strtoupper($preview['jenis']) ?></h3>
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
        <span class="badge badge-unggul">✅ Baru: <?= $preview['stat']['baru'] ?></span>
        <span class="badge badge-a">🔁 Update: <?= $preview['stat']['update'] ?></span>
        <span class="badge badge-unggul" style="background:#FEE2E2;color:#991B1B;"> Gagal: <?= $preview['stat']['gagal'] ?></span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Baris</th><th>Data</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($preview['rows'] as $r): ?>
                <tr>
                    <td><?= $r[0] ?></td>
                    <td><?= Security::e($r[1]) ?></td>
                    <td><span class="badge <?= str_starts_with($r[2], 'Gagal') ? 'badge-unggul' : ($r[2] === 'Baru' ? 'badge-unggul' : 'badge-a') ?>" style="<?= str_starts_with($r[2], 'Gagal') ? 'background:#FEE2E2;color:#991B1B' : '' ?>"><?= Security::e($r[2]) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:16px;">🎓 Data Dosen Terimpor (100 terakhir)</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>NIDN</th><th>Nama</th><th>Prodi</th><th>Jabatan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($dosenList)): ?><tr><td colspan="5" style="text-align:center;padding:30px;" class="text-muted">Belum ada dosen. Impor file pertama Anda di atas.</td></tr><?php endif; ?>
            <?php foreach ($dosenList as $d): ?>
                <tr>
                    <td><?= Security::e($d['nidn']) ?></td>
                    <td><strong><?= Security::e($d['nama_dosen']) ?></strong></td>
                    <td><?= Security::e($d['nama_prodi'] ?? '—') ?></td>
                    <td><?= Security::e($d['jabatan_fungsional']) ?></td>
                    <td><form method="POST" style="display:inline;" onsubmit="return confirm('Hapus dosen?');"><input type="hidden" name="id_dosen" value="<?= $d['id_dosen'] ?>"><?= Security::csrfField() ?><input type="hidden" name="action" value="del_dosen"><button class="btn btn-outline" style="padding:4px 10px;font-size:11px;color:var(--danger);">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>