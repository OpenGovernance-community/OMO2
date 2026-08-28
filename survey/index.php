<?php

require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once __DIR__ . '/content.php';

$lang = loadTranslationBundle('survey_prototype', 'fr', $sourceLang);
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$questions = [];
foreach ($surveyQuestionDefinitions as $questionDefinition) {
    $questionNumber = (int)$questionDefinition['number'];
    $questionPrefix = 'survey.question.' . $questionNumber;
    $options = [];

    foreach ($questionDefinition['options'] as $optionIndex => $optionDefinition) {
        $optionNumber = $optionIndex + 1;
        $optionPrefix = $questionPrefix . '.option.' . $optionNumber;
        $options[] = [
            'value' => $optionNumber,
            'title' => t($optionPrefix . '.title', [], $lang, $sourceLang),
            'description' => t($optionPrefix . '.description', [], $lang, $sourceLang),
        ];
    }

    $questions[] = [
        'number' => $questionNumber,
        'title' => t($questionPrefix . '.title', [], $lang, $sourceLang),
        'principle' => t($questionPrefix . '.principle', [], $lang, $sourceLang),
        'options' => $options,
    ];
}

$surveyConfig = [
    'questions' => $questions,
    'labels' => [
        'start' => t('survey.action.start', [], $lang, $sourceLang),
        'resume' => t('survey.action.resume', [], $lang, $sourceLang),
        'restart' => t('survey.action.restart', [], $lang, $sourceLang),
        'back' => t('survey.action.back', [], $lang, $sourceLang),
        'next' => t('survey.action.next', [], $lang, $sourceLang),
        'results' => t('survey.action.results', [], $lang, $sourceLang),
        'review' => t('survey.action.review', [], $lang, $sourceLang),
        'progressPrinciple' => t('survey.progress.principle', [], $lang, $sourceLang),
        'progressComplete' => t('survey.progress.complete', [], $lang, $sourceLang),
        'principleLabel' => t('survey.principle.label', [], $lang, $sourceLang),
        'scalePhaseLabel' => t('survey.phase.scale.label', [], $lang, $sourceLang),
        'scaleTitle' => t('survey.phase.scale.title', [], $lang, $sourceLang),
        'scaleHelp' => t('survey.phase.scale.help', [], $lang, $sourceLang),
        'choicePhaseLabel' => t('survey.phase.choice.label', [], $lang, $sourceLang),
        'choiceTitle' => t('survey.phase.choice.title', [], $lang, $sourceLang),
        'choiceHelp' => t('survey.phase.choice.help', [], $lang, $sourceLang),
        'today' => t('survey.period.today', [], $lang, $sourceLang),
        'todayHelp' => t('survey.period.today.help', [], $lang, $sourceLang),
        'tomorrow' => t('survey.period.tomorrow', [], $lang, $sourceLang),
        'tomorrowHelp' => t('survey.period.tomorrow.help', [], $lang, $sourceLang),
        'periodDone' => t('survey.period.done', [], $lang, $sourceLang),
        'scale' => [
            1 => t('survey.scale.1', [], $lang, $sourceLang),
            2 => t('survey.scale.2', [], $lang, $sourceLang),
            3 => t('survey.scale.3', [], $lang, $sourceLang),
            4 => t('survey.scale.4', [], $lang, $sourceLang),
            5 => t('survey.scale.5', [], $lang, $sourceLang),
        ],
        'saveStatus' => t('survey.save.status', [], $lang, $sourceLang),
        'incomplete' => t('survey.error.incomplete', [], $lang, $sourceLang),
        'resultsEyebrow' => t('survey.results.eyebrow', [], $lang, $sourceLang),
        'resultsTitle' => t('survey.results.title', [], $lang, $sourceLang),
        'resultsIntro' => t('survey.results.intro', [], $lang, $sourceLang),
        'todayAverage' => t('survey.results.today_average', [], $lang, $sourceLang),
        'tomorrowAverage' => t('survey.results.tomorrow_average', [], $lang, $sourceLang),
        'affinityAverage' => t('survey.results.affinity_average', [], $lang, $sourceLang),
        'largestGap' => t('survey.results.largest_gap', [], $lang, $sourceLang),
        'gapValue' => t('survey.results.gap_value', [], $lang, $sourceLang),
        'gapValuePlural' => t('survey.results.gap_value_plural', [], $lang, $sourceLang),
        'sameValue' => t('survey.results.same_value', [], $lang, $sourceLang),
        'currentSituation' => t('survey.results.current_situation', [], $lang, $sourceLang),
        'desiredSituation' => t('survey.results.desired_situation', [], $lang, $sourceLang),
        'affinityValue' => t('survey.results.affinity_value', [], $lang, $sourceLang),
        'radarTitle' => t('survey.results.radar_title', [], $lang, $sourceLang),
        'radarHelp' => t('survey.results.radar_help', [], $lang, $sourceLang),
        'radarToday' => t('survey.results.radar_today', [], $lang, $sourceLang),
        'radarTomorrow' => t('survey.results.radar_tomorrow', [], $lang, $sourceLang),
        'radarAffinity' => t('survey.results.radar_affinity', [], $lang, $sourceLang),
        'radarRisk' => t('survey.results.radar_risk', [], $lang, $sourceLang),
        'restartDialog' => t('survey.dialog.restart', [], $lang, $sourceLang),
    ],
];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-color-style="ocean-blue">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#073a59">
    <title><?= $escape(t('survey.page.title', [], $lang, $sourceLang)) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/survey/survey.css">
