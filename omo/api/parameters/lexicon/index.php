<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
$canEdit = false;

if ($organizationId > 0 && $organization->load($organizationId)) {
    $canEdit = $organization->canEdit();
}

if (!$organization instanceof \dbObject\Organization || (int)$organization->getId() <= 0) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoLexiconEscape(omoLexiconT('parameters.lexicon.error.organization')) . '</div>';
    exit;
}

if (!$canEdit) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoLexiconEscape(omoLexiconT('parameters.lexicon.error.access')) . '</div>';
    exit;
}

$lexicon = $organization->getLexicon();
$tensionTerm = $lexicon['tension'];
$adminTerm = $lexicon['admin'];
?>
<div class="omo-lexicon-editor" data-omo-lexicon-editor>
    <div class="omo-lexicon-editor__intro">
        <h2 class="generic-card-title generic-card-title--large"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.title')) ?></h2>
        <p><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.description')) ?></p>
    </div>

    <form class="omo-lexicon-editor__form" data-omo-lexicon-form>
        <section class="generic-section omo-lexicon-editor__section">
            <div class="generic-section__header">
                <h3 class="generic-card-title generic-card-title--medium"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.label')) ?></h3>
                <p><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.help')) ?></p>
            </div>
            <div class="omo-lexicon-editor__fields">
                <label class="omo-lexicon-editor__field">
                    <span><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.label')) ?></span>
                    <input
                        type="text"
                        class="generic-form-control"
                        name="tension_label"
                        value="<?= omoLexiconEscape($tensionTerm['label']) ?>"
                        maxlength="80"
                        required
                    >
                </label>
                <label class="omo-lexicon-editor__field">
                    <span><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.article')) ?></span>
                    <input
                        type="text"
                        class="generic-form-control"
                        name="tension_article"
                        value="<?= omoLexiconEscape($tensionTerm['article']) ?>"
                        maxlength="20"
                        required
                    >
                    <small><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.article_help')) ?></small>
                </label>
            </div>
        </section>

        <section class="generic-section omo-lexicon-editor__section">
            <div class="generic-section__header">
                <h3 class="generic-card-title generic-card-title--medium"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.label')) ?></h3>
                <p><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.help')) ?></p>
            </div>
            <label class="omo-lexicon-editor__field">
                <span><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.label')) ?></span>
                <input
                    type="text"
                    class="generic-form-control"
                    name="admin_label"
                    value="<?= omoLexiconEscape($adminTerm['label']) ?>"
                    maxlength="80"
                    required
                >
            </label>
        </section>

        <div class="omo-lexicon-editor__feedback" data-omo-lexicon-feedback aria-live="polite"></div>
        <div class="omo-lexicon-editor__actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-lexicon-reset><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.action.reset')) ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" data-omo-lexicon-save><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.action.save')) ?></button>
        </div>
    </form>
</div>

<style>
.omo-lexicon-editor {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 4px;
}

.omo-lexicon-editor__intro,
.omo-lexicon-editor__section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.omo-lexicon-editor__intro p,
.omo-lexicon-editor__section p,
.omo-lexicon-editor__field small {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.45;
}

.omo-lexicon-editor__fields {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(150px, 0.35fr);
    gap: 14px;
}

.omo-lexicon-editor__field {
    display: flex;
    flex-direction: column;
    gap: 7px;
    font-weight: 700;
}

.omo-lexicon-editor__field small {
    font-weight: 400;
}

.omo-lexicon-editor__feedback {
    min-height: 22px;
}

.omo-lexicon-editor__feedback.is-success {
    color: var(--color-success, #15803d);
}

.omo-lexicon-editor__feedback.is-error {
    color: var(--color-danger, #b91c1c);
}

.omo-lexicon-editor__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 620px) {
    .omo-lexicon-editor__fields {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function () {
    var root = document.querySelector('[data-omo-lexicon-editor]');
    if (!root || root.dataset.omoLexiconInitialized === '1') {
        return;
    }

    root.dataset.omoLexiconInitialized = '1';
    var form = root.querySelector('[data-omo-lexicon-form]');
    var feedback = root.querySelector('[data-omo-lexicon-feedback]');
    var saveButton = root.querySelector('[data-omo-lexicon-save]');
    var resetButton = root.querySelector('[data-omo-lexicon-reset]');
    var defaultValues = {
        tension_label: 'Tension',
        tension_article: 'une',
        admin_label: 'Admin'
    };

    function setFeedback(message, kind) {
        if (!feedback) {
            return;
        }

        feedback.textContent = String(message || '');
        feedback.className = 'omo-lexicon-editor__feedback' + (kind ? ' is-' + kind : '');
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            Object.keys(defaultValues).forEach(function (key) {
                var input = form ? form.elements[key] : null;
                if (input) {
                    input.value = defaultValues[key];
                }
            });
            setFeedback('', '');
        });
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (saveButton) {
            saveButton.disabled = true;
        }
        setFeedback('', '');

        fetch('/omo/api/parameters/lexicon/save.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== 'ok') {
                        throw new Error(payload && payload.message ? payload.message : <?= json_encode(omoLexiconT('parameters.lexicon.status.error'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>);
                    }
                    return payload;
                });
            })
            .then(function () {
                setFeedback(<?= json_encode(omoLexiconT('parameters.lexicon.status.saved'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, 'success');
                window.setTimeout(function () {
                    window.location.reload();
                }, 450);
            })
            .catch(function (error) {
                setFeedback(error && error.message ? error.message : <?= json_encode(omoLexiconT('parameters.lexicon.status.error'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, 'error');
                if (saveButton) {
                    saveButton.disabled = false;
                }
            });
    });
}());
</script>
