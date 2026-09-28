<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Holon, Organization};

function modelRoundtripCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function modelRoundtripSave($object): void
{
    modelRoundtripCheck(!empty($object->save()['status']), 'Fixture save failed');
}

class ModelRoundtripOrganization extends Organization
{
    public function importRecord(Holon $target, array $record, bool $preserveName = false, bool $isRoot = false): void
    {
        $this->applyImportedCompactRecordToHolon($target, $record, 0, $preserveName, $isRoot);
    }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organization = new ModelRoundtripOrganization();
    $organization->set('name', 'Model roundtrip fixture');
    modelRoundtripSave($organization);

    foreach ([null, 0, 2] as $minimum) {
        foreach ([null, 'Mon groupe'] as $name) {
            $source = new Holon();
            foreach ([
                'IDorganization' => $organization->getId(), 'IDtypeholon' => 3,
                'active' => true, 'visible' => false, 'name' => $name,
                'templatename' => 'Groupe', 'admin_min' => $minimum, 'admin_max' => null,
                'lockedadminmin' => true, 'adminminoverride' => true,
            ] as $field => $value) $source->set($field, $value);
            modelRoundtripSave($source);
            modelRoundtripCheck($source->load($source->getId(), true), 'Reload source');

            // JSON is the actual boundary used by structure exports and model copies.
            $record = json_decode(json_encode($source->toCompactExportRecord()), true);
            modelRoundtripCheck(array_key_exists('adminMin', $record) === ($minimum !== null), 'Explicit zero must be exported separately from an undefined minimum');
            $target = new Holon();
            $target->set('IDorganization', $organization->getId());
            $organization->importRecord($target, $record);
            modelRoundtripCheck((int)$target->getId() > 0 && $target->load($target->getId(), true), 'Reload imported object');
            modelRoundtripCheck($target->get('admin_min') === $minimum, 'Minimum must survive export/import: ' . var_export($minimum, true));
            modelRoundtripCheck($target->get('admin_max') === null, 'Undefined maximum stays undefined');
            modelRoundtripCheck($target->get('name') === $name, 'A template-only name must not be replaced by a generic name');
            modelRoundtripCheck($target->getDisplayName() === $source->getDisplayName(), 'Displayed group name must survive export/import');
            modelRoundtripCheck($target->get('templatename') === 'Groupe' && (int)$target->get('IDtypeholon') === 3, 'Group definition is preserved');
            modelRoundtripCheck((bool)$target->get('lockedadminmin') && (bool)$target->get('adminminoverride'), 'Minimum flags are preserved');
        }
    }

    foreach ([null, ''] as $emptyMinimum) {
        $organization->importRecord($target, ['name' => 'Sans minimum', 'adminMin' => $emptyMinimum]);
        modelRoundtripCheck($target->get('admin_min') === null, 'Explicit empty minimum stays undefined');
    }
    $organization->importRecord($target, []);
    modelRoundtripCheck($target->getDisplayName() === 'Espace', 'Unnamed records still receive the fallback');
    $target->set('name', 'Organisation choisie');
    $organization->importRecord($target, ['name' => 'Modele', 'templateName' => 'Groupe'], true, true);
    modelRoundtripCheck($target->getDisplayName() === 'Organisation choisie' && $target->get('templatename') === null, 'Organization root keeps its chosen name');
} finally {
    $pdo->rollBack();
}

echo "organization_model_structure_roundtrip_test: OK\n";
