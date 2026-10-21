<?php
$pageTitle = 'Unduh Dokumen Publik';
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();

$db = Database::getInstance();
$search = trim($_GET['search'] ?? '');
$kategori = $_GET['kategori'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

/* ===== KUMPULAN ICON SVG (GAYA MODERN & PROFESSIONAL) ===== */
$ic = [
    'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',
    'file_pdf' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 13v4"/><path d="M9 13h1.5a1.5 1.5 0 0 1 0 3H9"/><path d="M17 13h-1.5v4H17"/><path d="M15.5 15H17"/></svg>',
    'file_doc' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h6"/><path d="M8 17h4"/><path d="M8 9h1"/></svg>',
    'file_xls' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h6"/><path d="M8 17h6"/><path d="M11 13v4"/></svg>',
    'file_ppt' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h20"/><path d="M21 3v11H3V3"/><path d="m7 21 5-7 5 7"/></svg>',
    'archive' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>',
    'image' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
    'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
    'folder_open' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 14 1.45-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.55 6a2 2 0 0 1-1.94 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"/></svg>',
    'inbox' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
    'package' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
    'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>',
    'lightbulb' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
    'arrow_left' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
    'arrow_right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
    'arrow_up' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>',
    'grid' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
    'list' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>',
    'sort' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/></svg>',
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
    'clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'bookmark' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
    'bookmark_filled' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'layers' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>',
    'globe' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>',
    'badge_check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>',
    'shield_check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    'eye' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
    'link' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    'history' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>',
    'circle_dot' => '<svg xmlns="http://www.w3.org/2000/svg" width="0.5em" height="0.5em" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="12" r="6"/></svg>',
    'hash' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="9" y2="9"/><line x1="4" x2="20" y1="15" y2="15"/><line x1="10" x2="8" y1="3" y2="21"/><line x1="16" x2="14" y1="3" y2="21"/></svg>',
];

/* ===== HELPER FUNCTIONS ===== */
function dkIcon($path) {
    global $ic;
    if (empty($path)) return $ic['file'];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'pdf' => $ic['file_pdf'],
        'doc', 'docx', 'odt', 'rtf' => $ic['file_doc'],
        'xls', 'xlsx', 'csv', 'ods' => $ic['file_xls'],
        'ppt', 'pptx', 'odp' => $ic['file_ppt'],
        'zip', 'rar', '7z', 'tar', 'gz' => $ic['archive'],
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp' => $ic['image'],
        default => $ic['file']
    };
}
function dkColor($path) {
    if (empty($path)) return ['#64748B', '#94A3B8'];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'pdf' => ['#DC2626', '#F87171'],
        'doc', 'docx' => ['#2563EB', '#60A5FA'],
        'xls', 'xlsx' => ['#059669', '#34D399'],
        'ppt', 'pptx' => ['#EA580C', '#FB923C'],
        default => ['#64748B', '#94A3B8']
    };
}
function dkExtGroup($path) {
    if (empty($path)) return 'LAIN';
    $ext = strtoupper(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'PDF') return 'PDF';
    if (in_array($ext, ['DOC', 'DOCX', 'ODT', 'RTF'])) return 'DOC';
    if (in_array($ext, ['XLS', 'XLSX', 'CSV', 'ODS'])) return 'XLS';
    if (in_array($ext, ['PPT', 'PPTX', 'ODP'])) return 'PPT';
    return 'LAIN';
}
function dkFormatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return (int)$bytes . ' B';
}
function getFileSize($path) {
    $fullPath = dirname(__DIR__) . '/uploads/' . $path;
    if (file_exists($fullPath)) {
        return dkFormatBytes(filesize($fullPath));
    }
    return '—';
}
function getFileSizeBytes($path) {
    $fullPath = dirname(__DIR__) . '/uploads/' . $path;
    return file_exists($fullPath) ? (int)filesize($fullPath) : 0;
}

/* ===== DATA QUERIES ===== */
// 1. Absolute totals for Hero Stats
$absoluteTotal = (int)$db->query("SELECT COUNT(*) FROM dokumen_mutu WHERE tipe_akses='publik' AND status='Aktif'")->fetchColumn();
$kategoriList = $db->query("SELECT * FROM kategori_dokumen ORDER BY nama_kategori")->fetchAll();
$totalKategori = count($kategoriList);

// 1b. Breakdown tipe file + total ukuran pustaka
$extBreakdown = ['PDF'=>0,'DOC'=>0,'XLS'=>0,'PPT'=>0,'LAIN'=>0];
$totalLibrarySize = 0;
try {
    $allPublic = $db->query("SELECT file_path FROM dokumen_mutu WHERE tipe_akses='publik' AND status='Aktif'")->fetchAll();
    foreach ($allPublic as $ap) {
        $extBreakdown[dkExtGroup($ap['file_path'] ?? '')]++;
        $totalLibrarySize += getFileSizeBytes($ap['file_path'] ?? '');
    }
} catch (Throwable $e) { $allPublic = []; }

