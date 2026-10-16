<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, User, UserOrganization, Organization, Application, OrganizationApplication, Holon,
    Document, DocumentPvPoint, Project, Rule, DeferredProposal, DecisionGovernanceAction};

function pvProposalCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function pvProposalFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    pvProposalCheck(!empty($result['status']), 'Fixture failed: ' . $class . ' ' . json_encode($result));
    return $object;
}
function pvProposalPayloads(string $html): array
{
    preg_match_all('/data-omo-change-details-payload="([^"]+)"/', $html, $matches);
    return array_map(static fn($payload) => json_decode(base64_decode(htmlspecialchars_decode($payload)), true, 512, JSON_THROW_ON_ERROR), $matches[1]);
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(5));
    $organization = pvProposalFixture(Organization::class, ['name' => 'PV proposals ' . $nonce, 'shortname' => 'pvp-' . $nonce]);
    $oid = (int)$organization->getId();
    $viewer = pvProposalFixture(User::class, ['firstname' => 'Reader', 'email' => 'pvp-reader-' . $nonce . '@example.invalid', 'active' => 1]);
    $owner = pvProposalFixture(User::class, ['firstname' => 'Secretary', 'email' => 'pvp-owner-' . $nonce . '@example.invalid', 'active' => 1]);
    $uid = (int)$viewer->getId(); $ownerId = (int)$owner->getId();
    foreach ([$uid, $ownerId] as $userId) pvProposalFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $userId, 'active' => 1]);
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $app = new Application();
    pvProposalCheck($app->load([['hash', 'structure']]), 'Structure app fixture is required.');
    pvProposalFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $root = pvProposalFixture(Holon::class, ['IDorganization' => $oid, 'IDtypeholon' => 2, 'name' => 'Report root', 'active' => 1, 'visible' => 1]);
    $role = pvProposalFixture(Holon::class, ['IDorganization' => $oid, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(),
        'IDtypeholon' => 1, 'name' => 'Report role', 'active' => 1, 'visible' => 1]);
    $hid = (int)$role->getId();
    $document = pvProposalFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $ownerId, 'IDusercreation' => $ownerId,
        'IDuser_pv_editor' => $ownerId, 'title' => 'Report proposals', 'documenttype' => 'pv', 'pvstage' => 'validated', 'active' => 1]);
    $point = pvProposalFixture(DocumentPvPoint::class, ['IDdocument' => $document->getId(), 'IDuser_author' => $ownerId,
        'title' => 'Recorded point', 'content' => '<p>Point report text</p>', 'pointtype' => 'decision', 'is_handled' => 1, 'position' => 1, 'active' => 1]);
    $unfinished = pvProposalFixture(DocumentPvPoint::class, ['IDdocument' => $document->getId(), 'IDuser_author' => $ownerId,
        'title' => 'Unfinished point', 'pointtype' => 'decision', 'position' => 2, 'active' => 1]);
    $secret = pvProposalFixture(DocumentPvPoint::class, ['IDdocument' => $document->getId(), 'IDuser_author' => $ownerId,
        'title' => 'Confidential point', 'pointtype' => 'decision', 'is_handled' => 1, 'is_confidential' => 1, 'position' => 3, 'active' => 1]);
    $project = pvProposalFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Roadmap', 'active' => 1]);
    $privateProject = pvProposalFixture(Project::class, ['IDorganization' => $oid, 'IDusercreation' => $ownerId,
        'title' => 'Private project', 'visibility_type' => 'self', 'active' => 1]);
    $deletedProject = pvProposalFixture(Project::class, ['IDorganization' => $oid, 'title' => 'Deleted project', 'active' => 1]);
    $deletedId = (int)$deletedProject->getId();
    pvProposalCheck($deletedProject->delete(), 'Deleted target fixture must be removed.');
    unset(DbObject::$preload['project_' . $deletedId]);
    $rule = pvProposalFixture(Rule::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Updated & rule',
        'description' => '<p>New rule text</p>', 'scope' => Rule::SCOPE_LOCAL, 'review_date' => '2027-10-16', 'expiration_date' => '2028-10-16']);
    $makeProposal = static function (array $fields) use ($oid, $hid, $ownerId, $point): DeferredProposal {
        return pvProposalFixture(DeferredProposal::class, $fields + ['IDorganization' => $oid, 'IDholon' => $hid,
            'IDuser_author' => $ownerId, 'IDdocument_pv_point' => $point->getId(), 'target_type' => 'project',
            'target_id' => null, 'operation' => 'create', 'status' => DeferredProposal::STATUS_APPLIED]);
    };
    $created = $makeProposal(['target_id' => $project->getId(), 'position' => 1,
        'after_state' => ['title' => 'Roadmap', 'description' => '<p>Created project details</p>']]);
    $ruleAfter = DecisionGovernanceAction::captureRuleState($rule);
    $changed = $makeProposal(['target_type' => 'rule', 'target_id' => $rule->getId(), 'operation' => 'update', 'position' => 2,
        'before_state' => array_replace($ruleAfter, ['title' => 'Original rule', 'description' => '<p>Old rule text</p>']), 'after_state' => $ruleAfter]);
    $restricted = $makeProposal(['target_id' => $privateProject->getId(), 'position' => 3,
        'after_state' => ['title' => 'Private project', 'description' => 'PRIVATE_SNAPSHOT_MUST_NOT_LEAK']]);
    $missing = $makeProposal(['target_id' => $deletedId, 'position' => 4,
        'after_state' => ['title' => 'Deleted project', 'description' => 'DELETED_SNAPSHOT_MUST_NOT_LEAK']]);
    foreach (['pending', 'rejected', 'removed', 'failed', 'conflict'] as $status) {
        $makeProposal(['status' => $status, 'target_id' => $project->getId(), 'after_state' => ['title' => 'Exclude ' . $status]]);
    }
    $makeProposal(['IDdocument_pv_point' => $unfinished->getId(), 'target_id' => $project->getId(), 'after_state' => ['title' => 'Unfinished proposal hidden']]);
    $makeProposal(['IDdocument_pv_point' => $secret->getId(), 'target_id' => $project->getId(), 'after_state' => ['title' => 'Confidential proposal hidden']]);

    $html = $document->getRenderedContentForCurrentViewer();
    pvProposalCheck(str_contains($html, 'Point report text') && str_contains($html, 'Propositions validees'), 'Read-only PV must include its recorded proposals.');
    pvProposalCheck(str_contains($html, 'Roadmap') && str_contains($html, 'Updated &amp; rule'), 'Project and rule proposal titles must be visible and escaped.');
    pvProposalCheck(strpos($html, 'Roadmap') > strpos($html, 'Point report text') && strpos($html, 'Roadmap') < strpos($html, 'data-omo-pv-unhandled-list'), 'Proposals must be inside the handled point, before the unfinished list.');
    pvProposalCheck(str_contains($html, '>Detail</summary>') && substr_count($html, '<details ') === 2, 'Only accessible project and rule proposals must have native expandable Detail buttons.');
    pvProposalCheck(str_contains($html, 'Private project') && str_contains($html, 'Deleted project'), 'Historical titles must survive restricted or missing targets.');
    $payloads = pvProposalPayloads($html);
    $encodedDetails = json_encode($payloads);
    pvProposalCheck(str_contains($encodedDetails, 'Created project details') && str_contains($encodedDetails, 'Old rule text') && str_contains($encodedDetails, 'New rule text'), 'Details must contain the historical creation snapshot and before/after rule changes.');
    foreach (['PRIVATE_SNAPSHOT_MUST_NOT_LEAK', 'DELETED_SNAPSHOT_MUST_NOT_LEAK'] as $hidden) {
        pvProposalCheck(!str_contains($html . $encodedDetails, $hidden), 'Private details must not be embedded: ' . $hidden);
    }
    foreach (['Exclude ', 'Unfinished proposal hidden', 'Confidential proposal hidden', 'data-omo-deferred-proposal-edit', 'data-omo-deferred-proposal-delete'] as $hidden) {
        pvProposalCheck(!str_contains($html, $hidden), 'Read-only report must omit: ' . $hidden);
    }
    $reportData = DeferredProposal::getReportDataForPvPoints($document, [$point]);
    $privateData = array_values(array_filter($reportData[(int)$point->getId()], static fn($item) => $item['id'] === (int)$restricted->getId()))[0];
    pvProposalCheck(empty($privateData['canViewDetails']) && !isset($privateData['afterState'], $privateData['excerpt']), 'Restricted data must be removed before reaching the rendering helper.');
    $staticHtml = $document->getRenderedContentForCurrentViewer(['includePvProposalDetails' => false]);
    pvProposalCheck(str_contains($staticHtml, 'Roadmap') && !str_contains($staticHtml, '<details ') && !str_contains($staticHtml, 'data-omo-change-details-payload'), 'PDF/static rendering must retain summaries without interactive controls or payloads.');
    $document->set('pvstage', 'review');
    pvProposalCheck(!empty($document->save()['status']) && str_contains($document->getRenderedContentForCurrentViewer(), 'Roadmap'), 'Reviewed PV must also show validated proposals.');
    echo "pv_report_proposals_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
