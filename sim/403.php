<?php
require_once __DIR__ . '/../config/config.php';
Security::sendHeaders();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak | SIM-Mutu</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .f-wrap { min-height: 100vh; display: grid; place-items: center; text-align: center; padding: 24px; }
        .f-shield { font-size: 90px; animation: fFloat 3s ease-in-out infinite; display: inline-block; }
        @keyframes fFloat { 0%,100% { transform: translateY(0) rotate(-4deg); } 50% { transform: translateY(-16px) rotate(4deg); } }
        .f-code { font-size: 90px; font-weight: 800; background: linear-gradient(135deg, var(--danger), var(--warning)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; line-height: 1.1; }
    </style>
</head>
<body>
<div class="f-wrap">
    <div>
        <span class="f-shield">🛡️</span>
        <div class="f-code">403</div>
        <h1 style="font-size:26px;color:var(--primary-dark);margin:8px 0 10px;">Akses Ditolak</h1>
        <p class="text-muted" style="max-width:420px;margin:0 auto 32px;">
            Anda tidak memiliki izin untuk mengakses halaman ini.
            Silakan hubungi Admin LPM jika Anda merasa ini sebuah kesalahan.
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="/sim/index.php" class="btn btn-primary">← Kembali ke Dashboard</a>
            <a href="/logout.php" class="btn btn-outline">Ganti Akun</a>
        </div>
    </div>
</div>
</body>
</html>