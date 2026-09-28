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
?>
<div class="omo-lexicon-editor generic-drawer-content" data-omo-lexicon-editor>
    <div class="omo-lexicon-editor__intro generic-stack generic-stack--compact">
        <h2 class="generic-card-title generic-card-title--large"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.title')) ?></h2>
        <p class="generic-description generic-description--compact"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.description')) ?></p>
    </div>

    <form class="omo-lexicon-editor__form generic-form-stack" data-omo-lexicon-form>
        <section class="generic-section generic-section--stack generic-form-section">
            <div class="generic-form-section__copy">
                <h3 class="generic-card-title"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.section.structure.title')) ?></h3>
                <p class="generic-help-text"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.section.structure.help')) ?></p>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <?php foreach (array('space' => $spaceTerm, 'circle' => $circleTerm, 'role' => $roleTerm, 'group' => $groupTerm) as $termKey => $term): ?>
                    <?php $termLabel = omoLexiconT('parameters.lexicon.term.' . $termKey . '.label'); ?>
                    <div class="generic-form-field">
                        <label class="generic-form-label" for="omo-lexicon-<?= omoLexiconEscape($termKey) ?>"><?= omoLexiconEscape($termLabel) ?></label>
                        <input
                            id="omo-lexicon-<?= omoLexiconEscape($termKey) ?>"
                            type="text"
                            class="generic-form-control"
                            name="<?= omoLexiconEscape($termKey) ?>_label"
                            value="<?= omoLexiconEscape($term['label']) ?>"
                            maxlength="80"
                            required
                        >
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section">
            <div class="generic-form-section__copy">
                <h3 class="generic-card-title"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.label')) ?></h3>
                <p class="generic-help-text"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.help')) ?></p>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <label class="generic-form-label" for="omo-lexicon-tension"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.label')) ?></label>
                    <input
                        id="omo-lexicon-tension"
                        type="text"
                        class="generic-form-control"
                        name="tension_label"
                        value="<?= omoLexiconEscape($tensionTerm['label']) ?>"
                        maxlength="80"
                        required
                    >
                </div>
                <div class="generic-form-field">
                    <label class="generic-form-label" for="omo-lexicon-tension-article"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.article')) ?></label>
                    <input
                        id="omo-lexicon-tension-article"
                        type="text"
                        class="generic-form-control"
                        name="tension_article"
                        aria-describedby="omo-lexicon-article-help"
                        value="<?= omoLexiconEscape($tensionTerm['article']) ?>"
                        maxlength="20"
                        required
                    >
                    <p id="omo-lexicon-article-help" class="generic-help-text"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.tension.article_help')) ?></p>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section">
            <div class="generic-form-section__copy">
                <h3 class="generic-card-title"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.label')) ?></h3>
                <p class="generic-help-text"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.help')) ?></p>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <label class="generic-form-label" for="omo-lexicon-admin"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.admin.label')) ?></label>
                    <input
                        id="omo-lexicon-admin"
                        type="text"
                        class="generic-form-control"
                        name="admin_label"
                        value="<?= omoLexiconEscape($adminTerm['label']) ?>"
                        maxlength="80"
                        required
                    >
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section">
            <div class="generic-form-section__copy">
                <h3 class="generic-card-title"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.section.properties')) ?></h3>
                <p id="omo-lexicon-types-help" class="generic-help-text"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.section.properties.help')) ?></p>
            </div>
            <div class="generic-stack">
                <?php foreach (\dbObject\Property::TYPES as $type): ?>
                    <div class="generic-soft-panel generic-setting-row">
                        <label class="generic-checkbox">
                            <input type="checkbox" name="<?= omoLexiconEscape($type) ?>_enabled" value="1" aria-describedby="omo-lexicon-types-help" <?= !empty($lexicon[$type]['enabled']) ? 'checked' : '' ?>>
                            <span class="generic-form-section__copy">
                                <span class="generic-form-label"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.term.' . $type . '.label')) ?></span>
                                <span class="generic-help-text"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.type.enabled')) ?></span>
                            </span>
                        </label>
                        <div class="generic-form-field">
                            <label class="generic-form-label" for="omo-lexicon-<?= omoLexiconEscape($type) ?>"><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.type.name')) ?></label>
                            <input id="omo-lexicon-<?= omoLexiconEscape($type) ?>" type="text" class="generic-form-control" name="<?= omoLexiconEscape($type) ?>_label" value="<?= omoLexiconEscape($lexicon[$type]['label']) ?>" maxlength="80" required>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <div class="omo-lexicon-editor__feedback generic-feedback" data-omo-lexicon-feedback aria-live="polite"></div>
        <div class="omo-lexicon-editor__actions generic-form-actions generic-form-actions--sticky generic-form-actions--stack-mobile">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-lexicon-reset><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.action.reset')) ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" data-omo-lexicon-save><?= omoLexiconEscape(omoLexiconT('parameters.lexicon.action.save')) ?></button>
        </div>
    </form>
</div>

<?= commonPageScriptTags('/omo/api/parameters/lexicon/index.js', [
    'defaults' => \dbObject\Organization::getInitialLexicon(),
    'message' => omoLexiconT('parameters.lexicon.status.error'),
    'parametersLexiconStatusSaved' => omoLexiconT('parameters.lexicon.status.saved'),
]) ?>
