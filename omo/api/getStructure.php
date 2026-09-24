<?php
require_once __DIR__ . '/bootstrap.php';

function omoGetStructurePanelSourceLang(): array
{
    return [
        'structure.actions.export' => [
            'text' => 'Export',
            'context' => 'Structure action menu item used to export the current structure.',
        ],
        'structure.actions.export.download' => [
            'text' => 'Télécharger',
            'context' => 'Button label used in the structure export modal to start a file download.',
        ],
        'structure.actions.export.format.csv' => [
            'text' => 'CSV',
            'context' => 'Label used for the CSV structure export format.',
        ],
        'structure.actions.export.format.csv_description' => [
            'text' => 'Vue à plat des holons. Les droits sont listés dans une cellule avec leur code et leur portée.',
            'context' => 'Description shown for the CSV structure export format.',
        ],
        'structure.actions.export.format.json' => [
            'text' => 'JSON',
            'context' => 'Label used for the JSON structure export format.',
        ],
        'structure.actions.export.format.json_description' => [
            'text' => 'Format complet pour réimporter la structure. Il inclut aussi les droits des holons et des modèles.',
            'context' => 'Description shown for the JSON structure export format.',
        ],
        'structure.actions.export.format.xml' => [
            'text' => 'XML',
            'context' => 'Label used for the XML structure export format.',
        ],
        'structure.actions.export.format.xml_description' => [
            'text' => 'Format structuré et lisible, avec les mêmes codes de droits que le JSON.',
            'context' => 'Description shown for the XML structure export format.',
        ],
        'structure.actions.export.modal_intro' => [
            'text' => 'Choisissez le format d’export de cette structure.',
            'context' => 'Intro text shown in the structure export modal.',
        ],
        'structure.actions.export.modal_title' => [
            'text' => 'Exporter la structure',
            'context' => 'Modal title shown when choosing a structure export format.',
        ],
        'structure.actions.menu_aria' => [
            'text' => 'Actions',
            'context' => 'Aria label for the structure action menu toggle button.',
        ],
        'structure.actions.print' => [
            'text' => 'Imprimer',
            'context' => 'Structure action menu item used to print the current structure.',
        ],
        'structure.actions.refresh' => [
            'text' => 'Rafraîchir',
            'context' => 'Structure action menu item that clears caches and reloads the current structure.',
        ],
        'structure.actions.share' => [
            'text' => 'Partager',
            'context' => 'Structure action menu item used to open the sharing dialog for the current structure.',
        ],
        'structure.browser.generic_name' => [
            'text' => 'ce navigateur',
            'context' => 'Fallback browser name used in structure warnings when the exact browser cannot be detected.',
        ],
        'structure.error.organization_access_denied' => [
            'text' => 'Accès refusé à cette organisation.',
            'context' => 'Error message shown when the structure view cannot access the current organization.',
        ],
        'structure.list.empty_search' => [
            'text' => 'Aucun nœud ne correspond à cette recherche.',
            'context' => 'Message shown in the structure list view when the current search returns no visible nodes.',
        ],
        'structure.list.properties.hide_aria' => [
            'text' => 'Masquer les propriétés',
            'context' => 'Aria label for the button that collapses role details in the structure list view.',
        ],
        'structure.list.properties.show_aria' => [
            'text' => 'Afficher les propriétés',
            'context' => 'Aria label for the button that expands role details in the structure list view.',
        ],
        'structure.list.search.placeholder' => [
            'text' => 'Filtre rapide',
            'context' => 'Placeholder shown above the structure list view search field.',
        ],
        'structure.member.focus_line' => [
            'text' => 'Focus : {focus}',
            'context' => 'Second line of the terminal member avatar tooltip when a member focus is defined.',
        ],
        'structure.member.unassigned' => [
            'text' => 'Non attribué',
            'context' => 'Label displayed inside a selected terminal structure element with no assigned people.',
        ],
        'structure.message.invalid' => [
            'text' => 'Structure invalide.',
            'context' => 'Fallback error message shown when the structure data payload is invalid.',
        ],
        'structure.message.load_error' => [
            'text' => 'Impossible de charger la structure.',
            'context' => 'Fallback error message shown when the structure view fails to load its data.',
        ],
        'structure.message.no_structure' => [
            'text' => 'Aucune structure disponible pour cette organisation.',
            'context' => 'Message shown when the current organization has no visible structure to display.',
        ],
        'structure.message.disabled' => [
            'text' => 'L’application Structure est désactivée pour cette organisation.',
            'context' => 'Message shown when the Structure app is disabled for the current organization.',
        ],
        'structure.placeholder.action' => [
            'text' => 'Ouvrir l’application Structure',
            'context' => 'Call to action shown in the main structure panel when the organization has no structure yet.',
        ],
        'structure.placeholder.text' => [
            'text' => 'Aucune structure n’est encore définie pour cette organisation. Ouvrez l’application Structure dans la barre latérale pour créer une structure vide, importer un export ou partir d’un modèle.',
            'context' => 'Informational text shown in the main structure panel when the organization has no structure yet.',
        ],
        'structure.placeholder.title' => [
            'text' => 'Aucune structure',
            'context' => 'Title shown in the main structure panel when the organization has no structure yet.',
        ],
        'structure.share.modal_title' => [
            'text' => 'Partager la structure',
            'context' => 'Modal title used when opening the share dialog from the structure action menu.',
        ],
        'structure.view.toggle_label' => [
            'text' => 'O   L',
            'context' => 'Short toggle label used to switch between organization graph view and list view in the structure panel.',
        ],
        'structure.warning.brave' => [
            'text' => 'Brave semble bloquer la lecture du canevas utilisée pour la navigation graphique, probablement à cause du bouclier anti-empreinte numérique. La vue en liste a été activée pour continuer à naviguer. Vous pouvez aussi assouplir le bouclier pour ce site.',
            'context' => 'Warning shown in the structure panel when Brave blocks canvas pixel reading.',
        ],
        'structure.warning.dismiss_aria' => [
            'text' => 'Réduire ce message',
            'context' => 'Aria label for the button that collapses the structure browser warning.',
        ],
        'structure.warning.pixel_mismatch' => [
            'text' => '{browserName} bloque ou altère la lecture du canevas utilisée pour la navigation graphique. La vue en liste a été activée pour continuer à naviguer.',
            'context' => 'Warning shown in the structure panel when the browser alters canvas pixel reading.',
        ],
        'structure.warning.restore' => [
            'text' => 'Info navigateur',
            'context' => 'Button label used to reopen the collapsed browser warning in the structure panel.',
        ],
        'structure.warning.unavailable' => [
            'text' => 'La lecture du canevas utilisée pour la navigation graphique n’est pas disponible dans {browserName}. La vue en liste a été activée pour continuer à naviguer.',
            'context' => 'Warning shown in the structure panel when canvas pixel reading is unavailable in the current browser.',
        ],
    ];
}

