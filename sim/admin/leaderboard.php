<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireRole([1, 2, 3, 5]);
require_once dirname(__DIR__, 2) . '/core/GamificationEngine.php';
require_once dirname(__DIR__, 2) . '/core/HealthScoreEngine.php';

$simTitle = 'Leaderboard & Gamifikasi';
$activeMenu = 'leaderboard';

$user = Auth::user();
$myProfile = GamificationEngine::getUserProfile($user['id']);
$globalLeaderboard = GamificationEngine::getGlobalLeaderboard(15);

// Prodi leaderboard
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$prodiLeaderboard = HealthScoreEngine::getLeaderboard($tahun, 10);

// Recalculate health scores jika admin klik
if ($user['role'] == 1 && isset($_GET['recalculate'])) {
    $count = HealthScoreEngine::calculateAll($tahun);
    $pesan = "✅ Skor health $count prodi dihitung ulang.";
    $prodiLeaderboard = HealthScoreEngine::getLeaderboard($tahun, 10);
}

require_once dirname(__DIR__, 2) . '/includes/header-sim.php';
?>

<?php if (!empty($pesan)): ?><div class="alert alert-success"><?= Security::e($pesan) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;">🏆 Leaderboard & Gamifikasi</h2>
        <p class="text-muted" style="margin:4px 0 0;font-size:13px;">Papan peringkat pengguna dan prodi terbaik.</p>
    </div>
    <?php if ($user['role'] == 1): ?>
        <a href="?recalculate=1&tahun=<?= $tahun ?>" class="btn btn-outline" style="padding:8px 16px;font-size:13px;" onclick="return confirm('Hitung ulang semua skor health prodi?');">🔄 Recalculate Health Scores</a>
    <?php endif; ?>
</div>

