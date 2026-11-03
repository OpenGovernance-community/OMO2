<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsRenderCatalogBackLink(string $url): void
{
    if ($url === '') return;
    static $lang = null;
    $sourceLang = [
        'back_to_catalog' => ['text' => 'Retour au catalogue', 'context' => 'Link above a tutorial title to return to its available catalog.'],
    ];
    $lang ??= loadTranslationBundle('lms_navigation', translationBundleResolveRequestLocale('lang'), $sourceLang);
    ?>
    <div class="lms-catalog-navigation">
        <a class="generic-action-button generic-action-button--link generic-action-button--compact" data-lms-catalog-back href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
            <span aria-hidden="true">&lt;</span>
            <?= htmlspecialchars(t('back_to_catalog', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
    <?php
}
