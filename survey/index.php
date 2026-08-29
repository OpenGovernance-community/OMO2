<?php

require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/assessment.php';

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

$privateToken = surveyNormalizePrivateToken($_GET['edit'] ?? '');
$invitationToken = surveyNormalizeInvitationToken($_GET['invitation'] ?? '');
$invitation = $invitationToken !== ''
    ? \dbObject\OrganizationalMaturityInvitation::findByToken($invitationToken)
    : null;
$invitationOrganization = $invitation ? $invitation->getOrganizationObject() : null;
if (!$invitationOrganization) {
    $invitationToken = '';
}
$persistedAssessment = $invitationToken !== ''
    ? \dbObject\OrganizationalMaturityAssessment::findByInvitation((int)$invitation->getId())
    : ($privateToken !== '' ? \dbObject\OrganizationalMaturityAssessment::findByPrivateToken($privateToken) : null);
$persistedAnswers = $persistedAssessment ? $persistedAssessment->getSurveyAnswers() : [];
$persistedLinks = $persistedAssessment && $invitationToken === ''
    ? surveyBuildAssessmentUrls((string)$persistedAssessment->get('public_token'), $privateToken)
    : null;
$invitationOrganizationName = $invitationOrganization ? trim((string)$invitationOrganization->get('name')) : '';
$currentUserId = (int)commonGetCurrentUserId();
$inviteOrganizations = [];
if ($currentUserId > 0) {
    $memberships = new \dbObject\ArrayUserOrganization();
    $memberships->loadActiveForUser($currentUserId);
    foreach ($memberships as $membership) {
        if (!$membership instanceof \dbObject\UserOrganization || !$membership->load((int)$membership->getId())) {
            continue;
        }
        if (!$membership->isOrganizationAdmin()) {
            continue;
        }
        $organization = new \dbObject\Organization();
        $organizationId = (int)$membership->get('IDorganization');
        if ($organizationId <= 0 || !$organization->load($organizationId)) {
            continue;
        }
        $holons = new \dbObject\ArrayHolon();
        $holons->loadVisibilityTargetsForOrganization($organizationId, [2, 1]);
        $holonOptions = $holons->buildVisibilityTargetOptions();
        $organizationMembers = new \dbObject\ArrayUserOrganization();
        $organizationMembers->loadActiveForOrganization($organizationId);
        $memberOptions = [];
        foreach ($organizationMembers as $organizationMembership) {
            if (!$organizationMembership instanceof \dbObject\UserOrganization || !$organizationMembership->load((int)$organizationMembership->getId())) {
                continue;
            }
            $memberUser = new \dbObject\User();
            if (!$memberUser->load((int)$organizationMembership->get('IDuser'))) {
                continue;
            }
            $memberName = trim((string)$memberUser->get('firstname') . ' ' . (string)$memberUser->get('lastname'));
            if ($memberName === '') {
                $memberName = trim((string)$organizationMembership->get('username')) ?: trim((string)$memberUser->get('username'));
            }
            if ($memberName === '') {
                $memberName = (string)$memberUser->get('email');
            }
            $memberOptions[] = [
                'id' => (int)$organizationMembership->get('IDuser'),
                'label' => $memberName,
                'email' => (string)$memberUser->get('email'),
            ];
        }
        $inviteOrganizations[] = [
            'id' => $organizationId,
            'name' => (string)$organization->get('name'),
            'holons' => array_merge($holonOptions['circle'], $holonOptions['role']),
            'members' => $memberOptions,
        ];
    }
}
$isInvitationSurvey = $invitationToken !== '';
$pageTitle = $isInvitationSurvey
    ? t('survey.invitation.page_title', ['organization' => $invitationOrganizationName], $lang, $sourceLang)
    : t('survey.page.title', [], $lang, $sourceLang);
$heroEyebrow = $isInvitationSurvey
    ? t('survey.invitation.hero_eyebrow', ['organization' => $invitationOrganizationName], $lang, $sourceLang)
    : t('survey.hero.eyebrow', [], $lang, $sourceLang);
$heroTitle = $isInvitationSurvey
    ? t('survey.invitation.hero_title', ['organization' => $invitationOrganizationName], $lang, $sourceLang)
    : t('survey.hero.title', [], $lang, $sourceLang);
$privacyFact = $isInvitationSurvey
    ? t('survey.invitation.fact', ['organization' => $invitationOrganizationName], $lang, $sourceLang)
    : t('survey.intro.private', [], $lang, $sourceLang);

$omoPaths = [];
foreach ($surveyOmoPathDefinitions as $questionNumber => $pathDefinition) {
    $questionNumber = (int)$questionNumber;
    $stages = [];
    foreach ($pathDefinition['stages'] as $stageIndex => $stageDefinition) {
        $stageNumber = $stageIndex + 1;
        $stagePrefix = 'survey.omo.path.' . $questionNumber . '.stage.' . $stageNumber;
        $stages[] = [
            'level' => $stageNumber,
            'title' => t($stagePrefix . '.title', [], $lang, $sourceLang),
            'description' => t($stagePrefix . '.description', [], $lang, $sourceLang),
        ];
    }
    $omoPaths[$questionNumber] = ['stages' => $stages];
}

