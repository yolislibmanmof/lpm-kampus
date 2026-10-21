<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4, 5]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = NotificationManager::markAllAsRead(Auth::id());
    echo json_encode(['success' => $success, 'count' => 0]);
}