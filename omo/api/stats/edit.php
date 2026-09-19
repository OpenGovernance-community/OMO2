<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/spreadsheet.php';

use dbObject\StatIndicator;
use dbObject\StatIndicatorReferencePoint;
use dbObject\ArrayDocument;
use dbObject\ArrayUserOrganization;
use dbObject\Document;
use dbObject\DocumentPvPoint;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$indicatorId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoStatsResolveContext($organizationId, $currentHolonId);
$context['pvMeetingPermission'] = commonResolvePvMeetingPermissionContext($organizationId);

if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape((string)($context['message'] ?? omoStatsT('stats.error.context'))) . '</div>';
    exit;
}

$indicator = $indicatorId > 0 ? omoStatsLoadIndicator($indicatorId, $organizationId) : new StatIndicator();
if ($indicatorId > 0 && !($indicator instanceof StatIndicator)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.not_found')) . '</div>';
    exit;
}
if ($indicatorId > 0 && !omoStatsCanEditIndicator($indicator, $context)) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.forbidden')) . '</div>';
    exit;
}
if ($indicatorId <= 0 && !omoStatsCanCreateContext($context)) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.forbidden')) . '</div>';
    exit;
}

if ($indicatorId <= 0) {
    $indicator->set('reference_type', StatIndicator::REFERENCE_NONE);
}

$indicatorResponsibleOptions = [];
$organizationMembers = new ArrayUserOrganization();
$organizationMembers->loadActiveForOrganization($organizationId);
foreach ($organizationMembers as $membership) {
    $userId = (int)$membership->get('IDuser');
    if ($userId > 0) {
        $indicatorResponsibleOptions[] = [
            'id' => $userId,
            'label' => DocumentPvPoint::getUserDisplayNameForOrganization($userId, $organizationId),
        ];
    }
}

if (!function_exists('omoStatsEditInputNumber')) {
    function omoStatsEditInputNumber($value)
    {
        if (!is_numeric($value)) {
            return '';
        }
        return rtrim(rtrim(number_format((float)$value, 6, '.', ''), '0'), '.');
    }
}

$referencePoints = $indicatorId > 0
    ? omoStatsCollectionItems($indicator->getReferencePoints(), StatIndicatorReferencePoint::class)
    : [];
$referenceType = StatIndicator::normalizeReferenceType($indicator->get('reference_type'));
$ceilingValue = $referenceType === StatIndicator::REFERENCE_CEILING
    ? omoStatsGetCeilingValue($referencePoints)
    : null;
$measurementFrequency = StatIndicator::normalizeMeasurementFrequency($indicator->get('measurement_frequency'));
$measurementSchedule = StatIndicator::normalizeMeasurementSchedule($measurementFrequency, $indicator->get('measurement_schedule'));
$measurementFrequencyOptions = [['value' => '', 'label' => omoStatsT('stats.frequency.none')]];
foreach (StatIndicator::getMeasurementFrequencyCatalog() as $frequency) {
    $measurementFrequencyOptions[] = [
        'value' => $frequency,
        'label' => omoStatsMeasurementFrequencyLabel($frequency),
    ];
}
$measurementScheduleOptions = [];
foreach (StatIndicator::getMeasurementFrequencyCatalog() as $frequency) {
    $measurementScheduleOptions[$frequency] = omoStatsMeasurementScheduleOptions($frequency);
}

$requestedSourceType = StatIndicator::normalizeSourceType($_GET['source_type'] ?? StatIndicator::SOURCE_MANUAL);
$selectedSourceType = $indicatorId > 0
    ? StatIndicator::normalizeSourceType($indicator->get('source_type'))
    : $requestedSourceType;
$isEthercalcSource = in_array($selectedSourceType, [StatIndicator::SOURCE_ETHERCALC_CELL, StatIndicator::SOURCE_ETHERCALC_TABLE], true);
$isSpreadsheetSource = in_array($selectedSourceType, [StatIndicator::SOURCE_SPREADSHEET_CELL, StatIndicator::SOURCE_SPREADSHEET_TABLE], true);
$isEthercalcCellSource = $selectedSourceType === StatIndicator::SOURCE_ETHERCALC_CELL;
$isSpreadsheetCellSource = $selectedSourceType === StatIndicator::SOURCE_SPREADSHEET_CELL;
$isAutomaticSource = $isEthercalcSource || $isSpreadsheetSource;
$selectedSourceDocumentId = $indicatorId > 0
    ? (int)$indicator->get('IDdocument')
    : (isset($_GET['source_document_id']) && is_numeric($_GET['source_document_id']) ? (int)$_GET['source_document_id'] : 0);
