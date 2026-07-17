<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/holon_scope_helper.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$requestedHolonId = (int)($_GET['cid'] ?? $_POST['cid'] ?? 0);
$organization = new \dbObject\Organization();
$contextHolon = null;
$holonOptions = [];
$rootHolon = null;
$selectedHolonId = 0;
$currentContextSelectable = false;
$defaultTitle = trim((string)($_POST['title'] ?? ''));
$defaultDescription = trim((string)($_POST['description'] ?? ''));
$formAction = '/omo/api/tension_save.php';

if ($organizationId > 0 && $organization->load($organizationId)) {
    $contextHolon = commonHolonScopeResolveOrganizationHolon($organization, $requestedHolonId);
    $rootHolon = $organization->getStructuralRootHolon();
    $holonOptions = commonHolonScopeLoadOptionsForOrganization($organization);

    foreach ($holonOptions as $holonOption) {
        $optionId = (int)($holonOption['id'] ?? 0);
        if ($contextHolon instanceof \dbObject\Holon && $optionId === (int)$contextHolon->getId()) {
            $currentContextSelectable = !empty($holonOption['selectable']);
            if ($currentContextSelectable) {
                $selectedHolonId = $optionId;
            }
            break;
        }
    }

    if ($selectedHolonId <= 0) {
        foreach ($holonOptions as $holonOption) {
            if (!empty($holonOption['selectable'])) {
                $selectedHolonId = (int)($holonOption['id'] ?? 0);
                break;
            }
        }
    }
}
?>
<div
    class="omo-tension-popup"
    id="omoTensionPopup"
    data-submit-url="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
    data-default-oid="<?= (int)$organizationId ?>"
    data-default-cid="<?= $contextHolon instanceof \dbObject\Holon ? (int)$contextHolon->getId() : 0 ?>"
