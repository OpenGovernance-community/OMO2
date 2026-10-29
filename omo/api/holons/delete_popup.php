<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/choice/holon-removal-fields.php';

use dbObject\Holon;
use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = (int)($_GET['hid'] ?? $_POST['hid'] ?? 0);
$organization = new Organization();
$holon = new Holon();
$errorMessage = '';

if ($organizationId <= 0 || $holonId <= 0) {
$errorMessage = omoHolonsUiT('l_espace_a_supprimer_est_invalide');
} elseif (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
$errorMessage = omoHolonsUiT('l_espace_demande_est_introuvable');
} elseif (!$holon->isAllowed('CAN_DELETE_HOLON') || !$holon->canDelete() || !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)) {
$errorMessage = omoHolonsUiT('vous_n_avez_pas_les_droits_pour_supprimer_cet_espace');
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

    $choice = $_POST['authority_choice'] ?? '';
    if (!\dbObject\Authority::validateHolonRemovalChoice($choice)) {
        http_response_code(422);
        echo json_encode(['status' => false, 'message' => omoHolonRemovalT('required')]);
        exit;
    }
    $result = $organization->deleteHolonDefinition($holonId, (int)commonGetCurrentUserId(), false, $choice);
    if (!($result['status'] ?? false)) {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
        'message' => (string)($result['message'] ?? omoHolonsUiT('l_espace_n_a_pas_pu_etre_supprime')),
            'parent' => $result['parent'] ?? null,
            'holon' => $result['holon'] ?? null,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? omoHolonsUiT('espace_supprime')),
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
                <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoHolonsUiT('suppression'), ENT_QUOTES, 'UTF-8') ?></div>
    <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoHolonsUiT('supprimer_un_espace'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
        </div>
        <div class="omo-holon-delete__shell generic-drawer-content">
        <div class="omo-holon-delete__intro">
            <div class="omo-holon-delete__eyebrow generic-title generic-title--eyebrow"><?= htmlspecialchars(omoHolonsUiT('suppression'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="omo-holon-delete__title generic-title generic-title--medium">
                <?= htmlspecialchars(omoHolonsUiT('supprimer'), ENT_QUOTES, 'UTF-8') ?> <?= omoApiEscape($typeLabel) ?> <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong> ?
            </div>
        </div>

        <?php if ($descendantCount > 0): ?>
            <div class="omo-holon-delete__warning">
                Attention : <?= (int)$descendantCount ?> element<?= $descendantCount > 1 ? 's seront aussi supprimes.' : ' sera aussi supprime.' ?>
            </div>
        <?php endif; ?>

        <?php omoRenderHolonRemovalFields(); ?>

        <div id="omoHolonDeletePopupFeedback" class="omo-holon-delete__feedback generic-feedback"></div>

        <div class="omo-holon-delete__actions generic-action-row">
            <button type="button" id="omoHolonDeletePopupCancel" class="omo-holon-delete__button generic-action-button generic-action-button--secondary">
                <?= htmlspecialchars(omoHolonsUiT('annuler'), ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="submit" id="omoHolonDeletePopupSubmit" class="omo-holon-delete__button generic-action-button generic-action-button--danger">
                <?= htmlspecialchars(omoHolonsUiT('supprimer'), ENT_QUOTES, 'UTF-8') ?>
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
