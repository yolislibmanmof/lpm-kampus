<?php
require_once 'config/config.php';
Security::sendHeaders();

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'publik';

$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM dokumen_mutu WHERE id_dokumen = :id AND status = 'Aktif'");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

// Jika dokumen internal, wajib login
if ($doc['tipe_akses'] === 'internal' && !Auth::check()) {
    header('Location: /login.php');
    exit;
}

$filePath = PATH_UPLOAD . $doc['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File tidak tersedia di server.');
}

// Validasi ekstensi file untuk keamanan
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS)) {
    http_response_code(403);
    exit('Tipe file tidak valid.');
}

// Kirim file dengan header yang benar
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['file_path']) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;