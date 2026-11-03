<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsRenderCatalogBackLink(string $url, string $packUrl = ''): void
{
    if ($url === '' && $packUrl === '') return;
    static $lang = null;
    $sourceLang = [
        'back_to_catalog' => ['text' => 'Retour au catalogue', 'context' => 'Link above a tutorial title to return to its available catalog.'],
        'back_to_pack' => ['text' => 'Retour au pack', 'context' => 'Link above a tutorial title to return to the pack from which it was opened.'],
    ];
    $lang ??= loadTranslationBundle('lms_navigation', translationBundleResolveRequestLocale('lang'), $sourceLang);
    ?>
    <div class="lms-catalog-navigation generic-action-row generic-action-row--start">
        <?php foreach (['catalog' => $url, 'pack' => $packUrl] as $target => $targetUrl): ?>
            <?php if ($targetUrl === '') continue; ?>
            <a class="generic-action-button generic-action-button--link generic-action-button--compact" data-lms-<?= $target ?>-back href="<?= htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8') ?>">
                <span aria-hidden="true">&lt;</span>
                <?= htmlspecialchars(t('back_to_' . $target, [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}
