<?php

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/assessment.php';

$surveySourceLang = $sourceLang;
$surveyLang = loadTranslationBundle('survey_prototype', 'fr', $surveySourceLang);
$pageSourceLang = [
    'survey.organization.page_title' => ['text' => 'Maturité de {organization}', 'context' => 'Browser title for the public organization maturity report.'],
    'survey.organization.eyebrow' => ['text' => 'Maturité organisationnelle', 'context' => 'Eyebrow in the public organization maturity report.'],
    'survey.organization.title' => ['text' => 'Où en est {organization} ?', 'context' => 'Heading in the public organization maturity report.'],
    'survey.organization.intro' => ['text' => 'Cette lecture rassemble les perceptions des personnes ayant participé à l’évaluation.', 'context' => 'Introduction in the public organization maturity report.'],
    'survey.organization.radar_eyebrow' => ['text' => 'Lecture collective', 'context' => 'Eyebrow above the aggregate radar.'],
    'survey.organization.radar_title' => ['text' => 'Aujourd’hui et demain', 'context' => 'Title above the aggregate radar.'],
    'survey.organization.radar_help' => ['text' => 'Les deux zones montrent la moyenne de toutes les évaluations enregistrées pour cette organisation.', 'context' => 'Help above the aggregate radar.'],
    'survey.organization.dimensions_title' => ['text' => 'Les 10 dimensions évaluées', 'context' => 'Heading above the dimensions list in the public organization report.'],
    'survey.organization.agreement_title' => ['text' => 'Convergence des perceptions', 'context' => 'Heading above the agreement status legend.'],
    'survey.organization.agreement.aligned' => ['text' => 'Alignement', 'context' => 'Agreement status for tightly grouped responses.'],
    'survey.organization.agreement.dispersed' => ['text' => 'Dispersion', 'context' => 'Agreement status for spread responses.'],
    'survey.organization.agreement.polarized' => ['text' => 'Polarisation', 'context' => 'Agreement status for two opposing response poles.'],
    'survey.organization.agreement.insufficient' => ['text' => 'Données insuffisantes', 'context' => 'Agreement status when fewer than three responses are available.'],
    'survey.organization.agreement.description.aligned' => ['text' => 'Les réponses sont resserrées autour d’une position commune.', 'context' => 'Explanation of aligned responses.'],
    'survey.organization.agreement.description.dispersed' => ['text' => 'Les réponses sont étalées, sans deux camps nettement séparés.', 'context' => 'Explanation of dispersed responses.'],
    'survey.organization.agreement.description.polarized' => ['text' => 'Deux pôles de réponses opposés sont représentés.', 'context' => 'Explanation of polarized responses.'],
    'survey.organization.agreement.description.insufficient' => ['text' => 'Il faut au moins trois réponses pour proposer cette lecture.', 'context' => 'Explanation of insufficient data for agreement status.'],
    'survey.organization.agreement.stats' => ['text' => 'Échantillon : {count} · écart type : {stddev}', 'context' => 'Statistics shown in an agreement status tooltip.'],
    'survey.organization.groups.eyebrow' => ['text' => 'Cartographie des perceptions', 'context' => 'Eyebrow above respondent profile groups.'],
    'survey.organization.groups.title' => ['text' => 'Les profils qui se dessinent', 'context' => 'Title above respondent profile groups.'],
    'survey.organization.groups.intro' => ['text' => 'Cette lecture exploratoire rapproche les personnes dont les 30 réponses se ressemblent. Elle décrit des tendances, pas des catégories figées.', 'context' => 'Method introduction above respondent profile groups.'],
    'survey.organization.groups.quality' => ['text' => '{count} profils complets · {groups} groupes · séparation {score}', 'context' => 'Technical summary for respondent profile clustering.'],
    'survey.organization.groups.group_title' => ['text' => 'Groupe {letter}', 'context' => 'Respondent profile group title.'],
    'survey.organization.groups.group_size' => ['text' => '{count} profils · {share} %', 'context' => 'Respondent profile group size.'],
    'survey.organization.groups.unites' => ['text' => 'Ce qui rapproche ce groupe', 'context' => 'Heading above features characterizing a profile group.'],
    'survey.organization.groups.no_feature' => ['text' => 'Des réponses proches dans leur ensemble, sans dimension isolée nettement distinctive.', 'context' => 'Fallback description for a group without a distinctive feature.'],
    'survey.organization.groups.insufficient' => ['text' => 'Le regroupement devient pertinent à partir de 4 profils complets.', 'context' => 'Profile grouping empty state for too few respondents.'],
    'survey.organization.groups.no_structure' => ['text' => 'Les réponses ne dessinent pas encore de groupes suffisamment distincts. C’est plutôt le signe d’un continuum de perceptions.', 'context' => 'Profile grouping state when clustering is not robust enough.'],
    'survey.organization.groups.feature.today.high' => ['text' => 'une perception plus avancée de {theme} aujourd’hui ({score})', 'context' => 'Positive current feature characterizing a respondent group, with its group average.'],
    'survey.organization.groups.feature.today.low' => ['text' => 'une perception plus réservée de {theme} aujourd’hui ({score})', 'context' => 'Negative current feature characterizing a respondent group, with its group average.'],
    'survey.organization.groups.feature.tomorrow.high' => ['text' => 'une ambition plus forte pour {theme} ({score})', 'context' => 'Positive desired feature characterizing a respondent group, with its group average.'],
    'survey.organization.groups.feature.tomorrow.low' => ['text' => 'une ambition plus mesurée pour {theme} ({score})', 'context' => 'Negative desired feature characterizing a respondent group, with its group average.'],
    'survey.organization.groups.feature.affinity.high' => ['text' => 'une adhésion plus forte à {theme} ({score})', 'context' => 'Positive affinity feature characterizing a respondent group, with its group average.'],
    'survey.organization.groups.feature.affinity.low' => ['text' => 'une adhésion plus nuancée à {theme} ({score})', 'context' => 'Negative affinity feature characterizing a respondent group, with its group average.'],
    'survey.organization.common.eyebrow' => ['text' => 'Terrain commun', 'context' => 'Eyebrow above the organization common ground list.'],
    'survey.organization.common.title' => ['text' => 'Ce sur quoi les participants s’accordent le plus', 'context' => 'Title above the strongest collective consensus items.'],
    'survey.organization.common.intro' => ['text' => 'Deux lectures séparées pour faire apparaître aussi bien les perceptions partagées du présent que les destinations communes.', 'context' => 'Introduction explaining the two organization common ground rankings.'],
    'survey.organization.common.today_title' => ['text' => 'Les 4 constats les plus partagés', 'context' => 'Title above the current situation common ground ranking.'],
    'survey.organization.common.tomorrow_title' => ['text' => 'Les 4 destinations les plus partagées', 'context' => 'Title above the desired situation common ground ranking.'],
    'survey.organization.common.today' => ['text' => 'Constat aujourd’hui', 'context' => 'Tag for a current situation common ground item.'],
    'survey.organization.common.tomorrow' => ['text' => 'Projection demain', 'context' => 'Tag for a desired situation common ground item.'],
    'survey.organization.common.level' => ['text' => 'Niveau moyen {value}/5', 'context' => 'Average situation level in a common ground item.'],
    'survey.organization.common.alignment' => ['text' => 'Alignement {value}/100', 'context' => 'Alignment index in a common ground item.'],
    'survey.organization.common.alignment_help' => ['text' => '100 signifie que tout le monde a choisi la même valeur. 0 correspond à une dispersion uniforme entre les cinq niveaux, ou à une dispersion encore plus forte. Échantillon : {count} · écart type : {stddev}.', 'context' => 'Explanation and statistics of the common ground alignment index.'],
    'survey.organization.common.empty' => ['text' => 'Il faut au moins trois réponses pour faire émerger un terrain commun.', 'context' => 'Empty state when no common ground item is available.'],
    'survey.organization.summary_eyebrow' => ['text' => 'En synthèse', 'context' => 'Eyebrow above the aggregate survey summary.'],
    'survey.organization.summary' => ['text' => 'Les aspirations les plus marquées concernent {themes}.', 'context' => 'Dynamic aggregate summary below the radar.'],
    'survey.organization.participation' => ['text' => 'Participation des membres', 'context' => 'Participation statistic label.'],
    'survey.organization.participants' => ['text' => 'Membres participants', 'context' => 'Participant count statistic label.'],
    'survey.organization.evaluations' => ['text' => 'Évaluations enregistrées', 'context' => 'Evaluation count statistic label.'],
    'survey.organization.current' => ['text' => 'Aujourd’hui', 'context' => 'Current situation label in organization report cards.'],
    'survey.organization.desired' => ['text' => 'Demain', 'context' => 'Desired situation label in organization report cards.'],
    'survey.organization.affinity' => ['text' => 'Adhésion au principe : {value}/5', 'context' => 'Average affinity in organization report cards.'],
    'survey.organization.empty' => ['text' => 'Aucune évaluation complète n’est encore disponible pour cette organisation.', 'context' => 'Empty state for an organization with no survey evaluations.'],
    'survey.organization.not_found' => ['text' => 'Organisation introuvable.', 'context' => 'Error when the requested public organization does not exist.'],
    'survey.organization.theme.1' => ['text' => 'l’autorité assumée', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.2' => ['text' => 'l’intelligence collective', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.3' => ['text' => 'la raison d’être', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.4' => ['text' => 'la circulation du pouvoir', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.5' => ['text' => 'la confiance et la transparence', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.6' => ['text' => 'les structures apprenantes', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.7' => ['text' => 'la différenciation des rôles', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.8' => ['text' => 'l’usage des tensions', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.9' => ['text' => 'la présence consciente', 'context' => 'Short principle name used in the aggregate summary.'],
    'survey.organization.theme.10' => ['text' => 'le soin de l’écosystème', 'context' => 'Short principle name used in the aggregate summary.'],
];
$pageLang = loadTranslationBundle('survey_organization_report', 'fr', $pageSourceLang);
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$organizationId = (int)($_GET['id'] ?? 0);
$organization = new \dbObject\Organization();
if ($organizationId <= 0 || !$organization->load($organizationId)) {
    http_response_code(404);
    exit($escape(t('survey.organization.not_found', [], $pageLang, $pageSourceLang)));
}

$aggregate = \dbObject\OrganizationalMaturityAssessment::getOrganizationAggregate($organizationId);
$profileAnalysis = \dbObject\OrganizationalMaturityAssessment::getOrganizationProfileAnalysis($organizationId);
$questions = [];
foreach ($surveyQuestionDefinitions as $questionDefinition) {
    $number = (int)$questionDefinition['number'];
    $scores = $aggregate['principles'][$number] ?? null;
    if (!$scores || (int)$scores['responseCount'] <= 0) {
        $questions = [];
        break;
    }
    $today = max(1, min(5, (float)$scores['todayAverage']));
    $tomorrow = max(1, min(5, (float)$scores['tomorrowAverage']));
    $todayOption = $questionDefinition['options'][(int)round($today) - 1] ?? null;
    $tomorrowOption = $questionDefinition['options'][(int)round($tomorrow) - 1] ?? null;
    if (!$todayOption || !$tomorrowOption) {
        $questions = [];
        break;
    }
    $questions[] = [
        'number' => $number,
        'title' => t('survey.question.' . $number . '.title', [], $surveyLang, $surveySourceLang),
        'today' => round($today, 2),
        'tomorrow' => round($tomorrow, 2),
        'todayStddev' => round((float)$scores['todayStddev'], 2),
        'tomorrowStddev' => round((float)$scores['tomorrowStddev'], 2),
        'todayAgreement' => (string)($scores['todayAgreement'] ?? 'insufficient'),
        'tomorrowAgreement' => (string)($scores['tomorrowAgreement'] ?? 'insufficient'),
        'situation' => t('survey.question.' . $number . '.option.' . (int)round($today) . '.title', [], $surveyLang, $surveySourceLang),
        'situationDescription' => t('survey.question.' . $number . '.option.' . (int)round($today) . '.description', [], $surveyLang, $surveySourceLang),
        'desiredSituation' => t('survey.question.' . $number . '.option.' . (int)round($tomorrow) . '.title', [], $surveyLang, $surveySourceLang),
        'desiredSituationDescription' => t('survey.question.' . $number . '.option.' . (int)round($tomorrow) . '.description', [], $surveyLang, $surveySourceLang),
        'affinity' => round((float)$scores['affinityAverage'], 2),
        'affinityStddev' => round((float)$scores['affinityStddev'], 2),
        'affinityAgreement' => (string)($scores['affinityAgreement'] ?? 'insufficient'),
        'responseCount' => (int)$scores['responseCount'],
    ];
}
$questionsByNumber = [];
foreach ($questions as $question) {
    $questionsByNumber[(int)$question['number']] = $question;
}
$commonGroundItems = ['today' => [], 'tomorrow' => []];
foreach (['today', 'tomorrow'] as $period) {
    foreach (($aggregate['commonGround'][$period] ?? []) as $commonGround) {
        $principleNumber = (int)($commonGround['principle'] ?? 0);
        if (!isset($questionsByNumber[$principleNumber])) {
            continue;
        }
        $question = $questionsByNumber[$principleNumber];
        $commonGroundItems[$period][] = [
            'principle' => $principleNumber,
            'period' => $period,
            'title' => $question['title'],
            'situation' => $period === 'tomorrow' ? $question['desiredSituation'] : $question['situation'],
            'situationDescription' => $period === 'tomorrow' ? $question['desiredSituationDescription'] : $question['situationDescription'],
            'average' => (float)($commonGround['average'] ?? 0),
            'responseCount' => (int)($question['responseCount'] ?? 0),
            'standardDeviation' => (float)($commonGround['standardDeviation'] ?? 0),
            'alignmentScore' => max(0, min(100, (int)($commonGround['alignmentScore'] ?? 0))),
        ];
    }
}

$organizationName = trim((string)$organization->get('name')) ?: 'Organisation';
$organizationColor = trim((string)$organization->get('color'));
if (!preg_match('/^#[0-9a-f]{3,8}$/i', $organizationColor)) {
    $organizationColor = '#075b86';
}
$logoUrl = commonBuildAbsoluteAssetUrl((string)$organization->get('logo'));
$bannerUrl = commonBuildAbsoluteAssetUrl((string)$organization->get('banner'));
$activeMembers = (int)$aggregate['activeMembers'];
$participatedMembers = (int)$aggregate['participatedMembers'];
$participationPercent = $activeMembers > 0 ? (int)round(($participatedMembers / $activeMembers) * 100) : 0;
$summaryQuestions = $questions;
usort($summaryQuestions, static function (array $first, array $second): int {
    $firstGap = (float)$first['tomorrow'] - (float)$first['today'];
    $secondGap = (float)$second['tomorrow'] - (float)$second['today'];
    return $secondGap <=> $firstGap;
});
$summaryThemes = [];
foreach (array_slice($summaryQuestions, 0, 3) as $summaryQuestion) {
    $summaryThemes[] = t('survey.organization.theme.' . (int)$summaryQuestion['number'], [], $pageLang, $pageSourceLang);
}
$summaryText = count($summaryThemes) > 0
    ? t('survey.organization.summary', ['themes' => implode(', ', $summaryThemes)], $pageLang, $pageSourceLang)
    : '';
$dimensionIconMarkup = [
    1 => '<path d="M12 3 5.5 5.7v5.2c0 4.3 2.7 8.1 6.5 10.1 3.8-2 6.5-5.8 6.5-10.1V5.7L12 3Z"/>',
    2 => '<circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M6 19c0-3 2.2-5 5-5h2c2.8 0 5 2 5 5M12 5v12M7 11h10"/>',
    3 => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2.1 4.9-4.9 2.1 2.1-4.9 4.9-2.1Z"/>',
    4 => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
    5 => '<path d="m4 8 4-2 4 2 4-2 4 2-3 7-5 3-5-3-3-7Z"/><path d="m8 10 4 3 4-3"/>',
    6 => '<path d="m12 3 4 2.3v4.6L12 12l-4-2.1V5.3L12 3Z"/><path d="m7 12 4 2.3v4.6L7 21l-4-2.1v-4.6L7 12Zm10 0 4 2.3v4.6L17 21l-4-2.1v-4.6l4-2.3Z"/>',
    7 => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3.5 20c0-3.5 2.3-5.8 5.5-5.8s5.5 2.3 5.5 5.8M14 15c3.8-.8 6.5 1.2 6.5 4.5"/>',
    8 => '<path d="M2 13h4l2-7 4 13 3-9 2 5h5"/>',
    9 => '<path d="M20.8 5.9c-1.7-2-4.8-2.1-6.6-.2L12 8l-2.2-2.3C8 3.8 4.9 3.9 3.2 5.9c-1.6 1.9-1.5 4.7.3 6.5L12 21l8.5-8.6c1.8-1.8 1.9-4.6.3-6.5Z"/>',
    10 => '<path d="M20 4C12 4 6 7.7 6 14c0 3 2.2 5 5.3 5C17.7 19 20 12 20 4Z"/><path d="M4 21c2.5-5.5 6.5-9 12-12"/>',
];
$agreementStatuses = ['aligned', 'dispersed', 'polarized', 'insufficient'];
$agreementLabel = static function (string $status) use ($agreementStatuses, $pageLang, $pageSourceLang): string {
    if (!in_array($status, $agreementStatuses, true)) {
        $status = 'insufficient';
    }
    return t('survey.organization.agreement.' . $status, [], $pageLang, $pageSourceLang);
};
$agreementDescription = static function (string $status) use ($agreementStatuses, $pageLang, $pageSourceLang): string {
    if (!in_array($status, $agreementStatuses, true)) {
        $status = 'insufficient';
    }
    return t('survey.organization.agreement.description.' . $status, [], $pageLang, $pageSourceLang);
};
$groupFeatureText = static function (array $feature) use ($pageLang, $pageSourceLang): string {
    $dimension = in_array($feature['dimension'] ?? '', ['today', 'tomorrow', 'affinity'], true)
        ? (string)$feature['dimension']
        : 'today';
    $direction = (float)($feature['difference'] ?? 0) >= 0 ? 'high' : 'low';
    $theme = t('survey.organization.theme.' . (int)($feature['principle'] ?? 0), [], $pageLang, $pageSourceLang);
    return t(
        'survey.organization.groups.feature.' . $dimension . '.' . $direction,
        [
            'theme' => $theme,
            'score' => number_format((float)($feature['mean'] ?? 0), 1, ',', ''),
        ],
        $pageLang,
        $pageSourceLang
    );
};
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-color-style="ocean-blue">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= $escape($organizationColor) ?>">
    <title><?= $escape(t('survey.organization.page_title', ['organization' => $organizationName], $pageLang, $pageSourceLang)) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/survey/survey.css">
</head>
<body>
    <main class="survey-page organization-report" style="--param-organization-color: <?= $escape($organizationColor) ?>;<?= $bannerUrl !== '' ? ' --param-organization-banner: url(' . $escape($bannerUrl) . ');' : '' ?>">
        <section class="survey-results">
            <section class="generic-hero-panel organization-report__identity" aria-labelledby="surveyOrganizationRadarTitle">
                <header class="organization-report__identity-header">
                    <div class="organization-report__brand">
                        <?php if ($logoUrl !== ''): ?>
                            <img class="organization-report__logo" src="<?= $escape($logoUrl) ?>" alt="<?= $escape($organizationName) ?>">
                        <?php endif; ?>
                        <div>
                            <p class="survey-eyebrow"><?= $escape(t('survey.organization.eyebrow', [], $pageLang, $pageSourceLang)) ?></p>
                            <h1><?= $escape(t('survey.organization.title', ['organization' => $organizationName], $pageLang, $pageSourceLang)) ?></h1>
                            <p><?= $escape(t('survey.organization.intro', [], $pageLang, $pageSourceLang)) ?></p>
                        </div>
                    </div>
                    <div class="organization-report__participation">
                        <span class="organization-report__participation-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3.5 20c0-3.5 2.3-5.8 5.5-5.8s5.5 2.3 5.5 5.8M14 15c3.8-.8 6.5 1.2 6.5 4.5"/></svg>
                        </span>
                        <strong><?= $participationPercent ?> %</strong>
                        <span><?= $escape(t('survey.organization.participation', [], $pageLang, $pageSourceLang)) ?></span>
                    </div>
                </header>
                <?php if (count($questions) === 10): ?>
                    <div class="organization-report__dashboard">
                        <section class="organization-report__radar-card">
                            <header class="organization-report__radar-header">
                                <span class="organization-report__section-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M4 19V9m5 10V5m5 14v-7m5 7V3"/><path d="m3 14 5-5 5 3 7-7"/></svg>
                                </span>
                                <div>
                                    <p class="survey-eyebrow"><?= $escape(t('survey.organization.radar_eyebrow', [], $pageLang, $pageSourceLang)) ?></p>
                                    <h2 id="surveyOrganizationRadarTitle"><?= $escape(t('survey.organization.radar_title', [], $pageLang, $pageSourceLang)) ?></h2>
                                </div>
                            </header>
                            <div class="organization-report__series" aria-label="<?= $escape(t('survey.organization.radar_title', [], $pageLang, $pageSourceLang)) ?>">
                                <span class="organization-report__series-item organization-report__series-item--today"><i aria-hidden="true"></i><?= $escape(t('survey.organization.current', [], $pageLang, $pageSourceLang)) ?></span>
                                <span class="organization-report__series-item organization-report__series-item--tomorrow"><i aria-hidden="true"></i><?= $escape(t('survey.organization.desired', [], $pageLang, $pageSourceLang)) ?></span>
                            </div>
                            <div class="organization-report__radar-plot" id="surveyPublicRadar"></div>
                            <div class="organization-report__summary">
                                <span class="organization-report__summary-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M9 18h6M10 22h4"/><path d="M8.2 14.5A7 7 0 1 1 15.8 14.5c-.9.7-1.4 1.6-1.6 2.5h-4.4c-.2-.9-.7-1.8-1.6-2.5Z"/><path d="M12 2V0M4.9 4.9 3.5 3.5m15.6 1.4 1.4-1.4"/></svg>
                                </span>
                                <div>
                                    <p class="survey-eyebrow"><?= $escape(t('survey.organization.summary_eyebrow', [], $pageLang, $pageSourceLang)) ?></p>
                                    <p><?= $escape($summaryText) ?></p>
                                </div>
                            </div>
                        </section>
                        <aside class="organization-report__dimensions">
                            <h2><?= $escape(t('survey.organization.dimensions_title', [], $pageLang, $pageSourceLang)) ?></h2>
                            <ol>
                                <?php foreach ($questions as $question): ?>
                                    <li>
                                        <span class="organization-report__dimension-number"><?= (int)$question['number'] ?></span>
                                        <span class="organization-report__dimension-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><?= $dimensionIconMarkup[(int)$question['number']] ?? '' ?></svg>
                                        </span>
                                        <a href="#surveyPublicPrinciple<?= (int)$question['number'] ?>" data-survey-principle-link="<?= (int)$question['number'] ?>"><?= $escape($question['title']) ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </aside>
                    </div>
                <?php else: ?>
                    <div class="organization-report__empty"><p><?= $escape(t('survey.organization.empty', [], $pageLang, $pageSourceLang)) ?></p></div>
                <?php endif; ?>
            </section>
            <?php if (count($questions) === 10): ?>
                <section class="organization-report__groups generic-section" aria-labelledby="surveyOrganizationGroupsTitle">
                    <header class="organization-report__groups-header">
                        <div>
                            <p class="survey-eyebrow"><?= $escape(t('survey.organization.groups.eyebrow', [], $pageLang, $pageSourceLang)) ?></p>
                            <h2 id="surveyOrganizationGroupsTitle"><?= $escape(t('survey.organization.groups.title', [], $pageLang, $pageSourceLang)) ?></h2>
                            <p><?= $escape(t('survey.organization.groups.intro', [], $pageLang, $pageSourceLang)) ?></p>
                        </div>
                        <?php if (($profileAnalysis['status'] ?? '') === 'clustered'): ?>
                            <span class="organization-report__groups-quality"><?= $escape(t('survey.organization.groups.quality', [
                                'count' => (int)$profileAnalysis['participantCount'],
                                'groups' => count($profileAnalysis['groups']),
                                'score' => number_format((float)$profileAnalysis['silhouette'], 2, ',', ''),
                            ], $pageLang, $pageSourceLang)) ?></span>
                        <?php endif; ?>
                    </header>
                    <?php if (($profileAnalysis['status'] ?? '') === 'clustered'): ?>
                        <div class="organization-report__group-grid">
                            <?php foreach ($profileAnalysis['groups'] as $groupIndex => $group): ?>
                                <article class="organization-report__group-card">
                                    <header>
                                        <span class="organization-report__group-letter"><?= chr(65 + $groupIndex) ?></span>
                                        <div>
                                            <h3><?= $escape(t('survey.organization.groups.group_title', ['letter' => chr(65 + $groupIndex)], $pageLang, $pageSourceLang)) ?></h3>
                                            <p><?= $escape(t('survey.organization.groups.group_size', ['count' => (int)$group['size'], 'share' => (int)$group['share']], $pageLang, $pageSourceLang)) ?></p>
                                        </div>
                                    </header>
                                    <strong><?= $escape(t('survey.organization.groups.unites', [], $pageLang, $pageSourceLang)) ?></strong>
                                    <?php if (count($group['features']) > 0): ?>
                                        <ul>
                                            <?php foreach ($group['features'] as $feature): ?>
                                                <li><?= $escape($groupFeatureText($feature)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p><?= $escape(t('survey.organization.groups.no_feature', [], $pageLang, $pageSourceLang)) ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="organization-report__groups-empty"><?= $escape(t(
                            ($profileAnalysis['status'] ?? '') === 'insufficient'
                                ? 'survey.organization.groups.insufficient'
                                : 'survey.organization.groups.no_structure',
                            [],
                            $pageLang,
                            $pageSourceLang
                        )) ?></p>
                    <?php endif; ?>
                    <section class="organization-report__common-ground" aria-labelledby="surveyOrganizationCommonGroundTitle">
                        <header>
                            <p class="survey-eyebrow"><?= $escape(t('survey.organization.common.eyebrow', [], $pageLang, $pageSourceLang)) ?></p>
                            <h3 id="surveyOrganizationCommonGroundTitle"><?= $escape(t('survey.organization.common.title', [], $pageLang, $pageSourceLang)) ?></h3>
                            <p><?= $escape(t('survey.organization.common.intro', [], $pageLang, $pageSourceLang)) ?></p>
                        </header>
                        <div class="organization-report__common-columns">
                            <?php foreach (['today', 'tomorrow'] as $period): ?>
                                <section class="organization-report__common-period-list organization-report__common-period-list--<?= $period ?>">
                                    <h4><?= $escape(t('survey.organization.common.' . $period . '_title', [], $pageLang, $pageSourceLang)) ?></h4>
                                    <?php if (count($commonGroundItems[$period]) > 0): ?>
                                        <ol class="organization-report__common-list">
                                            <?php foreach ($commonGroundItems[$period] as $commonIndex => $commonGround): ?>
                                                <li>
                                                    <div class="organization-report__common-rank"><?= $commonIndex + 1 ?></div>
                                                    <div class="organization-report__common-copy">
                                                        <span class="organization-report__common-period organization-report__common-period--<?= $period ?>"><?= $escape(t('survey.organization.common.' . $period, [], $pageLang, $pageSourceLang)) ?></span>
                                                        <h5><?= $escape($commonGround['title']) ?></h5>
                                                        <?php $tooltipId = 'surveyCommonTooltip' . ucfirst($period) . ($commonIndex + 1); ?>
                                                        <p class="survey-situation-tooltip" tabindex="0" aria-describedby="<?= $tooltipId ?>">
                                                            <?= $escape($commonGround['situation']) ?>
                                                            <span id="<?= $tooltipId ?>" class="survey-situation-tooltip__content" role="tooltip"><?= $escape($commonGround['situationDescription']) ?></span>
                                                        </p>
                                                    </div>
                                                    <div class="organization-report__common-metrics">
                                                        <span><?= $escape(t('survey.organization.common.level', ['value' => number_format((float)$commonGround['average'], 1, ',', '')], $pageLang, $pageSourceLang)) ?></span>
                                                        <span class="organization-report__common-alignment" style="--param-alignment-score: <?= (int)$commonGround['alignmentScore'] ?>" title="<?= $escape(t('survey.organization.common.alignment_help', ['count' => (int)$commonGround['responseCount'], 'stddev' => number_format((float)$commonGround['standardDeviation'], 2, ',', '')], $pageLang, $pageSourceLang)) ?>">
                                                            <i aria-hidden="true"><i></i></i>
                                                            <?= $escape(t('survey.organization.common.alignment', ['value' => (int)$commonGround['alignmentScore']], $pageLang, $pageSourceLang)) ?>
                                                        </span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php else: ?>
                                        <p class="organization-report__common-empty"><?= $escape(t('survey.organization.common.empty', [], $pageLang, $pageSourceLang)) ?></p>
                                    <?php endif; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </section>
                <section class="survey-results__list" id="surveyPublicList"></section>
            <?php endif; ?>
        </section>
    </main>
    <script>
        window.SURVEY_PUBLIC_RESULT = <?= json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        window.SURVEY_PUBLIC_LABELS = <?= json_encode([
            'current' => t('survey.organization.current', [], $pageLang, $pageSourceLang),
            'desired' => t('survey.organization.desired', [], $pageLang, $pageSourceLang),
            'affinity' => t('survey.organization.affinity', [], $pageLang, $pageSourceLang),
            'agreementTitle' => t('survey.organization.agreement_title', [], $pageLang, $pageSourceLang),
            'agreementStats' => t('survey.organization.agreement.stats', [], $pageLang, $pageSourceLang),
            'agreementStatuses' => array_reduce($agreementStatuses, static function (array $labels, string $status) use ($agreementLabel, $agreementDescription): array {
                $labels[$status] = [
                    'label' => $agreementLabel($status),
                    'description' => $agreementDescription($status),
                ];
                return $labels;
            }, []),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        window.SURVEY_PUBLIC_OPTIONS = { separateDimensionList: true };
    </script>
    <script src="/survey/public.js"></script>
</body>
</html>
