<?php if (trim((string)($dashboardHtmlContent ?? '')) !== ''): ?>
    <div class="omo-dashboard-module__list omo-simple-html-render generic-description generic-description--primary generic-description--relaxed"><?= $dashboardHtmlContent ?></div>
<?php else: ?>
    <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.html.empty', [], $lang, $sourceLang)) ?></p>
<?php endif; ?>
