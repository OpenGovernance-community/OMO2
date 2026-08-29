<?php

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/assessment.php';

$lang = loadTranslationBundle('survey_prototype', 'fr', $sourceLang);
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$publicToken = surveyNormalizePublicToken($_GET['token'] ?? '');
$assessment = $publicToken !== ''
    ? \dbObject\OrganizationalMaturityAssessment::findByPublicToken($publicToken)
    : null;
$answers = $assessment ? $assessment->getSurveyAnswers() : [];

if (!$assessment || count($answers) !== 10) {
    http_response_code(404);
    exit($escape(t('survey.public.not_found', [], $lang, $sourceLang)));
}

$publicQuestions = [];
foreach ($surveyQuestionDefinitions as $questionIndex => $questionDefinition) {
    $questionNumber = (int)$questionDefinition['number'];
    $answer = $answers[$questionIndex];
    $todayScore = (int)$answer['situation']['today'];
    $tomorrowScore = (int)$answer['situation']['tomorrow'];
    $todayOptionDefinition = $questionDefinition['options'][$todayScore - 1] ?? null;
    $tomorrowOptionDefinition = $questionDefinition['options'][$tomorrowScore - 1] ?? null;
    if (!$todayOptionDefinition || !$tomorrowOptionDefinition) {
        http_response_code(404);
        exit($escape(t('survey.public.incomplete', [], $lang, $sourceLang)));
    }

    $questionPrefix = 'survey.question.' . $questionNumber;
    $optionPrefix = $questionPrefix . '.option.' . $todayScore;
    $publicQuestions[] = [
        'number' => $questionNumber,
        'title' => t($questionPrefix . '.title', [], $lang, $sourceLang),
        'today' => $todayScore,
        'tomorrow' => $tomorrowScore,
        'situation' => t($optionPrefix . '.title', [], $lang, $sourceLang),
        'desiredSituation' => t($questionPrefix . '.option.' . $tomorrowScore . '.title', [], $lang, $sourceLang),
    ];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-color-style="ocean-blue">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#073a59">
    <title><?= $escape(t('survey.public.page_title', [], $lang, $sourceLang)) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/survey/survey.css">
</head>
<body>
    <main class="survey-page">
        <section class="survey-results">
            <header class="generic-hero-panel survey-results__hero">
                <p class="survey-eyebrow"><?= $escape(t('survey.public.eyebrow', [], $lang, $sourceLang)) ?></p>
                <h1><?= $escape(t('survey.public.title', [], $lang, $sourceLang)) ?></h1>
                <p><?= $escape(t('survey.public.intro', [], $lang, $sourceLang)) ?></p>
            </header>
            <section class="generic-soft-panel survey-radar-card">
                <header class="survey-radar-card__header">
                    <div>
                        <p class="survey-eyebrow"><?= $escape(t('survey.public.comparison_label', [], $lang, $sourceLang)) ?></p>
                        <h2 class="generic-card-title generic-card-title--section"><?= $escape(t('survey.public.radar_title', [], $lang, $sourceLang)) ?></h2>
                        <p><?= $escape(t('survey.public.radar_help', [], $lang, $sourceLang)) ?></p>
                    </div>
                </header>
                <div class="survey-radar" id="surveyPublicRadar"></div>
                <div class="survey-radar-controls survey-public-radar-series" aria-label="<?= $escape(t('survey.public.radar_title', [], $lang, $sourceLang)) ?>">
                    <span class="survey-radar-toggle"><span aria-hidden="true"></span><?= $escape(t('survey.results.radar_today', [], $lang, $sourceLang)) ?></span>
                    <span class="survey-radar-toggle survey-radar-toggle--tomorrow"><span aria-hidden="true"></span><?= $escape(t('survey.results.radar_tomorrow', [], $lang, $sourceLang)) ?></span>
                </div>
            </section>
            <section class="survey-results__list" id="surveyPublicList"></section>
        </section>
    </main>
    <script>
        window.SURVEY_PUBLIC_RESULT = <?= json_encode($publicQuestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        window.SURVEY_PUBLIC_LABELS = <?= json_encode([
            'current' => t('survey.public.current_label', [], $lang, $sourceLang),
            'desired' => t('survey.public.desired_label', [], $lang, $sourceLang),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="/survey/public.js"></script>
</body>
</html>
