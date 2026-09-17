<?php
if (!isset($dashboardVideoEmbedData) || !is_array($dashboardVideoEmbedData)) {
    return;
}

$dashboardVideoEmbedUrl = trim((string)($dashboardVideoEmbedData['embedUrl'] ?? ''));
?>
<?php if ($dashboardVideoEmbedUrl !== ''): ?>
    <div class="omo-dashboard-video">
        <iframe
            src="<?= omoApiEscape($dashboardVideoEmbedUrl) ?>"
            loading="lazy"
            allow="autoplay; fullscreen; picture-in-picture"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"
            title="<?= omoApiEscape(t('personal_space.video.player_title', [], $lang, $sourceLang)) ?>"
        ></iframe>
    </div>
<?php else: ?>
    <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.video.empty', [], $lang, $sourceLang)) ?></p>
<?php endif; ?>
