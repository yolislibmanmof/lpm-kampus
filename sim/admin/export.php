<?php
/**
 * PUSAT EXPORT DATA — LPM SIM-Mutu (Batch 3)
 * Native tanpa Composer: CSV (Excel-friendly: BOM + ';') & XLSX asli (ZipArchive).
 * Keamanan: whitelist dataset, cek role per dataset, proteksi formula-injection (CSV),
 *           filename ASCII, nosniff, no-store, audit trail.
 * Pakai: /sim/admin/export.php?data=users|tracer|pengaduan&format=csv|xlsx [&prodi=ID]
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2]);

$db   = Database::getInstance();
$role = (int)Auth::role();

$data   = (string)($_GET['data'] ?? '');
$format = strtolower((string)($_GET['format'] ?? 'csv'));
if (!in_array($format, ['csv', 'xlsx'], true)) $format = 'csv';

/* ===== Definisi dataset (whitelist ketat) ===== */
$DATASETS = [
    'users' => [
        'roles' => [1],
        'title' => 'Pengguna',
        'sql' => "SELECT u.id_user, u.nidn_nip, u.nama_lengkap, u.email, r.nama_role, p.nama_prodi, u.is_active, u.last_login, u.created_at
                  FROM users u JOIN roles r ON u.id_role = r.id_role LEFT JOIN prodi p ON u.id_prodi = p.id_prodi
                  ORDER BY u.id_user DESC",
        'columns' => [
            'id_user' => 'ID', 'nidn_nip' => 'NIDN/NIP', 'nama_lengkap' => 'Nama Lengkap', 'email' => 'Email',
            'nama_role' => 'Role', 'nama_prodi' => 'Prodi', 'status' => 'Status',
            'last_login' => 'Login Terakhir', 'created_at' => 'Dibuat',
        ],
        'numeric' => ['id_user'],
        'format_row' => function (array $r): array {
            $r['status'] = ((int)($r['is_active'] ?? 0) === 1) ? 'Aktif' : 'Nonaktif';
            $r['nama_prodi'] = $r['nama_prodi'] ?? '';
            return $r;
        },
    ],
    'tracer' => [
        'roles' => [1, 2],
        'title' => 'Tracer Study',
        'sql' => "SELECT t.*, p.nama_prodi FROM tracer_alumni t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi ORDER BY t.created_at DESC",
        'columns' => [
            'nama' => 'Nama', 'nim' => 'NIM', 'nama_prodi' => 'Prodi', 'tahun_lulus' => 'Tahun Lulus',
            'email' => 'Email', 'no_wa' => 'WhatsApp', 'status_kerja' => 'Status Kerja', 'nama_instansi' => 'Instansi',
            'jabatan' => 'Jabatan', 'masa_tunggu_bulan' => 'Masa Tunggu (Bulan)', 'kesesuaian_bidang' => 'Kesesuaian Bidang',
            'kisaran_gaji' => 'Kisaran Gaji', 'siap_txt' => 'Siap Wawancara', 'created_at' => 'Diisi Pada',
        ],
        'numeric' => ['masa_tunggu_bulan'],
        'format_row' => function (array $r): array {
            $r['siap_txt'] = ((int)($r['siap_wawancara'] ?? 0) === 1) ? 'Ya' : 'Tidak';
            $r['nama_prodi'] = $r['nama_prodi'] ?? '';
            return $r;
        },
    ],
    'pengaduan' => [
        'roles' => [1],
        'title' => 'Pengaduan Publik',
        'sql' => "SELECT * FROM pengaduan ORDER BY created_at DESC",
        'columns' => [
            'id_pengaduan' => 'ID', 'nama' => 'Nama Pelapor', 'email' => 'Email', 'instansi' => 'Instansi',
            'kategori' => 'Kategori', 'subjek' => 'Subjek', 'isi_pesan' => 'Isi Pesan', 'rating' => 'Rating',
            'status' => 'Status', 'created_at' => 'Tanggal Masuk',
        ],
        'numeric' => ['id_pengaduan', 'rating'],
        'format_row' => function (array $r): array {
            $r['kategori'] = $r['kategori'] ?? '';
            $r['rating']   = $r['rating'] ?? '';
            return $r;
        },
    ],
];

if (!isset($DATASETS[$data])) { http_response_code(404); exit('Dataset tidak dikenal.'); }
$def = $DATASETS[$data];
if (!in_array($role, $def['roles'], true)) { http_response_code(403); exit('Anda tidak berhak mengekspor data ini.'); }