// 1c. Ticker dokumen terbaru
$tickerDocs = [];
try {
    $tickerDocs = $db->query("SELECT judul_dokumen, kode_dokumen, uploaded_at FROM dokumen_mutu
                              WHERE tipe_akses='publik' AND status='Aktif'
                              ORDER BY uploaded_at DESC LIMIT 8")->fetchAll();
} catch (Throwable $e) { $tickerDocs = []; }

// 2. Count for Pagination
$countQuery = "SELECT COUNT(*) as total FROM dokumen_mutu d WHERE d.tipe_akses = 'publik' AND d.status = 'Aktif'";
$countParams = [];
if ($kategori !== 'all') { $countQuery .= " AND d.id_kategori = :kategori"; $countParams[':kategori'] = (int)$kategori; }
if ($search) { $countQuery .= " AND (d.judul_dokumen LIKE :s1 OR d.kode_dokumen LIKE :s2)"; $countParams[':s1'] = "%$search%"; $countParams[':s2'] = "%$search%"; }
$countStmt = $db->prepare($countQuery);
$countStmt->execute($countParams);
$totalFiltered = (int)$countStmt->fetch()['total'];
$totalPages = max(1, (int)ceil($totalFiltered / $limit));

// Edge case: Redirect jika halaman melebihi total
if ($page > $totalPages) {
    $qp = [];
    if ($kategori !== 'all') $qp['kategori'] = $kategori;
    if ($search) $qp['search'] = $search;
    $qp['page'] = $totalPages;
    header('Location: ?' . http_build_query($qp));
    exit;
}

// 3. Fetch Documents
$query = "SELECT d.*, k.nama_kategori FROM dokumen_mutu d
          LEFT JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori
          WHERE d.tipe_akses = 'publik' AND d.status = 'Aktif'";
$params = [];
if ($kategori !== 'all') { $query .= " AND d.id_kategori = :kategori"; $params[':kategori'] = (int)$kategori; }
if ($search) { $query .= " AND (d.judul_dokumen LIKE :s1 OR d.kode_dokumen LIKE :s2)"; $params[':s1'] = "%$search%"; $params[':s2'] = "%$search%"; }
$query .= " ORDER BY d.uploaded_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $val) $stmt->bindValue($key, $val);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$dokumen = $stmt->fetchAll();

// 4. Category counts for pills
$catCount = $db->query("SELECT id_kategori, COUNT(*) c FROM dokumen_mutu WHERE tipe_akses='publik' AND status='Aktif' GROUP BY id_kategori")->fetchAll();
$countMap = array_column($catCount, 'c', 'id_kategori');

require_once __DIR__ . '/../includes/header-publik.php';
?>

<style>
/* ============================================================
   DOKUMEN STANDALONE ULTIMATE v11.0 — ENRICHED
============================================================ */
.dk-wrap {
    --p: #0F3D5C; --pl: #1A5A82; --pd: #092A40;
    --a: #C9A227; --al: #E8C55A;
    --bg: #F7F9FC; --card: #FFFFFF;
    --td: #1E293B; --tm: #64748B;
    --bd: #E2E8F0;
    --eo: cubic-bezier(.22, 1, .36, 1);
    --es: cubic-bezier(.34, 1.56, .64, 1);
}
.dk-wrap * { box-sizing: border-box; }
.dk-wrap svg { flex-shrink: 0; }

/* ===== SCROLL PROGRESS ===== */
.dk-progress { position: fixed; top: 0; left: 0; width: 100%; height: 4px; z-index: 1100; background: rgba(15,61,92,.06); }
.dk-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #C9A227, #E8C55A); box-shadow: 0 0 12px rgba(201,162,39,.5); transition: width .1s linear; }

/* ===== FLOATING SECTION NAV ===== */
.dk-toc {
    position: fixed; right: 24px; top: 50%; transform: translateY(-50%);
    display: flex; flex-direction: column; gap: 14px; z-index: 900;
    opacity: 0; pointer-events: none; transition: opacity .4s;
}
.dk-toc.visible { opacity: 1; pointer-events: auto; }
.dk-toc-link { width: 8px; height: 8px; border-radius: 50%; background: rgba(15,61,92,.25); position: relative; transition: .3s; display: block; text-decoration: none; }
.dk-toc-link::before {
    content: attr(data-title); position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
    background: #092A40; color: #fff; padding: 6px 12px; border-radius: 8px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
    opacity: 0; pointer-events: none; transition: .3s; box-shadow: 0 8px 20px rgba(0,0,0,.18);
}
.dk-toc-link:hover::before { opacity: 1; right: 24px; }
.dk-toc-link.active { background: #C9A227; box-shadow: 0 0 0 4px rgba(201,162,39,.2); transform: scale(1.4); }
@media (max-width: 1200px) { .dk-toc { display: none; } }

/* ===== BACK TO TOP ===== */
.dk-back-top {
    position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px;
    border-radius: 50%; border: none; cursor: pointer; z-index: 950;
    background: linear-gradient(135deg, #C9A227, #E8C55A); color: #092A40;
    display: grid; place-items: center; font-size: 22px;
    box-shadow: 0 10px 30px rgba(201,162,39,.45);
    opacity: 0; pointer-events: none; transform: translateY(20px);
    transition: .4s var(--eo);
}
.dk-back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.dk-back-top:hover { transform: translateY(-4px) scale(1.06); }

/* ===== HERO ===== */
.dk-hero {
    min-height: 78vh; position: relative; overflow: hidden;
    background: #061D2E; color: #fff; padding: 130px 0 90px;
    display: flex; align-items: center;
}
.dk-hero::before {
    content: ''; position: absolute; inset: 0; z-index: 0;
    background: radial-gradient(ellipse 70% 50% at 15% 30%, rgba(201,162,39,.25), transparent 55%),
                radial-gradient(ellipse 60% 70% at 85% 20%, rgba(26,90,130,.45), transparent 55%),
                radial-gradient(ellipse 60% 50% at 70% 80%, rgba(232,197,90,.18), transparent 55%),
                linear-gradient(160deg, #061D2E, #0F3D5C 55%, #092A40);
    animation: dkShift 16s ease-in-out infinite;
}
@keyframes dkShift { 0%,100% { filter: hue-rotate(0deg); transform: scale(1); } 50% { filter: hue-rotate(12deg); transform: scale(1.04); } }
.dk-hero::after {
    content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .35;
    background-image: radial-gradient(rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.dk-hero-inner { position: relative; z-index: 3; max-width: 900px; margin: 0 auto; text-align: center; padding: 0 24px; width: 100%; }

.dk-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 9px 20px; border-radius: 50px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.15);
    font-size: 12px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase;
    color: rgba(255,255,255,.9); margin-bottom: 28px; position: relative; overflow: hidden;
}
.dk-badge::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(201,162,39,.5), transparent);
    animation: dkShimmer 3.5s ease-in-out infinite;
}
@keyframes dkShimmer { to { left: 200%; } }
.dk-badge-dot { width: 8px; height: 8px; border-radius: 50%; background: #E8C55A; box-shadow: 0 0 14px #E8C55A; animation: dkPulse 2s ease infinite; }
@keyframes dkPulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .6; transform: scale(1.4); } }

.dk-hero h1 { font-size: clamp(40px, 6vw, 78px); font-weight: 800; line-height: 1.04; margin: 0 0 22px; letter-spacing: -.04em; color: #fff; }
.dk-hero h1 .gr {
    background: linear-gradient(120deg, #E8C55A, #C9A227, #F7E491, #C9A227);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    animation: dkShine 4s linear infinite; font-style: italic;
}
@keyframes dkShine { to { background-position: 200% center; } }
.dk-hero-lead { font-size: clamp(16px, 1.5vw, 18px); color: rgba(255,255,255,.75); max-width: 660px; margin: 0 auto 44px; line-height: 1.65; }

/* Glass search */
.dk-search {
    display: flex; gap: 6px; max-width: 720px; margin: 0 auto; align-items: center;
    background: rgba(255,255,255,.08); backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    padding: 6px; box-shadow: 0 20px 60px rgba(0,0,0,.45);
    transition: .3s;
}
.dk-search:focus-within { border-color: rgba(232,197,90,.6); box-shadow: 0 20px 60px rgba(0,0,0,.45), 0 0 0 4px rgba(201,162,39,.18); }
.dk-search-ic { display: grid; place-items: center; padding: 0 6px 0 18px; color: rgba(255,255,255,.55); font-size: 18px; flex-shrink: 0; }
.dk-search input {
    padding: 14px 8px; border: none; background: transparent;
    color: #fff; font-family: inherit; font-size: 14px; outline: none; flex: 1; min-width: 0;
}
.dk-search input::placeholder { color: rgba(255,255,255,.5); }
.dk-search button {
    padding: 14px 26px; border-radius: 50px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; font-weight: 800; font-size: 14px; font-family: inherit;
    transition: .3s; box-shadow: 0 8px 24px rgba(201,162,39,.4);
    display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.dk-search button:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,162,39,.6); }
.dk-kbd {
    padding: 3px 8px; border-radius: 6px; background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2); color: rgba(255,255,255,.7);
    font-size: 11px; font-weight: 800; font-family: monospace; flex-shrink: 0;
}
@media (max-width: 640px) { .dk-search { flex-wrap: wrap; border-radius: 22px; } .dk-kbd { display: none; } .dk-search button { width: 100%; justify-content: center; } }

/* Hero ticker */
.dk-ticker {
    margin: 28px auto 0; max-width: 760px; overflow: hidden; position: relative;
    background: rgba(255,255,255,.05); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.1); border-radius: 50px; padding: 11px 0;
    display: flex; align-items: center;
}
.dk-ticker::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 90px; z-index: 2;
    background: linear-gradient(90deg, #0A2A40, transparent); pointer-events: none;
}
.dk-ticker::after {
    content: ''; position: absolute; right: 0; top: 0; bottom: 0; width: 90px; z-index: 2;
    background: linear-gradient(270deg, #0A2A40, transparent); pointer-events: none;
}
.dk-ticker-label {
    position: absolute; left: 14px; z-index: 3; display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; border-radius: 50px; background: rgba(201,162,39,.22);
    border: 1px solid rgba(201,162,39,.4); color: #E8C55A;
    font-size: 9.5px; font-weight: 900; letter-spacing: 1.2px; text-transform: uppercase;
}
.dk-ticker-track { display: flex; gap: 44px; width: max-content; padding-left: 130px; animation: dkMarquee 42s linear infinite; }
.dk-ticker:hover .dk-ticker-track { animation-play-state: paused; }
.dk-ticker-item { color: rgba(255,255,255,.8); font-size: 12.5px; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: .25s; }
.dk-ticker-item:hover { color: #E8C55A; }
.dk-ticker-item svg { color: #C9A227; font-size: 10px; }
@keyframes dkMarquee { to { transform: translateX(-50%); } }

/* Floating stats */
.dk-hero-stats { display: flex; justify-content: center; gap: 36px; margin-top: 40px; flex-wrap: wrap; }
.dk-hs { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.dk-hs-ic { font-size: 20px; color: rgba(232,197,90,.7); margin-bottom: 4px; }
.dk-hs-n {
    font-size: 32px; font-weight: 800; line-height: 1;
    background: linear-gradient(180deg, #F7E491, #C9A227);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    letter-spacing: -.02em; display: block;
}
.dk-hs-l { font-size: 11px; color: rgba(255,255,255,.6); letter-spacing: 1.5px; text-transform: uppercase; font-weight: 600; }

/* ===== BODY ===== */
.dk-body { padding: 80px 0 100px; background: linear-gradient(180deg, #fff, #F7F9FC); }
.dk-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

/* ===== ANALYTICS PANEL ===== */
.dk-analytics {
    display: grid; grid-template-columns: 1.15fr 1fr; gap: 22px; margin-bottom: 56px;
}
@media (max-width: 900px) { .dk-analytics { grid-template-columns: 1fr; } }
.dk-an-card {
    background: #fff; border: 1px solid #E2E8F0; border-radius: 22px; padding: 28px;
    box-shadow: 0 6px 24px rgba(15,61,92,.06); position: relative; overflow: hidden;
}
.dk-an-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #C9A227, #0F3D5C, #10B981, #C9A227);
    background-size: 300% 100%; animation: dkBorderMove 7s linear infinite;
}
@keyframes dkBorderMove { to { background-position: 300% 0; } }
.dk-an-head { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
.dk-an-head svg { font-size: 20px; color: #C9A227; }
.dk-an-head h3 { font-size: 16px; color: #092A40; margin: 0; letter-spacing: -.01em; }
.dk-an-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.dk-an-row:last-child { margin-bottom: 0; }
.dk-an-lbl { width: 130px; font-size: 12.5px; font-weight: 700; color: #1E293B; flex-shrink: 0; display: flex; align-items: center; gap: 7px; }
.dk-an-lbl i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.dk-an-track { flex: 1; height: 12px; border-radius: 7px; background: #EEF2F7; overflow: hidden; }
.dk-an-fill { height: 100%; border-radius: 7px; position: relative; overflow: hidden; transform-origin: left; animation: dkGrowBar 1.3s var(--eo) backwards; }
@keyframes dkGrowBar { from { transform: scaleX(0); } }
.dk-an-fill::after {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(45deg, rgba(255,255,255,.28) 0 7px, transparent 7px 14px);
    animation: dkStripeMove .9s linear infinite;
}
@keyframes dkStripeMove { to { background-position: 20px 0; } }
.dk-an-num { width: 56px; text-align: right; font-weight: 800; font-size: 13px; color: #092A40; flex-shrink: 0; }
.dk-an-sum { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #E2E8F0; }
.dk-an-sum div { text-align: center; padding: 12px; border-radius: 14px; background: linear-gradient(135deg, rgba(201,162,39,.07), rgba(15,61,92,.03)); }
.dk-an-sum strong { display: block; font-size: 22px; font-weight: 800; color: #092A40; line-height: 1; }
.dk-an-sum span { display: block; margin-top: 5px; font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #64748B; }

/* ===== PILLS ===== */
.dk-pills { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 32px; }
.dk-pill {
    padding: 10px 22px; border-radius: 50px; font-weight: 700; font-size: 13.5px;
    text-decoration: none; transition: .3s var(--eo);
    border: 1.5px solid #E2E8F0; color: #1E293B; background: #fff;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 2px 8px rgba(15,61,92,.04); cursor: pointer;
}
.dk-pill:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,61,92,.1); }
.dk-pill.on {
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    border-color: transparent; color: #092A40;
    box-shadow: 0 10px 28px rgba(201,162,39,.4);
}
.dk-pill-c { padding: 2px 10px; border-radius: 50px; font-size: 11px; font-weight: 800; background: rgba(15,61,92,.08); color: #0F3D5C; }
.dk-pill.on .dk-pill-c { background: rgba(255,255,255,.28); color: #092A40; }

/* ===== TOOLBAR ===== */
.dk-toolbar {
    display: flex; gap: 12px; justify-content: space-between; align-items: center;
    flex-wrap: wrap; margin-bottom: 28px; padding: 14px 18px;
    background: #fff; border: 1px solid #E2E8F0; border-radius: 18px;
    box-shadow: 0 4px 18px rgba(15,61,92,.05);
}
.dk-tb-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.dk-tb-label { font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #64748B; display: inline-flex; align-items: center; gap: 6px; }
.dk-tb-btn {
    padding: 8px 15px; border-radius: 11px; border: 1.5px solid #E2E8F0;
    background: #fff; color: #1E293B; font-size: 12.5px; font-weight: 700; font-family: inherit;
    cursor: pointer; transition: .3s var(--eo);
    display: inline-flex; align-items: center; gap: 7px;
}
.dk-tb-btn:hover { border-color: rgba(201,162,39,.55); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15,61,92,.1); }
.dk-tb-btn.on { background: linear-gradient(135deg, #C9A227, #E8C55A); border-color: transparent; color: #092A40; box-shadow: 0 6px 18px rgba(201,162,39,.38); }
.dk-tb-select {
    padding: 8px 14px; border-radius: 11px; border: 1.5px solid #E2E8F0;
    background: #fff; color: #1E293B; font-size: 12.5px; font-weight: 700; font-family: inherit;
    cursor: pointer; outline: none; transition: .3s;
}
.dk-tb-select:focus { border-color: rgba(201,162,39,.6); box-shadow: 0 0 0 3px rgba(201,162,39,.14); }
.dk-tb-count {
    font-size: 12.5px; font-weight: 700; color: #64748B;
    padding: 7px 14px; border-radius: 50px; background: rgba(15,61,92,.05);
    display: inline-flex; align-items: center; gap: 7px;
}
.dk-tb-count b { color: #092A40; font-size: 13.5px; }

/* ===== CARDS ===== */
.dk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; perspective: 1400px; }
.dk-grid.list-view { grid-template-columns: 1fr; }

.dk-card {
    background: #fff; border-radius: 22px; overflow: hidden;
    border: 1px solid #E2E8F0; position: relative;
    transition: transform .5s var(--eo), box-shadow .4s, border-color .4s;
    transform-style: preserve-3d;
    box-shadow: 0 6px 24px rgba(15,61,92,.06);
    display: flex; flex-direction: column;
}
.dk-card::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(500px circle at var(--mx,50%) var(--my,50%), rgba(201,162,39,.12), transparent 40%);
    opacity: 0; transition: .4s; pointer-events: none; z-index: 3;
}
.dk-card:hover::before { opacity: 1; }
.dk-card:hover { transform: translateY(-8px) rotateX(2deg) rotateY(-2deg); box-shadow: 0 30px 60px rgba(15,61,92,.14); border-color: rgba(201,162,39,.4); }
.dk-card.hidden { display: none; }

/* List view layout */
.dk-grid.list-view .dk-card { flex-direction: row; align-items: stretch; }
.dk-grid.list-view .dk-cover { width: 190px; flex-shrink: 0; height: auto; min-height: 150px; }
.dk-grid.list-view .dk-body-card { flex: 1; justify-content: center; }
.dk-grid.list-view .dk-body-card p { -webkit-line-clamp: 3; }
.dk-grid.list-view .dk-foot {
    width: 250px; flex-shrink: 0; border-top: none;
    border-left: 1px dashed #E2E8F0; flex-direction: column;
    justify-content: center; align-items: flex-start; gap: 12px;
}
@media (max-width: 820px) {
    .dk-grid.list-view .dk-card { flex-direction: column; }
    .dk-grid.list-view .dk-cover { width: 100%; min-height: 160px; }
    .dk-grid.list-view .dk-foot { width: 100%; border-left: none; border-top: 1px dashed #E2E8F0; flex-direction: row; align-items: center; justify-content: space-between; }
}

.dk-cover { height: 160px; position: relative; overflow: hidden; background: linear-gradient(135deg, #F7F9FC, #EEF3F8); display: grid; place-items: center; }
.dk-cover::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, var(--c1, #0F3D5C), var(--c2, #1A5A82)); opacity: .08; }
.dk-cover-ic {
    width: 78px; height: 100px; border-radius: 8px;
    background: linear-gradient(135deg, var(--c1, #0F3D5C), var(--c2, #1A5A82));
    color: #fff; display: grid; place-items: center; font-size: 36px;
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
    position: relative; transition: .5s var(--es);
}
.dk-cover-ic svg { stroke-width: 1.6; }
.dk-card:hover .dk-cover-ic { transform: translateY(-6px) rotate(-4deg); }
.dk-cover-ext {
    position: absolute; bottom: 12px; right: 12px;
    padding: 4px 10px; border-radius: 6px;
    background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
    font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
    color: var(--c1, #0F3D5C);
}
.dk-cat {
    position: absolute; top: 14px; left: 14px;
    padding: 5px 12px; border-radius: 50px;
    background: rgba(255,255,255,.95); backdrop-filter: blur(10px);
    color: #092A40; font-size: 10.5px; font-weight: 800;
    letter-spacing: .8px; text-transform: uppercase;
    box-shadow: 0 4px 14px rgba(0,0,0,.1); max-width: 62%;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.dk-fav {
    position: absolute; top: 12px; right: 12px; z-index: 4;
    width: 36px; height: 36px; border-radius: 11px; border: none; cursor: pointer;
    background: rgba(255,255,255,.92); backdrop-filter: blur(10px);
    color: #94A3B8; display: grid; place-items: center; font-size: 17px;
    box-shadow: 0 4px 14px rgba(0,0,0,.12); transition: .3s var(--es);
}
.dk-fav:hover { transform: scale(1.14); color: #C9A227; }
.dk-fav.on { background: linear-gradient(135deg, #C9A227, #E8C55A); color: #fff; box-shadow: 0 6px 18px rgba(201,162,39,.45); }

.dk-body-card { padding: 24px; flex: 1; display: flex; flex-direction: column; }
.dk-code {
    display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 6px;
    background: rgba(15,61,92,.06); color: #0F3D5C;
    font-size: 11px; font-weight: 700; letter-spacing: .6px;
    margin-bottom: 10px; width: fit-content; max-width: 100%;
}
.dk-code svg { font-size: 11px; opacity: .6; }
.dk-body-card h3 {
    font-size: 16px; color: #092A40; margin: 0 0 10px;
    letter-spacing: -.01em; line-height: 1.35;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.dk-body-card p {
    font-size: 13.5px; color: #64748B; line-height: 1.55; margin: 0 0 16px; flex: 1;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.dk-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
.dk-tag {
    padding: 3px 9px; border-radius: 6px; font-size: 10px; font-weight: 800;
    letter-spacing: .6px; text-transform: uppercase;
    background: rgba(16,185,129,.1); color: #047857;
    display: inline-flex; align-items: center; gap: 4px;
}
.dk-tag svg { font-size: 10px; }
.dk-tag.ver { background: rgba(15,61,92,.07); color: #0F3D5C; }

.dk-foot { padding: 14px 24px; border-top: 1px dashed #E2E8F0; display: flex; justify-content: space-between; align-items: center; gap: 12px; background: rgba(15,61,92,.02); }
.dk-meta { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.dk-meta small { font-size: 10.5px; color: #64748B; letter-spacing: .5px; text-transform: uppercase; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
.dk-meta strong { font-size: 12.5px; color: #092A40; display: inline-flex; align-items: center; gap: 5px; }
.dk-actions { display: flex; gap: 7px; align-items: center; flex-shrink: 0; }
.dk-info-btn {
    width: 38px; height: 38px; border-radius: 50%; border: 1.5px solid #E2E8F0;
    background: #fff; color: #0F3D5C; cursor: pointer; display: grid; place-items: center;
    font-size: 16px; transition: .3s var(--eo);
}
.dk-info-btn:hover { border-color: rgba(201,162,39,.6); background: rgba(201,162,39,.08); color: #C9A227; transform: translateY(-2px); }
.dk-dl {
    padding: 10px 18px; border-radius: 50px; font-size: 12.5px; font-weight: 800;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
    transition: .3s; box-shadow: 0 6px 18px rgba(201,162,39,.35);
    position: relative; overflow: hidden; border: none; cursor: pointer; font-family: inherit;
}
.dk-dl::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    transition: left .6s;
}
.dk-dl:hover::before { left: 150%; }
.dk-dl:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(201,162,39,.5), 0 0 15px rgba(232,197,90,.4); }

/* ===== EMPTY ===== */
.dk-empty {
    grid-column: 1/-1; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; border: 2px dashed #E2E8F0;
    position: relative; overflow: hidden;
}
.dk-empty::before { content: ''; position: absolute; inset: 0; background: radial-gradient(600px circle at 50% 30%, rgba(201,162,39,.08), transparent 60%); }
.dk-empty-ic { font-size: 72px; margin-bottom: 20px; opacity: .45; position: relative; animation: dkFloat 4s ease-in-out infinite; color: #64748B; display: flex; justify-content: center; }
.dk-empty-ic svg { stroke-width: 1.4; }
@keyframes dkFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.dk-empty h3 { font-size: 22px; color: #092A40; margin: 0 0 8px; letter-spacing: -.01em; position: relative; }
.dk-empty p { color: #64748B; font-size: 14px; max-width: 420px; margin: 0 auto; position: relative; line-height: 1.6; }

/* ===== HISTORY ===== */
.dk-history { margin-top: 48px; background: #fff; border: 1px solid #E2E8F0; border-radius: 22px; overflow: hidden; box-shadow: 0 6px 24px rgba(15,61,92,.06); }
.dk-history-head {
    padding: 20px 26px; display: flex; align-items: center; justify-content: space-between; gap: 14px;
    background: linear-gradient(135deg, rgba(15,61,92,.04), rgba(201,162,39,.05));
    border-bottom: 1px solid #E2E8F0; cursor: pointer; transition: .3s;
}
.dk-history-head:hover { background: linear-gradient(135deg, rgba(15,61,92,.07), rgba(201,162,39,.09)); }
.dk-history-head h3 { font-size: 15px; color: #092A40; margin: 0; display: flex; align-items: center; gap: 9px; letter-spacing: -.01em; }
.dk-history-head h3 svg { color: #C9A227; font-size: 18px; }
.dk-history-chev { color: #64748B; font-size: 18px; transition: transform .35s var(--eo); display: flex; }
.dk-history.open .dk-history-chev { transform: rotate(180deg); }
.dk-history-body { max-height: 0; overflow: hidden; transition: max-height .45s var(--eo); }
.dk-history.open .dk-history-body { max-height: 600px; overflow-y: auto; }
.dk-history-list { display: flex; flex-direction: column; }
.dk-history-item {
    padding: 15px 26px; display: flex; align-items: center; gap: 14px;
    border-bottom: 1px dashed #E2E8F0; transition: .25s; text-decoration: none; color: inherit;
}
.dk-history-item:last-child { border-bottom: none; }
.dk-history-item:hover { background: rgba(201,162,39,.05); padding-left: 32px; }
.dk-history-item .h-ic {
    width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 18px; color: #fff;
    background: linear-gradient(135deg, var(--c1, #0F3D5C), var(--c2, #1A5A82));
}
.dk-history-item .h-tx { flex: 1; min-width: 0; }
.dk-history-item .h-tx strong { display: block; font-size: 13.5px; color: #092A40; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dk-history-item .h-tx small { display: block; font-size: 11px; color: #64748B; margin-top: 2px; }
.dk-history-empty { padding: 40px 26px; text-align: center; color: #64748B; font-size: 13.5px; }
.dk-history-empty svg { font-size: 40px; opacity: .3; display: block; margin: 0 auto 12px; }
.dk-history-clear {
    padding: 7px 15px; border-radius: 50px; border: 1.5px solid #E2E8F0; background: #fff;
    color: #64748B; font-size: 11.5px; font-weight: 700; cursor: pointer; font-family: inherit; transition: .3s;
}
.dk-history-clear:hover { border-color: #EF4444; color: #EF4444; background: rgba(239,68,68,.06); }

/* ===== NOTE ===== */
.dk-note {
    margin-top: 28px; padding: 22px 28px;
    background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(15,61,92,.04));
    border: 1px solid rgba(201,162,39,.25); border-left: 5px solid #C9A227;
    border-radius: 18px; display: flex; gap: 18px; align-items: flex-start;
}
.dk-note-ic {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; display: grid; place-items: center; font-size: 20px;
    box-shadow: 0 6px 18px rgba(201,162,39,.35);
}
.dk-note h4 { font-size: 15px; color: #092A40; margin: 0 0 4px; }
.dk-note p { font-size: 14px; color: #64748B; line-height: 1.55; margin: 0; }
.dk-note strong { color: #C9A227; }

/* ===== PAGINATION ===== */
.dk-pagination { display: flex; justify-content: center; gap: 8px; margin-top: 56px; flex-wrap: wrap; }
.dk-pg {
    min-width: 42px; height: 42px; padding: 0 14px; border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; text-decoration: none;
    border: 1.5px solid #E2E8F0; color: #1E293B; background: #fff; transition: .3s var(--eo);
}
.dk-pg:hover { border-color: rgba(201,162,39,.5); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,61,92,.1); }
.dk-pg.on { background: linear-gradient(135deg, #C9A227, #E8C55A); border-color: transparent; color: #092A40; box-shadow: 0 8px 22px rgba(201,162,39,.4); }
.dk-pg.disabled { opacity: .45; pointer-events: none; cursor: default; }
.dk-pg-dots { padding: 0 10px; color: #64748B; display: flex; align-items: center; font-weight: 700; }

/* ===== MODAL ===== */
.dk-modal { position: fixed; inset: 0; z-index: 2000; display: none; align-items: center; justify-content: center; padding: 24px; }
.dk-modal.open { display: flex; }
.dk-modal-bd { position: absolute; inset: 0; background: rgba(6,29,46,.78); backdrop-filter: blur(8px); animation: dkFade .3s ease; }
@keyframes dkFade { from { opacity: 0; } to { opacity: 1; } }
.dk-modal-box {
    position: relative; z-index: 1; background: #fff; border-radius: 26px;
    max-width: 580px; width: 100%; max-height: 86vh; overflow-y: auto;
    box-shadow: 0 40px 100px rgba(0,0,0,.45); animation: dkPop .45s var(--es);
}
@keyframes dkPop { from { opacity: 0; transform: translateY(30px) scale(.94); } to { opacity: 1; transform: none; } }
.dk-modal-hero {
    padding: 32px; background: linear-gradient(135deg, #061D2E, #0F3D5C 55%, #12365B);
    color: #fff; display: flex; gap: 20px; align-items: center; position: relative; overflow: hidden;
}
.dk-modal-hero::after {
    content: ''; position: absolute; bottom: -70px; right: -70px; width: 240px; height: 240px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
}
.dk-modal-ic {
    width: 74px; height: 92px; border-radius: 10px; flex-shrink: 0; position: relative; z-index: 1;
    background: linear-gradient(135deg, var(--c1, #C9A227), var(--c2, #E8C55A));
    color: #fff; display: grid; place-items: center; font-size: 34px;
    box-shadow: 0 12px 32px rgba(0,0,0,.35);
}
.dk-modal-ic svg { stroke-width: 1.6; }
.dk-modal-tt { position: relative; z-index: 1; min-width: 0; }
.dk-modal-tt .m-cat {
    display: inline-block; padding: 4px 12px; border-radius: 50px;
    background: rgba(201,162,39,.2); border: 1px solid rgba(201,162,39,.35);
    color: #E8C55A; font-size: 10px; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; margin-bottom: 10px;
}
.dk-modal-tt h3 { font-size: 19px; margin: 0; line-height: 1.35; letter-spacing: -.01em; color: #fff; }
.dk-modal-close {
    position: absolute; top: 16px; right: 16px; z-index: 2;
    width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer;
    background: rgba(255,255,255,.14); color: #fff; display: grid; place-items: center;
    font-size: 17px; transition: .3s; backdrop-filter: blur(8px);
}
.dk-modal-close:hover { background: rgba(239,68,68,.85); transform: rotate(90deg); }
.dk-modal-body { padding: 28px 32px 32px; }
.dk-modal-rows { display: flex; flex-direction: column; gap: 0; margin-bottom: 22px; }
.dk-modal-row { display: flex; justify-content: space-between; gap: 16px; padding: 13px 0; border-bottom: 1px dashed #E2E8F0; align-items: flex-start; }
.dk-modal-row:last-child { border-bottom: none; }
.dk-modal-row .k { font-size: 12px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: #64748B; display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0; }
.dk-modal-row .k svg { color: #C9A227; font-size: 14px; }
.dk-modal-row .v { font-size: 13.5px; font-weight: 700; color: #092A40; text-align: right; word-break: break-word; }
.dk-modal-desc {
    padding: 16px 18px; border-radius: 14px; background: #F7F9FC; border: 1px solid #E2E8F0;
    font-size: 13.5px; color: #64748B; line-height: 1.65; margin-bottom: 22px;
}
.dk-modal-acts { display: flex; gap: 10px; flex-wrap: wrap; }
.dk-modal-acts .dk-dl { flex: 1; justify-content: center; padding: 13px 20px; font-size: 13.5px; }
.dk-m-btn {
    padding: 13px 20px; border-radius: 50px; border: 1.5px solid #E2E8F0; background: #fff;
    color: #0F3D5C; font-size: 13px; font-weight: 800; font-family: inherit; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; transition: .3s var(--eo);
}
.dk-m-btn:hover { border-color: rgba(201,162,39,.6); background: rgba(201,162,39,.07); color: #C9A227; transform: translateY(-2px); }
.dk-m-btn.on { background: linear-gradient(135deg, #C9A227, #E8C55A); border-color: transparent; color: #092A40; }

/* ===== REVEAL ===== */
.dk-rev { opacity: 0; transform: translateY(30px); transition: opacity .7s var(--eo), transform .7s var(--eo); }
.dk-rev.in { opacity: 1; transform: none; }

@media (max-width: 640px) {
    .dk-hero { padding: 110px 0 70px; }
    .dk-hero-stats { gap: 22px; }
    .dk-hs-n { font-size: 26px; }
    .dk-toolbar { flex-direction: column; align-items: stretch; }
    .dk-tb-group { justify-content: center; }
    .dk-tb-count { justify-content: center; }
    .dk-modal-hero { flex-direction: column; text-align: center; padding: 28px 22px; }
    .dk-modal-body { padding: 24px 22px 28px; }
}
</style>

<div class="dk-progress"><div class="dk-progress-bar" id="dkProg"></div></div>
<nav class="dk-toc" id="dkToc">
    <a href="#dkBeranda" class="dk-toc-link" data-title="Beranda"></a>
    <a href="#dkStatistik" class="dk-toc-link" data-title="Statistik Pustaka"></a>
    <a href="#dkKatalog" class="dk-toc-link" data-title="Katalog Dokumen"></a>
    <a href="#dkRiwayat" class="dk-toc-link" data-title="Riwayat Unduhan"></a>
    <a href="#dkBantuan" class="dk-toc-link" data-title="Informasi Akses"></a>
</nav>

<div class="dk-wrap">

<section class="dk-hero" id="dkBeranda">
    <div class="dk-hero-inner">
        <span class="dk-badge"><span class="dk-badge-dot"></span> Pusat Dokumen Publik</span>
        <h1>Dokumen <span class="gr">Mutu</span><br>Akses Terbuka</h1>
        <p class="dk-hero-lead">Kebijakan, standar, manual, formulir, dan publikasi resmi LPM tersedia untuk diunduh secara transparan.</p>
        <form method="GET" class="dk-search" action="">
            <span class="dk-search-ic"><?= $ic['search'] ?></span>
            <input type="text" name="search" id="dkSearchInput" placeholder="Cari judul atau kode dokumen..." value="<?= Security::e($search) ?>" autocomplete="off">
            <span class="dk-kbd">/</span>
            <?php if ($kategori !== 'all'): ?>
                <input type="hidden" name="kategori" value="<?= Security::e($kategori) ?>">
            <?php endif; ?>
            <button type="submit"><?= $ic['search'] ?> Cari</button>
        </form>

        <?php if (!empty($tickerDocs)): ?>
        <div class="dk-ticker">
            <span class="dk-ticker-label"><?= $ic['clock'] ?> Terbaru</span>
            <div class="dk-ticker-track">
                <?php for ($r = 0; $r < 2; $r++): foreach ($tickerDocs as $td): ?>
                    <span class="dk-ticker-item"><?= $ic['circle_dot'] ?> <?= Security::e(mb_strimwidth($td['judul_dokumen'], 0, 55, '...')) ?><?= !empty($td['kode_dokumen']) ? ' · ' . Security::e($td['kode_dokumen']) : '' ?></span>
                <?php endforeach; endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="dk-hero-stats">
            <div class="dk-hs"><span class="dk-hs-ic"><?= $ic['layers'] ?></span><span class="dk-hs-n" data-count="<?= $absoluteTotal ?>"><?= number_format($absoluteTotal) ?></span><span class="dk-hs-l">Total Dokumen</span></div>
            <div class="dk-hs"><span class="dk-hs-ic"><?= $ic['folder_open'] ?></span><span class="dk-hs-n" data-count="<?= $totalKategori ?>"><?= $totalKategori ?></span><span class="dk-hs-l">Kategori</span></div>
            <div class="dk-hs"><span class="dk-hs-ic"><?= $ic['package'] ?></span><span class="dk-hs-n"><?= dkFormatBytes($totalLibrarySize) ?></span><span class="dk-hs-l">Ukuran Pustaka</span></div>
            <div class="dk-hs"><span class="dk-hs-ic"><?= $ic['globe'] ?></span><span class="dk-hs-n">24/7</span><span class="dk-hs-l">Akses Online</span></div>
            <div class="dk-hs"><span class="dk-hs-ic"><?= $ic['badge_check'] ?></span><span class="dk-hs-n">100%</span><span class="dk-hs-l">Gratis</span></div>
        </div>
    </div>
</section>

<section class="dk-body">
    <div class="dk-container">

        <!-- ===== ANALYTICS ===== -->
        <div class="dk-analytics dk-rev" id="dkStatistik">
            <div class="dk-an-card">
                <div class="dk-an-head"><?= $ic['layers'] ?><h3>Distribusi per Kategori</h3></div>
                <?php
                $maxCat = max(1, ...array_map(function($k) use ($countMap) { return (int)($countMap[$k['id_kategori']] ?? 0); }, $kategoriList ?: [['id_kategori'=>0]]));
                $catColors = ['#C9A227', '#0F3D5C', '#10B981', '#3B82F6', '#8B5CF6', '#EA580C', '#EC4899', '#14B8A6'];
                if (empty($kategoriList)): ?>
                    <p style="color:#64748B;font-size:13.5px;margin:0;">Belum ada kategori dokumen.</p>
                <?php else: foreach ($kategoriList as $ci => $kat):
                    $cnt = (int)($countMap[$kat['id_kategori']] ?? 0);
                    $pct = $maxCat > 0 ? round(($cnt / $maxCat) * 100) : 0;
                    $clr = $catColors[$ci % count($catColors)];
                ?>
                    <div class="dk-an-row">
                        <span class="dk-an-lbl"><i style="background:<?= $clr ?>"></i> <?= Security::e(mb_strimwidth($kat['nama_kategori'], 0, 18, '…')) ?></span>
                        <div class="dk-an-track"><div class="dk-an-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,<?= $clr ?>,<?= $clr ?>CC);animation-delay:<?= $ci * 0.08 ?>s;"></div></div>
                        <span class="dk-an-num"><?= $cnt ?> dok</span>
                    </div>
                <?php endforeach; endif; ?>
                <div class="dk-an-sum">
                    <div><strong><?= number_format($absoluteTotal) ?></strong><span>Total Dokumen</span></div>
                    <div><strong><?= $totalKategori ?></strong><span>Total Kategori</span></div>
                </div>
            </div>

            <div class="dk-an-card">
                <div class="dk-an-head"><?= $ic['file'] ?><h3>Komposisi Tipe Berkas</h3></div>
                <?php
                $extMax = max(1, ...array_values($extBreakdown));
                $extMeta = [
                    'PDF'  => ['label' => 'PDF Dokumen',  'color' => '#DC2626'],
                    'DOC'  => ['label' => 'Word / Teks',  'color' => '#2563EB'],
                    'XLS'  => ['label' => 'Spreadsheet',  'color' => '#059669'],
                    'PPT'  => ['label' => 'Presentasi',   'color' => '#EA580C'],
                    'LAIN' => ['label' => 'Lainnya',      'color' => '#64748B'],
                ];
                foreach ($extMeta as $ek => $em):
                    $cnt = (int)($extBreakdown[$ek] ?? 0);
                    $pct = $extMax > 0 ? round(($cnt / $extMax) * 100) : 0;
                ?>
                    <div class="dk-an-row">
                        <span class="dk-an-lbl"><i style="background:<?= $em['color'] ?>"></i> <?= $em['label'] ?></span>
                        <div class="dk-an-track"><div class="dk-an-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,<?= $em['color'] ?>,<?= $em['color'] ?>CC);"></div></div>
                        <span class="dk-an-num"><?= $cnt ?> dok</span>
                    </div>
                <?php endforeach; ?>
                <div class="dk-an-sum">
                    <div><strong><?= dkFormatBytes($totalLibrarySize) ?></strong><span>Total Ukuran</span></div>
                    <div><strong><?= $absoluteTotal > 0 ? dkFormatBytes($totalLibrarySize / max(1, $absoluteTotal)) : '—' ?></strong><span>Rata-rata File</span></div>
                </div>
            </div>
        </div>

        <!-- ===== CATEGORY PILLS ===== -->
        <div class="dk-pills dk-rev">
            <a href="?kategori=all<?= $search ? '&search=' . urlencode($search) : '' ?>" class="dk-pill <?= $kategori === 'all' ? 'on' : '' ?>"><?= $ic['folder_open'] ?> Semua <span class="dk-pill-c"><?= $absoluteTotal ?></span></a>
            <?php foreach ($kategoriList as $kat): ?>
                <a href="?kategori=<?= $kat['id_kategori'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="dk-pill <?= $kategori == $kat['id_kategori'] ? 'on' : '' ?>">
                    <?= Security::e($kat['nama_kategori']) ?> <span class="dk-pill-c"><?= $countMap[$kat['id_kategori']] ?? 0 ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ===== TOOLBAR ===== -->
        <div class="dk-toolbar dk-rev" id="dkKatalog">
            <div class="dk-tb-group">
                <span class="dk-tb-label"><?= $ic['list'] ?> Tampilan</span>
                <button type="button" class="dk-tb-btn dk-view on" data-view="grid"><?= $ic['grid'] ?> Grid</button>
                <button type="button" class="dk-tb-btn dk-view" data-view="list"><?= $ic['list'] ?> List</button>
            </div>
            <div class="dk-tb-group">
                <span class="dk-tb-label"><?= $ic['sort'] ?> Urutkan</span>
                <select class="dk-tb-select" id="dkSort">
                    <option value="new">Terbaru</option>
                    <option value="old">Terlama</option>
                    <option value="az">Judul A → Z</option>
                    <option value="za">Judul Z → A</option>
                    <option value="big">Ukuran Terbesar</option>
                    <option value="small">Ukuran Terkecil</option>
                </select>
            </div>
            <div class="dk-tb-group">
                <span class="dk-tb-label"><?= $ic['file'] ?> Tipe</span>
                <button type="button" class="dk-tb-btn dk-type on" data-type="all">Semua</button>
                <button type="button" class="dk-tb-btn dk-type" data-type="PDF">PDF <span class="dk-pill-c"><?= $extBreakdown['PDF'] ?></span></button>
                <button type="button" class="dk-tb-btn dk-type" data-type="DOC">DOC <span class="dk-pill-c"><?= $extBreakdown['DOC'] ?></span></button>
                <button type="button" class="dk-tb-btn dk-type" data-type="XLS">XLS <span class="dk-pill-c"><?= $extBreakdown['XLS'] ?></span></button>
                <button type="button" class="dk-tb-btn dk-type" data-type="PPT">PPT <span class="dk-pill-c"><?= $extBreakdown['PPT'] ?></span></button>
                <button type="button" class="dk-tb-btn dk-favfilter" data-fav="1"><?= $ic['bookmark'] ?> Favorit</button>
            </div>
            <div class="dk-tb-count"><?= $ic['eye'] ?> Menampilkan <b id="dkVisible">0</b> dari <b><?= count($dokumen) ?></b> dokumen</div>
        </div>

        <!-- ===== DOCUMENT GRID ===== -->
        <div class="dk-grid" id="dkGrid">
            <?php if (empty($dokumen)): ?>
                <div class="dk-empty dk-rev">
                    <div class="dk-empty-ic"><?= $ic['inbox'] ?></div>
                    <h3><?= $search ? 'Tidak ada dokumen yang cocok' : 'Belum ada dokumen' ?></h3>
                    <p><?= $search ? 'Coba ubah kata kunci pencarian atau pilih kategori lain.' : 'Dokumen untuk kategori ini akan segera ditambahkan.' ?></p>
                </div>
            <?php endif; ?>

            <?php foreach ($dokumen as $doc):
                $colors = dkColor($doc['file_path']);
                $ext = strtoupper(pathinfo($doc['file_path'] ?? '', PATHINFO_EXTENSION));
                $extGroup = dkExtGroup($doc['file_path'] ?? '');
                $sizeBytes = getFileSizeBytes($doc['file_path'] ?? '');
                $ts = $doc['uploaded_at'] ? strtotime($doc['uploaded_at']) : 0;
                $dlUrl = '/download.php?id=' . (int)$doc['id_dokumen'] . '&type=publik';
            ?>
            <article class="dk-card dk-rev"
                     style="--c1:<?= $colors[0] ?>;--c2:<?= $colors[1] ?>;"
                     data-id="<?= (int)$doc['id_dokumen'] ?>"
                     data-ext="<?= Security::e($extGroup) ?>"
                     data-title="<?= Security::e(mb_strtolower($doc['judul_dokumen'] ?? '')) ?>"
                     data-date="<?= $ts ?>"
                     data-size="<?= $sizeBytes ?>"
                     data-judul="<?= Security::e($doc['judul_dokumen'] ?? '') ?>"
                     data-kode="<?= Security::e($doc['kode_dokumen'] ?? '') ?>"
                     data-kategori="<?= Security::e($doc['nama_kategori'] ?? '—') ?>"
                     data-deskripsi="<?= Security::e($doc['deskripsi'] ?? 'Dokumen resmi penjaminan mutu.') ?>"
                     data-ukuran="<?= Security::e(getFileSize($doc['file_path'] ?? '')) ?>"
                     data-tanggal="<?= $ts ? date('d F Y, H:i', $ts) : '—' ?>"
                     data-versi="<?= (int)($doc['versi'] ?? 1) ?>"
                     data-file="<?= Security::e($ext ?: 'FILE') ?>"
                     data-url="<?= Security::e($dlUrl) ?>">
                <div class="dk-cover">
                    <span class="dk-cat"><?= Security::e($doc['nama_kategori']) ?></span>
                    <button type="button" class="dk-fav" data-id="<?= (int)$doc['id_dokumen'] ?>" aria-label="Simpan ke favorit"><?= $ic['bookmark'] ?></button>
                    <div class="dk-cover-ic"><?= dkIcon($doc['file_path']) ?></div>
                    <span class="dk-cover-ext"><?= Security::e($ext ?: 'FILE') ?></span>
                </div>
                <div class="dk-body-card">
                    <span class="dk-code"><?= $ic['hash'] ?> <?= Security::e($doc['kode_dokumen'] ?: '—') ?></span>
                    <h3><?= Security::e($doc['judul_dokumen']) ?></h3>
                    <p><?= Security::e(mb_strimwidth($doc['deskripsi'] ?? 'Dokumen resmi penjaminan mutu.', 0, 120, '...')) ?></p>
                    <div class="dk-tags">
                        <span class="dk-tag"><?= $ic['shield_check'] ?> Resmi</span>
                        <span class="dk-tag ver"><?= $ic['package'] ?> <?= Security::e(getFileSize($doc['file_path'])) ?></span>
                        <span class="dk-tag ver"><?= $ic['file'] ?> v<?= (int)($doc['versi'] ?? 1) ?></span>
                    </div>
                </div>
                <div class="dk-foot">
                    <div class="dk-meta">
                        <small><?= $ic['calendar'] ?> <?= $ts ? date('d M Y', $ts) : '—' ?></small>
                        <strong><?= $ic['package'] ?> <?= Security::e(getFileSize($doc['file_path'])) ?> • v<?= (int)($doc['versi'] ?? 1) ?></strong>
                    </div>
                    <div class="dk-actions">
                        <button type="button" class="dk-info-btn" aria-label="Detail dokumen"><?= $ic['info'] ?></button>
                        <a href="<?= $dlUrl ?>" class="dk-dl dk-dl-link" data-id="<?= (int)$doc['id_dokumen'] ?>" data-judul="<?= Security::e($doc['judul_dokumen'] ?? '') ?>" data-ext="<?= Security::e($extGroup) ?>" data-c1="<?= $colors[0] ?>" data-c2="<?= $colors[1] ?>" data-ukuran="<?= Security::e(getFileSize($doc['file_path'] ?? '')) ?>"><?= $ic['download'] ?> Unduh</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

            <div class="dk-empty" id="dkNoResult" style="display:none;">
                <div class="dk-empty-ic"><?= $ic['search'] ?></div>
                <h3>Tidak ada yang cocok dengan filter</h3>
                <p>Kombinasi filter tipe berkas dan favorit yang Anda pilih tidak menghasilkan dokumen. Silakan atur ulang filter.</p>
            </div>
        </div>

        <!-- ===== PAGINATION ===== -->
        <?php if ($totalPages > 1): ?>
        <div class="dk-pagination dk-rev">
            <?php
            $qp = [];
            if ($kategori !== 'all') $qp['kategori'] = $kategori;
            if ($search) $qp['search'] = $search;
            $baseQuery = http_build_query($qp);
            $sep = $baseQuery ? '&' : '';
            ?>
            <?php if ($page > 1): ?>
                <a href="?<?= $baseQuery ?><?= $sep ?>page=<?= $page - 1 ?>" class="dk-pg"><?= $ic['arrow_left'] ?></a>
            <?php else: ?>
                <span class="dk-pg disabled"><?= $ic['arrow_left'] ?></span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            if ($start > 1) echo '<span class="dk-pg-dots">…</span>';
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?<?= $baseQuery ?><?= $sep ?>page=<?= $i ?>" class="dk-pg <?= $page === $i ? 'on' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($end < $totalPages) echo '<span class="dk-pg-dots">…</span>'; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= $baseQuery ?><?= $sep ?>page=<?= $page + 1 ?>" class="dk-pg"><?= $ic['arrow_right'] ?></a>
            <?php else: ?>
                <span class="dk-pg disabled"><?= $ic['arrow_right'] ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ===== RIWAYAT UNDUHAN ===== -->
        <div class="dk-history dk-rev" id="dkRiwayat">
            <div class="dk-history-head" id="dkHistHead">
                <h3><?= $ic['history'] ?> Riwayat Unduhan Anda <span style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.4px;">(tersimpan di perangkat ini)</span></h3>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button type="button" class="dk-history-clear" id="dkHistClear" style="display:none;">Bersihkan</button>
                    <span class="dk-history-chev"><?= $ic['arrow_up'] ?></span>
                </div>
            </div>
            <div class="dk-history-body">
                <div class="dk-history-list" id="dkHistList">
                    <div class="dk-history-empty"><?= $ic['history'] ?> Belum ada riwayat unduhan pada perangkat ini.</div>
                </div>
            </div>
        </div>

        <!-- ===== NOTE ===== -->
        <div class="dk-note dk-rev" id="dkBantuan">
            <div class="dk-note-ic"><?= $ic['lightbulb'] ?></div>
            <div>
                <h4>Hak Akses & Versi Dokumen</h4>
                <p>Seluruh dokumen publik dapat diunduh bebas. Dokumen internal hanya tersedia bagi civitas akademika melalui portal <strong>SIM-Mutu</strong> setelah login. <?= $ic['lock'] ?></p>
            </div>
        </div>
    </div>
</section>

</div>

<!-- ===== MODAL DETAIL ===== -->
<div class="dk-modal" id="dkModal" role="dialog" aria-modal="true">
    <div class="dk-modal-bd" data-close></div>
    <div class="dk-modal-box">
        <div class="dk-modal-hero" id="dkModalHero">
            <button type="button" class="dk-modal-close" data-close aria-label="Tutup"><?= $ic['x'] ?></button>
            <div class="dk-modal-ic" id="dkModalIc"><?= $ic['file'] ?></div>
            <div class="dk-modal-tt">
                <span class="m-cat" id="dkModalCat">—</span>
                <h3 id="dkModalTitle">—</h3>
            </div>
        </div>
        <div class="dk-modal-body">
            <div class="dk-modal-desc" id="dkModalDesc">—</div>
            <div class="dk-modal-rows">
                <div class="dk-modal-row"><span class="k"><?= $ic['hash'] ?> Kode Dokumen</span><span class="v" id="dkModalKode">—</span></div>
                <div class="dk-modal-row"><span class="k"><?= $ic['file'] ?> Format Berkas</span><span class="v" id="dkModalExt">—</span></div>
                <div class="dk-modal-row"><span class="k"><?= $ic['package'] ?> Ukuran Berkas</span><span class="v" id="dkModalSize">—</span></div>
                <div class="dk-modal-row"><span class="k"><?= $ic['shield_check'] ?> Versi</span><span class="v" id="dkModalVer">—</span></div>
                <div class="dk-modal-row"><span class="k"><?= $ic['calendar'] ?> Tanggal Unggah</span><span class="v" id="dkModalDate">—</span></div>
            </div>
            <div class="dk-modal-acts">
                <a href="#" class="dk-dl" id="dkModalDl"><?= $ic['download'] ?> Unduh Dokumen</a>
                <button type="button" class="dk-m-btn" id="dkModalCopy"><?= $ic['link'] ?> Salin Tautan</button>
                <button type="button" class="dk-m-btn" id="dkModalFav"><?= $ic['bookmark'] ?> Favorit</button>
            </div>
        </div>
    </div>
</div>


<script>
(function () {
    'use strict';

    var ICON_FAV      = <?= json_encode($ic['bookmark']) ?>;
    var ICON_FAV_ON   = <?= json_encode($ic['bookmark_filled']) ?>;
    var ICON_CHECK    = <?= json_encode($ic['check']) ?>;
    var ICON_LINK     = <?= json_encode($ic['link']) ?>;
    var ICON_FILE     = <?= json_encode($ic['file']) ?>;
    var ICON_PDF      = <?= json_encode($ic['file_pdf']) ?>;
    var ICON_DOC      = <?= json_encode($ic['file_doc']) ?>;
    var ICON_XLS      = <?= json_encode($ic['file_xls']) ?>;
    var ICON_PPT      = <?= json_encode($ic['file_ppt']) ?>;
    var ICON_HISTORY  = <?= json_encode($ic['history']) ?>;
    var ICON_CALENDAR = <?= json_encode($ic['calendar']) ?>;
    var ICON_PACKAGE  = <?= json_encode($ic['package']) ?>;

    function iconFor(ext) {
        return ({ PDF: ICON_PDF, DOC: ICON_DOC, XLS: ICON_XLS, PPT: ICON_PPT })[ext] || ICON_FILE;
    }
    function lsGet(k, d) { try { return JSON.parse(localStorage.getItem(k)) || d; } catch (e) { return d; } }
    function lsSet(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

    var grid = document.getElementById('dkGrid');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.dk-card'));
    var noResult = document.getElementById('dkNoResult');
    var visibleCount = document.getElementById('dkVisible');

    /* ===== REVEAL ON SCROLL ===== */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.dk-rev').forEach(function (el) { io.observe(el); });

    /* ===== 3D TILT ===== */
    if (window.matchMedia('(pointer: fine)').matches) {
        cards.forEach(function (el) {
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                var px = ((e.clientX - r.left) / r.width - .5) * 7;
                var py = ((e.clientY - r.top) / r.height - .5) * -7;
                el.style.transform = 'translateY(-8px) rotateX(' + py + 'deg) rotateY(' + px + 'deg)';
                el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
            el.addEventListener('mouseleave', function () { el.style.transform = ''; });
        });
    }

    /* ===== COUNTER HERO ===== */
    document.querySelectorAll('.dk-hs-n[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1300, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(tick); else el.textContent = target.toLocaleString('id-ID');
        }
        requestAnimationFrame(tick);
    });

    /* ===== FAVORITES ===== */
    var favs = lsGet('dkFavs', []);
    function renderFavs() {
        document.querySelectorAll('.dk-fav').forEach(function (btn) {
            var id = btn.getAttribute('data-id');
            var on = favs.indexOf(id) !== -1;
            btn.classList.toggle('on', on);
            btn.innerHTML = on ? ICON_FAV_ON : ICON_FAV;
        });
    }
    function toggleFav(id) {
        var i = favs.indexOf(id);
        if (i === -1) favs.push(id); else favs.splice(i, 1);
        lsSet('dkFavs', favs);
        renderFavs();
        applyFilters();
    }
    document.querySelectorAll('.dk-fav').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            toggleFav(this.getAttribute('data-id'));
        });
    });
    renderFavs();

    /* ===== FILTERS / SORT / VIEW ===== */
    var activeType = 'all';
    var favOnly = false;
    var liveQuery = '';

    function applyFilters() {
        var shown = 0;
        cards.forEach(function (c) {
            var okType = (activeType === 'all') || (c.getAttribute('data-ext') === activeType);
            var okFav = !favOnly || (favs.indexOf(c.getAttribute('data-id')) !== -1);
            var okQuery = !liveQuery || (c.getAttribute('data-title') || '').indexOf(liveQuery) !== -1;
            var show = okType && okFav && okQuery;
            c.classList.toggle('hidden', !show);
            if (show) shown++;
        });
        if (visibleCount) visibleCount.textContent = shown;
        if (noResult) noResult.style.display = (shown === 0 && cards.length > 0) ? 'block' : 'none';
    }

    document.querySelectorAll('.dk-type').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.dk-type').forEach(function (b) { b.classList.remove('on'); });
            this.classList.add('on');
            activeType = this.getAttribute('data-type');
            applyFilters();
        });
    });

    var favBtn = document.querySelector('.dk-favfilter');
    if (favBtn) {
        favBtn.addEventListener('click', function () {
            favOnly = !favOnly;
            this.classList.toggle('on', favOnly);
            applyFilters();
        });
    }

    document.querySelectorAll('.dk-view').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.dk-view').forEach(function (b) { b.classList.remove('on'); });
            this.classList.add('on');
            if (grid) grid.classList.toggle('list-view', this.getAttribute('data-view') === 'list');
            lsSet('dkView', this.getAttribute('data-view'));
        });
    });
    var savedView = lsGet('dkView', 'grid');
    if (savedView === 'list' && grid) {
        grid.classList.add('list-view');
        document.querySelectorAll('.dk-view').forEach(function (b) { b.classList.toggle('on', b.getAttribute('data-view') === 'list'); });
    }

    var sortSel = document.getElementById('dkSort');
    if (sortSel) {
        sortSel.addEventListener('change', function () {
            var mode = this.value;
            var sorted = cards.slice();
            sorted.sort(function (a, b) {
                switch (mode) {
                    case 'old':   return (+a.getAttribute('data-date')) - (+b.getAttribute('data-date'));
                    case 'az':    return (a.getAttribute('data-title') || '').localeCompare(b.getAttribute('data-title') || '');
                    case 'za':    return (b.getAttribute('data-title') || '').localeCompare(a.getAttribute('data-title') || '');
                    case 'big':   return (+b.getAttribute('data-size')) - (+a.getAttribute('data-size'));
                    case 'small': return (+a.getAttribute('data-size')) - (+b.getAttribute('data-size'));
                    default:      return (+b.getAttribute('data-date')) - (+a.getAttribute('data-date'));
                }
            });
            if (noResult) grid.insertBefore(noResult, grid.firstChild);
            sorted.forEach(function (c) { grid.appendChild(c); });
        });
    }

    /* ===== LIVE SEARCH ===== */
    var searchInput = document.getElementById('dkSearchInput');
    var liveTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var v = this.value.toLowerCase().trim();
            clearTimeout(liveTimer);
            liveTimer = setTimeout(function () { liveQuery = v; applyFilters(); }, 220);
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && document.activeElement !== searchInput && !/input|textarea|select/i.test(document.activeElement.tagName)) {
            e.preventDefault();
            if (searchInput) { searchInput.focus(); searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }
        if (e.key === 'Escape') closeModal();
    });

    /* ===== MODAL DETAIL ===== */
    var modal = document.getElementById('dkModal');
    var currentCard = null;

    function openModal(card) {
        currentCard = card;
        var d = card.dataset;
        document.getElementById('dkModalHero').style.setProperty('--c1', card.style.getPropertyValue('--c1'));
        document.getElementById('dkModalHero').style.setProperty('--c2', card.style.getPropertyValue('--c2'));
        document.getElementById('dkModalIc').style.setProperty('--c1', card.style.getPropertyValue('--c1'));
        document.getElementById('dkModalIc').style.setProperty('--c2', card.style.getPropertyValue('--c2'));
        document.getElementById('dkModalIc').innerHTML = iconFor(d.ext);
        document.getElementById('dkModalCat').textContent = d.kategori || '—';
        document.getElementById('dkModalTitle').textContent = d.judul || '—';
        document.getElementById('dkModalDesc').textContent = d.deskripsi || '—';
        document.getElementById('dkModalKode').textContent = d.kode || '—';
        document.getElementById('dkModalExt').textContent = d.file || '—';
        document.getElementById('dkModalSize').textContent = d.ukuran || '—';
        document.getElementById('dkModalVer').textContent = 'Versi ' + (d.versi || 1);
        document.getElementById('dkModalDate').textContent = d.tanggal || '—';
        document.getElementById('dkModalDl').setAttribute('href', d.url || '#');
        syncModalFav();
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
    function syncModalFav() {
        var b = document.getElementById('dkModalFav');
        if (!currentCard) return;
        var on = favs.indexOf(currentCard.getAttribute('data-id')) !== -1;
        b.classList.toggle('on', on);
        b.innerHTML = (on ? ICON_FAV_ON : ICON_FAV) + ' ' + (on ? 'Tersimpan' : 'Favorit');
    }
    document.querySelectorAll('.dk-info-btn').forEach(function (b) {
        b.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); openModal(this.closest('.dk-card')); });
    });
    modal.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', closeModal); });
    var mFav = document.getElementById('dkModalFav');
    if (mFav) mFav.addEventListener('click', function () { if (currentCard) { toggleFav(currentCard.getAttribute('data-id')); syncModalFav(); } });
    var mCopy = document.getElementById('dkModalCopy');
    if (mCopy) mCopy.addEventListener('click', function () {
        if (!currentCard) return;
        var url = location.origin + currentCard.dataset.url;
        var self = this;
        navigator.clipboard.writeText(url).then(function () {
            self.innerHTML = ICON_CHECK + ' Tersalin';
            setTimeout(function () { self.innerHTML = ICON_LINK + ' Salin Tautan'; }, 2000);
        }).catch(function () { self.innerHTML = ICON_LINK + ' Gagal'; });
    });

    /* ===== RIWAYAT UNDUHAN ===== */
    var hist = lsGet('dkHistory', []);
    var histList = document.getElementById('dkHistList');
    var histClear = document.getElementById('dkHistClear');
    var histBox = document.getElementById('dkHistHead') ? document.getElementById('dkHistHead').parentNode : null;

    function renderHistory() {
        if (!histList) return;
        if (!hist.length) {
            histList.innerHTML = '<div class="dk-history-empty">' + ICON_HISTORY + ' Belum ada riwayat unduhan pada perangkat ini.</div>';
            if (histClear) histClear.style.display = 'none';
            return;
        }
        if (histClear) histClear.style.display = 'inline-flex';
        histList.innerHTML = hist.map(function (h) {
            var c1 = h.c1 || '#0F3D5C', c2 = h.c2 || '#1A5A82';
            return '<a class="dk-history-item" href="' + (h.url || '#') + '" style="--c1:' + c1 + ';--c2:' + c2 + '">' +
                   '<span class="h-ic">' + iconFor(h.ext) + '</span>' +
                   '<span class="h-tx"><strong>' + (h.judul || '—') + '</strong>' +
                   '<small>' + (h.ukuran || '—') + ' • ' + (h.when || '') + '</small></span>' +
                   ICON_CHECK + '</a>';
        }).join('');
    }
    function pushHistory(item) {
        hist = hist.filter(function (h) { return h.id !== item.id; });
        hist.unshift(item);
        if (hist.length > 8) hist = hist.slice(0, 8);
        lsSet('dkHistory', hist);
        renderHistory();
    }
    document.querySelectorAll('.dk-dl-link').forEach(function (a) {
        a.addEventListener('click', function () {
            var d = this.dataset;
            var now = new Date();
            pushHistory({
                id: d.id, judul: d.judul, ext: d.ext, ukuran: d.ukuran,
                c1: d.c1, c2: d.c2, url: this.getAttribute('href'),
                when: now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
            });
        });
    });
    if (histClear) histClear.addEventListener('click', function (e) { e.stopPropagation(); hist = []; lsSet('dkHistory', hist); renderHistory(); });
    if (document.getElementById('dkHistHead')) {
        document.getElementById('dkHistHead').addEventListener('click', function () {
            if (histBox) histBox.classList.toggle('open');
        });
    }
    renderHistory();
    applyFilters();

    /* ===== SCROLL PROGRESS / TOC / BACK TO TOP ===== */
    var prog = document.getElementById('dkProg');
    var toc = document.getElementById('dkToc');
    var backTop = document.getElementById('dkBackTop');
    window.addEventListener('scroll', function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? (window.scrollY / max) * 100 : 0;
        if (prog) prog.style.width = p + '%';
        if (toc) toc.classList.toggle('visible', window.scrollY > 420);
        if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    if (toc) {
        var tocLinks = toc.querySelectorAll('.dk-toc-link');
        var tocObs = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                var id = en.target.getAttribute('id');
                tocLinks.forEach(function (l) { l.classList.toggle('active', l.getAttribute('href') === '#' + id); });
            });
        }, { rootMargin: '-35% 0px -55% 0px' });
        document.querySelectorAll('#dkBeranda, #dkStatistik, #dkKatalog, #dkRiwayat, #dkBantuan').forEach(function (s) { tocObs.observe(s); });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer-publik.php'; ?>