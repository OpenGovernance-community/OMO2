<?php

use dbObject\DecisionProcess;
use dbObject\DecisionGroup;

$viewInput = isset($omoDecisionInput) && is_array($omoDecisionInput) ? $omoDecisionInput : $_GET;

$baseSourceLang = [
    'decisions.edit.create_title' => [
        'text' => 'Nouvelle prise de décision',
        'context' => 'Drawer title when creating a decision process.',
    ],
    'decisions.edit.edit_title' => [
        'text' => 'Modifier la prise de décision',
        'context' => 'Drawer title when editing a decision process.',
    ],
    'decisions.edit.view_title' => [
        'text' => 'Voir la prise de décision',
        'context' => 'Drawer title when opening a decision in read-only mode.',
    ],
    'decisions.edit.description' => [
        'text' => 'Choisissez une méthode, puis configurez un premier scrutin dans une structure pensée pour accueillir d’autres modules plus tard.',
        'context' => 'Short description for the decision editor screen.',
    ],
    'decisions.edit.choose_title' => [
        'text' => 'Choisir une méthode',
        'context' => 'Title of the method selection step.',
    ],
    'decisions.edit.choose_text' => [
        'text' => 'Commencez par sélectionner la manière d’évaluer cette prise de décision. Le parcours de création détaillé dépend ensuite du module choisi.',
        'context' => 'Description of the method selection step.',
    ],
    'decisions.edit.governance.label' => [
        'text' => 'Décision hors réorg',
        'context' => 'Label of the deferred governance workflow card.',
    ],
    'decisions.edit.governance.description' => [
        'text' => 'Proposez des modifications de règles ou de structure, discutez-les puis appliquez-les automatiquement après un consentement sans objection.',
        'context' => 'Description of the deferred governance workflow card.',
    ],
    'decisions.edit.governance.open' => [
        'text' => 'Préparer des modifications',
        'context' => 'CTA of the deferred governance workflow card.',
    ],
    'decisions.edit.choose_later' => [
        'text' => 'Bientôt disponible',
        'context' => 'Badge for methods not implemented yet.',
    ],
    'decisions.edit.method.simple_vote.label' => [
        'text' => 'Vote simple',
        'context' => 'Label of the simple vote module card.',
    ],
    'decisions.edit.method.simple_vote.description' => [
        'text' => 'Chaque participant choisit une proposition parmi plusieurs options.',
        'context' => 'Description of the simple vote module card.',
    ],
    'decisions.edit.method.majority_judgment.label' => [
        'text' => 'Jugement majoritaire',
        'context' => 'Label of the majority judgment module card.',
    ],
    'decisions.edit.method.majority_judgment.description' => [
        'text' => 'Chaque proposition reçoit une mention sur une échelle commune.',
        'context' => 'Description of the majority judgment module card.',
    ],
    'decisions.edit.method.consent.label' => [
        'text' => 'Consentement',
        'context' => 'Label of the consent module card.',
    ],
    'decisions.edit.method.consent.description' => [
        'text' => 'Une proposition est retenue tant qu’aucune objection bloquante n’est formulée.',
        'context' => 'Description of the consent module card.',
    ],
    'decisions.edit.method.consultation_only.label' => [
        'text' => 'Consultation seule',
        'context' => 'Label of the consultation only module card.',
    ],
    'decisions.edit.method.consultation_only.description' => [
        'text' => 'Recueillez et discutez des propositions avant de choisir, ou non, un mode de vote.',
        'context' => 'Description of the consultation only module card.',
    ],
    'decisions.edit.field.evaluation_method' => [
        'text' => 'Mode de prise de décision',
        'context' => 'Label for the read-only evaluation method reminder in one question.',
    ],
    'decisions.edit.method.open' => [
        'text' => 'Configurer cette méthode',
        'context' => 'CTA on an available method card.',
    ],
    'decisions.edit.method.locked' => [
        'text' => 'Méthode verrouillée',
        'context' => 'Hint when editing an existing decision method.',
    ],
    'decisions.edit.unsupported_title' => [
        'text' => 'Méthode non encore disponible',
        'context' => 'Title shown when a method exists but its editor is not implemented.',
    ],
    'decisions.edit.unsupported_text' => [
        'text' => 'Ce mode est prévu dans l’architecture, mais son écran de création détaillé n’est pas encore disponible.',
        'context' => 'Body shown when a method exists but its editor is not implemented.',
    ],
    'decisions.edit.summary.organization' => [
        'text' => 'Organisation',
        'context' => 'Summary label for the organization context.',
    ],
    'decisions.edit.summary.context' => [
        'text' => 'Contexte',
        'context' => 'Summary label for the current holon or organization level context.',
    ],
    'decisions.edit.summary.mode' => [
        'text' => 'Mode',
        'context' => 'Summary label for create or edit mode.',
    ],
    'decisions.edit.summary.method' => [
        'text' => 'Méthode',
        'context' => 'Summary label for the selected evaluation method.',
    ],
    'decisions.edit.summary.target' => [
        'text' => 'Cible',
        'context' => 'Summary label for the edited decision title.',
    ],
    'decisions.edit.visibility.label' => [
        'text' => 'Visibilité',
        'context' => 'Label for the decision visibility field shared by all decision editors.',
    ],
    'decisions.edit.summary.mode_create' => [
        'text' => 'Création',
        'context' => 'Summary value when creating a decision.',
    ],
    'decisions.edit.summary.mode_edit' => [
        'text' => 'Édition',
        'context' => 'Summary value when editing a decision.',
    ],
    'decisions.edit.summary.no_holon' => [
        'text' => 'Sans holon',
        'context' => 'Summary fallback when the decision is attached only to the organization.',
    ],
    'decisions.edit.context.organization_invalid' => [
        'text' => 'Organisation invalide.',
        'context' => 'Error when the organization id is missing or invalid.',
    ],
    'decisions.edit.context.organization_not_found' => [
        'text' => 'Organisation introuvable.',
        'context' => 'Error when the organization cannot be loaded.',
    ],
    'decisions.edit.context.organization_denied' => [
        'text' => 'Accès refusé à cette organisation.',
        'context' => 'Error when the user cannot view the organization.',
    ],
    'decisions.edit.context.organization_manage_denied' => [
        'text' => 'Vous n’avez pas les droits nécessaires pour créer une prise de décision dans cette organisation.',
        'context' => 'Error when the user cannot create an organization-level decision.',
    ],
    'decisions.edit.context.holon_not_found' => [
        'text' => 'Holon introuvable pour cette organisation.',
        'context' => 'Error when the requested holon is invalid.',
    ],
    'decisions.edit.context.holon_denied' => [
        'text' => 'Accès refusé à ce holon.',
        'context' => 'Error when the user cannot view the requested holon.',
    ],
    'decisions.edit.context.holon_manage_denied' => [
        'text' => 'Vous n’avez pas les droits nécessaires pour créer une prise de décision dans ce holon.',
        'context' => 'Error when the user cannot create a holon-level decision.',
    ],
    'decisions.edit.context.decision_not_found' => [
        'text' => 'Prise de décision introuvable.',
        'context' => 'Error when the requested decision cannot be loaded.',
    ],
    'decisions.edit.context.decision_mismatch' => [
        'text' => 'Cette prise de décision n’appartient pas à l’organisation courante.',
        'context' => 'Error when the decision does not belong to the current organization.',
    ],
    'decisions.edit.context.decision_denied' => [
        'text' => 'Vous n’avez pas les droits nécessaires pour modifier cette prise de décision.',
        'context' => 'Error when the user cannot manage the requested decision.',
    ],
    'decisions.edit.groups.title' => [
        'text' => 'Questions',
        'context' => 'Section title for decision groups navigation.',
    ],
    'decisions.edit.groups.text' => [
        'text' => 'Ajoutez plusieurs blocs de décision au même processus, puis passez de l’un à l’autre.',
        'context' => 'Help text for decision groups navigation.',
    ],
    'decisions.edit.groups.add' => [
        'text' => 'Ajouter une question',
        'context' => 'Button label to create a new decision group.',
    ],
    'decisions.edit.groups.choose_method' => [
        'text' => 'Choisissez le mode de la nouvelle question',
        'context' => 'Heading displayed before choosing the method of a locally added question.',
    ],
    'decisions.edit.groups.unsaved' => [
        'text' => 'Nouvelle',
        'context' => 'Small badge for a question which has not been saved yet.',
    ],
    'decisions.edit.multi.process_title' => [
        'text' => 'Paramètres généraux du scrutin',
        'context' => 'Heading for settings shared by every question in a multi-question decision.',
    ],
    'decisions.edit.multi.questions_title' => [
        'text' => 'Questions',
        'context' => 'Heading for the question-specific part of a multi-question decision.',
    ],
    'decisions.edit.multi.process_name' => [
        'text' => 'Titre du processus',
        'context' => 'Label for the shared decision process title.',
    ],
    'decisions.edit.multi.process_description' => [
        'text' => 'Description du contexte',
        'context' => 'Label for the shared decision process description.',
    ],
    'decisions.edit.multi.status' => [
        'text' => 'Statut',
        'context' => 'Label for the shared decision status.',
    ],
    'decisions.edit.multi.status.draft' => ['text' => 'En préparation', 'context' => 'Draft decision status.'],
    'decisions.edit.multi.status.scheduled' => ['text' => 'Planifiée', 'context' => 'Scheduled decision status.'],
    'decisions.edit.multi.status.consultation' => ['text' => 'En élaboration', 'context' => 'Elaboration decision status.'],
    'decisions.edit.multi.status.evaluation' => ['text' => 'En évaluation', 'context' => 'Evaluation decision status.'],
    'decisions.edit.multi.status.results' => ['text' => 'Résultats', 'context' => 'Results decision status.'],
    'decisions.edit.multi.status.archived' => ['text' => 'Archivée', 'context' => 'Archived decision status.'],
    'decisions.edit.multi.consultation_start' => [
        'text' => 'Début de la phase d’élaboration',
        'context' => 'Label for the shared consultation start date.',
    ],
    'decisions.edit.multi.consultation_end' => [
        'text' => 'Fin de la phase d’élaboration',
        'context' => 'Label for the shared consultation end date.',
    ],
    'decisions.edit.multi.evaluation_start' => [
        'text' => 'Début des prises de position',
        'context' => 'Label for the shared evaluation start date.',
    ],
    'decisions.edit.multi.evaluation_end' => [
        'text' => 'Clôture des prises de position',
        'context' => 'Label for the shared evaluation end date.',
    ],
    'decisions.edit.multi.save' => [
        'text' => 'Enregistrer le scrutin',
        'context' => 'Button used to save all questions and shared settings explicitly.',
    ],
    'decisions.edit.multi.saving' => [
        'text' => 'Enregistrement…',
        'context' => 'Temporary label while all decision questions are being saved.',
    ],
    'decisions.edit.multi.saved' => [
        'text' => 'Scrutin enregistré.',
        'context' => 'Success message after saving all decision questions.',
    ],
    'decisions.edit.multi.save_error' => [
        'text' => 'Impossible d’enregistrer toutes les questions.',
        'context' => 'Fallback error while saving a multi-question decision.',
    ],
    'decisions.edit.multi.unsaved_warning' => [
        'text' => 'Des modifications ne sont pas enregistrées. Voulez-vous fermer le formulaire et les abandonner ?',
        'context' => 'Warning shown before leaving a dirty multi-question editor.',
    ],
    'decisions.edit.groups.item' => [
        'text' => 'Question {index}',
        'context' => 'Numbered tab label for one decision group.',
    ],
    'decisions.edit.import.title' => [
        'text' => 'Importer un scrutin',
        'context' => 'Title of the import panel on the decision creation screen.',
    ],
    'decisions.edit.import.text' => [
        'text' => 'Chargez un fichier CSV, JSON ou XML exporté depuis Décisions pour recréer la structure du scrutin sans les réponses.',
        'context' => 'Help text of the import panel on the decision creation screen.',
    ],
    'decisions.edit.import.file_label' => [
        'text' => 'Fichier d’import',
        'context' => 'Label of the import file input on the decision creation screen.',
    ],
    'decisions.edit.import.button' => [
        'text' => 'Importer ce fichier',
        'context' => 'Submit button label for the decision import form.',
    ],
    'decisions.edit.import.loading' => [
        'text' => 'Importation en cours…',
        'context' => 'Temporary label shown while the decision import is running.',
    ],
    'decisions.edit.import.error' => [
        'text' => 'Impossible d’importer ce fichier pour le moment.',
        'context' => 'Fallback error message for the decision import form.',
    ],
    'decisions.edit.import.no_file' => [
        'text' => 'Choisissez un fichier CSV, JSON ou XML à importer.',
        'context' => 'Validation message when no import file was selected.',
    ],
    'decisions.edit.block_settings.vote_weighting' => [
        'text' => 'Pondération des votes',
        'context' => 'Shared label for the optional vote weighting setting on one decision block.',
    ],
    'decisions.edit.block_settings.vote_weighting_enable' => [
        'text' => 'Activer la pondération des votes',
        'context' => 'Shared label for enabling vote weighting on one decision block.',
    ],
    'decisions.edit.block_settings.vote_weighting_question' => [
        'text' => 'Question de pondération',
        'context' => 'Shared label for the question shown to participants before selecting a vote weight.',
    ],
    'decisions.edit.block_settings.vote_weighting_options' => [
        'text' => 'Options de pondération',
        'context' => 'Shared label for the weighting options editor on one decision block.',
    ],
    'decisions.edit.block_settings.vote_weighting_weight' => [
        'text' => 'Coefficient',
        'context' => 'Shared label for a vote weighting coefficient field.',
    ],
    'decisions.edit.block_settings.vote_weighting_weight_base' => [
        'text' => 'Référence',
        'context' => 'Shared label for the fixed 1x vote weighting coefficient field.',
    ],
    'decisions.edit.block_settings.vote_weighting_label' => [
        'text' => 'Libellé',
        'context' => 'Shared label for a vote weighting option label field.',
    ],
    'decisions.edit.block_settings.vote_weighting_add' => [
        'text' => 'Ajouter une ligne',
        'context' => 'Shared button label used to add a vote weighting row.',
    ],
    'decisions.edit.block_settings.vote_weighting_remove' => [
        'text' => 'Retirer',
        'context' => 'Shared button label used to remove a vote weighting row.',
    ],
    'decisions.edit.block_settings.vote_weighting_fixed_hint' => [
        'text' => 'La ligne 1× reste toujours présente comme référence neutre.',
        'context' => 'Shared hint explaining that the 1x weighting row is always present.',
    ],
    'decisions.edit.block_settings.vote_weighting_options_help' => [
        'text' => 'Une option par ligne, au format poids | libellé.',
        'context' => 'Shared help text for the weighting options multiline editor.',
    ],
    'decisions.edit.settings.behavior' => [
        'text' => 'Déroulement',
        'context' => 'Heading for voting behavior settings in the compact settings summary.',
    ],
    'decisions.edit.settings.participation' => [
        'text' => 'Participation et échanges',
        'context' => 'Heading for proposal participation and discussion settings.',
    ],
    'decisions.edit.settings.presentation' => [
        'text' => 'Présentation du vote',
        'context' => 'Heading for proposal display settings during voting.',
    ],
    'decisions.edit.settings.privacy' => [
        'text' => 'Confidentialité et résultats',
        'context' => 'Heading for privacy and result visibility settings in the compact settings summary.',
    ],
    'decisions.edit.proposal_content.title' => [
        'text' => 'Teneur des propositions',
        'context' => 'Section title for choosing which proposal fields are enabled.',
    ],
    'decisions.edit.proposal_content.hint' => [
        'text' => 'Choisissez les champs présentés lors de la saisie des propositions.',
        'context' => 'Help text for proposal content settings.',
    ],
    'decisions.edit.proposal_content.summary_label' => [
        'text' => 'Champs des propositions',
        'context' => 'Summary label for the enabled proposal fields.',
    ],
    'decisions.edit.proposal_content.title_field' => [
        'text' => 'Titre',
        'context' => 'Option to enable the proposal title field.',
    ],
    'decisions.edit.proposal_content.description_field' => [
        'text' => 'Description',
        'context' => 'Option to enable the proposal description field.',
    ],
    'decisions.edit.proposal_content.url_field' => [
        'text' => 'URL',
        'context' => 'Option to enable the proposal URL field.',
    ],
    'decisions.edit.block_settings.vote_weighting_summary_yes' => [
        'text' => 'Oui',
        'context' => 'Shared yes label for vote weighting summaries.',
    ],
    'decisions.edit.block_settings.vote_weighting_summary_no' => [
        'text' => 'Non',
        'context' => 'Shared no label for vote weighting summaries.',
    ],
    'decisions.edit.block_settings.vote_weighting_placeholder_question' => [
        'text' => 'À quel point assister à cette rencontre ?',
        'context' => 'Shared placeholder for the vote weighting question field.',
    ],
    'decisions.edit.block_settings.vote_weighting_placeholder_options' => [
        'text' => "0.75 | Pas important\n1 | Souhaitable\n1.5 | Important\n2 | Vital",
        'context' => 'Shared placeholder for the weighting options multiline editor.',
    ],
];