/* ===== Ambil data (filter prodi opsional utk tracer: int-cast + prepared) ===== */
try {
    $sql = $def['sql']; $params = [];
    if ($data === 'tracer') {
        $prodi = (int)($_GET['prodi'] ?? 0);
        if ($prodi > 0) {
            $sql = "SELECT t.*, p.nama_prodi FROM tracer_alumni t LEFT JOIN prodi p ON t.id_prodi = p.id_prodi WHERE t.id_prodi = ? ORDER BY t.created_at DESC";
            $params[] = $prodi;
        }
    }
    if ($params) { $st = $db->prepare($sql); $st->execute($params); $rows = $st->fetchAll(PDO::FETCH_ASSOC); }
    else { $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
} catch (Throwable $e) {
    error_log('export.php: ' . $e->getMessage());
    http_response_code(500); exit('Gagal memuat data.');
}

/* ===== Susun matriks header + baris ===== */
$cols   = $def['columns'];
$header = array_values($cols);
$matrix = [];
foreach ($rows as $r) {
    $r = ($def['format_row'])($r);
    $line = [];
    foreach ($cols as $key => $label) {
        $v = $r[$key] ?? '';
        $line[] = ($v === null) ? '' : (string)$v;
    }
    $matrix[] = $line;
}

Logger::log('EXPORT', 'Export ' . $def['title'] . ' (' . count($matrix) . ' baris, ' . strtoupper($format) . ')');

$stamp = date('Ymd_His');
$base  = preg_replace('/[^a-z0-9_\-]/i', '', str_replace(' ', '_', $def['title'])) . '_' . $stamp;

/* ===== Proteksi formula-injection (CSV). Angka murni dilewati agar tetap numerik. ===== */
function lpm_csv_safe(string $v): string {
    if ($v === '' || is_numeric($v)) return $v;
    $c = $v[0];
    if ($c === '=' || $c === '+' || $c === '-' || $c === '@' || $c === "\t" || $c === "\r") return "'" . $v;
    return $v;
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $base . '.csv"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM agar Excel membaca UTF-8
    fputcsv($out, $header, ';');
    foreach ($matrix as $line) fputcsv($out, array_map('lpm_csv_safe', $line), ';');
    fclose($out);
    exit;
}

/* ===== XLSX asli: inline strings (aman formula by design), angka bertipe numerik ===== */
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('Ekstensi ZIP (ZipArchive) tidak tersedia di server. Gunakan format CSV.');
}

function lpm_col_letter(int $i): string { // 0-based -> A, B, ... AA
    $s = ''; $i++;
    while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); }
    return $s;
}
function lpm_xml(string $v): string { return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }

/* Tulis sheet XML streaming ke file temp (hemat memori utk data besar) */
$tmp = tempnam(sys_get_temp_dir(), 'lpmxlsx');
$fh  = fopen($tmp, 'w');
fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
fwrite($fh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
fwrite($fh, '<row r="1">');
foreach ($header as $ci => $h) {
    fwrite($fh, '<c r="' . lpm_col_letter($ci) . '1" t="inlineStr"><is><t xml:space="preserve">' . lpm_xml($h) . '</t></is></c>');
}
fwrite($fh, '</row>');

$numericIdx = []; $ci = 0;
foreach ($cols as $key => $label) { if (in_array($key, $def['numeric'] ?? [], true)) $numericIdx[$ci] = true; $ci++; }

$rn = 1;
foreach ($matrix as $line) {
    $rn++;
    fwrite($fh, '<row r="' . $rn . '">');
    foreach ($line as $ci => $v) {
        $ref = lpm_col_letter($ci) . $rn;
        if (isset($numericIdx[$ci]) && $v !== '' && is_numeric($v)) {
            fwrite($fh, '<c r="' . $ref . '"><v>' . $v . '</v></c>');
        } else {
            fwrite($fh, '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . lpm_xml($v) . '</t></is></c>');
        }
    }
    fwrite($fh, '</row>');
}
fwrite($fh, '</sheetData></worksheet>');
fclose($fh);

$zipPath = $tmp . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    @unlink($tmp); http_response_code(500); exit('Gagal membuat berkas XLSX.');
}
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '</Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>');
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="' . lpm_xml(mb_substr($def['title'], 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '</Relationships>');
$zip->addFile($tmp, 'xl/worksheets/sheet1.xml');
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
@unlink($tmp); @unlink($zipPath);
exit;