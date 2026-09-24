<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;
use dbObject\Organization;

require_once __DIR__ . '/list_entries.php';

$sourceLang = [
    'documents.list.more' => [
        'text' => 'Afficher la suite',
        'context' => 'Fallback button to render the next batch of documents, also triggered while scrolling.',
    ],
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
    'documents.action.create_from_template' => [
        'text' => 'Créer depuis un modèle',
        'context' => 'Accessible label for the document template menu beside the new document action.',
    ],
    'documents.template.mark' => [
        'text' => 'Ajouter à la liste des modèles',
        'context' => 'Menu action that makes a document reusable as a template.',
    ],
    'documents.template.unmark' => [
        'text' => 'Retirer de la liste des modèles',
        'context' => 'Menu action that stops exposing a document as a template.',
    ],
    'documents.template.badge' => [
        'text' => 'Modèle',
        'context' => 'Accessible label shown beside document titles that are reusable templates.',
    ],
    'documents.upload.drop_root' => [
        'text' => 'Deposez les fichiers pour les televerser dans cette liste.',
        'context' => 'Feedback shown while files are dragged over the root document list.',
    ],
    'documents.upload.drop_folder' => [
        'text' => 'Deposez les fichiers dans ce dossier.',
        'context' => 'Feedback shown while files are dragged over an uploadable document folder.',
    ],
    'documents.upload.success' => [
        'one' => '{count} fichier televerse.',
        'other' => '{count} fichiers televerses.',
        'context' => 'Confirmation displayed after files dropped into the documents list have been uploaded.',
    ],
    'documents.upload.error' => [
        'text' => 'Le televersement des fichiers a echoue.',
        'context' => 'Fallback error displayed when a file dropped into the documents list cannot be uploaded.',
    ],
    'documents.upload.forbidden' => [
        'text' => 'Vous n avez pas le droit de creation de fichier ici.',
        'context' => 'Feedback shown when files are dragged over a document list or folder where the user cannot create files.',
    ],
    'documents.move.drop_root' => [
        'text' => 'Deposez le document pour le deplacer a la racine de cette liste.',
        'context' => 'Feedback shown while a document is dragged over the root document list.',
    ],
    'documents.move.drop_folder' => [
        'text' => 'Deposez le document pour le deplacer dans ce dossier.',
        'context' => 'Feedback shown while a document is dragged over a folder that can receive it.',
    ],
    'documents.move.drop_forbidden' => [
        'text' => 'Vous n avez pas le droit de deplacer ce document ici.',
        'context' => 'Feedback shown while a document is dragged over a destination where moving is forbidden.',
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
    'documents.nextcloud.initial' => [
        'text' => 'Ouvrez le dossier pour charger son contenu distant.',
        'context' => 'Initial lazy-loading state of a referenced NextCloud folder.',
    ],
    'documents.nextcloud.loading' => [
        'text' => 'Chargement du dossier distant...',
        'context' => 'Loading state while a referenced NextCloud folder is read.',
    ],
    'documents.nextcloud.error' => [
        'text' => 'Impossible de lire le dossier distant.',
        'context' => 'Error shown when a referenced NextCloud folder cannot be read.',
    ],
    'documents.nextcloud.empty' => [
        'text' => 'Dossier vide.',
        'context' => 'Empty state for a referenced NextCloud folder.',
    ],
    'documents.nextcloud.remote_folder' => [
        'text' => 'Dossier distant',
        'context' => 'Compact type label for a referenced NextCloud folder.',
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
    'documents.selection.merge' => [
        'text' => 'Fusionner les documents HTML sélectionnés',
        'context' => 'Bulk action merging selected HTML documents into a new document.',
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
    'documents.folder.unloaded' => [
        'text' => 'Ouvrir pour afficher le contenu.',
        'context' => 'Placeholder shown before a local document folder is loaded.',
    ],
    'documents.folder.loading' => [
        'text' => 'Chargement du dossier...',
        'context' => 'Placeholder shown while a local document folder is loading.',
    ],
    'documents.folder.empty' => [
        'text' => 'Dossier vide.',
        'context' => 'Message shown for a loaded local document folder without children.',
    ],
    'documents.folder.error' => [
        'text' => 'Impossible de charger ce dossier.',
        'context' => 'Fallback error shown when loading a local document folder fails.',
    ],
];

$sourceLang = array_merge(omoDocumentsGetListGroupSourceLang(), $sourceLang);

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
$isPvApplicationTab = !empty($applicationViewPreferences['isPvApplicationTab']);
commonReleaseReadOnlySession();
\dbObject\DbObject::enableReadOnlyMemoization();
$browserRestore = omoApplicationViewPreferencesGetBrowserRestore($applicationViewPreferences);
$documentScope = omoApiNormalizeContextScope(
    $_GET['document_scope'] ?? $browserRestore['view']['scope']
        ?? omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'document_scope', 'scope', 'contextual'),
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
$canDirectUploadToCurrentContext = $canCreateDocument && $organization->hasDocumentStorage();
$canMoveToCurrentContext = $canCreateDocument;
$newDocumentUrl = '/omo/api/documents/create.php?oid=' . $currentOrganizationId . ($effectiveCurrentHolonId > 0 ? '&cid=' . $effectiveCurrentHolonId : '');
$resolveDocumentTemplateIconUrl = static function (Document $document): string {
    if ($document->isFolder()) {
        return '/omo/assets/images/documents/folder.png';
    }

    return match ($document->getDocumentType()) {
        Document::TYPE_EXTERNAL_LINK => '/omo/assets/images/documents/link.png',
        Document::TYPE_UPLOADED_FILE => match ($document->getStoredFileKind()) {
            'image' => '/omo/assets/images/documents/image.png',
            'video' => '/omo/assets/images/documents/video.png',
            'audio' => '/omo/assets/images/documents/audio.png',
            'text' => '/omo/assets/images/documents/text.png',
            'spreadsheet' => '/omo/assets/images/documents/spreadsheet-kind.png',
            'presentation' => '/omo/assets/images/documents/presentation.png',
            'drawing' => '/omo/assets/images/documents/drawing.png',
            default => '/omo/assets/images/documents/download.png',
        },
        Document::TYPE_PV => '/omo/assets/images/documents/pv.png',
        Document::TYPE_ETHERPAD => '/omo/assets/images/documents/collaborative.png',
        Document::TYPE_ETHERCALC => '/omo/assets/images/documents/spreadsheet.png',
        default => '/omo/assets/images/documents/file.png',
    };
};
$documentTemplateGroups = [];
if ($canCreateDocument) {
    $documentTemplates = new \dbObject\ArrayDocument();
    $documentTemplates->loadDocumentTemplatesForOrganization($currentOrganizationId);
    foreach ($documentTemplates as $documentTemplate) {
        if (!($documentTemplate instanceof Document)
            || !$documentTemplate->canUseAsDocumentTemplateInOrganizationContext($currentOrganizationId, $effectiveCurrentHolonId > 0 ? $effectiveCurrentHolonId : null)) {
            continue;
        }
        $templateHolonId = (int)$documentTemplate->get('IDholon');
        $templateGroupKey = $templateHolonId > 0 ? 'holon-' . $templateHolonId : 'organization';
        $templateGroupLabel = $documentTemplate->getTemplateGroupLabel();
        if ($templateGroupLabel === '') {
            $templateGroupLabel = trim((string)$organization->get('name'));
        }
        if (!isset($documentTemplateGroups[$templateGroupKey])) {
            $documentTemplateGroups[$templateGroupKey] = [
                'label' => $templateGroupLabel,
                'templates' => [],
            ];
        }

        $templateTitle = trim((string)$documentTemplate->get('title'));
        $documentTemplateGroups[$templateGroupKey]['templates'][] = [
            'id' => (int)$documentTemplate->getId(),
            'label' => $templateTitle !== '' ? $templateTitle : ('Document #' . (int)$documentTemplate->getId()),
            'iconUrl' => $resolveDocumentTemplateIconUrl($documentTemplate),
        ];
    }
}
uasort($documentTemplateGroups, static function (array $left, array $right): int {
    return strnatcasecmp((string)$left['label'], (string)$right['label']);
});
foreach ($documentTemplateGroups as &$documentTemplateGroup) {
    usort($documentTemplateGroup['templates'], static function (array $left, array $right): int {
        return strnatcasecmp((string)$left['label'], (string)$right['label']);
    });
}
unset($documentTemplateGroup);

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
        $scopeHolonIds,
        $currentContextHolon instanceof Holon && $rootHolon instanceof Holon
            && (int)$currentContextHolon->getId() === (int)$rootHolon->getId()
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
    if ((int)$document->get('IDdocument_parent') > 0) {
        continue;
    }

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
    $canMergeDocument = $document->supportsHtmlContent()
        && $canManageDocument
        && $document->canEditInOrganizationContext($documentOrganizationId, $currentUserId, false);
    $canManageLifecycle = $document->canManageLifecycle($documentOrganizationId, $currentUserId);
    $associatedEvent = $pvEventsById[(int)$document->get('IDevent')] ?? null;
    $hasUpcomingPvEvent = $document->isPvDocument()
        && $associatedEvent instanceof \dbObject\Event
        && $associatedEvent->isUpcoming();
    $pvPreparationUrl = $canOpenPvEditor
        ? $document->buildPvEditorUrl($currentOrganizationId)
        : '';
    $isPvDocument = $document->isPvDocument();
    $isPvValidated = $isPvDocument && $document->isPvValidated();
    $canOpenInPvApplicationTab = !$isPvDocument || $isPvValidated;
    $canOpenInCurrentView = !$isPvApplicationTab || $canOpenInPvApplicationTab;
    $isFolder = $document->isFolder();
    $canUploadToFolder = $isFolder
        && !$document->isNextcloudFolder()
        && $organization->hasDocumentStorage()
        && Document::canCreateInOrganizationContext(
            $documentOrganizationId,
            $documentHolonId > 0 ? $documentHolonId : null,
            $currentUserId,
            $documentId,
            true
        );
    $canMoveToFolder = $isFolder && Document::canCreateInOrganizationContext(
        $documentOrganizationId,
        $documentHolonId > 0 ? $documentHolonId : null,
        $currentUserId,
        $documentId,
        true
    );
    $isExternalLink = $document->isExternalLink();
    $isDocumentTemplate = $document->isDocumentTemplate();
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
        'holonId' => $documentHolonId,
        'href' => '/memo/' . $documentId,
        'title' => $documentTitle,
        'listTitle' => $listTitle,
        'documentType' => $document->getDocumentType(),
        'isTemplate' => $isDocumentTemplate,
        'canManageTemplate' => $document->isTemplateEligible() && ($document->isPvDocument()
            ? $document->canUserManagePvDocument($currentUserId)
            : $canManageDocument),
        'isPvValidated' => $isPvValidated,
        'canOpenInPvApplicationTab' => $canOpenInPvApplicationTab,
        'storedFileKind' => $document->isUploadedFile() ? $document->getStoredFileKind() : '',
        'isMissingUploadedFile' => $document->hasMissingUploadedFile(),
        'canExportPdf' => $document->isPvDocument(),
        'pdfExportUrl' => $document->isPvDocument()
            ? '/omo/api/documents/pv/export_pdf.php?id=' . rawurlencode((string)$documentId)
                . '&oid=' . rawurlencode((string)$currentOrganizationId)
            : '',
        'isFolder' => $isFolder,
        'isNextcloudFolder' => $document->isNextcloudFolder(),
        'childrenLoaded' => !$isFolder,
        'canUpload' => $canUploadToFolder,
        'canMoveInto' => $canMoveToFolder,
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
        'canMerge' => $canMergeDocument,
        'canArchive' => $canManageLifecycle && !$document->isArchived(),
        'canDelete' => $document->canDeleteInOrganizationContext($documentOrganizationId, $currentUserId)
            && (int)$document->get('IDevent') <= 0
            && !isset($documentListMetadata['documentsWithChildren'][$documentId]),
        'canEdit' => $document->isPvDocument()
            ? ($canOpenInCurrentView && $canOpenPvEditor)
            : (
                $canManageDocument
                 || (!$document->isEtherpadDocument() && !$document->isEthercalcDocument() && !$document->isWhiteboardDocument() && $document->canEditInOrganizationContextWithVisibilityRules($documentOrganizationId, $currentUserId, $visibilityRule, $editVisibilityRule, $documentViewerContext))
            ),
        'editUrl' => $document->isPvDocument()
            ? ($canOpenInCurrentView ? $pvPreparationUrl : '')
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
            'isPvValidated' => $requestedCanOpenDirectly
                && $requestedOpenDocument->isPvDocument()
                && $requestedOpenDocument->isPvValidated(),
            'canOpenInPvApplicationTab' => !$requestedOpenDocument->isPvDocument()
                || ($requestedCanOpenDirectly && $requestedOpenDocument->isPvValidated()),
            'isFolder' => $requestedOpenDocument->isFolder(),
            'openInNewWindow' => false,
            'externalUrl' => '',
            'pvPreparationUrl' => $requestedCanOpenPvEditor
                ? $requestedOpenDocument->buildPvEditorUrl($currentOrganizationId)
                : '',
            'hasUpcomingPvEvent' => $requestedCanOpenDirectly ? $requestedOpenDocument->hasUpcomingAssociatedEvent() : false,
            'canEdit' => $requestedOpenDocument->isPvDocument()
                ? (
                    (!$isPvApplicationTab || $requestedOpenDocument->isPvValidated())
                    && $requestedCanOpenPvEditor
                )
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
                ? (
                    (!$isPvApplicationTab || $requestedOpenDocument->isPvValidated()) && $requestedCanOpenPvEditor
                        ? $requestedOpenDocument->buildPvEditorUrl($currentOrganizationId)
                        : ''
                )
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
            'isPvValidated' => false,
            'canOpenInPvApplicationTab' => false,
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
    data-omo-document-pv-application-tab="<?= $isPvApplicationTab ? '1' : '0' ?>"
    data-omo-document-can-upload="<?= $canDirectUploadToCurrentContext ? '1' : '0' ?>"
    data-omo-document-can-move-here="<?= $canMoveToCurrentContext ? '1' : '0' ?>"
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
                    <button type="button" class="generic-action-button generic-action-button--secondary omo-documents__bulk-action-button" data-omo-documents-bulk-action="merge" title="<?= $escape(omoDocumentsScopeT('documents.selection.merge')) ?>" aria-label="<?= $escape(omoDocumentsScopeT('documents.selection.merge')) ?>" hidden>
                        <span class="omo-documents__bulk-action-icon omo-documents__bulk-action-icon--merge" aria-hidden="true"></span>
                    </button>
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
                    <div class="omo-documents__new-actions">
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--main omo-documents__new-button omo-mobile-corner-action"
                            aria-label="<?= $escape(omoDocumentsScopeT('documents.action.new')) ?>"
                            data-omo-documents-new
                            data-omo-documents-new-url="<?= $escape($newDocumentUrl) ?>"
                        ><span class="omo-mobile-corner-action__text"><?= $escape(omoDocumentsScopeT('documents.action.new')) ?></span></button>
                        <?php if (count($documentTemplateGroups) > 0): ?>
                            <div class="omo-documents__template-picker generic-menu" data-omo-document-template-picker>
                                <button type="button" class="generic-action-button generic-action-button--main omo-documents__template-picker-toggle" data-omo-document-template-picker-toggle aria-label="<?= $escape(omoDocumentsScopeT('documents.action.create_from_template')) ?>" aria-haspopup="menu" aria-expanded="false">&#9662;</button>
                                <div class="omo-documents__template-picker-panel generic-menu-panel" data-omo-document-template-picker-panel role="menu" hidden>
                                    <?php foreach ($documentTemplateGroups as $documentTemplateGroup): ?>
                                        <div class="generic-menu-group" role="group" aria-label="<?= $escape((string)$documentTemplateGroup['label']) ?>">
                                            <span class="generic-menu-group-label"><?= $escape((string)$documentTemplateGroup['label']) ?></span>
                                            <?php foreach ($documentTemplateGroup['templates'] as $documentTemplateOption): ?>
                                                <button type="button" class="generic-menu-item omo-documents__template-menu-item" role="menuitem" data-omo-document-template-create="<?= (int)$documentTemplateOption['id'] ?>"><img class="omo-documents__template-menu-icon black-icon" src="<?= $escape((string)$documentTemplateOption['iconUrl']) ?>" alt="" aria-hidden="true"><span><?= $escape($documentTemplateOption['label']) ?></span></button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
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
                        <?php if (!empty($applicationViewPreferences['canSavePersonal']) || !empty($applicationViewPreferences['canSaveTemporary'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary"<?= !empty($applicationViewPreferences['canSavePersonal']) ? ' data-omo-documents-filter-save' : '' ?> data-omo-app-view-save-scope="<?= $escape($applicationViewPreferences['primarySaveScope']) ?>"><?= $escape(omoDocumentsScopeT('documents.filters.save_view')) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= $escape($applicationViewPreferences['primarySaveScope']) ?>"><?= $escape($applicationViewPreferences['primarySaveLabel'] ?? '') ?></button>
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

                foreach (array_slice($documentEntries, 0, 30) as $entry):
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
                            <?php $entryCanOpen = !$isPvApplicationTab || !empty($entry['canOpenInPvApplicationTab']); ?>
                            <article class="omo-documents__item-shell generic-file-list__item-shell<?= !empty($entry['isMissingUploadedFile']) ? ' omo-documents__item-shell--missing-upload' : '' ?>">
                                <div
                                    class="omo-documents__item omo-card<?= $entryCanOpen ? ' omo-card--interactive' : ' omo-documents__item--unavailable' ?>"
                                    <?= $entryCanOpen ? 'role="button" tabindex="0"' : 'aria-disabled="true"' ?>
                                    data-omo-document-id="<?= $escape($entry['id']) ?>"
                                    data-omo-document-href="<?= $escape($entry['href']) ?>"
                                    data-omo-document-context-url="<?= $escape($entry['contextUrl']) ?>"
                                    data-omo-document-external-url="<?= $escape($entry['externalUrl']) ?>"
                                    data-omo-document-open-in-new-window="<?= !empty($entry['openInNewWindow']) ? '1' : '0' ?>"
                                    data-omo-document-type="<?= $escape($entry['documentType']) ?>"
                                    data-omo-document-pv-editor-url="<?= $escape($entry['pvPreparationUrl'] ?? '') ?>"
                                    data-omo-document-can-open-in-pv-tab="<?= !empty($entry['canOpenInPvApplicationTab']) ? '1' : '0' ?>"
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

            <div class="omo-overlay-drawer<?= !empty($applicationViewPreferences['isPvApplicationTab']) ? ' omo-overlay-drawer--detail-panel' : '' ?> omo-documents__detail-drawer" data-omo-document-detail-drawer hidden>
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
            <script src="/common/drawer/subdrawer.js?v=20260906-slide-right"></script>
            <script src="/omo/assets/js/application-view-preferences.js?v=20260917-filter-hierarchy"></script>
            <script src="<?= commonAssetUrl('/omo/api/documents/progressive-list.js') ?>"></script>
<?= commonPageScriptTags('/omo/api/documents/list.js', [
    'omoDocumentsMissingUploadedFileLabel' => omoDocumentsScopeT('documents.upload_missing.badge'),
    'omoDocumentsNextcloudInitialLabel' => omoDocumentsScopeT('documents.nextcloud.initial'),
    'omoDocumentsNextcloudLoadingLabel' => omoDocumentsScopeT('documents.nextcloud.loading'),
    'omoDocumentsNextcloudErrorLabel' => omoDocumentsScopeT('documents.nextcloud.error'),
    'omoDocumentsNextcloudEmptyLabel' => omoDocumentsScopeT('documents.nextcloud.empty'),
    'omoDocumentsNextcloudFolderLabel' => omoDocumentsScopeT('documents.nextcloud.remote_folder'),
    'omoDocumentsFolderUnloadedLabel' => omoDocumentsScopeT('documents.folder.unloaded'),
    'omoDocumentsFolderLoadingLabel' => omoDocumentsScopeT('documents.folder.loading'),
    'omoDocumentsFolderEmptyLabel' => omoDocumentsScopeT('documents.folder.empty'),
    'omoDocumentsFolderErrorLabel' => omoDocumentsScopeT('documents.folder.error'),
    'omoDocumentsEtherpadIconLabel' => omoDocumentsScopeT('documents.icon.etherpad'),
    'omoDocumentsEthercalcIconLabel' => omoDocumentsScopeT('documents.icon.ethercalc'),
    'omoDocumentsWhiteboardIconLabel' => omoDocumentsScopeT('documents.icon.whiteboard'),
    'image' => omoDocumentsScopeT('documents.icon.image'),
    'video' => omoDocumentsScopeT('documents.icon.video'),
    'audio' => omoDocumentsScopeT('documents.icon.audio'),
    'text' => omoDocumentsScopeT('documents.icon.text'),
    'spreadsheet' => omoDocumentsScopeT('documents.icon.spreadsheet'),
    'presentation' => omoDocumentsScopeT('documents.icon.presentation'),
    'drawing' => omoDocumentsScopeT('documents.icon.drawing'),
    'root' => omoDocumentsScopeT('documents.upload.drop_root'),
    'folder' => omoDocumentsScopeT('documents.upload.drop_folder'),
    'success' => omoDocumentsScopeT('documents.upload.success'),
    'error' => omoDocumentsScopeT('documents.upload.error'),
    'forbidden' => omoDocumentsScopeT('documents.upload.forbidden'),
    'root2' => omoDocumentsScopeT('documents.move.drop_root'),
    'folder2' => omoDocumentsScopeT('documents.move.drop_folder'),
    'forbidden2' => omoDocumentsScopeT('documents.move.drop_forbidden'),
    'isPvApplicationTab' => ($isPvApplicationTab),
    'emptyStateMessage' => $documentsEmptyMessage,
    'moreLabel' => omoDocumentsScopeT('documents.list.more'),
    'documentsDateColumnCreated' => omoDocumentsScopeT('documents.date_column.created'),
    'documentsDateColumnUpdated' => omoDocumentsScopeT('documents.date_column.updated'),
    'tooltipLabel' => omoDocumentsScopeT('documents.scope.view'),
    'documentsScopeEdit' => omoDocumentsScopeT('documents.scope.edit'),
    'documentsSelectionCount' => omoDocumentsScopeT('documents.selection.count'),
    'documentsSelectionToggle' => omoDocumentsScopeT('documents.selection.toggle'),
    'documentsMenuActionError' => omoDocumentsScopeT('documents.menu.action_error'),
    'documentsTemplateBadge' => omoDocumentsScopeT('documents.template.badge'),
    'title' => omoDocumentsScopeT('documents.drawer.detail_title'),
    'documentsDrawerDetailDescription' => omoDocumentsScopeT('documents.drawer.detail_description'),
    'documentsActionLoading' => $escape(omoDocumentsScopeT('documents.action.loading')),
    'documentsErrorLoadDocument' => $escape(omoDocumentsScopeT('documents.error.load_document')),
    'documentsSearchEmpty' => omoDocumentsScopeT('documents.search.empty'),
    'visibleDocumentsCount' => (string)$visibleDocumentsCount
                                            . ($totalDocumentsCount > $visibleDocumentsCount ? ' (' . $totalDocumentsCount . ')' : ''),
    'documentsSelectionConfirmDelete' => omoDocumentsScopeT('documents.selection.confirm_delete'),
    'documentsSelectionConfirmArchive' => omoDocumentsScopeT('documents.selection.confirm_archive'),
]) ?>
        <script src="<?= commonAssetUrl('/omo/api/documents/scope.js') ?>"></script>
        <?= commonPageScriptTags('/omo/api/documents/drawers.js', [
    'documentsDrawerEditorTitle' => omoDocumentsScopeT('documents.drawer.editor_title'),
    'documentsDrawerEditorDescription' => omoDocumentsScopeT('documents.drawer.editor_description'),
    'documentsActionLoading' => $escape(omoDocumentsScopeT('documents.action.loading')),
    'documentsErrorLoadEditor' => $escape(omoDocumentsScopeT('documents.error.load_editor')),
    'documentsSelectionMerge' => omoDocumentsScopeT('documents.selection.merge'),
    'documentsSelectionMove' => omoDocumentsScopeT('documents.selection.move'),
    'documentsDrawerDetailDescription' => omoDocumentsScopeT('documents.drawer.detail_description'),
    'documentsErrorLoadDocument' => $escape(omoDocumentsScopeT('documents.error.load_document')),
    'text' => 'Éditer le document',
    'text2' => 'Modification du document dans le contexte courant.',
    'documentsMenuExportPdf' => omoDocumentsScopeT('documents.menu.export_pdf'),
    'documentsTemplateUnmark' => omoDocumentsScopeT('documents.template.unmark'),
    'documentsTemplateMark' => omoDocumentsScopeT('documents.template.mark'),
    'documentsMenuArchive' => omoDocumentsScopeT('documents.menu.archive'),
    'documentsMenuDelete' => omoDocumentsScopeT('documents.menu.delete'),
    'documentsMenuConfirmDelete' => omoDocumentsScopeT('documents.menu.confirm_delete'),
    'documentsMenuConfirmArchive' => omoDocumentsScopeT('documents.menu.confirm_archive'),
    'documentsMenuActionError' => omoDocumentsScopeT('documents.menu.action_error'),
]) ?>
    </div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/documents/list.css') ?>">