</head>
<body>
    <main class="survey-page" id="surveyPage">
        <section class="survey-welcome" id="surveyWelcome">
            <div class="survey-welcome__visual" aria-hidden="true">
                <span class="survey-orbit survey-orbit--one"></span>
                <span class="survey-orbit survey-orbit--two"></span>
                <span class="survey-orbit survey-orbit--three"></span>
            </div>
            <div class="survey-welcome__content">
                <div class="survey-welcome__copy">
                    <p class="survey-eyebrow"><?= $escape(t('survey.hero.eyebrow', [], $lang, $sourceLang)) ?></p>
                    <h1><?= $escape(t('survey.hero.title', [], $lang, $sourceLang)) ?></h1>
                    <p class="survey-welcome__intro"><?= $escape(t('survey.hero.intro', [], $lang, $sourceLang)) ?></p>
                    <div class="survey-facts" aria-label="<?= $escape(t('survey.page.title', [], $lang, $sourceLang)) ?>">
                        <span><?= $escape(t('survey.intro.time', [], $lang, $sourceLang)) ?></span>
                        <span><?= $escape(t('survey.intro.questions', [], $lang, $sourceLang)) ?></span>
                        <span><?= $escape(t('survey.intro.private', [], $lang, $sourceLang)) ?></span>
                    </div>
                    <div class="survey-welcome__actions">
                        <button class="generic-action-button generic-action-button--main survey-primary-action" type="button" id="surveyStart">
                            <span id="surveyStartLabel"><?= $escape(t('survey.action.start', [], $lang, $sourceLang)) ?></span>
                            <span class="survey-primary-action__arrow" aria-hidden="true">→</span>
                        </button>
                        <button class="generic-action-button generic-action-button--secondary" type="button" id="surveyRestart" hidden>
                            <?= $escape(t('survey.action.restart', [], $lang, $sourceLang)) ?>
                        </button>
                    </div>
                </div>
                <aside class="generic-soft-panel survey-how">
                    <div class="survey-how__drop" aria-hidden="true"></div>
                    <h2 class="generic-card-title generic-card-title--section"><?= $escape(t('survey.intro.how_title', [], $lang, $sourceLang)) ?></h2>
                    <ol>
                        <li><?= $escape(t('survey.intro.how_scale', [], $lang, $sourceLang)) ?></li>
                        <li><?= $escape(t('survey.intro.how_choice', [], $lang, $sourceLang)) ?></li>
                    </ol>
                </aside>
            </div>
        </section>

        <section class="survey-workspace" id="surveyWorkspace" hidden>
            <header class="survey-progress">
                <div class="survey-progress__labels">
                    <span id="surveyProgressPrinciple"></span>
                    <span id="surveyProgressPercent"></span>
                </div>
                <div class="survey-progress__track" aria-hidden="true">
                    <span id="surveyProgressBar"></span>
                </div>
            </header>

            <article class="generic-section survey-question-card" id="surveyQuestionCard" aria-live="polite">
                <header class="survey-question-card__header">
                    <div class="survey-question-number" id="surveyQuestionNumber" aria-hidden="true"></div>
                    <div>
                        <p class="survey-eyebrow" id="surveyPhaseLabel"></p>
                        <h1 id="surveyQuestionTitle"></h1>
                    </div>
                </header>

                <div class="generic-soft-panel survey-principle">
                    <p class="generic-meta-label" id="surveyPrincipleLabel"></p>
                    <p id="surveyPrincipleText"></p>
                </div>

                <div class="survey-response-heading">
                    <h2 id="surveyResponseTitle"></h2>
                    <p id="surveyResponseHelp"></p>
                </div>

                <div id="surveyResponse"></div>

                <p class="survey-validation" id="surveyValidation" role="status" hidden></p>

                <footer class="survey-navigation">
                    <button class="generic-action-button generic-action-button--secondary" type="button" id="surveyBack"></button>
                    <button class="generic-action-button generic-action-button--main" type="button" id="surveyNext"></button>
                </footer>
                <p class="survey-save-note" id="surveySaveNote"></p>
            </article>
        </section>

        <section class="survey-results" id="surveyResults" hidden>
            <div class="generic-hero-panel accent survey-results__hero">
                <p class="survey-eyebrow" id="surveyResultsEyebrow"></p>
                <h1 id="surveyResultsTitle"></h1>
                <p id="surveyResultsIntro"></p>
            </div>
            <section class="generic-section survey-radar-card" aria-labelledby="surveyRadarTitle">
                <header class="survey-radar-card__header">
                    <div>
                        <h2 id="surveyRadarTitle"><?= $escape(t('survey.results.radar_title', [], $lang, $sourceLang)) ?></h2>
                        <p><?= $escape(t('survey.results.radar_help', [], $lang, $sourceLang)) ?></p>
                    </div>
                    <div class="survey-radar-controls" role="group" aria-label="<?= $escape(t('survey.results.radar_help', [], $lang, $sourceLang)) ?>">
                        <label class="survey-radar-toggle survey-radar-toggle--today">
                            <input type="checkbox" data-radar-series="today" checked>
                            <span aria-hidden="true"></span>
                            <?= $escape(t('survey.results.radar_today', [], $lang, $sourceLang)) ?>
                        </label>
                        <label class="survey-radar-toggle survey-radar-toggle--tomorrow">
                            <input type="checkbox" data-radar-series="tomorrow" checked>
                            <span aria-hidden="true"></span>
                            <?= $escape(t('survey.results.radar_tomorrow', [], $lang, $sourceLang)) ?>
                        </label>
                        <label class="survey-radar-toggle survey-radar-toggle--affinity">
                            <input type="checkbox" data-radar-series="affinity" checked>
                            <span aria-hidden="true"></span>
                            <?= $escape(t('survey.results.radar_affinity', [], $lang, $sourceLang)) ?>
                        </label>
                    </div>
                </header>
                <div class="survey-radar" id="surveyRadar" role="img" aria-label="<?= $escape(t('survey.results.radar_title', [], $lang, $sourceLang)) ?>"></div>
                <p class="survey-radar-risk"><span aria-hidden="true"></span><?= $escape(t('survey.results.radar_risk', [], $lang, $sourceLang)) ?></p>
            </section>
            <div class="survey-results__stats" id="surveyResultsStats"></div>
            <div class="survey-results__list" id="surveyResultsList"></div>
            <div class="survey-results__actions">
                <button class="generic-action-button generic-action-button--secondary" type="button" id="surveyReview"></button>
                <button class="generic-action-button generic-action-button--main" type="button" id="surveyResultsRestart"></button>
            </div>
        </section>
    </main>

    <script>
        window.SURVEY_PROTOTYPE = <?= json_encode($surveyConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="/survey/survey.js"></script>
</body>
</html>