function omoRenderStructureEmptyPlaceholder(array $lang, array $sourceLang): void
{
    ?>
<div class="omo-structure-empty-panel">
    <div class="generic-section omo-structure-empty-panel__card">
        <div class="generic-title generic-title--card omo-structure-empty-panel__title"><?= omoApiEscape(t('structure.placeholder.title', [], $lang, $sourceLang)) ?></div>
        <p class="generic-description omo-structure-empty-panel__text"><?= omoApiEscape(t('structure.placeholder.text', [], $lang, $sourceLang)) ?></p>
        <button type="button" class="generic-action-button generic-action-button--main" data-omo-open-structure-drawer="1"><?= omoApiEscape(t('structure.placeholder.action', [], $lang, $sourceLang)) ?></button>
    </div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/getStructure-empty.css') ?>">

<script>
$(document)
  .off('click.omoStructureEmptyDrawer', '[data-omo-open-structure-drawer="1"]')
  .on('click.omoStructureEmptyDrawer', '[data-omo-open-structure-drawer="1"]', function () {
    if (typeof window.omoOpenDrawerHashState !== 'function') {
        return;
    }

    window.omoOpenDrawerHashState('structure');
  });
</script>
    <?php
}

function omoRenderStructureDisabledPlaceholder(array $lang, array $sourceLang): void
{
    ?>
<div class="omo-structure-empty-panel">
    <div class="generic-section omo-structure-empty-panel__card">
        <div class="generic-title generic-title--card omo-structure-empty-panel__title"><?= omoApiEscape(t('structure.actions.menu_aria', [], $lang, $sourceLang)) ?></div>
        <p class="generic-description omo-structure-empty-panel__text"><?= omoApiEscape(t('structure.message.disabled', [], $lang, $sourceLang)) ?></p>
    </div>
</div>
    <?php
}

$sourceLang = omoGetStructurePanelSourceLang();
$lang = translationBundleInit('omo_get_structure_panel', omoGetTranslationLocale(), $sourceLang);

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$drawerMode = isset($_GET['drawer']) && (string)$_GET['drawer'] === '1';
if ($organizationId > 0) {
    $organization = new \dbObject\Organization();
    $organizationLoaded = $organization->load($organizationId);
    if ($organizationLoaded && !$organization->canViewDetail()) {
        http_response_code(403);
        echo '<div class="error">' . omoApiEscape(t('structure.error.organization_access_denied')) . '</div>';
        exit;
    }

    if ($organizationLoaded && !$organization->isStructureApplicationEnabled()) {
        http_response_code(404);
        omoRenderStructureDisabledPlaceholder($lang, $sourceLang);
        exit;
    }

    if ($organizationLoaded && $organization->getEnabledStructuralRootHolon() === null) {
        if ($drawerMode) {
            require_once __DIR__ . '/organization_setup_panel.php';
            omoRenderOrganizationSetupPanel($organization);
        } else {
            omoRenderStructureEmptyPlaceholder($lang, $sourceLang);
        }
        exit;
    }
}

$structureDataParams = array();
if ($organizationId > 0) {
    $structureDataParams['oid'] = $organizationId;
}
$initialCid = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

