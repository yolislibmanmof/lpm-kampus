<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1]); // hanya Admin LPM

$id = (int)($_GET['id'] ?? 0);
$db = Database::getInstance();
$stmt = $db->prepare("SELECT k.sertifikat FROM auditor_kompetensi k WHERE k.id_kompetensi = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['sertifikat'])) { http_response_code(404); exit('File tidak ditemukan.'); }

$uploadRoot = realpath(PATH_UPLOAD);
$filePath   = realpath(PATH_UPLOAD . $row['sertifikat']);
if ($uploadRoot === false || $filePath === false
    || !str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR)
    || !is_file($filePath)) {
    http_response_code(404); exit('File tidak tersedia di server.');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) { http_response_code(403); exit('Tipe file tidak valid.'); }

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
readfile($filePath);
exit;