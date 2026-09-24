<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/etherpad.php';
require_once dirname(__DIR__, 3) . '/common/ethercalc.php';
require_once dirname(__DIR__, 3) . '/common/collabora.php';
require_once dirname(__DIR__, 3) . '/common/spacedeck.php';
require_once dirname(__DIR__, 3) . '/common/patreon.php';
require_once dirname(__DIR__, 3) . '/common/openai_text.php';
require_once dirname(__DIR__, 3) . '/common/object_visibility_selector.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;
use dbObject\Organization;

$sourceLang = [
    'documents.create.error.edit' => ['text' => 'Impossible de modifier ce document.', 'context' => 'Error shown when the document editor cannot be opened in edit mode.'],
    'documents.create.error.pv_unsupported' => ['text' => 'Ce document PV se crée ici, mais son contenu se modifie via l’éditeur PV dédié.', 'context' => 'Error shown when trying to edit a PV document from the generic documents editor.'],
    'documents.create.error.create' => ['text' => 'Impossible de créer un document dans ce contexte.', 'context' => 'Error shown when the document editor cannot be opened in creation mode.'],
    'documents.create.visibility.help_context_holon' => ['text' => 'Les portées cercle et rôle suivent automatiquement l’espace du document.', 'context' => 'Visibility help text shown when the document has a contextual space.'],
    'documents.create.visibility.help_no_holon' => ['text' => 'Ce document n’est pas lié à un espace. Les portées cercle et rôle ne sont pas disponibles.', 'context' => 'Visibility help text shown when the document has no space but still belongs to an organization.'],
    'documents.create.visibility.help_outside_context' => ['text' => 'Ce document est hors contexte. Les portées d’organisation, cercle et rôle ne sont pas disponibles.', 'context' => 'Visibility help text shown when the document has no organization context.'],
    'documents.create.context.organization' => ['text' => 'Organisation', 'context' => 'Fallback context label used for embeddable documents without a holon.'],
    'documents.create.field.type' => ['text' => 'Type', 'context' => 'Label of the document type field.'],
    'documents.create.field.type_help' => ['text' => 'Choisissez le format du document. Les champs nécessaires apparaissent ensuite.', 'context' => 'Help for the document type field.'],
    'documents.create.type.html' => ['text' => 'Document HTML', 'context' => 'Option label for HTML documents.'],
    'documents.create.type.external' => ['text' => 'Lien externe', 'context' => 'Option label for external links.'],
    'documents.create.type.uploaded' => ['text' => 'Fichier téléversé', 'context' => 'Option label for uploaded files.'],
    'documents.create.type.pv' => ['text' => 'PV', 'context' => 'Option label for PV documents.'],
    'documents.create.type.etherpad' => ['text' => 'Pad coopératif', 'context' => 'Option label for Etherpad documents.'],
    'documents.create.type.collabora' => ['text' => 'Document coopératif', 'context' => 'Option label for Collabora documents.'],
    'documents.create.type.collabora_spreadsheet' => ['text' => 'Classeur collaboratif', 'context' => 'Option label for Collabora spreadsheet documents.'],
    'documents.create.type.collabora_presentation' => ['text' => 'Présentation collaborative', 'context' => 'Option label for collaborative presentation documents.'],
    'documents.create.type.collabora_drawing' => ['text' => 'Dessin collaboratif', 'context' => 'Option label for collaborative drawing documents.'],
    'documents.create.type.whiteboard' => ['text' => 'Tableau blanc collaboratif', 'context' => 'Option label for SpaceDeck whiteboard documents.'],
    'documents.create.type.ethercalc' => ['text' => 'Tableur collaboratif', 'context' => 'Option label for EtherCalc documents.'],
    'documents.create.type.folder' => ['text' => 'Dossier', 'context' => 'Option label for folders.'],
    'documents.create.type.nextcloud_folder' => ['text' => 'Dossier NextCloud', 'context' => 'Option label for a remotely listed NextCloud folder.'],
	'documents.create.type.kdrive_folder' => ['text' => 'Dossier kDrive', 'context' => 'Option label for a remotely listed kDrive folder.'],
	'documents.create.field.kdrive_folder_path' => ['text' => 'Chemin du dossier kDrive', 'context' => 'Label for the selected remote kDrive folder path.'],
	'documents.create.field.kdrive_folder_hint' => ['text' => 'Le chemin est relatif au dossier kDrive configure pour les documents. Le contenu sera relu a chaque ouverture.', 'context' => 'Hint for the remote kDrive folder path.'],
	'documents.create.action.kdrive_browse' => ['text' => 'Parcourir kDrive', 'context' => 'Button opening the remote kDrive folder browser.'],
    'documents.create.field.nextcloud_folder_path' => ['text' => 'Chemin du dossier NextCloud', 'context' => 'Label for the selected remote folder path.'],
    'documents.create.field.nextcloud_folder_hint' => ['text' => 'Le chemin est relatif au dossier NextCloud configure pour les documents. Le contenu sera relu a chaque ouverture.', 'context' => 'Hint for the remote NextCloud folder path.'],
    'documents.create.action.nextcloud_browse' => ['text' => 'Parcourir NextCloud', 'context' => 'Button opening the remote NextCloud folder browser.'],
    'documents.create.nextcloud.empty' => ['text' => 'Aucun sous-dossier disponible.', 'context' => 'Empty state for the NextCloud folder picker.'],
    'documents.create.field.title' => ['text' => 'Titre', 'context' => 'Label of the document title field.'],
    'documents.create.field.title_placeholder' => ['text' => 'Nom du document', 'context' => 'Placeholder shown in the document title field.'],
    'documents.create.field.parent_folder' => ['text' => 'Dossier parent', 'context' => 'Label shown for the parent folder when present.'],
    'documents.create.field.description' => ['text' => 'Résumé', 'context' => 'Label of the document summary field.'],
    'documents.create.field.description_placeholder' => ['text' => 'Présentation rapide du document', 'context' => 'Placeholder shown in the document summary field.'],
    'documents.create.field.tags' => ['text' => 'Tags', 'context' => 'Label of the tag editor field.'],
    'documents.create.field.tags_placeholder' => ['text' => 'Ajouter un tag', 'context' => 'Placeholder shown in the tag input field.'],
    'documents.create.field.tags_hint' => ['text' => 'Écrivez un tag puis utilisez TAB ou une virgule pour le transformer en capsule.', 'context' => 'Hint shown below the tag editor field.'],
    'documents.create.field.tags_remove' => ['text' => 'Retirer le tag', 'context' => 'Accessible label prefix used to remove a tag from the editor.'],
    'documents.create.field.edit_visibility' => ['text' => 'Édition', 'context' => 'Label of the document edit visibility field.'],
    'documents.create.field.visibility' => ['text' => 'Visibilité', 'context' => 'Label of the document visibility field.'],
    'documents.create.field.project_visible_in_holon' => ['text' => 'Afficher dans l’espace', 'context' => 'Checkbox allowing a project-attached document to remain visible in the space document list.'],
    'documents.create.field.project_visible_in_holon_hint' => ['text' => 'Les documents liés à un projet sont masqués dans l’espace par défaut.', 'context' => 'Help text for the project document space visibility checkbox.'],
    'documents.create.field.html' => ['text' => 'Contenu HTML', 'context' => 'Label of the HTML content area.'],
    'documents.create.field.external_url' => ['text' => 'URL externe', 'context' => 'Label of the external URL field.'],
    'documents.create.field.external_url_placeholder' => ['text' => 'https://example.com/', 'context' => 'Placeholder shown in the external URL field.'],
    'documents.create.field.external_url_hint' => ['text' => 'Utilisez une adresse complète en http:// ou https://.', 'context' => 'Hint shown below the external URL field.'],
    'documents.create.field.open_new_window' => ['text' => 'Ouvrir dans une nouvelle fenêtre', 'context' => 'Checkbox label used for external links.'],
    'documents.create.field.pv_hint' => ['text' => 'Le contenu du PV se préparera ensuite dans l’éditeur PV dédié.', 'context' => 'Hint shown when creating a PV document from the generic document creator.'],
    'documents.create.field.etherpad_hint' => ['text' => 'Un nouveau pad sera créé sur le serveur Etherpad de cette organisation.', 'context' => 'Hint shown when creating an Etherpad document.'],
    'documents.create.field.etherpad_missing' => ['text' => 'Aucun serveur Etherpad n’est configuré pour cette organisation.', 'context' => 'Hint shown when Etherpad is not configured.'],
    'documents.create.field.collabora_hint' => ['text' => 'Un nouveau fichier bureautique sera créé dans le stockage de documents choisi puis ouvert avec Collabora.', 'context' => 'Hint shown when creating a Collabora document.'],
    'documents.create.field.collabora_spreadsheet_hint' => ['text' => 'Un nouveau classeur sera créé dans le stockage de documents choisi puis ouvert avec Collabora.', 'context' => 'Hint shown when creating a Collabora spreadsheet document.'],
    'documents.create.field.collabora_presentation_hint' => ['text' => 'Une nouvelle présentation sera créée dans le stockage de documents choisi puis ouverte avec Collabora.', 'context' => 'Hint shown when creating a collaborative presentation.'],
    'documents.create.field.collabora_drawing_hint' => ['text' => 'Un nouveau dessin sera créé dans le stockage de documents choisi puis ouvert avec Collabora.', 'context' => 'Hint shown when creating a collaborative drawing.'],
    'documents.create.field.collabora_missing' => ['text' => 'Configurez un stockage de documents et un serveur Collabora dans les paramètres Documents.', 'context' => 'Hint shown when Collabora is not configured.'],
    'documents.create.field.whiteboard_hint' => ['text' => 'Un nouveau tableau blanc sera créé sur le serveur SpaceDeck puis ouvert dans OMO.', 'context' => 'Hint shown when creating a SpaceDeck whiteboard document.'],
    'documents.create.field.whiteboard_missing' => ['text' => 'Configurez le serveur SpaceDeck dans la configuration du serveur avant de créer un whiteboard.', 'context' => 'Hint shown when SpaceDeck is not configured.'],
    'documents.create.field.ethercalc_hint' => ['text' => 'Un nouveau tableur sera créé sur le serveur EtherCalc configuré pour OMO.', 'context' => 'Hint shown when creating an EtherCalc document.'],
    'documents.create.field.ethercalc_missing' => ['text' => 'Aucun serveur EtherCalc n’est configuré.', 'context' => 'Hint shown when EtherCalc is not configured.'],
    'documents.create.field.pv_template' => ['text' => 'Modèle de base', 'context' => 'Label of the optional PV template selector.'],
    'documents.create.field.pv_template_none' => ['text' => 'PV vide', 'context' => 'Empty option of the PV template selector.'],
    'documents.create.field.pv_template_hint' => ['text' => 'Les groupes, points et contenus du modèle seront copiés sans leurs auteurs ni leurs invités.', 'context' => 'Help text below the PV template selector.'],
    'documents.create.field.upload' => ['text' => 'Fichier', 'context' => 'Label of the uploaded file field.'],
    'documents.create.upload.hint_nextcloud' => ['text' => 'Le fichier sera envoyé vers le stockage de documents configuré pour cette organisation.', 'context' => 'Hint shown when document storage is available.'],
    'documents.create.upload.hint_missing' => ['text' => 'Aucun stockage de documents n’est configuré pour cette organisation.', 'context' => 'Hint shown when no document storage is configured.'],
    'documents.create.upload.current' => ['text' => 'Fichier actuel', 'context' => 'Title shown above the current uploaded file metadata.'],
    'documents.create.upload.remove' => ['text' => 'Supprimer le fichier distant', 'context' => 'Checkbox label used to remove the uploaded file.'],
    'documents.create.upload.replace_confirm' => ['text' => 'Le fichier actuel sera définitivement remplacé par le nouveau fichier. L’ancien fichier sera supprimé. Continuer ?', 'context' => 'Confirmation shown before replacing an uploaded document file.'],
    'documents.icon.image' => ['text' => 'Image', 'context' => 'Alternative text for image file icons.'],
    'documents.icon.video' => ['text' => 'Vidéo', 'context' => 'Alternative text for video file icons.'],
    'documents.icon.text' => ['text' => 'Document texte', 'context' => 'Alternative text for text document icons.'],
    'documents.icon.spreadsheet' => ['text' => 'Tableur', 'context' => 'Alternative text for spreadsheet file icons.'],
    'documents.icon.presentation' => ['text' => 'Présentation', 'context' => 'Alternative text for presentation file icons.'],
    'documents.icon.drawing' => ['text' => 'Dessin', 'context' => 'Alternative text for drawing file icons.'],
    'documents.create.action.cancel' => ['text' => 'Annuler', 'context' => 'Secondary action used to close the document editor.'],
    'documents.create.action.save' => ['text' => 'Enregistrer', 'context' => 'Primary action used to save an existing document.'],
    'documents.create.action.create' => ['text' => 'Créer le document', 'context' => 'Primary action used to create a document.'],
    'documents.create.title.create' => ['text' => 'Nouveau document', 'context' => 'Title shown in the document editor drawer for creation.'],
    'documents.create.title.edit' => ['text' => 'Modifier le document', 'context' => 'Title shown in the document editor drawer for edition.'],
    'documents.create.embed.none' => ['text' => 'Aucun document sélectionné.', 'context' => 'Placeholder shown in the document embed picker preview.'],
    'documents.create.embed.search_placeholder' => ['text' => 'Titre, résumé ou contexte', 'context' => 'Search placeholder used in the document embed picker.'],
    'documents.create.embed.modal_title' => ['text' => 'Insérer un document', 'context' => 'Title of the document embed picker modal.'],
    'documents.create.embed.update' => ['text' => 'Mettre à jour', 'context' => 'Button used to update an embedded document reference.'],
    'documents.create.embed.insert' => ['text' => 'Insérer le document', 'context' => 'Button used to insert a new embedded document reference.'],
];

