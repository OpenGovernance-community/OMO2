<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$sourceLang = array(
    'organization_import.action.cancel' => array('text' => 'Annuler', 'context' => 'Cancel button in the organization import popup.'),
    'organization_import.action.submit' => array('text' => 'Créer et importer', 'context' => 'Submit button in the organization import popup.'),
    'organization_import.error.auth' => array('text' => 'Connexion requise.', 'context' => 'Authentication message in the organization import popup.'),
    'organization_import.error.file' => array('text' => 'Choisissez un fichier JSON avant de continuer.', 'context' => 'Validation message when no import file is selected.'),
    'organization_import.error.member_invitation_email_choice' => array('text' => 'Choisissez explicitement le traitement des e-mails d’invitation aux membres.', 'context' => 'Validation message when the member invitation email choice is not selected.'),
    'organization_import.error.generic' => array('text' => 'Impossible d importer la nouvelle organisation.', 'context' => 'Fallback error message for the organization import popup.'),
    'organization_import.field.file' => array('text' => 'Export JSON OMO 1', 'context' => 'File input label in the organization import popup.'),
    'organization_import.field.name' => array('text' => 'Nom de la nouvelle organisation', 'context' => 'Organization name label in the organization import popup.'),
    'organization_import.field.name_hint' => array('text' => 'Laissez vide pour reprendre le nom de l export.', 'context' => 'Organization name hint in the organization import popup.'),
    'organization_import.field.template' => array('text' => 'Modèle d’organisation de référence', 'context' => 'Organization template selector label in the organization import popup.'),
    'organization_import.field.template_empty' => array('text' => 'Conserver les templates du fichier importe', 'context' => 'Empty option in the organization template selector.'),
    'organization_import.field.template_hint' => array('text' => 'Associez ensuite les rôles structurels importés aux modèles de cette organisation.', 'context' => 'Hint below the organization template selector.'),
    'organization_import.mapping.empty' => array('text' => 'Conserver le template importe', 'context' => 'Empty option in a template mapping selector.'),
    'organization_import.mapping.exclude' => array('text' => 'Ne pas importer ce template, ses instances ni leurs descendants', 'context' => 'Option excluding an imported holon template, its instances and their descendants.'),
    'organization_import.mapping.help' => array('text' => 'Pour chaque template OMO 1, conservez sa définition, remplacez-la par un template du modèle ou excluez-la. Les propriétés à faire correspondre apparaissent sous le template choisi.', 'context' => 'Help text for template mappings.'),
    'organization_import.mapping.duplicate' => array('text' => 'Un modèle de référence ne peut être associé qu’à un seul modèle importé.', 'context' => 'Validation error when a target template is mapped twice.'),
    'organization_import.mapping.none' => array('text' => 'Aucun template structurel a associer dans ce fichier.', 'context' => 'Empty template mapping state.'),
    'organization_import.mapping.title' => array('text' => 'Correspondance des templates structurels', 'context' => 'Title of template mapping section.'),
    'organization_import.property_mapping.duplicate' => array('text' => 'Une propriété du modèle ne peut remplacer qu’une seule propriété importée.', 'context' => 'Validation error when a target property is mapped twice.'),
    'organization_import.property_mapping.empty' => array('text' => 'Conserver la propriété importée', 'context' => 'Empty option in a property mapping selector.'),
    'organization_import.property_mapping.exclude' => array('text' => 'Ne pas importer cette propriété', 'context' => 'Option excluding a source property from a mapped template and its instances.'),
    'organization_import.property_mapping.help' => array('text' => 'Confirmez les équivalences de propriétés lorsque leurs noms diffèrent. Les listes d’autorités compatibles sont proposées automatiquement.', 'context' => 'Help text for property mappings during organization import.'),
    'organization_import.property_mapping.none' => array('text' => 'Aucune propriété à faire correspondre pour les modèles sélectionnés.', 'context' => 'Empty property mapping state.'),
    'organization_import.property_mapping.title' => array('text' => 'Correspondance des propriétés', 'context' => 'Title of property mapping section.'),
    'organization_import.field.sections' => array('text' => 'Contenu à importer', 'context' => 'Section picker legend in the organization import popup.'),
    'organization_import.field.member_invitation_email_choice' => array('text' => 'E-mails d’invitation aux membres importés', 'context' => 'Required selector controlling whether imported members receive invitation emails.'),
    'organization_import.field.member_invitation_email_choice_empty' => array('text' => 'Choisissez...', 'context' => 'Empty option in the required member invitation email selector.'),
    'organization_import.field.member_invitation_email_choice_send' => array('text' => 'Envoyer maintenant les e-mails d’invitation', 'context' => 'Option sending invitation emails to imported members.'),
    'organization_import.field.member_invitation_email_choice_skip' => array('text' => 'Ne pas envoyer les e-mails d’invitation', 'context' => 'Option not sending invitation emails to imported members.'),
    'organization_import.field.member_invitation_email_choice_hint' => array('text' => 'Ce choix est obligatoire afin de confirmer le traitement des invitations des membres importés.', 'context' => 'Help text for the required member invitation email selector.'),
    'organization_import.help' => array('text' => 'Cette action crée une nouvelle organisation. La structure est toujours importée. Les tâches OMO 1 deviennent des projets enfants et les anciennes listes récurrentes deviennent des tâches récurrentes rattachées directement à leurs holons.', 'context' => 'Help text in the organization import popup.'),
    'organization_import.module.checklists' => array('text' => 'Tâches récurrentes', 'context' => 'Recurring tasks module label in the organization import popup.'),
    'organization_import.loading' => array('text' => 'Import en cours...', 'context' => 'Loading label shown during organization import.'),
    'organization_import.wait.title' => array('text' => 'Veuillez patienter', 'context' => 'Title of the full import waiting screen.'),
    'organization_import.wait.description' => array('text' => 'Ce processus peut prendre quelques minutes. Ne fermez pas cette fenetre pendant l import.', 'context' => 'Description of the full import waiting screen.'),
    'organization_import.wait.progress' => array('text' => 'Importation en cours...', 'context' => 'Indeterminate progress label of the full import waiting screen.'),
    'organization_import.module.calendar' => array('text' => 'Calendrier', 'context' => 'Calendar module label in the organization import popup.'),
    'organization_import.module.documents' => array('text' => 'Documents', 'context' => 'Documents module label in the organization import popup.'),
    'organization_import.module.indicators' => array('text' => 'Indicateurs', 'context' => 'Indicators module label in the organization import popup.'),
    'organization_import.module.members' => array('text' => 'Membres et roles', 'context' => 'Members module label in the organization import popup.'),
    'organization_import.module.projects' => array('text' => 'Projets', 'context' => 'Projects module label in the organization import popup.'),
    'organization_import.module.rules' => array('text' => 'Regles', 'context' => 'Rules module label in the organization import popup.'),
    'organization_import.module.pv' => array('text' => 'Proces-verbaux', 'context' => 'Meeting minutes module label in the organization import popup.'),
    'organization_import.module.structure' => array('text' => 'Structure', 'context' => 'Structure module label in the organization import popup.'),
    'organization_import.module.tasks' => array('text' => 'Tâches', 'context' => 'Tasks module label in the organization import popup.'),
    'organization_import.title' => array('text' => 'Importer une organisation', 'context' => 'Main title in the organization import popup.'),
);