$ethercalcDocuments = [];
$spreadsheetDocuments = [];
$documents = new ArrayDocument();
$documents->load([
    'where' => [
        ['field' => 'IDorganization', 'value' => $organizationId],
        ['field' => 'active', 'value' => 1],
    ],
    'orderBy' => [
        ['field' => 'title', 'dir' => 'ASC'],
        ['field' => 'id', 'dir' => 'ASC'],
    ],
]);
$documents->filterVisibleForCurrentViewer($organizationId);
foreach ($documents as $document) {
    if ($document instanceof Document && $document->isEthercalcDocument() && $document->getEthercalcRoomId() !== '') {
        $ethercalcDocuments[] = $document;
    }
    if (
        $document instanceof Document
        && $document->isUploadedFile()
        && $document->hasStoredFile()
        && omoSpreadsheetSupportsFilename($document->getStoredFileDownloadName())
    ) {
        $spreadsheetDocuments[] = $document;
    }
}

if (count($referencePoints) === 0 || $referenceType === StatIndicator::REFERENCE_CEILING) {
    $startValue = $referenceType === StatIndicator::REFERENCE_CEILING && is_numeric($ceilingValue)
        ? (float)$ceilingValue
        : 0;
    $endValue = $referenceType === StatIndicator::REFERENCE_CEILING && is_numeric($ceilingValue)
        ? (float)$ceilingValue
        : 100;
    $startPoint = new StatIndicatorReferencePoint();
    $startPoint->set('position_percent', 0);
    $startPoint->set('value', $startValue);
    $startPoint->set('point_at', new DateTime());
    $endPoint = new StatIndicatorReferencePoint();
    $endPoint->set('position_percent', 100);
    $endPoint->set('value', $endValue);
    $endPoint->set('point_at', new DateTime('+1 month'));
    $referencePoints = [$startPoint, $endPoint];
}

usort($referencePoints, static function (StatIndicatorReferencePoint $left, StatIndicatorReferencePoint $right) {
    return (float)$left->get('position_percent') <=> (float)$right->get('position_percent');
});


$sourceLang = [
    'editor.identity' => ['text' => 'Votre indicateur', 'context' => 'Heading for indicator identity fields.'],
    'editor.identity_help' => ['text' => 'Donnez un nom clair a la mesure et indiquez qui en assure le suivi.', 'context' => 'Indicator identity help.'],
    'editor.name' => ['text' => 'Nom de l indicateur', 'context' => 'Required indicator name field.'],
    'editor.name_placeholder' => ['text' => 'Ex. Chiffre d affaires mensuel', 'context' => 'Example indicator name.'],
    'editor.description' => ['text' => 'Description', 'context' => 'Indicator description field.'],
    'editor.description_placeholder' => ['text' => 'Que mesure cet indicateur ? Dans quelle unite ?', 'context' => 'Description prompt.'],
    'editor.source_url' => ['text' => 'Lien vers la source', 'context' => 'Optional measurement source URL field.'],
    'editor.chart' => ['text' => 'Graphique et reference', 'context' => 'Heading for indicator chart settings.'],
    'editor.chart_help' => ['text' => 'Choisissez comment lire vos mesures et les comparer a votre objectif.', 'context' => 'Chart settings help.'],
    'editor.cumulative' => ['text' => 'Afficher le cumul', 'context' => 'Cumulative display checkbox.'],
    'editor.cumulative_help' => ['text' => 'Les valeurs en barres, leur cumul sur une seconde echelle.', 'context' => 'Explanation of cumulative display.'],
    'editor.minimum' => ['text' => 'Valeur basse du graphique', 'context' => 'Optional chart lower scale value.'],
    'editor.minimum_help' => ['text' => 'Laissez vide pour une echelle automatique.', 'context' => 'Help for chart lower scale value.'],
    'editor.reference_type' => ['text' => 'Type de reference', 'context' => 'Indicator reference type field.'],
    'editor.scale_value_help' => ['text' => 'Ex. 10 000 de chiffre d affaires par mois.', 'context' => 'Example of a reference on the independent values axis.'],
    'editor.scale_cumulative_help' => ['text' => 'Ex. 120 000 de chiffre d affaires sur l annee.', 'context' => 'Example of a reference on the cumulative axis.'],
    'editor.saving' => ['text' => 'Enregistrement...', 'context' => 'Save action while an indicator request is pending.'],
    'editor.saved' => ['text' => 'Indicateur enregistre.', 'context' => 'Successful indicator save feedback.'],
];
$editorBundle = omoLoadTranslationBundle('omo_stats_editor', $sourceLang);
$editT = static function ($key) use ($editorBundle, $sourceLang) {
    return t($key, [], $editorBundle, $sourceLang);
};