$lang = omoLoadTranslationBundle('omo_documents_create', $sourceLang);

function omoDocumentsCreateT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$documentHelp = static function ($label, $text): string {
    return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
        . '<summary aria-label="' . omoApiEscape($label) . '">?</summary>'
        . '<div class="generic-context-help__content">' . omoApiEscape($text) . '</div></details>';
};

$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$parentDocumentId = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$projectId = isset($_GET['project_id']) ? max(0, (int)$_GET['project_id']) : 0;
$requestedEditorHost = trim((string)($_GET['editor_host'] ?? ''));
$editorHost = in_array($requestedEditorHost, ['project', 'project_picker'], true) && $projectId > 0
    ? $requestedEditorHost
    : 'documents';
$editorInstance = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_GET['editor_instance'] ?? ''));
$currentUserId = (int)commonGetCurrentUserId();
$escape = 'omoApiEscape';
$document = new Document();
$isEditing = false;
$canCreate = false;
$canUseForm = $canCreate;
$canManageDocument = false;
$canEditDocumentContent = false;
$isProjectDocument = false;
$projectVisibleInHolon = false;
$formErrorMessage = '';
$openAiAvailable = commonOpenAiGetApiKey() !== '';
$canUseAiTools = $openAiAvailable && patreonUserCanUseAi($currentUserId);

