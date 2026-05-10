<?php
require_once __DIR__ . '/context.php';

use dbObject\HolonShareLink;

if (!function_exists('omoSharePopupFormatDateTime')) {
    function omoSharePopupFormatDateTime($value)
    {
        if (!$value) {
            return '';
        }

        try {
            $date = $value instanceof DateTimeInterface ? $value : new DateTime((string)$value);
        } catch (Exception $exception) {
            return '';
        }

        return $date->format('d.m.Y H:i');
    }
}

if (!function_exists('omoSharePopupFormatDateTimeInput')) {
    function omoSharePopupFormatDateTimeInput($value)
    {
        if (!$value) {
            return '';
        }

        try {
            $date = $value instanceof DateTimeInterface ? $value : new DateTime((string)$value);
        } catch (Exception $exception) {
            return '';
        }

        return $date->format('Y-m-d\TH:i');
    }
}

$context = omoShareResolveManageContext($_GET);
if (empty($context['status'])) {
    ?>
    <div class="omo-share-popup omo-share-popup--error"><?= htmlspecialchars((string)($context['message'] ?? 'Erreur.'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organizationId = (int)$context['organizationId'];
$currentHolon = $context['currentHolon'];
$shareLinks = HolonShareLink::findAllForContext($organizationId, (int)$currentHolon->getId(), false);
$hasExistingLinks = count($shareLinks) > 0;
$defaultLabel = $currentHolon->getDisplayName();
$popupUrl = 'api/shares/popup.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . rawurlencode((string)$currentHolon->getId());
?>
<div
    class="omo-share-popup"
    id="omoSharePopupRoot"
    data-oid="<?= (int)$organizationId ?>"
    data-cid="<?= (int)$currentHolon->getId() ?>"
    data-popup-url="<?= htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-has-links="<?= $hasExistingLinks ? '1' : '0' ?>"
>
    <style>
    .omo-share-popup {
        display: grid;
        gap: 18px;
        color: var(--color-text, #1f2937);
    }

    .omo-share-popup--error {
        padding: 18px;
        border-radius: 16px;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-share-popup__hero {
        --generic-hero-gap: 8px;
        --generic-hero-padding: 18px;
        --generic-hero-radius: 18px;
    }

    .omo-share-popup__hero h2,
    .omo-share-popup__section-title {
        margin: 0;
    }

    .omo-share-popup__hero p,
    .omo-share-popup__section-text {
        margin: 0;
        color: var(--color-text-light, #6b7280);
        line-height: 1.5;
    }

    .omo-share-popup__feedback {
        min-height: 20px;
        font-size: 13px;
        color: #b91c1c;
    }

    .omo-share-popup__feedback.is-success {
        color: #166534;
    }

    .omo-share-popup__section,
    .omo-share-popup__list,
    .omo-share-popup__form-panel {
        display: grid;
        gap: 14px;
    }

    .omo-share-popup__list[hidden],
    .omo-share-popup__form-panel[hidden] {
        display: none;
    }

    .omo-share-popup__cards {
        display: grid;
        gap: 12px;
    }

    .omo-share-popup__card {
        --generic-section-gap: 10px;
        --generic-section-padding-block: 16px;
        --generic-section-padding-inline: 16px;
        --generic-section-radius: 16px;
        --generic-section-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
    }

    .omo-share-popup__card-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: start;
        flex-wrap: wrap;
    }

.omo-share-popup__card-title {
        margin: 0;
    }

    .omo-share-popup__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .omo-share-popup__badge {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text, #1f2937);
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-share-popup__badge--expired {
        background: color-mix(in srgb, #dc2626 12%, var(--color-surface, #ffffff));
        color: #dc2626;
        border-color: color-mix(in srgb, #dc2626 30%, var(--color-border, #e5e7eb));
    }

    .omo-share-popup__meta {
        display: grid;
        gap: 4px;
        font-size: 12px;
        color: var(--color-text-light, #6b7280);
    }

    .omo-share-popup__card-actions,
    .omo-share-popup__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .omo-share-popup__form {
        display: grid;
        gap: 16px;
    }

    .omo-share-popup__grid {
        display: grid;
        gap: 14px;
    }

    @media (min-width: 760px) {
        .omo-share-popup__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .omo-share-popup__field {
        display: grid;
        gap: 8px;
    }

    .omo-share-popup__field--full {
        grid-column: 1 / -1;
    }

    .omo-share-popup__label {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text, #1f2937);
    }

    .omo-share-popup__input {
        --generic-form-control-padding-block: 10px;
        --generic-form-control-border: var(--color-border-strong, #cbd5e1);
        --generic-form-control-background: var(--color-surface, #ffffff);
        --generic-form-control-background-focus: var(--color-surface, #ffffff);
    }

    .omo-share-popup__hint {
        font-size: 12px;
        color: var(--color-text-light, #6b7280);
        line-height: 1.45;
    }

    .omo-share-popup__permissions {
        --generic-soft-panel-border: var(--color-border, #e5e7eb);
        --generic-soft-panel-background: var(--color-surface-alt, #f0f2f5);
        --generic-soft-panel-gap: 12px;
        --generic-soft-panel-padding-block: 16px;
        --generic-soft-panel-padding-inline: 16px;
        --generic-soft-panel-radius: 16px;
    }

    .omo-share-popup__check {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 10px;
        align-items: start;
    }

    .omo-share-popup__check input {
        margin-top: 3px;
    }

    .omo-share-popup__check strong {
        display: block;
        margin-bottom: 4px;
        font-size: 14px;
    }
    </style>

    <div class="omo-share-popup__hero generic-hero-panel">
        <h2 class="generic-card-title generic-card-title--large">Partager ce contexte</h2>
        <p>Le lien demarrera sur <strong><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></strong> et pourra etre transmis a des personnes externes.</p>
    </div>

    <div id="omoSharePopupFeedback" class="omo-share-popup__feedback"></div>

    <div class="omo-share-popup__list" id="omoSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large">Liens existants</h3>
            <p class="omo-share-popup__section-text">Tu peux copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce holon.</p>
        </div>

        <div class="omo-share-popup__cards">
            <?php foreach ($shareLinks as $shareLink): ?>
                <?php
                $shareUrl = $shareLink->buildShareUrl();
                $shareLabel = trim((string)$shareLink->get('label'));
                if ($shareLabel === '') {
                    $shareLabel = $defaultLabel;
                }
                $expiresAt = $shareLink->get('dateexpiration');
                ?>
                <div
                    class="omo-share-popup__card generic-section generic-section--stack"
                    data-share-card="1"
                    data-share-id="<?= (int)$shareLink->getId() ?>"
                    data-label="<?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?>"
                    data-dateexpiration="<?= htmlspecialchars(omoSharePopupFormatDateTimeInput($expiresAt), ENT_QUOTES, 'UTF-8') ?>"
                    data-allow-structure="<?= $shareLink->allowsStructure() ? '1' : '0' ?>"
                    data-allow-people="<?= $shareLink->allowsPeople() ? '1' : '0' ?>"
                    data-allow-people-detail="<?= $shareLink->allowsPeopleDetail() ? '1' : '0' ?>"
                    data-has-password="<?= $shareLink->requiresPassword() ? '1' : '0' ?>"
                    data-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="omo-share-popup__card-head">
                        <div>
                            <h4 class="omo-share-popup__card-title generic-card-title generic-card-title--medium"><?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <div class="omo-share-popup__meta">
                                <span>Cree le <?= htmlspecialchars(omoSharePopupFormatDateTime($shareLink->get('datecreation')), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($expiresAt): ?>
                                    <span>Expire le <?= htmlspecialchars(omoSharePopupFormatDateTime($expiresAt), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span>Sans expiration</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="omo-share-popup__badges">
                            <?php if ($shareLink->isExpired()): ?>
                                <span class="omo-share-popup__badge omo-share-popup__badge--expired">Expire</span>
                            <?php endif; ?>
                            <?php if ($shareLink->requiresPassword()): ?>
                                <span class="omo-share-popup__badge">Mot de passe</span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsStructure()): ?>
                                <span class="omo-share-popup__badge">Structure</span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsPeople()): ?>
                                <span class="omo-share-popup__badge">Personnes</span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsPeopleDetail()): ?>
                                <span class="omo-share-popup__badge">Detail</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="omo-share-popup__card-actions">
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" data-share-copy="1">Copier</button>
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" data-share-edit="1">Editer</button>
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--danger" data-share-delete="1">Supprimer</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="omo-share-popup__actions">
            <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--main" id="omoSharePopupNewButton">Nouveau lien</button>
        </div>
    </div>

    <div class="omo-share-popup__form-panel" id="omoSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large" id="omoSharePopupFormTitle"><?= $hasExistingLinks ? 'Nouveau lien de partage' : 'Creer un lien de partage' ?></h3>
            <p class="omo-share-popup__section-text" id="omoSharePopupFormIntro"><?= $hasExistingLinks ? 'Configure un nouveau lien ou modifie un lien existant.' : 'Aucun lien n existe encore pour ce holon. Creons le premier.' ?></p>
        </div>

        <form class="omo-share-popup__form" id="omoSharePopupForm">
            <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
            <input type="hidden" name="cid" value="<?= (int)$currentHolon->getId() ?>">
            <input type="hidden" name="share_id" id="omoSharePopupShareId" value="">

            <div class="omo-share-popup__grid">
                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label" for="omoSharePopupLabel">Libelle interne</label>
                    <input class="omo-share-popup__input generic-form-control" type="text" id="omoSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-share-popup__hint">Ce libelle est interne et servira a retrouver le lien.</div>
                </div>

                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label" for="omoSharePopupExpiration">Expiration</label>
                    <input class="omo-share-popup__input generic-form-control" type="datetime-local" id="omoSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint">Laisse vide pour un lien sans date de fin.</div>
                </div>

                <div class="omo-share-popup__field omo-share-popup__field--full">
                    <label class="omo-share-popup__label" for="omoSharePopupPassword">Mot de passe optionnel</label>
                    <input class="omo-share-popup__input generic-form-control" type="password" id="omoSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint" id="omoSharePopupPasswordHint">Si un mot de passe est defini, il sera demande a l ouverture du lien.</div>
                </div>

                <label class="omo-share-popup__check omo-share-popup__field--full" id="omoSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoSharePopupClearPassword">
                    <span>
                        <strong>Supprimer le mot de passe actuel</strong>
                        <span>Laisse le champ mot de passe vide et coche ceci pour retirer la protection existante.</span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__permissions generic-soft-panel generic-soft-panel--stack">
                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_structure" id="omoSharePopupAllowStructure" checked>
                    <span>
                        <strong>Voir la structure</strong>
                        <span>Autorise l affichage de la structure et du detail des cercles et roles.</span>
                    </span>
                </label>

                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_people" id="omoSharePopupAllowPeople">
                    <span>
                        <strong>Voir les personnes</strong>
                        <span>Autorise l affichage des membres visibles dans le contexte partage.</span>
                    </span>
                </label>

                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_people_detail" id="omoSharePopupAllowPeopleDetail">
                    <span>
                        <strong>Voir le detail des personnes</strong>
                        <span>Autorise l ouverture de la popup detail d une personne visible.</span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" id="omoSharePopupCancelButton">Retour a la liste</button>
                <?php endif; ?>
                <button type="submit" class="omo-share-popup__button generic-action-button generic-action-button--main" id="omoSharePopupSubmit">Creer le lien</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const defaultLabel = <?= json_encode($defaultLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    window.omoSharePopupInit = function (popupRoot) {
        const root = popupRoot || document.getElementById('omoSharePopupRoot');
        if (!root || root.dataset.ready === '1') {
            return;
        }

        root.dataset.ready = '1';

        const hasExistingLinks = root.dataset.hasLinks === '1';
        const popupUrl = root.dataset.popupUrl || 'api/shares/popup.php';
        const feedback = document.getElementById('omoSharePopupFeedback');
        const listSection = document.getElementById('omoSharePopupListSection');
        const formSection = document.getElementById('omoSharePopupFormSection');
        const form = document.getElementById('omoSharePopupForm');
        const title = document.getElementById('omoSharePopupFormTitle');
        const intro = document.getElementById('omoSharePopupFormIntro');
        const shareIdInput = document.getElementById('omoSharePopupShareId');
        const labelInput = document.getElementById('omoSharePopupLabel');
        const expirationInput = document.getElementById('omoSharePopupExpiration');
        const passwordHint = document.getElementById('omoSharePopupPasswordHint');
        const clearPasswordWrap = document.getElementById('omoSharePopupClearPasswordWrap');
        const clearPasswordInput = document.getElementById('omoSharePopupClearPassword');
        const allowStructure = document.getElementById('omoSharePopupAllowStructure');
        const allowPeople = document.getElementById('omoSharePopupAllowPeople');
        const allowPeopleDetail = document.getElementById('omoSharePopupAllowPeopleDetail');
        const submitButton = document.getElementById('omoSharePopupSubmit');

        const resolveUrl = function (url) {
            if (typeof window.omoResolveAppUrl === 'function') {
                return window.omoResolveAppUrl(url);
            }

            return url;
        };

        const setFeedback = function (message, isSuccess) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.classList.toggle('is-success', Boolean(isSuccess));
        };

        const syncPermissionDependencies = function () {
            if (!allowPeople || !allowPeopleDetail) {
                return;
            }

            if (!allowPeople.checked) {
                allowPeopleDetail.checked = false;
            }

            allowPeopleDetail.disabled = !allowPeople.checked;
        };

        const resetForm = function () {
            form.reset();
            shareIdInput.value = '';
            labelInput.value = defaultLabel;
            clearPasswordInput.checked = false;
            clearPasswordWrap.hidden = true;
            allowStructure.checked = true;
            allowPeople.checked = false;
            allowPeopleDetail.checked = false;
            passwordHint.textContent = 'Si un mot de passe est defini, il sera demande a l ouverture du lien.';
            submitButton.textContent = 'Creer le lien';
            title.textContent = hasExistingLinks ? 'Nouveau lien de partage' : 'Creer un lien de partage';
            intro.textContent = hasExistingLinks
                ? 'Configure un nouveau lien ou modifie un lien existant.'
                : 'Aucun lien n existe encore pour ce holon. Creons le premier.';
            syncPermissionDependencies();
        };

        const openFormForCreate = function () {
            resetForm();
            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
        };

        const openFormForEdit = function (card) {
            if (!card) {
                return;
            }

            resetForm();
            shareIdInput.value = card.dataset.shareId || '';
            labelInput.value = card.dataset.label || defaultLabel;
            expirationInput.value = card.dataset.dateexpiration || '';
            allowStructure.checked = card.dataset.allowStructure === '1';
            allowPeople.checked = card.dataset.allowPeople === '1';
            allowPeopleDetail.checked = card.dataset.allowPeopleDetail === '1';
            clearPasswordWrap.hidden = card.dataset.hasPassword !== '1';
            passwordHint.textContent = 'Laisse vide pour conserver le mot de passe actuel, ou saisis-en un nouveau.';
            submitButton.textContent = 'Enregistrer';
            title.textContent = 'Modifier le lien de partage';
            intro.textContent = 'Mets a jour les droits, l expiration ou le mot de passe de ce lien.';
            syncPermissionDependencies();

            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
        };

        const showList = function () {
            if (!hasExistingLinks) {
                return;
            }

            if (listSection) {
                listSection.hidden = false;
            }
            if (formSection) {
                formSection.hidden = true;
            }
            setFeedback('', false);
        };

        const refreshPopup = async function (flashMessage, isSuccess) {
            window.omoSharePopupFlash = flashMessage ? {
                message: flashMessage,
                success: Boolean(isSuccess)
            } : null;

            const response = await fetch(resolveUrl(popupUrl), {
                method: 'GET',
                credentials: 'same-origin'
            });

            const html = await response.text();
            const container = root.parentNode;
            if (container) {
                container.innerHTML = html;
                if (typeof window.omoSharePopupInit === 'function') {
                    window.omoSharePopupInit(container.querySelector('#omoSharePopupRoot'));
                }
            }
        };

        if (window.omoSharePopupFlash && window.omoSharePopupFlash.message) {
            setFeedback(window.omoSharePopupFlash.message, window.omoSharePopupFlash.success);
            window.omoSharePopupFlash = null;
        }

        syncPermissionDependencies();
        if (allowPeople) {
            allowPeople.addEventListener('change', syncPermissionDependencies);
        }

        root.addEventListener('click', async function (event) {
            const newButton = event.target.closest('#omoSharePopupNewButton');
            if (newButton && root.contains(newButton)) {
                openFormForCreate();
                return;
            }

            const cancelButton = event.target.closest('#omoSharePopupCancelButton');
            if (cancelButton && root.contains(cancelButton)) {
                showList();
                return;
            }

            const copyButton = event.target.closest('[data-share-copy="1"]');
            if (copyButton && root.contains(copyButton)) {
                const card = copyButton.closest('[data-share-card="1"]');
                const url = card ? (card.dataset.url || '') : '';

                if (!url) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(url);
                    setFeedback('Lien copie.', true);
                } catch (error) {
                    setFeedback('Copie impossible automatiquement.', false);
                }
                return;
            }

            const editButton = event.target.closest('[data-share-edit="1"]');
            if (editButton && root.contains(editButton)) {
                openFormForEdit(editButton.closest('[data-share-card="1"]'));
                return;
            }

            const deleteButton = event.target.closest('[data-share-delete="1"]');
            if (!deleteButton || !root.contains(deleteButton)) {
                return;
            }

            const card = deleteButton.closest('[data-share-card="1"]');
            const shareId = card ? Number(card.dataset.shareId || 0) : 0;
            const label = card ? (card.dataset.label || defaultLabel) : defaultLabel;

            if (!shareId) {
                return;
            }

            if (!window.confirm('Supprimer le lien "' + label + '" ?')) {
                return;
            }

            setFeedback('', false);
            deleteButton.disabled = true;

            try {
                const formData = new FormData();
                formData.append('oid', root.dataset.oid || '');
                formData.append('cid', root.dataset.cid || '');
                formData.append('share_id', String(shareId));

                const response = await fetch(resolveUrl('api/shares/delete.php'), {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || !payload.status) {
                    throw new Error(payload && payload.message ? payload.message : 'Erreur de suppression.');
                }

                await refreshPopup(payload.message || 'Lien supprime.', true);
            } catch (error) {
                setFeedback(error && error.message ? error.message : 'Erreur de suppression.', false);
                deleteButton.disabled = false;
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            setFeedback('', false);
            submitButton.disabled = true;
            syncPermissionDependencies();

            try {
                const response = await fetch(resolveUrl('api/shares/create.php'), {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || !payload.status) {
                    throw new Error(payload && payload.message ? payload.message : 'Erreur de sauvegarde.');
                }

                await refreshPopup(payload.message || 'Lien enregistre.', true);
            } catch (error) {
                setFeedback(error && error.message ? error.message : 'Erreur de sauvegarde.', false);
                submitButton.disabled = false;
            }
        });
    };

    window.omoSharePopupInit(document.getElementById('omoSharePopupRoot'));
})();
</script>
