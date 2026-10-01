<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';

use dbObject\{DbObject, Organization, Permission};

function lexiconLabelCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$pole = Organization::normalizeLexicon(['space' => ['label' => 'Pole']]);
$space = Organization::getDefaultLexicon();
$network = Organization::normalizeLexicon(['space' => ['label' => 'Reseaux']]);
foreach ([
    ['Holon / Holons', 'Pole / Poles', $pole],
    ['Ajouter un holon', 'Ajouter un pole', $pole],
    ['Modifier des holons.', 'Modifier des poles.', $pole],
    ['Le holon courant, du holon, au holon, ce holon, de holon.', "L'espace courant, de l'espace, \u{00e0} l'espace, cet espace, d'espace.", $space],
    ['Holons', 'Reseaux', $network],
    ['CAN_EDIT_HOLON {holon} holon.move /holons/create.php IDholon', 'CAN_EDIT_HOLON {holon} holon.move /holons/create.php IDholon', $pole],
] as [$source, $expected, $lexicon]) {
    lexiconLabelCheck(Organization::formatLexiconText($source, $lexicon) === $expected, $source);
}

// The same raw catalog can be rendered for several organizations without leaking labels.
$first = Permission::getEditorCatalog($pole);
$second = Permission::getEditorCatalog($space);
lexiconLabelCheck(array_column($first, 'key') === array_column($second, 'key'), 'Permission identifiers and order stay intact');
foreach ($first as $entry) {
    lexiconLabelCheck(!preg_match('/\bholons?\b/i', $entry['title'] . ' ' . $entry['description'] . ' ' . $entry['groupTitle']), 'Legacy term in permission display: ' . $entry['key']);
    if ($entry['group'] === 'holons') lexiconLabelCheck($entry['groupTitle'] === 'Poles', 'Custom permission group');
}
$byKey = array_column($second, null, 'key');
lexiconLabelCheck($byKey['CAN_ADD_HOLON']['groupTitle'] === 'Espaces', 'Second organization uses its own lexicon');

// Adapt the translated template before interpolation, preserving user-authored names.
unset($_SESSION['currentOrganization']);
$sourceLang = ['sample' => ['text' => 'Holon : {holon}', 'context' => 'Test'], 'count' => ['one' => '{count} holon', 'other' => '{count} holons', 'context' => 'Test']];
lexiconLabelCheck(t('sample', ['holon' => 'Mon Holon personnel'], $sourceLang, $sourceLang) === 'Espace : Mon Holon personnel', 'Names and placeholder identifiers must not be rewritten');
lexiconLabelCheck(t('count', ['count' => 2], $sourceLang, $sourceLang) === '2 espaces', 'Plural translations');
lexiconLabelCheck($sourceLang['sample']['text'] === 'Holon : {holon}', 'Translation source remains organization-neutral');

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organizations = [];
    foreach (['Pole', 'Collectif'] as $label) {
        $organization = new Organization();
        $organization->set('name', 'Lexicon display fixture');
        $organization->setLexicon(['space' => ['label' => $label]]);
        lexiconLabelCheck(!empty($organization->save()['status']), 'Save lexicon fixture');
        $organizations[] = $organization;
    }
    foreach ([0, 1, 0] as $index) {
        $_SESSION['currentOrganization'] = (int)$organizations[$index]->getId();
        $expected = $index === 0 ? 'Pole' : 'Collectif';
        lexiconLabelCheck(t('sample', ['holon' => 'Mon Holon personnel'], $sourceLang, $sourceLang) === $expected . ' : Mon Holon personnel', 'Cached translations follow organization switches');
    }
} finally {
    $pdo->rollBack();
}
echo "Lexicon holon labels: OK\n";
