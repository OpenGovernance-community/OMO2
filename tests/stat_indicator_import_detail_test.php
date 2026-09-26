<?php
declare(strict_types=1);

// Render the real template with in-memory resources, without the HTTP bootstrap or DB.
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/recurrenceschedule.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicator.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicatorimport.class.php';

final class ImportDetailIndicator extends \dbObject\StatIndicator
{
    public function getMeasurements() { return []; }
    public function getReferencePoints() { return []; }
    public function getEffectiveMeasurementFrequency(): ?string { return null; }
}

function omoStatsResolveContext($organizationId, $holonId) { return ['status' => true]; }
function omoStatsLoadImport($id, $organizationId) { return $id === 20 ? $GLOBALS['fixtureImport'] : null; }
function omoStatsLoadIndicator($id, $organizationId) { return $id === 10 ? $GLOBALS['fixtureIndicator'] : null; }
function omoStatsCanEditIndicator($indicator, $context) { return true; }
function omoStatsCanDeleteContextResource($import, $context) { return $GLOBALS['fixtureCanDetach']; }
function omoStatsContextLabel($indicator) { return 'Source circle'; }
function omoStatsResponsibleAssignmentLabel($indicator) { return 'Responsible'; }
function omoStatsGetIndicatorReferencePercentage($indicator, $latest, $points) { return null; }
function omoStatsGetIndicatorOverdueInfo($indicator) { return ['severity' => '', 'overdue_days' => 0]; }
function omoStatsBuildIndicatorChartData($indicator, $values, $points, $severity) { return []; }
function omoStatsRenderChart($indicator, $values, $points, $variant, $severity, $interactive) { return '<svg data-test-chart></svg>'; }
function omoStatsRenderInteractiveChartRange($chartData) { return ''; }
function omoStatsT($key, $params = []) { return $key; }
function omoApiEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

require_once dirname(__DIR__) . '/omo/api/stats/shared.php';

if (($argv[1] ?? '') === '--render') {
    $scenario = $argv[2];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION = ['currentOrganization' => 1];
    $_GET = ['cid' => 7, 'id' => 10];
    if ($scenario !== 'original') {
        $_GET['import_id'] = match ($scenario) {
            'missing' => 999,
            'malformed' => 'invalid',
            default => 20,
        };
    }
    if ($scenario === 'wrong-source') {
        $_GET['id'] = 999;
    }
    $fixtureCanDetach = $scenario !== 'read-only';
    $fixtureImport = new \dbObject\StatIndicatorImport();
    $fixtureImport->set('IDstatindicator', 10);
    $fixtureIndicator = new ImportDetailIndicator();
    $fixtureIndicator->set('name', 'Source indicator');
    $fixtureIndicator->set('reference_type', 'none');
    $fixtureIndicator->set('source_type', 'manual');
    $source = (string)file_get_contents(dirname(__DIR__) . '/omo/api/stats/detail.php');
    $source = preg_replace('/^require_once .*;\R/m', '', $source, -1, $removed);
    if ($removed !== 2) {
        throw new RuntimeException('Review the template bootstrap isolation.');
    }
    http_response_code(200);
    ob_start();
    register_shutdown_function(static function (): void {
        $html = ob_get_clean();
        echo json_encode(['status' => http_response_code(), 'html' => $html], JSON_THROW_ON_ERROR);
    });
    eval('?>' . $source);
    exit;
}

function assertImportDetail(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach (['original', 'import', 'read-only', 'wrong-source', 'missing', 'malformed'] as $scenario) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --render ' . escapeshellarg($scenario);
    $result = json_decode((string)shell_exec($command), true, 512, JSON_THROW_ON_ERROR);
    $html = $result['html'];
    if (in_array($scenario, ['missing', 'malformed'], true)) {
        assertImportDetail($result['status'] === 404, 'Invalid imports must not fall back to a supplied original ID.');
        assertImportDetail(!str_contains($html, 'data-omo-stats-detail'), 'Invalid imports must not expose original content.');
        continue;
    }
    assertImportDetail($result['status'] === 200, 'Valid details must render.');
    assertImportDetail(str_contains($html, 'Source indicator') && str_contains($html, 'data-test-chart'), 'Imports must display their source content.');
    if ($scenario === 'original') {
        assertImportDetail(str_contains($html, 'data-omo-stats-open-editor-url'), 'Originals must keep editing available.');
        assertImportDetail(str_contains($html, 'data-omo-stats-add-value-form'), 'Originals must keep measurement entry available.');
        assertImportDetail(!str_contains($html, 'data-omo-stats-delete-import'), 'Originals must not expose detach.');
        continue;
    }
    assertImportDetail(!str_contains($html, 'data-omo-stats-open-editor-url'), 'Imports must not open the original editor, even when the viewer can edit it.');
    assertImportDetail(!str_contains($html, 'data-omo-stats-add-value-form'), 'Imports must not modify source values.');
    assertImportDetail(!str_contains($html, 'data-omo-stats-delete-indicator'), 'Imports must never expose original deletion.');
    assertImportDetail(str_contains($html, 'import_id=20'), 'Reload URLs must retain the import identity.');
    assertImportDetail(
        str_contains($html, 'data-omo-stats-delete-import="20"') === ($scenario !== 'read-only'),
        'Detach must target the import and respect its permission.'
    );
}

echo "Indicator import detail tests passed.\n";
