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
    $errorMessage = "Le holon a supprimer est invalide.";
} elseif (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    $errorMessage = 'Le holon demande est introuvable.';
} elseif (!$holon->canEdit() || !$holon->canDelete() || !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)) {
    $errorMessage = "Vous n'avez pas les droits pour supprimer ce holon.";
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
            'message' => (string)($result['message'] ?? "Le holon n'a pas pu etre supprime."),
            'parent' => $result['parent'] ?? null,
            'holon' => $result['holon'] ?? null,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? 'Holon supprime.'),
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
    <div class="omo-holon-delete__empty"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form
        id="omoHolonDeletePopupForm"
        class="omo-holon-delete"
        action="api/holons/delete_popup.php?hid=<?= (int)$holon->getId() ?>"
        method="post"
    >
        <div class="omo-holon-delete__intro">
            <div class="omo-holon-delete__eyebrow">Suppression</div>
            <div class="omo-holon-delete__title">
                Supprimer <?= omoApiEscape($typeLabel) ?> <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong> ?
            </div>
        </div>

        <?php if ($descendantCount > 0): ?>
            <div class="omo-holon-delete__warning">
                Attention : <?= (int)$descendantCount ?> element<?= $descendantCount > 1 ? 's seront aussi supprimes.' : ' sera aussi supprime.' ?>
            </div>
        <?php endif; ?>

        <div class="omo-holon-delete__hint">
            Cette fenetre pourra ensuite accueillir des options complementaires pour gerer le contenu rattache.
        </div>

        <div id="omoHolonDeletePopupFeedback" class="omo-holon-delete__feedback"></div>

        <div class="omo-holon-delete__actions">
            <button type="button" id="omoHolonDeletePopupCancel" class="omo-holon-delete__button generic-action-button generic-action-button--secondary">
                Annuler
            </button>
            <button type="submit" id="omoHolonDeletePopupSubmit" class="omo-holon-delete__button generic-action-button generic-action-button--danger">
                Supprimer
            </button>
        </div>
    </form>

    <script>
    (function () {
        var form = document.getElementById('omoHolonDeletePopupForm');
        var feedback = document.getElementById('omoHolonDeletePopupFeedback');
        var submitButton = document.getElementById('omoHolonDeletePopupSubmit');
        var cancelButton = document.getElementById('omoHolonDeletePopupCancel');
        var parentId = <?= (int)$parentId ?>;
        var parentIsRoot = <?= $parentIsRoot ? 'true' : 'false' ?>;
        var organizationId = <?= (int)$organizationId ?>;

        if (!form || !feedback || !submitButton || !cancelButton) {
            return;
        }

        function closePopup() {
            if (typeof window.omoSetPopupHashState === 'function') {
                window.omoSetPopupHashState({
                    open: false
                });
                return;
            }

            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        }

        cancelButton.addEventListener('click', function () {
            closePopup();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');
            submitButton.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return null;
                    }).then(function (data) {
                        return {
                            ok: response.ok,
                            data: data
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data || !result.data.status) {
                        feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                        submitButton.disabled = false;
                        return;
                    }

                    closePopup();

                    var targetCid = parentId > 0 && !parentIsRoot ? parentId : null;
                    var route = typeof parseUrl === 'function'
                        ? parseUrl()
                        : { oid: organizationId, cid: null, hash: null };

                    if (typeof navigate === 'function') {
                        navigate(route.oid, targetCid, route.hash || null);
                    } else if (typeof loadContent === 'function') {
                        var leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || organizationId || 0);
                        if (targetCid) {
                            leftUrl += '&cid=' + targetCid;
                        }
                        loadContent('#panel-left', leftUrl);
                    }

                    if (typeof window.omoReloadStructureAndFocus === 'function') {
                        window.omoReloadStructureAndFocus(targetCid, {
                            quickZoom: true
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                            detail: {
                                cid: targetCid
                            }
                        }));
                    }
                })
                .catch(function () {
                    feedback.textContent = 'Impossible de supprimer ce holon pour le moment.';
                    submitButton.disabled = false;
                });
        });
    })();
    </script>

    <style>
        .omo-holon-delete,
        .omo-holon-delete__empty {
            display: grid;
            gap: 16px;
            padding: 8px 4px 4px;
            color: var(--color-text, #1f2937);
        }

        .omo-holon-delete__eyebrow,
        .omo-holon-delete__hint,
        .omo-holon-delete__empty {
            color: var(--topbar-panel-muted, #64748b);
            line-height: 1.45;
        }

        .omo-holon-delete__eyebrow {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .omo-holon-delete__intro,
        .omo-holon-delete__warning {
            display: grid;
            gap: 8px;
        }

        .omo-holon-delete__title {
            line-height: 1.45;
        }

        .omo-holon-delete__warning {
            padding: 12px 14px;
            border-radius: 12px;
            background: color-mix(in srgb, #dc2626 10%, white);
            color: #991b1b;
            border: 1px solid color-mix(in srgb, #dc2626 22%, transparent);
        }

        .omo-holon-delete__feedback {
            min-height: 22px;
            color: #b91c1c;
            font-weight: 600;
        }

        .omo-holon-delete__actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

    </style>
<?php endif; ?>
