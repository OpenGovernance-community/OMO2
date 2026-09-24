<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Organization;

$sourceLang = [
    'documents.move.error.invalid' => ['text' => 'Le document à déplacer est invalide.', 'context' => 'Error shown when the source document id is invalid.'],
    'documents.move.error.not_found' => ['text' => 'Le document demandé est introuvable.', 'context' => 'Error shown when the source document cannot be loaded.'],
    'documents.move.error.organization_not_found' => ['text' => 'Organisation introuvable.', 'context' => 'Error shown when the document organization cannot be loaded.'],
    'documents.move.error.forbidden' => ['text' => 'Vous n’avez pas le droit de déplacer ce document.', 'context' => 'Error shown when the user cannot move the document.'],
    'documents.move.error.no_destination' => ['text' => 'Aucune destination compatible n’a été trouvée pour ce document.', 'context' => 'Error shown when no destination is available.'],
    'documents.move.error.bulk_not_found' => ['text' => 'L’un des documents sélectionnés est introuvable.', 'context' => 'Error shown when a selected document cannot be loaded for a bulk move.'],
    'documents.move.error.bulk_forbidden' => ['text' => 'Vous n’avez pas le droit de déplacer tous les documents sélectionnés.', 'context' => 'Error shown when the user cannot move every selected document.'],
    'documents.move.selected_count' => ['text' => '{count} documents sélectionnés', 'context' => 'Summary shown when several documents are selected for moving.'],
    'documents.move.field.destination' => ['text' => 'Dossier de destination', 'context' => 'Label shown above the destination picker.'],
    'documents.move.field.search_placeholder' => ['text' => 'Rechercher une destination', 'context' => 'Search placeholder used in the move dialog.'],
    'documents.move.field.holon' => ['text' => 'Holon de destination', 'context' => 'Label shown above the visual holon picker in the move dialog.'],
    'documents.move.destination.holon_root' => ['text' => 'À la racine du holon', 'context' => 'Destination choice placing a document at the root of the selected holon.'],
    'documents.move.action.cancel' => ['text' => 'Annuler', 'context' => 'Button used to cancel document moving.'],
    'documents.move.action.submit' => ['text' => 'Déplacer', 'context' => 'Button used to submit document moving.'],
    'documents.move.status.invalid_destination' => ['text' => 'Choisissez une destination valide.', 'context' => 'Hint or error shown when no valid destination is selected.'],
    'documents.move.status.select_other' => ['text' => 'Sélectionnez une autre destination pour activer le déplacement.', 'context' => 'Hint shown when the current destination is selected.'],
    'documents.move.status.no_match' => ['text' => 'Aucune destination correspondante', 'context' => 'Option shown when the destination search yields no result.'],
    'documents.move.status.submit_other' => ['text' => 'Sélectionnez une autre destination avant de déplacer ce document.', 'context' => 'Error shown when trying to submit with the current destination.'],
];

$lang = omoLoadTranslationBundle('omo_documents_move', $sourceLang);

function omoDocumentsMoveT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$documentIds = [];
$rawDocumentIds = $_GET['ids'] ?? [];
if (!is_array($rawDocumentIds)) {
    $rawDocumentIds = preg_split('/[,\s]+/', (string)$rawDocumentIds, -1, PREG_SPLIT_NO_EMPTY);
}
foreach ($rawDocumentIds as $rawDocumentId) {
    $normalizedDocumentId = (int)$rawDocumentId;
    if ($normalizedDocumentId > 0 && !in_array($normalizedDocumentId, $documentIds, true)) {
        $documentIds[] = $normalizedDocumentId;
    }
}
$documentId = (int)($_GET['id'] ?? 0);
if ($documentId > 0 && !in_array($documentId, $documentIds, true)) {
    array_unshift($documentIds, $documentId);
}
$documentId = (int)($documentIds[0] ?? 0);
$isBulkMove = count($documentIds) > 1;
$document = new Document();
$organization = new Organization();
$moveData = null;
$errorMessage = '';

