<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = (int)($_GET['hid'] ?? $_POST['hid'] ?? 0);
$organization = new Organization();
$holon = new Holon();
$errorMessage = '';

if ($organizationId <= 0 || $holonId <= 0) {
$errorMessage = "L’espace à supprimer est invalide.";
} elseif (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
$errorMessage = 'L’espace demandé est introuvable.';
} elseif (!$holon->isAllowed('CAN_DELETE_HOLON') || !$holon->canDelete() || !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)) {
$errorMessage = "Vous n'avez pas les droits pour supprimer cet espace.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    if ($errorMessage !== '') {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
            'message' => $errorMessage,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $result = $organization->deleteHolonDefinition($holonId, (int)commonGetCurrentUserId());
    if (!($result['status'] ?? false)) {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
        'message' => (string)($result['message'] ?? "L’espace n’a pas pu être supprimé."),
            'parent' => $result['parent'] ?? null,
            'holon' => $result['holon'] ?? null,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? 'Espace supprimé.'),
        'parent' => $result['parent'] ?? null,
        'holon' => $result['holon'] ?? null,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$parentHolon = $holon->getParentHolon();
$parentId = $parentHolon ? (int)$parentHolon->getId() : 0;
$parentIsRoot = $parentHolon ? ((int)$parentHolon->get('IDtypeholon') === 4) : false;
$descendantCount = $holon->countVisibleDescendants();
$typeLabel = strtolower((string)$holon->getTemplateLabel(true));
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-holon-delete__empty generic-description"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form
        id="omoHolonDeletePopupForm"
        class="omo-holon-delete generic-stack generic-stack--flush"
        action="api/holons/delete_popup.php?hid=<?= (int)$holon->getId() ?>"
        method="post"
    >
        <div class="omo-holon-delete__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-holon-delete__header-copy">
                <div class="generic-card-title generic-card-title--eyebrow">Suppression</div>
    <h3 class="generic-card-title generic-card-title--medium">Supprimer un espace</h3>
            </div>
        </div>
        <div class="omo-holon-delete__shell generic-drawer-content">
        <div class="omo-holon-delete__intro">
            <div class="omo-holon-delete__eyebrow generic-title generic-title--eyebrow">Suppression</div>
            <div class="omo-holon-delete__title generic-title generic-title--medium">
                Supprimer <?= omoApiEscape($typeLabel) ?> <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong> ?
            </div>
        </div>

        <?php if ($descendantCount > 0): ?>
            <div class="omo-holon-delete__warning">
                Attention : <?= (int)$descendantCount ?> element<?= $descendantCount > 1 ? 's seront aussi supprimes.' : ' sera aussi supprime.' ?>
            </div>
        <?php endif; ?>

        <div class="omo-holon-delete__hint generic-description generic-description--compact">
            Cette fenetre pourra ensuite accueillir des options complementaires pour gerer le contenu rattache.
        </div>

        <div id="omoHolonDeletePopupFeedback" class="omo-holon-delete__feedback generic-feedback"></div>

        <div class="omo-holon-delete__actions generic-action-row">
            <button type="button" id="omoHolonDeletePopupCancel" class="omo-holon-delete__button generic-action-button generic-action-button--secondary">
                Annuler
            </button>
            <button type="submit" id="omoHolonDeletePopupSubmit" class="omo-holon-delete__button generic-action-button generic-action-button--danger">
                Supprimer
            </button>
        </div>
        </div>
    </form>

    <?= commonPageScriptTags('/omo/api/holons/delete_popup.js', [
    'parentId' => (int)$parentId,
    'parentIsRoot' => ($parentIsRoot),
    'organizationId' => (int)$organizationId,
]) ?>

    <style>
        .omo-holon-delete__empty {
            display: grid;
            gap: 16px;
            color: var(--color-text, #1f2937);
        }

        .omo-holon-delete__empty {
            padding: 18px;
        }

        .omo-holon-delete__intro,
        .omo-holon-delete__warning {
            display: grid;
            gap: 8px;
        }

        .omo-holon-delete__warning {
            padding: 12px 14px;
            border-radius: var(--radius-md);
            background: color-mix(in srgb, #dc2626 10%, white);
            color: #991b1b;
            border: 1px solid color-mix(in srgb, #dc2626 22%, transparent);
        }

    </style>
<?php endif; ?>
