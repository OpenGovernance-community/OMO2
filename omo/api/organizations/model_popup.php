<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$sourceLang = array(
    'organization_model.action.submit' => array('text' => 'Continuer', 'context' => 'Submit button in the create-from-model popup.'),
    'organization_model.description' => array('text' => 'Choisissez un modèle public, puis renseignez les informations de votre nouvelle organisation.', 'context' => 'Description in the create-from-model popup.'),
    'organization_model.empty' => array('text' => 'Aucun modèle public n’est disponible pour le moment.', 'context' => 'Empty state in the create-from-model popup.'),
    'organization_model.field.model' => array('text' => 'Modèle', 'context' => 'Model selector label in the create-from-model popup.'),
    'organization_model.field.model_empty' => array('text' => 'Choisir un modèle...', 'context' => 'Empty model selector option in the create-from-model popup.'),
    'organization_model.loading' => array('text' => 'Ouverture du formulaire...', 'context' => 'Loading state in the create-from-model popup.'),
);
$lang = translationBundleInit('omo_organization_model_popup', omoGetTranslationLocale(), $sourceLang);
$models = Organization::getPublicModelCatalog();
?>
<div class="omo-model-popup generic-section generic-section--stack generic-section--roomy">
    <p class="generic-description"><?= htmlspecialchars(t('organization_model.description', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($models === array()): ?>
        <p class="generic-help-text"><?= htmlspecialchars(t('organization_model.empty', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <form id="omo-create-from-model-form" class="generic-stack generic-stack--roomy">
            <label class="omo-field">
                <span><?= htmlspecialchars(t('organization_model.field.model', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                <select class="generic-form-control" name="model_id" required>
                    <option value=""><?= htmlspecialchars(t('organization_model.field.model_empty', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach ($models as $model): ?>
                        <option value="<?= (int)$model['id'] ?>"><?= htmlspecialchars((string)$model['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
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
                    window.commonTopbarOpenModal('Créer à partir d’un modèle', url, 'fetch');
                    return;
                }
                window.location.href = url;
            });
        }());
        </script>
    <?php endif; ?>
</div>