$selectedGroup = (!empty($context['decisionGroup']) && $context['decisionGroup'] instanceof DecisionGroup)
    ? $context['decisionGroup']
    : null;
$groupAction = trim((string)($viewInput['group_action'] ?? ''));
$selectedMethod = '';
$requestedMethod = trim((string)($viewInput['method'] ?? ''));
if ($groupAction === 'create' && $requestedMethod !== '' && omoDecisionGetModuleDefinition($requestedMethod, (int)($context['organizationId'] ?? 0))) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($requestedMethod);
} elseif ($selectedGroup instanceof DecisionGroup) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($selectedGroup->get('evaluation_method'));
} elseif (!empty($context['decision']) && $context['decision'] instanceof DecisionProcess) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($context['decision']->get('evaluation_method'));
} elseif ($requestedMethod !== '' && omoDecisionGetModuleDefinition($requestedMethod, (int)($context['organizationId'] ?? 0))) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($requestedMethod);
}

$organizationIdForModules = (int)($context['organizationId'] ?? 0);
$moduleDefinition = $selectedMethod !== '' ? omoDecisionGetModuleDefinition($selectedMethod, $organizationIdForModules) : null;
$moduleDefinitionsToLoad = [];
$contextDecision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$loadEveryEditorModule = $contextDecision instanceof DecisionProcess
    && (($context['intent'] ?? '') === 'manage')
    && $groupAction === ''
    && !$contextDecision->isGovernanceWorkflow();

