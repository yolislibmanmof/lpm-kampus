<?php
$current = lang();
$available = ['id' => 'ID', 'en' => 'EN'];
?>
<div class="lang-switcher">
    <?php foreach ($available as $code => $label): ?>
        <a href="<?= currentLangUrl($code) ?>"
           class="lang-btn <?= $current === $code ? 'active' : '' ?>"
           title="<?= $code === 'id' ? 'Bahasa Indonesia' : 'English' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>