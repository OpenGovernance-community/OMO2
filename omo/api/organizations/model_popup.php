<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$sourceLang = array(
    'organization_model.action.submit' => array('text' => 'Continuer', 'context' => 'Submit button in the create-from-model popup.'),
    'organization_model.description' => array('text' => 'Choisissez un modèle public, puis renseignez les informations de votre nouvelle organisation.', 'context' => 'Description in the create-from-model popup.'),
    'organization_model.empty' => array('text' => 'Aucun modèle public n’est disponible pour le moment.', 'context' => 'Empty state in the create-from-model popup.'),
    'organization_model.field.model' => array('text' => 'Modèle', 'context' => 'Model selector label in the create-from-model popup.'),
    'organization_model.learn_more' => array('text' => 'En savoir plus', 'context' => 'Link opening more information about this organization model in a new tab.'),
    'organization_model.modal_title' => array('text' => 'Créer à partir d’un modèle', 'context' => 'Title of the organization form opened after choosing a public model.'),
    'organization_model.loading' => array('text' => 'Ouverture du formulaire...', 'context' => 'Loading state in the create-from-model popup.'),
    'organization_model.error.login' => array('text' => 'Connexion requise.', 'context' => 'Authentication error in the create-from-model popup.'),
);
$lang = translationBundleInit('omo_organization_model_popup', omoGetTranslationLocale(), $sourceLang);
if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo htmlspecialchars(t('organization_model.error.login', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8');
    exit;
}
$models = Organization::getPublicModelCatalog();
?>
<div class="omo-model-popup generic-section generic-section--stack generic-section--roomy">
    <p class="generic-description"><?= htmlspecialchars(t('organization_model.description', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($models === array()): ?>
        <p class="generic-help-text"><?= htmlspecialchars(t('organization_model.empty', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <form id="omo-create-from-model-form" class="generic-stack generic-stack--roomy">
            <fieldset class="generic-fieldset">
                <legend class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('organization_model.field.model', array(), $lang, $sourceLang)) ?></legend>
                <div class="generic-fieldset__body">
                    <?php foreach ($models as $model): ?>
                        <?php
                        $modelName = (string)$model['name'];
                        $modelColor = preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', (string)$model['color'])
                            ? (string)$model['color'] : 'var(--color-primary, #2563eb)';
                        $modelInitial = mb_strtoupper(mb_substr($modelName, 0, 1, 'UTF-8'), 'UTF-8');
                        ?>
                        <label class="generic-choice-card generic-choice-card--media-row" style="--param-choice-card-media-color: <?= omoApiEscape($modelColor) ?>; --param-choice-card-accent-color: <?= omoApiEscape($modelColor) ?>; --param-choice-card-accent-width: 4px;">
                            <span class="generic-choice-card__media" aria-hidden="true">
                                <?php if ($model['banner'] !== '' && $model['banner'] !== 'newimage'): ?>
                                    <img class="generic-choice-card__banner" src="<?= omoApiEscape($model['banner']) ?>" alt="" loading="lazy" decoding="async" onerror="this.hidden = true;">
                                <?php endif; ?>
                                <span class="generic-choice-card__logo">
                                    <?= omoApiEscape($modelInitial) ?>
                                    <?php if ($model['logo'] !== '' && $model['logo'] !== 'newimage'): ?>
                                        <img src="<?= omoApiEscape($model['logo']) ?>" alt="" loading="lazy" decoding="async" onerror="this.hidden = true;">
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="generic-choice-card__copy">
                                <span class="generic-card-title generic-card-title--medium"><?= omoApiEscape($modelName) ?></span>
                                <?php if ($model['description'] !== ''): ?>
                                    <span class="generic-description generic-description--small"><?= nl2br(omoApiEscape($model['description']), false) ?></span>
                                <?php endif; ?>
                                <?php if ($model['url'] !== ''): ?>
                                    <span><a class="generic-action-button generic-action-button--link" href="<?= omoApiEscape($model['url']) ?>" target="_blank" rel="noopener noreferrer"><?= omoApiEscape(t('organization_model.learn_more', array(), $lang, $sourceLang)) ?></a></span>
                                <?php endif; ?>
                            </span>
                            <input class="generic-choice-card__control" type="radio" name="model_id" value="<?= (int)$model['id'] ?>" required>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div id="omo-create-from-model-feedback" class="generic-help-text" role="status"></div>
            <div class="generic-actions generic-actions--end">
                <button class="generic-action-button generic-action-button--primary" type="submit"><?= htmlspecialchars(t('organization_model.action.submit', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
        <script>
        (function () {
            var form = document.getElementById('omo-create-from-model-form');
            var feedback = document.getElementById('omo-create-from-model-feedback');
            if (!form) { return; }
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = form.querySelector('button[type="submit"]');
                if (button) { button.disabled = true; }
                feedback.textContent = <?= json_encode(t('organization_model.loading', array(), $lang, $sourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                var modelId = String(new FormData(form).get('model_id') || '');
                var url = '/popup/organization_create.php?model_id=' + encodeURIComponent(modelId);
                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(<?= json_encode(t('organization_model.modal_title', array(), $lang, $sourceLang), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, url, 'fetch');
                    return;
                }
                window.location.href = url;
            });
        }());
        </script>
    <?php endif; ?>
</div>
