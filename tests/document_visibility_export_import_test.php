<?php
declare(strict_types=1);

// Real exporter and module importers; fixtures are rolled back, with no invitations.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, OrganizationExport, Holon, Document, Event, User, ObjectVisibility, ArrayApplication, OrganizationApplication};

function visibilityRoundtripCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function visibilityRoundtripSave($object): void {
    $result = $object->save();
    visibilityRoundtripCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}
final class VisibilityRoundtripOrganization extends Organization {
    public function importDocuments(array $records, int $actor, array $users, array $holons, array &$warnings): array {
        $ids = $projects = $parents = []; $stats = ['documents' => 0];
        self::omo1ImportDocuments($this, $records, $actor, $users, $holons, $ids, $projects, $parents, $stats, $warnings);
        return $ids;
    }
    public function importPvs(array $records, int $actor, array $users, array $holons, array $events, array &$warnings): void {
        $stats = ['pv' => 0, 'pvPoints' => 0];
        self::omo1ImportPvs($this, $records, $actor, $users, $holons, $events, $stats, $warnings);
    }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $makeUser = static function (): User {
        $user = new User(); $user->set('email', 'visibility-roundtrip-' . bin2hex(random_bytes(6)) . '@example.invalid');
        $user->set('active', true); visibilityRoundtripSave($user); return $user;
    };
    $sourceOwner = $makeUser(); $targetOwner = $makeUser(); $actor = $makeUser();
    $makeOrganization = static function (): VisibilityRoundtripOrganization {
        $org = new VisibilityRoundtripOrganization(); $org->set('name', 'Visibility roundtrip');
        $org->set('shortname', 'visibility-' . bin2hex(random_bytes(6))); visibilityRoundtripSave($org);
        $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'value' => 'structure']]]);
        foreach ($apps as $app) {
            $link = new OrganizationApplication(); $link->set('IDorganization', $org->getId());
            $link->set('IDapplication', $app->getId()); $link->set('active', true); visibilityRoundtripSave($link);
        }
        return $org;
    };
    $source = $makeOrganization(); $target = $makeOrganization();
    $makeHolon = static function (Organization $org, string $name, int $type, ?Holon $root = null): Holon {
        $holon = new Holon(); $holon->set('name', $name); $holon->set('IDtypeholon', $type);
        $holon->set('IDorganization', $org->getId()); $holon->set('active', true); $holon->set('visible', true);
        if ($root) { $holon->set('IDholon_org', $root->getId()); $holon->set('IDholon_parent', $root->getId()); }
        visibilityRoundtripSave($holon);
        if (!$root) { $holon->set('IDholon_org', $holon->getId()); visibilityRoundtripSave($holon); }
        return $holon;
    };
    $sourceRoot = $makeHolon($source, 'Source root', 4); $targetRoot = $makeHolon($target, 'Target root', 4);
    $sourceRole = $makeHolon($source, 'Attached role', 1, $sourceRoot);
    $sourceCircle = $makeHolon($source, 'Visibility circle', 2, $sourceRoot);
    $sourceOtherRole = $makeHolon($source, 'Visibility role', 1, $sourceRoot);
    $targetRole = $makeHolon($target, 'Attached role', 1, $targetRoot);
    $targetCircle = $makeHolon($target, 'Visibility circle', 2, $targetRoot);
    $targetOtherRole = $makeHolon($target, 'Visibility role', 1, $targetRoot);
    $holons = [$sourceRoot->getId() => $targetRoot->getId(), $sourceRole->getId() => $targetRole->getId(),
        $sourceCircle->getId() => $targetCircle->getId(), $sourceOtherRole->getId() => $targetOtherRole->getId()];
    $users = [$sourceOwner->getId() => $targetOwner->getId()];
    $types = array_keys(ObjectVisibility::getVisibilityTypeOptions());
    $sourceTarget = static fn (string $type) => match ($type) {
        'circle' => $sourceCircle->getId(), 'role' => $sourceOtherRole->getId(), default => null,
    };
    $expected = [];
    foreach ($types as $index => $readType) {
        $editType = $types[($index + 1) % count($types)];
        $doc = new Document(); $doc->set('title', 'Scope ' . $readType); $doc->set('documenttype', Document::TYPE_HTML);
        $doc->set('IDorganization', $source->getId()); $doc->set('IDholon', $sourceRole->getId());
        $doc->set('IDuser', $sourceOwner->getId()); $doc->set('active', true); visibilityRoundtripSave($doc);
        visibilityRoundtripCheck(!empty($doc->saveVisibilityRule($readType, $sourceTarget($readType))['status']), 'Read fixture must save');
        visibilityRoundtripCheck(!empty($doc->saveEditVisibilityRule($editType, $sourceTarget($editType))['status']), 'Edit fixture must save');
        $expected[$doc->getId()] = [$readType, $editType];
    }
    $makeEvent = static function (Organization $org, Holon $root) use ($actor): Event {
        $event = new Event(); $event->set('title', 'Visibility meeting'); $event->set('IDorganization', $org->getId());
        $event->set('IDholon', $root->getId()); $event->set('IDuser', $actor->getId());
        $event->set('start_at', new DateTimeImmutable('2026-10-01 10:00:00'));
        $event->set('end_at', new DateTimeImmutable('2026-10-01 11:00:00')); visibilityRoundtripSave($event); return $event;
    };
    $sourceEvent = $makeEvent($source, $sourceRoot); $targetEvent = $makeEvent($target, $targetRoot);
    $pv = new Document(); $pv->set('title', 'Visibility PV'); $pv->set('documenttype', Document::TYPE_PV);
    $pv->set('IDorganization', $source->getId()); $pv->set('IDholon', $sourceRole->getId());
    $pv->set('IDevent', $sourceEvent->getId()); $pv->set('IDuser', $sourceOwner->getId());
    $pv->set('IDuser_pv_editor', $actor->getId()); $pv->set('active', true); visibilityRoundtripSave($pv);
    visibilityRoundtripCheck(!empty($pv->saveVisibilityRule('self')['status']), 'PV read fixture must save');
    visibilityRoundtripCheck(!empty($pv->saveEditVisibilityRule('circle', $sourceCircle->getId())['status']), 'PV edit fixture must save');

    $payload = OrganizationExport::build($source, ['documents' => true, 'pv' => true]);
    // Include JSON serialization to verify the wire format, not just PHP values.
    $payload = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    $records = $payload['modules']['documents']['records'];
    visibilityRoundtripCheck(count($records) === count($types), 'All document scopes must be exported');
    foreach ($records as $record) {
        [$readType, $editType] = $expected[$record['sourceId']];
        visibilityRoundtripCheck(($record['visibility']['type'] ?? null) === $readType, 'Exporter must preserve read visibility: ' . $readType);
        visibilityRoundtripCheck(($record['editVisibility']['type'] ?? null) === $editType, 'Exporter must preserve edit visibility: ' . $editType);
    }
    $warnings = [];
    $ids = $target->importDocuments($records, $actor->getId(), $users, $holons, $warnings);
    foreach ($ids as $sourceId => $targetId) {
        $doc = new Document(); visibilityRoundtripCheck($doc->load($targetId), 'Imported document must load');
        visibilityRoundtripCheck((int)$doc->get('IDuser') === (int)$targetOwner->getId(), 'Owner must be remapped');
        foreach (['visibility' => $doc->getPrimaryVisibilityRuleRow(), 'editVisibility' => $doc->getPrimaryEditVisibilityRuleRow()] as $field => $rule) {
            $type = $expected[$sourceId][$field === 'visibility' ? 0 : 1];
            visibilityRoundtripCheck($rule['visibility_type'] === $type, 'Importer must preserve ' . $field . ': ' . $type);
            $expectedTarget = $sourceTarget($type);
            visibilityRoundtripCheck((int)$rule['IDholon'] === (int)($holons[(int)$expectedTarget] ?? 0), 'Explicit visibility target must be remapped independently of document location');
        }
    }
    $target->importPvs($payload['modules']['pv']['records'], $actor->getId(), $users, $holons,
        [$sourceEvent->getId() => $targetEvent->getId()], $warnings);
    $importedPv = new Document();
    visibilityRoundtripCheck($importedPv->load([['IDorganization', $target->getId()], ['IDevent', $targetEvent->getId()]]), 'Imported PV must load');
    visibilityRoundtripCheck($importedPv->getPrimaryVisibilityRuleRow()['visibility_type'] === 'self', 'PV read visibility must survive');
    visibilityRoundtripCheck((int)$importedPv->get('IDuser') === (int)$targetOwner->getId(), 'Private PV owner must not become its secretary');
    visibilityRoundtripCheck((int)$importedPv->getPrimaryEditVisibilityRuleRow()['IDholon'] === (int)$targetCircle->getId(), 'PV edit target must survive');
    visibilityRoundtripCheck($warnings === [], 'Valid modern exports must not generate legacy visibility warnings');

    $legacy = $records[0]; unset($legacy['visibility'], $legacy['editVisibility']); $legacy['legacyVisibility'] = 2;
    $legacyIds = $target->importDocuments([$legacy], $actor->getId(), $users, $holons, $warnings);
    $legacyDoc = new Document(); $legacyDoc->load(reset($legacyIds));
    visibilityRoundtripCheck($legacyDoc->getPrimaryVisibilityRuleRow()['visibility_type'] === 'organization', 'OMO1 numeric read visibility must remain compatible');
    visibilityRoundtripCheck($legacyDoc->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'role', 'Legacy edit default must remain compatible');
    foreach (['absent', 'null', 'empty'] as $missingValue) {
        $old = $records[0]; unset($old['visibility'], $old['editVisibility']);
        if ($missingValue === 'null') { $old['visibility'] = null; $old['editVisibility'] = ['type' => null]; }
        if ($missingValue === 'empty') { $old['visibility'] = []; $old['editVisibility'] = ['type' => '']; }
        $oldIds = $target->importDocuments([$old], $actor->getId(), $users, $holons, $warnings);
        $oldDoc = new Document(); $oldDoc->load(reset($oldIds));
        foreach ([$oldDoc->getPrimaryVisibilityRuleRow(), $oldDoc->getPrimaryEditVisibilityRuleRow()] as $rule) {
            visibilityRoundtripCheck($rule['visibility_type'] === 'role' && (int)$rule['IDholon'] === (int)$targetRole->getId(), 'Undefined scopes must default to the attached role: ' . $missingValue);
        }
    }
    $old['sourceHolonId'] = $sourceCircle->getId();
    $circleIds = $target->importDocuments([$old], $actor->getId(), $users, $holons, $warnings);
    $circleDoc = new Document(); $circleDoc->load(reset($circleIds));
    foreach ([$circleDoc->getPrimaryVisibilityRuleRow(), $circleDoc->getPrimaryEditVisibilityRuleRow()] as $rule) {
        visibilityRoundtripCheck($rule['visibility_type'] === 'circle' && (int)$rule['IDholon'] === (int)$targetCircle->getId(), 'Default role scope must adapt to an attached circle');
    }
    visibilityRoundtripCheck($warnings === [], 'Compatible default scopes must not generate warnings');
    $old['sourceHolonId'] = 0;
    $unattachedIds = $target->importDocuments([$old], $actor->getId(), $users, $holons, $warnings);
    $unattachedDoc = new Document(); $unattachedDoc->load(reset($unattachedIds));
    foreach ([$unattachedDoc->getPrimaryVisibilityRuleRow(), $unattachedDoc->getPrimaryEditVisibilityRuleRow()] as $rule) {
        visibilityRoundtripCheck($rule['visibility_type'] === 'self', 'A missing scope with no attached space must remain private');
    }
    visibilityRoundtripCheck(count($warnings) === 2, 'Unattached default scopes must warn');
    $warnings = [];
    $broken = $records[0]; $broken['visibility'] = ['type' => 'circle', 'sourceHolonId' => 999999999];
    $broken['editVisibility'] = ['type' => 'unknown'];
    $brokenIds = $target->importDocuments([$broken], $actor->getId(), $users, $holons, $warnings);
    $brokenDoc = new Document(); $brokenDoc->load(reset($brokenIds));
    visibilityRoundtripCheck($brokenDoc->getPrimaryVisibilityRuleRow()['visibility_type'] === 'self', 'Missing targets must not broaden access');
    visibilityRoundtripCheck($brokenDoc->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'self', 'Unknown explicit scope must not broaden access');
    visibilityRoundtripCheck(count($warnings) === 2, 'Both invalid scopes must produce explicit warnings');
    $wrongTargets = $holons; $wrongTargets[$sourceCircle->getId()] = $targetOtherRole->getId();
    $broken['visibility'] = ['type' => 'circle', 'sourceHolonId' => $sourceCircle->getId()];
    $warnings = [];
    $brokenIds = $target->importDocuments([$broken], $actor->getId(), $users, $wrongTargets, $warnings);
    $brokenDoc->load(reset($brokenIds));
    visibilityRoundtripCheck($brokenDoc->getPrimaryVisibilityRuleRow()['visibility_type'] === 'self', 'Incompatible remapped targets must remain private');
    visibilityRoundtripCheck(count($warnings) === 2, 'Incompatible targets must warn');
    echo "document_visibility_export_import_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
