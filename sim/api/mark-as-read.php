<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4, 5]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notifId = (int)($_POST['id'] ?? 0);
    $success = NotificationManager::markAsRead($notifId, Auth::id());
    
    // Recalculate count
    $newCount = NotificationManager::getUnreadCount(Auth::id());
    
    echo json_encode(['success' => $success, 'count' => $newCount]);
}