<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/common/choice/deferred-editor-fields.php';

use dbObject\{DbObject, DeferredProposal, Holon, Organization, Project, ProjectFollower, User, UserOrganization};

function projectProposerCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function projectProposerFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    projectProposerCheck(!empty($result['status']), 'Fixture failed: ' . $class . ' ' . json_encode($result));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(5));
    $organization = projectProposerFixture(Organization::class, ['name' => 'Proposer ' . $nonce, 'shortname' => 'proposer-' . $nonce]);
    $oid = (int)$organization->getId();
    $author = projectProposerFixture(User::class, ['firstname' => 'Author', 'email' => 'author-' . $nonce . '@example.invalid', 'active' => 1]);
    $requester = projectProposerFixture(User::class, ['firstname' => 'Requester', 'email' => 'requester-' . $nonce . '@example.invalid', 'active' => 1]);
    $outsider = projectProposerFixture(User::class, ['firstname' => 'Outsider', 'email' => 'outsider-' . $nonce . '@example.invalid', 'active' => 1]);
    $authorId = (int)$author->getId();
    $requesterId = (int)$requester->getId();
    foreach ([$authorId, $requesterId] as $userId) {
        projectProposerFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $userId, 'active' => 1]);
    }
    $_SESSION['currentUser'] = $authorId;
    $_SESSION['currentOrganization'] = $oid;
    $holon = projectProposerFixture(Holon::class, ['IDorganization' => $oid, 'IDtypeholon' => 2, 'name' => 'Collective', 'active' => 1, 'visible' => 1]);
    $defaults = ['IDorganization' => $oid, 'IDholon' => (int)$holon->getId(), 'title' => 'Project', 'active' => 1];

    $normal = projectProposerFixture(Project::class, $defaults);
    projectProposerCheck(!$normal->get('IDuser_proposed'), 'Direct creation must leave the proposer empty.');
    projectProposerCheck(ProjectFollower::getActiveUserIdsForProject((int)$normal->getId()) === [], 'A direct creator must not become the requester.');

    $individual = projectProposerFixture(Project::class, $defaults + ['proposal_status' => Project::PROPOSAL_PENDING]);
    $individualId = (int)$individual->getId();
    projectProposerCheck((int)$individual->get('IDuser_proposed') === $authorId, 'Individual proposal must use the current member.');
    projectProposerCheck(ProjectFollower::getActiveUserIdsForProject($individualId) === [$authorId], 'Individual proposer must follow the new project.');
    $individual->set('proposal_status', Project::PROPOSAL_ACCEPTED);
    projectProposerCheck(!empty($individual->save()['status']), 'Proposal acceptance must succeed.');
    projectProposerCheck(ProjectFollower::setFollowing($individualId, $authorId, false), 'Unsubscribe must succeed.');
    $individual->set('title', 'Edited');
    projectProposerCheck(!empty($individual->save()['status']), 'Project update must succeed.');
    projectProposerCheck(ProjectFollower::getActiveUserIdsForProject($individualId) === [], 'Later saves must respect unsubscribe.');

    $state = DeferredProposal::normalizeProjectState($defaults + ['IDuser_proposed' => $requesterId]);
    $proposal = projectProposerFixture(DeferredProposal::class, [
        'IDorganization' => $oid, 'IDholon' => $holon->getId(), 'IDuser_author' => $authorId,
        'target_type' => DeferredProposal::TARGET_PROJECT, 'operation' => DeferredProposal::OPERATION_CREATE,
        'status' => DeferredProposal::STATUS_PENDING, 'before_state' => [], 'after_state' => $state,
    ]);
    $presentation = $proposal->buildPresentationData();
    projectProposerCheck(($presentation['responsibleLabels'][$requesterId] ?? '') === 'Requester', 'PV and ballot proposal details must resolve the member name.');
    $result = DeferredProposal::applyBatch([$proposal], (int)$holon->getId(), $authorId, 'proposer-test');
    projectProposerCheck(!empty($result['status']), 'Collective application failed: ' . json_encode($result));
    $collective = new Project();
    projectProposerCheck($collective->load((int)$proposal->get('target_id'), true), 'Collective project must be created.');
    projectProposerCheck((int)$collective->get('IDuser_proposed') === $requesterId, 'Application must preserve the selected requester instead of the validator.');
    projectProposerCheck(ProjectFollower::getActiveUserIdsForProject((int)$collective->getId()) === [$requesterId], 'Only the selected requester must follow the collective project.');
    $captured = DeferredProposal::captureProjectState($collective);
    projectProposerCheck(DeferredProposal::normalizeProjectState($captured, $collective) === $captured, 'Project proposal snapshots must round-trip.');
    projectProposerCheck(DeferredProposal::normalizeProjectState(['IDuser_proposed' => ''], $collective)['IDuser_proposed'] === null, 'Clearing the select must remain empty.');
    projectProposerCheck(DeferredProposal::normalizeProjectState(['title' => 'Legacy draft'], $collective)['IDuser_proposed'] === $requesterId, 'Older update drafts must preserve the project proposer.');

    $blankProposal = projectProposerFixture(DeferredProposal::class, [
        'IDorganization' => $oid, 'IDholon' => $holon->getId(), 'IDuser_author' => $authorId,
        'target_type' => DeferredProposal::TARGET_PROJECT, 'operation' => DeferredProposal::OPERATION_CREATE,
        'status' => DeferredProposal::STATUS_PENDING, 'before_state' => [],
        'after_state' => array_replace($state, ['IDuser_proposed' => null]),
    ]);
    projectProposerCheck(!empty(DeferredProposal::applyBatch([$blankProposal], (int)$holon->getId(), $authorId, 'proposer-empty-test')['status']), 'A collective proposal may leave the requester empty.');
    $blankProject = new Project();
    projectProposerCheck($blankProject->load((int)$blankProposal->get('target_id'), true) && !$blankProject->get('IDuser_proposed'), 'Application must respect an explicitly empty requester.');
    projectProposerCheck(ProjectFollower::getActiveUserIdsForProject((int)$blankProject->getId()) === [], 'An empty requester must not subscribe the validator.');

    $options = Project::getProposerOptions($oid);
    projectProposerCheck(isset($options[$authorId], $options[$requesterId]) && !isset($options[(int)$outsider->getId()]), 'The select must contain organization members only.');
    ob_start();
    omoDeferredEditorRenderFields('project', ['IDuser_proposed' => $authorId], $options);
    $html = (string)ob_get_clean();
    projectProposerCheck(str_contains($html, 'name="IDuser_proposed"') && str_contains($html, 'value="' . $authorId . '" selected'), 'Shared editor must preselect the current member.');

    $invalid = new Project();
    foreach ($defaults + ['IDuser_proposed' => $outsider->getId()] as $field => $value) $invalid->set($field, $value);
    projectProposerCheck(empty($invalid->save()['status']) && !(int)$invalid->getId(), 'Foreign organization proposers must be rejected before creation.');

    $collective->set('IDuser_proposed', $authorId);
    projectProposerCheck(!empty($collective->save()['status']), 'An existing project proposer can be changed.');
    projectProposerCheck(ProjectFollower::getActiveUserIdsForProject((int)$collective->getId()) === [$requesterId], 'Editing the proposer must not alter the initial follow-up.');
    echo "project_proposer_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