if ($documentId > 0) {
    $isEditing = $document->load($documentId);
    if ($isEditing) {
        $organizationId = (int)$document->get('IDorganization');
        $canManageDocument = !$document->isPvDocument()
            && $document->canManageInOrganizationContext($organizationId, $currentUserId, false);
        $canEditDocumentContent = !$document->isPvDocument()
            && $document->canEditInOrganizationContext($organizationId, $currentUserId, false);
        $isProjectDocument = $document->hasProjectAssociation();
        $projectVisibleInHolon = $document->isVisibleInHolonWhenProjectDocument();
    }
    $canUseForm = $isEditing && ($canManageDocument || $canEditDocumentContent);

    if ($canUseForm && $document->isPvDocument()) {
        $canUseForm = false;
        $formErrorMessage = omoDocumentsCreateT('documents.create.error.pv_unsupported');
    }

    if ($canUseForm && ($document->isEtherpadDocument() || $document->isEthercalcDocument()) && !$canManageDocument) {
        $canUseForm = false;
    }

    if ($canUseForm && $canEditDocumentContent && $document->supportsHtmlContent()) {
        $lockResult = $document->touchEditLock($organizationId, $currentUserId);
        if (!is_array($lockResult) || ($lockResult['status'] ?? false) !== true) {
            $canUseForm = false;
            $formErrorMessage = trim((string)($lockResult['text'] ?? 'Ce document est déjà en cours d’édition.'));
        }
    }
}

$isProjectDocument = $isProjectDocument || $projectId > 0;

if ($isEditing && !$canEditDocumentContent) {
    $canUseAiTools = false;
}

$visibilityOptions = ObjectVisibility::getVisibilityTypeOptions();
$documentTitle = '';
$documentDescription = '';
$documentKeywords = '';
$documentContent = '';
$documentType = Document::TYPE_HTML;
$documentExternalUrl = '';
$documentOpenInNewWindow = false;
$documentStoredFilename = '';
$documentStoredFileMime = '';
$documentStoredFileSize = 0;
$documentHasStoredFile = false;
$documentNextcloudFolderPath = '';
$isFolder = false;
$selectedVisibilityType = $organizationId > 0
    ? Document::getDefaultVisibilityTypeForOrganization($organizationId)
    : ObjectVisibility::TYPE_ORGANIZATION;
$selectedEditVisibilityType = $organizationId > 0
    ? Document::getDefaultEditVisibilityTypeForOrganization($organizationId)
    : Document::getDefaultEditVisibilityType();
$disabledVisibilityTypes = array();
$visibilityHelpText = omoDocumentsCreateT('documents.create.visibility.help_context_holon');
$contextHolonId = $isEditing ? (int)$document->get('IDholon') : $holonId;
$parentFolderTitle = '';
$embeddableDocumentsPayload = array();
$pvTemplatesPayload = array();
$organization = new Organization();
$organizationLoaded = $organizationId > 0 && $organization->load($organizationId);
$nextcloudDocumentsAvailable = $organizationLoaded && $organization->hasDocumentStorage();
$pvDocumentsEnabled = $organizationLoaded && $organization->isPvDocumentEnabled();
$nextcloudFoldersAvailable = $organizationLoaded && $organization->hasDocumentStorage();
$remoteFolderTypeLabel = $organizationLoaded && $organization->isKdriveDocumentStorage()
	? omoDocumentsCreateT('documents.create.type.kdrive_folder')
	: omoDocumentsCreateT('documents.create.type.nextcloud_folder');
$remoteFolderPathLabel = $organizationLoaded && $organization->isKdriveDocumentStorage()
	? omoDocumentsCreateT('documents.create.field.kdrive_folder_path')
	: omoDocumentsCreateT('documents.create.field.nextcloud_folder_path');
$remoteFolderHint = $organizationLoaded && $organization->isKdriveDocumentStorage()
	? omoDocumentsCreateT('documents.create.field.kdrive_folder_hint')
	: omoDocumentsCreateT('documents.create.field.nextcloud_folder_hint');
$remoteFolderBrowseLabel = $organizationLoaded && $organization->isKdriveDocumentStorage()
	? omoDocumentsCreateT('documents.create.action.kdrive_browse')
	: omoDocumentsCreateT('documents.create.action.nextcloud_browse');