if ($documentId <= 0) {
    $errorMessage = omoDocumentsMoveT('documents.move.error.invalid');
} elseif (
    !$document->load($documentId)
    || (int)$document->get('IDorganization') <= 0
) {
    $errorMessage = omoDocumentsMoveT('documents.move.error.not_found');
} else {
    $organizationId = (int)$document->get('IDorganization');

    if (!$organization->load($organizationId)) {
        $errorMessage = omoDocumentsMoveT('documents.move.error.organization_not_found');
    } else {
        $moveData = $organization->getDocumentMoveEditorData($documentId);

        if (($moveData['documentId'] ?? 0) !== $documentId || !is_array($moveData['document'] ?? null)) {
            $errorMessage = omoDocumentsMoveT('documents.move.error.not_found');
        } elseif (empty($moveData['canMove'])) {
            $errorMessage = omoDocumentsMoveT('documents.move.error.forbidden');
        } else {
            foreach ($documentIds as $selectedDocumentId) {
                $selectedDocument = new Document();
                if (
                    !$selectedDocument->load($selectedDocumentId)
                    || (int)$selectedDocument->get('IDorganization') !== $organizationId
                ) {
                    $errorMessage = omoDocumentsMoveT('documents.move.error.bulk_not_found');
                    break;
                }
                if (!$selectedDocument->canMoveInOrganizationContext($organizationId, (int)commonGetCurrentUserId())) {
                    $errorMessage = omoDocumentsMoveT('documents.move.error.bulk_forbidden');
                    break;
                }
            }

            $alternativeCount = 0;
            foreach (($moveData['destinations'] ?? array()) as $destination) {
                if (empty($destination['isCurrentDestination'])) {
                    $alternativeCount += 1;
                }
            }

            if ($errorMessage === '' && $alternativeCount <= 0) {
                $errorMessage = omoDocumentsMoveT('documents.move.error.no_destination');
            }

            if ($errorMessage === '') {
                $moveData['documentIds'] = $documentIds;
                $moveData['documentCount'] = count($documentIds);
            }
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-document-move__empty generic-description generic-stack generic-stack--roomy"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-document-move-form" class="omo-document-move generic-stack generic-stack--flush">
        <div class="omo-document-move__shell generic-drawer-content">
        <div class="omo-document-move__intro generic-title-row generic-title-row--center generic-description">
            <strong><?= omoApiEscape($isBulkMove
                ? omoDocumentsMoveT('documents.move.selected_count', ['count' => count($documentIds)])
                : (string)($moveData['document']['title'] ?? '')) ?></strong>
            <span>&rarr;</span>
            <span data-omo-document-move-path></span>
        </div>

        <div class="omo-document-move__picker omo-resource-picker">
            <aside class="omo-resource-picker__navigation omo-document-move__navigation generic-stack">
                <span class="generic-form-label"><?= omoApiEscape(omoDocumentsMoveT('documents.move.field.holon')) ?></span>
                <div class="omo-document-move__holon-picker" data-omo-document-move-holon-picker></div>
            </aside>

            <div class="omo-resource-picker__content omo-document-move__content generic-stack">
                <label class="omo-document-move__field generic-stack generic-stack--compact">
                    <span class="generic-form-label"><?= omoApiEscape(omoDocumentsMoveT('documents.move.field.destination')) ?></span>
                    <span class="omo-resource-picker__quick-search">
                        <img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true">
                        <input type="search" id="omo-document-move-search" class="generic-form-control" placeholder="<?= omoApiEscape(omoDocumentsMoveT('documents.move.field.search_placeholder')) ?>">
                    </span>
                </label>

                <div class="omo-document-move__field generic-stack generic-stack--compact">
                    <div id="omo-document-move-destination" class="omo-document-move__destinations generic-stack generic-stack--compact" aria-label="<?= omoApiEscape(omoDocumentsMoveT('documents.move.field.destination')) ?>"></div>
                </div>
            </div>
        </div>

        <div id="omo-document-move-status" class="omo-document-move__status generic-feedback" hidden></div>

        <div class="omo-document-move__actions generic-action-row">
            <div id="omo-document-move-hint" class="omo-document-move__hint generic-help-text"></div>
            <button type="button" class="omo-document-move__button generic-action-button generic-action-button--secondary" id="omo-document-move-cancel"><?= omoApiEscape(omoDocumentsMoveT('documents.move.action.cancel')) ?></button>
            <button type="submit" class="omo-document-move__button generic-action-button generic-action-button--main" id="omo-document-move-submit"><?= omoApiEscape(omoDocumentsMoveT('documents.move.action.submit')) ?></button>
        </div>
        </div>
    </form>
<?php endif; ?>

<?php if ($moveData !== null && $errorMessage === ''): ?>
<?= commonPageScriptTags('/omo/api/documents/move.js', [
    'data' => $moveData,
    'text' => [
        'invalidDestination' => omoDocumentsMoveT('documents.move.status.invalid_destination'),
        'selectOther' => omoDocumentsMoveT('documents.move.status.select_other'),
        'noMatch' => omoDocumentsMoveT('documents.move.status.no_match'),
        'submitOther' => omoDocumentsMoveT('documents.move.status.submit_other'),
        'holonRoot' => omoDocumentsMoveT('documents.move.destination.holon_root'),
    ],
]) ?>
<?php endif; ?>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/documents/move.css') ?>">
