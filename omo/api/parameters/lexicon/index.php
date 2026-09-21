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
$spaceTerm = $lexicon['space'];
$circleTerm = $lexicon['circle'];
$roleTerm = $lexicon['role'];
$groupTerm = $lexicon['group'];
$tensionTerm = $lexicon['tension'];
$adminTerm = $lexicon['admin'];
$lexiconHelp = static function ($label, $text): string {
    return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
        . '<summary aria-label="' . omoLexiconEscape($label) . '">?</summary>'
        . '<div class="generic-context-help__content">' . omoLexiconEscape($text) . '</div></details>';
};
?>
<div class="omo-lexicon-editor generic-stack generic-stack--compact" data-omo-lexicon-editor>
    <div class="omo-lexicon-editor__intro generic-stack generic-stack--compact">
        <h2 class="generic-card-title generic-card-title--large"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.title')) ?></h2>
        <p class="generic-description generic-description--compact"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.description')) ?></p>
    </div>

    <form class="omo-lexicon-editor__form generic-form-stack generic-form-stack--compact" data-omo-lexicon-form>
        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.section.structure.title')) ?></h3>
                <?= $lexiconHelp(omoLexiconT('parameters.lexicon.section.structure.title'), omoLexiconT('parameters.lexicon.section.structure.help')) ?>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <?php foreach (array('space' => $spaceTerm, 'circle' => $circleTerm, 'role' => $roleTerm, 'group' => $groupTerm) as $termKey => $term): ?>
                    <?php $termLabel = omoLexiconT('parameters.lexicon.term.' . $termKey . '.label'); ?>
                    <div class="generic-form-field">
                        <label class="generic-form-label" for="omo-lexicon-<?= omoLexiconEscape($termKey) ?>"><?= omoLexiconEscape($termLabel) ?></label>
                        <input
                            id="omo-lexicon-<?= omoLexiconEscape($termKey) ?>"
                            type="text"
                            class="generic-form-control generic-form-control--compact"
                            name="<?= omoLexiconEscape($termKey) ?>_label"
                            value="<?= omoLexiconEscape($term['label']) ?>"
                            maxlength="80"
                            required
                        >
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.label')) ?></h3>
                <?= $lexiconHelp(omoLexiconT('parameters.lexicon.term.tension.label'), omoLexiconT('parameters.lexicon.term.tension.help')) ?>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <label class="generic-form-label" for="omo-lexicon-tension"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.label')) ?></label>
                    <input
                        id="omo-lexicon-tension"
                        type="text"
                        class="generic-form-control generic-form-control--compact"
                        name="tension_label"
                        value="<?= omoLexiconEscape($tensionTerm['label']) ?>"
                        maxlength="80"
                        required
                    >
                </div>
                <div class="generic-form-field">
                    <div class="generic-inline-help">
                        <label class="generic-form-label" for="omo-lexicon-tension-article"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.article')) ?></label>
                        <?= $lexiconHelp(omoLexiconT('parameters.lexicon.term.tension.article'), omoLexiconT('parameters.lexicon.term.tension.article_help')) ?>
                    </div>
                    <input
                        id="omo-lexicon-tension-article"
                        type="text"
                        class="generic-form-control generic-form-control--compact"
                        name="tension_article"
                        value="<?= omoLexiconEscape($tensionTerm['article']) ?>"
                        maxlength="20"
                        required
                    >
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.label')) ?></h3>
                <?= $lexiconHelp(omoLexiconT('parameters.lexicon.term.admin.label'), omoLexiconT('parameters.lexicon.term.admin.help')) ?>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <label class="generic-form-label" for="omo-lexicon-admin"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.label')) ?></label>
                    <input
                        id="omo-lexicon-admin"
                        type="text"
                        class="generic-form-control generic-form-control--compact"
                        name="admin_label"
                        value="<?= omoLexiconEscape($adminTerm['label']) ?>"
                        maxlength="80"
                        required
                    >
                </div>
            </div>
        </section>

        <div class="omo-lexicon-editor__feedback generic-feedback" data-omo-lexicon-feedback aria-live="polite"></div>
        <div class="omo-lexicon-editor__actions generic-form-actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-lexicon-reset><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.action.reset')) ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" data-omo-lexicon-save><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.action.save')) ?></button>
        </div>
    </form>
</div>

<script>
(function () {
    var root = document.querySelector('[data-omo-lexicon-editor]');
    if (!root || root.dataset.omoLexiconInitialized === '1') {
        return;
    }

    root.dataset.omoLexiconInitialized = '1';
    if (typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(root);
    }
    var form = root.querySelector('[data-omo-lexicon-form]');
    var feedback = root.querySelector('[data-omo-lexicon-feedback]');
    var saveButton = root.querySelector('[data-omo-lexicon-save]');
    var resetButton = root.querySelector('[data-omo-lexicon-reset]');
    var defaultValues = {
        space_label: 'Espace',
        circle_label: 'Cercle',
        role_label: 'Rôle',
        group_label: 'Groupe',
        tension_label: 'Tension',
        tension_article: 'une',
        admin_label: 'Admin'
    };

    function setFeedback(message, kind) {
        if (!feedback) {
            return;
        }

        feedback.textContent = String(message || '');
        feedback.className = 'omo-lexicon-editor__feedback generic-feedback' + (kind ? ' is-' + kind : '');
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