$editHelp = static function ($label, $text) {
    return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
        . '<summary aria-label="' . omoApiEscape($label) . '">?</summary>'
        . '<div class="generic-context-help__content">' . omoApiEscape($text) . '</div></details>';
};
$fieldLengths = StatIndicator::attributeLength();
$referenceScale = StatIndicator::normalizeReferenceScale($indicator->get('reference_scale'));
$showCumulative = (int)$indicator->get('show_cumulative') > 0;
?>
<link rel="stylesheet" href="/common/assets/components.css?v=20260919-compact-help">
<div class="omo-stats-editor generic-drawer-content" data-omo-stats-editor data-indicator-id="<?= (int)$indicatorId ?>">
    <div hidden data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoStatsT($indicatorId > 0 ? 'stats.form.edit_title' : 'stats.form.create_title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoStatsT('stats.form.intro')) ?>"></div>
    <form id="omoStatsIndicatorForm" class="generic-form-stack generic-form-stack--compact" action="/omo/api/stats/action.php" method="post">
        <section class="generic-form-grid generic-form-grid--pair" aria-label="<?= omoApiEscape($editT('editor.identity')) ?>">
            <div class="generic-form-field">
                <div class="generic-inline-help">
                    <label class="generic-form-label" for="stats-editor-name"><?= omoApiEscape($editT('editor.name')) ?> *</label>
                    <?= $editHelp($editT('editor.name'), $editT('editor.identity_help')) ?>
                </div>
                <input id="stats-editor-name" class="generic-form-control generic-form-control--compact" type="text" name="name" value="<?= omoApiEscape((string)$indicator->get('name')) ?>" maxlength="<?= (int)$fieldLengths['name'] ?>" placeholder="<?= omoApiEscape($editT('editor.name_placeholder')) ?>" required>
            </div>
            <div class="generic-form-field">
                <div class="generic-inline-help">
                    <label class="generic-form-label" for="stats-editor-responsible"><?= omoApiEscape(omoStatsT('stats.form.responsible')) ?></label>
                    <?= $editHelp(omoStatsT('stats.form.responsible'), omoStatsT('stats.form.responsible_help')) ?>
                </div>
                <select id="stats-editor-responsible" class="generic-form-control generic-form-control--compact" name="IDuser_responsible">
                    <option value=""><?= omoApiEscape(omoStatsT('stats.form.responsible_none')) ?></option>
                    <?php foreach ($indicatorResponsibleOptions as $option): ?>
                        <option value="<?= (int)$option['id'] ?>"<?= (int)$indicator->get('IDuser_responsible') === (int)$option['id'] ? ' selected' : '' ?>><?= omoApiEscape($option['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="generic-form-field generic-form-field--full">
                <span class="generic-form-label"><?= omoApiEscape($editT('editor.description')) ?></span>
                <textarea class="generic-form-control generic-form-control--compact" name="description" rows="2" placeholder="<?= omoApiEscape($editT('editor.description_placeholder')) ?>"><?= omoApiEscape((string)$indicator->get('description')) ?></textarea>
            </label>
        </section>

    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
        <div class="generic-form-section__heading">
            <div class="generic-form-section__copy">
                <div class="generic-heading-with-help"><h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoStatsT('stats.form.source_title')) ?></h3><?= $editHelp(omoStatsT('stats.form.source_title'), omoStatsT('stats.form.source_help')) ?></div>
            </div>
        </div>
        <label class="omo-stats-field generic-form-field">
            <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.form.source_type')) ?></span>
            <select class="generic-form-control generic-form-control--compact" name="source_type" data-omo-stats-source-type>
                <option value="<?= StatIndicator::SOURCE_MANUAL ?>"<?= $selectedSourceType === StatIndicator::SOURCE_MANUAL ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.form.source_manual')) ?></option>
                <option value="<?= StatIndicator::SOURCE_ETHERCALC_CELL ?>"<?= $selectedSourceType === StatIndicator::SOURCE_ETHERCALC_CELL ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.form.source_ethercalc_cell')) ?></option>
                <option value="<?= StatIndicator::SOURCE_ETHERCALC_TABLE ?>"<?= $selectedSourceType === StatIndicator::SOURCE_ETHERCALC_TABLE ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.form.source_ethercalc_table')) ?></option>
                <option value="<?= StatIndicator::SOURCE_SPREADSHEET_CELL ?>"<?= $selectedSourceType === StatIndicator::SOURCE_SPREADSHEET_CELL ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.form.source_spreadsheet_cell')) ?></option>
                <option value="<?= StatIndicator::SOURCE_SPREADSHEET_TABLE ?>"<?= $selectedSourceType === StatIndicator::SOURCE_SPREADSHEET_TABLE ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.form.source_spreadsheet_table')) ?></option>
            </select>
        </label>
    <section
        class="generic-form-stack generic-form-stack--compact"
        data-omo-stats-source-panel="ethercalc_cell ethercalc_table"
        <?= $isEthercalcSource ? '' : ' hidden' ?>
    >
        <div class="generic-form-grid">
            <label class="omo-stats-field generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.document')) ?></span>
                <select class="generic-form-control generic-form-control--compact" name="ethercalc_document_id" required>
                    <?php foreach ($ethercalcDocuments as $document): ?>
                        <option value="<?= (int)$document->getId() ?>"<?= (int)$document->getId() === $selectedSourceDocumentId ? ' selected' : '' ?>><?= omoApiEscape((string)$document->get('title')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="ethercalc_cell"<?= $isEthercalcCellSource ? '' : ' hidden' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.cell')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="ethercalc_cell" value="<?= omoApiEscape((string)($indicator->get('ethercalc_cell') ?: 'A1')) ?>" placeholder="A1" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="ethercalc_cell"<?= $isEthercalcCellSource ? '' : ' hidden' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.frequency_measurement')) ?></span>
                    <select class="generic-form-control generic-form-control--compact" name="ethercalc_frequency">
                        <?php foreach (StatIndicator::getEthercalcFrequencyCatalog() as $frequency => $label): ?>
                            <option value="<?= omoApiEscape($frequency) ?>"<?= $frequency === StatIndicator::normalizeEthercalcFrequency($indicator->get('ethercalc_frequency')) ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.import.ethercalc.frequency_' . $frequency)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="ethercalc_table"<?= $isEthercalcCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.range')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="ethercalc_range" value="<?= omoApiEscape((string)($indicator->get('ethercalc_range') ?: 'A1:C100')) ?>" placeholder="A1:C100" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="ethercalc_table"<?= $isEthercalcCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.date_column')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="ethercalc_date_column" value="<?= omoApiEscape((string)($indicator->get('ethercalc_date_column') ?: 'A')) ?>" placeholder="A" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="ethercalc_table"<?= $isEthercalcCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.value_columns')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="ethercalc_value_columns" value="<?= omoApiEscape((string)($indicator->get('ethercalc_value_column') ?: 'B')) ?>" placeholder="B,C" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="ethercalc_table"<?= $isEthercalcCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.ethercalc.frequency_sync')) ?></span>
                    <select class="generic-form-control generic-form-control--compact" name="ethercalc_frequency">
                        <?php foreach (StatIndicator::getEthercalcFrequencyCatalog() as $frequency => $label): ?>
                            <option value="<?= omoApiEscape($frequency) ?>"<?= $frequency === StatIndicator::normalizeEthercalcFrequency($indicator->get('ethercalc_frequency')) ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.import.ethercalc.frequency_' . $frequency)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
        </div>
    </section>
    <section
        class="generic-form-stack generic-form-stack--compact"
        data-omo-stats-source-panel="spreadsheet_cell spreadsheet_table"
        <?= $isSpreadsheetSource ? '' : ' hidden' ?>
    >
        <div class="generic-form-grid">
            <label class="omo-stats-field generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.document')) ?></span>
                <select class="generic-form-control generic-form-control--compact" name="spreadsheet_document_id" required>
                    <?php foreach ($spreadsheetDocuments as $document): ?>
                        <option value="<?= (int)$document->getId() ?>"<?= (int)$document->getId() === $selectedSourceDocumentId ? ' selected' : '' ?>><?= omoApiEscape((string)$document->get('title')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="omo-stats-field generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.sheet')) ?></span>
                <input type="text" class="generic-form-control generic-form-control--compact" name="spreadsheet_sheet" value="<?= omoApiEscape((string)$indicator->get('spreadsheet_sheet')) ?>" placeholder="Feuille1">
            </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="spreadsheet_cell"<?= $isSpreadsheetCellSource ? '' : ' hidden' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.cell')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="spreadsheet_cell" value="<?= omoApiEscape((string)($indicator->get('spreadsheet_cell') ?: 'A1')) ?>" placeholder="A1" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="spreadsheet_table"<?= $isSpreadsheetCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.range')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="spreadsheet_range" value="<?= omoApiEscape((string)($indicator->get('spreadsheet_range') ?: 'A1:C100')) ?>" placeholder="A1:C100" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="spreadsheet_table"<?= $isSpreadsheetCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.date_column')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="spreadsheet_date_column" value="<?= omoApiEscape((string)($indicator->get('spreadsheet_date_column') ?: 'A')) ?>" placeholder="A" required>
                </label>
                <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="spreadsheet_table"<?= $isSpreadsheetCellSource ? ' hidden' : '' ?>>
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.value_columns')) ?></span>
                    <input type="text" class="generic-form-control generic-form-control--compact" name="spreadsheet_value_columns" value="<?= omoApiEscape((string)($indicator->get('spreadsheet_value_column') ?: 'B')) ?>" placeholder="B,C" required>
                </label>
            <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="spreadsheet_cell"<?= $isSpreadsheetCellSource ? '' : ' hidden' ?>>
                <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.frequency_measurement')) ?></span>
                <select class="generic-form-control generic-form-control--compact" name="spreadsheet_frequency">
                    <?php foreach (StatIndicator::getSpreadsheetFrequencyCatalog() as $frequency => $label): ?>
                        <option value="<?= omoApiEscape($frequency) ?>"<?= $frequency === StatIndicator::normalizeSpreadsheetFrequency($indicator->get('spreadsheet_frequency')) ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.frequency_' . $frequency)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="omo-stats-field generic-form-field" data-omo-stats-source-mode-field="spreadsheet_table"<?= $isSpreadsheetCellSource ? ' hidden' : '' ?>>
                <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.frequency_sync')) ?></span>
                <select class="generic-form-control generic-form-control--compact" name="spreadsheet_frequency">
                    <?php foreach (StatIndicator::getSpreadsheetFrequencyCatalog() as $frequency => $label): ?>
                        <option value="<?= omoApiEscape($frequency) ?>"<?= $frequency === StatIndicator::normalizeSpreadsheetFrequency($indicator->get('spreadsheet_frequency')) ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.import.spreadsheet.frequency_' . $frequency)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </section>
