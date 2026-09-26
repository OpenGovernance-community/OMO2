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
            'text' => 'Sans espace',
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
            'text' => 'Espace introuvable pour cette organisation.',
        'context' => 'Error when the requested holon is invalid.',
    ],
    'decisions.edit.context.holon_denied' => [
            'text' => 'Accès refusé à cet espace.',
        'context' => 'Error when the user cannot view the requested holon.',
    ],
    'decisions.edit.context.holon_manage_denied' => [
            'text' => 'Vous n’avez pas les droits nécessaires pour créer une prise de décision dans cet espace.',
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
        'text' => 'Informations du scrutin',
        'context' => 'Heading for the shared decision process information and schedule.',
    ],
    'decisions.edit.multi.general_title' => [
        'text' => 'Paramètres généraux',
        'context' => 'Heading for settings shared by every question in a multi-question decision.',
    ],
    'decisions.edit.multi.general_configure' => [
        'text' => 'Configurer',
        'context' => 'Button opening the shared decision settings popup.',
    ],
    'decisions.edit.multi.general_cancel' => [
        'text' => 'Annuler',
        'context' => 'Button closing the shared decision settings popup without applying changes.',
    ],
    'decisions.edit.multi.general_apply' => [
        'text' => 'Appliquer',
        'context' => 'Button applying changes made in the shared decision settings popup.',
    ],
    'decisions.edit.multi.general_anonymous' => [
        'text' => 'Vote nominatif',
        'context' => 'Summary label for whether votes are named in shared decision settings.',
    ],
    'decisions.edit.multi.general_allow_anonymous_votes' => [
        'text' => 'Autoriser les participants à choisir un vote anonyme',
        'context' => 'Shared setting allowing participants to choose an anonymous vote when the decision is named.',
    ],
    'decisions.edit.multi.general_consultation_proposals' => [
        'text' => 'Autoriser les propositions pendant la consultation',
        'context' => 'Shared setting allowing proposals during the consultation phase.',
    ],
    'decisions.edit.multi.general_proposal_discussions' => [
        'text' => 'Autoriser les discussions des propositions',
        'context' => 'Shared setting allowing discussions on proposals.',
    ],
    'decisions.edit.multi.general_yes' => [
        'text' => 'Oui',
        'context' => 'Affirmative value in shared settings summaries.',
    ],
    'decisions.edit.multi.general_no' => [
        'text' => 'Non',
        'context' => 'Negative value in shared settings summaries.',
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
    'decisions.edit.owner_intermediate_results_access' => [
        'text' => 'Afficher les résultats intermédiaires',
        'context' => 'Label for allowing the decision organizer to view intermediate results before the end of the vote.',
    ],
    'decisions.edit.owner_intermediate_results_access_explicit' => [
        'text' => 'Afficher les résultats intermédiaires à l’organisateur',
        'context' => 'Explicit label for allowing the decision organizer to view intermediate results before the end of the vote.',
    ],
    'decisions.edit.participant_intermediate_results_access' => [
        'text' => 'Afficher les résultats intermédiaires aux participants ayant répondu à toutes les propositions',
        'context' => 'Label for allowing participants with a complete response to view intermediate results before the end of the vote.',
    ],
    'decisions.edit.participant_responses_editable' => [
        'text' => 'Permettre aux participants de modifier leur réponse après soumission',
        'context' => 'Label for allowing participants to update a submitted response while the vote remains open.',
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
    'decisions.edit.lifecycle.evaluation_start_confirmation' => [
        'text' => 'Le statut « En évaluation » n’est pas compatible avec la date de début d’évaluation définie au {date}. Voulez-vous vraiment commencer maintenant ? Les dates des phases d’élaboration et d’évaluation seront ajustées.',
        'context' => 'Confirmation before manually starting a multi-question decision before its scheduled date.',
    ],
    'decisions.edit.lifecycle.consultation_start_confirmation' => [
        'text' => 'Le statut « En élaboration » n’est pas compatible avec la date de début d’élaboration définie au {date}. Voulez-vous vraiment commencer maintenant ? La date de début de la phase d’élaboration sera ajustée.',
        'context' => 'Confirmation before manually starting a multi-question decision consultation before its scheduled date.',
    ],
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
} elseif (!empty($context['duplicateDecision']) && $context['duplicateDecision'] instanceof DecisionProcess) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($context['duplicateDecision']->get('evaluation_method'));
} elseif ($requestedMethod !== '' && omoDecisionGetModuleDefinition($requestedMethod, (int)($context['organizationId'] ?? 0))) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($requestedMethod);
}

