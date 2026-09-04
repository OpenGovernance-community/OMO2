<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;
use dbObject\Organization;

$sourceLang = [
    'documents.scope.toggle_aria' => [
        'text' => 'Portée des documents',
        'context' => 'Accessible label for the document scope toggle.',
    ],
    'documents.scope.contextual' => [
        'text' => 'Local',
        'context' => 'Label used to show only documents from the current holon.',
    ],
    'documents.scope.children' => [
        'text' => 'Enfants directs',
        'context' => 'Label used to show documents from the current holon and its direct children.',
    ],
    'documents.scope.descendants' => [
        'text' => 'Descendants',
        'context' => 'Label used to show documents from the current holon and its descendants.',
    ],
    'documents.scope.view' => [
        'text' => 'Voir',
        'context' => 'Short label used before the document visibility scope in tooltips.',
    ],
    'documents.scope.edit' => [
        'text' => 'Éditer',
        'context' => 'Short label used before the document edit scope in tooltips.',
    ],
    'documents.empty.visible_children' => [
        'one' => 'Aucun document visible pour ce contexte ou ses enfants directs. {count} fichier est caché.',
        'other' => 'Aucun document visible pour ce contexte ou ses enfants directs. {count} fichiers sont cachés.',
        'context' => 'Empty state shown when hidden documents exist in direct child scope.',
    ],
    'documents.empty.visible_contextual' => [
        'one' => 'Aucun document visible pour ce contexte. {count} fichier est caché.',
        'other' => 'Aucun document visible pour ce contexte. {count} fichiers sont cachés.',
        'context' => 'Empty state shown when hidden documents exist in contextual scope.',
    ],
    'documents.empty.visible_descendants' => [
        'one' => 'Aucun document visible pour ce contexte et ses descendants. {count} fichier est caché.',
        'other' => 'Aucun document visible pour ce contexte et ses descendants. {count} fichiers sont cachés.',
        'context' => 'Empty state shown when hidden documents exist in descendant scope.',
    ],
    'documents.empty.available_children' => [
        'text' => 'Aucun document disponible pour ce contexte ou ses enfants directs.',
        'context' => 'Empty state shown when no document exists in direct child scope.',
    ],
    'documents.empty.available_contextual' => [
        'text' => 'Aucun document disponible pour ce contexte.',
        'context' => 'Empty state shown when no document exists in contextual scope.',
    ],
    'documents.empty.available_descendants' => [
        'text' => 'Aucun document disponible pour ce contexte et ses descendants.',
        'context' => 'Empty state shown when no document exists in descendant scope.',
    ],
    'documents.page.title' => [
        'text' => 'Documents',
        'context' => 'Main title of the documents application.',
    ],
    'documents.action.new' => [
        'text' => 'Nouveau',
        'context' => 'Primary action used to create a new document.',
    ],
    'documents.controls.sort.aria' => [
        'text' => 'Tri des documents',
        'context' => 'Accessible label for the documents sort control.',
    ],
    'documents.controls.sort.date' => [
        'text' => 'Date',
        'context' => 'Short sort label shown before the sort control is rebuilt in JavaScript.',
    ],
    'documents.controls.sort.alpha' => [
        'text' => 'Alphabétique',
        'context' => 'Short alphabetical sort label shown before the sort control is rebuilt in JavaScript.',
    ],
    'documents.controls.density.aria' => [
        'text' => 'Densité d’affichage des documents',
        'context' => 'Accessible label for the documents density control.',
    ],
    'documents.controls.density.detail' => [
        'text' => 'Détail',
        'context' => 'Label used for detailed document density.',
    ],
    'documents.controls.density.compact' => [
        'text' => 'Compact',
        'context' => 'Label used for compact document density.',
    ],
    'documents.filters.aria' => [
        'text' => 'Filtres des documents',
        'context' => 'Accessible label for the compact documents filters control.',
    ],
    'documents.filters.scope' => [
        'text' => 'Contexte',
        'context' => 'Heading for document scope choices in the filters panel.',
    ],
    'documents.filters.sort' => [
        'text' => 'Tri',
        'context' => 'Heading for document sorting choices in the filters panel.',
    ],
    'documents.filters.density' => [
        'text' => 'Représentation',
        'context' => 'Heading for document representation choices in the filters panel.',
    ],
    'documents.filters.apply' => [
        'text' => 'Appliquer',
        'context' => 'Button applying temporary document filter choices.',
    ],
    'documents.filters.save_view' => [
        'text' => 'Enregistrer la vue',
        'context' => 'Button saving document filter choices for the current context.',
    ],
    'documents.filters.more_actions' => [
        'text' => 'Autres options de vue',
        'context' => 'Accessible label for additional document view preference actions.',
    ],
    'documents.filters.apply_everywhere' => [
        'text' => 'Appliquer partout',
        'context' => 'Action setting the current document view as the default and clearing specific views.',
    ],
    'documents.filters.set_default' => [
        'text' => 'Définir comme vue par défaut',
        'context' => 'Action saving the current document view as the default view.',
    ],
    'documents.filters.restore_default' => [
        'text' => 'Restaurer la vue par défaut',
        'context' => 'Action removing the current holon specific document view.',
    ],
    'documents.search.aria' => [
        'text' => 'Filtrer les documents affichés',
        'context' => 'Accessible label for the document quick search.',
    ],
    'documents.search.placeholder' => [
        'text' => 'Filtrer les documents',
        'context' => 'Placeholder for the document quick search.',
    ],
    'documents.search.empty' => [
        'text' => 'Aucun document ne correspond à cette recherche.',
        'context' => 'Empty state when the document quick search has no result.',
    ],
    'documents.drawer.detail_title' => [
        'text' => 'Détail du document',
        'context' => 'Title shown in the document detail drawer.',
    ],
    'documents.drawer.detail_description' => [
        'text' => 'Lecture du document dans OMO.',
        'context' => 'Description shown in the document detail drawer.',
    ],
    'documents.drawer.editor_title' => [
        'text' => 'Nouveau document',
        'context' => 'Title shown in the document editor drawer.',
    ],
    'documents.drawer.editor_description' => [
        'text' => 'Création d’un document dans le contexte courant.',
        'context' => 'Description shown in the document editor drawer.',
    ],
    'documents.drawer.close' => [
        'text' => 'Fermer',
        'context' => 'Button label used to close document drawers.',
    ],
    'documents.upload_missing.badge' => [
        'text' => 'Fichier absent',
        'context' => 'Warning badge shown on an uploaded document without a stored file.',
    ],
    'documents.icon.etherpad' => [
        'text' => 'Pad coopératif',
        'context' => 'Alternative text for the Etherpad document type icon.',
    ],
    'documents.icon.ethercalc' => [
        'text' => 'Tableur collaboratif',
        'context' => 'Alternative text for the EtherCalc document type icon.',
    ],
    'documents.icon.whiteboard' => [
        'text' => 'Tableau blanc collaboratif',
        'context' => 'Alternative text for the SpaceDeck whiteboard document type icon.',
    ],
    'documents.icon.image' => [
        'text' => 'Image',
        'context' => 'Alternative text for image file icons.',
    ],
    'documents.icon.video' => [
        'text' => 'Vidéo',
        'context' => 'Alternative text for video file icons.',
    ],
    'documents.icon.audio' => [
        'text' => 'Audio',
        'context' => 'Alternative text for audio file icons.',
    ],
    'documents.icon.text' => [
        'text' => 'Document texte',
        'context' => 'Alternative text for text document icons.',
    ],
    'documents.icon.spreadsheet' => [
        'text' => 'Tableur',
        'context' => 'Alternative text for spreadsheet file icons.',
    ],
    'documents.icon.presentation' => [
        'text' => 'Présentation',
        'context' => 'Alternative text for presentation file icons.',
    ],
    'documents.icon.drawing' => [
        'text' => 'Dessin',
        'context' => 'Alternative text for drawing file icons.',
    ],
    'documents.action.loading' => [
        'text' => 'Chargement…',
        'context' => 'Loading state shown while a document drawer is loading.',
    ],
    'documents.menu.archive' => [
        'text' => 'Archiver',
        'context' => 'Menu action used to hide a document from the document list.',
    ],
    'documents.menu.export_pdf' => [
        'text' => 'Exporter en PDF',
        'context' => 'Menu action used to download a PV document as a PDF file.',
    ],
    'documents.menu.delete' => [
        'text' => 'Supprimer',
        'context' => 'Menu action used to permanently delete an unreferenced document.',
    ],
    'documents.menu.confirm_archive' => [
        'text' => 'Archiver ce document ? Il ne sera plus visible dans la liste.',
        'context' => 'Confirmation shown before archiving a document.',
    ],
    'documents.menu.confirm_delete' => [
        'text' => 'Supprimer définitivement ce document ?',
        'context' => 'Confirmation shown before permanently deleting a document.',
    ],
    'documents.menu.action_error' => [
        'text' => 'Action impossible.',
        'context' => 'Fallback error shown when a document lifecycle action fails.',
    ],
    'documents.selection.toggle' => [
        'text' => 'Sélectionner ce document',
        'context' => 'Accessible label for the checkbox selecting a document for bulk actions.',
    ],
    'documents.selection.count' => [
        'text' => '{count} sélectionnés',
        'context' => 'Number of documents selected for bulk actions.',
    ],
    'documents.selection.archive' => [
        'text' => 'Archiver la sélection',
        'context' => 'Bulk action archiving the selected documents.',
    ],
    'documents.selection.delete' => [
        'text' => 'Supprimer la sélection',
        'context' => 'Bulk action deleting the selected documents.',
    ],
    'documents.selection.move' => [
        'text' => 'Déplacer la sélection',
        'context' => 'Bulk action moving the selected documents.',
    ],
    'documents.selection.confirm_archive' => [
        'text' => 'Archiver les {count} documents sélectionnés ? Ils ne seront plus visibles dans la liste.',
        'context' => 'Confirmation shown before bulk archiving documents.',
    ],
    'documents.selection.confirm_delete' => [
        'text' => 'Supprimer définitivement les {count} documents sélectionnés ?',
        'context' => 'Confirmation shown before bulk deleting documents.',
    ],
    'documents.error.load_document' => [
        'text' => 'Impossible de charger ce document.',
        'context' => 'Error shown when a document drawer cannot load its detail view.',
    ],
    'documents.error.load_editor' => [
        'text' => 'Impossible de charger l’éditeur du document.',
        'context' => 'Error shown when the document editor drawer cannot load.',
    ],
    'documents.sort.updated_aria' => [
        'text' => 'Date de modification',
        'context' => 'Accessible label for the updated date sort button.',
    ],
    'documents.sort.updated' => [
        'text' => 'Modification',
        'context' => 'Label for the updated date sort button.',
    ],
    'documents.sort.created_aria' => [
        'text' => 'Date de création',
        'context' => 'Accessible label for the creation date sort button.',
    ],
    'documents.sort.created' => [
        'text' => 'Création',
        'context' => 'Label for the creation date sort button.',
    ],
    'documents.sort.alpha_aria' => [
        'text' => 'Alphabétique',
        'context' => 'Accessible label for the alphabetical sort button.',
    ],
    'documents.date_column.created' => [
        'text' => 'Créé le',
        'context' => 'Compact column label used when sorting by creation date.',
    ],
    'documents.date_column.updated' => [
        'text' => 'Modifié le',
        'context' => 'Compact column label used when sorting by updated date.',
    ],
    'documents.group.today' => [
        'text' => "Aujourd'hui",
        'context' => 'Relative date group title for documents updated today.',
    ],
    'documents.group.yesterday' => [
        'text' => 'Hier',
        'context' => 'Relative date group title for documents updated yesterday.',
    ],
    'documents.group.this_week' => [
        'text' => 'Cette semaine',
        'context' => 'Relative date group title for documents updated earlier this week.',
    ],
    'documents.group.last_week' => [
        'text' => 'Semaine dernière',
        'context' => 'Relative date group title for documents updated last week.',
    ],
    'documents.group.this_month' => [
        'text' => 'Ce mois',
        'context' => 'Relative date group title for documents updated earlier this month.',
    ],
    'documents.group.last_month' => [
        'text' => 'Mois dernier',
        'context' => 'Relative date group title for documents updated last month.',
    ],
    'documents.group.this_year' => [
        'text' => 'Cette année',
        'context' => 'Relative date group title for documents updated earlier this year.',
    ],
    'documents.group.earlier' => [
        'text' => 'Plus ancien',
        'context' => 'Relative date group title for older documents.',
    ],
    'documents.group.too_far' => [
        'text' => 'Date inconnue',
        'context' => 'Fallback relative date group title for documents with missing or invalid dates.',
    ],
];

$lang = omoLoadTranslationBundle('omo_documents_index', $sourceLang);

function omoDocumentsScopeT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$currentOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$initialOpenDocumentId = isset($_GET['open_document_id']) ? (int)$_GET['open_document_id'] : 0;
$initialOpenDocumentMode = trim((string)($_GET['open_document_mode'] ?? ''));
$initialOpenDocumentMode = $initialOpenDocumentMode === 'edit'
    ? 'edit'
    : 'detail';
$requestedDocumentScope = $_GET['document_scope'] ?? 'contextual';

$organization = new Organization();
if ($currentOrganizationId > 0) {
    $organization->load($currentOrganizationId);
}

$rootHolon = $organization->getId() > 0 ? $organization->getEnabledStructuralRootHolon() : null;
$currentContextHolon = null;
if ($currentHolonId > 0) {
    $candidateHolon = new Holon();
    if (
        $candidateHolon->load($currentHolonId)
        && $organization->containsHolon($candidateHolon)
        && $candidateHolon->canViewDetail()
    ) {
        $currentContextHolon = $candidateHolon;
    }
}

if (!($currentContextHolon instanceof Holon) && $rootHolon instanceof Holon) {
    $currentContextHolon = $rootHolon;
    $currentHolonId = (int)$rootHolon->getId();
}

$effectiveCurrentHolonId = $currentContextHolon instanceof Holon ? (int)$currentContextHolon->getId() : 0;
$canToggleDocumentScope = $organization->getId() > 0 && $rootHolon instanceof Holon;
$availableDocumentScopes = omoApiGetAvailableContextScopes($canToggleDocumentScope, $currentContextHolon, $rootHolon);
$documentScope = omoApiNormalizeContextScope($requestedDocumentScope, $availableDocumentScopes);
$documentScopeActiveIndex = omoApiResolveContextScopeIndex($documentScope, $availableDocumentScopes);
$scopeHolonIds = $documentScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentContextHolon)
    : omoApiGetDescendantHolonIds($currentContextHolon);
$currentUserId = (int)commonGetCurrentUserId();
$applicationViewPreferences = omoApplicationViewPreferencesGetContext(
    'documents',
    $organization,
    $currentContextHolon instanceof Holon ? $currentContextHolon : null,
    $currentUserId
);
$documentScope = omoApiNormalizeContextScope(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'document_scope', 'scope', 'contextual'),
    $availableDocumentScopes
);
$documentScopeActiveIndex = omoApiResolveContextScopeIndex($documentScope, $availableDocumentScopes);
$scopeHolonIds = $documentScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentContextHolon)
    : omoApiGetDescendantHolonIds($currentContextHolon);
$canCreateDocument = $organization->getId() > 0
    && Document::canCreateInOrganizationContext(
        $currentOrganizationId,
        $effectiveCurrentHolonId > 0 ? $effectiveCurrentHolonId : null,
        $currentUserId,
        0,
        true
    );
$newDocumentUrl = '/omo/api/documents/create.php?oid=' . $currentOrganizationId . ($effectiveCurrentHolonId > 0 ? '&cid=' . $effectiveCurrentHolonId : '');

$documents = new \dbObject\ArrayDocument();
$documentVisibilityRuleMap = array();
$documentEditVisibilityRuleMap = array();
$documentListMetadata = array(
    'activityByDocumentId' => array(),
    'documentsWithChildren' => array(),
);
$pvEventsById = array();
$documentViewerContext = ObjectVisibility::buildCurrentViewerContext($currentOrganizationId, $currentUserId);
$visibleDocumentsCount = 0;
$totalDocumentsCount = 0;
$hiddenDocumentsCount = 0;
if ($currentOrganizationId > 0) {
    $documentVisibilityRuleMap = $documents->loadVisibleForOrganizationContext(
        $currentOrganizationId,
        $effectiveCurrentHolonId,
        $documentScope,
        $scopeHolonIds
    );
    $visibilityStats = $documents->getLastVisibilityStats();
    $visibleDocumentsCount = max(0, (int)($visibilityStats['visible'] ?? 0));
    $totalDocumentsCount = max($visibleDocumentsCount, (int)($visibilityStats['loaded'] ?? 0));
    $hiddenDocumentsCount = max(0, (int)($visibilityStats['hidden'] ?? 0));

    $documentIds = array();
    foreach ($documents as $documentItem) {
        if ($documentItem instanceof \dbObject\Document && (int)$documentItem->getId() > 0) {
            $documentIds[] = (int)$documentItem->getId();
        }
    }

    if (count($documentIds) > 0) {
        $documentEditVisibilityRuleMap = ObjectVisibility::loadActiveRuleRows(
            Document::getEditVisibilityObjectType(),
            $documentIds,
            $currentOrganizationId
        );

        $documentListMetadata = \dbObject\ArrayDocument::loadListMetadataForOrganization($currentOrganizationId);

        $pvEventIds = array();
        foreach ($documents as $documentItem) {
            if (!($documentItem instanceof \dbObject\Document) || !$documentItem->isPvDocument()) {
                continue;
            }

            $eventId = (int)$documentItem->get('IDevent');
            if ($eventId > 0) {
                $pvEventIds[$eventId] = $eventId;
            }
        }

        if (count($pvEventIds) > 0) {
            $pvEvents = new \dbObject\ArrayEvent();
            $pvEvents->load(array(
                'where' => array(
                    array('field' => 'id', 'op' => 'in', 'value' => array_values($pvEventIds)),
                ),
                'hydrate' => true,
            ));
            foreach ($pvEvents as $pvEvent) {
                if ($pvEvent instanceof \dbObject\Event && (int)$pvEvent->getId() > 0) {
                    $pvEventsById[(int)$pvEvent->getId()] = $pvEvent;
                }
            }
        }
    }
}

$today = new DateTimeImmutable('today');
$groups = sharedGetRelativeDateGroups($today, [
    'today' => omoDocumentsScopeT('documents.group.today'),
    'yesterday' => omoDocumentsScopeT('documents.group.yesterday'),
    'this_week' => omoDocumentsScopeT('documents.group.this_week'),
    'last_week' => omoDocumentsScopeT('documents.group.last_week'),
    'this_month' => omoDocumentsScopeT('documents.group.this_month'),
    'last_month' => omoDocumentsScopeT('documents.group.last_month'),
    'this_year' => omoDocumentsScopeT('documents.group.this_year'),
    'earlier' => omoDocumentsScopeT('documents.group.earlier'),
    'too_far' => omoDocumentsScopeT('documents.group.too_far'),
]);
$groupLayers = array();
$groupCount = count($groups);

foreach ($groups as $groupIndex => $groupDefinition) {
    $groupKey = (string)($groupDefinition['key'] ?? '');
    if ($groupKey === '') {
        continue;
    }

    $layerBase = max(0, ($groupCount - $groupIndex) * 10);
    $groupLayers[$groupKey] = array(
        'title' => $layerBase + 3,
        'list' => $layerBase + 2,
        'folder' => $layerBase + 1,
    );
}

$escape = 'omoApiEscape';
$normalizeSortValue = 'omoApiSortKey';

$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;

if ($formatter instanceof IntlDateFormatter) {
    $formatter->setPattern('d MMM');
}

$formatterWithYear = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;

if ($formatterWithYear instanceof IntlDateFormatter) {
    $formatterWithYear->setPattern('d MMM y');
}