<section
    class="generic-form-grid omo-stats-schedule"
    data-omo-stats-source-panel="manual"
    <?= $isAutomaticSource ? ' hidden' : '' ?>
>

    <label class="generic-form-field">
        <span class="generic-form-label"><?= omoApiEscape($editT('editor.source_url')) ?></span>
        <input type="url" class="generic-form-control generic-form-control--compact" name="source_url" maxlength="<?= (int)$fieldLengths['source_url'] ?>" value="<?= omoApiEscape((string)$indicator->get('source_url')) ?>" placeholder="https://">
    </label>

        <div class="generic-form-field">
            <div class="generic-inline-help">
                <label class="generic-form-label" for="stats-editor-frequency"><?= omoApiEscape(omoStatsT('stats.form.frequency')) ?></label>
                <?= $editHelp(omoStatsT('stats.form.frequency'), omoStatsT('stats.form.schedule_help')) ?>
            </div>
            <select id="stats-editor-frequency" class="generic-form-control generic-form-control--compact" name="measurement_frequency" data-omo-stats-measurement-frequency>
                <?php foreach ($measurementFrequencyOptions as $option): ?>
                    <option value="<?= omoApiEscape((string)$option['value']) ?>"<?= (string)$option['value'] === (string)$measurementFrequency ? ' selected' : '' ?>><?= omoApiEscape((string)$option['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="omo-stats-field generic-form-field" data-omo-stats-measurement-schedule-field>
            <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.form.schedule')) ?></span>
            <select class="generic-form-control generic-form-control--compact" name="measurement_schedule" data-omo-stats-measurement-schedule data-selected-schedule="<?= omoApiEscape((string)$measurementSchedule) ?>"></select>
        </label>
</section>

    </section>
    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact" aria-labelledby="stats-editor-chart">
        <div class="generic-form-section__copy">
            <div class="generic-heading-with-help"><h3 id="stats-editor-chart" class="generic-card-title generic-card-title--small"><?= omoApiEscape($editT('editor.chart')) ?></h3><?= $editHelp($editT('editor.chart'), $editT('editor.chart_help')) ?></div>
        </div>
        <div class="generic-form-grid">
            <div class="generic-form-field">
                <div class="generic-inline-help">
                    <label class="generic-form-label" for="stats-editor-minimum"><?= omoApiEscape($editT('editor.minimum')) ?></label>
                    <?= $editHelp($editT('editor.minimum'), $editT('editor.minimum_help')) ?>
                </div>
                <input id="stats-editor-minimum" type="number" class="generic-form-control generic-form-control--compact" name="chart_min_value" step="any" value="<?= omoApiEscape(omoStatsEditInputNumber($indicator->get('chart_min_value'))) ?>">
            </div>
        <label class="generic-form-field">
            <span class="generic-form-label"><?= omoApiEscape($editT('editor.reference_type')) ?></span>
            <select class="generic-form-control generic-form-control--compact" name="reference_type" data-omo-stats-reference-type>
                <?php foreach (['none', 'ceiling', 'objective'] as $type): ?>
                    <option value="<?= $type ?>"<?= $referenceType === $type ? ' selected' : '' ?>><?= omoApiEscape(omoStatsT('stats.form.reference_' . $type)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
            <div class="generic-form-field" data-omo-stats-ceiling-editor hidden>
                <div class="generic-inline-help">
                    <label class="generic-form-label" for="stats-editor-ceiling"><?= omoApiEscape(omoStatsT('stats.form.ceiling_value')) ?></label>
                    <?= $editHelp(omoStatsT('stats.form.ceiling_value'), omoStatsT('stats.form.ceiling_help')) ?>
                </div>
                <input id="stats-editor-ceiling" type="number" class="generic-form-control generic-form-control--compact" name="ceiling_value" value="<?= omoApiEscape(omoStatsEditInputNumber($ceilingValue)) ?>" step="any" required data-omo-stats-ceiling-value>
            </div>
        </div>
        <div class="generic-title-row generic-title-row--center">
            <div class="generic-inline-help">
                <label class="generic-checkbox">
                    <input type="checkbox" name="show_cumulative" value="1"<?= $showCumulative ? ' checked' : '' ?>>
                    <span><?= omoApiEscape($editT('editor.cumulative')) ?></span>
                </label>
                <?= $editHelp($editT('editor.cumulative'), $editT('editor.cumulative_help')) ?>
            </div>
            <div class="generic-form-field" data-omo-stats-reference-scale<?= $showCumulative && $referenceType !== StatIndicator::REFERENCE_NONE ? '' : ' hidden' ?>>
                <div class="generic-title-row generic-title-row--center" role="radiogroup" aria-labelledby="stats-editor-scale-label">
                    <span id="stats-editor-scale-label" class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.form.reference_scale')) ?> :</span>
                    <?php foreach (['value', 'cumulative'] as $scale): ?>
                        <div class="generic-inline-help">
                            <label class="generic-checkbox">
                                <input type="radio" name="reference_scale" value="<?= $scale ?>"<?= $referenceScale === $scale ? ' checked' : '' ?>>
                                <span><?= omoApiEscape(omoStatsT('stats.form.reference_scale_' . $scale)) ?></span>
                            </label>
                            <?= $editHelp(omoStatsT('stats.form.reference_scale_' . $scale), $editT('editor.scale_' . $scale . '_help')) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
<div class="omo-stats-reference-editor generic-form-stack generic-form-stack--compact" data-omo-stats-reference-editor>
    <div class="omo-stats-reference-editor__heading generic-form-section__heading">
        <div class="generic-form-section__copy">
            <div class="generic-heading-with-help"><h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoStatsT('stats.form.reference_title')) ?></h3><?= $editHelp(omoStatsT('stats.form.reference_title'), omoStatsT('stats.form.reference_help')) ?></div>
        </div>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-add-reference-point><?= omoApiEscape(omoStatsT('stats.form.add_point')) ?></button>
    </div>
    <div class="omo-stats-reference-editor__rail" data-omo-stats-reference-rail></div>
    <div class="omo-stats-reference-editor__points" data-omo-stats-reference-points>
        <?php foreach ($referencePoints as $pointIndex => $point): ?>
            <?php
            $position = (float)$point->get('position_percent');
            $isEndpoint = abs($position) < 0.0001 || abs($position - 100.0) < 0.0001;
            $pointAt = $point->get('point_at');
            ?>
            <div class="omo-stats-reference-point generic-soft-panel" data-omo-stats-reference-point data-endpoint="<?= $isEndpoint ? '1' : '0' ?>">
                <div class="omo-stats-reference-point__badge"><?= omoApiEscape(omoStatsT($isEndpoint ? 'stats.form.endpoint' : 'stats.form.intermediate')) ?></div>
                <label class="omo-stats-field generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.form.position')) ?></span>
                    <input
                        type="number"
                        class="generic-form-control generic-form-control--compact"
                        name="reference_points[<?= (int)$pointIndex ?>][position_percent]"
                        value="<?= omoApiEscape(omoStatsEditInputNumber($position)) ?>"
                        min="0"
                        max="100"
                        step="0.2"
                        data-omo-stats-point-position
                        <?= $isEndpoint ? 'readonly' : '' ?>
                        required
                    >
                </label>
                <label class="omo-stats-field generic-form-field omo-stats-field--date">
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT($isEndpoint ? 'stats.form.point_date' : 'stats.form.point_date_auto')) ?></span>
                    <input
                        type="datetime-local"
                        class="generic-form-control generic-form-control--compact"
                        name="reference_points[<?= (int)$pointIndex ?>][point_at]"
                        value="<?= $pointAt instanceof DateTimeInterface ? omoApiEscape($pointAt->format('Y-m-d\TH:i')) : '' ?>"
                        data-omo-stats-point-date
                        <?= $isEndpoint ? 'required' : '' ?>
                        <?= $isEndpoint ? '' : 'readonly aria-readonly="true"' ?>
                    >
                </label>
                <label class="omo-stats-field generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoStatsT('stats.form.point_value')) ?></span>
                    <input
                        type="number"
                        class="generic-form-control generic-form-control--compact"
                        name="reference_points[<?= (int)$pointIndex ?>][value]"
                        value="<?= omoApiEscape(omoStatsEditInputNumber($point->get('value'))) ?>"
                        step="any"
                        data-omo-stats-point-value
                        required
                    >
                </label>
                <?php if (!$isEndpoint): ?>
                    <button type="button" class="generic-action-button generic-action-button--danger omo-stats-reference-point__remove" data-omo-stats-remove-reference-point><?= omoApiEscape(omoStatsT('stats.form.remove_point')) ?></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</section>
<input type="hidden" name="id" value="<?= (int)$indicatorId ?>">
<input type="hidden" name="stats_action" value="save_indicator">
<input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
<input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
<?php if (!empty($context['pvMeetingPermission'])): ?>
    <input type="hidden" name="pv_meeting_document_id" value="<?= (int)($context['pvMeetingPermission']['documentId'] ?? 0) ?>">
    <input type="hidden" name="pv_meeting_editor_token" value="<?= omoApiEscape((string)($_GET['pv_meeting_editor_token'] ?? '')) ?>">
<?php endif; ?>
<div class="generic-feedback generic-feedback--collapse-empty" data-omo-stats-editor-feedback role="status" aria-live="polite"></div>
<div class="omo-stats-editor__actions generic-form-actions generic-form-actions--stack-mobile">
    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-cancel-editor data-indicator-id="<?= (int)$indicatorId ?>"><?= omoApiEscape(omoStatsT('stats.action.cancel')) ?></button>
    <button type="submit" class="generic-action-button generic-action-button--main" data-omo-stats-save-editor><?= omoApiEscape(omoStatsT('stats.action.save')) ?></button>
</div>
</form>
</div>
<script src="/omo/api/stats/reference-editor.js?v=20260919-reference-scale"></script>
<script>
(function () {
    var editor = document.querySelector('[data-omo-stats-editor]');
    if (!editor || editor.dataset.omoStatsEditorReady === '1') {
        return;
    }
    editor.dataset.omoStatsEditorReady = '1';

    var sourceTypeField = editor.querySelector('[data-omo-stats-source-type]');
    var selectedSourceType = <?= json_encode($selectedSourceType, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var sourcePanels = Array.prototype.slice.call(editor.querySelectorAll('[data-omo-stats-source-panel]'));
    var sourceModeFields = Array.prototype.slice.call(editor.querySelectorAll('[data-omo-stats-source-mode-field]'));

    function setSourceFieldsDisabled(container, disabled) {
        Array.prototype.forEach.call(container.querySelectorAll('input, select, textarea'), function (field) {
            field.disabled = disabled;
        });
    }

    function syncSourceType() {
        var sourceType = sourceTypeField ? sourceTypeField.value : selectedSourceType;
        sourceType = sourceType || 'manual';

        sourcePanels.forEach(function (panel) {
            var panelTypes = String(panel.getAttribute('data-omo-stats-source-panel') || '').trim().split(/\s+/);
            var isActive = panelTypes.indexOf(sourceType) !== -1;
            panel.hidden = !isActive;
            setSourceFieldsDisabled(panel, !isActive);
        });

        sourceModeFields.forEach(function (field) {
            var isActive = String(field.getAttribute('data-omo-stats-source-mode-field') || '') === sourceType;
            field.hidden = !isActive;
            setSourceFieldsDisabled(field, !isActive);
        });

        if (sourceType === 'manual' && typeof syncMeasurementSchedule === 'function') {
            syncMeasurementSchedule(false);
        }
    }

    if (sourceTypeField) {
        sourceTypeField.addEventListener('change', syncSourceType);
    }

    var editorForm = editor.querySelector('form');
    var cancelEditorButton = editor.querySelector('[data-omo-stats-cancel-editor]');
    var saveEditorButton = editor.querySelector('[data-omo-stats-save-editor]');
    if (editorForm && window.omoStatsDrawer && typeof window.omoStatsDrawer.setHeader === 'function') {
        if (!editorForm.id) {
            editorForm.id = 'omoStatsIndicatorForm';
        }
        if (cancelEditorButton) {
            cancelEditorButton.setAttribute('form', editorForm.id);
        }
        if (saveEditorButton) {
            saveEditorButton.setAttribute('form', editorForm.id);
            saveEditorButton.type = 'button';
            saveEditorButton.addEventListener('click', function (event) {
                event.preventDefault();
                if (typeof editorForm.requestSubmit === 'function') {
                    editorForm.requestSubmit();
                    return;
                }

                editorForm.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            });
        }
        window.omoStatsDrawer.setHeader({
            title: <?= json_encode(omoStatsT($indicatorId > 0 ? 'stats.form.edit_title' : 'stats.form.create_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            description: <?= json_encode(omoStatsT('stats.form.intro'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            actions: [cancelEditorButton, saveEditorButton].filter(function (button) {
                return button instanceof HTMLElement;
            })
        });
    }


    var feedback = editor.querySelector('[data-omo-stats-editor-feedback]');
    var saving = false;
    var saveLabel = saveEditorButton.textContent;
    var saveError = <?= json_encode(omoStatsT('stats.error.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    editorForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (saving || !editorForm.reportValidity()) {
            return;
        }
        var formData = new FormData(editorForm);
        saving = true;
        editorForm.setAttribute('aria-busy', 'true');
        saveEditorButton.disabled = true;
        cancelEditorButton.disabled = true;
        saveEditorButton.textContent = <?= json_encode($editT('editor.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        try {
            var response = await fetch(editorForm.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            var result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || saveError);
            }
            editorForm.elements.namedItem('id').value = String(result.id);
            if (typeof window.omoStatsAfterIndicatorSave === 'function') {
                window.omoStatsAfterIndicatorSave();
            } else {
                feedback.classList.add('is-success');
                feedback.textContent = <?= json_encode($editT('editor.saved'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            }
        } catch (error) {
            feedback.textContent = error.message || saveError;
            feedback.scrollIntoView({block: 'nearest', behavior: 'smooth'});
        } finally {
            saving = false;
            editorForm.removeAttribute('aria-busy');
            saveEditorButton.disabled = false;
            cancelEditorButton.disabled = false;
            saveEditorButton.textContent = saveLabel;
        }
    });

    var typeField = editor.querySelector('[data-omo-stats-reference-type]');
    var showCumulativeField = editor.querySelector('input[type="checkbox"][name="show_cumulative"]');
    var referenceScaleRow = editor.querySelector('[data-omo-stats-reference-scale]');
    var measurementFrequencyField = editor.querySelector('[data-omo-stats-measurement-frequency]');
    var measurementScheduleField = editor.querySelector('[data-omo-stats-measurement-schedule]');
    var measurementScheduleWrapper = editor.querySelector('[data-omo-stats-measurement-schedule-field]');
    var labels = <?= json_encode([
        'endpoint' => omoStatsT('stats.form.endpoint'),
        'intermediate' => omoStatsT('stats.form.intermediate'),
        'position' => omoStatsT('stats.form.position'),
        'date' => omoStatsT('stats.form.point_date'),
        'dateAuto' => omoStatsT('stats.form.point_date_auto'),
        'value' => omoStatsT('stats.form.point_value'),
        'remove' => omoStatsT('stats.form.remove_point'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var measurementScheduleOptions = <?= json_encode($measurementScheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function syncReferenceScale() {
        if (!referenceScaleRow) {
            return;
        }
        var isVisible = Boolean(showCumulativeField && showCumulativeField.checked)
            && Boolean(typeField && typeField.value !== 'none');
        referenceScaleRow.hidden = !isVisible;
    }

    if (showCumulativeField) {
        showCumulativeField.addEventListener('change', syncReferenceScale);
    }
    if (typeField) {
        typeField.addEventListener('change', syncReferenceScale);
    }

    function syncMeasurementSchedule(resetSelection) {
        if (!measurementScheduleField || !measurementFrequencyField) {
            return;
        }
        var frequency = measurementFrequencyField.value;
        var options = measurementScheduleOptions[frequency] || [];
        var selectedValue = resetSelection ? '' : (measurementScheduleField.dataset.selectedSchedule || measurementScheduleField.value || '');
        measurementScheduleField.innerHTML = '';
        options.forEach(function (option) {
            var optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            optionElement.selected = option.value === selectedValue;
            measurementScheduleField.appendChild(optionElement);
        });
        if (measurementScheduleWrapper) {
            measurementScheduleWrapper.hidden = options.length === 0;
        }
        measurementScheduleField.disabled = options.length === 0;
        measurementScheduleField.dataset.selectedSchedule = '';
    }

    if (measurementFrequencyField) {
        measurementFrequencyField.addEventListener('change', function () {
            syncMeasurementSchedule(true);
        });
    }

    var useSharedReferenceEditor = typeof window.omoStatsInitReferenceEditor === 'function';
    if (useSharedReferenceEditor) {
        window.omoStatsInitReferenceEditor(editor, {
            labels: labels
        });
    }

    syncSourceType();
    syncReferenceScale();
})();
</script>
