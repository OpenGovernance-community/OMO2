<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = (int)($_GET['hid'] ?? 0);
$organization = new Organization();
$moveData = null;
$errorMessage = '';

if ($organizationId <= 0) {
    $errorMessage = "Aucune organisation n'est actuellement selectionnee.";
} elseif ($holonId <= 0) {
$errorMessage = 'L’espace à déplacer est invalide.';
} elseif (!$organization->load($organizationId)) {
    $errorMessage = "L'organisation demandee est introuvable.";
} else {
    $moveData = $organization->getHolonMoveEditorData($holonId);

    if (($moveData['holonId'] ?? 0) !== $holonId || !is_array($moveData['holon'] ?? null)) {
$errorMessage = 'L’espace demandé est introuvable.';
    } elseif (empty($moveData['canMove'])) {
$errorMessage = "Vous n’avez pas les droits pour déplacer cet espace.";
    } else {
        $alternativeCount = 0;
        foreach (($moveData['destinations'] ?? array()) as $destination) {
            if (empty($destination['isCurrentParent'])) {
                $alternativeCount += 1;
            }
        }

        if ($alternativeCount <= 0) {
$errorMessage = 'Aucune destination compatible n’a été trouvée pour cet espace.';
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-holon-move__empty generic-description"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-holon-move-form" class="omo-holon-move generic-stack generic-stack--flush">
        <div class="omo-holon-move__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-holon-move__header-copy">
        <div class="generic-card-title generic-card-title--eyebrow">Espace</div>
        <h3 class="generic-card-title generic-card-title--medium">Déplacer un espace</h3>
            </div>
        </div>
        <div class="omo-holon-move__shell generic-drawer-content">
        <div class="omo-holon-move__intro generic-description">
            <strong><?= omoApiEscape((string)($moveData['holon']['name'] ?? '')) ?></strong>
            <span>&rarr;</span>
        </div>

        <label class="omo-holon-move__field generic-stack generic-stack--compact">
            <span>Ou ca va</span>
            <input type="search" id="omo-holon-move-search" class="generic-form-control" placeholder="Rechercher une destination">
        </label>

        <label class="omo-holon-move__field generic-stack generic-stack--compact">
            <select id="omo-holon-move-destination" class="omo-holon-move__select generic-form-control" size="10" required></select>
        </label>

        <div id="omo-holon-move-hint" class="omo-holon-move__hint generic-help-text"></div>
        <div id="omo-holon-move-status" class="omo-holon-move__status generic-feedback" hidden></div>

        <div class="omo-holon-move__actions generic-action-row">
            <button type="button" class="omo-holon-move__button generic-action-button generic-action-button--secondary" id="omo-holon-move-cancel">Annuler</button>
            <button type="submit" class="omo-holon-move__button generic-action-button generic-action-button--main" id="omo-holon-move-submit">Deplacer</button>
        </div>
        </div>
    </form>
<?php endif; ?>

<?php if ($moveData !== null && $errorMessage === ''): ?>
<?= commonPageScriptTags('/omo/api/holons/move.js', [
    'data' => $moveData,
]) ?>
<?php endif; ?>

<style>
.omo-holon-move__empty {
    display: grid;
    gap: 16px;
    color: var(--color-text, #1f2937);
}

.omo-holon-move__empty {
    padding: 18px;
}

.omo-holon-move__intro {
    display: flex;
    align-items: center;
    gap: 8px;
}

.omo-holon-move__select {
    min-height: 240px;
}

.omo-holon-move__status[hidden] {
    display: none !important;
}

</style>
