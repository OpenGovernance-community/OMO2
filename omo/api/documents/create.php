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
    'documents.create.visibility.help_context_holon' => ['text' => 'Les portées cercle et rôle suivent automatiquement le holon du document.', 'context' => 'Visibility help text shown when the document has a contextual holon.'],
    'documents.create.visibility.help_no_holon' => ['text' => 'Ce document n’est pas lié à un holon. Les portées cercle et rôle ne sont pas disponibles.', 'context' => 'Visibility help text shown when the document has no holon but still belongs to an organization.'],
    'documents.create.visibility.help_outside_context' => ['text' => 'Ce document est hors contexte. Les portées d’organisation, cercle et rôle ne sont pas disponibles.', 'context' => 'Visibility help text shown when the document has no organization context.'],
    'documents.create.context.organization' => ['text' => 'Organisation', 'context' => 'Fallback context label used for embeddable documents without a holon.'],
    'documents.create.field.type' => ['text' => 'Type', 'context' => 'Label of the document type field.'],
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
    'documents.create.field.project_visible_in_holon' => ['text' => 'Afficher dans le holon', 'context' => 'Checkbox allowing a project-attached document to remain visible in the holon document list.'],
    'documents.create.field.project_visible_in_holon_hint' => ['text' => 'Les documents liés à un projet sont masqués dans le holon par défaut.', 'context' => 'Help text for the project document holon visibility checkbox.'],
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