>
    <style>
    .omo-tension-popup {
        display: grid;
        gap: 0;
        color: var(--color-text, #1f2937);
    }

    .omo-tension-popup__panel,
    .omo-tension-popup__error {
        --generic-section-padding-block: 18px;
    }

    .omo-tension-popup__header {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .omo-tension-popup__header-copy {
        display: grid;
        gap: 10px;
        min-width: 0;
    }

    .omo-tension-popup__shell {
        display: grid;
        gap: 16px;
        padding: 16px 18px 18px;
    }

    .omo-tension-popup__hero {
        min-width: 0;
    }

    .omo-tension-popup__hero-copy,
    .omo-tension-popup__form,
    .omo-tension-popup__field {
        display: grid;
        gap: 10px;
    }

    .omo-tension-popup__hero p,
    .omo-tension-popup__hint,
    .omo-tension-popup__feedback {
        margin: 0;
        line-height: 1.5;
        color: var(--color-text-light, #6b7280);
    }

    .omo-tension-popup__hero-figure {
        width: 92px;
        height: 92px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        background: color-mix(in srgb, #f59e0b 12%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, #f59e0b 24%, var(--color-border, #e5e7eb));
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    }

    .omo-tension-popup__hero-figure img {
        width: 62px;
        height: 62px;
        object-fit: contain;
        display: block;
    }

    .omo-tension-popup__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-tension-popup__badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, #f59e0b 12%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, #f59e0b 22%, var(--color-border, #e5e7eb));
        color: #b45309;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .omo-tension-popup__error {
        display: grid;
        gap: 10px;
        border: 1px solid rgba(185, 28, 28, 0.18);
        background: rgba(185, 28, 28, 0.06);
        color: #991b1b;
    }

    .omo-tension-popup__error h3,
    .omo-tension-popup__panel h3 {
        margin: 0;
    }

    .omo-tension-popup__field label {
        font-weight: 700;
    }

    .omo-tension-popup__feedback {
        min-height: 24px;
        font-size: 0.95rem;
    }

    .omo-tension-popup__feedback.is-error {
        color: #b91c1c;
    }

    .omo-tension-popup__feedback.is-success {
        color: #166534;
    }

    .omo-tension-popup__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 640px) {
        .omo-tension-popup__hero {
            grid-template-columns: 1fr;
        }

        .omo-tension-popup__hero-figure {
            order: -1;
            width: 82px;
            height: 82px;
        }
    }
    </style>

    <?php if ($currentUserId <= 0): ?>
        <div class="omo-tension-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Connexion requise</h3>
            <p>Vous devez etre connecte pour declarer une tension.</p>
        </div>
    <?php elseif ($organizationId <= 0 || !$organization->getId() || !commonCurrentUserHasOrganizationAccess($organizationId)): ?>
        <div class="omo-tension-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Contexte indisponible</h3>
            <p>Impossible de retrouver l organisation courante pour enregistrer la tension.</p>
        </div>
    <?php else: ?>
        <div class="omo-tension-popup__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-tension-popup__header-copy omo-tension-popup__hero omo-tension-popup__hero-copy">
                <div class="generic-card-title generic-card-title--eyebrow">Gouvernance partagee</div>
                <h2 class="generic-card-title generic-card-title--large">Nouvelle tension</h2>
                <p>Une tension capte un besoin, un inconfort ou une question ouverte dont l issue n est pas encore connue.</p>
                <div class="omo-tension-popup__meta">
                    <span class="omo-tension-popup__badge">Organisation: <?= htmlspecialchars((string)$organization->getLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($contextHolon instanceof \dbObject\Holon): ?>
                        <span class="omo-tension-popup__badge">Holon: <?= htmlspecialchars((string)$contextHolon->getLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="omo-tension-popup__hero-figure" aria-hidden="true">
                <img src="/common/assets/icon-topbar-tension.png" alt="">
            </div>
        </div>
        <div class="omo-tension-popup__shell">
        <div class="omo-tension-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Saisir la tension</h3>
            <p class="omo-tension-popup__hint">Le titre doit rester tres court, maximum 3 mots. La description reste en texte simple.</p>

            <form class="omo-tension-popup__form" id="omoTensionForm">
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

                <div class="omo-tension-popup__field">
                    <label for="omoTensionTitle">Titre</label>
                    <input
                        type="text"
                        class="generic-form-control"
                        id="omoTensionTitle"
                        name="title"
                        maxlength="80"
                        value="<?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ex: roles flous"
                        required
                    >
                    <small class="omo-tension-popup__hint" data-omo-tension-title-hint>3 mots maximum.</small>
                </div>

                <div class="omo-tension-popup__field">
                    <label for="omoTensionHolon">Holon</label>
                    <?php if ($contextHolon instanceof \dbObject\Holon && !$currentContextSelectable): ?>
                        <small class="omo-tension-popup__hint">Le holon courant n est pas selectionnable directement. Son chemin reste visible en grise ci-dessous.</small>
                    <?php endif; ?>
                    <select class="generic-form-control" id="omoTensionHolon" name="IDholon">
                        <?php
                        $organizationOptionSelectable = false;
                        foreach ($holonOptions as $holonOption) {
                            if (!$rootHolon instanceof \dbObject\Holon || (int)($holonOption['id'] ?? 0) !== (int)$rootHolon->getId()) {
                                continue;
                            }

                            $organizationOptionSelectable = !empty($holonOption['selectable']);
                            break;
                        }
                        ?>
                        <option value=""<?= $organizationOptionSelectable ? '' : ' disabled' ?>>Organisation entiere</option>
                        <?php foreach ($holonOptions as $holonOption): ?>
                            <?php
                            $optionId = (int)($holonOption['id'] ?? 0);
                            $optionLabel = trim((string)($holonOption['label'] ?? ''));
                            if ($optionId <= 0 || $optionLabel === '') {
                                continue;
                            }
                            ?>
                            <option
                                value="<?= $optionId ?>"
                                <?= $selectedHolonId === $optionId ? ' selected' : '' ?>
                                <?= !empty($holonOption['disabled']) ? ' disabled' : '' ?>
                                <?= !empty($holonOption['disabled']) ? ' style="color:#9ca3af;"' : '' ?>
                            >
                                <?= htmlspecialchars($optionLabel . (!empty($holonOption['disabled']) ? ' [chemin]' : ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="omo-tension-popup__field">
                    <label for="omoTensionDescription">Description</label>
                    <textarea
                        class="generic-form-control"
                        id="omoTensionDescription"
                        name="description"
                        rows="8"
                        placeholder="Decrire le besoin, la situation ou la question ouverte."
                        required
                    ><?= htmlspecialchars($defaultDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="omo-tension-popup__feedback" id="omoTensionFeedback" aria-live="polite"></div>

                <div class="omo-tension-popup__actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-tension-cancel>Annuler</button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoTensionSubmit">Enregistrer</button>
                </div>
            </form>
        </div>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    if (typeof window.__omoPopupCleanup === 'function') {
        window.__omoPopupCleanup();
    }

    const root = document.getElementById('omoTensionPopup');
    if (!root) {
        return;
    }

    const form = document.getElementById('omoTensionForm');
    const feedback = document.getElementById('omoTensionFeedback');
    const titleInput = document.getElementById('omoTensionTitle');
    const titleHint = root.querySelector('[data-omo-tension-title-hint]');
    const submitButton = document.getElementById('omoTensionSubmit');
    const cancelButton = root.querySelector('[data-omo-tension-cancel]');

    function setFeedback(message, type) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message || '';
        feedback.classList.remove('is-error', 'is-success');
        if (type === 'error') {
            feedback.classList.add('is-error');
        } else if (type === 'success') {
            feedback.classList.add('is-success');
        }
    }

    function countWords(value) {
        const normalized = String(value || '').trim();
        if (normalized === '') {
            return 0;
        }

        return normalized.split(/\s+/).filter(Boolean).length;
    }

    function refreshTitleHint() {
        if (!titleInput || !titleHint) {
            return true;
        }

        const wordCount = countWords(titleInput.value);
        const isValid = wordCount <= 3;
        titleHint.textContent = wordCount > 0
            ? wordCount + ' / 3 mots'
            : '3 mots maximum.';
        titleHint.style.color = isValid ? '' : '#b91c1c';

        return isValid;
    }

    if (titleInput) {
        titleInput.addEventListener('input', function () {
            refreshTitleHint();
        });
        refreshTitleHint();
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        });
    }

    if (!form) {
        window.__omoPopupCleanup = function () {};
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!refreshTitleHint()) {
            setFeedback('Le titre doit contenir au maximum 3 mots.', 'error');
            if (titleInput) {
                titleInput.focus();
            }
            return;
        }

        const submitUrl = root.getAttribute('data-submit-url') || '/omo/api/tension_save.php';
        const formData = new FormData(form);
        setFeedback('Enregistrement...', '');

        if (submitButton) {
            submitButton.disabled = true;
        }

        fetch(submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
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
                    throw new Error(result && result.data && result.data.message ? result.data.message : 'save_failed');
                }

                setFeedback(result.data.message || 'Tension enregistree.', 'success');
                form.reset();
                refreshTitleHint();

                window.setTimeout(function () {
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                }, 700);
            })
            .catch(function (error) {
                const message = error && error.message && error.message !== 'save_failed'
                    ? error.message
                    : 'Impossible d enregistrer la tension.';
                setFeedback(message, 'error');
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
    });

    window.__omoPopupCleanup = function () {};
})();
</script>
