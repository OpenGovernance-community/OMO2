<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, History, User, UserOrganization, OrganizationalMaturityAssessment, OrganizationalMaturityAssessmentResponse};

function homepageOrganizationCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

function homepageOrganizationFixture(string $class, array $values): DbObject
{
    $object = new $class();
    $object->loadFromArray($values);
    homepageOrganizationCheck(!empty($object->save()['status']), 'Fixture save failed: ' . $class);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $cutoff = new DateTimeImmutable('-30 days', new DateTimeZone('UTC'));
    $recent = $cutoff->modify('+1 day');
    $old = $cutoff->modify('-1 second');
    $organizations = [];
    foreach (['recent', 'boundary', 'old', 'unknown', 'connection', 'old_connection', 'inactive_connection', 'inactive_history', 'history_priority', 'no_logo'] as $case) {
        $organizations[$case] = homepageOrganizationFixture(Organization::class, [
            'name' => 'Homepage test ' . $case,
            'logo' => $case === 'no_logo' ? '' : '/img/omo-iceberg.jpg',
            'latlong' => [46.5, 6.6],
            'description' => $case === 'recent' ? str_repeat('Collectif ', 35) : ($case === 'connection' ? "Court\ntexte <test>" : ''),
            'url' => $case === 'recent' ? 'https://example.org/about?a=1&b=2' : '',
        ]);
    }
    foreach (['recent' => $recent, 'boundary' => $cutoff, 'old' => $old, 'inactive_history' => $recent, 'history_priority' => $old, 'no_logo' => $recent] as $case => $date) {
        homepageOrganizationFixture(History::class, [
            'IDorganization' => $organizations[$case]->getId(), 'datecreation' => $date,
            'action' => 'homepage_test', 'content' => 'Homepage activity test', 'active' => $case !== 'inactive_history',
        ]);
    }
    $user = homepageOrganizationFixture(User::class, [
        'firstname' => 'Homepage test', 'email' => 'homepage-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1,
    ]);
    foreach (['connection', 'old_connection', 'inactive_connection', 'history_priority'] as $case) {
        homepageOrganizationFixture(UserOrganization::class, [
            'IDorganization' => $organizations[$case]->getId(), 'IDuser' => $user->getId(),
            'dateconnexion' => $case === 'old_connection' ? $old : $recent, 'active' => $case !== 'inactive_connection',
        ]);
    }
    $activeIds = array_column(Organization::fetchPublicLogoRowsByLastConnection($cutoff), 'id');
    foreach ($organizations as $case => $organization) {
        homepageOrganizationCheck(
            in_array((int)$organization->getId(), $activeIds, true) === in_array($case, ['recent', 'boundary', 'connection'], true),
            'Incorrect activity filter for ' . $case
        );
    }
    homepageOrganizationCheck(
        array_search((int)$organizations['recent']->getId(), $activeIds, true) < array_search((int)$organizations['boundary']->getId(), $activeIds, true),
        'Most recent organizations must appear first'
    );
    $unfilteredIds = array_column(Organization::fetchPublicLogoRowsByLastConnection(), 'id');
    homepageOrganizationCheck(in_array((int)$organizations['unknown']->getId(), $unfilteredIds, true), 'Unfiltered callers must retain organizations without activity');
    homepageOrganizationCheck(in_array((int)$organizations['old']->getId(), $unfilteredIds, true), 'Unfiltered callers must retain old organizations');

    $draftMember = homepageOrganizationFixture(User::class, [
        'firstname' => 'Draft participant', 'email' => 'homepage-draft-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1,
    ]);
    foreach ([$user, $draftMember] as $member) {
        homepageOrganizationFixture(UserOrganization::class, [
            'IDorganization' => $organizations['recent']->getId(), 'IDuser' => $member->getId(), 'active' => 1,
        ]);
    }
    foreach ([
        ['recent', true, 1, 3], ['recent', true, 4, 5], ['recent', false, 5, 1],
        ['connection', true, 2, 5], ['boundary', false, 5, 5], [null, true, 5, 1],
    ] as [$case, $completed, $today, $tomorrow]) {
        $assessment = homepageOrganizationFixture(OrganizationalMaturityAssessment::class, [
            'IDorganization' => $case === null ? null : $organizations[$case]->getId(),
            'IDuser' => $case === 'recent' ? ($completed ? $user->getId() : $draftMember->getId()) : null,
            'public_token' => bin2hex(random_bytes(8)), 'private_token_hash' => hash('sha256', random_bytes(32)),
            'completed_at' => $completed ? new DateTimeImmutable('now', new DateTimeZone('UTC')) : null,
        ]);
        for ($principle = 1; $principle <= 10; $principle++) {
            homepageOrganizationFixture(OrganizationalMaturityAssessmentResponse::class, [
                'IDassessment' => $assessment->getId(), 'principle_number' => $principle, 'affinity_score' => 3,
                'today_score' => $case === 'recent' && $completed && $principle === 1 ? $today + 1 : $today,
                'tomorrow_score' => $tomorrow,
            ]);
        }
    }
    $summaries = OrganizationalMaturityAssessment::getOrganizationScoreSummaries(array_map(static fn ($organization) => (int)$organization->getId(), $organizations));
    $expectedSummary = ['todayAverage' => 2.6, 'tomorrowAverage' => 4.0];
    homepageOrganizationCheck($summaries[$organizations['recent']->getId()] === $expectedSummary, 'Scores must average all ten principles and completed assessments, excluding drafts and unassociated responses');
    homepageOrganizationCheck($summaries[$organizations['connection']->getId()] === ['todayAverage' => 2.0, 'tomorrowAverage' => 5.0], 'Organizations must have separate averages');
    homepageOrganizationCheck(!isset($summaries[$organizations['boundary']->getId()]) && !isset($summaries[$organizations['unknown']->getId()]), 'Draft-only and unevaluated organizations must have no score');
    $aggregate = OrganizationalMaturityAssessment::getOrganizationAggregate($organizations['recent']->getId());
    homepageOrganizationCheck($aggregate['participatedMembers'] === 1 && $aggregate['activeMembers'] === 2, 'Participation must count distinct members with completed assessments, without counting repeat evaluations or draft-only members');
    homepageOrganizationCheck(count($aggregate['principles']) === 10 && $aggregate['principles'][1]['todayAverage'] === 3.5 && $aggregate['principles'][2]['todayAverage'] === 2.5, 'Existing radar must retain its per-principle means');
    homepageOrganizationCheck(OrganizationalMaturityAssessment::getOrganizationScoreSummaries([]) === [], 'Empty batches must return no scores');

    $publicMapRows = array_column(Organization::fetchPublicMapRows(), null, 'id');
    $recentMapRow = $publicMapRows[$organizations['recent']->getId()];
    homepageOrganizationCheck($recentMapRow['maturity'] === $expectedSummary && $publicMapRows[$organizations['boundary']->getId()]['maturity'] === null, 'Map rows must expose only aggregate scores when available');
    homepageOrganizationCheck(mb_strlen($recentMapRow['description'], 'UTF-8') <= 240 && str_ends_with($recentMapRow['description'], '...'), 'Long map descriptions must be limited to 240 characters with an ellipsis');
    homepageOrganizationCheck($recentMapRow['url'] === 'https://example.org/about?a=1&b=2', 'Map data must retain the organization website');
    homepageOrganizationCheck($publicMapRows[$organizations['connection']->getId()]['description'] === 'Court texte <test>', 'Short descriptions must keep their text without an ellipsis and normalize whitespace');
    homepageOrganizationCheck($publicMapRows[$organizations['boundary']->getId()]['description'] === '' && $publicMapRows[$organizations['boundary']->getId()]['url'] === '', 'Missing description and website must stay empty');

    // Render the real homepage with the same transaction, without publishing fixtures.
    ob_start();
    require dirname(__DIR__) . '/views/omo2-home.php';
    $html = (string)ob_get_clean();
    homepageOrganizationCheck(str_contains($html, 'Homepage test recent'), 'Recent organization must render');
    homepageOrganizationCheck(!str_contains($html, 'Homepage test old'), 'Old organizations must not render');
    homepageOrganizationCheck(!str_contains($html, 'Homepage test unknown'), 'Organizations without activity must not render');
    if (commonLeafletMapsEnabled()) {
        homepageOrganizationCheck(str_contains($html, 'id="omo2OrganizationMapPanel" hidden'), 'Map must start collapsed');
        homepageOrganizationCheck(str_contains($html, 'aria-controls="omo2OrganizationMapPanel"'), 'Map button must identify its panel');
        $mapIds = array_column($omo2OrganizationMapRows, 'id');
        homepageOrganizationCheck(in_array((int)$organizations['recent']->getId(), $mapIds, true), 'Active geolocated organizations must appear on map');
        homepageOrganizationCheck(!in_array((int)$organizations['old']->getId(), $mapIds, true), 'Old organizations must not appear on map');
    }
    $_GET = ['id' => (int)$organizations['recent']->getId(), 'chart' => '1'];
    ob_start();
    require dirname(__DIR__) . '/survey/organization.php';
    $chartHtml = (string)ob_get_clean();
    homepageOrganizationCheck(str_contains($chartHtml, '1 personne (50 % des membres)'), 'Compact chart must show the distinct participant count and member percentage with the correct singular');
    homepageOrganizationCheck(strpos($chartHtml, 'class="survey-organization-chart__participation"') > strpos($chartHtml, 'id="surveyPublicRadar"'), 'Participation must be below the radar');
    homepageOrganizationCheck(!str_contains($chartHtml, 'class="survey-radar-risk"'), 'Public chart must not display the risk warning');
    homepageOrganizationCheck(str_contains($chartHtml, 'id="surveyPublicRadar"') && !str_contains($chartHtml, 'data-group-radar-index'), 'Compact view must reuse the public radar without loading the full organization report');
    preg_match('/window\.SURVEY_PUBLIC_RESULT = (.*);/', $chartHtml, $chartMatch);
    $chartQuestions = json_decode($chartMatch[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    homepageOrganizationCheck(count($chartQuestions) === 10 && $chartQuestions[0]['today'] === 3.5 && (float)$chartQuestions[0]['tomorrow'] === 4.0, 'Popup chart must display the existing organization aggregate');
    echo "homepage_organizations_test: OK (activity, map details, maturity means, organization isolation, drafts and compact radar)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
