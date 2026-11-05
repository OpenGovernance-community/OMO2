<?php
// Rendered by organization.php after loading the public aggregate and translations.
if (!isset($questions, $organization, $pageLang, $pageSourceLang, $surveyLang, $surveySourceLang)) {
    http_response_code(404);
    exit;
}
?>
<!doctype html>
<html lang="<?= $escape(translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr')) ?>" class="survey-organization-chart-page" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape(t('survey.organization.page_title', ['organization' => (string)$organization->get('name')], $pageLang, $pageSourceLang)) ?></title>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <link rel="stylesheet" href="<?= commonAssetUrl('/survey/survey.css') ?>">
</head>
<body>
    <main class="generic-page-shell survey-organization-chart">
        <header class="survey-organization-chart__header">
            <h1 class="generic-card-title generic-card-title--medium"><?= $escape(t('survey.organization.radar_title', [], $pageLang, $pageSourceLang)) ?></h1>
            <?php if (count($questions) === 10): ?>
                <div class="survey-radar-controls survey-public-radar-series">
                    <span class="survey-radar-toggle"><span aria-hidden="true"></span><?= $escape(t('survey.organization.current', [], $pageLang, $pageSourceLang)) ?></span>
                    <span class="survey-radar-toggle survey-radar-toggle--tomorrow"><span aria-hidden="true"></span><?= $escape(t('survey.organization.desired', [], $pageLang, $pageSourceLang)) ?></span>
                </div>
            <?php endif; ?>
        </header>
        <?php if (count($questions) === 10): ?>
            <div class="survey-radar" id="surveyPublicRadar" role="img" aria-label="<?= $escape(t('survey.public.radar_title', [], $surveyLang, $surveySourceLang)) ?>"></div>
            <p class="survey-organization-chart__participation"><?= $escape(t(
                $activeMembers > 0 ? 'survey.organization.participation_summary' : 'survey.organization.participation_count',
                ['count' => $participatedMembers, 'percent' => $participationPercent],
                $pageLang,
                $pageSourceLang
            )) ?></p>
        <?php else: ?>
            <p><?= $escape(t('survey.organization.empty', [], $pageLang, $pageSourceLang)) ?></p>
        <?php endif; ?>
    </main>
    <script>
        window.SURVEY_PUBLIC_RESULT = <?= json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="<?= commonAssetUrl('/survey/public.js') ?>"></script>
</body>
</html>