if ($loadEveryEditorModule) {
    $moduleDefinitionsToLoad = array_values(omoDecisionGetModuleRegistry($organizationIdForModules));
} elseif ($moduleDefinition) {
    $moduleDefinitionsToLoad[] = $moduleDefinition;
}

foreach ($moduleDefinitionsToLoad as $definitionToLoad) {
    if (!is_array($definitionToLoad)) {
        continue;
    }
    if (!empty($definitionToLoad['shared_file']) && is_file($definitionToLoad['shared_file'])) {
        require_once $definitionToLoad['shared_file'];
    }
    if (!empty($definitionToLoad['editor_file']) && is_file($definitionToLoad['editor_file'])) {
        require_once $definitionToLoad['editor_file'];
        $moduleSourceFunction = (string)($definitionToLoad['source_lang_function'] ?? '');
        if ($moduleSourceFunction !== '' && function_exists($moduleSourceFunction)) {
            $baseSourceLang = array_merge($baseSourceLang, $moduleSourceFunction());
        }
    }
}

if (function_exists('omoDecisionInvitationGetSourceLang')) {
    $baseSourceLang = array_merge($baseSourceLang, omoDecisionInvitationGetSourceLang());
}

$lang = omoLoadTranslationBundle('omo_decision_edit', $baseSourceLang);
$escape = 'omoApiEscape';

if (empty($context['status'])) {
    http_response_code((int)($context['code'] ?? 400));
    $errorKey = (string)($context['error_key'] ?? 'decisions.edit.context.organization_invalid');
    ?>
    <div class="omo-decision-edit omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-empty-state"><?= $escape(t($errorKey, [], $lang, $baseSourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    return;
}

$organization = $context['organization'];
$decision = $context['decision'];
$effectiveHolon = $context['effectiveHolon'];
$intent = (string)($context['intent'] ?? 'manage');
$isEditing = $decision instanceof DecisionProcess;
$decisionGroups = $isEditing ? $decision->getDecisionGroups(false) : [];
$modeLabel = $isEditing
    ? t('decisions.edit.summary.mode_edit', [], $lang, $baseSourceLang)
    : t('decisions.edit.summary.mode_create', [], $lang, $baseSourceLang);
$contextLabel = $effectiveHolon
    ? trim((string)$effectiveHolon->get('name'))
    : t('decisions.edit.summary.no_holon', [], $lang, $baseSourceLang);
$registry = omoDecisionGetModuleRegistry((int)($context['organizationId'] ?? 0));
$selectedDefinition = $selectedMethod !== '' ? omoDecisionGetModuleDefinition($selectedMethod, (int)($context['organizationId'] ?? 0)) : null;
$selectedLabel = $selectedDefinition
    ? t((string)$selectedDefinition['label_key'], [], $lang, $baseSourceLang)
    : '';
$showContextSummary = (($context['accessMode'] ?? '') !== 'public') && empty($context['previewLayout']);
$isGovernanceWorkflow = trim((string)($viewInput['workflow'] ?? '')) === DecisionProcess::WORKFLOW_GOVERNANCE
    || ($decision instanceof DecisionProcess && $decision->isGovernanceWorkflow());
$decisionSettings = omoDecisionParamsGetConfig($organization);
$useMultiQuestionEditor = $isEditing
    && $intent === 'manage'
    && !$isGovernanceWorkflow
    && $groupAction === ''
    && count($decisionGroups) > 0;
$questionFragmentRequested = $isEditing
    && $intent === 'manage'
    && !$isGovernanceWorkflow
    && $groupAction === 'create'
    && !empty($viewInput['question_fragment'])
    && $selectedDefinition
    && !empty($selectedDefinition['render_function'])
    && function_exists((string)$selectedDefinition['render_function']);

if (!function_exists('omoDecisionRenderEditorGroupSwitch')) {
    function omoDecisionRenderEditorGroupSwitch(array $context, ?DecisionProcess $decision, ?DecisionGroup $selectedGroup, iterable $decisionGroups, array $lang, array $baseSourceLang, string $escape): void
    {
        if (!$decision instanceof DecisionProcess || (($context['intent'] ?? 'manage') !== 'manage')) {
            return;
        }
        $canAddQuestion = !array_key_exists('canAddQuestion', $context) || !empty($context['canAddQuestion']);
        ?>
        <nav
            class="omo-decision-edit__question-nav"
            aria-label="<?= $escape(t('decisions.edit.groups.title', [], $lang, $baseSourceLang)) ?>"
            data-omo-decision-question-nav
        >
            <div class="omo-decision-edit__question-tabs">
                <?php $groupIndex = 0; ?>
                <?php foreach ($decisionGroups as $groupItem): ?>
                    <?php
                    $groupIndex++;
                    $groupId = (int)$groupItem->getId();
                    $groupTitle = trim((string)$groupItem->get('title'));
                    $groupLabel = t('decisions.edit.groups.item', ['index' => (string)$groupIndex], $lang, $baseSourceLang);
                    $isSelectedGroup = $selectedGroup && (int)$selectedGroup->getId() === $groupId;
                    ?>
                    <a
                        class="omo-decision-edit__question-tab<?= $isSelectedGroup ? ' is-active' : '' ?>"
                        href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), trim((string)$groupItem->get('evaluation_method')), 'manage', $groupId)) ?>"
                        data-omo-decision-editor-link
                        data-omo-decision-question-link
                        data-question-key="group-<?= $groupId ?>"
                        data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                        <?= $isSelectedGroup ? 'aria-current="page"' : '' ?>
                        <?= $groupTitle !== '' ? 'title="' . $escape($groupTitle) . '"' : '' ?>
                    >
                        <?= $escape($groupLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($context['multiQuestionEditor'])): ?>
                <div class="omo-decision-edit__question-add-wrap">
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary omo-decision-edit__question-add"
                        data-omo-decision-question-add-toggle
                        aria-expanded="false"
                        <?= $canAddQuestion ? '' : 'disabled' ?>
                    >
                        <span aria-hidden="true">+</span>
                        <?= $escape(t('decisions.edit.groups.add', [], $lang, $baseSourceLang)) ?>
                    </button>
                    <div class="omo-decision-edit__question-methods generic-soft-panel" data-omo-decision-question-methods hidden>
                        <strong><?= $escape(t('decisions.edit.groups.choose_method', [], $lang, $baseSourceLang)) ?></strong>
                        <div class="omo-decision-edit__question-method-actions">
                            <?php foreach (omoDecisionGetModuleRegistry((int)$context['organizationId']) as $methodKey => $methodDefinition): ?>
                                <?php if (!empty($methodDefinition['available'])): ?>
                                <button
                                    type="button"
                                    class="generic-action-button generic-action-button--secondary"
                                    data-omo-decision-question-add-method="<?= $escape((string)$methodKey) ?>"
                                    data-fragment-url="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), (string)$methodKey, 'manage', 0, 'create') . '&question_fragment=1') ?>"
                                ><?= $escape(t((string)$methodDefinition['label_key'], [], $lang, $baseSourceLang)) ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a
                    class="generic-action-button generic-action-button--secondary omo-decision-edit__question-add"
                    href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), '', 'manage', 0, 'create')) ?>"
                    data-omo-decision-editor-link
                    data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                >
                    <span aria-hidden="true">+</span>
                    <?= $escape(t('decisions.edit.groups.add', [], $lang, $baseSourceLang)) ?>
                </a>
            <?php endif; ?>
        </nav>
        <?php
    }
}

