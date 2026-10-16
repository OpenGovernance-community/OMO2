<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/projects/shared.php';
require_once dirname(__DIR__) . '/db/migrations.php';

use dbObject\{DbObject, Organization, User, UserOrganization, Holon, Project, DeferredProposal,
    DecisionProcess, DecisionProposal, Document, DocumentPvPoint, Event, ObjectCreationContext};

function omoApiEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function projectOriginCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function projectOriginFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    projectOriginCheck(!empty($result['status']), 'Fixture failed: ' . $class . ' ' . json_encode($result));
    return $object;
}
function projectOriginApply(array $fields, int $organizationId, int $holonId, int $authorId, int $validatorId): Project
{
    $proposal = projectOriginFixture(DeferredProposal::class, $fields + [
        'IDorganization' => $organizationId, 'IDholon' => $holonId, 'IDuser_author' => $authorId,
        'target_type' => 'project', 'operation' => 'create', 'status' => DeferredProposal::STATUS_PENDING,
        'created_at' => '2026-10-15 09:30:00',
        'after_state' => ['title' => 'Origin project', 'IDuser_proposed' => $authorId, 'status' => Project::STATUS_READY],
    ]);
    $result = DeferredProposal::applyBatch([$proposal], $holonId, $validatorId, 'project-origin-test');
    projectOriginCheck(!empty($result['status']), 'Application failed: ' . json_encode($result));
    $project = new Project();
    projectOriginCheck($project->load((int)$proposal->get('target_id'), true), 'Created project must exist.');
    return $project;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(5));
    $organization = projectOriginFixture(Organization::class, ['name' => 'Origin ' . $nonce, 'shortname' => 'origin-' . $nonce]);
    $oid = (int)$organization->getId();
    $author = projectOriginFixture(User::class, ['firstname' => 'David', 'email' => 'origin-author-' . $nonce . '@example.invalid', 'active' => 1]);
    $validator = projectOriginFixture(User::class, ['firstname' => 'Validator', 'email' => 'origin-validator-' . $nonce . '@example.invalid', 'active' => 1]);
    $authorId = (int)$author->getId(); $validatorId = (int)$validator->getId();
    foreach ([$authorId, $validatorId] as $userId) {
        projectOriginFixture(UserOrganization::class, ['IDuser' => $userId, 'IDorganization' => $oid, 'active' => 1]);
    }
    $_SESSION = ['currentUser' => $validatorId, 'currentOrganization' => $oid];
    $holon = projectOriginFixture(Holon::class, ['IDorganization' => $oid, 'IDtypeholon' => 2, 'name' => 'Origin circle', 'active' => 1, 'visible' => 1]);
    $hid = (int)$holon->getId();
    $decision = projectOriginFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $validatorId, 'title' => 'Budget decision', 'visibility_type' => 'everyone',
        'decision_type' => DecisionProcess::TYPE_DECISION, 'status' => DecisionProcess::STATUS_DRAFT]);
    $decisionProposal = projectOriginFixture(DecisionProposal::class, ['IDdecision_group' => $decision->getPrimaryGroup(true)->getId(),
        'IDuser_author' => $authorId, 'title' => 'Project alternative', 'active' => 1]);
    $decisionProject = projectOriginApply(['IDdecision_proposal' => $decisionProposal->getId()], $oid, $hid, $authorId, $validatorId);
    $context = $decisionProject->getCreationContext();
    projectOriginCheck($context['type'] === 'decision' && $context['id'] === (int)$decision->getId(), 'Decision origin must be captured.');
    projectOriginCheck((int)$decisionProject->get('IDusercreation') === $authorId, 'The validator must not replace the original creator.');
    projectOriginCheck($decisionProject->get('proposed_at')->format('Y-m-d') === '2026-10-15', 'Origin date must be the proposal date.');
    $html = omoProjectsRenderOrigin($decisionProject, $author);
    projectOriginCheck(str_contains($html, 'David') && str_contains($html, 'decision collective')
        && str_contains($html, 'Budget decision') && str_contains($html, '#decision-v' . $decision->getId())
        && str_contains($html, 'le 15.10.2026'), 'Decision origin must render its author, title, link and date: ' . $html);

    $event = projectOriginFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $validatorId,
        'title' => 'Planning meeting', 'status' => Event::STATUS_CONFIRMED, 'start_at' => '2026-10-14 10:00:00', 'end_at' => '2026-10-14 11:00:00', 'active' => 1]);
    $document = projectOriginFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $validatorId,
        'IDevent' => $event->getId(), 'title' => 'Planning PV', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_VALIDATED, 'active' => 1]);
    projectOriginCheck(!empty($document->saveVisibilityRule('organization')['status']), 'PV visibility fixture must save.');
    $point = projectOriginFixture(DocumentPvPoint::class, ['IDdocument' => $document->getId(), 'title' => 'Project point', 'pointtype' => DocumentPvPoint::TYPE_DECISION]);
    $meetingProject = projectOriginApply(['IDdocument_pv_point' => $point->getId()], $oid, $hid, $authorId, $validatorId);
    $meetingContext = $meetingProject->getCreationContext();
    projectOriginCheck($meetingContext['type'] === 'event' && $meetingContext['id'] === (int)$event->getId()
        && $meetingContext['document_id'] === (int)$document->getId(), 'Meeting projects must retain both event and PV.');
    $html = omoProjectsRenderOrigin($meetingProject, $author);
    projectOriginCheck(str_contains($html, '(reunion ') && str_contains($html, 'Planning meeting')
        && str_contains($html, '#documents-d' . $document->getId()) && str_contains($html, 'le 15.10.2026'), 'Meeting origin must link to its PV: ' . $html);

    $document->set('IDevent', null);
    projectOriginCheck(!empty($document->save()['status']), 'PV detachment must save.');
    $pvProject = projectOriginApply(['IDdocument_pv_point' => $point->getId()], $oid, $hid, $authorId, $validatorId);
    projectOriginCheck($pvProject->getCreationContext()['type'] === 'document', 'Standalone PV projects must retain their document.');
    projectOriginCheck($meetingProject->getCreationContext() === $meetingContext, 'Source edits must not alter saved origins.');
    $meetingProject->set('creation_context', ObjectCreationContext::fromDecision($decision));
    $meetingProject->set('title', 'Edited project');
    projectOriginCheck(!empty($meetingProject->save()['status']) && $meetingProject->getCreationContext() === $meetingContext, 'Project edits must preserve original provenance.');

    $direct = projectOriginFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Direct meeting project',
        'creation_context' => ObjectCreationContext::fromDocument($document), 'active' => 1]);
    projectOriginCheck(!$direct->get('IDuser_proposed') && str_contains(omoProjectsRenderOrigin($direct, $validator), 'Cree par'), 'Direct meeting creation must keep the creator fallback.');

    $legacy = projectOriginFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Legacy project', 'active' => 1]);
    projectOriginFixture(DeferredProposal::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser_author' => $authorId,
        'IDdocument_pv_point' => $point->getId(), 'target_type' => 'project', 'operation' => 'create',
        'target_id' => $legacy->getId(), 'status' => DeferredProposal::STATUS_APPLIED, 'after_state' => ['title' => 'Legacy project'],
        'created_at' => '2026-10-13 08:00:00']);
    $migration = loadSqlMigrationContent(dirname(__DIR__) . '/sql/2026-10-16-03-project-creation-context.sql');
    $statements = splitSqlStatements($migration);
    foreach ($statements as $statement) {
        if (str_contains($statement, 'UPDATE `project`')) executeSqlMigrationStatements($pdo, $statement);
    }
    projectOriginCheck($legacy->load((int)$legacy->getId(), true) && $legacy->getCreationContext()['id'] === (int)$document->getId()
        && (int)$legacy->get('IDuser_proposed') === $authorId, 'Migration must recover historical origins and authors.');
    $legacyContext = $legacy->getCreationContext();
    foreach ($statements as $statement) {
        if (str_contains($statement, 'UPDATE `project`')) executeSqlMigrationStatements($pdo, $statement);
    }
    projectOriginCheck($legacy->load((int)$legacy->getId(), true) && $legacy->getCreationContext() === $legacyContext, 'Backfill must be idempotent.');

    $legacyBlank = projectOriginFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Blank legacy requester', 'active' => 1]);
    $updatedOnly = projectOriginFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Direct project later updated', 'active' => 1]);
    foreach ([$legacyBlank, $updatedOnly] as $legacyTarget) {
        projectOriginFixture(DeferredProposal::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser_author' => $authorId,
            'IDdecision_proposal' => $decisionProposal->getId(), 'target_type' => 'project',
            'operation' => $legacyTarget === $legacyBlank ? 'create' : 'update', 'target_id' => $legacyTarget->getId(),
            'status' => DeferredProposal::STATUS_APPLIED, 'after_state' => ['title' => 'Historical proposal', 'IDuser_proposed' => null]]);
    }
    foreach ($statements as $statement) {
        if (str_contains($statement, 'UPDATE `project`')) executeSqlMigrationStatements($pdo, $statement);
    }
    projectOriginCheck($legacyBlank->load((int)$legacyBlank->getId(), true) && !$legacyBlank->get('IDuser_proposed')
        && $legacyBlank->getCreationContext()['id'] === (int)$decision->getId(), 'Backfill must recover decisions while preserving an explicitly empty requester.');
    projectOriginCheck($updatedOnly->load((int)$updatedOnly->getId(), true) && $updatedOnly->getCreationContext() === [], 'Later collective edits must not become the creation origin.');

    projectOriginCheck(!empty($document->saveVisibilityRule('self')['status']), 'Restricted source fixture must save.');
    $_SESSION['currentUser'] = $authorId;
    projectOriginCheck(!str_contains(omoProjectsRenderOrigin($meetingProject, $author), '<a '), 'Restricted source links must be omitted.');
    $_SESSION['currentUser'] = $validatorId;

    projectOriginCheck($document->delete(), 'Source PV deletion must succeed.');
    DbObject::$preload = []; // Simulate the following page request after deleting the source.
    projectOriginCheck($meetingProject->load((int)$meetingProject->getId(), true) && $meetingProject->getCreationContext() === $meetingContext, 'Deleting the source must preserve the origin snapshot.');
    $html = omoProjectsRenderOrigin($meetingProject, $author);
    projectOriginCheck(str_contains($html, 'Planning meeting') && !str_contains($html, '<a '), 'Missing sources must keep readable provenance without a broken link.');

    $unsafe = new Project();
    $unsafe->set('IDorganization', $oid);
    $unsafe->set('creation_context', ['type' => 'decision', 'id' => 99999999, 'label' => '<img src=x onerror=alert(1)>']);
    projectOriginCheck(!str_contains(omoProjectsRenderOrigin($unsafe, $author), '<img'), 'Origin titles must be escaped.');
    echo "project_creation_context_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
