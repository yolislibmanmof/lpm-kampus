<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4, 5]); // Semua role bisa akses

header('Content-Type: application/json');

$userId = Auth::id();
$count = NotificationManager::getUnreadCount($userId);
$notifs = NotificationManager::getRecent($userId, 8);

// Render HTML untuk dropdown agar konsisten dengan format PHP
ob_start();
if (empty($notifs)) {
    echo '<div class="nt-empty">🌙 Tidak ada notifikasi.<br>Semua tenang dan terkendali.</div>';
} else {
    foreach ($notifs as $n) {
        $timeAgo = time() - strtotime($n['created_at']);
        $timeStr = $timeAgo < 60 ? 'Baru saja' : ($timeAgo < 3600 ? floor($timeAgo / 60) . ' mnt lalu' : ($timeAgo < 86400 ? floor($timeAgo / 3600) . ' jam lalu' : floor($timeAgo / 86400) . ' hari lalu'));
        
        $unreadClass = $n['is_read'] ? '' : 'unread';
        $readUrl = "/sim/notif.php?action=read&id={$n['id_notif']}&goto=" . urlencode($n['url']);
        
        echo "<a class=\"nt-item {$unreadClass}\" href=\"{$readUrl}\" data-id=\"{$n['id_notif']}\">";
        echo "<span class=\"nt-ic\">{$n['icon']}</span>";
        echo "<span class=\"nt-txt\">";
        echo "<strong>" . Security::e($n['judul']) . "</strong>";
        echo "<small>" . Security::e(mb_strimwidth($n['isi'], 0, 60, '...')) . "</small>";
        echo "<em>{$timeStr}</em>";
        echo "</span></a>";
    }
}
$htmlList = ob_get_clean();

echo json_encode([
    'success' => true,
    'count' => $count,
    'html' => $htmlList
]);