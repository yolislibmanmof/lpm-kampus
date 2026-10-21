<?php
require_once __DIR__ . '/config/config.php';

// 1. Wajib metode POST (mencegah serangan logout via link GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sim/index.php');
    exit;
}

// 2. Cek CSRF secara manual agar lebih toleran terhadap Session Timeout
$postToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['_csrf'] ?? '';

// Jika session masih aktif, pastikan token cocok.
// Jika session sudah mati (kosong), kita biarkan lolos agar user bisa logout dengan normal.
if (!empty($sessionToken) && !hash_equals($sessionToken, $postToken)) {
    // Token tidak cocok saat session masih aktif = potensi serangan CSRF
    // Tetap hancurkan session demi keamanan, lalu redirect dengan pesan error
    session_destroy();
    header('Location: /login.php?error=invalid_request');
    exit;
}

// 3. Proses Logout (Hancurkan Session & Cookie secara aman)
Auth::logout();

// 4. Redirect ke halaman login dengan parameter sukses
header('Location: /login.php?logged_out=1');
exit;