$etherpadDocumentsAvailable = $organizationLoaded && omoEtherpadCanUseEditingSessions($organization);
$collaboraDocumentsAvailable = $organizationLoaded && $nextcloudDocumentsAvailable && omoCollaboraHasConfig($organization);
$whiteboardDocumentsAvailable = omoSpacedeckHasConfig();
$ethercalcDocumentsAvailable = omoEthercalcHasConfig();
$etherpadGroupAvailable = $etherpadDocumentsAvailable
    || $ethercalcDocumentsAvailable
    || in_array($documentType, [Document::TYPE_ETHERPAD, Document::TYPE_ETHERCALC], true);
$collaboraGroupAvailable = $collaboraDocumentsAvailable
    || in_array($documentType, [Document::TYPE_COLLABORA_DOCUMENT, Document::TYPE_COLLABORA_SPREADSHEET, Document::TYPE_COLLABORA_PRESENTATION, Document::TYPE_COLLABORA_DRAWING], true);
$whiteboardGroupAvailable = $whiteboardDocumentsAvailable || $documentType === Document::TYPE_WHITEBOARD;

if (!$isEditing && $organizationLoaded && $pvDocumentsEnabled) {
    $pvTemplates = new \dbObject\ArrayDocument();
    $pvTemplates->loadVisiblePvTemplatesForOrganization($organizationId);
    foreach ($pvTemplates as $pvTemplate) {
        if (!($pvTemplate instanceof Document) || (int)$pvTemplate->getId() <= 0) {
            continue;
        }

        $templateLabel = trim((string)$pvTemplate->get('title'));
        $templateParent = $pvTemplate->getParentDocument();
        if ($templateParent instanceof Document && trim((string)$templateParent->get('title')) !== '') {
            $templateLabel = trim((string)$templateParent->get('title')) . ' / ' . $templateLabel;
        }
        $pvTemplatesPayload[] = array(
            'id' => (int)$pvTemplate->getId(),
            'label' => $templateLabel !== '' ? $templateLabel : ('PV #' . (int)$pvTemplate->getId()),
        );
    }
}

if (!$isEditing && $parentDocumentId > 0) {
    $parentDocument = new Document();
    if (
        $parentDocument->load($parentDocumentId)
        && (int)$parentDocument->get('IDorganization') === $organizationId
        && $parentDocument->isFolder()
    ) {
        $contextHolonId = (int)$parentDocument->get('IDholon');
        $parentFolderTitle = trim((string)$parentDocument->get('title'));
    } else {
        $parentDocumentId = 0;
    }
}

if (!$isEditing) {
    $canCreate = $organizationId > 0
        && $currentUserId > 0
        && Document::canCreateInOrganizationContext(
            $organizationId,
            $contextHolonId > 0 ? $contextHolonId : null,
            $currentUserId,
            $parentDocumentId,
            true
        );
    $canUseForm = $canCreate;
}

if ($contextHolonId <= 0) {
    $disabledVisibilityTypes[ObjectVisibility::TYPE_CIRCLE] = true;
    $disabledVisibilityTypes[ObjectVisibility::TYPE_ROLE] = true;
    $visibilityHelpText = $organizationId > 0
        ? 'Ce document n’est pas lié à un holon. Les portées cercle et rôle ne sont pas disponibles.'
        : omoDocumentsCreateT('documents.create.visibility.help_outside_context');
    if ($organizationId > 0) {
        $visibilityHelpText = omoDocumentsCreateT('documents.create.visibility.help_no_holon');
    }
} else {
    $contextHolon = new Holon();
    if ($contextHolon->load($contextHolonId)) {
        $contextHolonTypeId = (int)$contextHolon->get('IDtypeholon');
        if ($contextHolonTypeId !== 1) {
            $disabledVisibilityTypes[ObjectVisibility::TYPE_ROLE] = true;
        }

        if ($contextHolonTypeId !== 2 && (int)$contextHolon->getContainingCircleId(false) <= 0) {
            $disabledVisibilityTypes[ObjectVisibility::TYPE_CIRCLE] = true;
        }
    }
}

if ($isEditing) {
    $documentTitle = trim((string)$document->get('title'));
    $documentDescription = trim((string)$document->get('description'));
    $documentKeywords = trim((string)$document->get('keywords'));
    $documentContent = $document->getEffectiveEditingContentForUser($currentUserId);
    $documentType = $document->getDocumentType();
    $documentExternalUrl = $document->getExternalUrl();
    $documentOpenInNewWindow = $document->shouldOpenExternalLinkInNewWindow();
    $documentStoredFilename = $document->getStoredFileDownloadName();
    $documentStoredFileMime = $document->getStoredFileMimeType();
    $documentStoredFileSize = $document->getStoredFileSize();
    $documentHasStoredFile = $document->hasStoredFile();
	$documentNextcloudFolderPath = $document->getNextcloudFolderPath();
    $isFolder = $document->isFolder();
    $parentDocumentId = (int)$document->get('IDdocument_parent');
    if ($parentDocumentId > 0) {
        $parentDocument = $document->getParentDocument();
        if ($parentDocument instanceof Document) {
            $parentFolderTitle = trim((string)$parentDocument->get('title'));
        }
    }
    $visibilityRule = $document->getPrimaryVisibilityRuleRow();
    $selectedVisibilityType = ObjectVisibility::normalizeVisibilityType($visibilityRule['visibility_type'] ?? ObjectVisibility::TYPE_ORGANIZATION);
    $editVisibilityRule = $document->getPrimaryEditVisibilityRuleRow();
    $selectedEditVisibilityType = ObjectVisibility::normalizeVisibilityType($editVisibilityRule['visibility_type'] ?? Document::getDefaultEditVisibilityType());

    if ($organizationId <= 0) {
        $selectedVisibilityType = ObjectVisibility::TYPE_ORGANIZATION;
        $selectedEditVisibilityType = Document::getDefaultEditVisibilityType();
    }
}

$selectedVisibilityType = Document::resolveCompatibleScopeTypeForHolonId(
    $selectedVisibilityType,
    $organizationId,
    $contextHolonId > 0 ? $contextHolonId : null,
    ObjectVisibility::TYPE_ORGANIZATION
);
$selectedEditVisibilityType = Document::resolveCompatibleScopeTypeForHolonId(
    $selectedEditVisibilityType,
    $organizationId,
    $contextHolonId > 0 ? $contextHolonId : null,
    Document::getDefaultEditVisibilityType()
);