$structureDataUrl = 'api/getStructureData.php';
if (count($structureDataParams) > 0) {
    $structureDataUrl .= '?' . http_build_query($structureDataParams);
}

$isShareMode = function_exists('commonGetCurrentShareToken') && commonGetCurrentShareToken() !== '';
$canCreateShareLink = !$isShareMode && (int)commonGetCurrentUserId() > 0 && commonCurrentUserHasOrganizationAccess($organizationId);
$canExportStructure = !$isShareMode && (int)commonGetCurrentUserId() > 0 && commonCurrentUserHasOrganizationAccess($organizationId);
$structureTranslations = [
    'actionsMenuAria' => t('structure.actions.menu_aria'),
    'actionsExport' => t('structure.actions.export'),
    'exportDownloadLabel' => t('structure.actions.export.download'),
    'exportFormatCsvLabel' => t('structure.actions.export.format.csv'),
    'exportFormatCsvDescription' => t('structure.actions.export.format.csv_description'),
    'exportFormatJsonLabel' => t('structure.actions.export.format.json'),
    'exportFormatJsonDescription' => t('structure.actions.export.format.json_description'),
    'exportFormatXmlLabel' => t('structure.actions.export.format.xml'),
    'exportFormatXmlDescription' => t('structure.actions.export.format.xml_description'),
    'exportModalIntro' => t('structure.actions.export.modal_intro'),
    'exportModalTitle' => t('structure.actions.export.modal_title'),
    'actionsPrint' => t('structure.actions.print'),
    'actionsRefresh' => t('structure.actions.refresh'),
    'actionsShare' => t('structure.actions.share'),
    'browserGenericName' => t('structure.browser.generic_name'),
    'emptySearch' => t('structure.list.empty_search'),
    'memberFocusLine' => t('structure.member.focus_line'),
    'memberUnassigned' => t('structure.member.unassigned'),
    'hidePropertiesAria' => t('structure.list.properties.hide_aria'),
    'invalidStructure' => t('structure.message.invalid'),
    'loadError' => t('structure.message.load_error'),
    'noStructure' => t('structure.message.no_structure'),
    'searchPlaceholder' => t('structure.list.search.placeholder'),
    'shareModalTitle' => t('structure.share.modal_title'),
    'showPropertiesAria' => t('structure.list.properties.show_aria'),
    'toggleLabel' => t('structure.view.toggle_label'),
    'warningBrave' => t('structure.warning.brave'),
    'warningDismissAria' => t('structure.warning.dismiss_aria'),
    'warningPixelMismatch' => t('structure.warning.pixel_mismatch'),
    'warningRestore' => t('structure.warning.restore'),
    'warningUnavailable' => t('structure.warning.unavailable'),
];
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/getStructure.css') ?>">
    <div id="contentright" class="contentright">
        <div id="chart"></div>
        <div id="role_list" class="filter_zone"></div>
        <div id="omoStructureCanvasWarning" class="structure-browser-warning" hidden>
            <div class="structure-browser-warning__content">
                <div id="omoStructureCanvasWarningMessage" class="structure-browser-warning__message"></div>
                <button type="button" id="omoStructureCanvasWarningDismiss" class="structure-browser-warning__dismiss" aria-label="<?= omoApiEscape(t('structure.warning.dismiss_aria')) ?>">x</button>
            </div>
            <button type="button" id="omoStructureCanvasWarningRestore" class="structure-browser-warning__restore"><?= omoApiEscape(t('structure.warning.restore')) ?></button>
        </div>
        <div class="structure-actions" id="omoStructureActions">
            <button type="button" class="structure-actions__toggle" id="omoStructureActionsToggle" aria-label="<?= omoApiEscape(t('structure.actions.menu_aria')) ?>">...</button>
            <div class="structure-actions__panel" id="omoStructureActionsPanel">
                <?php if ($canExportStructure) { ?>
                    <button type="button" class="structure-actions__item" data-omo-structure-action="export"><?= omoApiEscape(t('structure.actions.export')) ?></button>
                <?php } ?>
                <button type="button" class="structure-actions__item" data-omo-structure-action="refresh"><?= omoApiEscape(t('structure.actions.refresh')) ?></button>
                <?php if ($canCreateShareLink) { ?>
                    <button type="button" class="structure-actions__item" data-omo-structure-action="share"><?= omoApiEscape(t('structure.actions.share')) ?></button>
                <?php } ?>
                <button type="button" class="structure-actions__item" data-omo-structure-action="print"><?= omoApiEscape(t('structure.actions.print')) ?></button>
            </div>
        </div>

        <div class="switch chart-toggle">
            <input type="checkbox" id="toggleSwitch" />
            <label for="toggleSwitch" class="slider"><?= omoApiEscape(t('structure.view.toggle_label')) ?></label>
        </div>
    </div>

  <?= commonPageScriptTags('/omo/api/getStructure.js', [
    'structureTranslations' => $structureTranslations,
    'structureDataUrl' => $structureDataUrl,
    'requestedCid' => (int)$initialCid,
    'canCreateShareLink' => ($canCreateShareLink),
    'canExportStructure' => ($canExportStructure),
]) ?>
