<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, History, User, UserOrganization};

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

    $publicMapRows = array_column(Organization::fetchPublicMapRows(), null, 'id');
    $recentMapRow = $publicMapRows[$organizations['recent']->getId()];
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
    echo "homepage_organizations_test: OK (activity cutoff, boundary, connection fallback, ordering, rendering and map data)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
