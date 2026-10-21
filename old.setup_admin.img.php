<?php
// ⚠️ JALANKAN SEKALI SAJA, LALU HAPUS FILE INI!
require_once __DIR__ . '/config/config.php';

$passwordBaru = 'GantiSegera2026!';
$hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

$db = Database::getInstance();
$stmt = $db->prepare("UPDATE users SET password_hash = :h WHERE email = :e");
$stmt->execute([':h' => $hash, ':e' => 'admin@kampus.ac.id']);

echo "✅ Password admin berhasil diatur.<br>";
echo "Email: <b>admin@kampus.ac.id</b><br>";
echo "Password: <b>$passwordBaru</b><br><br>";
echo "<span style='color:red'>⚠️ SEKARANG HAPUS file setup_admin.php ini!</span><br>";
echo "<a href='/login.php'>→ Menuju Halaman Login</a>";