$formatDate = static function ($value, bool $includeYear = false) use ($formatter, $formatterWithYear): string {
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    $selectedFormatter = $includeYear ? $formatterWithYear : $formatter;

    if ($selectedFormatter instanceof IntlDateFormatter) {
        $formatted = $selectedFormatter->format($value);

        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format($includeYear ? 'd.m.Y' : 'd.m');
};

$documentVisibilityIconMap = array(
    ObjectVisibility::TYPE_EVERYONE => '/omo/assets/images/documents/visibility/everyone.png',
    ObjectVisibility::TYPE_ORGANIZATION => '/omo/assets/images/documents/visibility/organization.png',
    ObjectVisibility::TYPE_CIRCLE => '/omo/assets/images/documents/visibility/circle.png',
    ObjectVisibility::TYPE_ROLE => '/omo/assets/images/documents/visibility/role.png',
    ObjectVisibility::TYPE_SELF => '/omo/assets/images/documents/visibility/me.png',
);

$resolveDocumentVisibilityIconUrl = static function (string $visibilityType) use ($documentVisibilityIconMap): string {
    $normalizedVisibilityType = ObjectVisibility::normalizeVisibilityType($visibilityType);

    return (string)($documentVisibilityIconMap[$normalizedVisibilityType] ?? $documentVisibilityIconMap[ObjectVisibility::TYPE_ORGANIZATION]);
};

if ($hiddenDocumentsCount > 0) {
    if ($documentScope === 'children') {
        $documentsEmptyMessage = omoDocumentsScopeT('documents.empty.visible_children', ['count' => (string)$hiddenDocumentsCount]);
    } elseif ($documentScope === 'descendants') {
        $documentsEmptyMessage = omoDocumentsScopeT('documents.empty.visible_descendants', ['count' => (string)$hiddenDocumentsCount]);
    } else {
        $documentsEmptyMessage = omoDocumentsScopeT('documents.empty.visible_contextual', ['count' => (string)$hiddenDocumentsCount]);
    }
} elseif ($documentScope === 'children') {
    $documentsEmptyMessage = omoDocumentsScopeT('documents.empty.available_children');
} elseif ($documentScope === 'descendants') {
    $documentsEmptyMessage = omoDocumentsScopeT('documents.empty.available_descendants');
} else {
    $documentsEmptyMessage = omoDocumentsScopeT('documents.empty.available_contextual');
}

$documentEntries = [];

foreach ($documents as $document) {
    $createdAt = $document->get('datecreation');
    $documentId = (int)$document->getId();
    $activityMetadata = $documentListMetadata['activityByDocumentId'][$documentId] ?? array();
    $updatedAt = $activityMetadata['date'] ?? $document->get('datemodification');
    $resolvedCreatedAt = $createdAt instanceof DateTimeInterface
        ? $createdAt
        : ($updatedAt instanceof DateTimeInterface ? $updatedAt : null);
    $resolvedUpdatedAt = $updatedAt instanceof DateTimeInterface
        ? $updatedAt
        : $resolvedCreatedAt;
    $documentOrganizationId = (int)$document->get('IDorganization');
    $documentHolonId = (int)$document->get('IDholon');
    $parentDocumentId = (int)$document->get('IDdocument_parent');
    $visibility = $document->getVisibilityDisplayData(
        $currentOrganizationId,
        $documentVisibilityRuleMap[$documentId] ?? null
    );
    $editVisibility = $document->getEditVisibilityDisplayData(
        $currentOrganizationId,
        $documentEditVisibilityRuleMap[$documentId] ?? null
    );
    $visibilityRule = $documentVisibilityRuleMap[$documentId] ?? null;
    $editVisibilityRule = $documentEditVisibilityRuleMap[$documentId] ?? null;
    $canOpenPvEditor = $document->canUserOpenPvEditor($currentUserId, $currentOrganizationId);
    $canManageDocument = $document->canManageInOrganizationContextWithVisibilityRule(
        $documentOrganizationId,
        $currentUserId,
        $visibilityRule,
        $documentViewerContext,
        true
    );
    $canMoveDocument = ($document->isPvDocument() && $document->canUserManagePvDocument($currentUserId))
        || $canManageDocument;
    $canManageLifecycle = $document->isPvDocument()
        ? $document->isPvCreatorOrEditor($currentUserId)
        : $canManageDocument;
    $associatedEvent = $pvEventsById[(int)$document->get('IDevent')] ?? null;
    $hasUpcomingPvEvent = $document->isPvDocument()
        && $associatedEvent instanceof \dbObject\Event
        && $associatedEvent->isUpcoming();
    $pvPreparationUrl = $canOpenPvEditor
        ? $document->buildPvEditorUrl($currentOrganizationId)
        : '';
    $isFolder = $document->isFolder();
    $isExternalLink = $document->isExternalLink();
    $canShareDocument = !$isFolder && $document->supportsHtmlContent();
    $documentTitle = (string)$document->get('title');
    $listTitle = $documentTitle;
    if ($document->isPvDocument() && !$document->isPvValidated()) {
        $listTitle .= ' (' . $document->getPvStageLabel() . ')';
    }
    $createdGroupIndex = sharedGetRelativeDateGroupIndexForDate($resolvedCreatedAt, $groups, $today);
    $createdGroup = $groups[$createdGroupIndex] ?? ['key' => 'too_far', 'label' => omoDocumentsScopeT('documents.group.too_far')];
    $createdGroupKey = (string)($createdGroup['key'] ?? 'too_far');
    $updatedGroupIndex = sharedGetRelativeDateGroupIndexForDate($resolvedUpdatedAt, $groups, $today);
    $updatedGroup = $groups[$updatedGroupIndex] ?? ['key' => 'too_far', 'label' => omoDocumentsScopeT('documents.group.too_far')];
    $updatedGroupKey = (string)($updatedGroup['key'] ?? 'too_far');

    $documentEntries[] = [
        'id' => $documentId,
        'href' => '/memo/' . $documentId,
        'title' => $documentTitle,
        'listTitle' => $listTitle,
        'documentType' => $document->getDocumentType(),
        'storedFileKind' => $document->isUploadedFile() ? $document->getStoredFileKind() : '',
        'isMissingUploadedFile' => $document->hasMissingUploadedFile(),
        'canExportPdf' => $document->isPvDocument(),
        'pdfExportUrl' => $document->isPvDocument()
            ? '/omo/api/documents/pv/export_pdf.php?id=' . rawurlencode((string)$documentId)
                . '&oid=' . rawurlencode((string)$currentOrganizationId)
            : '',
        'isFolder' => $isFolder,
        'isExternalLink' => $isExternalLink,
        'externalUrl' => $document->getExternalUrl(),
        'openInNewWindow' => $document->shouldOpenExternalLinkInNewWindow(),
        'canShare' => $canShareDocument,
        'pvPreparationUrl' => $pvPreparationUrl,
        'parentDocumentId' => $parentDocumentId > 0 ? $parentDocumentId : 0,
        'contextLabel' => $documentScope !== 'contextual'
            ? trim((string)$document->getOrganizationContextLabel())
            : '',
        'contextBreadcrumb' => $documentScope !== 'contextual'
            ? array_values(array_map(
                static function (array $item): array {
                    $organizationId = (int)($item['organizationId'] ?? 0);
                    $holonId = (int)($item['holonId'] ?? 0);

                    return array(
                        'label' => trim((string)($item['label'] ?? '')),
                        'organizationId' => $organizationId,
                        'holonId' => $holonId,
                    );
                },
                $document->getOrganizationContextBreadcrumbItems()
            ))
            : array(),
        'description' => trim((string)$document->get('description')),
        'keywords' => trim((string)$document->get('keywords')),
        'hasUpcomingPvEvent' => $hasUpcomingPvEvent,
        'canMove' => $canMoveDocument,
        'canArchive' => $canManageLifecycle && !$document->isArchived(),
        'canDelete' => $canManageLifecycle
            && (int)$document->get('IDevent') <= 0
            && !isset($documentListMetadata['documentsWithChildren'][$documentId]),
        'canEdit' => $document->isPvDocument()
            ? $canOpenPvEditor
            : (
                $canManageDocument
                 || (!$document->isEtherpadDocument() && !$document->isEthercalcDocument() && !$document->isWhiteboardDocument() && $document->canEditInOrganizationContextWithVisibilityRules($documentOrganizationId, $currentUserId, $visibilityRule, $editVisibilityRule, $documentViewerContext))
            ),
        'editUrl' => $document->isPvDocument()
            ? $pvPreparationUrl
            : ('/omo/api/documents/create.php?id=' . $documentId
                . ($documentOrganizationId > 0 ? '&oid=' . $documentOrganizationId : '')
                . ($documentHolonId > 0 ? '&cid=' . $documentHolonId : '')),
        'visibilityBadge' => (string)($visibility['badgeText'] ?? ''),
        'visibilityType' => (string)($visibility['type'] ?? ''),
        'visibilityIconUrl' => $resolveDocumentVisibilityIconUrl((string)($visibility['type'] ?? '')),
        'editVisibilityBadge' => (string)($editVisibility['badgeText'] ?? ''),
        'editVisibilityType' => (string)($editVisibility['type'] ?? ''),
        'editVisibilityIconUrl' => $resolveDocumentVisibilityIconUrl((string)($editVisibility['type'] ?? '')),
        'dateLabel' => $formatDate($resolvedUpdatedAt, in_array($updatedGroupKey, ['earlier', 'too_far'], true)),
        'fullDateLabel' => $formatDate($resolvedCreatedAt, true),
        'timestamp' => $resolvedUpdatedAt instanceof DateTimeInterface ? (int)$resolvedUpdatedAt->getTimestamp() : 0,
        'groupKey' => $updatedGroupKey,
        'groupLabel' => (string)($updatedGroup['label'] ?? omoDocumentsScopeT('documents.group.too_far')),
        'createdDateLabel' => $formatDate($resolvedCreatedAt, in_array($createdGroupKey, ['earlier', 'too_far'], true)),
        'createdFullDateLabel' => $formatDate($resolvedCreatedAt, true),
        'createdTimestamp' => $resolvedCreatedAt instanceof DateTimeInterface ? (int)$resolvedCreatedAt->getTimestamp() : 0,
        'createdGroupKey' => $createdGroupKey,
        'createdGroupLabel' => (string)($createdGroup['label'] ?? omoDocumentsScopeT('documents.group.too_far')),
        'updatedDateLabel' => $formatDate($resolvedUpdatedAt, in_array($updatedGroupKey, ['earlier', 'too_far'], true)),
        'updatedFullDateLabel' => $formatDate($resolvedUpdatedAt, true),
        'updatedTimestamp' => $resolvedUpdatedAt instanceof DateTimeInterface ? (int)$resolvedUpdatedAt->getTimestamp() : 0,
        'updatedGroupKey' => $updatedGroupKey,
        'updatedGroupLabel' => (string)($updatedGroup['label'] ?? omoDocumentsScopeT('documents.group.too_far')),
        'sortTitle' => $normalizeSortValue($document->get('title')),
        'contextUrl' => '/omo/api/documents/detail.php?id=' . $documentId
            . '&oid=' . $currentOrganizationId
            . ($documentHolonId > 0 ? '&cid=' . $documentHolonId : ''),
    ];
}

$requestedOpenDocumentPayload = null;
if ($initialOpenDocumentId > 0) {
    $requestedOpenDocument = new \dbObject\Document();
    $requestedDocumentContextUrl = '/omo/api/documents/detail.php?id=' . $initialOpenDocumentId
        . '&oid=' . $currentOrganizationId;

    if ($requestedOpenDocument->load($initialOpenDocumentId)) {
        $requestedDocumentId = (int)$requestedOpenDocument->getId();
        $requestedDocumentHolonId = (int)$requestedOpenDocument->get('IDholon');
        $requestedCreatedAt = $requestedOpenDocument->get('datecreation');
        $requestedOpenDocumentVisited = [];
        $requestedUpdatedAt = $requestedOpenDocument->getActivityDate($requestedOpenDocumentVisited);
        $requestedResolvedCreatedAt = $requestedCreatedAt instanceof DateTimeInterface
            ? $requestedCreatedAt
            : ($requestedUpdatedAt instanceof DateTimeInterface ? $requestedUpdatedAt : null);
        $requestedCanView = $requestedOpenDocument->canViewInOrganizationContext(
            $currentOrganizationId,
            $requestedDocumentHolonId > 0 ? $requestedDocumentHolonId : null
        );
        if ($requestedOpenDocument->isPvDocument()) {
            $hasPvInvitationAccess = !$requestedOpenDocument->isPvValidated()
                && $requestedOpenDocument->canUserAccessPvBeforeValidation($currentUserId, $currentOrganizationId);
            $requestedCanView = $requestedOpenDocument->canUserPassPvMeetingVisibilityGate($currentUserId, $currentOrganizationId)
                && ($hasPvInvitationAccess || $requestedCanView);
        }
        $requestedCanOpenPvEditor = $requestedOpenDocument->canUserOpenPvEditor($currentUserId, $currentOrganizationId);
        $requestedCanOpenDirectly = $requestedCanView || $requestedCanOpenPvEditor;

        if ($requestedDocumentHolonId > 0) {
            $requestedDocumentContextUrl .= '&cid=' . $requestedDocumentHolonId;
        }

        $requestedOpenDocumentPayload = [
            'id' => $requestedDocumentId,
            'contextUrl' => $requestedDocumentContextUrl,
            'title' => $requestedCanOpenDirectly ? (string)$requestedOpenDocument->get('title') : '',
            'fullDateLabel' => $requestedCanOpenDirectly ? $formatDate($requestedResolvedCreatedAt, true) : '',
            'documentType' => $requestedCanOpenDirectly ? $requestedOpenDocument->getDocumentType() : '',
            'isFolder' => $requestedOpenDocument->isFolder(),
            'openInNewWindow' => false,
            'externalUrl' => '',
            'pvPreparationUrl' => $requestedCanOpenPvEditor
                ? $requestedOpenDocument->buildPvEditorUrl($currentOrganizationId)
                : '',
            'hasUpcomingPvEvent' => $requestedCanOpenDirectly ? $requestedOpenDocument->hasUpcomingAssociatedEvent() : false,
            'canEdit' => $requestedOpenDocument->isPvDocument()
                ? $requestedCanOpenPvEditor
                : (
                    $requestedCanView
                    && (
                        $requestedOpenDocument->canManageInOrganizationContext($currentOrganizationId)
                         || (!$requestedOpenDocument->isEtherpadDocument()
                            && !$requestedOpenDocument->isEthercalcDocument()
                            && !$requestedOpenDocument->isWhiteboardDocument()
                            && $requestedOpenDocument->canEditInOrganizationContext($currentOrganizationId))
                    )
                ),
            'editUrl' => $requestedOpenDocument->isPvDocument()
                ? ($requestedCanOpenPvEditor ? $requestedOpenDocument->buildPvEditorUrl($currentOrganizationId) : '')
                : ($requestedCanView
                    ? '/omo/api/documents/create.php?id=' . $requestedDocumentId
                        . '&oid=' . $currentOrganizationId
                        . ($requestedDocumentHolonId > 0 ? '&cid=' . $requestedDocumentHolonId : '')
                    : ''),
        ];
    } else {
        if ($effectiveCurrentHolonId > 0) {
            $requestedDocumentContextUrl .= '&cid=' . $effectiveCurrentHolonId;
        }

        $requestedOpenDocumentPayload = [
            'id' => $initialOpenDocumentId,
            'contextUrl' => $requestedDocumentContextUrl,
            'title' => '',
            'fullDateLabel' => '',
            'documentType' => '',
            'isFolder' => false,
            'openInNewWindow' => false,
            'externalUrl' => '',
            'pvPreparationUrl' => '',
            'hasUpcomingPvEvent' => false,
            'canEdit' => false,
            'editUrl' => '',
        ];
    }
}

$documentsPayload = json_encode(
    [
        'documents' => $documentEntries,
        'openDocumentId' => $initialOpenDocumentId > 0 ? $initialOpenDocumentId : 0,
        'openDocumentMode' => $initialOpenDocumentMode,
        'requestedDocument' => $requestedOpenDocumentPayload,
        'groups' => array_map(
            static function (array $group): array {
                return [
                    'key' => (string)($group['key'] ?? ''),
                    'label' => (string)($group['label'] ?? ''),
                ];
            },
            $groups
        ),
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (!is_string($documentsPayload)) {
    $documentsPayload = '{"documents":[],"openDocumentId":0,"openDocumentMode":"detail","requestedDocument":null,"groups":[]}';
}
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
<div
    class="omo-documents omo-panel-view"
    id="omo-documents-root"
    data-omo-document-scope="<?= $escape($documentScope) ?>"
    data-omo-document-oid="<?= (int)$currentOrganizationId ?>"
    data-omo-document-cid="<?= (int)$effectiveCurrentHolonId ?>"
    data-omo-app-view-preferences="<?= $escape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-document-open-id="<?= (int)$initialOpenDocumentId ?>"
    data-omo-document-sort="updated"
    data-omo-document-density="detail"
    data-omo-view-filter-pending="1"
    aria-busy="true"
>
    <div class="omo-documents__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-documents__app-icon" aria-hidden="true">
                    <img src="images/tools/documents-folder.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-documents__title-row generic-title-row">
                        <h2 class="omo-panel-view__title"><?= $escape(omoDocumentsScopeT('documents.page.title')) ?></h2>
                        <span class="omo-documents__count omo-panel-view__count" data-omo-documents-header-count>
                            <?= $escape($visibleDocumentsCount) ?>
                            <?php if ($totalDocumentsCount > $visibleDocumentsCount): ?>
                                (<?= $escape($totalDocumentsCount) ?>)
                            <?php endif; ?>
                        </span>
                        <span class="generic-loading-indicator" data-omo-documents-loading-indicator role="status" hidden>
                            <svg class="generic-loading-indicator__spinner" viewBox="0 0 20 20" aria-hidden="true">
                                <circle class="generic-loading-indicator__track" cx="10" cy="10" r="7" fill="none" stroke="currentColor" stroke-width="2"></circle>
                                <path class="generic-loading-indicator__arc" d="M10 3a7 7 0 0 1 6.7 5" fill="none" stroke="currentColor" stroke-width="2"></path>
                            </svg>
                            <span><?= $escape(omoDocumentsScopeT('documents.action.loading')) ?></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="omo-panel-view__aside omo-documents__header-main-actions" data-omo-header-actions>
                <div class="omo-documents__bulk-actions" data-omo-documents-bulk-actions hidden>
                    <span class="omo-documents__bulk-count" data-omo-documents-bulk-count></span>
                    <button type="button" class="generic-action-button generic-action-button--secondary omo-documents__bulk-action-button" data-omo-documents-bulk-action="move" title="<?= $escape(omoDocumentsScopeT('documents.selection.move')) ?>" aria-label="<?= $escape(omoDocumentsScopeT('documents.selection.move')) ?>">
                        <span class="omo-documents__bulk-action-icon omo-documents__bulk-action-icon--move" aria-hidden="true">&#8594;</span>
                    </button>
                    <button type="button" class="generic-action-button generic-action-button--secondary omo-documents__bulk-action-button" data-omo-documents-bulk-action="archive" title="<?= $escape(omoDocumentsScopeT('documents.selection.archive')) ?>" aria-label="<?= $escape(omoDocumentsScopeT('documents.selection.archive')) ?>">
                        <span class="omo-documents__bulk-action-icon omo-documents__bulk-action-icon--archive" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="generic-action-button generic-action-button--danger omo-documents__bulk-action-button" data-omo-documents-bulk-action="delete" title="<?= $escape(omoDocumentsScopeT('documents.selection.delete')) ?>" aria-label="<?= $escape(omoDocumentsScopeT('documents.selection.delete')) ?>">
                        <span class="omo-documents__bulk-action-icon omo-documents__bulk-action-icon--delete" aria-hidden="true"></span>
                    </button>
                </div>
                <?php if ($canCreateDocument): ?>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--main omo-documents__new-button omo-mobile-corner-action"
                        aria-label="<?= $escape(omoDocumentsScopeT('documents.action.new')) ?>"
                        data-omo-documents-new
                        data-omo-documents-new-url="<?= $escape($newDocumentUrl) ?>"
                    ><span class="omo-mobile-corner-action__text"><?= $escape(omoDocumentsScopeT('documents.action.new')) ?></span></button>
                <?php endif; ?>
            </div>
        </div>
        <div class="omo-panel-view__header-secondary omo-documents__header-actions">
            <div class="omo-documents__filter-toolbar omo-view-filter" data-omo-documents-filter-control role="group" aria-label="<?= $escape(omoDocumentsScopeT('documents.filters.aria')) ?>">
                <div class="omo-view-filter__input">
                    <div class="omo-view-filter__chips">
                        <button type="button" class="omo-view-filter__chip" data-omo-documents-filter-toggle data-omo-documents-scope-chip aria-expanded="false" aria-controls="omo-documents-filter-panel"><?= $escape(omoDocumentsScopeT('documents.scope.' . $documentScope)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-documents-filter-toggle data-omo-documents-sort-chip aria-expanded="false" aria-controls="omo-documents-filter-panel"><?= $escape(omoDocumentsScopeT('documents.sort.updated')) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-documents-filter-toggle data-omo-documents-density-chip aria-expanded="false" aria-controls="omo-documents-filter-panel"><?= $escape(omoDocumentsScopeT('documents.controls.density.detail')) ?></button>
                    </div>
                    <label class="omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-omo-documents-quick-search placeholder="<?= $escape(omoDocumentsScopeT('documents.search.placeholder')) ?>" aria-label="<?= $escape(omoDocumentsScopeT('documents.search.aria')) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-documents-filter-panel" class="omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-omo-documents-filter-panel hidden>
                    <div class="omo-view-filter__panel-grid">
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(omoDocumentsScopeT('documents.filters.scope')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(omoDocumentsScopeT('documents.scope.toggle_aria')) ?>">
                                <?php foreach ($availableDocumentScopes as $scopeKey): ?>
                                    <?php $scopeLabel = omoDocumentsScopeT('documents.scope.' . $scopeKey); ?>
                                    <button type="button" class="omo-segmented__button<?= $documentScope === $scopeKey ? ' is-active' : '' ?>" data-omo-document-scope-toggle="<?= $escape($scopeKey) ?>" aria-pressed="<?= $documentScope === $scopeKey ? 'true' : 'false' ?>"><?= $escape($scopeLabel) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(omoDocumentsScopeT('documents.filters.sort')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(omoDocumentsScopeT('documents.controls.sort.aria')) ?>">
                                <button type="button" class="omo-segmented__button is-active" aria-label="<?= $escape(omoDocumentsScopeT('documents.sort.updated_aria')) ?>" data-omo-documents-sort="updated" aria-pressed="true"><?= $escape(omoDocumentsScopeT('documents.sort.updated')) ?></button>
                                <button type="button" class="omo-segmented__button" aria-label="<?= $escape(omoDocumentsScopeT('documents.sort.created_aria')) ?>" data-omo-documents-sort="created" aria-pressed="false"><?= $escape(omoDocumentsScopeT('documents.sort.created')) ?></button>
                                <button type="button" class="omo-segmented__button" aria-label="<?= $escape(omoDocumentsScopeT('documents.sort.alpha_aria')) ?>" data-omo-documents-sort="alpha" aria-pressed="false"><?= $escape(omoDocumentsScopeT('documents.controls.sort.alpha')) ?></button>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(omoDocumentsScopeT('documents.filters.density')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(omoDocumentsScopeT('documents.controls.density.aria')) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-documents-density="detail" aria-pressed="true"><?= $escape(omoDocumentsScopeT('documents.controls.density.detail')) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-documents-density="compact" aria-pressed="false"><?= $escape(omoDocumentsScopeT('documents.controls.density.compact')) ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-omo-documents-filter-apply><?= $escape(omoDocumentsScopeT('documents.filters.apply')) ?></button>
                        <?php if (!empty($applicationViewPreferences['canSavePersonal'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-documents-filter-save data-omo-app-view-save-scope="personal"><?= $escape(omoDocumentsScopeT('documents.filters.save_view')) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= $escape($applicationViewPreferences['primarySaveScope']) ?>"><?= $escape(omoApplicationViewPreferencesT('app_view.save_organization_template', array('templateName' => $applicationViewPreferences['templateLabel'] ?? ''))) ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <div class="omo-documents__results generic-file-list" data-omo-documents-results data-generic-file-list>
            <?php if (count($documentEntries) === 0): ?>
                <div class="omo-documents__empty omo-empty-state"><?= $escape($documentsEmptyMessage) ?></div>
            <?php else: ?>
                <?php
                $currentGroupKey = null;

                foreach ($documentEntries as $entry):
                    if ($entry['groupKey'] !== $currentGroupKey):
                        if ($currentGroupKey !== null):
                ?>
                        </div>
                    </section>
                <?php
                        endif;

                        $currentGroupKey = $entry['groupKey'];
                ?>
                    <?php
                    $sectionLayers = $groupLayers[$currentGroupKey] ?? array('title' => 5, 'list' => 4, 'folder' => 3);
                    ?>
                    <section
                        class="omo-documents__group omo-panel-group generic-file-list__group"
                        style="--generic-file-list-group-title-z: <?= (int)$sectionLayers['title'] ?>; --generic-file-list-group-header-z: <?= (int)$sectionLayers['list'] ?>; --generic-file-list-group-folder-z: <?= (int)$sectionLayers['folder'] ?>;"
                    >
                        <h3 class="omo-panel-group__title generic-file-list__group-title"><?= $escape($entry['groupLabel']) ?></h3>
                        <div class="omo-documents__list omo-panel-view__body_content">
                <?php endif; ?>
                            <article class="omo-documents__item-shell generic-file-list__item-shell<?= !empty($entry['isMissingUploadedFile']) ? ' omo-documents__item-shell--missing-upload' : '' ?>">
                                <div
                                    class="omo-documents__item omo-card omo-card--interactive"
                                    role="button"
                                    tabindex="0"
                                    data-omo-document-id="<?= $escape($entry['id']) ?>"
                                    data-omo-document-href="<?= $escape($entry['href']) ?>"
                                    data-omo-document-context-url="<?= $escape($entry['contextUrl']) ?>"
                                    data-omo-document-external-url="<?= $escape($entry['externalUrl']) ?>"
                                    data-omo-document-open-in-new-window="<?= !empty($entry['openInNewWindow']) ? '1' : '0' ?>"
                                    data-omo-document-type="<?= $escape($entry['documentType']) ?>"
                                    data-omo-document-pv-editor-url="<?= $escape($entry['pvPreparationUrl'] ?? '') ?>"
                                    data-omo-document-title="<?= $escape($entry['title']) ?>"
                                    data-omo-document-full-date="<?= $escape($entry['fullDateLabel']) ?>"
                                >
                                    <div class="omo-documents__item-head">
                                        <span class="omo-documents__date"><?= $escape($entry['dateLabel']) ?></span>
                                        <span class="omo-documents__title-line">
                                            <strong class="omo-documents__title generic-title generic-title--item"><?= $escape($entry['listTitle']) ?></strong>
                                            <?php if (!empty($entry['isMissingUploadedFile'])): ?>
                                                <span class="omo-documents__missing-upload-badge"><?= $escape(omoDocumentsScopeT('documents.upload_missing.badge')) ?></span>
                                            <?php endif; ?>
                                            <?php if (
                                                $entry['visibilityBadge'] !== '' && $entry['visibilityIconUrl'] !== ''
                                                && $entry['editVisibilityBadge'] !== '' && $entry['editVisibilityIconUrl'] !== ''
                                            ): ?>
                                                <span
                                                    class="omo-documents__scope-capsule"
                                                    aria-label="<?= $escape(
                                                        omoDocumentsScopeT('documents.scope.view') . ': ' . $entry['visibilityBadge']
                                                        . ' | '
                                                        . omoDocumentsScopeT('documents.scope.edit') . ': ' . $entry['editVisibilityBadge']
                                                    ) ?>"
                                                    title="<?= $escape(
                                                        omoDocumentsScopeT('documents.scope.view') . ': ' . $entry['visibilityBadge']
                                                        . ' | '
                                                        . omoDocumentsScopeT('documents.scope.edit') . ': ' . $entry['editVisibilityBadge']
                                                    ) ?>"
                                                >
                                                    <span class="omo-documents__scope-icon" aria-hidden="true">
                                                        <img
                                                            src="<?= $escape($entry['visibilityIconUrl']) ?>"
                                                            alt=""
                                                            class="black-icon"
                                                            loading="lazy"
                                                        >
                                                    </span>
                                                    <span class="omo-documents__scope-separator" aria-hidden="true"></span>
                                                    <span class="omo-documents__scope-icon" aria-hidden="true">
                                                        <img
                                                            src="<?= $escape($entry['editVisibilityIconUrl']) ?>"
                                                            alt=""
                                                            class="black-icon"
                                                            loading="lazy"
                                                        >
                                                    </span>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($entry['contextBreadcrumb'])): ?>
                                        <div class="omo-documents__context" aria-label="Contexte du document">
                                            <?php foreach ($entry['contextBreadcrumb'] as $breadcrumbIndex => $breadcrumbItem): ?>
                                                <?php if ($breadcrumbIndex > 0): ?>
                                                    <span class="omo-documents__context-separator">›</span>
                                                <?php endif; ?>
                                                <button
                                                    type="button"
                                                    class="omo-documents__context-link"
                                                    data-omo-document-context-jump="1"
                                                    data-omo-document-context-jump-oid="<?= (int)($breadcrumbItem['organizationId'] ?? 0) ?>"
                                                    data-omo-document-context-jump-cid="<?= (int)($breadcrumbItem['holonId'] ?? 0) ?>"
                                                ><?= $escape($breadcrumbItem['label'] ?? '') ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($entry['contextLabel'] !== ''): ?>
                                        <div class="omo-documents__context"><?= $escape($entry['contextLabel']) ?></div>
                                    <?php endif; ?>

                                    <?php if ($entry['description'] !== ''): ?>
                                        <p class="omo-documents__description generic-description"><?= $escape($entry['description']) ?></p>
                                    <?php endif; ?>

                                    <?php if ($entry['keywords'] !== ''): ?>
                                        <div class="omo-documents__keywords"><?= $escape($entry['keywords']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($entry['canEdit']) || !empty($entry['canMove']) || !empty($entry['canArchive']) || !empty($entry['canDelete']) || !empty($entry['canShare']) || !empty($entry['canExportPdf'])): ?>
                                    <div class="omo-documents__menu generic-menu" data-omo-document-menu="1">
                                        <button
                                            type="button"
                                            class="omo-documents__menu-toggle generic-menu-toggle"
                                            data-omo-document-menu-toggle="1"
                                            data-omo-document-menu-document-id="<?= (int)$entry['id'] ?>"
                                            data-omo-document-menu-title="<?= $escape($entry['title']) ?>"
                                            data-omo-document-menu-edit-url="<?= $escape($entry['editUrl']) ?>"
                                            data-omo-document-menu-can-edit="<?= !empty($entry['canEdit']) ? '1' : '0' ?>"
                                            data-omo-document-menu-can-move="<?= !empty($entry['canMove']) ? '1' : '0' ?>"
                                            data-omo-document-menu-can-archive="<?= !empty($entry['canArchive']) ? '1' : '0' ?>"
                                            data-omo-document-menu-can-delete="<?= !empty($entry['canDelete']) ? '1' : '0' ?>"
                                            data-omo-document-menu-is-folder="<?= !empty($entry['isFolder']) ? '1' : '0' ?>"
                                            data-omo-document-menu-can-share="<?= !empty($entry['canShare']) ? '1' : '0' ?>"
                                            data-omo-document-menu-can-export-pdf="<?= !empty($entry['canExportPdf']) ? '1' : '0' ?>"
                                            data-omo-document-menu-pdf-url="<?= $escape((string)($entry['pdfExportUrl'] ?? '')) ?>"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Actions pour <?= $escape($entry['title']) ?>"
                                        >...</button>
                                    </div>
                                <?php endif; ?>
                            </article>
                <?php endforeach; ?>
                        </div>
                    </section>
            <?php endif; ?>
        </div>

            <div class="omo-overlay-drawer omo-documents__detail-drawer" data-omo-document-detail-drawer hidden>
                <div class="omo-overlay-drawer__backdrop" data-omo-document-detail-close></div>
                <div class="omo-overlay-drawer__panel">
                    <div class="omo-overlay-drawer__header generic-drawer-header">
                        <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                            <h3 class="omo-overlay-drawer__title" data-omo-subdrawer-title data-omo-document-detail-title><?= $escape(omoDocumentsScopeT('documents.drawer.detail_title')) ?></h3>
                            <p class="omo-overlay-drawer__description" data-omo-subdrawer-description data-omo-document-detail-description><?= $escape(omoDocumentsScopeT('documents.drawer.detail_description')) ?></p>
                        </div>
                        <div class="generic-drawer-header__actions">
                            <div class="omo-documents__drawer-custom-actions" data-omo-subdrawer-actions></div>
                            <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-omo-document-detail-close><?= $escape(omoDocumentsScopeT('documents.drawer.close')) ?></button>
                        </div>
                    </div>
                    <div class="omo-overlay-drawer__body" data-omo-document-detail-body></div>
                </div>
            </div>

            <script type="application/json" data-omo-documents-data><?= $documentsPayload ?></script>
            <script src="/common/drawer/subdrawer.js?v=20260816-header-help"></script>
            <script src="/omo/assets/js/application-view-preferences.js?v=20260902-view-cleanup"></script>
            <script>
            (function () {
                const omoDocumentsSavedViewsStorageKey = 'omo.documents.saved-views.v2';
                const omoDocumentsLegacySavedViewsStorageKey = 'omo.documents.saved-views.v1';
                const omoDocumentsSessionViewsStorageKey = 'omo.documents.session-views.v1';
                const omoDocumentsSearchStorageKey = 'omo.documents.quick-search.v1';
                const omoDocumentsFileIconUrl = '/omo/assets/images/documents/file.png';
                const omoDocumentsDownloadIconUrl = '/omo/assets/images/documents/download.png';
                const omoDocumentsFolderIconUrl = '/omo/assets/images/documents/folder.png';
                const omoDocumentsLinkIconUrl = '/omo/assets/images/documents/link.png';
                const omoDocumentsPvIconUrl = '/omo/assets/images/documents/pv.png';
                 const omoDocumentsEtherpadIconUrl = '/omo/assets/images/documents/collaborative.png';
                 const omoDocumentsEthercalcIconUrl = '/omo/assets/images/documents/spreadsheet.png';
                 const omoDocumentsWhiteboardIconUrl = '/omo/assets/images/documents/whiteboard.png';
                const omoDocumentsStoredKindIconUrls = {
                    image: '/omo/assets/images/documents/image.png',
                    video: '/omo/assets/images/documents/video.png',
                    audio: '/omo/assets/images/documents/audio.png',
                    text: '/omo/assets/images/documents/text.png',
                    spreadsheet: '/omo/assets/images/documents/spreadsheet-kind.png',
                    presentation: '/omo/assets/images/documents/presentation.png',
                    drawing: '/omo/assets/images/documents/drawing.png'
                };
                const omoDocumentsPvType = 'pv';
                 const omoDocumentsEtherpadType = 'etherpad';
                 const omoDocumentsEthercalcType = 'ethercalc';
                 const omoDocumentsWhiteboardType = 'whiteboard';
                const omoDocumentsMissingUploadedFileLabel = <?= json_encode(omoDocumentsScopeT('documents.upload_missing.badge'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                 const omoDocumentsEtherpadIconLabel = <?= json_encode(omoDocumentsScopeT('documents.icon.etherpad'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                 const omoDocumentsEthercalcIconLabel = <?= json_encode(omoDocumentsScopeT('documents.icon.ethercalc'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                 const omoDocumentsWhiteboardIconLabel = <?= json_encode(omoDocumentsScopeT('documents.icon.whiteboard'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                const omoDocumentsGetIconUrl = function (documentItem) {
                    if (documentItem && documentItem.isFolder) {
                        return omoDocumentsFolderIconUrl;
                    }

                    if (documentItem && documentItem.isExternalLink) {
                        return omoDocumentsLinkIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'uploaded_file') {
                        const storedFileKind = String(documentItem.storedFileKind || '').trim().toLowerCase();
                        return omoDocumentsStoredKindIconUrls[storedFileKind] || omoDocumentsDownloadIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsPvType) {
                        return omoDocumentsPvIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEtherpadType) {
                        return omoDocumentsEtherpadIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEthercalcType) {
                        return omoDocumentsEthercalcIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsWhiteboardType) {
                        return omoDocumentsWhiteboardIconUrl;
                    }

                    return omoDocumentsFileIconUrl;
                };

                const omoDocumentsGetIconAlt = function (documentItem) {
                    if (documentItem && documentItem.isFolder) {
                        return 'Dossier';
                    }

                    if (documentItem && documentItem.isExternalLink) {
                        return 'Lien externe';
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'uploaded_file') {
                        const storedFileKind = String(documentItem.storedFileKind || '').trim().toLowerCase();
                        const storedKindLabels = {
                            image: <?= json_encode(omoDocumentsScopeT('documents.icon.image'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                            video: <?= json_encode(omoDocumentsScopeT('documents.icon.video'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                            audio: <?= json_encode(omoDocumentsScopeT('documents.icon.audio'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                            text: <?= json_encode(omoDocumentsScopeT('documents.icon.text'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                            spreadsheet: <?= json_encode(omoDocumentsScopeT('documents.icon.spreadsheet'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                            presentation: <?= json_encode(omoDocumentsScopeT('documents.icon.presentation'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                            drawing: <?= json_encode(omoDocumentsScopeT('documents.icon.drawing'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                        };
                        return storedKindLabels[storedFileKind] || 'Fichier a telecharger';
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsPvType) {
                        return 'Proces verbal';
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEtherpadType) {
                        return omoDocumentsEtherpadIconLabel;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEthercalcType) {
                        return omoDocumentsEthercalcIconLabel;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsWhiteboardType) {
                        return omoDocumentsWhiteboardIconLabel;
                    }

                    return 'Fichier';
                };

                const omoDocumentsNormalizeSortPreference = function (value) {
                    const normalizedValue = String(value || '').trim().toLowerCase();

                    if (normalizedValue === 'alpha') {
                        return 'alpha';
                    }

                    if (normalizedValue === 'created' || normalizedValue === 'creation') {
                        return 'created';
                    }

                    return 'updated';
                };

                const omoDocumentsNormalizeDensityPreference = function (value) {
                    return String(value || '').trim().toLowerCase() === 'compact'
                        ? 'compact'
                        : 'detail';
                };

                const omoDocumentsNormalizeScope = function (value) {
                    const normalizedScope = String(value || '').trim().toLowerCase();
                    if (normalizedScope === 'global') {
                        return 'descendants';
                    }
                    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
                };

                const omoDocumentsReadSessionCookie = function (name) {
                    if (typeof window.omoReadCookie === 'function') {
                        return String(window.omoReadCookie(name) || '');
                    }

                    const cookiePrefix = encodeURIComponent(name) + '=';
                    const cookies = document.cookie ? document.cookie.split(';') : [];

                    for (let index = 0; index < cookies.length; index += 1) {
                        const cookie = cookies[index].trim();

                        if (cookie.indexOf(cookiePrefix) === 0) {
                            return decodeURIComponent(cookie.slice(cookiePrefix.length));
                        }
                    }

                    return '';
                };

                const omoDocumentsWriteSessionCookie = function (name, value) {
                    document.cookie = [
                        encodeURIComponent(name) + '=' + encodeURIComponent(String(value || '')),
                        'path=/',
                        'SameSite=Lax'
                    ].join('; ');
                };

                const omoDocumentsBuildFolderStateCookieName = function (organizationId, holonId, scope) {
                    const normalizedOrganizationId = Number(organizationId || 0) > 0
                        ? String(Number(organizationId || 0))
                        : '0';
                    const normalizedHolonId = Number(holonId || 0) > 0
                        ? String(Number(holonId || 0))
                        : '0';
                    const normalizedScope = omoDocumentsNormalizeScope(scope);

                    return 'omo_documents_folders_' + normalizedOrganizationId + '_' + normalizedHolonId + '_' + normalizedScope;
                };

                const omoDocumentsParseFolderState = function (rawValue) {
                    const folderIds = new Set();

                    String(rawValue || '')
                        .split(',')
                        .forEach(function (part) {
                            const folderId = Number(String(part || '').trim());
                            if (Number.isInteger(folderId) && folderId > 0) {
                                folderIds.add(folderId);
                            }
                        });

                    return folderIds;
                };

                const omoDocumentsGetPreferencesContextKey = function (panel) {
                    return String(panel && panel.getAttribute('data-omo-document-oid') || '0')
                        + ':' + String(panel && panel.getAttribute('data-omo-document-cid') || '0');
                };

                const omoDocumentsCreatePreferences = function (preferences) {
                    return {
                        scope: omoDocumentsNormalizeScope(preferences && preferences.scope),
                        sort: omoDocumentsNormalizeSortPreference(preferences && preferences.sort),
                        density: omoDocumentsNormalizeDensityPreference(preferences && preferences.density)
                    };
                };

                const omoDocumentsGetSavedViewsStore = function () {
                    try {
                        const rawValue = window.localStorage.getItem(omoDocumentsSavedViewsStorageKey);
                        const savedViews = rawValue ? JSON.parse(rawValue) : null;
                        if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                            return {
                                defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                                    ? savedViews.defaultView
                                    : null,
                                contexts: savedViews.contexts
                            };
                        }

                        const legacyValue = window.localStorage.getItem(omoDocumentsLegacySavedViewsStorageKey);
                        const legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
                        return {
                            defaultView: null,
                            contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
                        };
                    } catch (error) {
                        return {defaultView: null, contexts: {}};
                    }
                };

                const omoDocumentsSaveViewsStore = function (store) {
                    try {
                        window.localStorage.setItem(omoDocumentsSavedViewsStorageKey, JSON.stringify({
                            defaultView: store.defaultView && typeof store.defaultView === 'object'
                                ? store.defaultView
                                : null,
                            contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
                        }));
                    } catch (error) {
                    }
                };

                const omoDocumentsGetStoredPreferences = function (panel) {
                    const preferences = omoDocumentsGetSavedViewsStore().contexts[
                        omoDocumentsGetPreferencesContextKey(panel)
                    ];
                    return preferences && typeof preferences === 'object' ? preferences : null;
                };

                const omoDocumentsGetDefaultPreferences = function () {
                    return omoDocumentsGetSavedViewsStore().defaultView;
                };

                const omoDocumentsStorePreferences = function (panel, preferences) {
                    const store = omoDocumentsGetSavedViewsStore();
                    store.contexts[omoDocumentsGetPreferencesContextKey(panel)] = omoDocumentsCreatePreferences(preferences);
                    omoDocumentsSaveViewsStore(store);
                };

                const omoDocumentsStoreDefaultPreferences = function (preferences) {
                    const store = omoDocumentsGetSavedViewsStore();
                    store.defaultView = omoDocumentsCreatePreferences(preferences);
                    omoDocumentsSaveViewsStore(store);
                };

                const omoDocumentsClearStoredPreferences = function (panel) {
                    const store = omoDocumentsGetSavedViewsStore();
                    delete store.contexts[omoDocumentsGetPreferencesContextKey(panel)];
                    omoDocumentsSaveViewsStore(store);
                };

                const omoDocumentsReadStoredValue = function (storage, storageKey, panel) {
                    try {
                        const rawValue = storage.getItem(storageKey);
                        const values = rawValue ? JSON.parse(rawValue) : null;
                        return values && typeof values === 'object'
                            ? values[omoDocumentsGetPreferencesContextKey(panel)] || null
                            : null;
                    } catch (error) {
                        return null;
                    }
                };

                const omoDocumentsReadPreferences = function (panel) {
                    const temporary = omoDocumentsReadStoredValue(
                        window.sessionStorage,
                        omoDocumentsSessionViewsStorageKey,
                        panel
                    );
                    const saved = omoDocumentsGetStoredPreferences(panel);
                    const defaultView = omoDocumentsGetDefaultPreferences();
                    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                        ? window.omoApplicationViewPreferencesGetDefault(panel)
                        : null;
                    const personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
                        ? window.omoApplicationViewPreferencesGetPersonal(panel)
                        : null;
                    const preferences = temporary || personalView || serverDefault || saved || defaultView || {};

                    return {
                        scope: omoDocumentsNormalizeScope(
                            preferences.scope || (panel && panel.getAttribute('data-omo-document-scope')) || 'contextual'
                        ),
                        sort: omoDocumentsNormalizeSortPreference(preferences.sort),
                        density: omoDocumentsNormalizeDensityPreference(preferences.density)
                    };
                };

                const omoDocumentsWritePreferences = function (panel, preferences, saveView) {
                    const normalizedPreferences = omoDocumentsCreatePreferences(preferences);

                    if (saveView) {
                        omoDocumentsStorePreferences(panel, normalizedPreferences);
                        omoDocumentsClearTemporaryPreferences(panel);
                        return;
                    }

                    try {
                        const rawValue = window.sessionStorage.getItem(omoDocumentsSessionViewsStorageKey);
                        let values = rawValue ? JSON.parse(rawValue) : {};
                        if (!values || typeof values !== 'object') {
                            values = {};
                        }
                        values[omoDocumentsGetPreferencesContextKey(panel)] = normalizedPreferences;
                        window.sessionStorage.setItem(omoDocumentsSessionViewsStorageKey, JSON.stringify(values));
                    } catch (error) {
                    }
                };

                const omoDocumentsClearTemporaryPreferences = function (panel) {
                    try {
                        const rawValue = window.sessionStorage.getItem(omoDocumentsSessionViewsStorageKey);
                        const values = rawValue ? JSON.parse(rawValue) : {};
                        if (!values || typeof values !== 'object') {
                            return;
                        }
                        delete values[omoDocumentsGetPreferencesContextKey(panel)];
                        window.sessionStorage.setItem(omoDocumentsSessionViewsStorageKey, JSON.stringify(values));
                    } catch (error) {
                    }
                };

                const omoDocumentsClearAllTemporaryPreferences = function () {
                    try {
                        window.sessionStorage.removeItem(omoDocumentsSessionViewsStorageKey);
                    } catch (error) {
                    }
                };

                const omoDocumentsReadSearch = function (panel) {
                    const storedSearch = omoDocumentsReadStoredValue(
                        window.sessionStorage,
                        omoDocumentsSearchStorageKey,
                        panel
                    );
                    return typeof storedSearch === 'string' ? storedSearch : '';
                };

                const omoDocumentsWriteSearch = function (panel, searchValue) {
                    try {
                        const rawValue = window.sessionStorage.getItem(omoDocumentsSearchStorageKey);
                        let values = rawValue ? JSON.parse(rawValue) : {};
                        if (!values || typeof values !== 'object') {
                            values = {};
                        }
                        values[omoDocumentsGetPreferencesContextKey(panel)] = String(searchValue || '');
                        window.sessionStorage.setItem(omoDocumentsSearchStorageKey, JSON.stringify(values));
                    } catch (error) {
                    }
                };

                window.omoDocumentsReadViewPreferences = omoDocumentsReadPreferences;

                window.omoInitDocumentsPanels = function (root) {
                        const scope = root instanceof Element ? root : document;

                        scope.querySelectorAll('.omo-documents').forEach(function (panel) {
                            if (panel.dataset.omoDocumentsReady === '1') {
                                return;
                            }

                            const results = panel.querySelector('[data-omo-documents-results]');
                            const dataNode = panel.querySelector('[data-omo-documents-data]');
                            const detailDrawer = panel.querySelector('[data-omo-document-detail-drawer]');
                            const detailBody = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-body]') : null;
                            const detailTitle = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-title]') : null;
                            const detailDescription = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-description]') : null;
                            const detailDrawerController = detailDrawer && typeof window.omoCreateSubdrawerController === 'function'
                                ? (detailDrawer.__omoSubdrawerController || window.omoCreateSubdrawerController({ drawer: detailDrawer }))
                                : null;

                            if (detailDrawerController) {
                                detailDrawer.__omoSubdrawerController = detailDrawerController;
                                window.omoDocumentsDetailDrawer = detailDrawerController;
                            }

                            if (!results || !dataNode) {
                                return;
                            }

                            let payload = null;

                            try {
                                payload = JSON.parse(dataNode.textContent || '{}');
                            } catch (error) {
                                return;
                            }

                            const documents = Array.isArray(payload.documents) ? payload.documents.slice() : [];
                            const requestedDocument = payload && payload.requestedDocument && typeof payload.requestedDocument === 'object'
                                ? payload.requestedDocument
                                : null;
                            const groups = Array.isArray(payload.groups) ? payload.groups : [];
                            const emptyStateMessage = <?= json_encode($documentsEmptyMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                            const folderStateCookieName = omoDocumentsBuildFolderStateCookieName(
                                Number(panel.getAttribute('data-omo-document-oid') || 0),
                                Number(panel.getAttribute('data-omo-document-cid') || 0),
                                panel.getAttribute('data-omo-document-scope') || 'contextual'
                            );

                            panel.dataset.omoDocumentsReady = '1';
                            const savedPreferences = omoDocumentsReadPreferences(panel);

                            const state = {
                                sort: savedPreferences.sort,
                                density: savedPreferences.density,
                                query: omoDocumentsReadSearch(panel),
                                openFolderIds: omoDocumentsParseFolderState(
                                    omoDocumentsReadSessionCookie(folderStateCookieName)
                                ),
                                selectedDocumentIds: new Set(),
                                activeDocumentId: detailDrawer && detailDrawer.dataset.omoDocumentActiveId
                                    ? Number(detailDrawer.dataset.omoDocumentActiveId)
                                    : null
                            };
                            let detailRequestToken = 0;
                            let pendingDisplayFilters = null;
                            let filterPanelOpen = false;

                            const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
                                ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
                                : null;

                            const compareText = function (left, right) {
                                const normalizedLeft = String(left || '');
                                const normalizedRight = String(right || '');

                                if (collator) {
                                    return collator.compare(normalizedLeft, normalizedRight);
                                }

                                return normalizedLeft.localeCompare(normalizedRight);
                            };

                            const normalizeQuickSearch = function (value) {
                                return String(value || '')
                                    .toLocaleLowerCase()
                                    .normalize('NFD')
                                    .replace(/[\u0300-\u036f]/g, '')
                                    .trim();
                            };

                            const documentMatchesQuickSearch = function (documentItem, query) {
                                if (query === '') {
                                    return true;
                                }
                                const breadcrumb = Array.isArray(documentItem && documentItem.contextBreadcrumb)
                                    ? documentItem.contextBreadcrumb.map(function (item) {
                                        return item && item.label ? item.label : '';
                                    }).join(' ')
                                    : '';
                                const searchText = [
                                    documentItem && documentItem.title,
                                    documentItem && documentItem.listTitle,
                                    documentItem && documentItem.description,
                                    documentItem && documentItem.keywords,
                                    documentItem && documentItem.contextLabel,
                                    breadcrumb,
                                    documentItem && documentItem.documentType
                                ].join(' ');
                                return normalizeQuickSearch(searchText).indexOf(query) !== -1;
                            };

                            const getTemporalSortMode = function (sortMode) {
                                return sortMode === 'created'
                                    ? 'created'
                                    : 'updated';
                            };

                            const getDocumentTimestamp = function (documentItem, sortMode) {
                                if (getTemporalSortMode(sortMode) === 'created') {
                                    return Number(documentItem && documentItem.createdTimestamp ? documentItem.createdTimestamp : 0);
                                }

                                return Number(documentItem && documentItem.updatedTimestamp ? documentItem.updatedTimestamp : 0);
                            };

                            const getDocumentDateLabel = function (documentItem, sortMode, includeFullDate) {
                                const temporalSortMode = getTemporalSortMode(sortMode);

                                if (temporalSortMode === 'created') {
                                    return includeFullDate
                                        ? String(documentItem && documentItem.createdFullDateLabel ? documentItem.createdFullDateLabel : '')
                                        : String(documentItem && documentItem.createdDateLabel ? documentItem.createdDateLabel : '');
                                }

                                return includeFullDate
                                    ? String(documentItem && documentItem.updatedFullDateLabel ? documentItem.updatedFullDateLabel : '')
                                    : String(documentItem && documentItem.updatedDateLabel ? documentItem.updatedDateLabel : '');
                            };

                            const getDocumentGroupKey = function (documentItem, sortMode) {
                                return getTemporalSortMode(sortMode) === 'created'
                                    ? String(documentItem && documentItem.createdGroupKey ? documentItem.createdGroupKey : 'too_far')
                                    : String(documentItem && documentItem.updatedGroupKey ? documentItem.updatedGroupKey : 'too_far');
                            };

                            const getDateColumnLabel = function (sortMode) {
                                return getTemporalSortMode(sortMode) === 'created'
                                    ? <?= json_encode(omoDocumentsScopeT('documents.date_column.created'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                                    : <?= json_encode(omoDocumentsScopeT('documents.date_column.updated'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                            };

                            const sortByTemporal = function (items, sortMode) {
                                return items.slice().sort(function (left, right) {
                                    const timestampDiff = getDocumentTimestamp(right, sortMode) - getDocumentTimestamp(left, sortMode);

                                    if (timestampDiff !== 0) {
                                        return timestampDiff;
                                    }

                                    return compareText(left.sortTitle || left.title, right.sortTitle || right.title);
                                });
                            };

                            const sortByAlpha = function (items) {
                                return items.slice().sort(function (left, right) {
                                    const titleDiff = compareText(left.sortTitle || left.title, right.sortTitle || right.title);

                                    if (titleDiff !== 0) {
                                        return titleDiff;
                                    }

                                    return getDocumentTimestamp(right, 'updated') - getDocumentTimestamp(left, 'updated');
                                });
                            };

                            const buildExternalFaviconUrl = function (externalUrl) {
                                const trimmedUrl = String(externalUrl || '').trim();
                                if (trimmedUrl === '') {
                                    return '';
                                }

                                try {
                                    const parsedUrl = new URL(trimmedUrl, window.location.origin);
                                    if (!/^https?:$/i.test(parsedUrl.protocol)) {
                                        return '';
                                    }

                                    return parsedUrl.origin.replace(/\/+$/, '') + '/favicon.ico';
                                } catch (error) {
                                    return '';
                                }
                            };

                            const appendExternalFaviconBadge = function (iconBox, externalUrl) {
                                if (!iconBox) {
                                    return;
                                }

                                const faviconUrl = buildExternalFaviconUrl(externalUrl);
                                if (faviconUrl === '') {
                                    return;
                                }

                                const faviconBadge = document.createElement('span');
                                faviconBadge.className = 'omo-documents__favicon-badge';
                                faviconBadge.hidden = true;

                                const faviconImage = document.createElement('img');
                                faviconImage.className = 'omo-documents__favicon-image';
                                faviconImage.src = faviconUrl;
                                faviconImage.alt = '';
                                faviconImage.loading = 'lazy';
                                faviconImage.referrerPolicy = 'no-referrer';
                                faviconImage.addEventListener('load', function () {
                                    faviconBadge.hidden = false;
                                }, { once: true });
                                faviconImage.addEventListener('error', function () {
                                    faviconBadge.remove();
                                }, { once: true });

                                faviconBadge.appendChild(faviconImage);
                                iconBox.appendChild(faviconBadge);
                            };

                            const createVisibilityCapsule = function (documentItem) {
                                const visibilityLabel = String(documentItem && documentItem.visibilityBadge ? documentItem.visibilityBadge : '').trim();
                                const visibilityIconUrl = String(documentItem && documentItem.visibilityIconUrl ? documentItem.visibilityIconUrl : '').trim();
                                const editVisibilityLabel = String(documentItem && documentItem.editVisibilityBadge ? documentItem.editVisibilityBadge : '').trim();
                                const editVisibilityIconUrl = String(documentItem && documentItem.editVisibilityIconUrl ? documentItem.editVisibilityIconUrl : '').trim();

                                if (
                                    visibilityLabel === ''
                                    || visibilityIconUrl === ''
                                    || editVisibilityLabel === ''
                                    || editVisibilityIconUrl === ''
                                ) {
                                    return null;
                                }

                                const tooltipLabel = <?= json_encode(omoDocumentsScopeT('documents.scope.view'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                                    + ': ' + visibilityLabel
                                    + ' | '
                                    + <?= json_encode(omoDocumentsScopeT('documents.scope.edit'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                                    + ': ' + editVisibilityLabel;

                                const capsule = document.createElement('span');
                                capsule.className = 'omo-documents__scope-capsule';
                                capsule.setAttribute('aria-label', tooltipLabel);
                                capsule.setAttribute('title', tooltipLabel);

                                const buildScopeIcon = function (iconUrl) {
                                    const icon = document.createElement('span');
                                    icon.className = 'omo-documents__scope-icon';
                                    icon.setAttribute('aria-hidden', 'true');

                                    const image = document.createElement('img');
                                    image.className = 'black-icon';
                                    image.src = iconUrl;
                                    image.alt = '';
                                    image.loading = 'lazy';

                                    icon.appendChild(image);
                                    return icon;
                                };

                                const separator = document.createElement('span');
                                separator.className = 'omo-documents__scope-separator';
                                separator.setAttribute('aria-hidden', 'true');

                                capsule.appendChild(buildScopeIcon(visibilityIconUrl));
                                capsule.appendChild(separator);
                                capsule.appendChild(buildScopeIcon(editVisibilityIconUrl));

                                return capsule;
                            };

                            const childrenByParentId = new Map();
                            documents.forEach(function (documentItem) {
                                const parentDocumentId = Number(documentItem && documentItem.parentDocumentId ? documentItem.parentDocumentId : 0);
                                const normalizedParentDocumentId = Number.isInteger(parentDocumentId) && parentDocumentId > 0
                                    ? parentDocumentId
                                    : 0;

                                if (!childrenByParentId.has(normalizedParentDocumentId)) {
                                    childrenByParentId.set(normalizedParentDocumentId, []);
                                }

                                childrenByParentId.get(normalizedParentDocumentId).push(documentItem);
                            });

                            const getSortedTree = function (sortMode, parentDocumentId) {
                                const normalizedParentDocumentId = Number(parentDocumentId || 0) > 0
                                    ? Number(parentDocumentId || 0)
                                    : 0;
                                const sourceItems = childrenByParentId.get(normalizedParentDocumentId) || [];
                                const sortedItems = sortMode === 'alpha'
                                    ? sortByAlpha(sourceItems)
                                    : sortByTemporal(sourceItems, sortMode);

                                const query = normalizeQuickSearch(state.query);
                                return sortedItems.map(function (documentItem) {
                                    const clonedItem = Object.assign({}, documentItem);
                                    clonedItem.children = getSortedTree(sortMode, Number(documentItem.id || 0));
                                    return query === ''
                                        || documentMatchesQuickSearch(documentItem, query)
                                        || clonedItem.children.length > 0
                                        ? clonedItem
                                        : null;
                                }).filter(function (documentItem) {
                                    return documentItem !== null;
                                });
                            };

                            const formatSelectionText = function (template, values) {
                                return String(template || '').replace(/\{([a-z_]+)\}/gi, function (match, key) {
                                    return values && values[key] !== undefined ? String(values[key]) : match;
                                });
                            };

                            const getSelectedDocumentIds = function () {
                                return Array.from(state.selectedDocumentIds).filter(function (documentId) {
                                    return Number.isInteger(documentId) && documentId > 0;
                                });
                            };

                            const selectedDocumentsCanBeDeleted = function (selectedDocumentIds) {
                                return selectedDocumentIds.every(function (documentId) {
                                    return documents.some(function (documentItem) {
                                        return Number(documentItem && documentItem.id || 0) === documentId
                                            && documentItem.canDelete === true;
                                    });
                                });
                            };

                            const selectedDocumentsCanBeMoved = function (selectedDocumentIds) {
                                return selectedDocumentIds.every(function (documentId) {
                                    return documents.some(function (documentItem) {
                                        return Number(documentItem && documentItem.id || 0) === documentId
                                            && documentItem.canMove === true;
                                    });
                                });
                            };

                            const syncDocumentSelection = function () {
                                const selectedDocumentIds = getSelectedDocumentIds();
                                const selectedCount = selectedDocumentIds.length;
                                const bulkActions = panel.querySelector('[data-omo-documents-bulk-actions]');
                                const bulkCount = panel.querySelector('[data-omo-documents-bulk-count]');
                                const moveButton = panel.querySelector('[data-omo-documents-bulk-action="move"]');
                                const deleteButton = panel.querySelector('[data-omo-documents-bulk-action="delete"]');

                                if (bulkActions) {
                                    bulkActions.hidden = selectedCount === 0;
                                }
                                if (bulkCount) {
                                    bulkCount.textContent = formatSelectionText(
                                        <?= json_encode(omoDocumentsScopeT('documents.selection.count'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                        {count: selectedCount}
                                    );
                                }
                                if (moveButton) {
                                    moveButton.hidden = selectedCount === 0 || !selectedDocumentsCanBeMoved(selectedDocumentIds);
                                }
                                if (deleteButton) {
                                    deleteButton.hidden = selectedCount === 0 || !selectedDocumentsCanBeDeleted(selectedDocumentIds);
                                }

                                results.querySelectorAll('[data-omo-document-select]').forEach(function (input) {
                                    const documentId = Number(input.value || 0);
                                    input.checked = Number.isInteger(documentId) && state.selectedDocumentIds.has(documentId);
                                    const documentNode = input.closest('.omo-documents__item');
                                    if (documentNode) {
                                        documentNode.classList.toggle(
                                            'is-selected',
                                            Number.isInteger(documentId) && state.selectedDocumentIds.has(documentId)
                                        );
                                    }
                                });
                            };

                            const createDocumentSelectionControl = function (documentItem) {
                                if (!documentItem || (!documentItem.canArchive && !documentItem.canMove)) {
                                    return null;
                                }

                                const documentId = Number(documentItem.id || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return null;
                                }

                                const control = document.createElement('label');
                                control.className = 'omo-documents__selection-control';
                                const input = document.createElement('input');
                                input.type = 'checkbox';
                                input.value = String(documentId);
                                input.checked = state.selectedDocumentIds.has(documentId);
                                input.setAttribute('data-omo-document-select', '1');
                                input.setAttribute('data-document-can-delete', documentItem.canDelete ? '1' : '0');
                                input.setAttribute('aria-label', <?= json_encode(omoDocumentsScopeT('documents.selection.toggle'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
                                control.appendChild(input);
                                return control;
                            };

                            const createDocumentSelectionPlaceholder = function () {
                                const placeholder = document.createElement('span');
                                placeholder.className = 'omo-documents__selection-placeholder';
                                placeholder.setAttribute('aria-hidden', 'true');
                                return placeholder;
                            };

                            const postBulkDocumentAction = function (action) {
                                return fetch('/omo/api/documents/lifecycle_action.php', {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({ids: getSelectedDocumentIds(), action: action})
                                }).then(function (response) {
                                    return response.json().then(function (payload) {
                                        if (!response.ok || !payload || payload.status !== true) {
                                            throw new Error(String(payload && payload.message || <?= json_encode(omoDocumentsScopeT('documents.menu.action_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>));
                                        }
                                        return payload;
                                    });
                                });
                            };

                            window.addEventListener('omo-documents-bulk-move-complete', function (event) {
                                const completedIds = event && event.detail && Array.isArray(event.detail.ids)
                                    ? event.detail.ids
                                    : [];
                                completedIds.forEach(function (documentId) {
                                    const normalizedId = Number(documentId || 0);
                                    if (Number.isInteger(normalizedId) && normalizedId > 0) {
                                        state.selectedDocumentIds.delete(normalizedId);
                                    }
                                });
                                syncDocumentSelection();
                            });

                            const appendDocumentCardContent = function (container, documentItem, options) {
                                const settings = options && typeof options === 'object' ? options : {};

                                if (!container) {
                                    return;
                                }

                                if (state.density === 'compact') {
                                    container.classList.add('omo-documents__item--compact');
                                }

                                if (settings.interactive) {
                                    container.classList.add('omo-card--interactive');
                                    container.setAttribute('role', 'button');
                                    container.setAttribute('tabindex', '0');
                                    container.setAttribute('data-omo-document-id', documentItem.id || '');
                                    container.setAttribute('data-omo-document-href', documentItem.href || '');
                                    container.setAttribute('data-omo-document-context-url', documentItem.contextUrl || '');
                                    container.setAttribute('data-omo-document-external-url', documentItem.externalUrl || '');
                                    container.setAttribute('data-omo-document-open-in-new-window', documentItem.openInNewWindow ? '1' : '0');
                                    container.setAttribute('data-omo-document-type', documentItem.documentType || '');
                                    container.setAttribute('data-omo-document-pv-editor-url', documentItem.pvPreparationUrl || '');
                                    container.setAttribute('data-omo-document-title', documentItem.title || '');
                                    container.setAttribute('data-omo-document-full-date', documentItem.fullDateLabel || '');
                                }

                                container.classList.add(
                                    documentItem.isFolder
                                        ? 'omo-documents__item--folder-card'
                                        : 'omo-documents__item--file-card'
                                );
                                container.classList.toggle('omo-documents__item--missing-upload', !!documentItem.isMissingUploadedFile);

                                const frame = document.createElement('div');
                                frame.className = 'omo-documents__item-frame omo-documents__item-frame--with-selection';
                                const selectionControl = createDocumentSelectionControl(documentItem);
                                frame.appendChild(selectionControl || createDocumentSelectionPlaceholder());

                                const visual = document.createElement('div');
                                visual.className = 'omo-documents__visual';

                                const iconBox = document.createElement('div');
                                iconBox.className = 'omo-documents__icon-box generic-file-list__icon-box';

                                const icon = document.createElement('img');
                                icon.className = 'omo-documents__icon black-icon';
                                icon.src = omoDocumentsGetIconUrl(documentItem);
                                icon.alt = omoDocumentsGetIconAlt(documentItem);
                                icon.loading = 'lazy';

                                iconBox.appendChild(icon);
                                if (documentItem.isExternalLink) {
                                    appendExternalFaviconBadge(iconBox, documentItem.externalUrl || '');
                                }
                                visual.appendChild(iconBox);

                                const content = document.createElement('div');
                                content.className = 'omo-documents__content';

                                const keywordList = String(documentItem.keywords || '')
                                    .split(',')
                                    .map(function (keyword) {
                                        return String(keyword || '').trim();
                                    })
                                    .filter(function (keyword) {
                                        return keyword !== '';
                                    });

                                if (state.density === 'compact') {
                                    const frame = document.createElement('div');
                                    frame.className = 'omo-documents__item-frame omo-documents__item-frame--compact-list generic-file-list__row';
                                    const showsContext = omoDocumentsNormalizeScope(panel.getAttribute('data-omo-document-scope') || 'contextual') !== 'contextual';

                                    const compactNameCell = document.createElement('div');
                                    compactNameCell.className = 'omo-documents__compact-cell omo-documents__compact-cell--name generic-file-list__cell generic-file-list__cell--name';

                                    const compactNameMain = document.createElement('div');
                                    compactNameMain.className = 'omo-documents__compact-name-main generic-file-list__name-main';

                                    const compactTitleBlock = document.createElement('div');
                                    compactTitleBlock.className = 'omo-documents__compact-title-block generic-file-list__title-block';

                                    const compactTitleStack = document.createElement('div');
                                    compactTitleStack.className = 'omo-documents__compact-title-stack generic-file-list__title-row';

                                    const compactTitle = document.createElement('strong');
                                    compactTitle.className = 'omo-documents__compact-title generic-file-list__title';
                                    compactTitle.textContent = documentItem.listTitle || documentItem.title || '';
                                    compactTitleStack.appendChild(compactTitle);
                                    if (documentItem.isMissingUploadedFile) {
                                        const compactMissingUpload = document.createElement('span');
                                        compactMissingUpload.className = 'omo-documents__missing-upload-badge';
                                        compactMissingUpload.textContent = omoDocumentsMissingUploadedFileLabel;
                                        compactTitleStack.appendChild(compactMissingUpload);
                                    }
                                    const compactScopeCapsule = createVisibilityCapsule(documentItem);
                                    if (compactScopeCapsule) {
                                        compactTitleStack.appendChild(compactScopeCapsule);
                                    }

                                    if (documentItem.isFolder) {
                                        const count = Array.isArray(documentItem.children) ? documentItem.children.length : 0;
                                        const compactCount = document.createElement('span');
                                        compactCount.className = 'omo-documents__compact-count generic-file-list__count';
                                        compactCount.textContent = count > 0
                                            ? String(count) + ' element' + (count > 1 ? 's' : '')
                                            : 'Vide';
                                        compactTitleStack.appendChild(compactCount);
                                    }

                                    compactTitleBlock.appendChild(compactTitleStack);

                                    if (showsContext) {
                                        let compactContextLine = null;

                                        if (Array.isArray(documentItem.contextBreadcrumb) && documentItem.contextBreadcrumb.length > 0) {
                                            compactContextLine = document.createElement('div');
                                            compactContextLine.className = 'omo-documents__compact-context-line generic-file-list__meta-line';

                                            documentItem.contextBreadcrumb.forEach(function (breadcrumbItem, breadcrumbIndex) {
                                                if (breadcrumbIndex > 0) {
                                                    const separator = document.createElement('span');
                                                    separator.className = 'omo-documents__compact-context-separator';
                                                    separator.textContent = '>';
                                                    compactContextLine.appendChild(separator);
                                                }

                                                if (settings.plainContext === true) {
                                                    const contextText = document.createElement('span');
                                                    contextText.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                                    compactContextLine.appendChild(contextText);
                                                } else {
                                                    const contextButton = document.createElement('button');
                                                    contextButton.type = 'button';
                                                    contextButton.className = 'omo-documents__context-link omo-documents__context-link--compact';
                                                    contextButton.setAttribute('data-omo-document-context-jump', '1');
                                                    contextButton.setAttribute('data-omo-document-context-jump-oid', String(breadcrumbItem && breadcrumbItem.organizationId ? breadcrumbItem.organizationId : ''));
                                                    contextButton.setAttribute('data-omo-document-context-jump-cid', String(breadcrumbItem && breadcrumbItem.holonId ? breadcrumbItem.holonId : '0'));
                                                    contextButton.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                                    compactContextLine.appendChild(contextButton);
                                                }
                                            });
                                        } else if (documentItem.contextLabel) {
                                            compactContextLine = document.createElement('div');
                                            compactContextLine.className = 'omo-documents__compact-context-line generic-file-list__meta-line';
                                            compactContextLine.textContent = documentItem.contextLabel;
                                        }

                                        if (compactContextLine) {
                                            const compactContextInline = document.createElement('div');
                                            compactContextInline.className = 'omo-documents__compact-context-inline';
                                            compactContextInline.appendChild(compactContextLine);
                                            compactTitleBlock.appendChild(compactContextInline);
                                        }
                                    }

                                    compactNameMain.appendChild(selectionControl || createDocumentSelectionPlaceholder());
                                    compactNameMain.appendChild(visual);
                                    compactNameMain.appendChild(compactTitleBlock);
                                    compactNameCell.appendChild(compactNameMain);

                                    const compactTagsCell = document.createElement('div');
                                    compactTagsCell.className = 'omo-documents__compact-cell omo-documents__compact-cell--tags generic-file-list__cell';
                                    compactTagsCell.setAttribute('data-label', 'Tags');
                                    const compactTags = document.createElement('div');
                                    compactTags.className = 'omo-documents__compact-tags generic-file-list__tag-list';

                                    if (keywordList.length > 0) {
                                        keywordList.forEach(function (keyword) {
                                            const keywordTag = document.createElement('span');
                                            keywordTag.className = 'omo-documents__keyword-tag generic-file-list__tag';
                                            keywordTag.textContent = '#' + keyword.replace(/^#+/, '');
                                            compactTags.appendChild(keywordTag);
                                        });
                                    } else {
                                        compactTags.classList.add('omo-documents__compact-tags--empty');
                                        compactTags.textContent = '-';
                                    }

                                    compactTagsCell.appendChild(compactTags);

                                    const compactDateCell = document.createElement('div');
                                    compactDateCell.className = 'omo-documents__compact-cell omo-documents__compact-cell--date generic-file-list__cell generic-file-list__cell--date';
                                    compactDateCell.setAttribute('data-label', getDateColumnLabel(state.sort));
                                    compactDateCell.textContent = state.sort === 'alpha'
                                        ? getDocumentDateLabel(documentItem, 'updated', true)
                                        : getDocumentDateLabel(documentItem, state.sort, false);

                                    frame.appendChild(compactNameCell);
                                    frame.appendChild(compactTagsCell);
                                    frame.appendChild(compactDateCell);
                                    container.appendChild(frame);
                                    return;
                                }

                                const eyebrow = document.createElement('div');
                                eyebrow.className = 'omo-documents__eyebrow';

                                const date = document.createElement('span');
                                date.className = 'omo-documents__date';
                                date.textContent = state.sort === 'alpha'
                                    ? getDocumentDateLabel(documentItem, 'updated', true)
                                    : getDocumentDateLabel(documentItem, state.sort, false);

                                if (documentItem.isFolder) {
                                    const count = Array.isArray(documentItem.children) ? documentItem.children.length : 0;
                                    const countLabel = document.createElement('span');
                                    countLabel.className = 'omo-documents__kind-detail';
                                    countLabel.textContent = count > 0
                                        ? String(count) + ' element' + (count > 1 ? 's' : '')
                                        : 'Vide';
                                    eyebrow.appendChild(countLabel);
                                } else if (String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsPvType) {
                                    const countLabel = document.createElement('span');
                                    countLabel.className = 'omo-documents__kind-detail';
                                    countLabel.textContent = 'PV';
                                    eyebrow.appendChild(countLabel);
                                }

                                eyebrow.appendChild(date);

                                const head = document.createElement('div');
                                head.className = 'omo-documents__item-head';
                                const titleLine = document.createElement('span');
                                titleLine.className = 'omo-documents__title-line';
                                const title = document.createElement('strong');
                                title.className = 'omo-documents__title generic-title generic-title--item';
                                title.textContent = documentItem.listTitle || documentItem.title || '';
                                titleLine.appendChild(title);
                                if (documentItem.isMissingUploadedFile) {
                                    const missingUpload = document.createElement('span');
                                    missingUpload.className = 'omo-documents__missing-upload-badge';
                                    missingUpload.textContent = omoDocumentsMissingUploadedFileLabel;
                                    titleLine.appendChild(missingUpload);
                                }
                                const scopeCapsule = createVisibilityCapsule(documentItem);
                                if (scopeCapsule) {
                                    titleLine.appendChild(scopeCapsule);
                                }
                                head.appendChild(titleLine);

                                content.appendChild(eyebrow);
                                content.appendChild(head);

                                if (Array.isArray(documentItem.contextBreadcrumb) && documentItem.contextBreadcrumb.length > 0) {
                                    const context = document.createElement('div');
                                    context.className = 'omo-documents__context';
                                    context.setAttribute('aria-label', 'Contexte du document');

                                    documentItem.contextBreadcrumb.forEach(function (breadcrumbItem, breadcrumbIndex) {
                                        if (breadcrumbIndex > 0) {
                                            const separator = document.createElement('span');
                                            separator.className = 'omo-documents__context-separator';
                                            separator.textContent = '›';
                                            context.appendChild(separator);
                                        }

                                        if (settings.plainContext === true) {
                                            const contextText = document.createElement('span');
                                            contextText.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                            context.appendChild(contextText);
                                        } else {
                                            const contextButton = document.createElement('button');
                                            contextButton.type = 'button';
                                            contextButton.className = 'omo-documents__context-link';
                                            contextButton.setAttribute('data-omo-document-context-jump', '1');
                                            contextButton.setAttribute('data-omo-document-context-jump-oid', String(breadcrumbItem && breadcrumbItem.organizationId ? breadcrumbItem.organizationId : ''));
                                            contextButton.setAttribute('data-omo-document-context-jump-cid', String(breadcrumbItem && breadcrumbItem.holonId ? breadcrumbItem.holonId : '0'));
                                            contextButton.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                            context.appendChild(contextButton);
                                        }
                                    });

                                    content.appendChild(context);
                                } else if (documentItem.contextLabel) {
                                    const context = document.createElement('div');
                                    context.className = 'omo-documents__context';
                                    context.textContent = documentItem.contextLabel;
                                    content.appendChild(context);
                                }

                                if (state.density !== 'compact' && documentItem.description) {
                                    const description = document.createElement('p');
                                    description.className = 'omo-documents__description generic-description';
                                    description.textContent = documentItem.description;
                                    content.appendChild(description);
                                }

                                if (state.density !== 'compact' && documentItem.keywords) {
                                    const keywords = document.createElement('div');
                                    keywords.className = 'omo-documents__keywords';
                                    keywordList.forEach(function (keyword) {
                                        const keywordTag = document.createElement('span');
                                        keywordTag.className = 'omo-documents__keyword-tag';
                                        keywordTag.textContent = '#' + keyword.replace(/^#+/, '');
                                        keywords.appendChild(keywordTag);
                                    });
                                    content.appendChild(keywords);
                                }

                                if (selectionControl) {
                                    frame.appendChild(selectionControl);
                                }
                                frame.appendChild(visual);
                                frame.appendChild(content);
                                container.appendChild(frame);
                            };

                            const createMenu = function (documentItem) {
                                if (
                                    (!documentItem.canEdit || !documentItem.editUrl)
                                    && !documentItem.canMove
                                    && !documentItem.canArchive
                                    && !documentItem.canDelete
                                    && !documentItem.canShare
                                    && !documentItem.canExportPdf
                                ) {
                                    return null;
                                }

                                const menu = document.createElement('div');
                                menu.className = 'omo-documents__menu generic-menu generic-file-list__menu';
                                menu.setAttribute('data-omo-document-menu', '1');

                                const toggle = document.createElement('button');
                                toggle.type = 'button';
                                toggle.className = 'omo-documents__menu-toggle generic-menu-toggle generic-file-list__menu-toggle';
                                toggle.setAttribute('data-omo-document-menu-toggle', '1');
                                toggle.setAttribute('data-omo-document-menu-document-id', String(documentItem.id || '0'));
                                toggle.setAttribute('data-omo-document-menu-title', String(documentItem.title || ''));
                                toggle.setAttribute('data-omo-document-menu-edit-url', String(documentItem.editUrl || ''));
                                toggle.setAttribute('data-omo-document-menu-can-edit', documentItem.canEdit ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-move', documentItem.canMove ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-archive', documentItem.canArchive ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-delete', documentItem.canDelete ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-is-folder', documentItem.isFolder ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-share', documentItem.canShare ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-export-pdf', documentItem.canExportPdf ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-pdf-url', String(documentItem.pdfExportUrl || ''));
                                toggle.setAttribute('aria-haspopup', 'menu');
                                toggle.setAttribute('aria-expanded', 'false');
                                toggle.setAttribute('aria-label', 'Actions pour ' + String(documentItem.title || 'ce document'));
                                toggle.textContent = '...';
                                menu.appendChild(toggle);

                                return menu;
                            };

                            const createCompactListHeader = function (sortMode) {
                                const header = document.createElement('div');
                                header.className = 'omo-documents__list-header generic-file-list__header';

                                [
                                    { label: 'Nom', className: 'omo-documents__list-header-cell--name' },
                                    { label: 'Tags', className: 'omo-documents__list-header-cell--tags' },
                                    { label: getDateColumnLabel(sortMode), className: 'omo-documents__list-header-cell--date' }
                                ].forEach(function (column) {
                                    const cell = document.createElement('div');
                                    cell.className = 'omo-documents__list-header-cell generic-file-list__header-cell ' + column.className;
                                    cell.textContent = column.label;
                                    header.appendChild(cell);
                                });

                                return header;
                            };

                            const createItem = function (documentItem) {
                                const shell = document.createElement('article');
                                shell.className = 'omo-documents__item-shell generic-file-list__item-shell';

                                if (state.density === 'compact') {
                                    shell.classList.add('omo-documents__item-shell--compact');
                                }
                                if (documentItem.isMissingUploadedFile) {
                                    shell.classList.add('omo-documents__item-shell--missing-upload');
                                }

                                if (documentItem.isFolder) {
                                    shell.classList.add('omo-documents__item-shell--folder', 'generic-file-list__item-shell--folder');
                                    const folderId = Number(documentItem.id || 0);
                                    const isExpanded = normalizeQuickSearch(state.query) !== ''
                                        ? Array.isArray(documentItem.children) && documentItem.children.length > 0
                                        : Number.isInteger(folderId)
                                            && folderId > 0
                                            && state.openFolderIds.has(folderId);

                                    const accordion = document.createElement('div');
                                    accordion.className = 'generic-accordion generic-accordion--collapsible omo-documents__folder'
                                        + (isExpanded ? '' : ' is-collapsed');
                                    accordion.setAttribute('data-generic-accordion', '1');
                                    accordion.setAttribute('data-omo-document-folder', String(documentItem.id || '0'));

                                    const header = document.createElement('div');
                                    header.className = 'generic-accordion__header omo-documents__folder-header generic-file-list__folder-header';

                                    const headerToggle = document.createElement('div');
                                    headerToggle.className = 'omo-documents__folder-toggle generic-file-list__folder-toggle';
                                    headerToggle.setAttribute('data-generic-accordion-toggle', '1');
                                    headerToggle.setAttribute('data-omo-document-folder-toggle', '1');
                                    headerToggle.setAttribute('tabindex', '0');
                                    headerToggle.setAttribute('role', 'button');
                                    headerToggle.setAttribute('aria-expanded', 'false');

                                    const folderCard = document.createElement('div');
                                    folderCard.className = 'omo-documents__item omo-card omo-documents__folder-card';
                                    appendDocumentCardContent(folderCard, documentItem, { interactive: false, plainContext: true });

                                    const folderChevron = document.createElement('span');
                                    folderChevron.className = 'generic-accordion__toggle omo-documents__folder-chevron generic-file-list__folder-chevron';
                                    folderChevron.textContent = '▾';

                                    const folderMenu = createMenu(documentItem);
                                    headerToggle.appendChild(folderCard);
                                    headerToggle.appendChild(folderChevron);
                                    header.appendChild(headerToggle);

                                    if (folderMenu) {
                                        shell.classList.add('omo-documents__item-shell--has-menu', 'generic-file-list__item-shell--with-menu');
                                        folderMenu.classList.add('omo-documents__menu--folder-header');
                                        header.appendChild(folderMenu);
                                    }

                                    const content = document.createElement('div');
                                    content.className = 'generic-accordion__content omo-documents__folder-content generic-file-list__folder-content';

                                    if (Array.isArray(documentItem.children) && documentItem.children.length > 0) {
                                        const childList = document.createElement('div');
                                        childList.className = 'omo-documents__folder-children generic-file-list__children';

                                        if (state.density === 'compact') {
                                            childList.classList.add('omo-documents__folder-children--compact');
                                        }

                                        documentItem.children.forEach(function (childDocument) {
                                            childList.appendChild(createItem(childDocument));
                                        });

                                        content.appendChild(childList);
                                    } else {
                                        const emptyFolder = document.createElement('div');
                                        emptyFolder.className = 'omo-documents__folder-empty generic-file-list__empty';
                                        emptyFolder.textContent = 'Dossier vide.';
                                        content.appendChild(emptyFolder);
                                    }

                                    accordion.appendChild(header);
                                    accordion.appendChild(content);
                                    shell.appendChild(accordion);
                                } else {
                                    const link = document.createElement('div');
                                    link.className = 'omo-documents__item omo-card';
                                    appendDocumentCardContent(link, documentItem, { interactive: true });
                                    shell.appendChild(link);
                                }

                                const menu = documentItem.isFolder ? null : createMenu(documentItem);
                                if (menu) {
                                    shell.classList.add('omo-documents__item-shell--has-menu', 'generic-file-list__item-shell--with-menu');
                                    shell.appendChild(menu);
                                }

                                return shell;
                            };

                            const persistOpenFolderState = function () {
                                omoDocumentsWriteSessionCookie(
                                    folderStateCookieName,
                                    Array.from(state.openFolderIds).join(',')
                                );
                            };

                            const syncFolderAccordionState = function (persistState) {
                                const nextOpenFolderIds = new Set();

                                results.querySelectorAll('[data-omo-document-folder-toggle]').forEach(function (toggle) {
                                    const accordion = toggle.closest('[data-generic-accordion]');
                                    const isExpanded = !!accordion && !accordion.classList.contains('is-collapsed');
                                    const folderId = accordion
                                        ? Number(accordion.getAttribute('data-omo-document-folder') || 0)
                                        : 0;
                                    toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');

                                    if (isExpanded && Number.isInteger(folderId) && folderId > 0) {
                                        nextOpenFolderIds.add(folderId);
                                    }
                                });

                                if (normalizeQuickSearch(state.query) === '') {
                                    state.openFolderIds = nextOpenFolderIds;
                                }

                                if (persistState === true && normalizeQuickSearch(state.query) === '') {
                                    persistOpenFolderState();
                                }
                            };

                            const setDetailHeader = function (documentItem) {
                                if (!detailTitle || !detailDescription) {
                                    return;
                                }

                                if (detailDrawerController) {
                                    detailDrawerController.setHeader({
                                        title: documentItem && documentItem.title
                                            ? documentItem.title
                                            : <?= json_encode(omoDocumentsScopeT('documents.drawer.detail_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                        description: documentItem && documentItem.fullDateLabel
                                            ? 'Document créé le ' + documentItem.fullDateLabel + '.'
                                            : <?= json_encode(omoDocumentsScopeT('documents.drawer.detail_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                        actions: []
                                    });
                                    return;
                                }

                                detailTitle.textContent = documentItem && documentItem.title
                                    ? documentItem.title
                                    : 'Détail du document';
                                detailDescription.textContent = documentItem && documentItem.fullDateLabel
                                    ? 'Document créé le ' + documentItem.fullDateLabel + '.'
                                    : <?= json_encode(omoDocumentsScopeT('documents.drawer.detail_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                            };

                            const openDetailDrawer = function () {
                                if (!detailDrawer) {
                                    return;
                                }

                                detailDrawer.hidden = false;

                                requestAnimationFrame(function () {
                                    detailDrawer.classList.add('is-open');
                                });
                            };

                            const clearCollaboraTokenRefresh = function () {
                                if (!detailDrawer || typeof detailDrawer.__omoCollaboraTokenRefreshCleanup !== 'function') {
                                    return;
                                }

                                detailDrawer.__omoCollaboraTokenRefreshCleanup();
                                delete detailDrawer.__omoCollaboraTokenRefreshCleanup;
                            };

                            const setupCollaboraTokenRefresh = function () {
                                clearCollaboraTokenRefresh();
                                if (!detailDrawer || !detailBody) {
                                    return;
                                }

                                const frame = detailBody.querySelector('.omo-document-collabora__frame');
                                const detail = detailBody.querySelector('[data-omo-collabora-origin]');
                                const documentId = frame instanceof HTMLIFrameElement
                                    ? Number(frame.getAttribute('data-omo-collabora-document-id') || 0)
                                    : 0;
                                const collaboraOrigin = detail instanceof HTMLElement
                                    ? String(detail.getAttribute('data-omo-collabora-origin') || '').trim()
                                    : '';

                                if (!(frame instanceof HTMLIFrameElement) || !Number.isInteger(documentId) || documentId <= 0 || collaboraOrigin === '') {
                                    return;
                                }

                                let refreshTimer = 0;
                                let refreshInFlight = false;
                                let editorReady = false;

                                const clearRefreshTimer = function () {
                                    if (refreshTimer) {
                                        window.clearTimeout(refreshTimer);
                                        refreshTimer = 0;
                                    }
                                };

                                const postToCollabora = function (messageId, values) {
                                    if (!frame.contentWindow || !document.body.contains(frame)) {
                                        return false;
                                    }

                                    frame.contentWindow.postMessage(JSON.stringify({
                                        MessageId: messageId,
                                        SendTime: Date.now(),
                                        Values: values || {},
                                    }), collaboraOrigin);
                                    return true;
                                };

                                const scheduleRefresh = function (delay) {
                                    clearRefreshTimer();
                                    refreshTimer = window.setTimeout(function () {
                                        refreshWopiToken();
                                    }, Math.max(60000, Number(delay) || 0));
                                };

                                const refreshWopiToken = function () {
                                    if (refreshInFlight || !editorReady || !document.body.contains(frame)) {
                                        return;
                                    }

                                    refreshInFlight = true;
                                    const body = new URLSearchParams();
                                    body.set('id', String(documentId));

                                    fetch('/omo/api/documents/collabora/token.php', {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                        },
                                        body: body.toString(),
                                    })
                                        .then(function (response) {
                                            if (!response.ok) {
                                                throw new Error('Unable to renew the Collabora token.');
                                            }
                                            return response.json();
                                        })
                                        .then(function (payload) {
                                            const accessToken = payload && typeof payload.accessToken === 'string'
                                                ? payload.accessToken.trim()
                                                : '';
                                            if (!payload || payload.status !== true || accessToken === '' || !postToCollabora('Reset_Access_Token', {token: accessToken})) {
                                                throw new Error('Invalid Collabora token renewal response.');
                                            }

                                            const expiresAt = Number(payload.expiresAt || 0) * 1000;
                                            const refreshDelay = Number.isFinite(expiresAt)
                                                ? expiresAt - Date.now() - (15 * 60 * 1000)
                                                : 30 * 60 * 1000;
                                            scheduleRefresh(refreshDelay);
                                        })
                                        .catch(function () {
                                            if (document.body.contains(frame)) {
                                                scheduleRefresh(60 * 1000);
                                            }
                                        })
                                        .finally(function () {
                                            refreshInFlight = false;
                                        });
                                };

                                const onCollaboraMessage = function (event) {
                                    if (event.origin !== collaboraOrigin || event.source !== frame.contentWindow) {
                                        return;
                                    }

                                    let message = event.data;
                                    if (typeof message === 'string') {
                                        try {
                                            message = JSON.parse(message);
                                        } catch (error) {
                                            return;
                                        }
                                    }

                                    if (!message || typeof message !== 'object') {
                                        return;
                                    }

                                    const messageId = String(message.MessageId || '').trim();
                                    const values = message.Values && typeof message.Values === 'object' ? message.Values : {};
                                    if (messageId === 'App_LoadingStatus' && values.Status === 'Document_Loaded') {
                                        editorReady = postToCollabora('Host_PostmessageReady');
                                        if (editorReady) {
                                            scheduleRefresh(20 * 60 * 1000);
                                        }
                                        return;
                                    }

                                    if (messageId === 'App_TokenExpired') {
                                        refreshWopiToken();
                                    }
                                };

                                window.addEventListener('message', onCollaboraMessage);
                                detailDrawer.__omoCollaboraTokenRefreshCleanup = function () {
                                    clearRefreshTimer();
                                    window.removeEventListener('message', onCollaboraMessage);
                                };
                            };

                            const closeDetailDrawer = function () {
                                if (!detailDrawer) {
                                    return;
                                }

                                clearCollaboraTokenRefresh();
                                detailDrawer.classList.remove('is-open');
                                detailDrawer.dataset.omoDocumentActiveId = '';
                                state.activeDocumentId = null;

                                window.setTimeout(function () {
                                    if (!detailDrawer.classList.contains('is-open')) {
                                        detailDrawer.hidden = true;
                                    }
                                }, 200);
                            };

                            if (detailDrawer) {
                                const notifyEthercalcFrameResize = function () {
                                    const frame = detailDrawer.querySelector('.omo-document-ethercalc__frame');
                                    if (!(frame instanceof HTMLIFrameElement) || !frame.contentWindow) {
                                        return;
                                    }

                                    [0, 80, 260, 700].forEach(function (delay) {
                                        window.setTimeout(function () {
                                            const bounds = frame.getBoundingClientRect();
                                            frame.contentWindow.postMessage({
                                                type: 'omo-ethercalc-resize',
                                                viewport: {
                                                    width: Math.round(bounds.width),
                                                    height: Math.round(bounds.height),
                                                },
                                            }, '*');
                                        }, delay);
                                    });
                                };

                                const syncDocumentFullscreenButton = function () {
                                    const fullscreenButton = detailDrawer.querySelector('[data-omo-document-fullscreen]');
                                     const frame = detailDrawer.querySelector('.omo-document-etherpad__frame, .omo-document-ethercalc__frame, .omo-document-collabora__frame, .omo-document-spacedeck__frame');
                                    if (!(fullscreenButton instanceof HTMLButtonElement) || !(frame instanceof HTMLElement)) {
                                        return;
                                    }

                                    const isFullscreen = document.fullscreenElement === frame;
                                    fullscreenButton.setAttribute('aria-pressed', isFullscreen ? 'true' : 'false');
                                    fullscreenButton.textContent = isFullscreen
                                        ? (fullscreenButton.dataset.omoDocumentExitFullscreenLabel || '')
                                        : (fullscreenButton.dataset.omoDocumentFullscreenLabel || '');

                                    if (frame.classList.contains('omo-document-ethercalc__frame')) {
                                        notifyEthercalcFrameResize();
                                    }
                                };

                                document.addEventListener('fullscreenchange', syncDocumentFullscreenButton);
                                detailDrawer.addEventListener('click', function (event) {
                                    const fullscreenButton = event.target.closest('[data-omo-document-fullscreen]');
                                    if (fullscreenButton) {
                                        event.preventDefault();
                                         const frame = detailDrawer.querySelector('.omo-document-etherpad__frame, .omo-document-ethercalc__frame, .omo-document-collabora__frame, .omo-document-spacedeck__frame');
                                        if (!(frame instanceof HTMLElement)) {
                                            return;
                                        }

                                        if (document.fullscreenElement === frame) {
                                            document.exitFullscreen().catch(function () {});
                                        } else if (typeof frame.requestFullscreen === 'function') {
                                            frame.requestFullscreen().then(function () {
                                                if (frame.classList.contains('omo-document-ethercalc__frame')) {
                                                    notifyEthercalcFrameResize();
                                                }
                                            }).catch(function () {});
                                        }
                                        return;
                                    }

                                    const editButton = event.target.closest('[data-omo-document-open-editor-url]');
                                    if (!editButton) {
                                        return;
                                    }

                                    event.preventDefault();
                                    const editUrl = String(editButton.getAttribute('data-omo-document-open-editor-url') || '').trim();
                                    if (editUrl !== '' && typeof window.omoOpenDocumentEditorDrawer === 'function') {
                                        window.omoOpenDocumentEditorDrawer(editUrl, 'Modifier le document', 'Modification du document dans le contexte courant.');
                                    }
                                });
                            }

                            const renderDetailLoading = function () {
                                clearCollaboraTokenRefresh();
                                if (!detailBody) {
                                    return;
                                }

                                detailBody.innerHTML = window.getSkeleton
                                    ? getSkeleton('panel')
                                    : '<div class="loading"><?= $escape(omoDocumentsScopeT('documents.action.loading')) ?></div>';
                            };

                            const renderDetailError = function () {
                                clearCollaboraTokenRefresh();
                                if (!detailBody) {
                                    return;
                                }

                                detailBody.innerHTML = '<div class="loading"><div class="omo-empty-state"><?= $escape(omoDocumentsScopeT('documents.error.load_document')) ?></div></div>';
                            };

                            const openExternalDocumentWindow = function (documentItem) {
                                const externalUrl = documentItem && documentItem.externalUrl
                                    ? String(documentItem.externalUrl).trim()
                                    : '';

                                if (externalUrl === '') {
                                    return false;
                                }

                                const link = document.createElement('a');
                                link.href = externalUrl;
                                link.target = '_blank';
                                link.rel = 'noopener noreferrer';
                                link.style.display = 'none';
                                document.body.appendChild(link);
                                link.click();
                                link.remove();

                                return true;
                            };

                            const openDocumentDetail = function (documentItem) {
                                if (!detailDrawer || !detailBody || !documentItem || !documentItem.id || documentItem.isFolder) {
                                    return;
                                }

                                if (
                                    String(documentItem.documentType || '').trim().toLowerCase() === 'pv'
                                    && String(documentItem.pvPreparationUrl || '').trim() !== ''
                                    && typeof window.omoOpenDocumentPvPreparationByPayload === 'function'
                                    && window.omoOpenDocumentPvPreparationByPayload(documentItem)
                                ) {
                                    return;
                                }

                                if (documentItem.openInNewWindow && documentItem.externalUrl) {
                                    openExternalDocumentWindow(documentItem);
                                    return;
                                }

                                state.activeDocumentId = Number(documentItem.id);
                                detailDrawer.dataset.omoDocumentActiveId = String(documentItem.id);
                                setDetailHeader(documentItem);
                                renderDetailLoading();
                                openDetailDrawer();

                                const requestToken = ++detailRequestToken;
                                const detailUrl = documentItem.contextUrl
                                    ? String(documentItem.contextUrl)
                                    : '/omo/api/documents/detail.php?id=' + encodeURIComponent(documentItem.id);

                                $.ajax({
                                    url: detailUrl,
                                    method: 'GET',
                                    cache: false,
                                    success: function (data) {
                                        if (requestToken !== detailRequestToken || state.activeDocumentId !== Number(documentItem.id)) {
                                            return;
                                        }

                                        detailBody.innerHTML = data;
                                        setupCollaboraTokenRefresh();
                                        const temp = document.createElement('div');
                                        temp.innerHTML = data;
                                        syncDocumentDetailDrawerMetadata(temp, detailDrawer, documentItem.title || '', detailDescription ? detailDescription.textContent : '');
                                        if (detailDrawerController) {
                                            detailDrawerController.applyContentHeader(detailBody);
                                        }
                                    },
                                    error: function () {
                                        if (requestToken !== detailRequestToken) {
                                            return;
                                        }

                                        const responseHtml = arguments.length > 0
                                            && arguments[0]
                                            && typeof arguments[0].responseText === 'string'
                                            ? String(arguments[0].responseText).trim()
                                            : '';

                                        if (responseHtml !== '') {
                                            detailBody.innerHTML = responseHtml;
                                            setupCollaboraTokenRefresh();
                                            return;
                                        }

                                        renderDetailError();
                                    }
                                });
                            };

                            const renderEmptyState = function () {
                                const emptyState = document.createElement('div');
                                emptyState.className = 'omo-documents__empty omo-empty-state';
                                emptyState.textContent = emptyStateMessage;
                                results.replaceChildren(emptyState);
                            };

                            const renderSearchEmptyState = function () {
                                const emptyState = document.createElement('div');
                                emptyState.className = 'omo-documents__empty omo-empty-state';
                                emptyState.textContent = <?= json_encode(omoDocumentsScopeT('documents.search.empty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                                results.replaceChildren(emptyState);
                            };

                            const renderByTemporal = function (sortMode, rootItems) {
                                const fragment = document.createDocumentFragment();
                                const groupedDocuments = new Map();

                                rootItems.forEach(function (documentItem) {
                                    const groupKey = getDocumentGroupKey(documentItem, sortMode);

                                    if (!groupedDocuments.has(groupKey)) {
                                        groupedDocuments.set(groupKey, []);
                                    }

                                    groupedDocuments.get(groupKey).push(documentItem);
                                });

                                groups.forEach(function (group, groupIndex) {
                                    const items = groupedDocuments.get(group.key || '') || [];

                                    if (items.length === 0) {
                                        return;
                                    }

                                    const section = document.createElement('section');
                                    section.className = 'omo-documents__group omo-panel-group generic-file-list__group';

                                    const title = document.createElement('h3');
                                    title.className = 'omo-panel-group__title generic-file-list__group-title';
                                    title.textContent = group.label || '';

                                    const list = document.createElement('div');
                                    list.className = 'omo-documents__list omo-panel-view__body_content';

                                    if (state.density === 'compact') {
                                        list.classList.add('omo-documents__list--compact', 'generic-file-list__table');
                                        list.appendChild(createCompactListHeader(sortMode));
                                    }

                                    items.forEach(function (documentItem) {
                                        list.appendChild(createItem(documentItem));
                                    });

                                    section.appendChild(title);
                                    section.appendChild(list);
                                    fragment.appendChild(section);
                                });

                                results.replaceChildren(fragment);
                                if (typeof window.initGenericComponents === 'function') {
                                    window.initGenericComponents(results);
                                }
                                syncFolderAccordionState(false);
                                syncDocumentSelection();
                            };

                            const renderByAlpha = function (rootItems) {
                                const list = document.createElement('div');
                                list.className = 'omo-documents__list omo-documents__list--alphabetical omo-panel-view__body_content';

                                if (state.density === 'compact') {
                                    list.classList.add('omo-documents__list--compact', 'generic-file-list__table');
                                    list.appendChild(createCompactListHeader('updated'));
                                }

                                rootItems.forEach(function (documentItem) {
                                    list.appendChild(createItem(documentItem));
                                });

                                results.replaceChildren(list);
                                if (typeof window.initGenericComponents === 'function') {
                                    window.initGenericComponents(results);
                                }
                                syncFolderAccordionState(false);
                                syncDocumentSelection();
                            };

                            const syncButtons = function (selector, activeValue, attributeName) {
                                panel.querySelectorAll(selector).forEach(function (button) {
                                    const isActive = button.getAttribute(attributeName) === activeValue;
                                    button.classList.toggle('is-active', isActive);
                                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                                });
                            };

                            const syncFilterChips = function () {
                                const currentScope = omoDocumentsNormalizeScope(
                                    panel.getAttribute('data-omo-document-scope') || 'contextual'
                                );
                                [
                                    {button: '[data-omo-document-scope-toggle="' + currentScope + '"]', chip: '[data-omo-documents-scope-chip]'},
                                    {button: '[data-omo-documents-sort="' + state.sort + '"]', chip: '[data-omo-documents-sort-chip]'},
                                    {button: '[data-omo-documents-density="' + state.density + '"]', chip: '[data-omo-documents-density-chip]'}
                                ].forEach(function (entry) {
                                    const button = panel.querySelector(entry.button);
                                    const chip = panel.querySelector(entry.chip);
                                    if (button && chip) {
                                        chip.textContent = button.textContent.trim();
                                    }
                                });
                            };

                            const render = function () {
                                if (typeof closeDocumentMenus === 'function') {
                                    closeDocumentMenus();
                                }

                                panel.classList.toggle('omo-documents--compact', state.density === 'compact');
                                panel.classList.toggle(
                                    'omo-documents--compact-date-sort',
                                    state.density === 'compact' && state.sort !== 'alpha'
                                );
                                results.classList.toggle('generic-file-list--structured', state.density === 'compact');
                                results.classList.toggle('generic-file-list--stacked-sticky', state.density === 'compact');
                                panel.setAttribute('data-omo-document-sort', state.sort);
                                panel.setAttribute('data-omo-document-density', state.density);
                                const query = normalizeQuickSearch(state.query);
                                const visibleTree = getSortedTree(state.sort, 0);

                                if (documents.length === 0) {
                                    renderEmptyState();
                                    syncDocumentSelection();
                                    syncButtons('[data-omo-documents-sort]', state.sort, 'data-omo-documents-sort');
                                    syncButtons('[data-omo-documents-density]', state.density, 'data-omo-documents-density');
                                    syncFilterChips();
                                    return;
                                }

                                if (query !== '' && visibleTree.length === 0) {
                                    renderSearchEmptyState();
                                } else if (state.sort === 'alpha') {
                                    renderByAlpha(visibleTree);
                                } else {
                                    renderByTemporal(state.sort, visibleTree);
                                }

                                syncButtons('[data-omo-documents-sort]', state.sort, 'data-omo-documents-sort');
                                syncButtons('[data-omo-documents-density]', state.density, 'data-omo-documents-density');
                                syncFilterChips();
                                const headerCount = panel.querySelector('[data-omo-documents-header-count]');
                                if (headerCount) {
                                    headerCount.textContent = query === ''
                                        ? <?= json_encode(
                                            (string)$visibleDocumentsCount
                                            . ($totalDocumentsCount > $visibleDocumentsCount ? ' (' . $totalDocumentsCount . ')' : ''),
                                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                        ) ?>
                                        : String(documents.filter(function (documentItem) {
                                            return documentMatchesQuickSearch(documentItem, query);
                                        }).length);
                                }
                                if (typeof window.syncGenericFileLists === 'function') {
                                    window.syncGenericFileLists(results);
                                }
                            };

                            const findDocumentItemById = function (documentId) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return null;
                                }

                                return documents.find(function (item) {
                                    return Number(item.id || 0) === resolvedDocumentId;
                                }) || null;
                            };

                            const normalizeDocumentOpenMode = function (value) {
                                return String(value || '').trim().toLowerCase() === 'edit'
                                    ? 'edit'
                                    : 'detail';
                            };

                            const buildPanelDocumentOpenUrl = function (documentId, mode, scopeOverride) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return '';
                                }

                                const normalizedMode = normalizeDocumentOpenMode(mode || 'detail');
                                const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
                                const holonId = Number(panel.getAttribute('data-omo-document-cid') || 0);
                                const requestedScope = String(scopeOverride || '').trim().toLowerCase();
                                const normalizedScope = requestedScope !== ''
                                    ? normalizeDocumentScope(requestedScope)
                                    : normalizeDocumentScope(panel.getAttribute('data-omo-document-scope') || 'contextual');
                                const query = [];

                                if (organizationId > 0) {
                                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                                }

                                if (holonId > 0) {
                                    query.push('cid=' + encodeURIComponent(String(holonId)));
                                }

                                query.push('open_document_id=' + encodeURIComponent(String(resolvedDocumentId)));

                                if (normalizedMode === 'edit') {
                                    query.push('open_document_mode=edit');
                                }

                                if (normalizedScope !== 'contextual') {
                                    query.push('document_scope=' + encodeURIComponent(normalizedScope));
                                }

                                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
                            };

                            const buildDirectDocumentPayload = function (documentId) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return null;
                                }

                                const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
                                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                                    return null;
                                }

                                return {
                                    id: resolvedDocumentId,
                                    contextUrl: '/omo/api/documents/detail.php?id='
                                        + encodeURIComponent(String(resolvedDocumentId))
                                        + '&oid='
                                        + encodeURIComponent(String(organizationId)),
                                    title: '',
                                    fullDateLabel: '',
                                    isFolder: false,
                                    openInNewWindow: false,
                                    externalUrl: '',
                                    pvPreparationUrl: ''
                                };
                            };

                            const refreshPanelForDocumentRoute = function (documentId, mode, scopeOverride) {
                                if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                                    return false;
                                }

                                const targetUrl = buildPanelDocumentOpenUrl(documentId, mode, scopeOverride);
                                if (targetUrl === '') {
                                    return false;
                                }

                                window.omoReplaceFetchedPanelRoot({
                                    rootSelector: '#omo-documents-root',
                                    currentRoot: panel,
                                    url: targetUrl,
                                    setLoadingState: function (isLoading) {
                                        if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                            window.omoSetDocumentsPanelLoadingState(panel, isLoading, {
                                                showResultsPlaceholder: true
                                            });
                                        } else {
                                            panel.classList.toggle('is-loading', !!isLoading);
                                        }
                                    }
                                }).catch(function () {
                                    if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                        window.omoSetDocumentsPanelLoadingState(panel, false);
                                    } else {
                                        panel.classList.remove('is-loading');
                                    }
                                });

                                return true;
                            };

                            const openInitialDocumentFromPayload = function () {
                                const documentId = Number(payload.openDocumentId || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return false;
                                }

                                const openMode = normalizeDocumentOpenMode(payload.openDocumentMode || 'detail');

                                const documentItem = findDocumentItemById(documentId)
                                    || (
                                        requestedDocument
                                        && Number(requestedDocument.id || 0) === documentId
                                        ? requestedDocument
                                        : null
                                    );
                                if (!documentItem) {
                                    return false;
                                }

                                let opened = false;
                                if (openMode === 'edit' && typeof window.omoOpenDocumentEditorByPayload === 'function') {
                                    opened = window.omoOpenDocumentEditorByPayload(documentItem, panel) === true;
                                } else if (openMode !== 'edit' && typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                    opened = window.omoOpenDocumentDetailByPayload(documentItem, panel) === true;
                                }

                                if (!opened) {
                                    return false;
                                }

                                payload.openDocumentId = 0;
                                payload.openDocumentMode = 'detail';
                                payload.requestedDocument = null;
                                return true;
                            };

                            const retryInitialDocumentOpen = function (attempt) {
                                if (!document.body.contains(panel)) {
                                    return;
                                }

                                if (openInitialDocumentFromPayload() || attempt >= 8) {
                                    return;
                                }

                                window.setTimeout(function () {
                                    retryInitialDocumentOpen(attempt + 1);
                                }, 50);
                            };

                            const getActiveDisplayFilters = function () {
                                return {
                                    scope: omoDocumentsNormalizeScope(
                                        panel.getAttribute('data-omo-document-scope') || 'contextual'
                                    ),
                                    sort: omoDocumentsNormalizeSortPreference(state.sort),
                                    density: omoDocumentsNormalizeDensityPreference(state.density)
                                };
                            };

                            const normalizeDisplayFilters = function (filters) {
                                const active = getActiveDisplayFilters();
                                let scope = omoDocumentsNormalizeScope(filters && filters.scope);
                                if (!panel.querySelector('[data-omo-document-scope-toggle="' + scope + '"]')) {
                                    scope = active.scope;
                                }
                                return {
                                    scope: scope,
                                    sort: omoDocumentsNormalizeSortPreference(filters && filters.sort),
                                    density: omoDocumentsNormalizeDensityPreference(filters && filters.density)
                                };
                            };

                            const syncFilterChoices = function () {
                                if (!pendingDisplayFilters) {
                                    return;
                                }
                                pendingDisplayFilters = normalizeDisplayFilters(pendingDisplayFilters);
                                [
                                    {selector: '[data-omo-document-scope-toggle]', attribute: 'data-omo-document-scope-toggle', value: pendingDisplayFilters.scope},
                                    {selector: '[data-omo-documents-sort]', attribute: 'data-omo-documents-sort', value: pendingDisplayFilters.sort},
                                    {selector: '[data-omo-documents-density]', attribute: 'data-omo-documents-density', value: pendingDisplayFilters.density}
                                ].forEach(function (choice) {
                                    panel.querySelectorAll(choice.selector).forEach(function (button) {
                                        const active = button.getAttribute(choice.attribute) === choice.value;
                                        button.classList.toggle('is-active', active);
                                        button.setAttribute('aria-pressed', active ? 'true' : 'false');
                                    });
                                });
                            };

                            const applyDisplayFilters = function (filters, active) {
                                const next = normalizeDisplayFilters(filters);
                                const previous = active || getActiveDisplayFilters();
                                state.sort = next.sort;
                                state.density = next.density;

                                if (next.scope !== previous.scope && typeof window.omoSetDocumentsScope === 'function') {
                                    window.omoSetDocumentsScope(next.scope, {panel: panel});
                                    return;
                                }

                                const completeRender = function () {
                                    render();
                                    if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                        window.omoSetDocumentsPanelLoadingState(panel, false);
                                    }
                                };
                                if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                    window.omoSetDocumentsPanelLoadingState(panel, true);
                                    if (typeof window.requestAnimationFrame === 'function') {
                                        window.requestAnimationFrame(completeRender);
                                    } else {
                                        window.setTimeout(completeRender, 0);
                                    }
                                    return;
                                }

                                render();
                            };

                            const closeFilterMoreMenu = function () {
                                panel.querySelectorAll('[data-omo-documents-filter-more-menu]').forEach(function (menu) {
                                    const morePanel = menu.querySelector('[data-omo-documents-filter-more-panel]');
                                    const moreToggle = menu.querySelector('[data-omo-documents-filter-more-toggle]');
                                    if (morePanel) {
                                        morePanel.hidden = true;
                                    }
                                    menu.classList.remove('is-open');
                                    if (moreToggle) {
                                        moreToggle.setAttribute('aria-expanded', 'false');
                                    }
                                });
                            };

                            const handleFilterOutsidePointerDown = function (event) {
                                const control = panel.querySelector('[data-omo-documents-filter-control]');
                                if (control && control.contains(event.target)) {
                                    return;
                                }
                                closeFilterPanel(true, false);
                            };

                            const openFilterPanel = function () {
                                const filterPanel = panel.querySelector('[data-omo-documents-filter-panel]');
                                if (!filterPanel || filterPanelOpen) {
                                    return;
                                }
                                pendingDisplayFilters = getActiveDisplayFilters();
                                closeFilterMoreMenu();
                                syncFilterChoices();
                                filterPanel.hidden = false;
                                filterPanelOpen = true;
                                panel.querySelectorAll('[data-omo-documents-filter-toggle]').forEach(function (button) {
                                    button.setAttribute('aria-expanded', 'true');
                                });
                                document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
                            };

                            const closeFilterPanel = function (applyChanges, saveView) {
                                const filterPanel = panel.querySelector('[data-omo-documents-filter-panel]');
                                if (!filterPanelOpen) {
                                    return;
                                }
                                filterPanelOpen = false;
                                if (filterPanel) {
                                    filterPanel.hidden = true;
                                }
                                panel.querySelectorAll('[data-omo-documents-filter-toggle]').forEach(function (button) {
                                    button.setAttribute('aria-expanded', 'false');
                                });
                                document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
                                closeFilterMoreMenu();

                                if (!applyChanges || !pendingDisplayFilters) {
                                    pendingDisplayFilters = null;
                                    return;
                                }

                                const active = getActiveDisplayFilters();
                                const next = normalizeDisplayFilters(pendingDisplayFilters);
                                pendingDisplayFilters = null;
                                omoDocumentsWritePreferences(panel, next, saveView);
                                applyDisplayFilters(next, active);
                            };

                            const applyFilterMoreAction = function (action) {
                                if (!filterPanelOpen || !pendingDisplayFilters) {
                                    return;
                                }

                                const active = getActiveDisplayFilters();
                                const next = normalizeDisplayFilters(pendingDisplayFilters);
                                closeFilterPanel(false, false);

                                if (action === 'set-default') {
                                    omoDocumentsClearStoredPreferences(panel);
                                    omoDocumentsClearTemporaryPreferences(panel);
                                    omoDocumentsStoreDefaultPreferences(next);
                                    applyDisplayFilters(next, active);
                                    return;
                                }

                                if (action === 'apply-everywhere') {
                                    const store = omoDocumentsGetSavedViewsStore();
                                    store.defaultView = omoDocumentsCreatePreferences(next);
                                    store.contexts = {};
                                    omoDocumentsSaveViewsStore(store);
                                    omoDocumentsClearAllTemporaryPreferences();
                                    applyDisplayFilters(next, active);
                                    return;
                                }

                                if (action === 'restore-default') {
                                    omoDocumentsClearStoredPreferences(panel);
                                    omoDocumentsClearTemporaryPreferences(panel);
                                    const store = omoDocumentsGetSavedViewsStore();
                                    store.defaultView = null;
                                    omoDocumentsSaveViewsStore(store);
                                    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                                        ? window.omoApplicationViewPreferencesGetDefault(root)
                                        : null;
                                    applyDisplayFilters(serverDefault || {
                                        scope: 'contextual',
                                        sort: 'updated',
                                        density: 'detail'
                                    }, active);
                                }
                            };

                            panel.querySelectorAll('[data-omo-documents-filter-toggle]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    if (filterPanelOpen) {
                                        closeFilterPanel(true, false);
                                    } else {
                                        openFilterPanel();
                                    }
                                });
                            });

                            const filterPanel = panel.querySelector('[data-omo-documents-filter-panel]');
                            if (filterPanel) {
                                filterPanel.addEventListener('click', function (event) {
                                    const moreToggle = event.target.closest('[data-omo-documents-filter-more-toggle]');
                                    if (moreToggle) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        const moreMenu = moreToggle.closest('[data-omo-documents-filter-more-menu]');
                                        const morePanel = moreMenu ? moreMenu.querySelector('[data-omo-documents-filter-more-panel]') : null;
                                        const isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                                        closeFilterMoreMenu();
                                        if (!isMoreMenuOpen && morePanel) {
                                            morePanel.hidden = false;
                                            moreMenu.classList.add('is-open');
                                            moreToggle.setAttribute('aria-expanded', 'true');
                                        }
                                        return;
                                    }
                                    const moreAction = event.target.closest('[data-omo-documents-filter-more-action]');
                                    if (moreAction) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        applyFilterMoreAction(moreAction.getAttribute('data-omo-documents-filter-more-action') || '');
                                        return;
                                    }
                                    const applyButton = event.target.closest('[data-omo-documents-filter-apply]');
                                    if (applyButton) {
                                        event.preventDefault();
                                        closeFilterPanel(true, false);
                                        return;
                                    }
                                    const saveButton = event.target.closest('[data-omo-documents-filter-save]');
                                    if (saveButton) {
                                        event.preventDefault();
                                        closeFilterPanel(true, true);
                                        return;
                                    }
                                    const scopeButton = event.target.closest('[data-omo-document-scope-toggle]');
                                    if (scopeButton && pendingDisplayFilters) {
                                        pendingDisplayFilters.scope = omoDocumentsNormalizeScope(
                                            scopeButton.getAttribute('data-omo-document-scope-toggle')
                                        );
                                        syncFilterChoices();
                                        return;
                                    }
                                    const sortButton = event.target.closest('[data-omo-documents-sort]');
                                    if (sortButton && pendingDisplayFilters) {
                                        pendingDisplayFilters.sort = omoDocumentsNormalizeSortPreference(
                                            sortButton.getAttribute('data-omo-documents-sort')
                                        );
                                        syncFilterChoices();
                                        return;
                                    }
                                    const densityButton = event.target.closest('[data-omo-documents-density]');
                                    if (densityButton && pendingDisplayFilters) {
                                        pendingDisplayFilters.density = omoDocumentsNormalizeDensityPreference(
                                            densityButton.getAttribute('data-omo-documents-density')
                                        );
                                        syncFilterChoices();
                                    }
                                });
                            }

                            const quickSearch = panel.querySelector('[data-omo-documents-quick-search]');
                            if (quickSearch) {
                                quickSearch.value = state.query;
                                const applySearch = function () {
                                    state.query = quickSearch.value || '';
                                    omoDocumentsWriteSearch(panel, state.query);
                                    render();
                                };
                                quickSearch.addEventListener('input', applySearch);
                                quickSearch.addEventListener('search', applySearch);
                            }

                            panel.addEventListener('keydown', function (event) {
                                if (event.key === 'Escape' && filterPanelOpen) {
                                    closeFilterPanel(false, false);
                                }
                            });

                            panel.addEventListener('change', function (event) {
                                const selectionInput = event.target.closest('[data-omo-document-select]');
                                if (!selectionInput || !panel.contains(selectionInput)) {
                                    return;
                                }

                                event.stopPropagation();
                                const documentId = Number(selectionInput.value || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return;
                                }

                                if (selectionInput.checked) {
                                    state.selectedDocumentIds.add(documentId);
                                } else {
                                    state.selectedDocumentIds.delete(documentId);
                                }
                                syncDocumentSelection();
                            });

                            panel.addEventListener('click', function (event) {
                                const bulkActionButton = event.target.closest('[data-omo-documents-bulk-action]');
                                if (bulkActionButton && panel.contains(bulkActionButton)) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    const selectedDocumentIds = getSelectedDocumentIds();
                                    const selectedCount = selectedDocumentIds.length;
                                    const action = String(bulkActionButton.getAttribute('data-omo-documents-bulk-action') || '').trim();
                                    const isDelete = action === 'delete';
                                    const isMove = action === 'move';

                                    if (selectedCount <= 0 || (!isDelete && !isMove && action !== 'archive')) {
                                        return;
                                    }
                                    if (isMove) {
                                        if (!selectedDocumentsCanBeMoved(selectedDocumentIds)) {
                                            return;
                                        }
                                        openDocumentMovePopup(null, selectedDocumentIds);
                                        return;
                                    }
                                    if (isDelete && !selectedDocumentsCanBeDeleted(selectedDocumentIds)) {
                                        return;
                                    }

                                    const confirmation = isDelete
                                        ? <?= json_encode(omoDocumentsScopeT('documents.selection.confirm_delete'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                                        : <?= json_encode(omoDocumentsScopeT('documents.selection.confirm_archive'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                                    if (!window.confirm(formatSelectionText(confirmation, {count: selectedCount}))) {
                                        return;
                                    }

                                    bulkActionButton.disabled = true;
                                    postBulkDocumentAction(isDelete ? 'delete' : 'archive').then(function () {
                                        state.selectedDocumentIds.clear();
                                        if (typeof window.omoRefreshDocumentsPanel === 'function') {
                                            return window.omoRefreshDocumentsPanel();
                                        }
                                        window.location.reload();
                                        return null;
                                    }).catch(function (error) {
                                        window.omoNotify(String(error && error.message || <?= json_encode(omoDocumentsScopeT('documents.menu.action_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>), 'error');
                                    }).finally(function () {
                                        bulkActionButton.disabled = false;
                                    });
                                    return;
                                }

                                const selectionInput = event.target.closest('[data-omo-document-select]');
                                if (selectionInput && panel.contains(selectionInput)) {
                                    event.stopPropagation();
                                    return;
                                }

                                const contextJump = event.target.closest('[data-omo-document-context-jump]');
                                if (contextJump) {
                                    event.preventDefault();
                                    event.stopPropagation();

                                    const targetOrganizationId = Number(contextJump.getAttribute('data-omo-document-context-jump-oid') || panel.getAttribute('data-omo-document-oid') || 0);
                                    const targetHolonId = Number(contextJump.getAttribute('data-omo-document-context-jump-cid') || 0);
                                    if (!Number.isInteger(targetOrganizationId) || targetOrganizationId <= 0) {
                                        return;
                                    }

                                    const hashState = typeof window.omoParsePopupHashState === 'function'
                                        ? window.omoParsePopupHashState()
                                        : { popupToken: null };
                                    const nextHash = typeof window.omoBuildHashFromState === 'function'
                                        ? window.omoBuildHashFromState('documents', hashState && hashState.popupToken ? String(hashState.popupToken) : null)
                                        : 'documents';

                                    if (typeof window.omoNavigate === 'function') {
                                        window.omoNavigate(
                                            targetOrganizationId,
                                            Number.isInteger(targetHolonId) && targetHolonId > 0 ? targetHolonId : null,
                                            nextHash
                                        );
                                        return;
                                    }

                                    window.location.href = '/omo/' + (Number.isInteger(targetHolonId) && targetHolonId > 0 ? ('c/' + encodeURIComponent(String(targetHolonId))) : '') + (nextHash ? ('#' + nextHash) : '');
                                    return;
                                }

                                const closeTrigger = event.target.closest('[data-omo-document-detail-close]');

                                if (closeTrigger) {
                                    event.preventDefault();
                                    closeDetailDrawer();
                                    return;
                                }

                                const folderToggle = event.target.closest('[data-omo-document-folder-toggle]');
                                if (folderToggle && panel.contains(folderToggle)) {
                                    window.setTimeout(function () {
                                        syncFolderAccordionState(true);
                                    }, 0);
                                    return;
                                }

                                const trigger = event.target.closest('[data-omo-document-id]');

                                if (!trigger || !panel.contains(trigger)) {
                                    return;
                                }

                                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                                    return;
                                }

                                if (typeof window.omoOpenDocumentDetailFromTrigger === 'function') {
                                    window.omoOpenDocumentDetailFromTrigger(trigger, event);
                                    return;
                                }

                                event.preventDefault();

                                const documentId = Number(trigger.getAttribute('data-omo-document-id'));

                                if (!documentId || Number.isNaN(documentId)) {
                                    return;
                                }

                                const documentItem = documents.find(function (item) {
                                    return Number(item.id) === documentId;
                                });

                                if (!documentItem) {
                                    return;
                                }

                                openDocumentDetail(documentItem);
                            });

                            panel.addEventListener('keydown', function (event) {
                                const folderToggle = event.target.closest('[data-omo-document-folder-toggle]');
                                if (
                                    folderToggle
                                    && panel.contains(folderToggle)
                                    && (event.key === 'Enter' || event.key === ' ')
                                ) {
                                    event.preventDefault();
                                    folderToggle.click();
                                    return;
                                }

                                const card = event.target.closest('[data-omo-document-id]');
                                if (
                                    card
                                    && panel.contains(card)
                                    && (event.key === 'Enter' || event.key === ' ')
                                    && !event.target.closest('[data-omo-document-context-jump]')
                                    && !event.target.closest('input, button, a')
                                ) {
                                    event.preventDefault();
                                    if (typeof window.omoOpenDocumentDetailFromTrigger === 'function') {
                                        window.omoOpenDocumentDetailFromTrigger(card);
                                        return;
                                    }

                                    const documentItem = findDocumentItemById(Number(card.getAttribute('data-omo-document-id') || 0));
                                    if (documentItem) {
                                        openDocumentDetail(documentItem);
                                    }
                                    return;
                                }

                                if (event.key === 'Escape' && detailDrawer && detailDrawer.classList.contains('is-open')) {
                                    closeDetailDrawer();
                                }
                            });

                            render();
                            window.setTimeout(function () {
                                retryInitialDocumentOpen(0);
                            }, 0);

                            if (!panel.__omoDocumentsApplyRouteChange) {
                                panel.__omoDocumentsApplyRouteChange = function (detail) {
                                    if (!document.body.contains(panel)) {
                                        return false;
                                    }

                                    const routeDetail = detail && typeof detail === 'object' ? detail : {};
                                    const targetDocumentId = Number(routeDetail.documentId || 0);
                                    const targetMode = normalizeDocumentOpenMode(routeDetail.mode || 'detail');
                                    const previousDocumentId = Number(routeDetail.previousDocumentId || 0);
                                    const previousMode = normalizeDocumentOpenMode(routeDetail.previousMode || 'detail');
                                    const rawForcedScope = String(routeDetail.forcedScope || '').trim().toLowerCase();
                                    const forcedScope = rawForcedScope !== ''
                                        ? normalizeDocumentScope(rawForcedScope)
                                        : '';
                                    const fallbackDocumentScope = forcedScope !== ''
                                        ? forcedScope
                                        : 'descendants';

                                    if (targetDocumentId > 0) {
                                        const documentItem = findDocumentItemById(targetDocumentId);
                                        const shouldPreferPanelRefresh = targetMode !== 'edit'
                                            && !documentItem;

                                        if (targetMode === 'edit') {
                                            if (
                                                typeof window.omoPreserveDocumentPvPreparationDrawer !== 'function'
                                                || !window.omoPreserveDocumentPvPreparationDrawer()
                                            ) {
                                                if (typeof window.omoCloseDocumentPvPreparationDrawer === 'function') {
                                                    window.omoCloseDocumentPvPreparationDrawer({ force: true });
                                                }
                                            }
                                            if (typeof window.omoOpenDocumentEditorFromDocumentId === 'function' && window.omoOpenDocumentEditorFromDocumentId(targetDocumentId)) {
                                                return true;
                                            }
                                            if (refreshPanelForDocumentRoute(targetDocumentId, targetMode, fallbackDocumentScope)) {
                                                return true;
                                            }
                                        }

                                        if (shouldPreferPanelRefresh && refreshPanelForDocumentRoute(targetDocumentId, targetMode, fallbackDocumentScope)) {
                                            return true;
                                        }

                                        if (targetMode !== 'edit' && documentItem && typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                            window.omoCloseDocumentEditorDrawer({ force: true, preserveDrawer: true });
                                            if (window.omoOpenDocumentDetailByPayload(documentItem, panel) === true) {
                                                return true;
                                            }
                                        }

                                        if (targetMode !== 'edit') {
                                            window.omoCloseDocumentEditorDrawer({ force: true, preserveDrawer: true });
                                            if (refreshPanelForDocumentRoute(targetDocumentId, targetMode, fallbackDocumentScope)) {
                                                return true;
                                            }

                                            if (typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                                const directDocumentPayload = buildDirectDocumentPayload(targetDocumentId);
                                                if (
                                                    directDocumentPayload
                                                    && window.omoOpenDocumentDetailByPayload(directDocumentPayload, panel) === true
                                                ) {
                                                    return true;
                                                }
                                            }

                                            return false;
                                        }
                                        return false;
                                    }

                                    if (forcedScope !== '' && typeof window.omoSetDocumentsScope === 'function') {
                                        window.omoSetDocumentsScope(forcedScope, {
                                            panel: panel
                                        });
                                    }

                                    window.omoCloseDocumentEditorDrawer({ force: true });
                                    if (
                                        typeof window.omoPreserveDocumentPvPreparationDrawer !== 'function'
                                        || !window.omoPreserveDocumentPvPreparationDrawer()
                                    ) {
                                        if (typeof window.omoCloseDocumentPvPreparationDrawer === 'function') {
                                            window.omoCloseDocumentPvPreparationDrawer({ force: true });
                                        }
                                    }
                                    closeDetailDrawer();
                                    return true;
                                };
                            }

                            window.omoHandleDocumentsRouteChange = function (detail) {
                                const activePanel = document.getElementById('omo-documents-root');
                                if (!(activePanel instanceof Element) || !document.body.contains(activePanel)) {
                                    return false;
                                }

                                if (typeof activePanel.__omoDocumentsApplyRouteChange !== 'function') {
                                    return false;
                                }

                                return activePanel.__omoDocumentsApplyRouteChange(detail) === true;
                            };

                            if (!panel.__omoDocumentsRouteHandler) {
                                panel.__omoDocumentsRouteHandler = function (routeEvent) {
                                    const detail = routeEvent && routeEvent.detail ? routeEvent.detail : {};
                                    if (typeof panel.__omoDocumentsApplyRouteChange === 'function') {
                                        panel.__omoDocumentsApplyRouteChange(detail);
                                    }
                                };

                                window.addEventListener('omo-documents-route-change', panel.__omoDocumentsRouteHandler);
                            }

                        });
                    };

                window.omoInitDocumentsPanels();
            })();
            </script>
        <script>
        (function () {
            const normalizeDocumentScope = function (scopeValue) {
                const normalizedScope = String(scopeValue || '').trim().toLowerCase();
                if (normalizedScope === 'global') {
                    return 'descendants';
                }
                return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
            };

            const resolveDocumentsScopePanel = function (panelCandidate) {
                if (panelCandidate instanceof Element) {
                    return panelCandidate.closest('.omo-documents') || panelCandidate;
                }

                return document.getElementById('omo-documents-root');
            };

            const getCurrentDocumentsScope = function (panel) {
                return normalizeDocumentScope(panel && panel.getAttribute('data-omo-document-scope') || 'contextual');
            };

            window.omoSetDocumentsPanelLoadingState = function (panel, isLoading, options = {}) {
                if (!(panel instanceof Element)) {
                    return;
                }

                const loading = Boolean(isLoading);
                const showResultsPlaceholder = options && options.showResultsPlaceholder === true;
                panel.classList.toggle('is-loading', loading);
                const indicator = panel.querySelector('[data-omo-documents-loading-indicator]');
                if (indicator) {
                    indicator.hidden = !loading;
                }

                if (showResultsPlaceholder && typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
                    window.omoSetPanelResultsLoadingSkeleton(panel, loading, {
                        contentSelector: '[data-omo-documents-results]'
                    });
                }

                if (loading) {
                    panel.setAttribute('aria-busy', 'true');
                } else {
                    panel.removeAttribute('aria-busy');
                }

                panel.querySelectorAll('[data-omo-documents-filter-toggle], [data-omo-documents-filter-apply], [data-omo-documents-filter-save], [data-omo-document-scope-toggle], [data-omo-documents-sort], [data-omo-documents-density]').forEach(function (button) {
                    button.disabled = loading;
                });
            };

            const buildDocumentsScopeUrl = function (panel, scopeValue) {
                const resolvedScope = normalizeDocumentScope(scopeValue);
                const organizationId = Number(panel && panel.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(panel && panel.getAttribute('data-omo-document-cid') || 0);
                const query = [];

                if (organizationId > 0) {
                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                }

                if (holonId > 0) {
                    query.push('cid=' + encodeURIComponent(String(holonId)));
                }

                if (resolvedScope !== 'contextual') {
                    query.push('document_scope=' + encodeURIComponent(resolvedScope));
                }

                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
            };

            const setDocumentsScopeLoadingState = function (panel, isLoading, targetScope) {
                if (!(panel instanceof Element)) {
                    return;
                }

                window.omoSetDocumentsPanelLoadingState(panel, isLoading, {
                    showResultsPlaceholder: true
                });
                let activeScopeIndex = 0;

                panel.querySelectorAll('[data-omo-document-scope-toggle]').forEach(function (button) {
                    const buttonScope = String(button.getAttribute('data-omo-document-scope-toggle') || '').trim().toLowerCase();
                    const isActive = buttonScope === targetScope;

                    button.disabled = Boolean(isLoading);
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    if (isActive) {
                        activeScopeIndex = parseInt(button.getAttribute('data-omo-scope-index') || '0', 10) || 0;
                    }
                });

                const toggle = panel.querySelector('[data-omo-scope-switch]');
                if (toggle) {
                    toggle.setAttribute('data-omo-scope-switch', targetScope);
                    toggle.style.setProperty('--omo-scope-active-index', String(activeScopeIndex));
                }
            };

            window.omoSetDocumentsScope = function (scopeValue, options = {}) {
                const panel = resolveDocumentsScopePanel(options.panel);
                if (!(panel instanceof Element)) {
                    return false;
                }

                const targetScope = normalizeDocumentScope(scopeValue);
                if (targetScope === getCurrentDocumentsScope(panel)) {
                    setDocumentsScopeLoadingState(panel, false, targetScope);
                    return true;
                }

                if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                    window.location.href = buildDocumentsScopeUrl(panel, targetScope);
                    return true;
                }

                const requestId = (parseInt(panel.dataset.omoDocumentsScopeRequestId || '0', 10) || 0) + 1;
                panel.dataset.omoDocumentsScopeRequestId = String(requestId);

                const triggerRefresh = function () {
                    window.omoReplaceFetchedPanelRoot({
                        rootSelector: '#omo-documents-root',
                        currentRoot: panel,
                        url: buildDocumentsScopeUrl(panel, targetScope),
                        setLoadingState: function (isLoading) {
                            if ((panel.dataset.omoDocumentsScopeRequestId || '') !== String(requestId) && !isLoading) {
                                return;
                            }

                            setDocumentsScopeLoadingState(panel, isLoading, targetScope);
                        }
                    }).catch(function () {
                        if ((panel.dataset.omoDocumentsScopeRequestId || '') !== String(requestId)) {
                            return;
                        }

                        setDocumentsScopeLoadingState(panel, false, getCurrentDocumentsScope(panel));
                        panel.removeAttribute('data-omo-view-filter-pending');
                        panel.removeAttribute('aria-busy');
                    });
                };

                setDocumentsScopeLoadingState(panel, true, targetScope);

                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(triggerRefresh);
                } else {
                    triggerRefresh();
                }

                return true;
            };

            window.omoInitDocumentsScopePanels = function (root) {
                    const scope = root instanceof Element ? root : document;

                    scope.querySelectorAll('.omo-documents').forEach(function (panel) {
                        if (panel.dataset.omoDocumentsScopeReady === '1') {
                            return;
                        }

                        panel.addEventListener('click', function (event) {
                            const button = event.target.closest('[data-omo-document-scope-toggle]');
                            if (!button || !panel.contains(button)) {
                                return;
                            }
                            if (button.closest('[data-omo-documents-filter-panel]')) {
                                return;
                            }

                            const targetScope = normalizeDocumentScope(button.getAttribute('data-omo-document-scope-toggle') || '');

                            if (targetScope === getCurrentDocumentsScope(panel)) {
                                return;
                            }

                            window.omoSetDocumentsScope(targetScope, {
                                panel: panel
                            });
                        });

                        panel.dataset.omoDocumentsScopeReady = '1';
                    });
                };

            window.omoInitDocumentsScopePanels();
            const activeDocumentsPanel = document.getElementById('omo-documents-root');
            if (activeDocumentsPanel) {
                const initialPreferences = typeof window.omoDocumentsReadViewPreferences === 'function'
                    ? window.omoDocumentsReadViewPreferences(activeDocumentsPanel)
                    : null;
                const hasInitialDocument = Number(activeDocumentsPanel.getAttribute('data-omo-document-open-id') || 0) > 0;
                let initialScope = normalizeDocumentScope(
                    hasInitialDocument
                        ? activeDocumentsPanel.getAttribute('data-omo-document-scope')
                        : initialPreferences && initialPreferences.scope
                            ? initialPreferences.scope
                            : activeDocumentsPanel.getAttribute('data-omo-document-scope')
                );
                if (!activeDocumentsPanel.querySelector('[data-omo-document-scope-toggle="' + initialScope + '"]')) {
                    initialScope = getCurrentDocumentsScope(activeDocumentsPanel);
                }
                if (initialScope !== getCurrentDocumentsScope(activeDocumentsPanel)) {
                    window.omoSetDocumentsScope(initialScope, {panel: activeDocumentsPanel});
                } else {
                    activeDocumentsPanel.removeAttribute('data-omo-view-filter-pending');
                    activeDocumentsPanel.removeAttribute('aria-busy');
                }
            }
        })();
        </script>
        <script>
        (function () {
            function getDocumentsRoot() {
                return document.getElementById('omo-documents-root');
            }

            function buildDocumentsPanelUrl(root) {
                if (!root) {
                    return '/omo/api/documents/index.php';
                }

                const normalizeDocumentScope = function (scopeValue) {
                    const normalizedScope = String(scopeValue || '').trim().toLowerCase();
                    if (normalizedScope === 'global') {
                        return 'descendants';
                    }
                    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
                };
                const organizationId = Number(root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root.getAttribute('data-omo-document-cid') || 0);
                const scope = normalizeDocumentScope(root.getAttribute('data-omo-document-scope') || 'contextual');
                const query = [];

                if (organizationId > 0) {
                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                }

                if (holonId > 0) {
                    query.push('cid=' + encodeURIComponent(String(holonId)));
                }

                if (scope !== 'contextual') {
                    query.push('document_scope=' + encodeURIComponent(scope));
                }

                query.push('_=' + String(Date.now()));

                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
            }

            function getDocumentsPayloadItems(rootOverride) {
                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const dataNode = root ? root.querySelector('[data-omo-documents-data]') : null;

                if (!dataNode) {
                    return [];
                }

                try {
                    const payload = JSON.parse(dataNode.textContent || '{}');
                    return Array.isArray(payload.documents) ? payload.documents : [];
                } catch (error) {
                    return [];
                }
            }

            function findDocumentPayloadItemById(documentId, rootOverride) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return null;
                }

                return getDocumentsPayloadItems(rootOverride).find(function (item) {
                    return Number(item && item.id ? item.id : 0) === resolvedDocumentId;
                }) || null;
            }

            function executeFetchedScripts(container) {
                if (!container) {
                    return;
                }

                Array.from(container.querySelectorAll('script')).forEach(function (script) {
                    const executableScript = document.createElement('script');
                    Array.from(script.attributes).forEach(function (attribute) {
                        executableScript.setAttribute(attribute.name, attribute.value);
                    });
                    executableScript.text = script.textContent || '';
                    document.body.appendChild(executableScript);
                    document.body.removeChild(executableScript);
                });
            }

            function openEditorDrawer(url, title, description) {
                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;
                const titleNode = drawer ? drawer.querySelector('[data-omo-document-detail-title]') : null;
                const descriptionNode = drawer ? drawer.querySelector('[data-omo-document-detail-description]') : null;
                const drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
                    ? (drawer.__omoSubdrawerController || window.omoCreateSubdrawerController({ drawer: drawer }))
                    : null;
                const targetUrl = String(url || '').trim();

                if (!drawer || !body || targetUrl === '') {
                    return;
                }

                if (drawer.dataset.omoDocumentDrawerMode === 'edit' && typeof cleanupDocumentEditorDrawer === 'function') {
                    cleanupDocumentEditorDrawer(drawer);
                }

                if (drawerController) {
                    drawer.__omoSubdrawerController = drawerController;
                    window.omoDocumentsEditorDrawer = drawerController;
                    drawerController.setHeader({
                        title: String(title || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>).trim() || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        description: String(description || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>).trim()
                            || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        actions: []
                    });
                }

                try {
                    const editorUrl = new URL(targetUrl, window.location.origin);
                    const documentId = Number(editorUrl.searchParams.get('id') || 0);
                    drawer.dataset.omoDocumentDrawerDocumentId = Number.isInteger(documentId) && documentId > 0
                        ? String(documentId)
                        : '';
                } catch (error) {
                    drawer.dataset.omoDocumentDrawerDocumentId = '';
                }
                drawer.dataset.omoDocumentDrawerMode = 'edit';

                if (titleNode) {
                    titleNode.textContent = String(title || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>).trim() || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }

                if (descriptionNode) {
                    descriptionNode.textContent = String(description || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>).trim()
                        || <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }

                body.innerHTML = window.getSkeleton
                    ? getSkeleton('panel')
                    : '<div class="loading"><?= $escape(omoDocumentsScopeT('documents.action.loading')) ?></div>';

                drawer.hidden = false;
                requestAnimationFrame(function () {
                    drawer.classList.add('is-open');
                });

                fetch(targetUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('document_editor_load_failed');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        body.innerHTML = html;
                        if (drawerController) {
                            drawerController.applyContentHeader(body);
                        }
                        executeFetchedScripts(temp);
                    })
                    .catch(function () {
                        body.innerHTML = '<div class="omo-empty-state"><?= $escape(omoDocumentsScopeT('documents.error.load_editor')) ?></div>';
                    });
            }

            function buildDocumentEditorUrl(documentId, rootOverride) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return '';
                }

                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const organizationId = Number(root && root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root && root.getAttribute('data-omo-document-cid') || 0);

                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                    return '';
                }

                let url = '/omo/api/documents/create.php?oid=' + encodeURIComponent(String(organizationId));
                if (Number.isInteger(holonId) && holonId > 0) {
                    url += '&cid=' + encodeURIComponent(String(holonId));
                }

                url += '&id=' + encodeURIComponent(String(resolvedDocumentId));
                return url;
            }

            function buildDocumentDetailUrl(documentId, rootOverride) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return '';
                }

                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const organizationId = Number(root && root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root && root.getAttribute('data-omo-document-cid') || 0);

                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                    return '';
                }

                let url = '/omo/api/documents/detail.php?oid=' + encodeURIComponent(String(organizationId));
                if (Number.isInteger(holonId) && holonId > 0) {
                    url += '&cid=' + encodeURIComponent(String(holonId));
                }

                url += '&id=' + encodeURIComponent(String(resolvedDocumentId));
                return url;
            }

            function openExternalDocumentWindow(documentItem) {
                const externalUrl = documentItem && documentItem.externalUrl
                    ? String(documentItem.externalUrl).trim()
                    : '';

                if (externalUrl === '') {
                    return false;
                }

                const link = document.createElement('a');
                link.href = externalUrl;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                link.remove();

                return true;
            }

            function normalizeDocumentOpenMode(value) {
                return String(value || '').trim().toLowerCase() === 'edit'
                    ? 'edit'
                    : 'detail';
            }

            function buildDocumentRouteToken(documentId, mode) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return null;
                }

                if (typeof window.omoBuildDocumentRouteToken === 'function') {
                    return window.omoBuildDocumentRouteToken(resolvedDocumentId, normalizeDocumentOpenMode(mode));
                }

                return normalizeDocumentOpenMode(mode) === 'edit'
                    ? ('documents-de' + String(resolvedDocumentId))
                    : ('documents-d' + String(resolvedDocumentId));
            }

            function openDocumentMovePopup(documentId, documentIds) {
                const resolvedDocumentId = Number(documentId || 0);
                const resolvedDocumentIds = (Array.isArray(documentIds) ? documentIds : [resolvedDocumentId])
                    .map(function (value) { return Number(value || 0); })
                    .filter(function (value, index, values) {
                        return Number.isInteger(value) && value > 0 && values.indexOf(value) === index;
                    });

                if (resolvedDocumentIds.length === 0) {
                    return;
                }

                if (resolvedDocumentIds.length > 1 && typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        <?= json_encode(omoDocumentsScopeT('documents.selection.move'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        '/omo/api/documents/move.php?ids=' + encodeURIComponent(resolvedDocumentIds.join(',')),
                        'fetch'
                    );
                    return;
                }

                const singleDocumentId = resolvedDocumentIds[0];

                if (typeof window.omoOpenPopupHashState === 'function') {
                    window.omoOpenPopupHashState('document-move', singleDocumentId);
                    return;
                }

                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        'Déplacer',
                        '/omo/api/documents/move.php?id=' + encodeURIComponent(String(singleDocumentId)),
                        'fetch'
                    );
                }
            }

            function openDocumentSharePopup(documentId) {
                const resolvedDocumentId = Number(documentId || 0);

                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return;
                }

                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        'Partager le document',
                        '/omo/api/documents/share_popup.php?id=' + encodeURIComponent(String(resolvedDocumentId)),
                        'fetch'
                    );
                }
            }

            const cleanupDocumentEditorDrawer = function (drawer) {
                if (!(drawer instanceof Element) || drawer.dataset.omoDocumentDrawerMode !== 'edit') {
                    return;
                }

                window.dispatchEvent(new CustomEvent('omo-document-editor-drawer-close'));
                drawer.dataset.omoDocumentDrawerMode = '';
            };

            window.omoCloseDocumentEditorDrawer = function (options) {
                const settings = options && typeof options === 'object'
                    ? options
                    : {};
                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const documentId = Number(drawer && drawer.dataset.omoDocumentDrawerDocumentId || 0);
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
                const editingDocumentMatch = routeToken.match(/^(?:documents|document)-de(\d+)$/i);

                if (!drawer) {
                    return;
                }

                cleanupDocumentEditorDrawer(drawer);

                if (settings.returnToDetail === true && Number.isInteger(documentId) && documentId > 0) {
                    if (editingDocumentMatch && settings.force !== true && typeof window.omoOpenDrawerHashState === 'function') {
                        window.omoOpenDrawerHashState('documents-d' + editingDocumentMatch[1]);
                        return;
                    }

                    const documentItem = findDocumentPayloadItemById(documentId, root) || {
                        id: documentId,
                        contextUrl: buildDocumentDetailUrl(documentId, root)
                    };
                    drawer.dataset.omoDocumentDrawerMode = 'detail';
                    window.omoOpenDocumentDetailByPayload(documentItem, root);
                    return;
                }

                if (settings.preserveDrawer === true) {
                    return;
                }

                if (settings.force !== true && editingDocumentMatch && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('documents');
                }

                window.omoCloseDocumentDetailDrawer({ force: true });
            };

            window.omoRefreshDocumentsPanel = function () {
                const root = getDocumentsRoot();
                if (!root || typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                    return Promise.resolve(null);
                }

                return window.omoReplaceFetchedPanelRoot({
                    rootSelector: '#omo-documents-root',
                    currentRoot: root,
                    url: buildDocumentsPanelUrl(root),
                    setLoadingState: function (isLoading) {
                        root.classList.toggle('is-loading', !!isLoading);
                    }
                });
            };

            window.omoOpenDocumentEditorDrawer = function (url, title, description) {
                openEditorDrawer(url, title, description);
            };

            window.omoCloseDocumentPvPreparationDrawer = function (options) {
                if (typeof window.omoCloseExternalPanelDrawer !== 'function') {
                    return;
                }

                window.omoCloseExternalPanelDrawer(options && typeof options === 'object'
                    ? options
                    : {});
            };

            window.omoPreserveDocumentPvPreparationDrawer = function () {
                if (typeof window.omoPeekPersistentExternalPanelDrawer !== 'function') {
                    return false;
                }

                return window.omoPeekPersistentExternalPanelDrawer({
                    persistKeyPrefix: 'omo-pv-preparation-',
                    contentSelector: '[data-omo-pv-editor-root]'
                }) === true;
            };

            window.omoCloseDocumentDetailDrawer = function (options) {
                const settings = options && typeof options === 'object'
                    ? options
                    : {};
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
                if (settings.force !== true && /^(?:documents|document)-(?:d(?:e)?)?\d+$/i.test(routeToken) && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('documents');
                }

                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;

                if (!drawer) {
                    return;
                }

                cleanupDocumentEditorDrawer(drawer);
                drawer.dataset.omoDocumentDrawerMode = '';
                drawer.dataset.omoDocumentDrawerDocumentId = '';
                drawer.classList.remove('is-open');
                window.setTimeout(function () {
                    if (!drawer.classList.contains('is-open')) {
                        drawer.hidden = true;
                        if (body) {
                            body.innerHTML = '';
                        }
                    }
                }, 200);
            };

            const syncDocumentDetailDrawerMetadata = function (sourceNode, drawer, fallbackTitle, fallbackDescription) {
                if (!(drawer instanceof Element) || !(sourceNode instanceof Element)) {
                    return;
                }

                const titleNode = drawer.querySelector('[data-omo-document-detail-title]');
                const descriptionNode = drawer.querySelector('[data-omo-document-detail-description]');
                const metadataNode = sourceNode.matches('[data-omo-document-drawer-title], [data-omo-document-drawer-description]')
                    ? sourceNode
                    : sourceNode.querySelector('[data-omo-document-drawer-title], [data-omo-document-drawer-description]');
                const resolvedTitle = metadataNode
                    ? String(metadataNode.getAttribute('data-omo-document-drawer-title') || '').trim()
                    : '';
                const resolvedDescription = metadataNode
                    ? String(metadataNode.getAttribute('data-omo-document-drawer-description') || '').trim()
                    : '';

                if (titleNode) {
                    titleNode.textContent = resolvedTitle !== ''
                        ? resolvedTitle
                        : String(fallbackTitle || '').trim() || 'Détail du document';
                }

                if (descriptionNode) {
                    descriptionNode.textContent = resolvedDescription !== ''
                        ? resolvedDescription
                        : String(fallbackDescription || '').trim()
                            || <?= json_encode(omoDocumentsScopeT('documents.drawer.detail_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }
            };

            window.omoOpenDocumentPvPreparationByPayload = function (documentItem) {
                const preparationUrl = String(documentItem && documentItem.pvPreparationUrl ? documentItem.pvPreparationUrl : '').trim();
                const documentId = Number(documentItem && documentItem.id ? documentItem.id : 0);
                const title = String(documentItem && documentItem.title ? documentItem.title : '').trim();
                const fullDate = String(documentItem && documentItem.fullDateLabel ? documentItem.fullDateLabel : '').trim();
                const hasUpcomingPvEvent = !!(documentItem && documentItem.hasUpcomingPvEvent);

                if (
                    preparationUrl === ''
                    || !Number.isInteger(documentId)
                    || documentId <= 0
                    || typeof window.omoOpenExternalPanelDrawer !== 'function'
                ) {
                    return false;
                }

                window.omoCloseDocumentEditorDrawer({ force: true });
                window.omoCloseDocumentDetailDrawer({ force: true });

                return window.omoOpenExternalPanelDrawer({
                    url: preparationUrl,
                    mode: 'fetch',
                    title: title !== '' ? title : (hasUpcomingPvEvent ? 'Préparation du PV' : 'Édition du PV'),
                    description: hasUpcomingPvEvent
                        ? (fullDate !== ''
                            ? 'Préparation ouverte avant la réunion du ' + fullDate + '.'
                            : 'Préparation du PV avant la réunion.')
                        : '',
                    variant: 'top-sheet',
                    persistKey: 'omo-pv-preparation-' + String(documentId),
                    keepMountedOnClose: true
                }) === true;
            };

            window.omoOpenDocumentDetailByPayload = function (documentItem, rootOverride) {
                if (documentItem && documentItem.openInNewWindow && documentItem.externalUrl) {
                    if (openExternalDocumentWindow(documentItem)) {
                        return true;
                    }
                }

                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;
                const titleNode = drawer ? drawer.querySelector('[data-omo-document-detail-title]') : null;
                const descriptionNode = drawer ? drawer.querySelector('[data-omo-document-detail-description]') : null;
                const detailUrl = String(documentItem && documentItem.contextUrl ? documentItem.contextUrl : '').trim();
                const title = String(documentItem && documentItem.title ? documentItem.title : '').trim();
                const fullDate = String(documentItem && documentItem.fullDateLabel ? documentItem.fullDateLabel : '').trim();

                if (!drawer || !body || detailUrl === '' || (documentItem && documentItem.isFolder)) {
                    return false;
                }

                if (!window.omoPreserveDocumentPvPreparationDrawer()) {
                    window.omoCloseDocumentPvPreparationDrawer({ force: true });
                }

                cleanupDocumentEditorDrawer(drawer);
                drawer.dataset.omoDocumentDrawerMode = 'detail';
                drawer.dataset.omoDocumentDrawerDocumentId = String(documentItem && documentItem.id ? documentItem.id : '');

                if (titleNode) {
                    titleNode.textContent = title !== '' ? title : 'Détail du document';
                }

                if (descriptionNode) {
                    descriptionNode.textContent = fullDate !== ''
                        ? 'Document créé le ' + fullDate + '.'
                        : <?= json_encode(omoDocumentsScopeT('documents.drawer.detail_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }

                body.innerHTML = window.getSkeleton
                    ? getSkeleton('panel')
                    : '<div class="loading"><?= $escape(omoDocumentsScopeT('documents.action.loading')) ?></div>';

                drawer.hidden = false;
                requestAnimationFrame(function () {
                    drawer.classList.add('is-open');
                });

                fetch(detailUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then(function (response) {
                        return response.text().then(function (html) {
                            return {
                                ok: response.ok,
                                html: html
                            };
                        });
                    })
                    .then(function (result) {
                        const html = typeof result.html === 'string' ? result.html : '';
                        if (html.trim() === '') {
                            if (!result.ok) {
                                throw new Error('document_detail_load_failed');
                            }

                            body.innerHTML = '';
                            return;
                        }

                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        body.innerHTML = html;
                        syncDocumentDetailDrawerMetadata(temp, drawer, title, descriptionNode ? descriptionNode.textContent : '');
                        if (drawer.__omoSubdrawerController) {
                            drawer.__omoSubdrawerController.applyContentHeader(body);
                        }
                        executeFetchedScripts(temp);
                    })
                    .catch(function () {
                        body.innerHTML = '<div class="omo-empty-state"><?= $escape(omoDocumentsScopeT('documents.error.load_document')) ?></div>';
                    });

                return true;
            };

            window.omoOpenDocumentEditorByPayload = function (documentItem) {
                if (!documentItem || !documentItem.canEdit) {
                    return false;
                }

                if (String(documentItem.documentType || '').trim().toLowerCase() === 'pv') {
                    return window.omoOpenDocumentPvPreparationByPayload(documentItem);
                }

                if (!documentItem.editUrl) {
                    return false;
                }

                if (!window.omoPreserveDocumentPvPreparationDrawer()) {
                    window.omoCloseDocumentPvPreparationDrawer({ force: true });
                }

                openEditorDrawer(
                    String(documentItem.editUrl || '').trim(),
                    <?= json_encode('Éditer le document', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    <?= json_encode('Modification du document dans le contexte courant.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                );
                return true;
            };

            window.omoOpenDocumentDetailFromTrigger = function (trigger, event) {
                if (!(trigger instanceof Element)) {
                    return true;
                }

                const documentId = Number(trigger.getAttribute('data-omo-document-id') || 0);
                const documentPayload = {
                    id: documentId,
                    contextUrl: String(trigger.getAttribute('data-omo-document-context-url') || '').trim(),
                    externalUrl: String(trigger.getAttribute('data-omo-document-external-url') || '').trim(),
                    openInNewWindow: String(trigger.getAttribute('data-omo-document-open-in-new-window') || '').trim() === '1',
                    documentType: String(trigger.getAttribute('data-omo-document-type') || '').trim(),
                    pvPreparationUrl: String(trigger.getAttribute('data-omo-document-pv-editor-url') || '').trim(),
                    title: String(trigger.getAttribute('data-omo-document-title') || '').trim(),
                    fullDateLabel: String(trigger.getAttribute('data-omo-document-full-date') || '').trim()
                };

                if (event) {
                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return true;
                    }
                    event.preventDefault();
                }

                if (documentPayload.openInNewWindow && documentPayload.externalUrl !== '') {
                    return openExternalDocumentWindow(documentPayload) ? false : true;
                }

                if (
                    documentPayload.documentType.toLowerCase() === 'pv'
                    && documentPayload.pvPreparationUrl !== ''
                    && window.omoOpenDocumentPvPreparationByPayload(documentPayload)
                ) {
                    return false;
                }

                const routeToken = buildDocumentRouteToken(documentId, 'detail');
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const currentRouteToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';

                if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                    window.omoOpenDrawerHashState(routeToken);
                    return false;
                }

                const root = trigger.closest('#omo-documents-root') || getDocumentsRoot();
                const opened = window.omoOpenDocumentDetailByPayload(documentPayload, root);

                if (!opened) {
                    return true;
                }

                return false;
            };

            window.omoOpenDocumentEditorFromDocumentId = function (documentId) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return false;
                }

                const root = getDocumentsRoot();
                const documentItem = findDocumentPayloadItemById(resolvedDocumentId, root);
                if (
                    documentItem
                    && String(documentItem.documentType || '').trim().toLowerCase() === 'pv'
                    && window.omoOpenDocumentEditorByPayload(documentItem)
                ) {
                    return true;
                }

                const routeToken = buildDocumentRouteToken(resolvedDocumentId, 'edit');
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const currentRouteToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';

                if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                    window.omoOpenDrawerHashState(routeToken);
                    return true;
                }

                if (documentItem && window.omoOpenDocumentEditorByPayload(documentItem)) {
                    return true;
                }

                const editUrl = buildDocumentEditorUrl(resolvedDocumentId, root);
                if (editUrl === '') {
                    return false;
                }

                openEditorDrawer(
                    editUrl,
                    <?= json_encode('Éditer le document', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    <?= json_encode('Modification du document dans le contexte courant.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                );
                return true;
            };

            const root = getDocumentsRoot();
            if (!root || root.dataset.omoDocumentsDrawerReady === '1') {
                return;
            }

            root.dataset.omoDocumentsDrawerReady = '1';
            const ownerDocument = root.ownerDocument || document;
            const floatingMenu = ownerDocument.createElement('div');
            floatingMenu.className = 'omo-documents__menu-panel generic-menu-panel generic-menu-panel--floating omo-documents__menu-panel--floating';
            floatingMenu.setAttribute('data-omo-document-floating-menu', '1');
            floatingMenu.setAttribute('role', 'menu');
            floatingMenu.hidden = true;
            ownerDocument.body.appendChild(floatingMenu);

            let activeDocumentMenuToggle = null;

            function buildDocumentMenuItem(label, attributes) {
                const button = ownerDocument.createElement('button');
                button.type = 'button';
                button.className = 'omo-documents__menu-item generic-menu-item';
                button.setAttribute('role', 'menuitem');
                button.textContent = label;

                Object.keys(attributes || {}).forEach(function (attributeName) {
                    button.setAttribute(attributeName, String(attributes[attributeName] || ''));
                });

                return button;
            }

            function populateDocumentMenu(toggle) {
                const documentId = Number(toggle && toggle.getAttribute('data-omo-document-menu-document-id') || 0);
                const documentTitle = String(toggle && toggle.getAttribute('data-omo-document-menu-title') || '').trim();
                const editUrl = String(toggle && toggle.getAttribute('data-omo-document-menu-edit-url') || '').trim();
                const canEdit = String(toggle && toggle.getAttribute('data-omo-document-menu-can-edit') || '') === '1';
                const canMove = String(toggle && toggle.getAttribute('data-omo-document-menu-can-move') || '') === '1';
                const canArchive = String(toggle && toggle.getAttribute('data-omo-document-menu-can-archive') || '') === '1';
                const canDelete = String(toggle && toggle.getAttribute('data-omo-document-menu-can-delete') || '') === '1';
                const isFolder = String(toggle && toggle.getAttribute('data-omo-document-menu-is-folder') || '') === '1';
                const canShare = String(toggle && toggle.getAttribute('data-omo-document-menu-can-share') || '') === '1';
                const canExportPdf = String(toggle && toggle.getAttribute('data-omo-document-menu-can-export-pdf') || '') === '1';
                const pdfExportUrl = String(toggle && toggle.getAttribute('data-omo-document-menu-pdf-url') || '').trim();
                const fragment = ownerDocument.createDocumentFragment();

                if (canExportPdf && pdfExportUrl !== '') {
                    fragment.appendChild(buildDocumentMenuItem(<?= json_encode(omoDocumentsScopeT('documents.menu.export_pdf'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
                        'data-omo-document-menu-action': 'export-pdf',
                        'data-omo-document-export-pdf-url': pdfExportUrl
                    }));
                }

                if (canMove && Number.isInteger(documentId) && documentId > 0) {
                    fragment.appendChild(buildDocumentMenuItem('Déplacer', {
                        'data-omo-document-menu-action': 'move',
                        'data-omo-document-move': '1',
                        'data-omo-document-move-id': String(documentId)
                    }));
                }

                if (canArchive && Number.isInteger(documentId) && documentId > 0) {
                    fragment.appendChild(buildDocumentMenuItem(<?= json_encode(omoDocumentsScopeT('documents.menu.archive'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
                        'data-omo-document-menu-action': 'archive',
                        'data-omo-document-lifecycle': 'archive',
                        'data-omo-document-lifecycle-id': String(documentId)
                    }));
                }

                if (canDelete && Number.isInteger(documentId) && documentId > 0) {
                    fragment.appendChild(buildDocumentMenuItem(<?= json_encode(omoDocumentsScopeT('documents.menu.delete'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
                        'data-omo-document-menu-action': 'delete',
                        'data-omo-document-lifecycle': 'delete',
                        'data-omo-document-lifecycle-id': String(documentId)
                    }));
                }

                if (Number.isInteger(documentId) && documentId > 0 && !isFolder && canShare) {
                    fragment.appendChild(buildDocumentMenuItem('Partager', {
                        'data-omo-document-menu-action': 'share',
                        'data-omo-document-share': '1',
                        'data-omo-document-share-id': String(documentId)
                    }));
                }

                if (canEdit && editUrl !== '') {
                    fragment.appendChild(buildDocumentMenuItem('Éditer', {
                        'data-omo-document-menu-action': 'edit',
                        'data-omo-document-edit': '1',
                        'data-omo-document-edit-id': String(documentId),
                        'data-omo-document-edit-url': editUrl,
                        'data-omo-document-edit-title': documentTitle
                    }));
                }

                floatingMenu.replaceChildren(fragment);
            }

            function positionDocumentMenu(toggle) {
                if (!toggle || !toggle.isConnected) {
                    closeDocumentMenus();
                    return;
                }

                floatingMenu.hidden = false;
                floatingMenu.style.visibility = 'hidden';
                floatingMenu.style.top = '0px';
                floatingMenu.style.left = '0px';

                const toggleRect = toggle.getBoundingClientRect();
                const menuRect = floatingMenu.getBoundingClientRect();
                const viewportPadding = 12;
                const gap = 8;
                let top = toggleRect.bottom + gap;
                let left = toggleRect.right - menuRect.width;

                if (top + menuRect.height > window.innerHeight - viewportPadding) {
                    top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
                }

                if (left + menuRect.width > window.innerWidth - viewportPadding) {
                    left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
                }

                if (left < viewportPadding) {
                    left = viewportPadding;
                }

                floatingMenu.style.top = String(Math.round(top)) + 'px';
                floatingMenu.style.left = String(Math.round(left)) + 'px';
                floatingMenu.style.visibility = '';
            }

            async function runDocumentLifecycleAction(actionButton) {
                const action = String(actionButton && actionButton.getAttribute('data-omo-document-lifecycle') || '').trim().toLowerCase();
                const documentId = Number(actionButton && actionButton.getAttribute('data-omo-document-lifecycle-id') || 0);
                if (!['archive', 'delete'].includes(action) || !Number.isInteger(documentId) || documentId <= 0) {
                    return false;
                }

                const message = action === 'delete'
                    ? <?= json_encode(omoDocumentsScopeT('documents.menu.confirm_delete'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                    : <?= json_encode(omoDocumentsScopeT('documents.menu.confirm_archive'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                if (!window.confirm(message)) {
                    return true;
                }

                const response = await fetch('/omo/api/documents/lifecycle_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: documentId, action: action})
                });
                const payload = await response.json();
                if (!response.ok || !payload || payload.status !== true) {
                    window.omoNotify(String(payload && payload.message || <?= json_encode(omoDocumentsScopeT('documents.menu.action_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>), 'error');
                    return true;
                }

                if (typeof window.omoRefreshDocumentsPanel === 'function') {
                    await window.omoRefreshDocumentsPanel();
                } else {
                    window.location.reload();
                }
                return true;
            }

            function runDocumentMenuAction(actionButton) {
                const action = String(actionButton && actionButton.getAttribute('data-omo-document-menu-action') || '').trim().toLowerCase();
                if (action === '') {
                    return false;
                }

                closeDocumentMenus();

                if (action === 'export-pdf') {
                    const pdfExportUrl = String(actionButton.getAttribute('data-omo-document-export-pdf-url') || '').trim();
                    if (pdfExportUrl === '') {
                        return false;
                    }

                    const downloadLink = ownerDocument.createElement('a');
                    downloadLink.href = pdfExportUrl;
                    downloadLink.download = '';
                    downloadLink.hidden = true;
                    ownerDocument.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                    return true;
                }

                if (action === 'archive' || action === 'delete') {
                    runDocumentLifecycleAction(actionButton);
                    return true;
                }

                if (action === 'move') {
                    openDocumentMovePopup(actionButton.getAttribute('data-omo-document-move-id'));
                    return true;
                }

                if (action === 'share') {
                    openDocumentSharePopup(actionButton.getAttribute('data-omo-document-share-id'));
                    return true;
                }

                if (action !== 'edit') {
                    return false;
                }

                const documentId = Number(actionButton.getAttribute('data-omo-document-edit-id') || 0);
                if (window.omoOpenDocumentEditorFromDocumentId(documentId)) {
                    return true;
                }

                const documentItem = findDocumentPayloadItemById(documentId);
                if (documentItem && window.omoOpenDocumentEditorByPayload(documentItem)) {
                    return true;
                }

                const editUrl = String(actionButton.getAttribute('data-omo-document-edit-url') || '').trim();
                if (editUrl === '') {
                    return false;
                }

                openEditorDrawer(
                    editUrl,
                    <?= json_encode('Éditer le document', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    <?= json_encode('Modification du document dans le contexte courant.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                );
                return true;
            }

            function closeDocumentMenus() {
                root.querySelectorAll('[data-omo-document-menu="1"]').forEach(function (menu) {
                    menu.classList.remove('is-open');
                });

                root.querySelectorAll('[data-omo-document-menu-toggle="1"]').forEach(function (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                });

                activeDocumentMenuToggle = null;
                floatingMenu.hidden = true;
                floatingMenu.style.visibility = '';
                floatingMenu.replaceChildren();
            }

            function openDocumentMenu(toggle) {
                const parentMenu = toggle ? toggle.closest('[data-omo-document-menu="1"]') : null;
                const shouldOpen = !!toggle && (!activeDocumentMenuToggle || activeDocumentMenuToggle !== toggle || floatingMenu.hidden);

                closeDocumentMenus();

                if (!toggle || !parentMenu || !shouldOpen) {
                    return;
                }

                populateDocumentMenu(toggle);
                if (!floatingMenu.childElementCount) {
                    return;
                }

                activeDocumentMenuToggle = toggle;
                parentMenu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                positionDocumentMenu(toggle);
            }

            root.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-omo-document-menu-toggle="1"]');
                if (!toggle) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                openDocumentMenu(toggle);
            });

            ownerDocument.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-omo-document-menu-toggle="1"]');
                if (toggle) {
                    return;
                }

                const actionButton = event.target.closest('[data-omo-document-menu-action]');
                if (actionButton && floatingMenu.contains(actionButton)) {
                    event.preventDefault();
                    event.stopPropagation();
                    runDocumentMenuAction(actionButton);
                    return;
                }

                const editButton = event.target.closest('[data-omo-document-edit="1"]');
                if (editButton && floatingMenu.contains(editButton)) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    if (window.omoOpenDocumentEditorFromDocumentId(Number(editButton.getAttribute('data-omo-document-edit-id') || 0))) {
                        return;
                    }

                    openEditorDrawer(
                        String(editButton.getAttribute('data-omo-document-edit-url') || '').trim(),
                        <?= json_encode('Éditer le document', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        <?= json_encode('Modification du document dans le contexte courant.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                    );
                    return;
                }

                const moveButton = event.target.closest('[data-omo-document-move="1"]');
                if (moveButton && floatingMenu.contains(moveButton)) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    openDocumentMovePopup(moveButton.getAttribute('data-omo-document-move-id'));
                    return;
                }

                const shareButton = event.target.closest('[data-omo-document-share="1"]');
                if (shareButton && floatingMenu.contains(shareButton)) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    openDocumentSharePopup(shareButton.getAttribute('data-omo-document-share-id'));
                    return;
                }

                if (!event.target.closest('[data-omo-document-floating-menu="1"]')) {
                    closeDocumentMenus();
                }
            });

            ownerDocument.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !floatingMenu.hidden) {
                    closeDocumentMenus();
                }
            });

            ownerDocument.addEventListener('scroll', function () {
                if (!activeDocumentMenuToggle || floatingMenu.hidden) {
                    return;
                }

                positionDocumentMenu(activeDocumentMenuToggle);
            }, true);

            window.addEventListener('resize', function () {
                if (!activeDocumentMenuToggle || floatingMenu.hidden) {
                    return;
                }

                positionDocumentMenu(activeDocumentMenuToggle);
            });

            const documentDrawer = root.querySelector('[data-omo-document-detail-drawer]');
            if (documentDrawer) {
                documentDrawer.addEventListener('click', function (event) {
                    if (!(event.target instanceof Element) || !event.target.closest('[data-omo-document-editor-cancel]')) {
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    window.omoCloseDocumentEditorDrawer({ returnToDetail: true });
                }, true);
            }

            root.querySelectorAll('[data-omo-document-detail-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.omoCloseDocumentDetailDrawer();
                });
            });

            const newButton = root.querySelector('[data-omo-documents-new]');
            if (newButton) {
                newButton.addEventListener('click', function () {
                    const targetUrl = String(newButton.getAttribute('data-omo-documents-new-url') || '').trim();
                    openEditorDrawer(
                        targetUrl,
                        <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        <?= json_encode(omoDocumentsScopeT('documents.drawer.editor_description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                    );
                });
            }
        })();
        </script>
    </div>
</div>

<style>
.omo-documents__app-icon {
    --omo-panel-view-app-icon-accent: #d97706;
}

.omo-documents__header {
    display: block;
    width: 100%;
    min-width: 0;
    position: relative;
    z-index: 120;
    justify-content: stretch;
    align-items: initial;
}

.omo-documents__count {
    min-width: 0;
}

.omo-documents__header-actions {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: center;
    gap: 12px;
    width: 100%;
}

.omo-documents__header-main-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}

.omo-documents__bulk-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-documents__bulk-actions[hidden],
.omo-documents__bulk-action-button[hidden] {
    display: none !important;
}

.omo-documents__bulk-count {
    color: var(--color-text-light, #64748b);
    font-size: .8rem;
    font-weight: 800;
    white-space: nowrap;
}

.omo-documents__bulk-action-button {
    width: 36px;
    min-width: 36px;
    height: 36px;
    padding: 8px;
}

.omo-documents__bulk-action-icon {
    display: block;
    width: 100%;
    height: 100%;
    background: currentColor;
    mask-position: center;
    mask-repeat: no-repeat;
    mask-size: contain;
    -webkit-mask-position: center;
    -webkit-mask-repeat: no-repeat;
    -webkit-mask-size: contain;
}

.omo-documents__bulk-action-icon--archive {
    mask-image: url('/omo/assets/images/projects/archive.svg');
    -webkit-mask-image: url('/omo/assets/images/projects/archive.svg');
}

.omo-documents__bulk-action-icon--delete {
    mask-image: url('/omo/assets/images/documents/poubelle.png');
    -webkit-mask-image: url('/omo/assets/images/documents/poubelle.png');
}

.omo-documents__bulk-action-icon--move {
    align-items: center;
    background: none;
    display: flex;
    font-size: 1.25rem;
    justify-content: center;
    line-height: 1;
}

.omo-documents__new-button {
    flex: 0 0 auto;
}

.omo-documents__detail-drawer[data-omo-document-drawer-mode="edit"] .omo-overlay-drawer__body {
    padding: 0;
}

.omo-documents__drawer-custom-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.omo-documents__detail-drawer {
    z-index: 6000;
}

.omo-documents__results {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.omo-documents__results.generic-file-list {
    --generic-file-list-columns: minmax(260px, 2.35fr) minmax(120px, 1.15fr) minmax(92px, max-content);
    --generic-file-list-title-gap: 18px;
    --generic-file-list-table-margin-inline: 12px;
    --generic-file-list-padding-inline-start: 16px;
    --generic-file-list-padding-inline-end: 18px;
    --generic-file-list-header-padding-block: 14px;
    --generic-file-list-row-padding-block: 12px;
    --generic-file-list-menu-space: 64px;
}

.omo-documents__results.generic-file-list .generic-file-list__group-title {
    padding: 15px 12px;
}

.omo-documents__list {
    display: grid;
    gap: 14px;
}

.omo-documents__list.omo-panel-view__body_content {
    margin: 10px;
    padding:0px;
}

.omo-documents__group {
    position: relative;
}

.omo-documents__list--alphabetical {
    align-content: start;
}

.omo-documents__item-shell {
    position: relative;
}

.omo-documents__item-shell--has-menu .omo-documents__item {
    padding-right: 104px;
}

.omo-documents__item-shell--folder {
    display: grid;
    gap: 12px;
}

.omo-documents__item {
    display: block;
    text-decoration: none;
    padding: 0;
    padding-right: 56px;
    overflow: hidden;
    border-radius: var(--radius-md);
    transition: transform 140ms ease, box-shadow 140ms ease, border-color 140ms ease, background 140ms ease;
}

.omo-documents__folder {
    display: grid;
    gap: 0;
}

.omo-documents__folder-header {
    align-items: stretch;
    gap: 12px;
    position: relative;
}

.omo-documents__item-shell--compact.omo-documents__item-shell--folder {
    border-bottom: 0;
}

.omo-documents__folder-toggle {
    position: relative;
    display: block;
    width: 100%;
    cursor: pointer;
}

.omo-documents__folder-toggle:focus {
    outline: 2px solid color-mix(in srgb, var(--color-primary) 55%, white);
    outline-offset: 2px;
}

.omo-documents__folder-card {
    width: 100%;
    padding-right: 62px;
}

.omo-documents__folder-chevron {
    position: absolute;
    top: 16px;
    right: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface) 74%, white 26%);
    color: var(--color-text-light);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border) 80%, white 20%);
    transition: transform 160ms ease, background 160ms ease, color 160ms ease;
    pointer-events: none;
}

.omo-documents__item-shell--has-menu .omo-documents__folder-chevron {
    right: 58px;
}

.omo-documents__folder-content {
    position: relative;
    margin-left: 28px;
    padding: 6px 0 0 20px;
}

.omo-documents__folder-content::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 8px;
    left: 0;
    width: 2px;
    border-radius: 999px;
    background: linear-gradient(180deg, color-mix(in srgb, #d97706 32%, var(--color-border)), color-mix(in srgb, var(--color-border) 82%, white 18%));
}

.omo-documents__folder-children {
    display: grid;
    gap: 12px;
}

.omo-documents__folder-children--compact {
    gap: 0;
}

.omo-documents__folder-children--compact > .omo-documents__item-shell {
    border-bottom: 1px solid color-mix(in srgb, var(--color-border) 82%, white 18%);
}

.omo-documents__folder-children--compact > .omo-documents__item-shell:last-of-type {
    border-bottom: 0;
}

.omo-documents__folder-empty {
    color: var(--color-text-light);
    font-size: 0.9rem;
    padding: 8px 12px 4px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, #fef3c7 28%, var(--color-surface));
}

.omo-documents__item-frame {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
    padding: 18px 18px 18px 16px;
}

.omo-documents__item-frame--with-selection {
    grid-template-columns: 24px 72px minmax(0, 1fr);
}

.omo-documents__visual {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 2px;
}

.omo-documents__icon-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface) 76%, white 24%);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border) 78%, white 22%);
}

.omo-documents__icon {
    width: 34px;
    height: 34px;
    display: block;
    object-fit: contain;
}

.omo-documents__favicon-badge {
    position: absolute;
    right: -4px;
    bottom: -4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 999px;
    background: var(--color-surface, #fff);
    box-shadow:
        0 4px 10px rgba(15, 23, 42, 0.12),
        inset 0 0 0 1px color-mix(in srgb, var(--color-border) 84%, white 16%);
    overflow: hidden;
}

.omo-documents__favicon-image {
    width: 14px;
    height: 14px;
    display: block;
    object-fit: contain;
}

.omo-documents__content {
    display: grid;
    gap: 10px;
    min-width: 0;
}

.omo-documents__eyebrow {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 10px;
}

.omo-documents__item-head {
    display: block;
    margin: 0;
}

.omo-documents__title-line {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    max-width: 100%;
}

.omo-documents__selection-control {
    display: inline-flex;
    align-self: center;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    margin: 0;
    cursor: pointer;
}

.omo-documents__selection-control input {
    width: 17px;
    height: 17px;
    margin: 0;
    accent-color: var(--color-primary, #2563eb);
    cursor: pointer;
}

.omo-documents__selection-placeholder {
    display: inline-block;
    width: 17px;
    height: 17px;
    flex: 0 0 17px;
}

.omo-documents__kind-detail {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, #fef3c7 70%, white 30%);
    color: #a16207;
    font-size: 0.78rem;
    font-weight: 700;
}

.omo-documents__date {
    color: var(--color-text-light);
    font-size: 0.84rem;
    margin-left: auto;
}

.omo-documents__title {
    display: block;
}

.omo-documents__scope-capsule {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 84%, var(--color-surface, #ffffff));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border, #d1d5db) 86%, transparent);
}

.omo-documents__scope-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    opacity: 0.86;
}

.omo-documents__scope-icon img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.omo-documents__scope-separator {
    width: 1px;
    height: 12px;
    background: color-mix(in srgb, var(--color-text-light, #64748b) 32%, transparent);
    border-radius: 999px;
}

.omo-documents__context {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 6px;
    color: var(--color-text-light);
    font-size: 0.84rem;
    line-height: 1.4;
}

.omo-documents__context-separator {
    opacity: 0.65;
}

.omo-documents__context-link {
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    line-height: inherit;
    text-decoration: underline;
    text-underline-offset: 0.14em;
    cursor: pointer;
}

.omo-documents__context-link:hover {
    color: var(--color-primary);
}

.omo-documents__menu {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 0;
}

.omo-documents__menu--folder-header {
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    z-index: 7;
}

.omo-documents__menu-toggle {
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
}

.omo-documents__meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-documents__keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.omo-documents__keyword-tag {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 9px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface-alt) 82%, white 18%);
    color: color-mix(in srgb, var(--color-primary) 78%, #334155 22%);
    font-size: 0.76rem;
    line-height: 1;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border) 82%, white 18%);
}

.omo-documents__item--file-card {
    border-color: color-mix(in srgb, #cbd5e1 20%, var(--color-border));
    background: var(--color-surface);
    box-shadow: 0 14px 32px -30px rgba(15, 23, 42, 0.28);
}

.omo-documents__item--folder-card {
    border-color: color-mix(in srgb, #fcd34d 34%, var(--color-border));
    background: color-mix(in srgb, #f59e0b 13%, var(--color-surface));
    box-shadow: 0 18px 38px -30px rgba(180, 83, 9, 0.26);
}

.omo-documents__item--folder-card .omo-documents__icon-box {
    background: color-mix(in srgb, #f59e0b 18%, var(--color-surface-alt));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, #f59e0b 24%, var(--color-border));
}

.omo-documents__item--file-card .omo-documents__icon-box {
    background: color-mix(in srgb, var(--color-surface-alt) 84%, white 16%);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border) 84%, white 16%);
}

.omo-documents__item-shell--missing-upload .omo-documents__item {
    border-color: color-mix(in srgb, #dc2626 72%, var(--color-border));
    background: color-mix(in srgb, #fef2f2 78%, var(--color-surface));
    box-shadow: inset 4px 0 0 #dc2626, 0 14px 32px -30px rgba(185, 28, 28, 0.44);
}

.omo-documents__item-shell--missing-upload .omo-documents__icon-box {
    background: color-mix(in srgb, #fee2e2 74%, var(--color-surface));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, #dc2626 45%, var(--color-border));
}

.omo-documents__missing-upload-badge {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    min-height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 0.74rem;
    font-weight: 800;
    line-height: 1;
    box-shadow: inset 0 0 0 1px rgba(185, 28, 28, 0.24);
}

.omo-documents__item:hover,
.omo-documents__item:focus-visible,
.omo-documents__folder-toggle:hover .omo-documents__item,
.omo-documents__folder-toggle:focus-visible .omo-documents__item {
    transform: translateY(-1px);
    box-shadow: 0 20px 40px -30px rgba(15, 23, 42, 0.34);
}

.omo-documents__item.is-selected {
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 72%, var(--color-border, #e5e7eb));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 18%, transparent);
}

.omo-documents__item--compact.is-selected {
    background: color-mix(in srgb, var(--color-primary, #2563eb) 8%, var(--color-surface, #fff));
    box-shadow: inset 3px 0 0 color-mix(in srgb, var(--color-primary, #2563eb) 72%, transparent);
}

.omo-documents__folder:not(.is-collapsed) .omo-documents__folder-chevron {
    transform: rotate(180deg);
    background: color-mix(in srgb, #f59e0b 18%, var(--color-surface-alt));
    color: #b45309;
}

.omo-documents__folder:not(.is-collapsed) .omo-documents__folder-card {
    border-color: color-mix(in srgb, #f59e0b 38%, var(--color-border));
}

.omo-documents__item--compact {
    padding-right: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

.omo-documents__item-shell--has-menu .omo-documents__item--compact {
    padding-right: 0;
}

.omo-documents__item--compact .omo-documents__scope-capsule {
    min-height: 22px;
    padding: 0 7px;
    gap: 5px;
}

.omo-documents__item--compact .omo-documents__scope-icon {
    width: 13px;
    height: 13px;
}

.omo-documents__compact-context-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-width: 0;
}

.omo-documents__compact-context-separator {
    opacity: 0.5;
}

.omo-documents__context-link--compact {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    color: inherit;
    text-decoration: none;
    white-space: nowrap;
}

.omo-documents__compact-tags--empty {
    color: var(--color-text-light);
}

.omo-documents__item--compact .omo-documents__context,
.omo-documents__item--compact .omo-documents__description,
.omo-documents__item--compact .omo-documents__keywords,
.omo-documents__item--compact .omo-documents__meta-row,
.omo-documents__item--compact .omo-documents__eyebrow,
.omo-documents__item--compact .omo-documents__item-head {
    display: none;
}

.omo-documents--compact .omo-documents__item:hover,
.omo-documents--compact .omo-documents__item:focus-visible,
.omo-documents--compact .omo-documents__folder-toggle:hover .omo-documents__item,
.omo-documents--compact .omo-documents__folder-toggle:focus-visible .omo-documents__item {
    transform: none;
    box-shadow: none;
    background: color-mix(in srgb, var(--color-primary) 4%, var(--color-surface));
}

@media (max-width: 768px) {
    .omo-documents__title-row {
        flex-wrap: wrap;
        gap: 6px 10px;
    }

    .omo-documents__header-actions {
        align-items: stretch;
        justify-content: flex-start;
    }

    .omo-documents__header-actions {
        grid-template-columns: 1fr;
    }

    .omo-documents__header-main-actions {
        width: 100%;
    }

    .omo-documents__header-main-actions .omo-documents__new-button {
        width: 100%;
    }

    .omo-documents__item {
        padding-right: 50px;
    }

    .omo-documents__item-shell--has-menu .omo-documents__item {
        padding-right: 92px;
    }

    .omo-documents__item-frame {
        grid-template-columns: 52px minmax(0, 1fr);
        gap: 12px;
        padding: 14px 14px 14px 12px;
    }

    .omo-documents__item-frame--with-selection {
        grid-template-columns: 24px 52px minmax(0, 1fr);
    }

    .omo-documents__icon-box {
        width: 42px;
        height: 42px;
        border-radius: var(--radius-md);
    }

    .omo-documents__icon {
        width: 24px;
        height: 24px;
    }

    .omo-documents__folder-content {
        margin-left: 18px;
        padding-left: 14px;
    }

    .omo-documents__item--compact {
        padding-right: 0;
    }

    .omo-documents__item-shell--has-menu .omo-documents__item--compact {
        padding-right: 0;
    }

    .omo-documents__date {
        margin-left: 0;
    }
}

@media (max-width: 1024px) {
    .omo-documents__header-main-actions {
        width: auto;
        position: static;
        z-index: auto;
    }

    .omo-documents__header-main-actions .omo-mobile-corner-action {
        width: 42px;
        min-width: 42px;
        max-width: 42px;
        flex: 0 0 42px;
        border-radius: 0 0 0 var(--radius-md) !important;
    }

    .omo-documents__header-actions {
        align-items: flex-start;
        grid-template-columns: minmax(0, 1fr);
        gap: 10px;
    }
}
</style>
