<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentShareLink;

if (!function_exists('omoDocumentShareFormatDateTime')) {
    function omoDocumentShareFormatDateTime($value)
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

if (!function_exists('omoDocumentShareFormatDateTimeInput')) {
    function omoDocumentShareFormatDateTimeInput($value)
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

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$document = new Document();

if (
    $documentId <= 0
    || !$document->load($documentId)
    || $document->isFolder()
    || !$document->supportsHtmlContent()
    || !$document->canEditInOrganizationContext((int)$document->get('IDorganization'))
) {
    ?>
    <div class="omo-share-popup omo-share-popup--error">Impossible de partager ce document.</div>
    <?php
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$shareLinks = DocumentShareLink::findAllForContext($organizationId, $documentId, false);
$hasExistingLinks = count($shareLinks) > 0;
$defaultLabel = trim((string)$document->get('title'));
if ($defaultLabel === '') {
    $defaultLabel = 'Document #' . $documentId;
}
$popupUrl = '/omo/api/documents/share_popup.php?id=' . rawurlencode((string)$documentId);
?>
<div
    class="omo-share-popup"
    id="omoDocumentSharePopupRoot"
    data-document-id="<?= (int)$documentId ?>"
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
    .omo-share-popup__hero,
    .omo-share-popup__section,
    .omo-share-popup__list,
    .omo-share-popup__form-panel,
    .omo-share-popup__cards,
    .omo-share-popup__form,
    .omo-share-popup__field {
        display: grid;
        gap: 12px;
    }
    .omo-share-popup__hero h2,
    .omo-share-popup__section-title {
        margin: 0;
    }
    .omo-share-popup__hero p,
    .omo-share-popup__section-text,
    .omo-share-popup__hint {
        margin: 0;
        color: var(--color-text-light, #6b7280);
        line-height: 1.5;
    }
    .omo-share-popup__list[hidden],
    .omo-share-popup__form-panel[hidden] {
        display: none;
    }
    .omo-share-popup__feedback {
        min-height: 20px;
        font-size: 13px;
        color: #b91c1c;
    }
    .omo-share-popup__feedback.is-success {
        color: #166534;
    }
    .omo-share-popup__card {
        --generic-section-gap: 10px;
    }
    .omo-share-popup__card-head,
    .omo-share-popup__card-actions,
    .omo-share-popup__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
        align-items: start;
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
        border: 1px solid var(--color-border, #e5e7eb);
    }
    .omo-share-popup__badge--expired {
        color: #dc2626;
    }
    .omo-share-popup__meta {
        display: grid;
        gap: 4px;
        font-size: 12px;
        color: var(--color-text-light, #6b7280);
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
    .omo-share-popup__field--full {
        grid-column: 1 / -1;
    }
    .omo-share-popup__label {
        font-size: 13px;
        font-weight: 700;
    }
    .omo-share-popup__check {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 10px;
        align-items: start;
    }
    </style>

    <div class="omo-share-popup__hero generic-hero-panel">
        <h2 class="generic-card-title generic-card-title--large">Partager ce document</h2>
        <p>Le lien ouvrira directement le contenu de <strong><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
    </div>

    <div id="omoDocumentSharePopupFeedback" class="omo-share-popup__feedback"></div>

    <div class="omo-share-popup__list" id="omoDocumentSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large">Liens existants</h3>
            <p class="omo-share-popup__section-text">Tu peux copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce document.</p>
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
                    data-dateexpiration="<?= htmlspecialchars(omoDocumentShareFormatDateTimeInput($expiresAt), ENT_QUOTES, 'UTF-8') ?>"
                    data-allow-live-follow="<?= $shareLink->allowsLiveFollow() ? '1' : '0' ?>"
                    data-has-password="<?= $shareLink->requiresPassword() ? '1' : '0' ?>"
                    data-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="omo-share-popup__card-head">
                        <div>
                            <h4 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <div class="omo-share-popup__meta">
                                <span>Cree le <?= htmlspecialchars(omoDocumentShareFormatDateTime($shareLink->get('datecreation')), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($expiresAt): ?>
                                    <span>Expire le <?= htmlspecialchars(omoDocumentShareFormatDateTime($expiresAt), ENT_QUOTES, 'UTF-8') ?></span>
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
                            <?php if ($shareLink->allowsLiveFollow()): ?>
                                <span class="omo-share-popup__badge">Temps reel</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="omo-share-popup__card-actions">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-share-copy="1">Copier</button>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-share-edit="1">Editer</button>
                        <button type="button" class="generic-action-button generic-action-button--danger" data-share-delete="1">Supprimer</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="omo-share-popup__actions">
            <button type="button" class="generic-action-button generic-action-button--main" id="omoDocumentSharePopupNewButton">Nouveau lien</button>
        </div>
    </div>

    <div class="omo-share-popup__form-panel" id="omoDocumentSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large" id="omoDocumentSharePopupFormTitle"><?= $hasExistingLinks ? 'Nouveau lien de partage' : 'Creer un lien de partage' ?></h3>
            <p class="omo-share-popup__section-text" id="omoDocumentSharePopupFormIntro"><?= $hasExistingLinks ? 'Configure un nouveau lien ou modifie un lien existant.' : 'Aucun lien n existe encore pour ce document. Creons le premier.' ?></p>
        </div>

        <form class="omo-share-popup__form" id="omoDocumentSharePopupForm">
            <input type="hidden" name="id" value="<?= (int)$documentId ?>">
            <input type="hidden" name="share_id" id="omoDocumentSharePopupShareId" value="">

            <div class="omo-share-popup__grid">
                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label" for="omoDocumentSharePopupLabel">Libelle interne</label>
                    <input class="generic-form-control" type="text" id="omoDocumentSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-share-popup__hint">Ce libelle sert a retrouver le lien.</div>
                </div>

                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label" for="omoDocumentSharePopupExpiration">Expiration</label>
                    <input class="generic-form-control" type="datetime-local" id="omoDocumentSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint">Laisse vide pour un lien sans date de fin.</div>
                </div>

                <div class="omo-share-popup__field omo-share-popup__field--full">
                    <label class="omo-share-popup__label" for="omoDocumentSharePopupPassword">Mot de passe optionnel</label>
                    <input class="generic-form-control" type="password" id="omoDocumentSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint" id="omoDocumentSharePopupPasswordHint">Si un mot de passe est defini, il sera demande a l ouverture du lien.</div>
                </div>

                <label class="omo-share-popup__check omo-share-popup__field--full" id="omoDocumentSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoDocumentSharePopupClearPassword">
                    <span>
                        <strong>Supprimer le mot de passe actuel</strong>
                        <span>Laisse le champ vide et coche ceci pour retirer la protection existante.</span>
                    </span>
                </label>
            </div>

            <div class="generic-soft-panel generic-soft-panel--stack">
                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_live_follow" id="omoDocumentSharePopupAllowLiveFollow">
                    <span>
                        <strong>Suivre en temps reel</strong>
                        <span>Le lien affichera aussi le brouillon temporaire pendant qu un utilisateur edite le document.</span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoDocumentSharePopupCancelButton">Retour a la liste</button>
                <?php endif; ?>
                <button type="submit" class="generic-action-button generic-action-button--main" id="omoDocumentSharePopupSubmit">Creer le lien</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const defaultLabel = <?= json_encode($defaultLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    window.omoDocumentSharePopupInit = function (popupRoot) {
        const root = popupRoot || document.getElementById('omoDocumentSharePopupRoot');
        if (!root || root.dataset.ready === '1') {
            return;
        }

        root.dataset.ready = '1';

        const hasExistingLinks = root.dataset.hasLinks === '1';
        const popupUrl = root.dataset.popupUrl || '/omo/api/documents/share_popup.php';
        const feedback = document.getElementById('omoDocumentSharePopupFeedback');
        const listSection = document.getElementById('omoDocumentSharePopupListSection');
        const formSection = document.getElementById('omoDocumentSharePopupFormSection');
        const form = document.getElementById('omoDocumentSharePopupForm');
        const title = document.getElementById('omoDocumentSharePopupFormTitle');
        const intro = document.getElementById('omoDocumentSharePopupFormIntro');
        const shareIdInput = document.getElementById('omoDocumentSharePopupShareId');
        const labelInput = document.getElementById('omoDocumentSharePopupLabel');
        const expirationInput = document.getElementById('omoDocumentSharePopupExpiration');
        const allowLiveFollow = document.getElementById('omoDocumentSharePopupAllowLiveFollow');
        const passwordHint = document.getElementById('omoDocumentSharePopupPasswordHint');
        const clearPasswordWrap = document.getElementById('omoDocumentSharePopupClearPasswordWrap');
        const clearPasswordInput = document.getElementById('omoDocumentSharePopupClearPassword');
        const submitButton = document.getElementById('omoDocumentSharePopupSubmit');

        const setFeedback = function (message, isSuccess) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.classList.toggle('is-success', Boolean(isSuccess));
        };

        const resetForm = function () {
            form.reset();
            shareIdInput.value = '';
            labelInput.value = defaultLabel;
            allowLiveFollow.checked = false;
            clearPasswordInput.checked = false;
            clearPasswordWrap.hidden = true;
            passwordHint.textContent = 'Si un mot de passe est defini, il sera demande a l ouverture du lien.';
            submitButton.textContent = 'Creer le lien';
            title.textContent = hasExistingLinks ? 'Nouveau lien de partage' : 'Creer un lien de partage';
            intro.textContent = hasExistingLinks
                ? 'Configure un nouveau lien ou modifie un lien existant.'
                : 'Aucun lien n existe encore pour ce document. Creons le premier.';
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
            allowLiveFollow.checked = card.dataset.allowLiveFollow === '1';
            clearPasswordWrap.hidden = card.dataset.hasPassword !== '1';
            passwordHint.textContent = 'Laisse vide pour conserver le mot de passe actuel, ou saisis-en un nouveau.';
            submitButton.textContent = 'Enregistrer';
            title.textContent = 'Modifier le lien de partage';
            intro.textContent = 'Mets a jour l expiration, le mot de passe ou le suivi temps reel.';

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
            window.omoDocumentSharePopupFlash = flashMessage ? {
                message: flashMessage,
                success: Boolean(isSuccess)
            } : null;

            const response = await fetch(popupUrl, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const html = await response.text();
            const container = root.parentNode;
            if (container) {
                container.innerHTML = html;
                if (typeof window.omoDocumentSharePopupInit === 'function') {
                    window.omoDocumentSharePopupInit(container.querySelector('#omoDocumentSharePopupRoot'));
                }
            }
        };

        if (window.omoDocumentSharePopupFlash && window.omoDocumentSharePopupFlash.message) {
            setFeedback(window.omoDocumentSharePopupFlash.message, window.omoDocumentSharePopupFlash.success);
            window.omoDocumentSharePopupFlash = null;
        }

        root.addEventListener('click', async function (event) {
            const newButton = event.target.closest('#omoDocumentSharePopupNewButton');
            if (newButton && root.contains(newButton)) {
                openFormForCreate();
                return;
            }

            const cancelButton = event.target.closest('#omoDocumentSharePopupCancelButton');
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

            const response = await fetch('/omo/api/documents/share_delete.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    id: String(root.dataset.documentId || ''),
                    share_id: String(shareId)
                }).toString()
            });

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || payload.status !== true) {
                setFeedback(payload && payload.message ? payload.message : 'Suppression impossible.', false);
                return;
            }

            refreshPopup('Lien supprime.', true);
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setFeedback('', false);

            const response = await fetch('/omo/api/documents/share_create.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || payload.status !== true) {
                setFeedback(payload && payload.message ? payload.message : 'Enregistrement impossible.', false);
                return;
            }

            refreshPopup(payload.message || 'Lien enregistre.', true);
        });
    };

    window.omoDocumentSharePopupInit();
})();
</script>
