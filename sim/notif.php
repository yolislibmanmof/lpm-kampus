<?php
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireRole([1, 2, 3, 4, 5]);
$db = Database::getInstance();

/** Hanya path relatif same-origin. Tolak //evil.com, backslash, skema URL, karakter kontrol. */
function notifSafePath(?string $url): string
{
    $url = (string)$url;
    if ($url === '' || $url[0] !== '/') return '/sim/index.php';
    if (isset($url[1]) && ($url[1] === '/' || $url[1] === '\\')) return '/sim/index.php';
    if (strpos($url, '\\') !== false) return '/sim/index.php';
    if (preg_match('/[\x00-\x1F\x7F]/', $url)) return '/sim/index.php';
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) return '/sim/index.php';
    return $url;
}

$action = $_GET['action'] ?? '';

if ($action === 'read_all') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id_user = ?")->execute([Auth::id()]);
    $target = '/sim/index.php';
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $p = parse_url($ref);
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (isset($p['host'], $p['path']) && $p['host'] === $host && $p['path'] !== '' && $p['path'][0] === '/') {
            $target = notifSafePath($p['path'] . (isset($p['query']) ? '?' . $p['query'] : ''));
        }
    }
    header('Location: ' . $target);
    exit;
}

if ($action === 'read') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id_notif = ? AND id_user = ?")
       ->execute([(int)($_GET['id'] ?? 0), Auth::id()]);
    header('Location: ' . notifSafePath($_GET['goto'] ?? '/sim/index.php'));
    exit;
}

header('Location: /sim/index.php');
exit;