if (!empty($disabledVisibilityTypes[$selectedVisibilityType])) {
    $selectedVisibilityType = ObjectVisibility::TYPE_ORGANIZATION;
}

if (!empty($disabledVisibilityTypes[$selectedEditVisibilityType])) {
    $selectedEditVisibilityType = Document::getDefaultEditVisibilityType();
}

if ($organizationId > 0 && $currentUserId > 0 && commonCurrentUserHasOrganizationAccess($organizationId)) {
    $visibleDocuments = new \dbObject\ArrayDocument();
    $visibleDocuments->loadVisibleForOrganization($organizationId);
    $holonTitleCache = array();

    foreach ($visibleDocuments as $visibleDocument) {
        if (
            !($visibleDocument instanceof \dbObject\Document)
            || !$visibleDocument->canBeEmbedded()
            || (int)$visibleDocument->getId() <= 0
            || ($documentId > 0 && (int)$visibleDocument->getId() === $documentId)
        ) {
            continue;
        }

        $itemHolonId = (int)$visibleDocument->get('IDholon');
        $contextLabel = omoDocumentsCreateT('documents.create.context.organization');
        if ($itemHolonId > 0) {
            if (!array_key_exists($itemHolonId, $holonTitleCache)) {
                $holonItem = new Holon();
                $holonTitleCache[$itemHolonId] = $holonItem->load($itemHolonId)
                    ? trim((string)$holonItem->get('title'))
                    : '';
            }

            if (trim((string)$holonTitleCache[$itemHolonId]) !== '') {
                $contextLabel = (string)$holonTitleCache[$itemHolonId];
            }
        }

        $embeddableDocumentsPayload[] = array(
            'id' => (int)$visibleDocument->getId(),
            'title' => trim((string)$visibleDocument->get('title')),
            'description' => trim((string)$visibleDocument->get('description')),
            'contextLabel' => $contextLabel,
        );
    }

    usort($embeddableDocumentsPayload, static function (array $left, array $right): int {
        $leftTitle = mb_strtolower(trim((string)($left['title'] ?? '')), 'UTF-8');
        $rightTitle = mb_strtolower(trim((string)($right['title'] ?? '')), 'UTF-8');
        if ($leftTitle !== $rightTitle) {
            return $leftTitle <=> $rightTitle;
        }

        return ((int)($left['id'] ?? 0)) <=> ((int)($right['id'] ?? 0));
    });
}
?>
<div class="omo-document-editor generic-drawer-content">
    <?php if (!$canUseForm): ?>
        <div class="omo-empty-state"><?= $escape($formErrorMessage !== '' ? $formErrorMessage : ($isEditing ? omoDocumentsCreateT('documents.create.error.edit') : omoDocumentsCreateT('documents.create.error.create'))) ?></div>
    <?php else: ?>
        <?php $documentFormId = 'omoDocumentEditorForm' . ($editorInstance !== '' ? '-' . $editorInstance : ''); ?>
        <div
            hidden
            data-omo-subdrawer-header
            data-omo-subdrawer-title="<?= $escape(omoDocumentsCreateT($isEditing ? 'documents.create.title.edit' : 'documents.create.title.create')) ?>"
            data-omo-subdrawer-description="<?= $escape($isEditing ? trim((string)$document->get('title')) : '') ?>"
        >
            <button
                type="button"
                form="<?= $escape($documentFormId) ?>"
                class="generic-action-button generic-action-button--secondary"
                data-omo-subdrawer-action
                data-omo-document-editor-cancel
            ><?= $escape(omoDocumentsCreateT('documents.create.action.cancel')) ?></button>
            <button
                type="submit"
                form="<?= $escape($documentFormId) ?>"
                class="generic-action-button generic-action-button--main"
                data-omo-subdrawer-action
                data-omo-document-editor-submit
            ><?= $escape($isEditing ? omoDocumentsCreateT('documents.create.action.save') : omoDocumentsCreateT('documents.create.action.create')) ?></button>
        </div>
        <form id="<?= $escape($documentFormId) ?>" class="omo-document-editor__form generic-form-stack generic-form-stack--compact" action="/omo/api/documents/save.php" method="post" enctype="multipart/form-data" data-omo-document-create-form data-omo-document-editor-host="<?= $escape($editorHost) ?>">
            <input type="hidden" name="oid" value="<?= $escape($organizationId) ?>">
            <input type="hidden" name="cid" value="<?= $escape($contextHolonId) ?>">
            <input type="hidden" name="parent_document_id" value="<?= (int)$parentDocumentId ?>">
            <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?= (int)$document->getId() ?>">
            <?php endif; ?>

            <div class="omo-document-editor__grid generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                <fieldset class="omo-document-editor__metadata"<?= $isEditing && !$canManageDocument ? ' disabled' : '' ?>>
                <div class="omo-document-editor__meta-row generic-form-grid generic-form-grid--trio">
                    <div class="omo-document-editor__field generic-form-field">
                        <div class="generic-inline-help">
                            <label class="omo-document-editor__label generic-form-label" for="omo-document-editor-type"><?= $escape(omoDocumentsCreateT('documents.create.field.type')) ?></label>
                            <?= $documentHelp(omoDocumentsCreateT('documents.create.field.type'), omoDocumentsCreateT('documents.create.field.type_help')) ?>
                        </div>
                        <select
                            id="omo-document-editor-type"
                            name="document_type"
                            class="generic-form-control generic-form-control--compact"
                            data-omo-document-type
                            <?= $isEditing ? 'disabled' : '' ?>
                        >
                            <option value="<?= $escape(Document::TYPE_FOLDER) ?>" <?= $documentType === Document::TYPE_FOLDER ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.folder')) ?></option>
							<?php if ($nextcloudFoldersAvailable || $documentType === Document::TYPE_NEXTCLOUD_FOLDER): ?>
                                <option value="<?= $escape(Document::TYPE_NEXTCLOUD_FOLDER) ?>" <?= $documentType === Document::TYPE_NEXTCLOUD_FOLDER ? ' selected' : '' ?>><?= $escape($remoteFolderTypeLabel) ?></option>
							<?php endif; ?>
                            <?php if ($etherpadGroupAvailable || $collaboraGroupAvailable || $whiteboardGroupAvailable): ?>
                            <option disabled aria-hidden="true">--------------------</option>
                            <?php endif; ?>
                            <option value="<?= $escape(Document::TYPE_EXTERNAL_LINK) ?>" <?= $documentType === Document::TYPE_EXTERNAL_LINK ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.external')) ?></option>
                            <?php if ($nextcloudDocumentsAvailable || $documentType === Document::TYPE_UPLOADED_FILE): ?>
                                <option value="<?= $escape(Document::TYPE_UPLOADED_FILE) ?>" <?= $documentType === Document::TYPE_UPLOADED_FILE ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.uploaded')) ?></option>
                            <?php endif; ?>
                            <option value="<?= $escape(Document::TYPE_HTML) ?>" <?= $documentType === Document::TYPE_HTML ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.html')) ?></option>
                            <?php if ($pvDocumentsEnabled): ?>
                                <option value="<?= $escape(Document::TYPE_PV) ?>" <?= $documentType === Document::TYPE_PV ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.pv')) ?></option>
                            <?php endif; ?>
                            <option disabled aria-hidden="true">--------------------</option>
                            <?php if ($etherpadDocumentsAvailable || $documentType === Document::TYPE_ETHERPAD): ?>
                                <option value="<?= $escape(Document::TYPE_ETHERPAD) ?>" <?= $documentType === Document::TYPE_ETHERPAD ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.etherpad')) ?></option>
                            <?php endif; ?>
                            <?php if ($ethercalcDocumentsAvailable || $documentType === Document::TYPE_ETHERCALC): ?>
                                <option value="<?= $escape(Document::TYPE_ETHERCALC) ?>" <?= $documentType === Document::TYPE_ETHERCALC ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.ethercalc')) ?></option>
                            <?php endif; ?>
                            <?php if ($etherpadGroupAvailable && ($collaboraGroupAvailable || $whiteboardGroupAvailable)): ?>
                            <option disabled aria-hidden="true">--------------------</option>
                            <?php endif; ?>
                            <?php if ($collaboraGroupAvailable): ?>
                                <option value="<?= $escape(Document::TYPE_COLLABORA_DOCUMENT) ?>" <?= $documentType === Document::TYPE_COLLABORA_DOCUMENT ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.collabora')) ?></option>
                                <option value="<?= $escape(Document::TYPE_COLLABORA_SPREADSHEET) ?>" <?= $documentType === Document::TYPE_COLLABORA_SPREADSHEET ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.collabora_spreadsheet')) ?></option>
                                <option value="<?= $escape(Document::TYPE_COLLABORA_PRESENTATION) ?>" <?= $documentType === Document::TYPE_COLLABORA_PRESENTATION ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.collabora_presentation')) ?></option>
                                <option value="<?= $escape(Document::TYPE_COLLABORA_DRAWING) ?>" <?= $documentType === Document::TYPE_COLLABORA_DRAWING ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.collabora_drawing')) ?></option>
                            <?php endif; ?>
                            <?php if ($collaboraGroupAvailable && $whiteboardGroupAvailable): ?>
                            <option disabled aria-hidden="true">--------------------</option>
                            <?php endif; ?>
                            <?php if ($whiteboardGroupAvailable): ?>
                                <option value="<?= $escape(Document::TYPE_WHITEBOARD) ?>" <?= $documentType === Document::TYPE_WHITEBOARD ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.whiteboard')) ?></option>
                            <?php endif; ?>
                        </select>
                        <?php if ($isEditing): ?>
                            <input type="hidden" name="document_type" value="<?= $escape($documentType) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="omo-document-editor__field generic-form-field">
                        <?= commonRenderObjectVisibilitySelector(array(
                            'inputName' => 'visibility_type',
                            'fieldLabel' => omoDocumentsCreateT('documents.create.field.visibility'),
                            'ariaLabel' => omoDocumentsCreateT('documents.create.field.visibility'),
                            'selectedValue' => $selectedVisibilityType,
                            'optionLabels' => $visibilityOptions,
                            'disabledValues' => $disabledVisibilityTypes,
                            'idPrefix' => 'omo-document-visibility',
                            'hint' => $visibilityHelpText,
                            'hintAsContextHelp' => true,
                        )) ?>
                    </div>

                    <div class="omo-document-editor__field generic-form-field">
                        <?= commonRenderObjectVisibilitySelector(array(
                            'inputName' => 'edit_visibility_type',
                            'fieldLabel' => omoDocumentsCreateT('documents.create.field.edit_visibility'),
                            'ariaLabel' => omoDocumentsCreateT('documents.create.field.edit_visibility'),
                            'selectedValue' => $selectedEditVisibilityType,
                            'optionLabels' => $visibilityOptions,
                            'disabledValues' => $disabledVisibilityTypes,
                            'idPrefix' => 'omo-document-edit-visibility',
                            'hint' => $visibilityHelpText,
                            'hintAsContextHelp' => true,
                        )) ?>
                    </div>

                    <?php if ($isProjectDocument): ?>
                        <div class="generic-inline-help generic-form-field generic-form-field--full">
                            <label class="omo-document-editor__checkbox generic-checkbox">
                                <input
                                    type="checkbox"
                                    name="project_visible_in_holon"
                                    value="1"
                                    <?= $projectVisibleInHolon ? ' checked' : '' ?>
                                    <?= $isEditing && !$canManageDocument ? ' disabled' : '' ?>
                                >
                                <span><?= $escape(omoDocumentsCreateT('documents.create.field.project_visible_in_holon')) ?></span>
                            </label>
                            <?= $documentHelp(omoDocumentsCreateT('documents.create.field.project_visible_in_holon'), omoDocumentsCreateT('documents.create.field.project_visible_in_holon_hint')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="generic-form-grid generic-form-grid--main-aside">
                <label class="omo-document-editor__field generic-form-field">
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.title')) ?></span>
                    <input
                        type="text"
                        name="title"
                        class="generic-form-control generic-form-control--compact"
                        maxlength="100"
                        required
                        autocomplete="off"
                        placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.title_placeholder')) ?>"
                        value="<?= $escape($documentTitle) ?>"
                    >
                </label>

                <div class="omo-document-editor__field generic-form-field">
                    <div class="generic-inline-help">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.tags')) ?></span>
                        <?= $documentHelp(omoDocumentsCreateT('documents.create.field.tags'), omoDocumentsCreateT('documents.create.field.tags_hint')) ?>
                    </div>
                    <input type="hidden" name="keywords" value="<?= $escape($documentKeywords) ?>" data-omo-document-tags-hidden>
                    <div class="omo-document-editor__tag-editor generic-form-control generic-form-control--compact generic-form-control--composite" data-omo-document-tags-editor>
                        <div class="omo-document-editor__tag-list" data-omo-document-tags-list></div>
                        <input
                            type="text"
                            class="omo-document-editor__tag-input"
                            placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.tags_placeholder')) ?>"
                            autocomplete="off"
                            spellcheck="false"
                            data-omo-document-tags-input
                        >
                    </div>
                </div>

                <label class="omo-document-editor__field generic-form-field generic-form-field--full">
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.description')) ?></span>
                    <textarea
                        name="description"
                        class="generic-form-control generic-form-control--compact"
                        rows="2"
                        placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.description_placeholder')) ?>"
                    ><?= $escape($documentDescription) ?></textarea>
                </label>

                <?php if ($parentFolderTitle !== ''): ?>
                    <div class="omo-document-editor__field generic-form-field generic-form-field--full">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.parent_folder')) ?></span>
                        <div class="omo-document-editor__hint generic-help-text"><?= $escape($parentFolderTitle) ?></div>
                    </div>
                <?php endif; ?>
                </div>
                </fieldset>

                <div class="omo-document-editor__content-section" data-omo-document-content-section<?= $documentType !== Document::TYPE_HTML ? ' hidden' : '' ?>>
                    <div class="omo-document-editor__field generic-form-field" data-omo-document-content-field>
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.html')) ?></span>
                        <?php if ($isEditing && !$canEditDocumentContent): ?>
                            <div class="omo-document-editor__content-readonly generic-soft-panel"><?= \dbObject\PropertyFormat::sanitizeHtml($documentContent) ?></div>
                        <?php else: ?>
                            <div class="omo-document-editor__html" data-omo-document-editor-html></div>
                        <?php endif; ?>
                        <div class="omo-document-editor__dictation-status generic-soft-panel" data-omo-document-dictation-status hidden></div>
                    </div>
                </div>

                <div class="omo-document-editor__field generic-form-field" data-omo-document-nextcloud-folder-section<?= $documentType !== Document::TYPE_NEXTCLOUD_FOLDER ? ' hidden' : '' ?>>
                    <label class="omo-document-editor__field generic-form-field">
                        <span class="generic-inline-help"><span class="omo-document-editor__label generic-form-label"><?= $escape($remoteFolderPathLabel) ?></span><?= $documentHelp($remoteFolderPathLabel, $remoteFolderHint) ?></span>
                        <input type="text" name="nextcloud_folder_path" class="generic-form-control generic-form-control--compact" maxlength="1000" autocomplete="off" value="<?= $escape($documentNextcloudFolderPath) ?>" data-omo-document-nextcloud-folder-path <?= $isEditing && !$canManageDocument ? ' disabled' : '' ?>>
                    </label>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-nextcloud-browse<?= $isEditing && !$canManageDocument ? ' disabled' : '' ?>><?= $escape($remoteFolderBrowseLabel) ?></button>
                    <div class="generic-soft-panel" data-omo-document-nextcloud-browser hidden></div>
                </div>

                <div class="omo-document-editor__field generic-form-field" data-omo-document-pv-section<?= $documentType !== Document::TYPE_PV ? ' hidden' : '' ?>>
                    <label class="omo-document-editor__field generic-form-field">
                        <span class="generic-inline-help"><span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.pv_template')) ?></span><?= $documentHelp(omoDocumentsCreateT('documents.create.field.pv_template'), omoDocumentsCreateT('documents.create.field.pv_template_hint') . ' ' . omoDocumentsCreateT('documents.create.field.pv_hint')) ?></span>
                        <select name="pv_template_id" class="generic-form-control generic-form-control--compact">
                            <option value="0"><?= $escape(omoDocumentsCreateT('documents.create.field.pv_template_none')) ?></option>
                            <?php foreach ($pvTemplatesPayload as $pvTemplateOption): ?>
                                <option value="<?= (int)$pvTemplateOption['id'] ?>"><?= $escape((string)$pvTemplateOption['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="omo-document-editor__field generic-form-field" data-omo-document-etherpad-section<?= $documentType !== Document::TYPE_ETHERPAD ? ' hidden' : '' ?>>
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.type.etherpad')) ?></span>
                    <span class="omo-document-editor__hint generic-help-text">
                        <?= $escape($etherpadDocumentsAvailable || $documentType === Document::TYPE_ETHERPAD
                            ? omoDocumentsCreateT('documents.create.field.etherpad_hint')
                            : omoDocumentsCreateT('documents.create.field.etherpad_missing')) ?>
                    </span>
                </div>

                <div class="omo-document-editor__field generic-form-field" data-omo-document-collabora-section<?= !in_array($documentType, [Document::TYPE_COLLABORA_DOCUMENT, Document::TYPE_COLLABORA_SPREADSHEET, Document::TYPE_COLLABORA_PRESENTATION, Document::TYPE_COLLABORA_DRAWING], true) ? ' hidden' : '' ?>>
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.type.collabora')) ?></span>
                    <span class="omo-document-editor__hint generic-help-text">
                        <?= $escape($collaboraDocumentsAvailable || in_array($documentType, [Document::TYPE_COLLABORA_DOCUMENT, Document::TYPE_COLLABORA_SPREADSHEET, Document::TYPE_COLLABORA_PRESENTATION, Document::TYPE_COLLABORA_DRAWING], true)
                            ? ($documentType === Document::TYPE_COLLABORA_SPREADSHEET
                                ? omoDocumentsCreateT('documents.create.field.collabora_spreadsheet_hint')
                                : ($documentType === Document::TYPE_COLLABORA_PRESENTATION
                                ? omoDocumentsCreateT('documents.create.field.collabora_presentation_hint')
                                : ($documentType === Document::TYPE_COLLABORA_DRAWING
                                    ? omoDocumentsCreateT('documents.create.field.collabora_drawing_hint')
                                    : omoDocumentsCreateT('documents.create.field.collabora_hint'))))
                            : omoDocumentsCreateT('documents.create.field.collabora_missing')) ?>
                    </span>
                </div>

                <div class="omo-document-editor__field generic-form-field" data-omo-document-whiteboard-section<?= $documentType !== Document::TYPE_WHITEBOARD ? ' hidden' : '' ?>>
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.type.whiteboard')) ?></span>
                    <span class="omo-document-editor__hint generic-help-text">
                        <?= $escape($whiteboardDocumentsAvailable || $documentType === Document::TYPE_WHITEBOARD
                            ? omoDocumentsCreateT('documents.create.field.whiteboard_hint')
                            : omoDocumentsCreateT('documents.create.field.whiteboard_missing')) ?>
                    </span>
                </div>

                <div class="omo-document-editor__field generic-form-field" data-omo-document-ethercalc-section<?= $documentType !== Document::TYPE_ETHERCALC ? ' hidden' : '' ?>>
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.type.ethercalc')) ?></span>
                    <span class="omo-document-editor__hint generic-help-text">
                        <?= $escape($ethercalcDocumentsAvailable || $documentType === Document::TYPE_ETHERCALC
                            ? omoDocumentsCreateT('documents.create.field.ethercalc_hint')
                            : omoDocumentsCreateT('documents.create.field.ethercalc_missing')) ?>
                    </span>
                </div>

                <div class="omo-document-editor__external-section" data-omo-document-external-section<?= $documentType !== Document::TYPE_EXTERNAL_LINK ? ' hidden' : '' ?>>
                    <div class="omo-document-editor__field generic-form-field">
                        <div class="generic-inline-help">
                            <label class="omo-document-editor__label generic-form-label" for="omo-document-editor-external-url"><?= $escape(omoDocumentsCreateT('documents.create.field.external_url')) ?></label>
                            <?= $documentHelp(omoDocumentsCreateT('documents.create.field.external_url'), omoDocumentsCreateT('documents.create.field.external_url_hint')) ?>
                        </div>
                        <input
                            id="omo-document-editor-external-url"
                            type="url"
                            name="external_url"
                            class="generic-form-control generic-form-control--compact"
                            maxlength="2000"
                            autocomplete="off"
                            placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.external_url_placeholder')) ?>"
                            data-omo-document-external-url
                            value="<?= $escape($documentExternalUrl) ?>"
                            <?= $isEditing && !$canEditDocumentContent ? ' disabled' : '' ?>
                        >
                    </div>

                    <label class="omo-document-editor__checkbox generic-checkbox">
                        <input
                            type="checkbox"
                            name="open_in_new_window"
                            value="1"
                            <?= $documentOpenInNewWindow ? ' checked' : '' ?>
                            <?= $isEditing && !$canEditDocumentContent ? ' disabled' : '' ?>
                        >
                        <span><?= $escape(omoDocumentsCreateT('documents.create.field.open_new_window')) ?></span>
                    </label>
                </div>

                <div class="omo-document-editor__upload-section" data-omo-document-upload-section<?= $documentType !== Document::TYPE_UPLOADED_FILE ? ' hidden' : '' ?>>
                    <label class="omo-document-editor__field generic-form-field">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.upload')) ?></span>
                        <input
                            type="file"
                            name="uploaded_file"
                            class="generic-form-control generic-form-control--compact"
                            data-omo-document-upload-input
                            <?= $isEditing && !$canEditDocumentContent ? ' disabled' : '' ?>
                        >
                        <span class="omo-document-editor__hint generic-help-text">
                            <?php if ($nextcloudDocumentsAvailable): ?>
                                <?= $escape(omoDocumentsCreateT('documents.create.upload.hint_nextcloud')) ?>
                            <?php else: ?>
                                <?= $escape(omoDocumentsCreateT('documents.create.upload.hint_missing')) ?>
                            <?php endif; ?>
                        </span>
                    </label>

                    <?php if ($documentType === Document::TYPE_UPLOADED_FILE && $documentHasStoredFile): ?>
                        <div class="omo-document-editor__upload-current generic-soft-panel generic-soft-panel--stack">
                            <div class="omo-document-editor__upload-current-title"><?= $escape(omoDocumentsCreateT('documents.create.upload.current')) ?></div>
                            <div class="omo-document-editor__upload-current-name"><?= $escape($documentStoredFilename) ?></div>
                            <div class="omo-document-editor__upload-current-meta">
                                <?= $escape($documentStoredFileMime) ?>
                                <?php if ($documentStoredFileSize > 0): ?>
                                    · <?= $escape(number_format($documentStoredFileSize, 0, '.', '\'')) ?> octets
                                <?php endif; ?>
                            </div>
                        </div>

                        <label class="omo-document-editor__checkbox generic-checkbox">
                            <input type="checkbox" name="remove_uploaded_file" value="1"<?= $isEditing && !$canEditDocumentContent ? ' disabled' : '' ?>>
                            <span><?= $escape(omoDocumentsCreateT('documents.create.upload.remove')) ?></span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="omo-document-editor__status generic-soft-panel generic-feedback" data-omo-document-editor-status hidden></div>

            <div class="omo-document-editor__actions generic-form-actions generic-form-actions--stack-mobile">
                <?php if ($editorHost === 'project_picker'): ?>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-editor-cancel><?= $escape(omoDocumentsCreateT('documents.create.action.cancel')) ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" data-omo-document-editor-submit><?= $escape(omoDocumentsCreateT('documents.create.action.create')) ?></button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="/omo/api/documents/editor.css?v=20260917-style-review-final">

<?= commonPageScriptTags('/omo/api/documents/create.js', [
    'documentFormId' => $documentFormId,
    'uploadHasExistingFile' => ($documentType === Document::TYPE_UPLOADED_FILE && $documentHasStoredFile),
    'aiToolsEnabled' => ($canUseAiTools),
    'initialHtmlValue' => $documentContent,
    'embeddableDocuments' => $embeddableDocumentsPayload,
    'uiText' => [
        'embedNone' => omoDocumentsCreateT('documents.create.embed.none'),
        'embedSearchPlaceholder' => omoDocumentsCreateT('documents.create.embed.search_placeholder'),
        'embedModalTitle' => omoDocumentsCreateT('documents.create.embed.modal_title'),
        'actionCancel' => omoDocumentsCreateT('documents.create.action.cancel'),
        'embedUpdate' => omoDocumentsCreateT('documents.create.embed.update'),
        'embedInsert' => omoDocumentsCreateT('documents.create.embed.insert'),
        'tagRemove' => omoDocumentsCreateT('documents.create.field.tags_remove'),
        'uploadReplaceConfirm' => omoDocumentsCreateT('documents.create.upload.replace_confirm'),
    ],
    'editingDocumentId' => $isEditing && $canEditDocumentContent && $document->supportsHtmlContent() ? (int)$document->getId() : 0,
    'editLockHeartbeatIntervalMs' => (int)(\dbObject\Document::getDraftHeartbeatIntervalSeconds() * 1000),
    'organizationId' => (int)$organizationId,
    'holonId' => (int)$holonId,
    'contextHolonId' => (int)$contextHolonId,
    'documentsCreateNextcloudEmpty' => omoDocumentsCreateT('documents.create.nextcloud.empty'),
]) ?>