$lang = translationBundleInit('omo_organization_create_import_popup', omoGetTranslationLocale(), $sourceLang);
$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="generic-soft-panel"><?= htmlspecialchars(t('organization_import.error.auth', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$modules = array(
    'structure' => t('organization_import.module.structure', array(), $lang, $sourceLang),
    'members' => t('organization_import.module.members', array(), $lang, $sourceLang),
    'rules' => t('organization_import.module.rules', array(), $lang, $sourceLang),
    'documents' => t('organization_import.module.documents', array(), $lang, $sourceLang),
    'projects' => t('organization_import.module.projects', array(), $lang, $sourceLang),
    'tasks' => t('organization_import.module.tasks', array(), $lang, $sourceLang),
    'checklists' => t('organization_import.module.checklists', array(), $lang, $sourceLang),
    'indicators' => t('organization_import.module.indicators', array(), $lang, $sourceLang),
    'calendar' => t('organization_import.module.calendar', array(), $lang, $sourceLang),
    'pv' => t('organization_import.module.pv', array(), $lang, $sourceLang),
);
$templateCatalog = (new \dbObject\Organization())->getStructuralImportTemplateCatalog();
?>
<div class="omo-create-import" data-omo-create-import="1">
    <header class="generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy">
            <div class="generic-card-title generic-card-title--eyebrow">OMO 1 vers OMO 2</div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(t('organization_import.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('organization_import.help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>

    <div class="omo-create-import__content generic-drawer-content">
    <form class="omo-create-import__form generic-section generic-section--stack" data-omo-create-import-form="1" enctype="multipart/form-data">
        <label class="omo-create-import__field">
            <span><?= htmlspecialchars(t('organization_import.field.file', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="file" name="omo1_export_file" class="generic-form-control" accept=".json,application/json" required>
        </label>

        <label class="omo-create-import__field">
            <span><?= htmlspecialchars(t('organization_import.field.name', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="text" name="organization_name" class="generic-form-control" maxlength="100">
            <small><?= htmlspecialchars(t('organization_import.field.name_hint', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
        </label>

        <label class="omo-create-import__field">
            <span><?= htmlspecialchars(t('organization_import.field.template', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <select name="organization_template_id" class="generic-form-control" data-omo-create-import-template="1">
                <option value="0"><?= htmlspecialchars(t('organization_import.field.template_empty', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></option>
                <?php foreach ($templateCatalog as $template): ?>
                    <option value="<?= (int)($template['id'] ?? 0) ?>"><?= htmlspecialchars((string)($template['name'] ?? 'Modele'), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <small><?= htmlspecialchars(t('organization_import.field.template_hint', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
        </label>

        <section class="omo-create-import__mappings generic-soft-panel" data-omo-create-import-mappings="1" hidden>
            <div class="generic-card-title generic-card-title--small"><?= htmlspecialchars(t('organization_import.mapping.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></div>
            <p><?= htmlspecialchars(t('organization_import.mapping.help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="omo-create-import__mapping-list" data-omo-create-import-mapping-list="1"></div>
            <input type="hidden" name="template_mappings" value="{}" data-omo-create-import-mapping-value="1">
            <input type="hidden" name="property_mappings" value="{}" data-omo-create-import-property-mapping-value="1">
        </section>

        <fieldset class="omo-create-import__modules">
            <legend><?= htmlspecialchars(t('organization_import.field.sections', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></legend>
            <input type="hidden" name="modules[]" value="structure">
            <?php foreach ($modules as $module => $label): ?>
                <label class="omo-create-import__module" data-omo-create-import-module="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>">
                    <input
                        type="checkbox"
                        <?php if ($module !== 'structure'): ?>name="modules[]" value="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>" checked<?php else: ?>checked disabled<?php endif; ?>
                        data-omo-create-import-module-input="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <small data-omo-create-import-module-count="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>"></small>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <label class="omo-create-import__field generic-soft-panel omo-create-import__invitation-option">
            <span><?= htmlspecialchars(t('organization_import.field.member_invitation_email_choice', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <select name="member_invitation_email_choice" class="generic-form-control" required data-omo-create-import-member-invitation-email-choice="1">
                <option value="" selected disabled><?= htmlspecialchars(t('organization_import.field.member_invitation_email_choice_empty', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="send"><?= htmlspecialchars(t('organization_import.field.member_invitation_email_choice_send', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="skip"><?= htmlspecialchars(t('organization_import.field.member_invitation_email_choice_skip', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></option>
            </select>
            <small><?= htmlspecialchars(t('organization_import.field.member_invitation_email_choice_hint', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
        </label>

        <div class="omo-create-import__actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-create-import-cancel="1"><?= htmlspecialchars(t('organization_import.action.cancel', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" data-omo-create-import-submit="1"><?= htmlspecialchars(t('organization_import.action.submit', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </form>

    <section class="omo-create-import__waiting generic-section" data-omo-create-import-waiting="1" hidden aria-live="polite" aria-busy="true">
        <div class="omo-create-import__waiting-spinner" aria-hidden="true"></div>
        <h3 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(t('organization_import.wait.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(t('organization_import.wait.description', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="omo-create-import__waiting-progress" role="progressbar" aria-label="<?= htmlspecialchars(t('organization_import.wait.progress', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>" aria-valuemin="0" aria-valuemax="100">
            <span></span>
        </div>
        <small><?= htmlspecialchars(t('organization_import.wait.progress', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
    </section>

    <div class="omo-create-import__feedback generic-soft-panel" data-omo-create-import-feedback="1" hidden></div>
    </div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/organizations/create-import.css') ?>">

<?= commonPageScriptTags('/omo/api/organizations/create_import_popup.js', [
    'templateCatalog' => $templateCatalog,
    'ui' => array(
        'fileError' => t('organization_import.error.file', array(), $lang, $sourceLang),
        'memberInvitationEmailChoiceError' => t('organization_import.error.member_invitation_email_choice', array(), $lang, $sourceLang),
        'genericError' => t('organization_import.error.generic', array(), $lang, $sourceLang),
        'loading' => t('organization_import.loading', array(), $lang, $sourceLang),
        'mappingDuplicate' => t('organization_import.mapping.duplicate', array(), $lang, $sourceLang),
        'mappingEmpty' => t('organization_import.mapping.empty', array(), $lang, $sourceLang),
        'mappingExclude' => t('organization_import.mapping.exclude', array(), $lang, $sourceLang),
        'mappingNone' => t('organization_import.mapping.none', array(), $lang, $sourceLang),
        'propertyMappingDuplicate' => t('organization_import.property_mapping.duplicate', array(), $lang, $sourceLang),
        'propertyMappingEmpty' => t('organization_import.property_mapping.empty', array(), $lang, $sourceLang),
        'propertyMappingExclude' => t('organization_import.property_mapping.exclude', array(), $lang, $sourceLang),
        'propertyMappingHelp' => t('organization_import.property_mapping.help', array(), $lang, $sourceLang),
        'propertyMappingNone' => t('organization_import.property_mapping.none', array(), $lang, $sourceLang),
        'propertyMappingTitle' => t('organization_import.property_mapping.title', array(), $lang, $sourceLang),
    ),
]) ?>