if (!function_exists('omoDecisionResolveVisibilityEditorState')) {
    function omoDecisionResolveVisibilityEditorState(?DecisionProcess $decision, array $context): array
    {
        $organizationId = $decision instanceof DecisionProcess
            ? (int)$decision->get('IDorganization')
            : (int)($context['organizationId'] ?? 0);
        $holonId = $decision instanceof DecisionProcess
            ? (int)$decision->get('IDholon')
            : (int)($context['targetHolonId'] ?? 0);
        $editorConfig = DecisionProcess::buildVisibilityEditorConfig($organizationId, $holonId);
        $visibilityOptions = is_array($editorConfig['visibilityOptions'] ?? null)
            ? $editorConfig['visibilityOptions']
            : DecisionProcess::getVisibilityTypeOptions();
        $disabledVisibilityTypes = is_array($editorConfig['disabledTypes'] ?? null)
            ? $editorConfig['disabledTypes']
            : array();
        $selectedVisibilityType = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeVisibilityType($decision->get('visibility_type'))
            : DecisionProcess::getDefaultVisibilityType();

        if (!empty($disabledVisibilityTypes[$selectedVisibilityType])) {
            $selectedVisibilityType = DecisionProcess::getDefaultVisibilityType();
        }

        return array(
            'selectedVisibilityType' => $selectedVisibilityType,
            'visibilityOptions' => $visibilityOptions,
            'disabledVisibilityTypes' => $disabledVisibilityTypes,
            'visibilityHelpText' => trim((string)($editorConfig['helpText'] ?? '')),
        );
    }
}
?>
<link rel="stylesheet" href="/common/choice/decision_cards.css?v=20260813-decision-uniformity">
<div class="omo-decision-edit omo-panel-view">
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-decision-edit__stack generic-drawer-content generic-form-stack">
            

            <?php if (false && $isEditing && $intent === 'manage'): ?>
            <section class="generic-soft-panel generic-soft-panel--stack omo-decision-edit__group-switch">
                <div class="omo-decision-edit__section-head">
                    <div>
                        <h3 class="generic-card-title generic-card-title--section">Groupes</h3>
                        <p class="omo-decision-edit__lead">Ajoutez plusieurs blocs de decision dans le meme processus, puis passez de l un a l autre.</p>
                    </div>
                    <a
                        class="generic-action-button generic-action-button--secondary"
                        href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), '', 'manage', 0, 'create')) ?>"
                        data-omo-decision-editor-link
                        data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                    >
                        Ajouter un groupe
                    </a>
                </div>
                <div class="omo-decision-edit__group-tabs">
                    <?php foreach ($decisionGroups as $groupItem): ?>
                        <?php
                        $groupId = (int)$groupItem->getId();
                        $groupMethodDefinition = omoDecisionGetModuleDefinition((string)$groupItem->get('evaluation_method'));
                        $groupMethodLabel = $groupMethodDefinition
                            ? t((string)$groupMethodDefinition['label_key'], [], $lang, $baseSourceLang)
                            : trim((string)$groupItem->get('evaluation_method'));
                        $groupTitle = trim((string)$groupItem->get('title'));
                        if ($groupTitle === '') {
                            $groupTitle = 'Bloc ' . (string)$groupItem->get('position');
                        }
                        ?>
                        <a
                            class="generic-action-button <?= $selectedGroup && (int)$selectedGroup->getId() === $groupId ? 'generic-action-button--main' : 'generic-action-button--secondary' ?>"
                            href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), trim((string)$groupItem->get('evaluation_method')), 'manage', $groupId)) ?>"
                            data-omo-decision-editor-link
                            data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                        >
                            <?= $escape($groupTitle) ?> · <?= $escape($groupMethodLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($questionFragmentRequested): ?>
                <?php
                $fragmentRenderFunction = (string)$selectedDefinition['render_function'];
                $fragmentContext = $context;
                $fragmentContext['decisionGroup'] = null;
                $fragmentContext['decisionGroupId'] = 0;
                $fragmentContext['multiQuestionEditor'] = true;
                ?>
                <div
                    data-omo-decision-question-fragment
                    data-question-method="<?= $escape($selectedMethod) ?>"
                >
                    <?php
                    $fragmentRenderFunction([
                        'context' => $fragmentContext,
                        'decision' => $decision,
                        'organization' => $organization,
                        'effectiveHolon' => $effectiveHolon,
                        'lang' => $lang,
                        'sourceLang' => $baseSourceLang,
                        'escape' => $escape,
                        'selectedMethod' => $selectedMethod,
                        'forceNewGroup' => true,
                        'embeddedQuestion' => true,
                        'includeAssets' => true,
                    ]);
                    ?>
                </div>
            <?php elseif ($useMultiQuestionEditor): ?>
                <?php
                $multiStatus = DecisionProcess::normalizeStatus($decision->get('status'));
                $multiConsultationOnly = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method')) === DecisionProcess::METHOD_CONSULTATION_ONLY;
                $multiCoreLocked = $decision->hasEvaluationStarted();
                $multiStartDatesLocked = $multiCoreLocked || $decision->hasSubmittedResponses();
                $multiResultsMode = in_array($multiStatus, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true);
                $multiCanEditStructure = !$multiResultsMode && !$multiCoreLocked;
                $multiCanEditStartDates = !$multiResultsMode && !$multiStartDatesLocked;
                $multiVisibilityState = omoDecisionResolveVisibilityEditorState($decision, $context);
                $multiDateValue = static function ($value): string {
                    $date = DecisionProcess::normalizeDateTimeValue($value);
                    return $date instanceof DateTimeInterface ? $date->format('Y-m-d\TH:i') : '';
                };
                $multiStatusOptions = [
                    DecisionProcess::STATUS_DRAFT => 'decisions.edit.multi.status.draft',
                    DecisionProcess::STATUS_SCHEDULED => 'decisions.edit.multi.status.scheduled',
                    DecisionProcess::STATUS_CONSULTATION => 'decisions.edit.multi.status.consultation',
                    DecisionProcess::STATUS_EVALUATION => 'decisions.edit.multi.status.evaluation',
                    DecisionProcess::STATUS_RESULTS => 'decisions.edit.multi.status.results',
                    DecisionProcess::STATUS_ARCHIVED => 'decisions.edit.multi.status.archived',
                ];
                $multiSelectedGroupId = $selectedGroup instanceof DecisionGroup
                    ? (int)$selectedGroup->getId()
                    : (int)($decisionGroups[0]->getId() ?? 0);
                $multiContext = $context;
                $multiContext['multiQuestionEditor'] = true;
                $multiContext['canAddQuestion'] = $multiCanEditStructure;
                $multiEditorDomId = 'omo-decision-multi-editor-' . (int)$decision->getId();
                ?>
                <div
                    hidden
                    data-omo-subdrawer-header
                    data-omo-subdrawer-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                >
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--main"
                        data-omo-subdrawer-action
                        data-omo-decision-multi-save-for="<?= $escape($multiEditorDomId) ?>"
                    ><?= $escape(t('decisions.edit.multi.save', [], $lang, $baseSourceLang)) ?></button>
                </div>
                <div
                    id="<?= $escape($multiEditorDomId) ?>"
                    class="omo-decision-edit__multi"
                    data-omo-decision-multi-editor
                    data-original-status="<?= $escape($multiStatus) ?>"
                    data-save-label="<?= $escape(t('decisions.edit.multi.save', [], $lang, $baseSourceLang)) ?>"
                    data-saving-label="<?= $escape(t('decisions.edit.multi.saving', [], $lang, $baseSourceLang)) ?>"
                    data-saved-message="<?= $escape(t('decisions.edit.multi.saved', [], $lang, $baseSourceLang)) ?>"
                    data-error-message="<?= $escape(t('decisions.edit.multi.save_error', [], $lang, $baseSourceLang)) ?>"
                    data-unsaved-warning="<?= $escape(t('decisions.edit.multi.unsaved_warning', [], $lang, $baseSourceLang)) ?>"
                >
                    <section class="generic-section generic-section--stack generic-form-section omo-decision-edit__process-settings">
                        <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.multi.process_title', [], $lang, $baseSourceLang)) ?></h3>
                        <form class="generic-form-stack" data-omo-decision-process-form>
                            <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                            <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                            <input type="hidden" name="id" value="<?= $escape((int)$decision->getId()) ?>">

                            <label class="generic-form-field">
                                <span class="generic-form-label"><?= $escape(t('decisions.edit.multi.process_name', [], $lang, $baseSourceLang)) ?></span>
                                <input type="text" class="generic-form-control" name="process_title" required maxlength="190" value="<?= $escape(trim((string)$decision->get('title'))) ?>" <?= $multiCanEditStructure ? '' : 'readonly' ?>>
                            </label>

                            <label class="generic-form-field">
                                <span class="generic-form-label"><?= $escape(t('decisions.edit.multi.process_description', [], $lang, $baseSourceLang)) ?></span>
                                <textarea class="generic-form-control" name="process_description" rows="4" <?= $multiCanEditStructure ? '' : 'readonly' ?>><?= $escape(trim((string)$decision->get('description'))) ?></textarea>
                            </label>

                            <div class="omo-decision-edit__process-primary">
                                <label class="generic-form-field">
                                    <span class="generic-form-label"><?= $escape(t('decisions.edit.visibility.label', [], $lang, $baseSourceLang)) ?></span>
                                    <select class="generic-form-control" name="visibility_type" <?= $multiCanEditStructure ? '' : 'disabled' ?>>
                                        <?php foreach (($multiVisibilityState['visibilityOptions'] ?? []) as $optionValue => $optionLabel): ?>
                                        <option value="<?= $escape((string)$optionValue) ?>" <?= $optionValue === ($multiVisibilityState['selectedVisibilityType'] ?? DecisionProcess::getDefaultVisibilityType()) ? 'selected' : '' ?> <?= !empty(($multiVisibilityState['disabledVisibilityTypes'] ?? [])[$optionValue]) ? 'disabled' : '' ?>><?= $escape((string)$optionLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="generic-form-field">
                                    <span class="generic-form-label"><?= $escape(t('decisions.edit.multi.status', [], $lang, $baseSourceLang)) ?></span>
                                    <select class="generic-form-control" name="status">
                                        <?php foreach ($multiStatusOptions as $statusValue => $statusLabelKey): ?>
                                        <?php if ($multiConsultationOnly && !in_array($statusValue, [DecisionProcess::STATUS_DRAFT, DecisionProcess::STATUS_SCHEDULED, DecisionProcess::STATUS_CONSULTATION], true)) continue; ?>
                                        <option value="<?= $escape($statusValue) ?>" <?= $multiStatus === $statusValue ? 'selected' : '' ?>><?= $escape(t($statusLabelKey, [], $lang, $baseSourceLang)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <div class="omo-decision-edit__process-dates">
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.consultation_start', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control" name="consultation_start_at" value="<?= $escape($multiDateValue($decision->get('consultation_start_at'))) ?>" <?= $multiCanEditStartDates ? '' : 'readonly' ?>></label>
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.consultation_end', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control" name="consultation_end_at" value="<?= $escape($multiDateValue($decision->get('consultation_end_at'))) ?>"></label>
                                <?php if (!$multiConsultationOnly): ?>
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.evaluation_start', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control" name="evaluation_start_at" value="<?= $escape($multiDateValue($decision->get('evaluation_start_at'))) ?>" <?= $multiCanEditStartDates ? '' : 'readonly' ?>></label>
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.evaluation_end', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control" name="evaluation_end_at" value="<?= $escape($multiDateValue($decision->get('evaluation_end_at'))) ?>"></label>
                                <?php endif; ?>
                            </div>

                            <?= omoDecisionRenderInvitationSection($decision, $multiContext, $lang, $baseSourceLang, $escape, 'omo-decision-edit__multi-invitations') ?>
                        </form>
                    </section>

                    <section class="generic-section generic-section--stack omo-decision-edit__questions-section">
                        <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.multi.questions_title', [], $lang, $baseSourceLang)) ?></h3>
                        <?php omoDecisionRenderEditorGroupSwitch($multiContext, $decision, $selectedGroup, $decisionGroups, $lang, $baseSourceLang, $escape); ?>

                        <div class="omo-decision-edit__question-panels" data-omo-decision-question-panels>
                            <?php $renderedQuestionAssets = []; ?>
                            <?php foreach ($decisionGroups as $questionIndex => $groupItem): ?>
                                <?php
                                $groupId = (int)$groupItem->getId();
                                $groupMethod = DecisionProcess::normalizeEvaluationMethod($groupItem->get('evaluation_method'));
                                $groupDefinition = omoDecisionGetModuleDefinition($groupMethod, (int)$context['organizationId']);
                                $groupRenderFunction = is_array($groupDefinition) ? (string)($groupDefinition['render_function'] ?? '') : '';
                                $groupContext = $context;
                                $groupContext['decisionGroup'] = $groupItem;
                                $groupContext['decisionGroupId'] = $groupId;
                                $groupContext['multiQuestionEditor'] = true;
                                $isActiveQuestion = $groupId === $multiSelectedGroupId;
                                ?>
                                <div
                                    class="omo-decision-edit__question-panel"
                                    data-omo-decision-question-panel
                                    data-question-key="group-<?= $groupId ?>"
                                    data-question-method="<?= $escape($groupMethod) ?>"
                                    <?= $isActiveQuestion ? '' : 'hidden' ?>
                                >
                                    <?php if ($groupRenderFunction !== '' && function_exists($groupRenderFunction)): ?>
                                        <?php
                                        $groupRenderFunction([
                                            'context' => $groupContext,
                                            'decision' => $decision,
                                            'organization' => $organization,
                                            'effectiveHolon' => $effectiveHolon,
                                            'lang' => $lang,
                                            'sourceLang' => $baseSourceLang,
                                            'escape' => $escape,
                                            'selectedMethod' => $groupMethod,
                                            'embeddedQuestion' => true,
                                            'includeAssets' => empty($renderedQuestionAssets[$groupMethod]),
                                        ]);
                                        $renderedQuestionAssets[$groupMethod] = true;
                                        ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="omo-decision-edit__feedback" data-omo-decision-multi-feedback aria-live="polite"></div>
                    </section>
                </div>
            <?php elseif ($isGovernanceWorkflow): ?>
                <?php require __DIR__ . '/governance/edit.php'; ?>
            <?php elseif ((!$isEditing && $selectedMethod === '') || ($isEditing && $groupAction === 'create' && $selectedMethod === '')): ?>
            <section class="generic-section generic-section--stack generic-form-section">
                <div class="omo-decision-edit__section-head generic-form-section__heading">
                    <div class="generic-form-section__copy">
                        <div class="generic-heading-with-help">
                            <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.choose_title', [], $lang, $baseSourceLang)) ?></h3>
                            <details class="generic-context-help">
                                <summary aria-label="<?= $escape($isEditing ? 'Choisissez la methode du nouveau groupe.' : t('decisions.edit.choose_text', [], $lang, $baseSourceLang)) ?>">?</summary>
                                <div class="generic-context-help__content"><?= $escape($isEditing ? 'Choisissez la methode du nouveau groupe.' : t('decisions.edit.choose_text', [], $lang, $baseSourceLang)) ?></div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="omo-decision-edit__module-grid">
                <?php if (!$isEditing && !empty($decisionSettings['governance']['enabled']) && $effectiveHolon instanceof \dbObject\Holon && in_array((int)$effectiveHolon->get('IDtypeholon'), [1, 2], true)): ?>
                    <article class="generic-soft-panel generic-soft-panel--stack omo-decision-edit__module-card omo-decision-edit__module-card--governance is-available">
                        <div class="omo-decision-edit__module-copy">
                            <div class="omo-decision-edit__module-headline"><h4 class="generic-card-title"><?= $escape(t('decisions.edit.governance.label', [], $lang, $baseSourceLang)) ?></h4></div>
                            <details class="generic-context-help">
                                <summary aria-label="<?= $escape(t('decisions.edit.governance.description', [], $lang, $baseSourceLang)) ?>">?</summary>
                                <div class="generic-context-help__content"><?= $escape(t('decisions.edit.governance.description', [], $lang, $baseSourceLang)) ?></div>
                            </details>
                        </div>
                        <a
                            class="generic-action-button generic-action-button--main omo-decision-edit__module-action"
                            href="<?= $escape(omoDecisionBuildGovernanceEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'])) ?>"
                            data-omo-decision-editor-link
                            data-omo-decision-editor-title="<?= $escape(t('decisions.edit.governance.label', [], $lang, $baseSourceLang)) ?>"
                        ><?= $escape(t('decisions.edit.governance.open', [], $lang, $baseSourceLang)) ?></a>
                    </article>
                <?php endif; ?>

                    <?php foreach ($registry as $methodKey => $definition): ?>
                        <?php
                        if (empty($definition['available'])) {
                            continue;
                        }
                        $methodUrl = omoDecisionBuildEditorUrl(
                            (int)$context['organizationId'],
                            (int)$context['targetHolonId'],
                            $isEditing ? (int)$decision->getId() : 0,
                            (string)$methodKey,
                            'manage',
                            0,
                            $isEditing ? 'create' : ''
                        );
                        $isAvailable = true;
                        ?>
                        <article class="generic-soft-panel generic-soft-panel--stack omo-decision-edit__module-card<?= $isAvailable ? ' is-available' : '' ?>">
                            <div class="omo-decision-edit__module-copy">
                                <div class="omo-decision-edit__module-headline">
                                    <h4 class="generic-card-title"><?= $escape(t((string)$definition['label_key'], [], $lang, $baseSourceLang)) ?></h4>
                                    <?php if (!$isAvailable): ?>
                                    <span class="omo-decision-edit__module-badge"><?= $escape(t('decisions.edit.choose_later', [], $lang, $baseSourceLang)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <details class="generic-context-help">
                                    <summary aria-label="<?= $escape(t((string)$definition['description_key'], [], $lang, $baseSourceLang)) ?>">?</summary>
                                    <div class="generic-context-help__content"><?= $escape(t((string)$definition['description_key'], [], $lang, $baseSourceLang)) ?></div>
                                </details>
                            </div>

                            <?php if ($isAvailable): ?>
                            <a
                                class="generic-action-button generic-action-button--main omo-decision-edit__module-action"
                                href="<?= $escape($methodUrl) ?>"
                                data-omo-decision-editor-link
                                data-omo-decision-editor-title="<?= $escape($isEditing ? t('decisions.edit.edit_title', [], $lang, $baseSourceLang) : t('decisions.edit.create_title', [], $lang, $baseSourceLang)) ?>"
                            >
                                <?= $escape(t('decisions.edit.method.open', [], $lang, $baseSourceLang)) ?>
                            </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (!$isEditing): ?>
                <details class="omo-decision-edit__import-panel">
                    <summary class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.import.title', [], $lang, $baseSourceLang)) ?></summary>
                    <form
                        class="generic-form-stack"
                        action="/omo/api/decision/import.php"
                        method="post"
                        enctype="multipart/form-data"
                        data-omo-decision-import-form
                    >
                        <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                        <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                        <input type="hidden" name="intent" value="manage">
                        <p class="omo-decision-edit__lead"><?= $escape(t('decisions.edit.import.text', [], $lang, $baseSourceLang)) ?></p>
                        <label class="omo-decision-edit__import-field generic-form-field">
                            <span class="generic-form-label"><?= $escape(t('decisions.edit.import.file_label', [], $lang, $baseSourceLang)) ?></span>
                            <input type="file" class="generic-form-control" name="import_file" accept=".csv,.json,.xml" required>
                        </label>
                        <div class="omo-decision-edit__import-actions generic-form-actions">
                            <button type="submit" class="generic-action-button generic-action-button--secondary" data-omo-decision-import-submit>
                                <?= $escape(t('decisions.edit.import.button', [], $lang, $baseSourceLang)) ?>
                            </button>
                            <div class="omo-decision-edit__feedback" data-omo-decision-import-feedback aria-live="polite"></div>
                        </div>
                    </form>
                </details>
                <?php endif; ?>
                
            </section>
            <?php elseif ($selectedDefinition && ($isEditing || !empty($selectedDefinition['available'])) && !empty($selectedDefinition['render_function']) && function_exists((string)$selectedDefinition['render_function'])): ?>
                <?php
                $renderFunction = (string)$selectedDefinition['render_function'];
                $renderFunction([
                    'context' => $context,
                    'decision' => $decision,
                    'organization' => $organization,
                    'effectiveHolon' => $effectiveHolon,
                    'lang' => $lang,
                    'sourceLang' => $baseSourceLang,
                    'escape' => $escape,
                    'selectedMethod' => $selectedMethod,
                    'forceNewGroup' => $isEditing && $groupAction === 'create',
                ]);
                ?>
            <?php else: ?>
            <section class="generic-section generic-section--stack generic-form-section">
                <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.unsupported_title', [], $lang, $baseSourceLang)) ?></h3>
                <p class="omo-decision-edit__text"><?= $escape(t('decisions.edit.unsupported_text', [], $lang, $baseSourceLang)) ?></p>
                <?php if ($isEditing): ?>
                <p class="omo-decision-edit__text omo-decision-edit__muted"><?= $escape(t('decisions.edit.method.locked', [], $lang, $baseSourceLang)) ?></p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    function openEditorUrl(url, title) {
        if (!url) {
            return;
        }

        if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
            window.omoDecisionOpenNestedDrawer(title || 'Prises de decision', url, '');
            return;
        }

        if (typeof window.commonTopbarOpenDrawer === 'function') {
            window.commonTopbarOpenDrawer(title || 'Prises de decision', url, 'fetch');
            return;
        }

        window.location.href = url;
    }

    function notifyQuestionSwitch(message, type) {
        if (typeof window.commonNotify === 'function') {
            window.commonNotify(message, type || 'error');
            return;
        }

        window.alert(message);
    }

    function executeQuestionScripts(container) {
        const scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        let sequence = Promise.resolve();

        scripts.forEach(function (script) {
            sequence = sequence.then(function () {
                return new Promise(function (resolve) {
                    const replacement = document.createElement('script');
                    Array.prototype.forEach.call(script.attributes, function (attribute) {
                        replacement.setAttribute(attribute.name, attribute.value);
                    });

                    if (replacement.src) {
                        replacement.async = false;
                        replacement.addEventListener('load', resolve, {once: true});
                        replacement.addEventListener('error', resolve, {once: true});
                    } else {
                        replacement.textContent = script.textContent || '';
                    }

                    script.parentNode.replaceChild(replacement, script);
                    if (!replacement.src) {
                        resolve();
                    }
                });
            });
        });

        return sequence;
    }

    const questionFormSelector = [
        'form[data-omo-decision-consent-form]',
        'form[data-omo-decision-vote-form]',
        'form[data-omo-decision-majority-judgment-form]'
    ].join(',');
    const unsavedQuestionLabel = <?= json_encode(t('decisions.edit.groups.unsaved', [], $lang, $baseSourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const questionItemTemplate = <?= json_encode(t('decisions.edit.groups.item', ['index' => '__INDEX__'], $lang, $baseSourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function getCompleteFormData(form) {
        const data = new FormData(form);
        form.querySelectorAll('[name]:disabled').forEach(function (field) {
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                return;
            }
            if (field.name) {
                if (field.name.slice(-2) === '[]') {
                    data.append(field.name, field.value || '');
                } else {
                    data.set(field.name, field.value || '');
                }
            }
        });
        return data;
    }

    function appendSignature(values, prefix, form) {
        getCompleteFormData(form).forEach(function (value, key) {
            if (typeof value === 'string') {
                values.push([prefix, key, value]);
                return;
            }
            values.push([prefix, key, value.name || '', value.size || 0, value.lastModified || 0]);
        });
    }

    function getMultiEditorSignature(root) {
        const values = [];
        const processForm = root.querySelector('[data-omo-decision-process-form]');
        if (processForm) {
            appendSignature(values, 'process', processForm);
        }
        root.querySelectorAll('[data-omo-decision-question-panel]').forEach(function (panel) {
            const form = panel.querySelector(questionFormSelector);
            if (form) {
                appendSignature(values, panel.getAttribute('data-question-key') || 'question', form);
            }
        });
        return JSON.stringify(values);
    }

    function getActiveQuestionKey(root) {
        const panel = root.querySelector('[data-omo-decision-question-panel]:not([hidden])');
        return panel ? panel.getAttribute('data-question-key') || '' : '';
    }

    function switchLocalQuestion(root, questionKey) {
        let matched = false;
        root.querySelectorAll('[data-omo-decision-question-panel]').forEach(function (panel) {
            const active = panel.getAttribute('data-question-key') === questionKey;
            panel.hidden = !active;
            matched = matched || active;
        });
        if (!matched) {
            return false;
        }
        root.querySelectorAll('[data-omo-decision-question-link]').forEach(function (link) {
            const active = link.getAttribute('data-question-key') === questionKey;
            link.classList.toggle('is-active', active);
            if (active) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
        return true;
    }

    function makePanelIdsUnique(panel, questionKey) {
        panel.querySelectorAll('[id]').forEach(function (field) {
            const previousId = field.id;
            const nextId = previousId + '-' + questionKey;
            panel.querySelectorAll('label[for="' + CSS.escape(previousId) + '"]').forEach(function (label) {
                label.setAttribute('for', nextId);
            });
            field.id = nextId;
        });
    }

    function appendLocalQuestionTab(root, questionKey) {
        const tabs = root.querySelector('.omo-decision-edit__question-tabs');
        if (!tabs) {
            return;
        }
        const questionNumber = root.querySelectorAll('[data-omo-decision-question-panel]').length;
        const link = document.createElement('a');
        link.href = '#';
        link.className = 'omo-decision-edit__question-tab';
        link.setAttribute('data-omo-decision-question-link', '');
        link.setAttribute('data-question-key', questionKey);
        link.textContent = String(questionItemTemplate || 'Question __INDEX__').replace('__INDEX__', String(questionNumber));
        const badge = document.createElement('span');
        badge.className = 'omo-decision-edit__question-tab-badge';
        badge.textContent = unsavedQuestionLabel;
        link.appendChild(badge);
        tabs.appendChild(link);
    }

    function addLocalQuestion(root, button) {
        const url = button.getAttribute('data-fragment-url') || '';
        const nav = button.closest('[data-omo-decision-question-nav]');
        const errorMessage = root.getAttribute('data-error-message') || 'Impossible d’ajouter cette question.';
        if (!url || (nav && nav.getAttribute('aria-busy') === 'true')) {
            return;
        }
        if (nav) {
            nav.setAttribute('aria-busy', 'true');
        }
        fetch(typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(errorMessage);
                }
                return response.text();
            })
            .then(function (html) {
                const documentFragment = new DOMParser().parseFromString(html, 'text/html');
                const source = documentFragment.querySelector('[data-omo-decision-question-fragment]');
                const panels = root.querySelector('[data-omo-decision-question-panels]');
                if (!source || !panels) {
                    throw new Error(errorMessage);
                }
                const questionKey = 'new-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
                source.removeAttribute('data-omo-decision-question-fragment');
                source.setAttribute('data-omo-decision-question-panel', '');
                source.setAttribute('data-question-key', questionKey);
                source.classList.add('omo-decision-edit__question-panel');
                makePanelIdsUnique(source, questionKey);
                panels.appendChild(source);
                appendLocalQuestionTab(root, questionKey);
                switchLocalQuestion(root, questionKey);
                return executeQuestionScripts(source);
            })
            .catch(function (error) {
                notifyQuestionSwitch(error && error.message ? error.message : errorMessage, 'error');
            })
            .finally(function () {
                if (nav) {
                    nav.removeAttribute('aria-busy');
                    const chooser = nav.querySelector('[data-omo-decision-question-methods]');
                    const toggle = nav.querySelector('[data-omo-decision-question-add-toggle]');
                    if (chooser) {
                        chooser.hidden = true;
                    }
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });
    }

    function validateMultiEditor(root) {
        const processForm = root.querySelector('[data-omo-decision-process-form]');
        if (processForm && !processForm.reportValidity()) {
            return false;
        }
        const panels = Array.prototype.slice.call(root.querySelectorAll('[data-omo-decision-question-panel]'));
        for (let index = 0; index < panels.length; index += 1) {
            const form = panels[index].querySelector(questionFormSelector);
            if (form && !form.checkValidity()) {
                switchLocalQuestion(root, panels[index].getAttribute('data-question-key') || '');
                form.reportValidity();
                return false;
            }
        }
        return true;
    }

    function saveMultiEditor(root) {
        if (root.getAttribute('aria-busy') === 'true' || !validateMultiEditor(root)) {
            return;
        }
        const processForm = root.querySelector('[data-omo-decision-process-form]');
        const saveButton = getMultiSaveButton(root);
        const feedback = root.querySelector('[data-omo-decision-multi-feedback]');
        const activeQuestionKey = getActiveQuestionKey(root);
        const originalStatus = root.getAttribute('data-original-status') || 'draft';
        const commonData = getCompleteFormData(processForm);
        const panels = Array.prototype.slice.call(root.querySelectorAll('[data-omo-decision-question-panel]'));
        const activeQuestionIndex = panels.findIndex(function (panel) {
            return (panel.getAttribute('data-question-key') || '') === activeQuestionKey;
        });
        const batchData = new FormData();
        let activeRedirectUrl = '';
        let lastRedirectUrl = '';

        root.setAttribute('aria-busy', 'true');
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = root.getAttribute('data-saving-label') || 'Enregistrement…';
        }
        if (feedback) {
            feedback.textContent = '';
        }

        panels.forEach(function (panel, index) {
            const form = panel.querySelector(questionFormSelector);
            const formData = getCompleteFormData(form);
            const serialized = new URLSearchParams();
            commonData.forEach(function (value, key) {
                formData.set(key, value);
            });
            if (index < panels.length - 1) {
                formData.set('status', originalStatus);
            }
            formData.forEach(function (value, key) {
                serialized.append(key, typeof value === 'string' ? value : '');
            });
            batchData.append('groups[]', serialized.toString());
        });

        fetch('/omo/api/decision/save_multi.php', {
            method: 'POST',
            body: batchData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (data) {
                    if (!response.ok || !data || !data.status) {
                        throw new Error(data && data.message ? data.message : (root.getAttribute('data-error-message') || 'Erreur.'));
                    }
                    return data;
                });
            })
            .then(function (data) {
                const results = Array.isArray(data.results) ? data.results : [];
                results.forEach(function (result, index) {
                    lastRedirectUrl = String(result && result.redirectUrl ? result.redirectUrl : lastRedirectUrl);
                    if (index === activeQuestionIndex) {
                        activeRedirectUrl = String(result && result.redirectUrl ? result.redirectUrl : '');
                    }
                });
                root.dataset.multiInitialSignature = getMultiEditorSignature(root);
                notifyQuestionSwitch(root.getAttribute('data-saved-message') || 'Scrutin enregistré.', 'success');
                openEditorUrl(activeRedirectUrl || lastRedirectUrl, 'Prises de decision');
            })
            .catch(function (error) {
                if (feedback) {
                    feedback.textContent = error && error.message ? error.message : (root.getAttribute('data-error-message') || 'Erreur.');
                }
            })
            .finally(function () {
                root.removeAttribute('aria-busy');
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = root.getAttribute('data-save-label') || 'Enregistrer le scrutin';
                }
            });
    }

    function getMultiSaveButton(root) {
        const editorId = root.id || '';

        if (editorId !== '') {
            return document.querySelector('[data-omo-decision-multi-save-for="' + CSS.escape(editorId) + '"]');
        }

        return null;
    }

    document.querySelectorAll('[data-omo-decision-multi-editor]').forEach(function (root) {
        if (root.dataset.omoDecisionMultiReady === '1') {
            return;
        }
        root.dataset.omoDecisionMultiReady = '1';
        [
            window.omoDecisionConsentInit,
            window.omoDecisionVoteInit,
            window.omoDecisionMajorityJudgmentInit
        ].forEach(function (initializer) {
            if (typeof initializer === 'function') {
                initializer(root);
            }
        });
        root.dataset.multiInitialSignature = getMultiEditorSignature(root);

        root.addEventListener('click', function (event) {
            const questionLink = event.target.closest('[data-omo-decision-question-link]');
            if (questionLink && root.contains(questionLink)) {
                event.preventDefault();
                switchLocalQuestion(root, questionLink.getAttribute('data-question-key') || '');
                return;
            }
            const toggle = event.target.closest('[data-omo-decision-question-add-toggle]');
            if (toggle && root.contains(toggle)) {
                const chooser = toggle.parentNode.querySelector('[data-omo-decision-question-methods]');
                if (chooser) {
                    chooser.hidden = !chooser.hidden;
                    toggle.setAttribute('aria-expanded', chooser.hidden ? 'false' : 'true');
                }
                return;
            }
            const methodButton = event.target.closest('[data-omo-decision-question-add-method]');
            if (methodButton && root.contains(methodButton)) {
                addLocalQuestion(root, methodButton);
                return;
            }
            const invitationButton = event.target.closest('[data-omo-decision-invitations-open]');
            if (invitationButton && root.contains(invitationButton)) {
                const url = invitationButton.getAttribute('data-omo-decision-invitations-url') || '';
                if (url && typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        invitationButton.getAttribute('data-omo-decision-invitations-title') || invitationButton.textContent || 'Invitations',
                        url,
                        'fetch'
                    );
                }
                return;
            }
            const invitationSendButton = event.target.closest('[data-omo-decision-invitations-send-open]');
            if (invitationSendButton && root.contains(invitationSendButton)) {
                const url = invitationSendButton.getAttribute('data-omo-decision-invitations-send-url') || '';
                if (url && typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        invitationSendButton.getAttribute('data-omo-decision-invitations-send-title') || invitationSendButton.textContent || 'Invitations',
                        url,
                        'fetch'
                    );
                }
                return;
            }
            const saveButton = event.target.closest('[data-omo-decision-multi-save]');
            if (saveButton && root.contains(saveButton)) {
                saveMultiEditor(root);
            }
        });

        root.addEventListener('submit', function (event) {
            if (event.target.matches(questionFormSelector)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                saveMultiEditor(root);
            }
        }, true);

        document.addEventListener('click', function (event) {
            if (!document.contains(root) || !event.target.closest('[data-topbar-drawer-close]')) {
                return;
            }
            if (root.dataset.multiInitialSignature === getMultiEditorSignature(root)) {
                return;
            }
            if (!window.confirm(root.getAttribute('data-unsaved-warning') || 'Des modifications ne sont pas enregistrées.')) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    });

    if (!window.omoDecisionMultiHeaderSaveReady) {
        window.omoDecisionMultiHeaderSaveReady = true;
        document.addEventListener('click', function (event) {
            const saveButton = event.target.closest('[data-omo-decision-multi-save-for]');
            const editorId = saveButton ? saveButton.getAttribute('data-omo-decision-multi-save-for') || '' : '';
            const root = editorId !== '' ? document.getElementById(editorId) : null;

            if (!root) {
                return;
            }

            event.preventDefault();
            saveMultiEditor(root);
        });
    }

    if (!window.omoDecisionMultiBeforeUnloadReady) {
        window.omoDecisionMultiBeforeUnloadReady = true;
        window.addEventListener('beforeunload', function (event) {
            const dirty = Array.prototype.some.call(document.querySelectorAll('[data-omo-decision-multi-editor]'), function (root) {
                return root.dataset.multiInitialSignature !== getMultiEditorSignature(root);
            });
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }

    document.querySelectorAll('[data-omo-decision-editor-link]').forEach(function (link) {
        if (link.hasAttribute('data-omo-decision-question-link')) {
            return;
        }
        if (link.dataset.omoDecisionEditorReady === '1') {
            return;
        }

        link.dataset.omoDecisionEditorReady = '1';
        link.addEventListener('click', function (event) {
            event.preventDefault();
            openEditorUrl(
                link.getAttribute('href') || '',
                link.getAttribute('data-omo-decision-editor-title') || 'Prises de decision'
            );
        });
    });

    document.querySelectorAll('[data-omo-decision-import-form]').forEach(function (form) {
        if (form.dataset.omoDecisionImportReady === '1') {
            return;
        }

        form.dataset.omoDecisionImportReady = '1';

        var submitButton = form.querySelector('[data-omo-decision-import-submit]');
        var feedback = form.querySelector('[data-omo-decision-import-feedback]');
        var fileInput = form.querySelector('input[type="file"][name="import_file"]');
        var defaultLabel = submitButton ? submitButton.textContent : '';

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!fileInput || !fileInput.files || !fileInput.files.length) {
                if (feedback) {
                    feedback.textContent = <?= json_encode(t('decisions.edit.import.no_file', [], $lang, $baseSourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }
                return;
            }

            var formData = new FormData(form);
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = <?= json_encode(t('decisions.edit.import.loading', [], $lang, $baseSourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            }
            if (feedback) {
                feedback.textContent = '';
            }

            fetch(form.getAttribute('action') || '/omo/api/decision/import.php', {
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
                        throw new Error(
                            result.data && result.data.message
                                ? String(result.data.message)
                                : <?= json_encode(t('decisions.edit.import.error', [], $lang, $baseSourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                        );
                    }

                    openEditorUrl(
                        String(result.data.redirectUrl || ''),
                        String(result.data.drawerTitle || 'Prises de decision')
                    );
                })
                .catch(function (error) {
                    if (feedback) {
                        feedback.textContent = error && error.message
                            ? error.message
                            : <?= json_encode(t('decisions.edit.import.error', [], $lang, $baseSourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                    }
                })
                .finally(function () {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = defaultLabel;
                    }
                });
        });
    });
})();
</script>

<style>
.omo-decision-edit__stack {
    --generic-form-gap: var(--generic-space-4, 16px);
}

.omo-decision-edit__summary-grid,
.omo-decision-edit__module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.omo-decision-edit__module-copy {
    display: grid;
    gap: 8px;
}

.omo-decision-edit__module-card {
    justify-content: space-between;
    min-height: 150px;
}

.omo-decision-edit__module-card.is-available {
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 28%, white);
}

.omo-decision-edit__module-card--governance {
    background: color-mix(in srgb, var(--color-primary, #2563eb) 7%, var(--color-surface, #ffffff));
}

.omo-decision-edit__module-headline {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.omo-decision-edit__module-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-text-light, #64748b) 12%, white);
    color: var(--color-text-light, #475569);
    font-size: 12px;
    white-space: nowrap;
}

.omo-decision-edit__module-action {
    align-self: end;
}

.omo-decision-edit__import-panel {
    padding-top: 4px;
    border-top: 1px solid var(--color-border, #d1d5db);
}

.omo-decision-edit__import-panel > summary {
    padding: 12px 0;
    color: var(--color-text-light, #475569);
    cursor: pointer;
}

.omo-decision-edit__import-panel > form {
    padding: 4px 0 12px;
}

.omo-decision-edit__feedback {
    min-height: 20px;
    color: var(--color-text-light, #475569);
    line-height: 1.5;
}

.omo-decision-edit__muted {
    font-size: 14px;
}
</style>