<!-- PROFIL GAMIFIKASI USER -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border:none;">
    <div style="display:grid;grid-template-columns:120px 1fr auto;gap:24px;align-items:center;">
        <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#C9A227,#E8C55A);color:#0F3D5C;display:grid;place-items:center;font-size:42px;font-weight:900;box-shadow:0 10px 30px rgba(201,162,39,.4);">
            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
        </div>
        <div>
            <small style="opacity:.8;letter-spacing:1px;text-transform:uppercase;font-size:11px;">Profil Anda</small>
            <h2 style="margin:4px 0 8px;font-size:24px;color:#fff;"><?= Security::e($user['nama']) ?></h2>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <div><strong style="font-size:24px;color:#E8C55A;"><?= $myProfile['total_poin'] ?></strong> <small style="opacity:.8;">poin</small></div>
                <div><strong style="font-size:24px;">#<?= $myProfile['rank'] ?></strong> <small style="opacity:.8;">peringkat</small></div>
                <div><strong style="font-size:24px;"><?= count($myProfile['badges']) ?></strong> <small style="opacity:.8;">badges</small></div>
            </div>
            <?php if ($myProfile['next_badge']): ?>
                <div style="margin-top:12px;background:rgba(255,255,255,.1);padding:10px 14px;border-radius:10px;">
                    <small style="opacity:.8;">Menuju badge <strong><?= Security::e($myProfile['next_badge']['nama']) ?></strong></small>
                    <div style="background:rgba(255,255,255,.2);height:6px;border-radius:3px;margin-top:6px;overflow:hidden;">
                        <div style="width:<?= $myProfile['progress_to_next'] ?>%;height:100%;background:#E8C55A;border-radius:3px;"></div>
                    </div>
                    <small style="opacity:.7;font-size:11px;"><?= $myProfile['total_poin'] ?>/<?= $myProfile['next_badge']['threshold'] ?> poin</small>
                </div>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <?php foreach (array_slice($myProfile['badges'], 0, 6) as $b): ?>
                <div style="display:inline-block;font-size:32px;margin:0 4px;" title="<?= Security::e($b['nama']) ?>: <?= Security::e($b['deskripsi']) ?>">
                    <?= Security::e($b['icon']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- LEADERBOARD USER -->
    <div class="card">
        <h3 style="margin-bottom:16px;">🥇 Top Pengguna</h3>
        <?php if (empty($globalLeaderboard)): ?>
            <p class="text-muted" style="text-align:center;padding:30px;">Belum ada data.</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($globalLeaderboard as $i => $u):
                $medal = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#' . ($i + 1)));
                $isMe = (int)$u['id_user'] === (int)$user['id'];
            ?>
                <div style="display:flex;align-items:center;gap:14px;padding:12px;background:<?= $isMe ? 'rgba(201,162,39,.1)' : 'var(--bg-light)' ?>;border-radius:12px;<?= $isMe ? 'border:2px solid var(--accent);' : '' ?>">
                    <div style="font-size:24px;width:40px;text-align:center;"><?= $medal ?></div>
                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;display:grid;place-items:center;font-weight:800;flex-shrink:0;">
                        <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <strong style="display:block;overflow:hidden;text-overflow:ellipsis;"><?= Security::e($u['nama_lengkap']) ?></strong>
                        <small class="text-muted"><?= $u['total_badges'] ?> badges • Role #<?= $u['id_role'] ?></small>
                    </div>
                    <div style="text-align:right;">
                        <strong style="font-size:18px;color:var(--accent);"><?= number_format($u['total_poin']) ?></strong>
                        <small class="text-muted" style="display:block;font-size:11px;">poin</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- LEADERBOARD PRODI -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;">🏅 Prodi Terbaik (<?= $tahun ?>)</h3>
            <form method="GET" style="display:inline;">
                <select name="tahun" class="form-control" style="width:auto;padding:6px 10px;font-size:12px;" onchange="this.form.submit()">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <?php if (empty($prodiLeaderboard)): ?>
            <p class="text-muted" style="text-align:center;padding:30px;">Belum ada data. Klik "Recalculate" jika Anda Admin.</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($prodiLeaderboard as $i => $p):
                $medal = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#' . ($i + 1)));
                $colorMap = ['Excellent' => 'var(--success)', 'Good' => '#10B981', 'Fair' => 'var(--warning)', 'Poor' => '#F59E0B', 'Critical' => 'var(--danger)'];
                $color = $colorMap[$p['peringkat']] ?? 'var(--text-muted)';
            ?>
                <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--bg-light);border-radius:12px;">
                    <div style="font-size:24px;width:40px;text-align:center;"><?= $medal ?></div>
                    <div style="flex:1;min-width:0;">
                        <strong style="display:block;overflow:hidden;text-overflow:ellipsis;"><?= Security::e($p['nama_prodi']) ?></strong>
                        <small class="text-muted"><?= Security::e($p['nama_fakultas'] ?? '—') ?></small>
                    </div>
                    <div style="text-align:right;">
                        <strong style="font-size:20px;color:<?= $color ?>;"><?= number_format($p['total_skor'], 1) ?></strong>
                        <span class="badge" style="background:<?= $color ?>20;color:<?= $color ?>;margin-left:6px;"><?= $p['peringkat'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- BADGES GRID -->
<div class="card" style="margin-top:24px;">
    <h3 style="margin-bottom:16px;">🎖️ Koleksi Badge Saya</h3>
    <?php if (empty($myProfile['badges'])): ?>
        <p class="text-muted" style="text-align:center;padding:20px;">Belum ada badge. Mulai kumpulkan poin!</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">
            <?php foreach ($myProfile['badges'] as $b): ?>
                <div style="padding:16px;background:var(--bg-light);border-radius:12px;text-align:center;transition:.3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                    <div style="font-size:42px;margin-bottom:6px;"><?= Security::e($b['icon']) ?></div>
                    <strong style="display:block;font-size:12px;margin-bottom:2px;"><?= Security::e($b['nama']) ?></strong>
                    <small class="text-muted" style="font-size:10px;"><?= Security::e(mb_strimwidth($b['deskripsi'], 0, 40, '...')) ?></small>
                    <div style="margin-top:6px;font-size:10px;color:var(--accent);"><?= date('d M Y', strtotime($b['unlocked_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer-sim.php'; ?>