$organizationIdForModules = (int)($context['organizationId'] ?? 0);
$moduleDefinition = $selectedMethod !== '' ? omoDecisionGetModuleDefinition($selectedMethod, $organizationIdForModules) : null;
$moduleDefinitionsToLoad = [];
$contextDecision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$loadEveryEditorModule = ($contextDecision instanceof DecisionProcess || (($context['duplicateDecision'] ?? null) instanceof DecisionProcess))
    && (($context['intent'] ?? '') === 'manage')
    && $groupAction === ''
    && !($contextDecision instanceof DecisionProcess && $contextDecision->isGovernanceWorkflow());

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
$duplicateDecision = ($context['duplicateDecision'] ?? null) instanceof DecisionProcess
    ? $context['duplicateDecision']
    : null;
$duplicateDecisionGroups = is_iterable($context['duplicateDecisionGroups'] ?? null)
    ? $context['duplicateDecisionGroups']
    : [];
$isDuplicate = !($decision instanceof DecisionProcess) && $duplicateDecision instanceof DecisionProcess;
$effectiveHolon = $context['effectiveHolon'];
$intent = (string)($context['intent'] ?? 'manage');
$isEditing = $decision instanceof DecisionProcess;
$decisionGroups = $isEditing
    ? $decision->getDecisionGroups(false)
    : ($isDuplicate ? $duplicateDecisionGroups : []);
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
$useMultiQuestionEditor = ($isEditing || $isDuplicate)
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
<link rel="stylesheet" href="/common/choice/decision_cards.css?v=20260923-compact-editor">
<div class="omo-decision-edit omo-panel-view">
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-decision-edit__stack generic-drawer-content generic-form-stack generic-form-stack--compact">
            

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
                $multiDecision = $isDuplicate ? $duplicateDecision : $decision;
                $multiStatus = DecisionProcess::normalizeStatus($multiDecision->get('status'));
                $multiConsultationOnly = DecisionProcess::normalizeEvaluationMethod($multiDecision->get('evaluation_method')) === DecisionProcess::METHOD_CONSULTATION_ONLY;
                $multiHasVotingGroup = false;
                foreach ($decisionGroups as $multiGroup) {
                    if (DecisionProcess::normalizeEvaluationMethod($multiGroup->get('evaluation_method')) !== DecisionProcess::METHOD_CONSULTATION_ONLY) {
                        $multiHasVotingGroup = true;
                        break;
                    }
                }
                $multiOwnerIntermediateResultsAccess = $multiDecision->hasOwnerIntermediateResultsAccess();
                $multiParticipantIntermediateResultsAccess = $multiDecision->hasParticipantIntermediateResultsAccess();
                $multiParticipantResponsesEditable = $multiDecision->areParticipantResponsesEditable();
                $multiCoreLocked = !$isDuplicate && $multiDecision->hasEvaluationStarted();
                $multiStartDatesLocked = !$isDuplicate && ($multiCoreLocked || $multiDecision->hasSubmittedResponses());
                $multiResultsMode = !$isDuplicate
                    && in_array($multiStatus, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true);
                $multiCanEditStructure = !$multiResultsMode && !$multiCoreLocked;
                $multiCanEditStartDates = !$multiResultsMode && !$multiStartDatesLocked;
                $multiVisibilityState = omoDecisionResolveVisibilityEditorState($multiDecision, $context);
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
                $multiContext['isDuplicate'] = $isDuplicate;
                $multiEditorDomId = 'omo-decision-multi-editor-' . ($isDuplicate ? 'duplicate-' . (int)$duplicateDecision->getId() : (int)$decision->getId());
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
                    data-omo-decision-lifecycle-confirm-template="<?= $escape(t('decisions.edit.lifecycle.evaluation_start_confirmation', [], $lang, $baseSourceLang)) ?>"
                    data-omo-decision-lifecycle-consultation-confirm-template="<?= $escape(t('decisions.edit.lifecycle.consultation_start_confirmation', [], $lang, $baseSourceLang)) ?>"
                >
                    <script src="/omo/api/decision/modules/lifecycle_status.js"></script>
                    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-decision-edit__process-settings">
                        <h3 class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.multi.process_title', [], $lang, $baseSourceLang)) ?></h3>
                        <form class="generic-form-stack generic-form-stack--compact" data-omo-decision-process-form>
                            <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                            <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                            <input type="hidden" name="id" value="<?= $escape($isDuplicate ? 0 : (int)$decision->getId()) ?>">

                            <label class="generic-form-field">
                                <span class="generic-form-label"><?= $escape(t('decisions.edit.multi.process_name', [], $lang, $baseSourceLang)) ?></span>
                                <input type="text" class="generic-form-control generic-form-control--compact" name="process_title" required maxlength="190" value="<?= $escape(trim((string)$multiDecision->get('title'))) ?>" <?= $multiCanEditStructure ? '' : 'readonly' ?>>
                            </label>

                            <label class="generic-form-field">
                                <span class="generic-form-label"><?= $escape(t('decisions.edit.multi.process_description', [], $lang, $baseSourceLang)) ?></span>
                                <textarea class="generic-form-control generic-form-control--compact" name="process_description" rows="4" <?= $multiCanEditStructure ? '' : 'readonly' ?>><?= $escape(trim((string)$multiDecision->get('description'))) ?></textarea>
                            </label>

                            <div class="omo-decision-edit__process-primary">
                                <label class="generic-form-field">
                                    <span class="generic-form-label"><?= $escape(t('decisions.edit.visibility.label', [], $lang, $baseSourceLang)) ?></span>
                                    <select class="generic-form-control generic-form-control--compact" name="visibility_type" <?= $multiCanEditStructure ? '' : 'disabled' ?>>
                                        <?php foreach (($multiVisibilityState['visibilityOptions'] ?? []) as $optionValue => $optionLabel): ?>
                                        <option value="<?= $escape((string)$optionValue) ?>" <?= $optionValue === ($multiVisibilityState['selectedVisibilityType'] ?? DecisionProcess::getDefaultVisibilityType()) ? 'selected' : '' ?> <?= !empty(($multiVisibilityState['disabledVisibilityTypes'] ?? [])[$optionValue]) ? 'disabled' : '' ?>><?= $escape((string)$optionLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="generic-form-field">
                                    <span class="generic-form-label"><?= $escape(t('decisions.edit.multi.status', [], $lang, $baseSourceLang)) ?></span>
                                    <select class="generic-form-control generic-form-control--compact" name="status">
                                        <?php foreach ($multiStatusOptions as $statusValue => $statusLabelKey): ?>
                                        <?php if ($multiConsultationOnly && !in_array($statusValue, [DecisionProcess::STATUS_DRAFT, DecisionProcess::STATUS_SCHEDULED, DecisionProcess::STATUS_CONSULTATION], true)) continue; ?>
                                        <option value="<?= $escape($statusValue) ?>" <?= $multiStatus === $statusValue ? 'selected' : '' ?>><?= $escape(t($statusLabelKey, [], $lang, $baseSourceLang)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <div class="generic-form-grid">
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.consultation_start', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control generic-form-control--compact" name="consultation_start_at" value="<?= $escape($isDuplicate ? '' : $multiDateValue($multiDecision->get('consultation_start_at'))) ?>" <?= $multiCanEditStartDates ? '' : 'readonly' ?>></label>
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.consultation_end', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control generic-form-control--compact" name="consultation_end_at" value="<?= $escape($isDuplicate ? '' : $multiDateValue($multiDecision->get('consultation_end_at'))) ?>"></label>
                                <?php if (!$multiConsultationOnly): ?>
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.evaluation_start', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control generic-form-control--compact" name="evaluation_start_at" value="<?= $escape($isDuplicate ? '' : $multiDateValue($multiDecision->get('evaluation_start_at'))) ?>" <?= $multiCanEditStartDates ? '' : 'readonly' ?>></label>
                                <label class="generic-form-field"><span class="generic-form-label"><?= $escape(t('decisions.edit.multi.evaluation_end', [], $lang, $baseSourceLang)) ?></span><input type="datetime-local" class="generic-form-control generic-form-control--compact" name="evaluation_end_at" value="<?= $escape($isDuplicate ? '' : $multiDateValue($multiDecision->get('evaluation_end_at'))) ?>"></label>
                                <?php endif; ?>
                            </div>

                            <?php if ($multiHasVotingGroup): ?>
                            <input type="hidden" name="owner_intermediate_results_access" value="<?= $multiOwnerIntermediateResultsAccess ? '1' : '0' ?>" data-omo-decision-general-hidden-owner-intermediate-results>
                            <input type="hidden" name="participant_intermediate_results_access" value="<?= $multiParticipantIntermediateResultsAccess ? '1' : '0' ?>" data-omo-decision-general-hidden-participant-intermediate-results>
                            <input type="hidden" name="participant_responses_editable" value="<?= $multiParticipantResponsesEditable ? '1' : '0' ?>" data-omo-decision-general-hidden-participant-responses-editable>
                            <section class="generic-section generic-section--stack omo-decision-edit__general-settings">
                                <div class="omo-decision-settings-title-row">
                                    <h4 class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.multi.general_title', [], $lang, $baseSourceLang)) ?></h4>
                                    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-settings-button" data-omo-decision-general-settings-open data-omo-decision-general-settings-title="<?= $escape(t('decisions.edit.multi.general_title', [], $lang, $baseSourceLang)) ?>"><?= $escape(t('decisions.edit.multi.general_configure', [], $lang, $baseSourceLang)) ?></button>
                                </div>
                                <div class="generic-soft-panel generic-soft-panel--stack generic-soft-panel--summary omo-decision-edit__general-summary">
                                    <div class="omo-decision-settings-overview">
                                        <section class="omo-decision-settings-overview__group">
                                            <span class="omo-decision-settings-overview__title"><?= $escape(t('decisions.edit.owner_intermediate_results_access', [], $lang, $baseSourceLang)) ?></span>
                                            <div class="omo-decision-settings-overview__items">
                                                <span class="omo-decision-edit__readonly-stat"><strong><?= $escape(t('decisions.edit.owner_intermediate_results_access_explicit', [], $lang, $baseSourceLang)) ?></strong><span data-omo-decision-general-owner-summary data-yes-label="<?= $escape(t('decisions.edit.multi.general_yes', [], $lang, $baseSourceLang)) ?>" data-no-label="<?= $escape(t('decisions.edit.multi.general_no', [], $lang, $baseSourceLang)) ?>"><?= $escape($multiOwnerIntermediateResultsAccess ? t('decisions.edit.multi.general_yes', [], $lang, $baseSourceLang) : t('decisions.edit.multi.general_no', [], $lang, $baseSourceLang)) ?></span></span>
                                                <span class="omo-decision-edit__readonly-stat"><strong><?= $escape(t('decisions.edit.participant_intermediate_results_access', [], $lang, $baseSourceLang)) ?></strong><span data-omo-decision-general-participant-summary data-yes-label="<?= $escape(t('decisions.edit.multi.general_yes', [], $lang, $baseSourceLang)) ?>" data-no-label="<?= $escape(t('decisions.edit.multi.general_no', [], $lang, $baseSourceLang)) ?>"><?= $escape($multiParticipantIntermediateResultsAccess ? t('decisions.edit.multi.general_yes', [], $lang, $baseSourceLang) : t('decisions.edit.multi.general_no', [], $lang, $baseSourceLang)) ?></span></span>
                                                <span class="omo-decision-edit__readonly-stat"><strong><?= $escape(t('decisions.edit.participant_responses_editable', [], $lang, $baseSourceLang)) ?></strong><span data-omo-decision-general-editable-summary data-yes-label="<?= $escape(t('decisions.edit.multi.general_yes', [], $lang, $baseSourceLang)) ?>" data-no-label="<?= $escape(t('decisions.edit.multi.general_no', [], $lang, $baseSourceLang)) ?>"><?= $escape($multiParticipantResponsesEditable ? t('decisions.edit.multi.general_yes', [], $lang, $baseSourceLang) : t('decisions.edit.multi.general_no', [], $lang, $baseSourceLang)) ?></span></span>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                <template data-omo-decision-general-settings-template>
                                    <div class="omo-decision-settings-popup" data-topbar-modal-max-width="720px">
                                        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                                            <span class="omo-decision-settings-popup__group-title"><?= $escape(t('decisions.edit.owner_intermediate_results_access', [], $lang, $baseSourceLang)) ?></span>
                                            <div class="omo-decision-settings-popup__options">
                                                <label class="omo-decision-settings-popup__option omo-decision-settings-popup__option--wide"><input type="checkbox" data-omo-decision-general-owner-intermediate-results <?= $multiOwnerIntermediateResultsAccess ? 'checked' : '' ?> <?= $multiCanEditStructure ? '' : 'disabled' ?>><span><?= $escape(t('decisions.edit.owner_intermediate_results_access_explicit', [], $lang, $baseSourceLang)) ?></span></label>
                                                <label class="omo-decision-settings-popup__option omo-decision-settings-popup__option--wide"><input type="checkbox" data-omo-decision-general-participant-intermediate-results <?= $multiParticipantIntermediateResultsAccess ? 'checked' : '' ?> <?= $multiCanEditStructure ? '' : 'disabled' ?>><span><?= $escape(t('decisions.edit.participant_intermediate_results_access', [], $lang, $baseSourceLang)) ?></span></label>
                                                <label class="omo-decision-settings-popup__option omo-decision-settings-popup__option--wide"><input type="checkbox" data-omo-decision-general-participant-responses-editable <?= $multiParticipantResponsesEditable ? 'checked' : '' ?> <?= $multiCanEditStructure ? '' : 'disabled' ?>><span><?= $escape(t('decisions.edit.participant_responses_editable', [], $lang, $baseSourceLang)) ?></span></label>
                                            </div>
                                        </section>
                                        <div class="omo-decision-settings-popup__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-general-settings-cancel><?= $escape(t('decisions.edit.multi.general_cancel', [], $lang, $baseSourceLang)) ?></button><button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-general-settings-apply <?= $multiCanEditStructure ? '' : 'disabled' ?>><?= $escape(t('decisions.edit.multi.general_apply', [], $lang, $baseSourceLang)) ?></button></div>
                                    </div>
                                </template>
                            </section>
                            <?php endif; ?>

                            <?php if (!$isDuplicate): ?>
                            <?= omoDecisionRenderInvitationSection($decision, $multiContext, $lang, $baseSourceLang, $escape, 'omo-decision-edit__multi-invitations') ?>
                            <?php endif; ?>
                        </form>
                    </section>

                    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-decision-edit__questions-section">
                        <h3 class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.multi.questions_title', [], $lang, $baseSourceLang)) ?></h3>
                        <?php if ($isDuplicate): ?>
                        <nav class="omo-decision-edit__question-nav" data-omo-decision-question-nav aria-label="<?= $escape(t('decisions.edit.groups.title', [], $lang, $baseSourceLang)) ?>">
                            <div class="omo-decision-edit__question-tabs">
                                <?php foreach ($decisionGroups as $duplicateQuestionIndex => $duplicateQuestionGroup): ?>
                                <?php $duplicateQuestionId = (int)$duplicateQuestionGroup->getId(); ?>
                                <a class="omo-decision-edit__question-tab<?= $duplicateQuestionId === $multiSelectedGroupId ? ' is-active' : '' ?>" href="#" data-omo-decision-question-link data-question-key="group-<?= $duplicateQuestionId ?>"<?= $duplicateQuestionId === $multiSelectedGroupId ? ' aria-current="page"' : '' ?>><?= $escape(t('decisions.edit.groups.item', ['index' => (string)($duplicateQuestionIndex + 1)], $lang, $baseSourceLang)) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </nav>
                        <?php else: ?>
                        <?php omoDecisionRenderEditorGroupSwitch($multiContext, $decision, $selectedGroup, $decisionGroups, $lang, $baseSourceLang, $escape); ?>
                        <?php endif; ?>

                        <div class="omo-decision-edit__question-panels" data-omo-decision-question-panels>
                            <?php $renderedQuestionAssets = []; ?>
                            <?php foreach ($decisionGroups as $questionIndex => $groupItem): ?>
                                <?php
                                $groupId = (int)$groupItem->getId();
                                $groupMethod = DecisionProcess::normalizeEvaluationMethod($groupItem->get('evaluation_method'));
                                $groupDefinition = omoDecisionGetModuleDefinition($groupMethod, (int)$context['organizationId']);
                                $groupRenderFunction = is_array($groupDefinition) ? (string)($groupDefinition['render_function'] ?? '') : '';
                                $groupContext = $context;
                                $groupContext['decisionGroup'] = $isDuplicate ? null : $groupItem;
                                $groupContext['decisionGroupId'] = $isDuplicate ? 0 : $groupId;
                                $groupContext['duplicateDecision'] = $isDuplicate ? $duplicateDecision : null;
                                $groupContext['duplicateDecisionGroup'] = $isDuplicate ? $groupItem : null;
                                $groupContext['isDuplicate'] = $isDuplicate;
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
            <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
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

                <div class="omo-decision-edit__module-grid generic-form-grid generic-form-grid--compact">
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
                        class="generic-form-stack generic-form-stack--compact"
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
                            <input type="file" class="generic-form-control generic-form-control--compact" name="import_file" accept=".csv,.json,.xml" required>
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
            <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
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

<?= commonPageScriptTags('/omo/api/decision/edit_shared.js', [
    'unsavedQuestionLabel' => t('decisions.edit.groups.unsaved', [], $lang, $baseSourceLang),
    'questionItemTemplate' => t('decisions.edit.groups.item', ['index' => '__INDEX__'], $lang, $baseSourceLang),
    'decisionsEditImportNoFile' => t('decisions.edit.import.no_file', [], $lang, $baseSourceLang),
    'decisionsEditImportLoading' => t('decisions.edit.import.loading', [], $lang, $baseSourceLang),
    'decisionsEditImportError' => t('decisions.edit.import.error', [], $lang, $baseSourceLang),
]) ?>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/decision/edit_shared.css') ?>">
