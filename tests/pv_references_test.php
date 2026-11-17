<?php
declare(strict_types=1);
// Local integration fixtures use dbObjects and are rolled back.
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/document/pv-references.php';

use dbObject\{DbObject, Document, DocumentInvitation, DocumentPvPoint, Event, Organization, User, UserOrganization};

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $organization = mcpFixture(Organization::class, ['name' => 'PV references', 'shortname' => 'pvref-' . $nonce]);
    $viewer = mcpFixture(User::class, ['firstname' => 'David', 'lastname' => 'Dräyer', 'username' => 'global-dd', 'email' => 'dd-' . $nonce . '@example.invalid']);
    $other = mcpFixture(User::class, ['firstname' => 'David', 'lastname' => 'Morel', 'username' => 'davidm', 'email' => 'dm-' . $nonce . '@example.invalid']);
    mcpFixture(UserOrganization::class, ['IDuser' => $viewer->getId(), 'IDorganization' => $organization->getId(), 'username' => 'dd', 'active' => 1]);
    $document = mcpFixture(Document::class, ['title' => 'PV references', 'documenttype' => 'pv', 'pvstage' => 'review',
        'IDorganization' => $organization->getId(), 'IDuser' => $viewer->getId(), 'active' => 1]);
    mcpFixture(DocumentInvitation::class, ['resource_type' => 'document', 'resource_id' => $document->getId(),
        'invitation_type' => 'user', 'IDuser' => $other->getId(), 'status' => 'invited', 'active' => 1]);
    $identities = $document->getPvReferenceIdentities((int)$viewer->getId());
    $byKey = array_column($identities, null, 'key');
    $viewerKey = 'user:' . $viewer->getId();
    mcpCheck($byKey[$viewerKey]['username'] === 'dd', 'Use the organization username.');
    mcpCheck($byKey[$viewerKey]['fullName'] === 'David Dräyer', 'Keep UTF-8 names intact.');
    mcpCheck(isset($byKey['user:' . $other->getId()]), 'Use standalone PV invitees to distinguish names.');
    mcpCheck($document->getPvReferenceIdentities(0) === [], 'Anonymous readers have no personal reference configuration.');
    mcpCheck(!str_contains(json_encode($identities), '@example.invalid'), 'Reference aliases contain no email addresses.');
    ob_start();
    commonPvReferencesAssets($document, (int)$viewer->getId(), '#pv');
    $assets = ob_get_clean();
    mcpCheck(str_contains($assets, 'pv-references.js') && str_contains($assets, '"stage":"review"')
        && str_contains($assets, '"viewerKey":"' . $viewerKey . '"'), 'Readers receive their own reference configuration.');
    $_SESSION['currentUser'] = (int)$viewer->getId();
    $point = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $document->getId(), 'IDuser_author' => $viewer->getId(),
        'title' => 'Reference point', 'content' => '<p>Action pour @David Dräyer</p>', 'pointtype' => 'information', 'is_handled' => 1, 'active' => 1]);
    mcpCheck(str_contains($document->getReaderContent((int)$viewer->getId()), '<p>Action pour @David Dräyer</p>'),
        'The normal PV reader preserves reference text for client-side navigation.');
    $viewerId = (int)$viewer->getId();
    $otherId = (int)$other->getId();
    $candidates = [$viewerId, $otherId];
    foreach ([
        ['<p>@David <strong>Dräyer</strong> et @DAVIDM</p>', $candidates],
        ['<p>@david</p>', $candidates],
        ['<p>@David Morel</p>', [$otherId]],
        ['<p>@David&nbsp;Dräyer</p>', [$viewerId]],
        ['<p>@DD</p>', [$viewerId]],
        ['<p>not@david.ch @davido @@dd @dd_123 @dd.fr</p>', []],
        ["<p>@David\nMorel</p>", [$otherId]],
        ['<p>@David<br>Morel</p>', $candidates],
        ["<p>@David Dra\u{0308}yer</p>", [$viewerId]],
        // These unsupported hiding attributes are removed by the report's HTML sanitizer.
        ['<p hidden>@dd</p><p style="display:none">@David</p><p aria-hidden="true">@davidm</p>', $candidates],
        ['<a href="https://example.invalid/@dd">Lien</a>', []],
        ['<p>@<strong>dd</strong></p>', [$viewerId]],
    ] as [$html, $expected]) {
        $point->set('content', $html);
        mcpCheck(!empty($point->save()['status']), 'Update the reference fixture.');
        mcpCheck($document->getPvReferencedUserIds($candidates) === $expected, 'Server reference recipients for ' . $html);
    }
    $usernameUser = mcpFixture(User::class, ['firstname' => 'Jean', 'lastname' => 'Dupont', 'username' => 'david', 'email' => 'username-' . $nonce . '@example.invalid']);
    mcpFixture(DocumentInvitation::class, ['resource_type' => 'document', 'resource_id' => $document->getId(),
        'invitation_type' => 'user', 'IDuser' => $usernameUser->getId(), 'status' => 'invited', 'active' => 1]);
    $point->set('content', '<p>@david</p>');
    mcpCheck(!empty($point->save()['status']), 'Reference an exact username.');
    mcpCheck($document->getPvReferencedUserIds([...$candidates, (int)$usernameUser->getId()]) === [(int)$usernameUser->getId()],
        'An exact username takes precedence over shared firstnames.');
    $point->set('is_handled', 0);
    $point->set('content', '<p>@dd</p>');
    mcpCheck(!empty($point->save()['status']), 'Prepare an unfinished point.');
    mcpCheck($document->getPvReferencedUserIds($candidates) === [], 'Unfinished draft content is omitted from notifications.');
    $point->set('title', 'Point @dd');
    mcpCheck(!empty($point->save()['status']), 'Reference an unfinished point title.');
    mcpCheck($document->getPvReferencedUserIds($candidates) === [$viewerId], 'Public unfinished titles still count.');
    $point->set('title', 'Confidential fixture');
    $point->set('is_handled', 1);
    $point->set('is_confidential', 1);
    $point->set('content', '<p>@dd et @davidm</p>');
    mcpCheck(!empty($point->save()['status']), 'Prepare a confidential reference.');
    mcpCheck($document->getPvReferencedUserIds($candidates) === [$viewerId], 'References in inaccessible confidential content are excluded.');
    $point->set('is_confidential', 0);
    $point->set('content', '');
    mcpCheck(!empty($point->save()['status']), 'Clear the point content.');
    $document->set('description', 'Un PV pour @davidm');
    mcpCheck($document->getPvReferencedUserIds($candidates) === [$otherId], 'PV descriptions can reference recipients.');
    $document->set('description', '');
    $event = mcpFixture(Event::class, ['title' => 'PV reference meeting', 'IDorganization' => $organization->getId(),
        'IDuser' => $viewer->getId(), 'status' => 'confirmed',
        'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour'), 'active' => 1,
        'parameters' => ['meeting_participants' => ['entries' => [
            ['identityKey' => 'user:' . $other->getId(), 'userId' => $other->getId(), 'displayLabel' => 'David Morel'],
            ['identityKey' => 'email:external@example.invalid', 'userId' => 0, 'displayLabel' => 'David Externe'],
        ]]]]);
    $document->set('IDevent', $event->getId());
    mcpCheck(!empty($document->save()['status']), 'Associate the meeting with the PV.');
    $identities = $document->getPvReferenceIdentities((int)$viewer->getId());
    mcpCheck(count($identities) === 3, 'Use meeting participants, including named external invitees and the reader.');
    mcpCheck(in_array('David Externe', array_column($identities, 'fullName'), true), 'External full names also disambiguate firstnames.');
    echo "pv_references_test: OK (viewer, invitees, meeting snapshot, external names, scoped usernames, UTF-8)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
