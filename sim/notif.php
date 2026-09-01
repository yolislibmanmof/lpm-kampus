<?php
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4]);
$db = Database::getInstance();

$action = $_GET['action'] ?? '';

if ($action === 'read_all') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id_user = ?")->execute([Auth::id()]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/sim/index.php'));
    exit;
}

if ($action === 'read') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id_notif = ? AND id_user = ?")
       ->execute([(int)$_GET['id'], Auth::id()]);
    $goto = $_GET['goto'] ?? '/sim/index.php';
    if (!str_starts_with($goto, '/')) $goto = '/sim/index.php';
    header('Location: ' . $goto);
    exit;
}

header('Location: /sim/index.php');
exit;