$surveyConfig = [
    'questions' => $questions,
    'omoPaths' => $omoPaths,
    'persistence' => [
        'privateToken' => $persistedAssessment ? $privateToken : '',
        'invitationToken' => $invitationToken,
        'isInvitation' => $isInvitationSurvey,
        'answers' => $persistedAnswers,
        'links' => $persistedLinks,
        'saveEndpoint' => '/survey/api/save.php',
    ],
    'invite' => [
        'organizations' => $inviteOrganizations,
        'loginUrl' => '/survey/invite.php',
        'authenticated' => $currentUserId > 0,
        'openDialog' => $currentUserId > 0 && isset($_GET['invite']),
        'endpoint' => '/survey/api/invitations.php',
    ],
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
        'omoAction' => t('survey.omo.action', [], $lang, $sourceLang),
        'omoEyebrow' => t('survey.omo.eyebrow', [], $lang, $sourceLang),
        'omoTitle' => t('survey.omo.title', [], $lang, $sourceLang),
        'omoIntro' => t('survey.omo.intro', [], $lang, $sourceLang),
        'omoAxisLabel' => t('survey.omo.axis_label', [], $lang, $sourceLang),
        'omoScores' => t('survey.omo.scores', [], $lang, $sourceLang),
        'omoPath' => t('survey.omo.path', [], $lang, $sourceLang),
        'omoStage' => t('survey.omo.stage', [], $lang, $sourceLang),
        'omoRisk' => t('survey.omo.risk', [], $lang, $sourceLang),
        'omoClose' => t('survey.omo.close', [], $lang, $sourceLang),
        'saveLinks' => t('survey.save.links', [], $lang, $sourceLang),
        'saveResult' => t('survey.save.result', [], $lang, $sourceLang),
        'saveChanges' => t('survey.save.changes', [], $lang, $sourceLang),
        'saveSaved' => t('survey.save.saved', [], $lang, $sourceLang),
        'saveOmo' => t('survey.save.omo', [], $lang, $sourceLang),
        'saveEyebrow' => t('survey.save.eyebrow', [], $lang, $sourceLang),
        'saveTitle' => t('survey.save.title', [], $lang, $sourceLang),
        'savePublicLabel' => t('survey.save.public_label', [], $lang, $sourceLang),
        'savePrivateLabel' => t('survey.save.private_label', [], $lang, $sourceLang),
        'savePrivateHelp' => t('survey.save.private_help', [], $lang, $sourceLang),
        'saveCopy' => t('survey.save.copy', [], $lang, $sourceLang),
        'saveCopied' => t('survey.save.copied', [], $lang, $sourceLang),
        'saveSaving' => t('survey.save.saving', [], $lang, $sourceLang),
        'saveError' => t('survey.save.error', [], $lang, $sourceLang),
        'saveAssociate' => t('survey.save.associate', [], $lang, $sourceLang),
        'saveClose' => t('survey.save.close', [], $lang, $sourceLang),
        'restartDialog' => t('survey.dialog.restart', [], $lang, $sourceLang),
        'inviteAction' => t('survey.invite.action', [], $lang, $sourceLang),
        'inviteEyebrow' => t('survey.invite.eyebrow', [], $lang, $sourceLang),
        'inviteTitle' => t('survey.invite.title', [], $lang, $sourceLang),
        'inviteIntro' => t('survey.invite.intro', [], $lang, $sourceLang),
        'inviteOrganization' => t('survey.invite.organization', [], $lang, $sourceLang),
        'inviteHolons' => t('survey.invite.holons', [], $lang, $sourceLang),
        'inviteMembers' => t('survey.invite.members', [], $lang, $sourceLang),
        'inviteEmails' => t('survey.invite.emails', [], $lang, $sourceLang),
        'inviteEmailHelp' => t('survey.invite.email_help', [], $lang, $sourceLang),
        'inviteEmailPlaceholder' => t('survey.invite.email_placeholder', [], $lang, $sourceLang),
        'inviteSend' => t('survey.invite.send', [], $lang, $sourceLang),
        'inviteSending' => t('survey.invite.sending', [], $lang, $sourceLang),
        'inviteSent' => t('survey.invite.sent', [], $lang, $sourceLang),
        'inviteError' => t('survey.invite.error', [], $lang, $sourceLang),
        'inviteEmpty' => t('survey.invite.empty', [], $lang, $sourceLang),
        'inviteClose' => t('survey.invite.close', [], $lang, $sourceLang),
    ],
];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-color-style="ocean-blue">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#073a59">
    <title><?= $escape($pageTitle) ?></title>
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
                    <p class="survey-eyebrow"><?= $escape($heroEyebrow) ?></p>
                    <h1><?= $escape($heroTitle) ?></h1>
                    <p class="survey-welcome__intro"><?= $escape(t('survey.hero.intro', [], $lang, $sourceLang)) ?></p>
                    <div class="survey-facts" aria-label="<?= $escape(t('survey.page.title', [], $lang, $sourceLang)) ?>">
                        <span><?= $escape(t('survey.intro.time', [], $lang, $sourceLang)) ?></span>
                        <span><?= $escape(t('survey.intro.questions', [], $lang, $sourceLang)) ?></span>
                        <span><?= $escape($privacyFact) ?></span>
                    </div>
                    <div class="survey-welcome__actions">
                        <button class="generic-action-button generic-action-button--main survey-primary-action" type="button" id="surveyStart">
                            <span id="surveyStartLabel"><?= $escape(t('survey.action.start', [], $lang, $sourceLang)) ?></span>
                            <span class="survey-primary-action__arrow" aria-hidden="true">→</span>
                        </button>
                        <button class="generic-action-button generic-action-button--secondary" type="button" id="surveyRestart" hidden>
                            <?= $escape(t('survey.action.restart', [], $lang, $sourceLang)) ?>
                        </button>
                        <?php if (!$isInvitationSurvey): ?>
                            <button class="generic-action-button generic-action-button--secondary survey-invite-action" type="button" id="surveyInviteAction">
                                <?= $escape(t('survey.invite.action', [], $lang, $sourceLang)) ?>
                            </button>
                        <?php endif; ?>
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
            <div class="survey-results__actions survey-results__actions--primary">
                <button class="generic-action-button generic-action-button--main" type="button" id="surveySaveResult"></button>
                <button class="generic-action-button generic-action-button--secondary" type="button" id="surveySaveLinks"></button>
                <button class="generic-action-button generic-action-button--secondary" type="button" id="surveySaveOmo"></button>
                <button class="generic-action-button generic-action-button--secondary" type="button" id="surveyReview"></button>
            </div>
            <div class="survey-results__stats" id="surveyResultsStats"></div>
            <div class="survey-results__list" id="surveyResultsList"></div>
            <div class="survey-results__actions">
                <button class="generic-action-button generic-action-button--main" type="button" id="surveyOmoAction">
                    <?= $escape(t('survey.omo.action', [], $lang, $sourceLang)) ?>
                </button>
                <button class="generic-action-button generic-action-button--secondary" type="button" id="surveyResultsRestart"></button>
            </div>
        </section>
    </main>

    <dialog class="survey-omo-dialog" id="surveyOmoDialog" aria-labelledby="surveyOmoDialogTitle">
        <div class="survey-omo-dialog__surface">
            <header class="survey-omo-dialog__header">
                <div>
                    <p class="survey-eyebrow"><?= $escape(t('survey.omo.eyebrow', [], $lang, $sourceLang)) ?></p>
                    <h2 id="surveyOmoDialogTitle"><?= $escape(t('survey.omo.title', [], $lang, $sourceLang)) ?></h2>
                </div>
                <button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" id="surveyOmoClose" aria-label="<?= $escape(t('survey.omo.close', [], $lang, $sourceLang)) ?>" title="<?= $escape(t('survey.omo.close', [], $lang, $sourceLang)) ?>">×</button>
            </header>
            <div class="survey-omo-dialog__body" id="surveyOmoContent"></div>
        </div>
    </dialog>

    <dialog class="survey-omo-dialog survey-save-dialog" id="surveySaveDialog" aria-labelledby="surveySaveDialogTitle">
        <div class="survey-omo-dialog__surface">
            <header class="survey-omo-dialog__header">
                <div>
                    <p class="survey-eyebrow" id="surveySaveDialogEyebrow"></p>
                    <h2 id="surveySaveDialogTitle"></h2>
                </div>
                <button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" id="surveySaveClose" aria-label="<?= $escape(t('survey.save.close', [], $lang, $sourceLang)) ?>" title="<?= $escape(t('survey.save.close', [], $lang, $sourceLang)) ?>">×</button>
            </header>
            <div class="survey-omo-dialog__body" id="surveySaveContent"></div>
        </div>
    </dialog>

    <dialog class="survey-omo-dialog survey-invite-dialog" id="surveyInviteDialog" aria-labelledby="surveyInviteDialogTitle">
        <div class="survey-omo-dialog__surface">
            <header class="survey-omo-dialog__header">
                <div>
                    <p class="survey-eyebrow" id="surveyInviteDialogEyebrow"></p>
                    <h2 id="surveyInviteDialogTitle"></h2>
                </div>
                <button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" id="surveyInviteClose" aria-label="<?= $escape(t('survey.invite.close', [], $lang, $sourceLang)) ?>" title="<?= $escape(t('survey.invite.close', [], $lang, $sourceLang)) ?>">×</button>
            </header>
            <div class="survey-omo-dialog__body" id="surveyInviteContent"></div>
        </div>
    </dialog>

    <script>
        window.SURVEY_PROTOTYPE = <?= json_encode($surveyConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="/survey/survey.js"></script>
</body>
</html>
