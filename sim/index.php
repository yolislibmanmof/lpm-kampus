<?php
require_once __DIR__ . '/../config/config.php';

if (!Auth::check()) {
    header('Location: /login.php');
    exit;
}

/* ===== Mesin Reminder H-90 (v4.1) =====
   Cek tenggat tiap SIM dibuka; aman karena semua kiriman ter-log di reminder_log */
require_once __DIR__ . '/../core/Reminder.php';
Reminder::run();

switch (Auth::role()) {
    case 1:
        require __DIR__ . '/admin/dashboard.php';
        break;
    case 2:
        require __DIR__ . '/pimpinan/dashboard.php';
        break;
    case 3:
        require __DIR__ . '/prodi/dashboard.php';
        break;
    case 4:
        require __DIR__ . '/auditor/dashboard.php';
        break;
    case 5:
        require __DIR__ . '/gpm/dashboard.php';
        break;
    default:
        header('Location: /login.php');
        exit;
}