if (!$isEditing && $organizationLoaded) {
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
        <form id="<?= $escape($documentFormId) ?>" class="omo-document-editor__form generic-form-stack" action="/omo/api/documents/save.php" method="post" enctype="multipart/form-data" data-omo-document-create-form data-omo-document-editor-host="<?= $escape($editorHost) ?>">
            <input type="hidden" name="oid" value="<?= $escape($organizationId) ?>">
            <input type="hidden" name="cid" value="<?= $escape($contextHolonId) ?>">
            <input type="hidden" name="parent_document_id" value="<?= (int)$parentDocumentId ?>">
            <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?= (int)$document->getId() ?>">
            <?php endif; ?>

            <div class="omo-document-editor__grid generic-section generic-section--stack generic-form-section">
                <fieldset class="omo-document-editor__metadata"<?= $isEditing && !$canManageDocument ? ' disabled' : '' ?>>
                <div class="omo-document-editor__meta-row generic-form-grid">
                    <label class="omo-document-editor__field generic-form-field">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.type')) ?></span>
                        <select
                            name="document_type"
                            class="generic-form-control"
                            data-omo-document-type
                            <?= $isEditing ? 'disabled' : '' ?>
                        >
                            <option value="<?= $escape(Document::TYPE_FOLDER) ?>" <?= $documentType === Document::TYPE_FOLDER ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.folder')) ?></option>
                            <?php if ($etherpadGroupAvailable || $collaboraGroupAvailable || $whiteboardGroupAvailable): ?>
                            <option disabled aria-hidden="true">--------------------</option>
                            <?php endif; ?>
                            <option value="<?= $escape(Document::TYPE_EXTERNAL_LINK) ?>" <?= $documentType === Document::TYPE_EXTERNAL_LINK ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.external')) ?></option>
                            <?php if ($nextcloudDocumentsAvailable || $documentType === Document::TYPE_UPLOADED_FILE): ?>
                                <option value="<?= $escape(Document::TYPE_UPLOADED_FILE) ?>" <?= $documentType === Document::TYPE_UPLOADED_FILE ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.uploaded')) ?></option>
                            <?php endif; ?>
                            <option value="<?= $escape(Document::TYPE_HTML) ?>" <?= $documentType === Document::TYPE_HTML ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.html')) ?></option>
                            <option value="<?= $escape(Document::TYPE_PV) ?>" <?= $documentType === Document::TYPE_PV ? ' selected' : '' ?>><?= $escape(omoDocumentsCreateT('documents.create.type.pv')) ?></option>
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
                    </label>

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
                        )) ?>
                    </div>

                    <?php if ($isProjectDocument): ?>
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
                        <span class="omo-document-editor__hint generic-help-text"><?= $escape(omoDocumentsCreateT('documents.create.field.project_visible_in_holon_hint')) ?></span>
                    <?php endif; ?>
                </div>

                <label class="omo-document-editor__field generic-form-field">
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.title')) ?></span>
                    <input
                        type="text"
                        name="title"
                        class="generic-form-control"
                        maxlength="100"
                        required
                        autocomplete="off"
                        placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.title_placeholder')) ?>"
                        value="<?= $escape($documentTitle) ?>"
                    >
                </label>

                <?php if ($parentFolderTitle !== ''): ?>
                    <div class="omo-document-editor__field generic-form-field">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.parent_folder')) ?></span>
                        <div class="omo-document-editor__hint generic-help-text"><?= $escape($parentFolderTitle) ?></div>
                    </div>
                <?php endif; ?>

                <label class="omo-document-editor__field generic-form-field">
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.description')) ?></span>
                    <textarea
                        name="description"
                        class="generic-form-control"
                        rows="3"
                        placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.description_placeholder')) ?>"
                    ><?= $escape($documentDescription) ?></textarea>
                </label>

                <div class="omo-document-editor__field generic-form-field">
                    <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.tags')) ?></span>
                    <input type="hidden" name="keywords" value="<?= $escape($documentKeywords) ?>" data-omo-document-tags-hidden>
                    <div class="omo-document-editor__tag-editor generic-form-control" data-omo-document-tags-editor>
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
                    <span class="omo-document-editor__hint generic-help-text"><?= $escape(omoDocumentsCreateT('documents.create.field.tags_hint')) ?></span>
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

                <div class="omo-document-editor__field generic-form-field" data-omo-document-pv-section<?= $documentType !== Document::TYPE_PV ? ' hidden' : '' ?>>
                    <label class="omo-document-editor__field generic-form-field">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.pv_template')) ?></span>
                        <select name="pv_template_id" class="generic-form-control">
                            <option value="0"><?= $escape(omoDocumentsCreateT('documents.create.field.pv_template_none')) ?></option>
                            <?php foreach ($pvTemplatesPayload as $pvTemplateOption): ?>
                                <option value="<?= (int)$pvTemplateOption['id'] ?>"><?= $escape((string)$pvTemplateOption['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <span class="omo-document-editor__hint generic-help-text"><?= $escape(omoDocumentsCreateT('documents.create.field.pv_template_hint')) ?></span>
                    <span class="omo-document-editor__hint generic-help-text"><?= $escape(omoDocumentsCreateT('documents.create.field.pv_hint')) ?></span>
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
                    <label class="omo-document-editor__field generic-form-field">
                        <span class="omo-document-editor__label generic-form-label"><?= $escape(omoDocumentsCreateT('documents.create.field.external_url')) ?></span>
                        <input
                            type="url"
                            name="external_url"
                            class="generic-form-control"
                            maxlength="2000"
                            autocomplete="off"
                            placeholder="<?= $escape(omoDocumentsCreateT('documents.create.field.external_url_placeholder')) ?>"
                            data-omo-document-external-url
                            value="<?= $escape($documentExternalUrl) ?>"
                            <?= $isEditing && !$canEditDocumentContent ? ' disabled' : '' ?>
                        >
                        <span class="omo-document-editor__hint generic-help-text"><?= $escape(omoDocumentsCreateT('documents.create.field.external_url_hint')) ?></span>
                    </label>

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
                            class="generic-form-control"
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

<style>
.omo-document-editor__meta-row {
    --generic-form-grid-min: 230px;
}

.omo-document-editor [hidden] {
    display: none !important;
}

.omo-document-editor__metadata {
    display: contents;
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
}

.omo-document-editor__metadata:disabled .generic-form-label,
.omo-document-editor__metadata:disabled .generic-help-text {
    color: var(--color-text-light);
    opacity: 0.72;
}

.omo-document-editor__metadata:disabled .generic-form-control,
.omo-document-editor__metadata:disabled .omo-document-editor__tag-editor,
.omo-document-editor__metadata:disabled .omo-visibility-choice {
    border-color: color-mix(in srgb, var(--color-border, #d1d5db) 72%, var(--color-text-light, #64748b));
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 86%, var(--color-text-light, #64748b) 14%);
    color: var(--color-text-light);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border, #d1d5db) 70%, var(--color-text-light, #64748b));
}

.omo-document-editor__metadata:disabled .generic-form-control,
.omo-document-editor__metadata:disabled .omo-document-editor__tag-editor,
.omo-document-editor__metadata:disabled .omo-visibility-choice,
.omo-document-editor__metadata:disabled .omo-visibility-choice__button,
.omo-document-editor__metadata:disabled .omo-document-editor__tag-input,
.omo-document-editor__metadata:disabled .omo-document-editor__tag-remove {
    cursor: not-allowed !important;
}

.omo-document-editor__metadata:disabled .omo-document-editor__tag-editor {
    cursor: not-allowed;
}

.omo-document-editor__metadata:disabled .omo-document-editor__tag {
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 80%, var(--color-text-light, #64748b) 20%);
    color: var(--color-text-light);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border, #d1d5db) 68%, var(--color-text-light, #64748b));
}

.omo-document-editor__metadata:disabled .omo-visibility-choice {
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 78%, var(--color-text-light, #64748b) 22%);
}

.omo-document-editor__metadata:disabled .omo-visibility-choice::before {
    background: color-mix(in srgb, var(--color-surface, #ffffff) 78%, var(--color-text-light, #64748b) 22%);
    box-shadow: none;
}

.omo-document-editor__metadata:disabled .omo-visibility-choice__button {
    color: var(--color-text-light);
    opacity: 0.58;
}

.omo-document-editor__metadata:disabled .omo-visibility-choice__input:checked + .omo-visibility-choice__button {
    color: var(--color-text-light);
    opacity: 0.9;
}

.omo-document-editor__content-readonly {
    min-height: 160px;
    color: var(--color-text-light);
    opacity: 0.7;
}

.omo-document-editor__tag-editor {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    cursor: text;
}

.omo-document-editor__tag-editor:focus-within {
    border-color: var(--generic-form-control-border-focus);
    box-shadow: var(--generic-form-control-focus-shadow);
    background: var(--generic-form-control-background-focus);
}

.omo-document-editor__tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-document-editor__tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 82%, white 18%);
    color: color-mix(in srgb, var(--color-primary, #2563eb) 78%, #334155 22%);
    font-size: 0.8rem;
    line-height: 1;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border, #d1d5db) 82%, white 18%);
}

.omo-document-editor__tag-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: inherit;
    font: inherit;
    line-height: 1;
    cursor: pointer;
}

.omo-document-editor__tag-remove:hover,
.omo-document-editor__tag-remove:focus-visible {
    background: color-mix(in srgb, currentColor 12%, transparent);
    outline: none;
}

.omo-document-editor__tag-input {
    flex: 1 1 140px;
    min-width: 140px;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--color-text);
    font: inherit;
    outline: none;
    box-shadow: none;
}

.omo-document-editor__tag-input::placeholder {
    color: var(--color-text-light);
}

.omo-document-editor__upload-current {
    --generic-soft-panel-gap: 4px;
}

.omo-document-editor__upload-current-title {
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-document-editor__upload-current-name {
    font-weight: 600;
    color: var(--color-text);
    word-break: break-word;
}

.omo-document-editor__upload-current-meta {
    font-size: 0.84rem;
    color: var(--color-text-light);
}

.omo-document-editor__status {
    --generic-soft-panel-border: color-mix(in srgb, #dc2626 25%, var(--color-border));
    --generic-soft-panel-background: color-mix(in srgb, #fef2f2 88%, var(--color-surface));
    color: #991b1b;
}

.omo-document-editor__dictation-status {
    --generic-soft-panel-gap: 0;
    --generic-soft-panel-border: color-mix(in srgb, var(--color-border) 85%, #38bdf8 15%);
    --generic-soft-panel-background: color-mix(in srgb, var(--color-surface) 88%, #eff6ff 12%);
    margin-top: 10px;
    color: var(--color-text-light);
    font-size: 0.84rem;
    line-height: 1.45;
}

.omo-document-editor__dictation-status.is-live {
    --generic-soft-panel-border: color-mix(in srgb, #f59e0b 35%, var(--color-border));
    --generic-soft-panel-background: color-mix(in srgb, #fffbeb 82%, var(--color-surface));
    color: #92400e;
}

.omo-document-editor__dictation-status.is-error {
    --generic-soft-panel-border: color-mix(in srgb, #dc2626 28%, var(--color-border));
    --generic-soft-panel-background: color-mix(in srgb, #fef2f2 88%, var(--color-surface));
    color: #991b1b;
}

.omo-document-editor__dictation-status.is-success {
    --generic-soft-panel-border: color-mix(in srgb, #16a34a 26%, var(--color-border));
    --generic-soft-panel-background: color-mix(in srgb, #f0fdf4 88%, var(--color-surface));
    color: #166534;
}

</style>

<script>
(function () {
    const form = document.getElementById(<?= json_encode($documentFormId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
    if (!form || form.dataset.omoDocumentCreateReady === '1') {
        return;
    }

    form.dataset.omoDocumentCreateReady = '1';

    const editorHost = String(form.getAttribute('data-omo-document-editor-host') || 'documents');

    const htmlHost = form.querySelector('[data-omo-document-editor-html]');
    const statusNode = form.querySelector('[data-omo-document-editor-status]');
    const dictationStatusNode = form.querySelector('[data-omo-document-dictation-status]');
    const cancelButton = form.querySelector('[data-omo-document-editor-cancel]')
        || document.querySelector('[data-omo-document-editor-cancel][form="' + form.id + '"]');
    const typeSelect = form.querySelector('[data-omo-document-type]');
    const tagsEditor = form.querySelector('[data-omo-document-tags-editor]');
    const tagsList = form.querySelector('[data-omo-document-tags-list]');
    const tagsInput = form.querySelector('[data-omo-document-tags-input]');
    const tagsHiddenInput = form.querySelector('[data-omo-document-tags-hidden]');
    const contentSection = form.querySelector('[data-omo-document-content-section]');
    const pvSection = form.querySelector('[data-omo-document-pv-section]');
    const externalSection = form.querySelector('[data-omo-document-external-section]');
    const uploadSection = form.querySelector('[data-omo-document-upload-section]');
    const etherpadSection = form.querySelector('[data-omo-document-etherpad-section]');
    const collaboraSection = form.querySelector('[data-omo-document-collabora-section]');
    const whiteboardSection = form.querySelector('[data-omo-document-whiteboard-section]');
    const ethercalcSection = form.querySelector('[data-omo-document-ethercalc-section]');
    const externalUrlField = form.querySelector('[data-omo-document-external-url]');
    const externalOpenInNewWindowField = form.querySelector('input[name="open_in_new_window"]');
    const uploadInput = form.querySelector('[data-omo-document-upload-input]');
    const uploadHasExistingFile = <?= $documentType === Document::TYPE_UPLOADED_FILE && $documentHasStoredFile ? 'true' : 'false' ?>;
    const aiToolsEnabled = <?= $canUseAiTools ? 'true' : 'false' ?>;
    const initialHtmlValue = <?= json_encode($documentContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const embeddableDocuments = <?= json_encode($embeddableDocumentsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const uiText = <?= json_encode([
        'embedNone' => omoDocumentsCreateT('documents.create.embed.none'),
        'embedSearchPlaceholder' => omoDocumentsCreateT('documents.create.embed.search_placeholder'),
        'embedModalTitle' => omoDocumentsCreateT('documents.create.embed.modal_title'),
        'actionCancel' => omoDocumentsCreateT('documents.create.action.cancel'),
        'embedUpdate' => omoDocumentsCreateT('documents.create.embed.update'),
        'embedInsert' => omoDocumentsCreateT('documents.create.embed.insert'),
        'tagRemove' => omoDocumentsCreateT('documents.create.field.tags_remove'),
        'uploadReplaceConfirm' => omoDocumentsCreateT('documents.create.upload.replace_confirm'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const editingDocumentId = <?= $isEditing && $canEditDocumentContent && $document->supportsHtmlContent() ? (int)$document->getId() : 0 ?>;
    const editLockEndpointUrl = '/omo/api/documents/edit_lock.php';
    const editLockHeartbeatIntervalMs = <?= (int)(\dbObject\Document::getDraftHeartbeatIntervalSeconds() * 1000) ?>;
    const draftSyncDebounceMs = 1000;
    let keywordTags = [];
    let htmlField = null;
    let htmlValueCache = String(initialHtmlValue || '');
    let mediaStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingMimeType = '';
    let transcriptionController = null;
    let dictationMode = 'idle';
    let rewriteController = null;
    let rewriteMode = 'idle';
    let summarizeController = null;
    let summarizeMode = 'idle';
    let aiToolsOpen = false;
    let editLockLost = false;
    let editLockHeartbeatTimer = null;
    let draftSyncTimer = null;

    function closeDocumentEditor(options) {
        if (editorHost === 'project' && typeof window.omoCloseProjectDocumentEditorDrawer === 'function') {
            window.omoCloseProjectDocumentEditorDrawer(options);
            return;
        }
        if (editorHost === 'project_picker' && typeof window.commonTopbarCloseModal === 'function') {
            window.commonTopbarCloseModal();
            return;
        }
        if (typeof window.omoCloseDocumentEditorDrawer === 'function') {
            window.omoCloseDocumentEditorDrawer(options);
        }
    }

    function getSelectedDocumentType() {
        if (!typeSelect) {
            return 'html';
        }

        return String(typeSelect.value || 'html').trim().toLowerCase() || 'html';
    }

    function isFolderTypeSelected() {
        return getSelectedDocumentType() === 'folder';
    }

    function isHtmlTypeSelected() {
        return getSelectedDocumentType() === 'html';
    }

    function isUploadedFileTypeSelected() {
        return getSelectedDocumentType() === 'uploaded_file';
    }

    function syncTypeUi() {
        const isHtmlDocument = isHtmlTypeSelected();
        const isPvDocument = getSelectedDocumentType() === 'pv';
        const isExternalLink = getSelectedDocumentType() === 'external_link';
        const isUploadedFile = isUploadedFileTypeSelected();
        const isEtherpad = getSelectedDocumentType() === 'etherpad';
        const isCollabora = ['collabora_document', 'collabora_spreadsheet', 'collabora_presentation', 'collabora_drawing'].includes(getSelectedDocumentType());
        const isWhiteboard = getSelectedDocumentType() === 'whiteboard';
        const isEthercalc = getSelectedDocumentType() === 'ethercalc';

        if (contentSection) {
            contentSection.hidden = !isHtmlDocument;
        }

        if (pvSection) {
            pvSection.hidden = !isPvDocument;
        }

        if (externalSection) {
            externalSection.hidden = !isExternalLink;
        }

        if (externalUrlField) {
            externalUrlField.required = isExternalLink;
        }

        if (uploadSection) {
            uploadSection.hidden = !isUploadedFile;
        }

        if (etherpadSection) {
            etherpadSection.hidden = !isEtherpad;
        }

        if (collaboraSection) {
            collaboraSection.hidden = !isCollabora;
        }

        if (whiteboardSection) {
            whiteboardSection.hidden = !isWhiteboard;
        }

        if (ethercalcSection) {
            ethercalcSection.hidden = !isEthercalc;
        }

        if (uploadInput) {
            uploadInput.required = isUploadedFile && !uploadHasExistingFile;
        }

        if (!isHtmlDocument) {
            cleanupDictation({ discard: true });
            cleanupRewrite({ keepStatus: true });
            cleanupSummarize({ keepStatus: true });
            destroyHtmlField();
        } else {
            ensureHtmlFieldMounted();
        }
    }

    function hasEditLockSupport() {
        return Number.isInteger(editingDocumentId) && editingDocumentId > 0;
    }

    function clearEditLockHeartbeatTimer() {
        if (editLockHeartbeatTimer) {
            window.clearTimeout(editLockHeartbeatTimer);
            editLockHeartbeatTimer = null;
        }
    }

    function clearDraftSyncTimer() {
        if (draftSyncTimer) {
            window.clearTimeout(draftSyncTimer);
            draftSyncTimer = null;
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeKeywordTag(value) {
        return String(value || '')
            .replace(/[\r\n\t]+/g, ' ')
            .replace(/^#+/g, '')
            .replace(/\s+/g, ' ')
            .replace(/^,+|,+$/g, '')
            .trim();
    }

    function syncKeywordTagsField() {
        if (!tagsHiddenInput) {
            return;
        }

        tagsHiddenInput.value = keywordTags.join(',');
    }

    function renderKeywordTags() {
        if (!tagsList) {
            return;
        }

        tagsList.replaceChildren();

        keywordTags.forEach(function (tagLabel, tagIndex) {
            const chip = document.createElement('span');
            chip.className = 'omo-document-editor__tag';

            const label = document.createElement('span');
            label.textContent = tagLabel;
            chip.appendChild(label);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'omo-document-editor__tag-remove';
            removeButton.innerHTML = '&times;';
            removeButton.setAttribute('aria-label', String(uiText.tagRemove || 'Retirer le tag') + ' ' + tagLabel);
            removeButton.setAttribute('title', String(uiText.tagRemove || 'Retirer le tag') + ' ' + tagLabel);
            removeButton.addEventListener('click', function () {
                keywordTags.splice(tagIndex, 1);
                syncKeywordTagsField();
                renderKeywordTags();
                if (tagsInput) {
                    tagsInput.focus();
                }
            });
            chip.appendChild(removeButton);

            tagsList.appendChild(chip);
        });
    }

    function addKeywordTag(rawValue) {
        const normalizedTag = normalizeKeywordTag(rawValue);
        if (normalizedTag === '') {
            return false;
        }

        const normalizedLookup = normalizedTag.toLocaleLowerCase();
        const alreadyExists = keywordTags.some(function (existingTag) {
            return String(existingTag || '').toLocaleLowerCase() === normalizedLookup;
        });

        if (alreadyExists) {
            return false;
        }

        keywordTags.push(normalizedTag);
        syncKeywordTagsField();
        renderKeywordTags();
        return true;
    }

    function commitKeywordInput() {
        if (!tagsInput) {
            return false;
        }

        const didAddTag = addKeywordTag(tagsInput.value);
        tagsInput.value = '';
        return didAddTag;
    }

    function flushKeywordInputDelimiters(commitTrailingValue) {
        if (!tagsInput) {
            return false;
        }

        const rawValue = String(tagsInput.value || '').replace(/[\r\n]+/g, ',');
        if (rawValue.indexOf(',') < 0) {
            if (!commitTrailingValue) {
                return false;
            }

            return commitKeywordInput();
        }

        const parts = rawValue.split(',');
        const trailingValue = commitTrailingValue ? '' : parts.pop();
        let didChange = false;

        parts.forEach(function (part) {
            didChange = addKeywordTag(part) || didChange;
        });

        tagsInput.value = normalizeKeywordTag(trailingValue);
        return didChange;
    }

    function initializeKeywordTags() {
        if (!tagsInput || !tagsHiddenInput) {
            return;
        }

        keywordTags = [];
        String(tagsHiddenInput.value || '').split(',').forEach(function (part) {
            addKeywordTag(part);
        });
        tagsInput.value = '';
        syncKeywordTagsField();
        renderKeywordTags();
    }

    function buildDocumentEmbedHtml(documentItem) {
        if (!documentItem) {
            return '';
        }

        const numericId = Number.parseInt(String(documentItem.id || ''), 10);
        if (!Number.isInteger(numericId) || numericId <= 0) {
            return '';
        }

        const title = String(documentItem.title || '').trim();
        const description = String(documentItem.description || '').trim();
        const resolvedTitle = title !== '' ? title : 'Document #' + String(numericId);
        let html = ''
            + '<span class="omo-document-embed"'
            + ' data-omo-embed-type="document"'
            + ' data-omo-document-id="' + escapeHtml(String(numericId)) + '"'
            + ' data-omo-document-title="' + escapeHtml(resolvedTitle) + '"';

        if (description !== '') {
            html += ' data-omo-document-description="' + escapeHtml(description) + '"';
        }

        html += ' contenteditable="false">'
            + '<strong>Document lié</strong><br>'
            + '<strong>' + escapeHtml(resolvedTitle) + '</strong>';

        if (description !== '') {
            html += '<br>' + escapeHtml(description);
        }

        html += '</span>';
        return html;
    }

    function findEmbeddableDocumentById(documentId) {
        const numericId = Number.parseInt(String(documentId || ''), 10);
        if (!Number.isInteger(numericId) || numericId <= 0) {
            return null;
        }

        return embeddableDocuments.find(function (documentItem) {
            return Number.parseInt(String(documentItem.id || ''), 10) === numericId;
        }) || null;
    }

    function getEmbedNodeDocumentId(targetNode) {
        if (!targetNode || !targetNode.getAttribute) {
            return 0;
        }

        return Number.parseInt(String(targetNode.getAttribute('data-omo-document-id') || ''), 10) || 0;
    }

    function updateDocumentEmbedPickerPreview(modalBody, selectedItem) {
        if (!modalBody) {
            return;
        }

        const titleNode = modalBody.querySelector('[data-omo-document-embed-preview-title]');
        const contextNode = modalBody.querySelector('[data-omo-document-embed-preview-context]');
        const descriptionNode = modalBody.querySelector('[data-omo-document-embed-preview-description]');
        const applyButton = modalBody.querySelector('[data-omo-document-embed-apply]');

        if (!selectedItem) {
            if (titleNode) {
                titleNode.textContent = uiText.embedNone || '';
            }
            if (contextNode) {
                contextNode.textContent = '';
                contextNode.hidden = true;
            }
            if (descriptionNode) {
                descriptionNode.textContent = '';
                descriptionNode.hidden = true;
            }
            if (applyButton) {
                applyButton.disabled = true;
            }
            return;
        }

        if (titleNode) {
            const title = String(selectedItem.title || '').trim();
            titleNode.textContent = title !== '' ? title : 'Document #' + String(selectedItem.id || '');
        }

        if (contextNode) {
            const contextLabel = String(selectedItem.contextLabel || '').trim();
            contextNode.textContent = contextLabel;
            contextNode.hidden = contextLabel === '';
        }

        if (descriptionNode) {
            const description = String(selectedItem.description || '').trim();
            descriptionNode.textContent = description;
            descriptionNode.hidden = description === '';
        }

        if (applyButton) {
            applyButton.disabled = false;
        }
    }

    function renderDocumentEmbedPickerOptions(selectNode, searchValue, selectedDocumentId) {
        if (!selectNode) {
            return null;
        }

        const normalizedSearch = String(searchValue || '').trim().toLowerCase();
        const matchingItems = embeddableDocuments.filter(function (documentItem) {
            if (normalizedSearch === '') {
                return true;
            }

            const haystack = [
                String(documentItem.title || ''),
                String(documentItem.description || ''),
                String(documentItem.contextLabel || '')
            ].join(' ').toLowerCase();

            return haystack.indexOf(normalizedSearch) >= 0;
        });

        selectNode.innerHTML = '';
        matchingItems.forEach(function (documentItem) {
            const option = document.createElement('option');
            const title = String(documentItem.title || '').trim();
            option.value = String(documentItem.id || '');
            option.textContent = title !== '' ? title : 'Document #' + String(documentItem.id || '');
            selectNode.appendChild(option);
        });

        let selectedItem = null;
        if (matchingItems.length > 0) {
            const preferredId = Number.parseInt(String(selectedDocumentId || ''), 10) || 0;
            selectedItem = matchingItems.find(function (documentItem) {
                return Number.parseInt(String(documentItem.id || ''), 10) === preferredId;
            }) || matchingItems[0];

            selectNode.value = String(selectedItem.id || '');
        }

        selectNode.disabled = matchingItems.length === 0;
        return selectedItem;
    }

    function applyDocumentEmbedSelection(documentItem, targetNode, insertionMarker) {
        if (!htmlField || !documentItem) {
            return;
        }

        const embedHtml = buildDocumentEmbedHtml(documentItem);
        if (embedHtml === '') {
            return;
        }

        if (targetNode && typeof htmlField.replaceNodeWithHtml === 'function') {
            htmlField.replaceNodeWithHtml(targetNode, embedHtml);
            return;
        }

        if (insertionMarker && typeof htmlField.replaceMarkerWithHtml === 'function') {
            htmlField.replaceMarkerWithHtml(insertionMarker, embedHtml);
            return;
        }

        if (typeof htmlField.insertHtmlAtCursor === 'function') {
            htmlField.insertHtmlAtCursor(embedHtml);
        }
    }

    function openDocumentEmbedPicker(options) {
        if (!htmlField || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const settings = options && typeof options === 'object' ? options : {};
        const targetNode = settings.targetNode || null;
        let selectedDocumentId = Number.parseInt(String(settings.selectedDocumentId || ''), 10) || 0;
        let insertionMarker = null;
        let pickerResolved = false;

        if (!targetNode && typeof htmlField.createTemporaryCursorMarker === 'function') {
            insertionMarker = htmlField.createTemporaryCursorMarker();
        }

        const modalHtml = ''
            + '<div class="omo-document-embed-picker">'
            + '  <label class="omo-document-embed-picker__field">'
            + '    <span class="omo-document-embed-picker__label generic-form-label">Recherche</span>'
            + '    <input type="search" class="generic-form-control" data-omo-document-embed-search placeholder="' + escapeHtml(uiText.embedSearchPlaceholder || '') + '">'
            + '  </label>'
            + '  <label class="omo-document-embed-picker__field">'
            + '    <span class="omo-document-embed-picker__label generic-form-label">Documents visibles</span>'
            + '    <select class="generic-form-control omo-document-embed-picker__select" data-omo-document-embed-select size="10"></select>'
            + '  </label>'
            + '  <div class="omo-document-embed-picker__preview">'
            + '    <div class="omo-document-embed-picker__preview-title" data-omo-document-embed-preview-title>' + escapeHtml(uiText.embedNone || '') + '</div>'
            + '    <div class="omo-document-embed-picker__preview-context" data-omo-document-embed-preview-context hidden></div>'
            + '    <div class="omo-document-embed-picker__preview-description" data-omo-document-embed-preview-description hidden></div>'
            + '  </div>'
            + '  <div class="omo-document-embed-picker__actions">'
            + (targetNode
                ? '    <button type="button" class="generic-action-button generic-action-button--danger" data-omo-document-embed-delete>Supprimer</button>'
                : '')
            + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-embed-cancel>' + escapeHtml(uiText.actionCancel || '') + '</button>'
            + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-document-embed-apply disabled>' + escapeHtml(targetNode ? (uiText.embedUpdate || '') : (uiText.embedInsert || '')) + '</button>'
            + '  </div>'
            + '</div>';

        window.commonTopbarOpenModal(uiText.embedModalTitle || '', modalHtml, 'html');

        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!modalBody) {
            if (
                insertionMarker
                && htmlField
                && typeof htmlField.removeTemporaryMarker === 'function'
            ) {
                htmlField.removeTemporaryMarker(insertionMarker);
            }
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-document-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-document-embed-select]');
        const deleteButton = modalBody.querySelector('[data-omo-document-embed-delete]');
        const cancelButton = modalBody.querySelector('[data-omo-document-embed-cancel]');
        const applyButton = modalBody.querySelector('[data-omo-document-embed-apply]');

        function cleanupInsertionMarker() {
            if (
                insertionMarker
                && htmlField
                && typeof htmlField.removeTemporaryMarker === 'function'
            ) {
                htmlField.removeTemporaryMarker(insertionMarker);
            }

            insertionMarker = null;
        }

        window.addEventListener('common-topbar-modal-close', function () {
            if (!pickerResolved) {
                cleanupInsertionMarker();
            }
        }, { once: true });

        function syncSelection(nextSelectedDocumentId) {
            selectedDocumentId = Number.parseInt(String(nextSelectedDocumentId || ''), 10) || 0;
            const selectedItem = findEmbeddableDocumentById(selectedDocumentId);
            updateDocumentEmbedPickerPreview(modalBody, selectedItem);
            return selectedItem;
        }

        function rerenderOptions() {
            const selectedItem = renderDocumentEmbedPickerOptions(
                selectNode,
                searchNode ? searchNode.value : '',
                selectedDocumentId > 0 ? selectedDocumentId : (targetNode ? getEmbedNodeDocumentId(targetNode) : 0)
            );

            selectedDocumentId = selectedItem ? Number.parseInt(String(selectedItem.id || ''), 10) : 0;
            updateDocumentEmbedPickerPreview(modalBody, selectedItem);
        }

        rerenderOptions();

        if (searchNode) {
            searchNode.focus();
            searchNode.addEventListener('input', rerenderOptions);
        }

        if (selectNode) {
            selectNode.addEventListener('change', function () {
                syncSelection(selectNode.value);
            });

            selectNode.addEventListener('dblclick', function () {
                const selectedItem = syncSelection(selectNode.value);
                if (!selectedItem) {
                    return;
                }

                pickerResolved = true;
                applyDocumentEmbedSelection(selectedItem, targetNode, insertionMarker);
                insertionMarker = null;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                pickerResolved = false;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                pickerResolved = true;
                cleanupInsertionMarker();
                if (targetNode && htmlField && typeof htmlField.removeNode === 'function') {
                    htmlField.removeNode(targetNode);
                }

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', function () {
                const selectedItem = syncSelection(selectNode ? selectNode.value : 0);
                if (!selectedItem) {
                    return;
                }

                pickerResolved = true;
                applyDocumentEmbedSelection(selectedItem, targetNode, insertionMarker);
                insertionMarker = null;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }
    }

    function getCurrentDraftContent() {
        if (!isHtmlTypeSelected()) {
            return '';
        }

        if (htmlField && typeof htmlField.getValue === 'function') {
            return String(htmlField.getValue() || '');
        }

        return String(htmlValueCache || '');
    }

    function postEditLockAction(action, options) {
        if (!hasEditLockSupport()) {
            return Promise.resolve({ status: true });
        }

        const settings = options && typeof options === 'object' ? options : {};
        const body = new URLSearchParams();
        body.set('id', String(editingDocumentId));
        body.set('action', String(action || 'heartbeat'));
        if (settings.clearDraft) {
            body.set('clear_draft', '1');
        }
        if (settings.includeDraft) {
            body.set('draft_content', getCurrentDraftContent());
        }

        return fetch(editLockEndpointUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString(),
            cache: 'no-store',
            keepalive: !!settings.keepalive
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                return {
                    ok: response.ok,
                    payload: payload
                };
            });
        });
    }

    function markEditLockLost(message) {
        editLockLost = true;
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();
        setStatus(String(message || 'Ce document n’est plus verrouillé pour votre édition.'));
        syncDictationToolbarButtons();
    }

    function scheduleDraftSync(delayMs) {
        if (!hasEditLockSupport() || editLockLost || !isHtmlTypeSelected()) {
            return;
        }

        clearDraftSyncTimer();
        draftSyncTimer = window.setTimeout(function () {
            postEditLockAction('heartbeat', { includeDraft: true })
                .then(function (result) {
                    if (!result || !result.ok || !result.payload || result.payload.status !== true) {
                        const message = result && result.payload && result.payload.message
                            ? result.payload.message
                            : 'Ce document est desormais edite ailleurs.';
                        markEditLockLost(message);
                    }
                })
                .catch(function () {
                });
        }, Math.max(250, Number(delayMs || 0)));
    }

    function scheduleEditLockHeartbeat() {
        if (!hasEditLockSupport() || editLockLost) {
            return;
        }

        clearEditLockHeartbeatTimer();
        editLockHeartbeatTimer = window.setTimeout(function () {
            postEditLockAction('heartbeat', { includeDraft: true })
                .then(function (result) {
                    if (!result || !result.ok || !result.payload || result.payload.status !== true) {
                        const message = result && result.payload && result.payload.message
                            ? result.payload.message
                            : 'Ce document est desormais edite ailleurs.';
                        markEditLockLost(message);
                        return;
                    }

                    scheduleEditLockHeartbeat();
                })
                .catch(function () {
                    scheduleEditLockHeartbeat();
                });
        }, editLockHeartbeatIntervalMs);
    }

    function releaseEditLock(options) {
        if (!hasEditLockSupport()) {
            return Promise.resolve();
        }

        const settings = options && typeof options === 'object' ? options : {};
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();

        if (settings.beacon && navigator && typeof navigator.sendBeacon === 'function') {
            const body = new URLSearchParams();
            body.set('id', String(editingDocumentId));
            body.set('action', 'release');
            if (settings.clearDraft) {
                body.set('clear_draft', '1');
            }
            navigator.sendBeacon(editLockEndpointUrl, body);
            return Promise.resolve();
        }

        return postEditLockAction('release', {
            keepalive: !!settings.keepalive,
            clearDraft: !!settings.clearDraft
        })
            .catch(function () {
            });
    }

    function setDictationStatus(message, type) {
        if (!dictationStatusNode) {
            return;
        }

        const text = String(message || '').trim();
        const statusType = String(type || '').trim().toLowerCase();

        dictationStatusNode.hidden = text === '';
        dictationStatusNode.textContent = text;
        dictationStatusNode.classList.toggle('is-live', statusType === 'live');
        dictationStatusNode.classList.toggle('is-error', statusType === 'error');
        dictationStatusNode.classList.toggle('is-success', statusType === 'success');
    }

    function getRecordingMimeType() {
        if (typeof window.MediaRecorder === 'undefined' || typeof window.MediaRecorder.isTypeSupported !== 'function') {
            return '';
        }

        const candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/ogg;codecs=opus',
            'audio/ogg'
        ];

        for (let index = 0; index < candidates.length; index += 1) {
            if (window.MediaRecorder.isTypeSupported(candidates[index])) {
                return candidates[index];
            }
        }

        return '';
    }

    function getRecordingFileExtension(mimeType) {
        const normalizedMimeType = String(mimeType || '').toLowerCase();

        if (normalizedMimeType.indexOf('mp4') >= 0 || normalizedMimeType.indexOf('m4a') >= 0) {
            return 'm4a';
        }

        if (normalizedMimeType.indexOf('ogg') >= 0) {
            return 'ogg';
        }

        if (normalizedMimeType.indexOf('wav') >= 0) {
            return 'wav';
        }

        return 'webm';
    }

    function releaseMediaStream() {
        if (mediaStream && typeof mediaStream.getTracks === 'function') {
            mediaStream.getTracks().forEach(function (track) {
                try {
                    track.stop();
                } catch (error) {
                }
            });
        }

        mediaStream = null;
    }

    function resetDictationRecorderState() {
        mediaRecorder = null;
        recordedChunks = [];
        recordingMimeType = '';
        releaseMediaStream();
    }

    function syncDictationToolbarButtons() {
        if (!aiToolsEnabled || !htmlField || typeof htmlField.setToolbarButtonState !== 'function') {
            return;
        }

        const isRecording = dictationMode === 'recording';
        const isTranscribing = dictationMode === 'transcribing';
        const isRewriting = rewriteMode === 'pending';
        const isSummarizing = summarizeMode === 'pending';
        const isAiBusy = isRecording || isTranscribing || isRewriting || isSummarizing;
        const aiToolsVisible = aiToolsOpen || isAiBusy;
        const submitButton = form.querySelector('[data-omo-document-editor-submit]');

        htmlField.setToolbarButtonState('omoDocumentAiToggle', {
            label: 'IA',
            title: aiToolsVisible ? 'Masquer les outils IA' : 'Afficher les outils IA',
            active: aiToolsVisible,
            disabled: false
        });

        htmlField.setToolbarButtonState('omoDocumentDictate', {
            label: isRecording ? 'En cours…' : 'Dicter',
            title: isRecording ? 'Enregistrement en cours' : 'Démarrer une dictée',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentTranscript', {
            label: isTranscribing ? 'Transcription…' : 'Transcrire',
            title: 'Arrêter l’enregistrement et transcrire',
            disabled: !isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentDictationCancel', {
            label: 'Annuler',
            title: 'Annuler la dictée en cours',
            disabled: (!isRecording && !isTranscribing),
            hidden: !aiToolsVisible || (!isRecording && !isTranscribing)
        });
        htmlField.setToolbarButtonState('omoDocumentRewrite', {
            label: isRewriting ? 'Réécriture…' : 'Réécrire',
            title: isRewriting ? 'Réécriture en cours' : 'Réécrire la sélection',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentSummarize', {
            label: isSummarizing ? 'Résumé…' : 'Résumer',
            title: isSummarizing ? 'Résumé en cours' : 'Résumer la sélection',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });

        if (submitButton) {
            submitButton.disabled = editLockLost || isRecording || isTranscribing || isRewriting || isSummarizing;
        }
    }

    function setDictationMode(nextMode) {
        dictationMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function toggleAiTools(forceOpen) {
        if (!aiToolsEnabled) {
            return;
        }

        const shouldOpen = forceOpen === undefined
            ? !aiToolsOpen
            : !!forceOpen;

        aiToolsOpen = shouldOpen;
        syncDictationToolbarButtons();
    }

    function formatDictationError(error) {
        const errorName = error && error.name ? String(error.name) : '';

        if (errorName === 'NotAllowedError' || errorName === 'SecurityError') {
            return 'L’accès au micro a été refusé.';
        }

        if (errorName === 'NotFoundError' || errorName === 'DevicesNotFoundError') {
            return 'Aucun micro n’est disponible sur cet appareil.';
        }

        if (errorName === 'AbortError') {
            return 'La dictée a été interrompue.';
        }

        if (error && error.message) {
            return String(error.message);
        }

        return 'Impossible d’utiliser la dictée pour le moment.';
    }

    function abortTranscriptionRequest() {
        if (transcriptionController && typeof transcriptionController.abort === 'function') {
            try {
                transcriptionController.abort();
            } catch (error) {
            }
        }

        transcriptionController = null;
    }

    function abortRewriteRequest() {
        if (rewriteController && typeof rewriteController.abort === 'function') {
            try {
                rewriteController.abort();
            } catch (error) {
            }
        }

        rewriteController = null;
    }

    function abortSummarizeRequest() {
        if (summarizeController && typeof summarizeController.abort === 'function') {
            try {
                summarizeController.abort();
            } catch (error) {
            }
        }

        summarizeController = null;
    }

    function stopRecorder(discardRecording) {
        return new Promise(function (resolve, reject) {
            if (!mediaRecorder) {
                resetDictationRecorderState();
                resolve(null);
                return;
            }

            const recorder = mediaRecorder;
            const mimeType = recordingMimeType || recorder.mimeType || 'audio/webm';

            const handleStop = function () {
                const blob = discardRecording
                    ? null
                    : new Blob(recordedChunks, { type: mimeType });
                resetDictationRecorderState();
                resolve(blob);
            };

            const handleError = function () {
                resetDictationRecorderState();
                reject(new Error('Impossible de finaliser l’enregistrement audio.'));
            };

            recorder.addEventListener('stop', handleStop, { once: true });
            recorder.addEventListener('error', handleError, { once: true });

            try {
                if (typeof recorder.requestData === 'function' && recorder.state === 'recording') {
                    recorder.requestData();
                }

                if (recorder.state !== 'inactive') {
                    recorder.stop();
                    return;
                }

                handleStop();
            } catch (error) {
                resetDictationRecorderState();
                reject(error);
            }
        });
    }

    function cleanupDictation(options) {
        const shouldDiscardRecording = !options || options.discard !== false;
        abortTranscriptionRequest();

        if (mediaRecorder) {
            stopRecorder(shouldDiscardRecording).catch(function () {
            });
        } else {
            resetDictationRecorderState();
        }

        setDictationMode('idle');
        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function setRewriteMode(nextMode) {
        rewriteMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function normalizeRewriteComparisonText(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function cleanupRewrite(options) {
        abortRewriteRequest();
        setRewriteMode('idle');

        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function setSummarizeMode(nextMode) {
        summarizeMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function cleanupSummarize(options) {
        abortSummarizeRequest();
        setSummarizeMode('idle');

        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function destroyHtmlField() {
        if (!htmlField) {
            return;
        }

        if (typeof htmlField.getValue === 'function') {
            htmlValueCache = String(htmlField.getValue() || '');
        }

        if (typeof htmlField.destroy === 'function') {
            htmlField.destroy();
        }

        htmlField = null;
        if (htmlHost) {
            htmlHost.innerHTML = '';
        }
    }

    function mountHtmlField() {
        if (
            !htmlHost
            || !isHtmlTypeSelected()
            || htmlField
            || !window.omoSimpleHtmlField
            || typeof window.omoSimpleHtmlField.mount !== 'function'
        ) {
            return;
        }

        const customButtons = [];

        customButtons.push({
            name: 'omoDocumentEmbed',
            group: 'omo-embed',
            label: 'Document',
            title: 'Insérer un document',
            className: 'note-btn-light',
            focusForInsertion: true,
            onClick: function () {
                openDocumentEmbedPicker();
            }
        });

        if (aiToolsEnabled) {
            customButtons.push({
                name: 'omoDocumentAiToggle',
                group: 'omo-ai-toggle',
                label: 'IA',
                title: 'Afficher les outils IA',
                className: 'note-btn-light',
                onClick: function () {
                    toggleAiTools();
                }
            });
            customButtons.push({
                name: 'omoDocumentDictate',
                group: 'omo-ai-tools',
                label: 'Dicter',
                title: 'Démarrer une dictée',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    startDictation();
                }
            });
            customButtons.push({
                name: 'omoDocumentTranscript',
                group: 'omo-ai-tools',
                label: 'Transcrire',
                title: 'Arrêter l’enregistrement et transcrire',
                className: 'note-btn-light',
                disabled: true,
                hidden: true,
                onClick: function () {
                    transcribeCurrentRecording();
                }
            });
            customButtons.push({
                name: 'omoDocumentDictationCancel',
                group: 'omo-ai-tools',
                label: 'Annuler',
                title: 'Annuler la dictée en cours',
                className: 'note-btn-light',
                hidden: true,
                disabled: true,
                onClick: function () {
                    cleanupDictation({ discard: true });
                    setDictationStatus('Dictée annulée.', 'error');
                }
            });
            customButtons.push({
                name: 'omoDocumentRewrite',
                group: 'omo-ai-tools',
                label: 'Rewrite',
                title: 'Réécrire la sélection',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    rewriteSelectedPassage();
                }
            });
            customButtons.push({
                name: 'omoDocumentSummarize',
                group: 'omo-ai-tools',
                label: 'Résumer',
                title: 'Résumer la sélection',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    summarizeSelectedPassage();
                }
            });
        }

        htmlField = window.omoSimpleHtmlField.mount(htmlHost, {
            value: htmlValueCache,
            placeholder: 'Rédigez le contenu du document…',
            height: 240,
            customButtons: customButtons,
            onChange: function (value) {
                htmlValueCache = String(value || '');
                scheduleDraftSync(draftSyncDebounceMs);
            },
            onDoubleClick: function (context) {
                const rawTarget = context && context.target && context.target.closest
                    ? context.target.closest('.omo-document-embed[data-omo-embed-type="document"]')
                    : null;

                if (!rawTarget) {
                    return;
                }

                openDocumentEmbedPicker({
                    targetNode: rawTarget,
                    selectedDocumentId: getEmbedNodeDocumentId(rawTarget)
                });
            }
        });

        syncDictationToolbarButtons();

        if (aiToolsEnabled && htmlField && typeof htmlField.setToolbarButtonState === 'function') {
            htmlField.setToolbarButtonState('omoDocumentAiToggle', {
                label: 'IA',
                title: 'Afficher les outils IA'
            });
            htmlField.setToolbarButtonState('omoDocumentDictate', {
                label: 'Dicter',
                title: 'Démarrer une dictée'
            });
            htmlField.setToolbarButtonState('omoDocumentTranscript', {
                title: 'Arrêter l’enregistrement et transcrire'
            });
            htmlField.setToolbarButtonState('omoDocumentRewrite', {
                title: 'Réécrire la sélection'
            });
            htmlField.setToolbarButtonState('omoDocumentSummarize', {
                title: 'Résumer la sélection'
            });
        }

        syncDictationToolbarButtons();
    }

    function ensureHtmlFieldMounted() {
        if (!isHtmlTypeSelected()) {
            destroyHtmlField();
            return;
        }

        const htmlFieldVersion = '20260903-toolbar-insert-focus';
        if (
            window.omoSimpleHtmlField
            && typeof window.omoSimpleHtmlField.mount === 'function'
            && String(window.omoSimpleHtmlField.version || '') === htmlFieldVersion
        ) {
            mountHtmlField();
            return;
        }

        const scriptSelector = 'script[data-omo-simple-html-field-script="1"][data-omo-simple-html-field-version="' + htmlFieldVersion + '"]';
        const existingScript = document.querySelector(scriptSelector);
        if (existingScript) {
            if (existingScript.getAttribute('data-loaded') === '1') {
                mountHtmlField();
            } else {
                existingScript.addEventListener('load', mountHtmlField, { once: true });
            }
            return;
        }

        const script = document.createElement('script');
        script.src = '/omo/assets/js/simple-html-field.js?v=' + encodeURIComponent(htmlFieldVersion);
        script.async = false;
        script.setAttribute('data-omo-simple-html-field-script', '1');
        script.setAttribute('data-omo-simple-html-field-version', htmlFieldVersion);
        script.onload = function () {
            script.setAttribute('data-loaded', '1');
            mountHtmlField();
        };
        document.head.appendChild(script);
    }

    async function startDictation() {
        if (!aiToolsEnabled || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (typeof window.MediaRecorder === 'undefined' || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setDictationStatus('La dictée n’est pas disponible sur ce navigateur.', 'error');
            return;
        }

        try {
            if (htmlField && typeof htmlField.saveRange === 'function') {
                htmlField.saveRange();
            }

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mimeType = getRecordingMimeType();
            const recorder = mimeType !== ''
                ? new window.MediaRecorder(stream, { mimeType: mimeType })
                : new window.MediaRecorder(stream);

            mediaStream = stream;
            mediaRecorder = recorder;
            recordedChunks = [];
            recordingMimeType = mimeType || recorder.mimeType || 'audio/webm';

            recorder.addEventListener('dataavailable', function (recordEvent) {
                if (recordEvent.data && recordEvent.data.size > 0) {
                    recordedChunks.push(recordEvent.data);
                }
            });

            recorder.start();
            setDictationMode('recording');
            setDictationStatus('Enregistrement en cours. Quand vous avez terminé, cliquez sur Transcrire.', 'live');
        } catch (error) {
            cleanupDictation({ discard: true, keepStatus: true });
            setDictationStatus(formatDictationError(error), 'error');
        }
    }

    async function transcribeCurrentRecording() {
        if (dictationMode !== 'recording') {
            return;
        }

        setDictationMode('transcribing');
        setDictationStatus('Transcription en cours…', 'live');

        try {
            const audioBlob = await stopRecorder(false);
            if (!audioBlob || audioBlob.size <= 0) {
                throw new Error('Aucun son n’a été enregistré.');
            }

            const formData = new FormData();
            formData.set('oid', String(<?= (int)$organizationId ?>));
            formData.set('cid', String(<?= (int)$holonId ?>));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set(
                'audio',
                audioBlob,
                'document-dictation.' + getRecordingFileExtension(audioBlob.type || recordingMimeType)
            );

            transcriptionController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/html/transcribe.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: transcriptionController ? transcriptionController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de transcrire cet enregistrement.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de transcrire cet enregistrement.');
            }

            const transcriptText = String(payload.text || '').trim();
            if (transcriptText === '') {
                throw new Error('La transcription est vide.');
            }

            if (!htmlField || typeof htmlField.insertTextAtCursor !== 'function') {
                throw new Error('Impossible d’insérer la transcription dans l’éditeur.');
            }

            htmlField.insertTextAtCursor(transcriptText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setDictationMode('idle');
            setDictationStatus('Transcription insérée dans le document.', 'success');
        } catch (error) {
            setDictationMode('idle');
            setDictationStatus(formatDictationError(error), 'error');
        } finally {
            transcriptionController = null;
        }
    }

    async function rewriteSelectedPassage() {
        if (!aiToolsEnabled || rewriteMode === 'pending' || summarizeMode === 'pending' || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (!htmlField || typeof htmlField.getSelectedText !== 'function' || typeof htmlField.getPlainText !== 'function') {
            setDictationStatus('Impossible d’accéder à la sélection dans l’éditeur.', 'error');
            return;
        }

        if (typeof htmlField.saveRange === 'function') {
            htmlField.saveRange();
        }

        const selectedText = String(htmlField.getSelectedText() || '').trim();
        const fullText = String(htmlField.getPlainText() || '').trim();
        const rewritesWholeDocument = normalizeRewriteComparisonText(selectedText) === normalizeRewriteComparisonText(fullText);

        if (selectedText === '') {
            setDictationStatus('Sélectionnez un bloc de texte avant de lancer la réécriture.', 'error');
            return;
        }

        if (fullText === '') {
            setDictationStatus('Le document est vide.', 'error');
            return;
        }

        setRewriteMode('pending');
        setDictationStatus('Réécriture en cours…', 'live');

        try {
            const formData = new FormData();
            formData.set('oid', String(<?= (int)$organizationId ?>));
            formData.set('cid', String(<?= (int)$holonId ?>));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set('selected_text', selectedText);
            formData.set('full_text', fullText);
            formData.set('rewrite_full_document', rewritesWholeDocument ? '1' : '0');

            rewriteController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/html/rewrite.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: rewriteController ? rewriteController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de réécrire cette sélection.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de réécrire cette sélection.');
            }

            const rewrittenText = String(payload.text || '').trim();
            if (rewrittenText === '') {
                throw new Error('La réécriture est vide.');
            }

            if (typeof htmlField.replaceSelectionWithText !== 'function') {
                throw new Error('Impossible de remplacer la sélection dans l’éditeur.');
            }

            htmlField.replaceSelectionWithText(rewrittenText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setRewriteMode('idle');
            setDictationStatus('Sélection réécrite.', 'success');
        } catch (error) {
            setRewriteMode('idle');
            setDictationStatus(
                error && error.message ? String(error.message) : 'Impossible de réécrire cette sélection.',
                'error'
            );
        } finally {
            rewriteController = null;
        }
    }

    async function summarizeSelectedPassage() {
        if (!aiToolsEnabled || summarizeMode === 'pending' || rewriteMode === 'pending' || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (!htmlField || typeof htmlField.getSelectedText !== 'function' || typeof htmlField.getPlainText !== 'function') {
            setDictationStatus('Impossible d’accéder à la sélection dans l’éditeur.', 'error');
            return;
        }

        if (typeof htmlField.saveRange === 'function') {
            htmlField.saveRange();
        }

        const selectedText = String(htmlField.getSelectedText() || '').trim();
        const fullText = String(htmlField.getPlainText() || '').trim();

        if (selectedText === '') {
            setDictationStatus('Sélectionnez un bloc de texte avant de lancer le résumé.', 'error');
            return;
        }

        if (fullText === '') {
            setDictationStatus('Le document est vide.', 'error');
            return;
        }

        setSummarizeMode('pending');
        setDictationStatus('Résumé en cours…', 'live');

        try {
            const formData = new FormData();
            formData.set('oid', String(<?= (int)$organizationId ?>));
            formData.set('cid', String(<?= (int)$holonId ?>));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set('selected_text', selectedText);
            formData.set('full_text', fullText);

            summarizeController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/html/summarize.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: summarizeController ? summarizeController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de résumer cette sélection.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de résumer cette sélection.');
            }

            const summarizedText = String(payload.text || '').trim();
            if (summarizedText === '') {
                throw new Error('Le résumé est vide.');
            }

            if (typeof htmlField.replaceSelectionWithText !== 'function') {
                throw new Error('Impossible de remplacer la sélection dans l’éditeur.');
            }

            htmlField.replaceSelectionWithText(summarizedText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setSummarizeMode('idle');
            setDictationStatus('Sélection résumée.', 'success');
        } catch (error) {
            setSummarizeMode('idle');
            setDictationStatus(
                error && error.message ? String(error.message) : 'Impossible de résumer cette sélection.',
                'error'
            );
        } finally {
            summarizeController = null;
        }
    }

    ensureHtmlFieldMounted();
    initializeKeywordTags();

    syncDictationToolbarButtons();

    function setStatus(message) {
        if (!statusNode) {
            return;
        }

        const text = String(message || '').trim();
        statusNode.hidden = text === '';
        statusNode.textContent = text;
    }

    function setSavingState(isSaving) {
        form.querySelectorAll('input, textarea, select, button').forEach(function (field) {
            field.disabled = !!isSaving;
        });
        document.querySelectorAll('[form="' + form.id + '"]').forEach(function (field) {
            field.disabled = !!isSaving;
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            cleanupDictation({ discard: true });
            cleanupRewrite({ keepStatus: true });
            cleanupSummarize({ keepStatus: true });
            releaseEditLock({ keepalive: true });
            closeDocumentEditor({ returnToDetail: true });
        });
    }

    const handleDrawerClose = function () {
        cleanupDictation({ discard: true });
        cleanupRewrite({ keepStatus: true });
        cleanupSummarize({ keepStatus: true });
        releaseEditLock({ keepalive: true });
        destroyHtmlField();
        window.removeEventListener('omo-document-editor-drawer-close', handleDrawerClose);
    };

    window.addEventListener('omo-document-editor-drawer-close', handleDrawerClose);
    if (editorHost === 'project_picker') {
        window.addEventListener('common-topbar-modal-close', handleDrawerClose, { once: true });
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', syncTypeUi);
    }

    if (tagsEditor && tagsInput) {
        tagsEditor.addEventListener('click', function (event) {
            if (event.target instanceof Element && event.target.closest('.omo-document-editor__tag-remove')) {
                return;
            }

            tagsInput.focus();
        });

        tagsInput.addEventListener('keydown', function (event) {
            const key = String(event.key || '');
            const hasTypedValue = normalizeKeywordTag(tagsInput.value) !== '';

            if (key === 'Enter') {
                event.preventDefault();
                if (hasTypedValue) {
                    commitKeywordInput();
                }
                return;
            }

            if (key === ',') {
                event.preventDefault();
                if (hasTypedValue) {
                    commitKeywordInput();
                }
                return;
            }

            if (key === 'Tab' && hasTypedValue) {
                event.preventDefault();
                commitKeywordInput();
                return;
            }

            if (key === 'Backspace' && !hasTypedValue && keywordTags.length > 0) {
                event.preventDefault();
                keywordTags.splice(keywordTags.length - 1, 1);
                syncKeywordTagsField();
                renderKeywordTags();
            }
        });

        tagsInput.addEventListener('input', function () {
            flushKeywordInputDelimiters(false);
        });

        tagsInput.addEventListener('blur', function () {
            commitKeywordInput();
        });

        tagsInput.addEventListener('paste', function () {
            window.setTimeout(function () {
                flushKeywordInputDelimiters(true);
            }, 0);
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setStatus('');

        if (editLockLost) {
            setStatus('Ce document n’est plus verrouillé pour votre édition. Rechargez le formulaire.');
            return;
        }

        commitKeywordInput();

        if (
            uploadHasExistingFile
            && uploadInput
            && uploadInput.files
            && uploadInput.files.length > 0
            && !window.confirm(String(uiText.uploadReplaceConfirm || 'Le fichier actuel sera remplacé. Continuer ?'))
        ) {
            return;
        }

        const formData = new FormData(form);
        formData.set('content', !isHtmlTypeSelected()
            ? ''
            : (htmlField && typeof htmlField.getValue === 'function'
                ? String(htmlField.getValue() || '')
                : htmlValueCache));

        const shouldCloseDocumentDrawerAfterSave = editingDocumentId > 0
            && getSelectedDocumentType() === 'external_link'
            && !!(externalOpenInNewWindowField && externalOpenInNewWindowField.checked);
        const usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';

        if (usesSharedPendingState && !window.omoBeginPendingAction(form)) {
            return;
        }
        setSavingState(true);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('save_failed');
                }

                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'save_failed');
                }

                const savedDocumentId = Number(payload.id || editingDocumentId || 0);

                const refreshPromise = editorHost !== 'project' && editorHost !== 'project_picker' && typeof window.omoRefreshDocumentsPanel === 'function'
                    ? window.omoRefreshDocumentsPanel()
                    : Promise.resolve(null);

                return Promise.resolve(refreshPromise).finally(function () {
                    releaseEditLock({ keepalive: true });
                    cleanupDictation({ discard: true });
                    cleanupRewrite({ keepStatus: true });
                    cleanupSummarize({ keepStatus: true });

                    if (editorHost === 'project' || editorHost === 'project_picker') {
                        window.dispatchEvent(new CustomEvent('omo-project-document-saved', {
                            detail: {
                                projectId: Number(form.querySelector('input[name="project_id"]') && form.querySelector('input[name="project_id"]').value || 0),
                                documentId: savedDocumentId
                            }
                        }));
                        closeDocumentEditor();
                    } else if (savedDocumentId > 0) {
                        if (shouldCloseDocumentDrawerAfterSave) {
                            closeDocumentEditor();
                            return;
                        }

                        closeDocumentEditor({
                            returnToDetail: true,
                            force: true
                        });

                        if (typeof window.omoOpenDrawerHashState === 'function') {
                            window.omoOpenDrawerHashState('documents-d' + String(savedDocumentId));
                        }
                    } else {
                        closeDocumentEditor({ returnToDetail: true });
                    }
                });
            })
            .catch(function (error) {
                setStatus(error && error.message && error.message !== 'save_failed'
                    ? error.message
                    : 'Impossible d’enregistrer ce document pour le moment.');
            })
            .finally(function () {
                setSavingState(false);
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                }
                syncDictationToolbarButtons();
            });
    });

    syncTypeUi();
    scheduleEditLockHeartbeat();
    scheduleDraftSync(400);
    window.addEventListener('pagehide', function () {
        releaseEditLock({ beacon: true });
    }, { once: true });
})();
</script>
