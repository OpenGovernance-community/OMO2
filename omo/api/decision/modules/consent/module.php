<?php

use dbObject\DecisionProcess;
use dbObject\DecisionGroup;
use dbObject\DecisionResponse;

require_once __DIR__ . '/shared.php';

if (!function_exists('omoDecisionConsentModuleGetSourceLang')) {
    function omoDecisionConsentModuleGetSourceLang()
    {
        return [
            'decisions.consent.title' => ['text' => 'Configurer un scrutin par consentement', 'context' => 'Title of the consent management screen.'],
            'decisions.consent.description' => ['text' => 'Créez un scrutin où chaque participant indique s’il est pour, sans objection ou en objection sur chaque proposition.', 'context' => 'Description of the consent management screen.'],
            'decisions.consent.view_title' => ['text' => 'Voir le scrutin', 'context' => 'Title of the read-only consent screen.'],
            'decisions.consent.view_description' => ['text' => 'Consultez le scrutin, ses réglages et ses propositions sans modifier sa configuration.', 'context' => 'Description of the read-only consent screen.'],
            'decisions.consent.participate_title' => ['text' => 'Participer au scrutin', 'context' => 'Title of the consent participation screen.'],
            'decisions.consent.participate_description' => ['text' => 'Prononcez-vous sur chaque proposition avec trois positions possibles.', 'context' => 'Description of the consent participation screen.'],
            'decisions.consent.change_method' => ['text' => 'Changer de méthode', 'context' => 'Secondary action to go back to the method chooser.'],
            'decisions.consent.notice.started' => ['text' => 'Le scrutin a commencé. Le titre, la description, les questions et les paramètres sont désormais verrouillés.', 'context' => 'Notice shown when the configuration is locked after evaluation starts.'],
            'decisions.consent.notice.responses' => ['text' => 'Au moins une réponse a déjà été soumise. Seuls le statut et les dates de fin restent ajustables.', 'context' => 'Notice shown when some schedule fields are also locked.'],
            'decisions.consent.notice.consultation_proposals' => ['text' => 'Les propositions restent ajustables pendant la consultation tant qu’aucune réponse n’a été soumise.', 'context' => 'Notice shown when proposal editing remains allowed.'],
            'decisions.consent.notice.results' => ['text' => 'Ce scrutin est terminé. Seule la consultation des résultats reste disponible.', 'context' => 'Notice shown when the vote is in results or archived mode.'],
            'decisions.consent.field.title' => ['text' => 'Question', 'context' => 'Label for the group title field.'],
            'decisions.consent.field.description' => ['text' => 'Description de la question', 'context' => 'Label for the group description field.'],
            'decisions.consent.field.process_title' => ['text' => 'Titre du processus', 'context' => 'Label for the process title field.'],
            'decisions.consent.field.process_description' => ['text' => 'Description du contexte', 'context' => 'Label for the process description field.'],
            'decisions.consent.field.process_section' => ['text' => 'Contexte du processus', 'context' => 'Section title for process-level context fields.'],
            'decisions.consent.field.group_section' => ['text' => 'Question de ce groupe', 'context' => 'Section title for group-level question fields.'],
            'decisions.consent.field.type' => ['text' => 'Type de prise de décision', 'context' => 'Label for the decision type field.'],
            'decisions.consent.field.status' => ['text' => 'Statut', 'context' => 'Label for the status field.'],
            'decisions.consent.field.consultation_start' => ['text' => 'Début de la consultation', 'context' => 'Label for the consultation start field.'],
            'decisions.consent.field.consultation_end' => ['text' => 'Fin de la consultation', 'context' => 'Label for the consultation end field.'],
            'decisions.consent.field.evaluation_start' => ['text' => 'Début des prises de position', 'context' => 'Label for the evaluation start field.'],
            'decisions.consent.field.evaluation_end' => ['text' => 'Clôture des prises de position', 'context' => 'Label for the evaluation end field.'],
            'decisions.consent.field.proposals' => ['text' => 'Propositions', 'context' => 'Label for the proposals list.'],
            'decisions.consent.field.proposals_hint' => ['text' => 'Ajoutez une proposition par ligne, puis réorganisez-les par glisser-déposer. Une proposition suffit.', 'context' => 'Hint under the proposals list.'],
            'decisions.consent.field.proposals_add' => ['text' => 'Ajouter une proposition', 'context' => 'Button label to append a new proposal field.'],
            'decisions.consent.field.proposals_remove' => ['text' => 'Supprimer', 'context' => 'Button label to remove a proposal field.'],
            'decisions.consent.field.proposals_reorder' => ['text' => 'Réordonner', 'context' => 'Aria label for the proposal drag handle.'],
            'decisions.consent.field.proposals_item' => ['text' => 'Proposition {index}', 'context' => 'Visible label prefix for one proposal row.'],
            'decisions.consent.field.proposal_details' => ['text' => 'Détails', 'context' => 'Button label opening the proposal detail popup.'],
            'decisions.consent.field.proposal_description' => ['text' => 'Description de la proposition', 'context' => 'Label for the proposal description field.'],
            'decisions.consent.field.proposal_info_url' => ['text' => 'URL d’information', 'context' => 'Label for the proposal info URL field.'],
            'decisions.consent.field.proposal_actions' => ['text' => 'Actions', 'context' => 'Accessibility label for the proposal actions menu.'],
            'decisions.consent.field.settings' => ['text' => 'Paramètres du scrutin', 'context' => 'Section title for consent-specific settings.'],
            'decisions.consent.field.scale' => ['text' => 'Positions possibles', 'context' => 'Label for the position summary.'],
            'decisions.consent.field.scale_summary' => ['text' => 'Pour / Pas d’objection / Objection', 'context' => 'Summary label for the consent scale.'],
            'decisions.consent.field.anonymous' => ['text' => 'Vote anonyme', 'context' => 'Label for the anonymity setting.'],
            'decisions.consent.field.allow_anonymous_votes' => ['text' => 'Autoriser les votes anonymes', 'context' => 'Label for allowing participants to choose anonymity for their own vote.'],
            'decisions.consent.field.allow_consultation_proposals' => ['text' => 'Autoriser les propositions pendant la consultation', 'context' => 'Label for allowing proposals during consultation.'],
            'decisions.consent.field.allow_proposal_discussions' => ['text' => 'Autoriser les discussions des propositions', 'context' => 'Label for allowing account users to discuss proposals.'],
            'decisions.consent.field.your_choices' => ['text' => 'Vos positions', 'context' => 'Legend for the participant choice fieldset.'],
            'decisions.consent.field.total_votes' => ['text' => 'Votes enregistrés', 'context' => 'Label for the total number of submitted votes.'],
            'decisions.consent.field.proposal_votes' => ['text' => 'Réponses reçues', 'context' => 'Label for the number of received responses on one proposal.'],
            'decisions.consent.field.summary' => ['text' => 'Synthèse', 'context' => 'Label for the summary badge.'],
            'decisions.consent.field.distribution' => ['text' => 'Répartition des positions', 'context' => 'Label for the result distribution bar.'],
            'decisions.consent.field.select_all' => ['text' => 'Prononcez-vous sur chaque proposition.', 'context' => 'Help text for the participation form.'],
            'decisions.consent.option.type.decision' => ['text' => 'Décisionnaire', 'context' => 'Select option for a decision-oriented process.'],
            'decisions.consent.option.type.consultation' => ['text' => 'Consultative', 'context' => 'Select option for a consultation-oriented process.'],
            'decisions.consent.option.status.draft' => ['text' => 'En préparation', 'context' => 'Draft status option.'],
            'decisions.consent.option.status.scheduled' => ['text' => 'Planifiée', 'context' => 'Scheduled status option.'],
            'decisions.consent.option.status.consultation' => ['text' => 'En consultation', 'context' => 'Consultation status option.'],
            'decisions.consent.option.status.evaluation' => ['text' => 'En évaluation', 'context' => 'Evaluation status option.'],
            'decisions.consent.option.status.results' => ['text' => 'Résultats', 'context' => 'Results status option.'],
            'decisions.consent.option.status.archived' => ['text' => 'Archivée', 'context' => 'Archived status option.'],
            'decisions.consent.option.common.yes' => ['text' => 'Oui', 'context' => 'Generic yes option label.'],
            'decisions.consent.option.common.no' => ['text' => 'Non', 'context' => 'Generic no option label.'],
            'decisions.consent.option.choice.favor' => ['text' => 'Pour', 'context' => 'Consent choice label meaning support.'],
            'decisions.consent.option.choice.no_objection' => ['text' => 'Pas d’objection', 'context' => 'Consent choice label meaning no objection.'],
            'decisions.consent.option.choice.objection' => ['text' => 'Objection', 'context' => 'Consent choice label meaning objection.'],
            'decisions.consent.placeholder.title' => ['text' => 'Ex. Quelle formulation retenez-vous ?', 'context' => 'Placeholder for the group title field.'],
            'decisions.consent.placeholder.description' => ['text' => 'Précisez la question, les nuances et les critères utiles…', 'context' => 'Placeholder for the group description field.'],
            'decisions.consent.placeholder.process_title' => ['text' => 'Ex. Préparation de l’assemblée d’équipe', 'context' => 'Placeholder for the process title field.'],
            'decisions.consent.placeholder.process_description' => ['text' => 'Contexte global, informations communes, cadre de la consultation…', 'context' => 'Placeholder for the process description field.'],
            'decisions.consent.placeholder.proposals' => ['text' => 'Nom de la proposition', 'context' => 'Placeholder for one proposal input.'],
            'decisions.consent.placeholder.proposal_info_url' => ['text' => 'https://...', 'context' => 'Placeholder for one proposal info URL input.'],
            'decisions.consent.action.create' => ['text' => 'Créer le scrutin', 'context' => 'Submit label for a new consent process.'],
            'decisions.consent.action.save' => ['text' => 'Enregistrer le scrutin', 'context' => 'Submit label for an existing consent process.'],
            'decisions.consent.action.saving' => ['text' => 'Enregistrement…', 'context' => 'Temporary label while saving.'],
            'decisions.consent.action.configure' => ['text' => 'Configurer', 'context' => 'Button label to open the settings modal.'],
            'decisions.consent.action.close' => ['text' => 'Fermer', 'context' => 'Button label to close the settings modal.'],
            'decisions.consent.action.apply' => ['text' => 'Appliquer', 'context' => 'Button label to apply the settings modal changes.'],
            'decisions.consent.action.proposal_apply' => ['text' => 'Enregistrer les détails', 'context' => 'Button label used to save proposal detail popup fields.'],
            'decisions.consent.action.submit_response' => ['text' => 'Enregistrer mes positions', 'context' => 'Submit label for a new response.'],
            'decisions.consent.action.update_response' => ['text' => 'Mettre à jour mes positions', 'context' => 'Submit label when updating an existing response.'],
            'decisions.consent.action.submitting_response' => ['text' => 'Enregistrement du vote…', 'context' => 'Temporary label while saving a response.'],
            'decisions.consent.feedback.success' => ['text' => 'Scrutin enregistré.', 'context' => 'Generic success feedback after saving.'],
            'decisions.consent.feedback.error' => ['text' => 'Impossible d’enregistrer ce scrutin pour le moment.', 'context' => 'Generic error feedback after saving.'],
            'decisions.consent.feedback.response_success' => ['text' => 'Vote enregistré.', 'context' => 'Generic success feedback after saving a response.'],
            'decisions.consent.feedback.response_error' => ['text' => 'Impossible d’enregistrer votre vote pour le moment.', 'context' => 'Generic error feedback after saving a response.'],
            'decisions.consent.empty_proposals' => ['text' => 'Aucune proposition active pour le moment.', 'context' => 'Fallback text when no active proposal exists.'],
            'decisions.consent.empty_results' => ['text' => 'Aucune réponse n’a encore été enregistrée pour ce scrutin.', 'context' => 'Fallback text when no submitted response exists yet.'],
            'decisions.consent.summary.no_objection' => ['text' => 'Sans objection', 'context' => 'Summary shown when a proposal has no objection.'],
            'decisions.consent.summary.with_objection' => ['text' => '{count} objection(s)', 'context' => 'Summary shown when objections exist on a proposal.'],
            'decisions.consent.tooltip.segment' => ['text' => '{label} : {count} réponse(s) ({percent} %)', 'context' => 'Tooltip shown on one result segment.'],
            'decisions.consent.drawer_title' => ['text' => 'Prises de décision', 'context' => 'Drawer title reused after saving.'],
        ];
    }
}

if (!function_exists('omoDecisionConsentFormatDateTimeLocal')) {
    function omoDecisionConsentFormatDateTimeLocal($value)
    {
        if (!$value instanceof DateTimeInterface) {
            return '';
        }

        return $value->format('Y-m-d\TH:i');
    }
}

if (!function_exists('omoDecisionConsentModuleRender')) {
    function omoDecisionConsentModuleRender(array $renderContext)
    {
        $context = $renderContext['context'];
        $decision = $renderContext['decision'];
        $decisionGroup = ($context['decisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['decisionGroup']
            : ($decision instanceof DecisionProcess ? $decision->getPrimaryGroup(false) : null);
        $lang = $renderContext['lang'];
        $sourceLang = $renderContext['sourceLang'];
        $escape = $renderContext['escape'];
        $intent = (string)($context['intent'] ?? 'manage');

        $isManageMode = $intent === 'manage';
        $isParticipateMode = $intent === 'participate';
        $isViewMode = !$isManageMode && !$isParticipateMode;

        $decisionType = $decisionGroup instanceof DecisionGroup
            ? DecisionProcess::normalizeDecisionType($decisionGroup->get('decision_type'))
            : DecisionProcess::TYPE_DECISION;
        $status = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeStatus($decision->get('status'))
            : DecisionProcess::STATUS_DRAFT;

        $proposalObjects = [];
        if ($decisionGroup instanceof DecisionGroup) {
            foreach ($decisionGroup->getProposals(true) as $proposal) {
                $proposalObjects[] = $proposal;
            }
        }

        $proposalItems = $decisionGroup instanceof DecisionGroup
            ? omoDecisionBuildProposalItemsFromDecision($decisionGroup, 1)
            : omoDecisionBuildProposalItemsFromDecision($decision, 1);

        $config = $decisionGroup instanceof DecisionGroup
            ? omoDecisionConsentBuildConfig($decisionGroup)
            : omoDecisionConsentBuildConfig([]);
        $isAnonymous = !empty($config['is_anonymous']);
        $allowAnonymousVotes = !empty($config['allow_anonymous_votes']);
        $allowConsultationProposals = !empty($config['allow_consultation_proposals']);
        $allowProposalDiscussions = !empty($config['allow_proposal_discussions']);
        $choices = $config['choices'];
        $choiceUiMap = omoDecisionConsentGetChoiceUiMap();
        $renderChoices = [];
        foreach (omoDecisionConsentGetChoiceRenderOrder() as $choiceKey) {
            if (!array_key_exists($choiceKey, $choices)) {
                continue;
            }
            $renderChoices[$choiceKey] = $choices[$choiceKey];
        }
        foreach ($choices as $choiceKey => $choiceLabel) {
            if (array_key_exists($choiceKey, $renderChoices)) {
                continue;
            }
            $renderChoices[$choiceKey] = $choiceLabel;
        }
        $voteWeightEnabled = !empty($config['vote_weight_enabled']);
        $voteWeightQuestion = trim((string)($config['vote_weight_question'] ?? ''));
        $voteWeightOptions = is_array($config['vote_weight_options'] ?? null) ? array_values((array)$config['vote_weight_options']) : [];
        $voteWeightOptionsText = (string)($config['vote_weight_options_text'] ?? '');
        $voteWeightSummaryData = omoDecisionBlockSettingsBuildVoteWeightSummaryData([
            'enabled' => $voteWeightEnabled,
            'options' => $voteWeightOptions,
        ]);
        $voteWeightSummaryText = omoDecisionBlockSettingsBuildVoteWeightSummaryText(
            $voteWeightSummaryData,
            t('decisions.edit.block_settings.vote_weighting_summary_yes', [], $lang, $sourceLang),
            t('decisions.edit.block_settings.vote_weighting_summary_no', [], $lang, $sourceLang)
        );
        $voteWeightOptionsJson = omoDecisionModuleEncodeJsonPayload($voteWeightOptions, '[]');
        $defaultVoteWeightOptionsJson = omoDecisionModuleEncodeJsonPayload(omoDecisionBlockSettingsGetDefaultVoteWeightOptions(), '[]');

        $consultationStarted = $decision instanceof DecisionProcess ? $decision->hasConsultationStarted() : false;
        $evaluationStarted = $decision instanceof DecisionProcess ? $decision->hasEvaluationStarted() : false;
        $hasSubmittedResponses = $decision instanceof DecisionProcess ? $decision->hasSubmittedResponses() : false;
        $resultsMode = $decision instanceof DecisionProcess
            && in_array($status, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true);
        $coreLocked = $decision instanceof DecisionProcess && $evaluationStarted;
        $startDatesLocked = $coreLocked || ($decision instanceof DecisionProcess && $hasSubmittedResponses);
        $isEditable = $isManageMode && !$resultsMode;
        $canEditStructure = $isEditable && !$coreLocked;
        $canEditProposals = $isEditable && !$coreLocked;
        $canEditStartDates = $isEditable && !$startDatesLocked;
        $publicLayout = (($context['accessMode'] ?? '') === 'public') || !empty($context['previewLayout']);
        $visibilityState = function_exists('omoDecisionResolveVisibilityEditorState')
            ? omoDecisionResolveVisibilityEditorState($decision instanceof DecisionProcess ? $decision : null, $context)
            : array(
                'selectedVisibilityType' => DecisionProcess::getDefaultVisibilityType(),
                'visibilityOptions' => DecisionProcess::getVisibilityTypeOptions(),
                'disabledVisibilityTypes' => array(),
                'visibilityHelpText' => '',
            );

        $participant = $context['participant'] ?? null;
        $selectedResponse = null;
        $selectedResponseIsAnonymous = false;
        $selectedChoices = [];
        if ($decision instanceof DecisionProcess && $participant && (int)$participant->getId() > 0) {
            $selectedResponse = \dbObject\DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId(), $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0);
            $selectedChoices = omoDecisionConsentExtractChoices($selectedResponse);
            $selectedResponseIsAnonymous = omoDecisionResponseIsAnonymous($selectedResponse, omoDecisionConsentGetMethodKey());
        }
        $anonymousVoteChecked = $isAnonymous || ($allowAnonymousVotes && $selectedResponseIsAnonymous);
        $anonymousVoteDisabled = $isAnonymous || !$allowAnonymousVotes;

        $submittedResponses = [];
        $submittedVoteCount = 0;
        if ($decision instanceof DecisionProcess) {
            foreach (($decisionGroup instanceof DecisionGroup ? $decisionGroup->getResponses(DecisionResponse::STATUS_SUBMITTED) : $decision->getResponses(DecisionResponse::STATUS_SUBMITTED)) as $submittedResponse) {
                $submittedResponses[] = $submittedResponse;
                $submittedVoteCount++;
            }
        }
        $proposalStats = omoDecisionConsentBuildStats($proposalObjects, $submittedResponses);

        $headerTitleKey = 'decisions.consent.title';
        $headerDescriptionKey = 'decisions.consent.description';
        if ($isParticipateMode) {
            $headerTitleKey = 'decisions.consent.participate_title';
            $headerDescriptionKey = 'decisions.consent.participate_description';
        } elseif ($isViewMode) {
            $headerTitleKey = 'decisions.consent.view_title';
            $headerDescriptionKey = 'decisions.consent.view_description';
        }

        $managePayload = [
            'saveUrl' => '/omo/api/decision/modules/consent/save.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'manage'),
            'drawerTitle' => t('decisions.consent.drawer_title', [], $lang, $sourceLang),
            'proposalEditable' => $canEditProposals,
            'texts' => [
                'save' => $decision instanceof DecisionProcess
                    ? t('decisions.consent.action.save', [], $lang, $sourceLang)
                    : t('decisions.consent.action.create', [], $lang, $sourceLang),
                'saving' => t('decisions.consent.action.saving', [], $lang, $sourceLang),
                'success' => t('decisions.consent.feedback.success', [], $lang, $sourceLang),
                'error' => t('decisions.consent.feedback.error', [], $lang, $sourceLang),
                'proposalPlaceholder' => t('decisions.consent.placeholder.proposals', [], $lang, $sourceLang),
                'proposalInfoUrlPlaceholder' => t('decisions.consent.placeholder.proposal_info_url', [], $lang, $sourceLang),
                'proposalRemove' => t('decisions.consent.field.proposals_remove', [], $lang, $sourceLang),
                'proposalReorder' => t('decisions.consent.field.proposals_reorder', [], $lang, $sourceLang),
                'proposalDetails' => t('decisions.consent.field.proposal_details', [], $lang, $sourceLang),
                'proposalActions' => t('decisions.consent.field.proposal_actions', [], $lang, $sourceLang),
                'proposalDescriptionLabel' => t('decisions.consent.field.proposal_description', [], $lang, $sourceLang),
                'proposalInfoUrlLabel' => t('decisions.consent.field.proposal_info_url', [], $lang, $sourceLang),
                'proposalApply' => t('decisions.consent.action.proposal_apply', [], $lang, $sourceLang),
                'proposalItemTemplate' => t('decisions.consent.field.proposals_item', ['index' => '__INDEX__'], $lang, $sourceLang),
                'yesLabel' => t('decisions.consent.option.common.yes', [], $lang, $sourceLang),
                'noLabel' => t('decisions.consent.option.common.no', [], $lang, $sourceLang),
            ],
        ];

        $responsePayload = [
            'saveUrl' => '/omo/api/decision/modules/consent/respond.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'participate'),
            'drawerTitle' => t('decisions.consent.drawer_title', [], $lang, $sourceLang),
            'texts' => [
                'save' => $selectedResponse instanceof DecisionResponse
                    ? t('decisions.consent.action.update_response', [], $lang, $sourceLang)
                    : t('decisions.consent.action.submit_response', [], $lang, $sourceLang),
                'saving' => t('decisions.consent.action.submitting_response', [], $lang, $sourceLang),
                'success' => t('decisions.consent.feedback.response_success', [], $lang, $sourceLang),
                'error' => t('decisions.consent.feedback.response_error', [], $lang, $sourceLang),
            ],
        ];

        $managePayloadJson = omoDecisionModuleEncodeJsonPayload($managePayload);
        $responsePayloadJson = omoDecisionModuleEncodeJsonPayload($responsePayload);
        $consultationProposalPanel = ($publicLayout && $decision instanceof DecisionProcess)
            ? omoDecisionRenderConsultationProposalPublicPanel($decision, $context, $escape, 'omo-decision-consent__consultation-panel')
            : '';
        ?>
        <section class="omo-decision-consent generic-section generic-section--stack">
            <?= omoDecisionRenderProposalDiscussionAssets() ?>
<!--
        <?php if (!$publicLayout): ?>
            <div class="generic-hero-panel accent">
                <div class="omo-decision-consent__head">
                    <h3 class="generic-card-title"><?= $escape(t($headerTitleKey, [], $lang, $sourceLang)) ?></h3>
                    <p class="omo-decision-consent__text"><?= $escape(t($headerDescriptionKey, [], $lang, $sourceLang)) ?></p>
                </div>
            </div>
            <?php endif; ?>
-->

            <?php if ($resultsMode && !$publicLayout): ?>
            <div class="generic-soft-panel generic-soft-panel--stack">
                <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.notice.results', [], $lang, $sourceLang)) ?></p>
            </div>
            <?php elseif ($coreLocked && !$publicLayout): ?>
            <div class="generic-soft-panel generic-soft-panel--stack">
                <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.notice.started', [], $lang, $sourceLang)) ?></p>
                <?php if ($startDatesLocked): ?>
                <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.notice.responses', [], $lang, $sourceLang)) ?></p>
                <?php elseif ($allowConsultationProposals): ?>
                <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.notice.consultation_proposals', [], $lang, $sourceLang)) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($isManageMode): ?>
            <form class="omo-decision-consent__form generic-form-stack" action="/omo/api/decision/modules/consent/save.php" method="post" data-omo-decision-consent-form>
                <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                <input type="hidden" name="method" value="<?= $escape(DecisionProcess::METHOD_CONSENT) ?>">
                <input type="hidden" name="intent" value="manage">
                <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>

                <div class="generic-card-title"><?= $escape(t('decisions.consent.field.process_section', [], $lang, $sourceLang)) ?></div>

                <label class="omo-decision-consent__field">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.process_title', [], $lang, $sourceLang)) ?></span>
                    <input type="text" class="generic-form-control" name="process_title" value="<?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.consent.placeholder.process_title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'readonly' ?>>
                </label>

                <label class="omo-decision-consent__field">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.process_description', [], $lang, $sourceLang)) ?></span>
                    <textarea class="generic-form-control omo-decision-consent__textarea" name="process_description" placeholder="<?= $escape(t('decisions.consent.placeholder.process_description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'readonly' ?>><?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('description')) : '') ?></textarea>
                </label>

                <label class="omo-decision-consent__field">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.visibility.label', [], $lang, $sourceLang)) ?></span>
                    <select class="generic-form-control" name="visibility_type" <?= $canEditStructure ? '' : 'disabled' ?>>
                        <?php foreach (($visibilityState['visibilityOptions'] ?? array()) as $optionValue => $optionLabel): ?>
                        <option
                            value="<?= $escape($optionValue) ?>"
                            <?= $optionValue === ($visibilityState['selectedVisibilityType'] ?? DecisionProcess::getDefaultVisibilityType()) ? 'selected' : '' ?>
                            <?= !empty(($visibilityState['disabledVisibilityTypes'] ?? array())[$optionValue]) ? ' disabled' : '' ?>
                        ><?= $escape($optionLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (trim((string)($visibilityState['visibilityHelpText'] ?? '')) !== ''): ?>
                    <span class="omo-decision-consent__text"><?= $escape((string)$visibilityState['visibilityHelpText']) ?></span>
                    <?php endif; ?>
                </label>

                <div class="omo-decision-consent__grid">
                    <label class="omo-decision-consent__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.status', [], $lang, $sourceLang)) ?></span>
                        <select class="generic-form-control" name="status">
                            <option value="<?= $escape(DecisionProcess::STATUS_DRAFT) ?>" <?= $status === DecisionProcess::STATUS_DRAFT ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.status.draft', [], $lang, $sourceLang)) ?></option>
                            <option value="<?= $escape(DecisionProcess::STATUS_SCHEDULED) ?>" <?= $status === DecisionProcess::STATUS_SCHEDULED ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.status.scheduled', [], $lang, $sourceLang)) ?></option>
                            <option value="<?= $escape(DecisionProcess::STATUS_CONSULTATION) ?>" <?= $status === DecisionProcess::STATUS_CONSULTATION ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.status.consultation', [], $lang, $sourceLang)) ?></option>
                            <option value="<?= $escape(DecisionProcess::STATUS_EVALUATION) ?>" <?= $status === DecisionProcess::STATUS_EVALUATION ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.status.evaluation', [], $lang, $sourceLang)) ?></option>
                            <option value="<?= $escape(DecisionProcess::STATUS_RESULTS) ?>" <?= $status === DecisionProcess::STATUS_RESULTS ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.status.results', [], $lang, $sourceLang)) ?></option>
                            <option value="<?= $escape(DecisionProcess::STATUS_ARCHIVED) ?>" <?= $status === DecisionProcess::STATUS_ARCHIVED ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.status.archived', [], $lang, $sourceLang)) ?></option>
                        </select>
                    </label>

                    <label class="omo-decision-consent__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.consultation_start', [], $lang, $sourceLang)) ?></span>
                        <input type="datetime-local" class="generic-form-control" name="consultation_start_at" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionConsentFormatDateTimeLocal(DecisionProcess::normalizeDateTimeValue($decision->get('consultation_start_at'))) : '') ?>" <?= $canEditStartDates ? '' : 'readonly' ?>>
                    </label>

                    <label class="omo-decision-consent__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.consultation_end', [], $lang, $sourceLang)) ?></span>
                        <input type="datetime-local" class="generic-form-control" name="consultation_end_at" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionConsentFormatDateTimeLocal(DecisionProcess::normalizeDateTimeValue($decision->get('consultation_end_at'))) : '') ?>">
                    </label>

                    <label class="omo-decision-consent__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.evaluation_start', [], $lang, $sourceLang)) ?></span>
                        <input type="datetime-local" class="generic-form-control" name="evaluation_start_at" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionConsentFormatDateTimeLocal(DecisionProcess::normalizeDateTimeValue($decision->get('evaluation_start_at'))) : '') ?>" <?= $canEditStartDates ? '' : 'readonly' ?>>
                    </label>

                    <label class="omo-decision-consent__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.evaluation_end', [], $lang, $sourceLang)) ?></span>
                        <input type="datetime-local" class="generic-form-control" name="evaluation_end_at" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionConsentFormatDateTimeLocal(DecisionProcess::normalizeDateTimeValue($decision->get('evaluation_end_at'))) : '') ?>">
                    </label>
                </div>

                <?php if (function_exists('omoDecisionRenderEditorGroupSwitch')) {
                    omoDecisionRenderEditorGroupSwitch($context, $decision instanceof DecisionProcess ? $decision : null, $decisionGroup instanceof DecisionGroup ? $decisionGroup : null, $decision instanceof DecisionProcess ? $decision->getDecisionGroups(false) : [], $lang, $sourceLang, $escape);
                } ?>

                <div class="generic-card-title"><?= $escape(t('decisions.consent.field.group_section', [], $lang, $sourceLang)) ?></div>

                <label class="omo-decision-consent__field">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.title', [], $lang, $sourceLang)) ?></span>
                    <input type="text" class="generic-form-control" name="title" value="<?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.consent.placeholder.title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'readonly' ?>>
                </label>

                <label class="omo-decision-consent__field">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.description', [], $lang, $sourceLang)) ?></span>
                    <textarea class="generic-form-control omo-decision-consent__textarea" name="description" placeholder="<?= $escape(t('decisions.consent.placeholder.description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'readonly' ?>><?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('description')) : '') ?></textarea>
                </label>

                <div class="omo-decision-consent__grid">
                    <label class="omo-decision-consent__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.type', [], $lang, $sourceLang)) ?></span>
                        <select class="generic-form-control" name="decision_type" <?= $canEditStructure ? '' : 'disabled' ?>>
                            <option value="<?= $escape(DecisionProcess::TYPE_DECISION) ?>" <?= $decisionType === DecisionProcess::TYPE_DECISION ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.type.decision', [], $lang, $sourceLang)) ?></option>
                            <option value="<?= $escape(DecisionProcess::TYPE_CONSULTATION) ?>" <?= $decisionType === DecisionProcess::TYPE_CONSULTATION ? 'selected' : '' ?>><?= $escape(t('decisions.consent.option.type.consultation', [], $lang, $sourceLang)) ?></option>
                        </select>
                    </label>
                </div>

                <div class="generic-soft-panel generic-soft-panel--stack omo-decision-consent__settings-summary">
                    <?= omoDecisionRenderVoteWeightEditorAssets() ?>
                    <input type="hidden" name="is_anonymous" value="<?= $isAnonymous ? '1' : '' ?>" data-omo-decision-consent-hidden-anonymous>
                    <input type="hidden" name="allow_anonymous_votes" value="<?= $allowAnonymousVotes ? '1' : '' ?>" data-omo-decision-consent-hidden-allow-anonymous-votes>
                    <input type="hidden" name="allow_consultation_proposals" value="<?= $allowConsultationProposals ? '1' : '' ?>" data-omo-decision-consent-hidden-consultation-proposals>
                    <input type="hidden" name="allow_proposal_discussions" value="<?= $allowProposalDiscussions ? '1' : '' ?>" data-omo-decision-consent-hidden-proposal-discussions>
                    <input type="hidden" name="vote_weight_enabled" value="<?= $voteWeightEnabled ? '1' : '' ?>" data-omo-decision-consent-hidden-vote-weight-enabled>
                    <input type="hidden" name="vote_weight_question" value="<?= $escape($voteWeightQuestion) ?>" data-omo-decision-consent-hidden-vote-weight-question>
                    <input
                        type="hidden"
                        name="vote_weight_options_json"
                        value="<?= $escape($voteWeightOptionsJson) ?>"
                        data-omo-decision-consent-hidden-vote-weight-options
                        data-default-options-json="<?= $escape($defaultVoteWeightOptionsJson) ?>"
                    >
                    <div class="omo-decision-consent__settings-head">
                        <div class="omo-decision-consent__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.settings', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-decision-consent__readonly-stats omo-decision-consent__readonly-stats--settings">
                                <span class="omo-decision-consent__readonly-stat">
                                    <strong><?= $escape(t('decisions.consent.field.scale', [], $lang, $sourceLang)) ?></strong>
                                    <span data-omo-decision-consent-scale-summary><?= $escape(t('decisions.consent.field.scale_summary', [], $lang, $sourceLang)) ?></span>
                                </span>
                                <span class="omo-decision-consent__readonly-stat">
                                    <strong><?= $escape(t('decisions.consent.field.anonymous', [], $lang, $sourceLang)) ?></strong>
                                    <span
                                        data-omo-decision-consent-anonymous-summary
                                        data-yes-label="<?= $escape(t('decisions.consent.option.common.yes', [], $lang, $sourceLang)) ?>"
                                        data-no-label="<?= $escape(t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?>"
                                    ><?= $escape($isAnonymous ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?></span>
                                </span>
                                <span class="omo-decision-consent__readonly-stat">
                                    <strong><?= $escape(t('decisions.consent.field.allow_anonymous_votes', [], $lang, $sourceLang)) ?></strong>
                                    <span data-omo-decision-consent-allow-anonymous-votes-summary data-yes-label="<?= $escape(t('decisions.consent.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($allowAnonymousVotes ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?></span>
                                </span>
                                <span class="omo-decision-consent__readonly-stat">
                                    <strong><?= $escape(t('decisions.consent.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></strong>
                                    <span
                                        data-omo-decision-consent-consultation-summary
                                        data-yes-label="<?= $escape(t('decisions.consent.option.common.yes', [], $lang, $sourceLang)) ?>"
                                        data-no-label="<?= $escape(t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?>"
                                    ><?= $escape($allowConsultationProposals ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?></span>
                                </span>
                                <span class="omo-decision-consent__readonly-stat">
                                    <strong><?= $escape(t('decisions.consent.field.allow_proposal_discussions', [], $lang, $sourceLang)) ?></strong>
                                    <span
                                        data-omo-decision-consent-discussions-summary
                                        data-yes-label="<?= $escape(t('decisions.consent.option.common.yes', [], $lang, $sourceLang)) ?>"
                                        data-no-label="<?= $escape(t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?>"
                                    ><?= $escape($allowProposalDiscussions ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang)) ?></span>
                                </span>
                                <span class="omo-decision-consent__readonly-stat">
                                    <strong><?= $escape(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang)) ?></strong>
                                    <span
                                        data-omo-decision-consent-vote-weight-summary
                                        data-yes-label="<?= $escape(t('decisions.edit.block_settings.vote_weighting_summary_yes', [], $lang, $sourceLang)) ?>"
                                        data-no-label="<?= $escape(t('decisions.edit.block_settings.vote_weighting_summary_no', [], $lang, $sourceLang)) ?>"
                                    ><?= $escape($voteWeightSummaryText) ?></span>
                                </span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--secondary"
                            data-omo-decision-consent-settings-open
                            data-omo-decision-consent-settings-title="<?= $escape(t('decisions.consent.field.settings', [], $lang, $sourceLang)) ?>"
                        >
                            <?= $escape(t('decisions.consent.field.settings', [], $lang, $sourceLang)) ?>
                        </button>
                    </div>

                    <template data-omo-decision-consent-settings-template>
                        <div class="omo-decision-settings-popup omo-decision-consent__modal" data-topbar-modal-max-width="700px">
                            <label class="omo-decision-consent__modal-option">
                                <input type="checkbox" data-omo-decision-consent-popup-anonymous>
                                <span><?= $escape(t('decisions.consent.field.anonymous', [], $lang, $sourceLang)) ?></span>
                            </label>
                            <label class="omo-decision-consent__modal-option">
                                <input type="checkbox" data-omo-decision-consent-popup-allow-anonymous-votes <?= $canEditStructure ? '' : 'disabled' ?>>
                                <span><?= $escape(t('decisions.consent.field.allow_anonymous_votes', [], $lang, $sourceLang)) ?></span>
                            </label>
                            <label class="omo-decision-consent__modal-option">
                                <input type="checkbox" data-omo-decision-consent-popup-consultation-proposals>
                                <span><?= $escape(t('decisions.consent.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></span>
                            </label>
                            <label class="omo-decision-consent__modal-option">
                                <input type="checkbox" data-omo-decision-consent-popup-proposal-discussions <?= $canEditStructure ? '' : 'disabled' ?>>
                                <span><?= $escape(t('decisions.consent.field.allow_proposal_discussions', [], $lang, $sourceLang)) ?></span>
                            </label>
                            <?= omoDecisionRenderVoteWeightEditor($lang, $sourceLang, $escape, [
                                'canEdit' => $canEditStructure,
                                'enabled' => $voteWeightEnabled,
                                'question' => $voteWeightQuestion,
                                'options' => $voteWeightOptions,
                            ]) ?>
                            <div class="omo-decision-settings-popup__actions omo-decision-consent__modal-actions">
                                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-consent-popup-cancel><?= $escape(t('decisions.consent.action.close', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-consent-popup-apply><?= $escape(t('decisions.consent.action.apply', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="omo-decision-consent__proposal-main">
                        <span class="generic-card-title"><?= $escape(t('decisions.consent.field.proposals', [], $lang, $sourceLang)) ?></span>
                        <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.field.proposals_hint', [], $lang, $sourceLang)) ?></p>
                    </div>

                    <div class="omo-decision-consent__proposal-list" data-omo-decision-consent-proposal-list>
                        <?php foreach ($proposalItems as $index => $proposalItem): ?>
                        <div class="omo-decision-consent__proposal-card omo-decision-proposal-card generic-section<?= $canEditProposals ? '' : ' omo-decision-consent__proposal-card--locked omo-decision-proposal-card--locked' ?>" data-omo-decision-consent-proposal-card>
                            <?php if ($canEditProposals): ?>
                            <button type="button" class="omo-decision-consent__proposal-drag" data-omo-decision-consent-proposal-drag aria-label="<?= $escape(t('decisions.consent.field.proposals_reorder', [], $lang, $sourceLang)) ?>">::</button>
                            <?php endif; ?>
                            <div class="omo-decision-consent__proposal-field">
                                <span class="generic-card-title generic-card-title--small" data-omo-decision-consent-proposal-label><?= $escape(str_replace('{index}', (string)($index + 1), t('decisions.consent.field.proposals_item', ['index' => (string)($index + 1)], $lang, $sourceLang))) ?></span>
                                <input type="text" class="generic-form-control" name="proposals[]" value="<?= $escape((string)$proposalItem['title']) ?>" placeholder="<?= $escape(t('decisions.consent.placeholder.proposals', [], $lang, $sourceLang)) ?>" <?= $canEditProposals ? '' : 'readonly' ?>>
                                <input type="hidden" name="proposal_descriptions[]" value="<?= $escape((string)($proposalItem['description'] ?? '')) ?>" data-omo-decision-consent-proposal-description>
                                <input type="hidden" name="proposal_info_urls[]" value="<?= $escape((string)($proposalItem['info_url'] ?? '')) ?>" data-omo-decision-consent-proposal-info-url>
                                <input type="hidden" name="proposal_ids[]" value="<?= $escape((int)($proposalItem['id'] ?? 0)) ?>">
                            </div>
                            <div class="omo-decision-consent__proposal-menu" data-omo-decision-consent-proposal-menu>
                                <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-consent__proposal-menu-toggle" data-omo-decision-consent-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= $escape(t('decisions.consent.field.proposal_actions', [], $lang, $sourceLang)) ?>">...</button>
                                <div class="omo-decision-consent__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel" data-omo-decision-consent-proposal-menu-panel role="menu" hidden>
                                    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-consent__proposal-menu-item" data-omo-decision-consent-proposal-settings role="menuitem"><?= $escape(t('decisions.consent.field.proposal_details', [], $lang, $sourceLang)) ?></button>
                                    <?php if ($canEditProposals): ?>
                                    <button type="button" class="generic-action-button generic-action-button--danger omo-decision-consent__proposal-menu-item" data-omo-decision-consent-proposal-remove role="menuitem"><?= $escape(t('decisions.consent.field.proposals_remove', [], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-consent-proposal-add <?= $canEditProposals ? '' : 'disabled' ?>><?= $escape(t('decisions.consent.field.proposals_add', [], $lang, $sourceLang)) ?></button>
                    </div>
                </div>

                <?= omoDecisionRenderInvitationSection($decision, $context, $lang, $sourceLang, $escape, 'omo-decision-consent__invitation-summary') ?>

                <div class="omo-decision-consent__footer">
                    <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-consent-submit><?= $escape($decision instanceof DecisionProcess ? t('decisions.consent.action.save', [], $lang, $sourceLang) : t('decisions.consent.action.create', [], $lang, $sourceLang)) ?></button>
                    <div class="omo-decision-consent__feedback" data-omo-decision-consent-feedback aria-live="polite"></div>
                </div>

                <script type="application/json" data-omo-decision-consent-data><?= $managePayloadJson ?></script>
            </form>
            <?php else: ?>
                <?php if (!$publicLayout): ?>
                <div class="omo-decision-consent__summary-grid">
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.consent.field.scale', [], $lang, $sourceLang), t('decisions.consent.field.scale_summary', [], $lang, $sourceLang), $escape, 'omo-decision-consent__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.consent.field.anonymous', [], $lang, $sourceLang), $isAnonymous ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-consent__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.consent.field.allow_consultation_proposals', [], $lang, $sourceLang), $allowConsultationProposals ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-consent__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.consent.field.allow_proposal_discussions', [], $lang, $sourceLang), $allowProposalDiscussions ? t('decisions.consent.option.common.yes', [], $lang, $sourceLang) : t('decisions.consent.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-consent__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang), $voteWeightSummaryText, $escape, 'omo-decision-consent__meta-card') ?>
                    <?php if ($resultsMode): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.consent.field.total_votes', [], $lang, $sourceLang), (string)$submittedVoteCount, $escape, 'omo-decision-consent__meta-card') ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$publicLayout && $decision instanceof DecisionProcess && trim((string)$decision->get('description')) !== ''): ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.description', [], $lang, $sourceLang)) ?></span>
                    <p class="omo-decision-consent__text"><?= nl2br($escape(trim((string)$decision->get('description')))) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($isParticipateMode): ?>
                <form class="omo-decision-consent__form generic-form-stack" action="/omo/api/decision/modules/consent/respond.php" method="post" data-omo-decision-consent-response-form>
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                    <input type="hidden" name="method" value="<?= $escape(DecisionProcess::METHOD_CONSENT) ?>">
                    <input type="hidden" name="intent" value="participate">
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>

                    <fieldset class="omo-decision-consent__fieldset">
                        <legend class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.your_choices', [], $lang, $sourceLang)) ?></legend>
                        <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.field.select_all', [], $lang, $sourceLang)) ?></p>

                        <?php if (count($proposalObjects) === 0): ?>
                        <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.empty_proposals', [], $lang, $sourceLang)) ?></p>
                        <?php else: ?>
                        <div class="omo-decision-consent__choice-list">
                            <?php foreach ($proposalObjects as $proposal): ?>
                            <?php $proposalId = (int)$proposal->getId(); ?>
                            <div class="generic-section generic-section--stack omo-decision-consent__choice-card">
                                <div class="omo-decision-consent__choice-head">
                                    <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                    <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-consent__text', 'omo-decision-consent__link') ?>
                                    <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
                                </div>
                                <div class="omo-decision-consent__choice-scale">
                                    <?php foreach ($renderChoices as $choiceKey => $choiceLabel): ?>
                                    <?php
                                    $choiceUi = $choiceUiMap[$choiceKey] ?? [];
                                    $choiceTheme = trim((string)($choiceUi['theme'] ?? $choiceKey));
                                    $choiceIconUrl = trim((string)($choiceUi['icon_url'] ?? ''));
                                    $choiceIconStyle = $choiceIconUrl !== '' ? '--omo-consent-choice-icon: url(\'' . $choiceIconUrl . '\');' : '';
                                    ?>
                                    <div class="omo-decision-consent__choice-option omo-decision-consent__choice-option--<?= $escape($choiceTheme) ?><?= (($selectedChoices[$proposalId] ?? '') === $choiceKey) ? ' is-selected' : '' ?>">
                                        <input class="omo-decision-consent__choice-input" type="radio" name="choices[<?= $escape($proposalId) ?>]" value="<?= $escape($choiceKey) ?>" <?= (($selectedChoices[$proposalId] ?? '') === $choiceKey) ? 'checked' : '' ?> required>
                                        <button type="button" class="omo-decision-consent__choice-chip" data-omo-decision-consent-choice-trigger aria-pressed="<?= (($selectedChoices[$proposalId] ?? '') === $choiceKey) ? 'true' : 'false' ?>">
                                            <?php if ($choiceIconStyle !== ''): ?>
                                            <span class="omo-decision-consent__choice-icon" style="<?= $escape($choiceIconStyle) ?>" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <span class="omo-decision-consent__choice-label"><?= $escape($choiceLabel) ?></span>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </fieldset>
                    <label class="omo-decision-consent__modal-option">
                        <input type="checkbox" name="is_anonymous" value="1"<?= $anonymousVoteChecked ? ' checked' : '' ?><?= $anonymousVoteDisabled ? ' disabled' : '' ?>>
                        <span><?= $escape(t('decisions.consent.field.anonymous', [], $lang, $sourceLang)) ?></span>
                    </label>
                    <?php if ($consultationProposalPanel !== ''): ?>
                    <?= $consultationProposalPanel ?>
                    <?php endif; ?>

                    <div class="omo-decision-consent__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-consent-response-submit><?= $escape($selectedResponse instanceof DecisionResponse ? t('decisions.consent.action.update_response', [], $lang, $sourceLang) : t('decisions.consent.action.submit_response', [], $lang, $sourceLang)) ?></button>
                        <div class="omo-decision-consent__feedback" data-omo-decision-consent-response-feedback aria-live="polite"></div>
                    </div>

                    <script type="application/json" data-omo-decision-consent-response-data><?= $responsePayloadJson ?></script>
                </form>
                <?php else: ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.consent.field.proposals', [], $lang, $sourceLang)) ?></span>
                    <?php if (count($proposalObjects) === 0): ?>
                    <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.empty_proposals', [], $lang, $sourceLang)) ?></p>
                    <?php elseif ($resultsMode && $submittedVoteCount === 0): ?>
                    <p class="omo-decision-consent__text"><?= $escape(t('decisions.consent.empty_results', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>

                    <div class="omo-decision-consent__result-list">
                        <?php foreach ($proposalObjects as $proposal): ?>
                        <?php
                        $proposalId = (int)$proposal->getId();
                        $stat = $proposalStats[$proposalId] ?? [
                            'distribution' => ['favor' => 0, 'no_objection' => 0, 'objection' => 0],
                            'count' => 0,
                            'objection_count' => 0,
                            'has_objection' => false,
                            'dominant_choice' => 'no_objection',
                        ];
                        $selectedChoice = (string)($selectedChoices[$proposalId] ?? '');
                        $summaryLabel = $stat['has_objection']
                            ? t('decisions.consent.summary.with_objection', ['count' => (string)$stat['objection_count']], $lang, $sourceLang)
                            : t('decisions.consent.summary.no_objection', [], $lang, $sourceLang);
                        $summaryChoiceKey = $resultsMode ? ($stat['has_objection'] ? 'objection' : 'no_objection') : $selectedChoice;
                        $summaryChoiceUi = $choiceUiMap[$summaryChoiceKey] ?? [];
                        $summaryChoiceTheme = trim((string)($summaryChoiceUi['theme'] ?? $summaryChoiceKey));
                        $summaryChoiceIconUrl = trim((string)($summaryChoiceUi['icon_url'] ?? ''));
                        $summaryChoiceIconStyle = $summaryChoiceIconUrl !== '' ? '--omo-consent-choice-icon: url(\'' . $summaryChoiceIconUrl . '\');' : '';
                        ?>
                        <div class="omo-decision-consent__result-card generic-section generic-section--stack<?= $selectedChoice !== '' ? ' is-selected' : '' ?>">
                            <div class="omo-decision-consent__result-head">
                                <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-consent__text', 'omo-decision-consent__link') ?>
                                <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
                                <?php if ($resultsMode): ?>
                                <span class="omo-decision-consent__summary-badge omo-decision-consent__summary-badge--<?= $escape($summaryChoiceTheme) ?>">
                                    <?php if ($summaryChoiceIconStyle !== ''): ?>
                                    <span class="omo-decision-consent__choice-icon omo-decision-consent__choice-icon--small" style="<?= $escape($summaryChoiceIconStyle) ?>" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <span><?= $escape($summaryLabel) ?></span>
                                </span>
                                <?php elseif ($selectedChoice !== ''): ?>
                                <span class="omo-decision-consent__summary-badge omo-decision-consent__summary-badge--<?= $escape($summaryChoiceTheme) ?>">
                                    <?php if ($summaryChoiceIconStyle !== ''): ?>
                                    <span class="omo-decision-consent__choice-icon omo-decision-consent__choice-icon--small" style="<?= $escape($summaryChoiceIconStyle) ?>" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <span><?= $escape((string)$choices[$selectedChoice]) ?></span>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($resultsMode): ?>
                            <div class="omo-decision-consent__result-meta">
                                <span class="omo-decision-consent__result-meta-label"><?= $escape(t('decisions.consent.field.proposal_votes', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)$stat['count']) ?></strong>
                            </div>
                            <div class="omo-decision-consent__distribution" aria-label="<?= $escape(t('decisions.consent.field.distribution', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($renderChoices as $choiceKey => $choiceLabel): ?>
                                <?php
                                $segmentCount = (int)($stat['distribution'][$choiceKey] ?? 0);
                                $segmentPercent = $stat['count'] > 0 ? ($segmentCount / (int)$stat['count']) * 100 : 0;
                                $segmentPercentLabel = number_format($segmentPercent, 1, ',', ' ');
                                $segmentWidth = $segmentPercent > 0 ? number_format($segmentPercent, 4, '.', '') : '0';
                                $segmentTooltip = t(
                                    'decisions.consent.tooltip.segment',
                                    [
                                        'label' => (string)$choiceLabel,
                                        'count' => (string)$segmentCount,
                                        'percent' => $segmentPercentLabel,
                                    ],
                                    $lang,
                                    $sourceLang
                                );
                                ?>
                                <span
                                    class="omo-decision-consent__distribution-segment omo-decision-consent__distribution-segment--<?= $escape($choiceKey) ?>"
                                    style="width: <?= $escape($segmentWidth) ?>%;"
                                    title="<?= $escape($segmentTooltip) ?>"
                                    aria-label="<?= $escape($segmentTooltip) ?>"
                                ></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="omo-decision-consent__distribution-scale" aria-hidden="true">
                                <?php foreach ($renderChoices as $choiceKey => $choiceLabel): ?>
                                <?php
                                $choiceUi = $choiceUiMap[$choiceKey] ?? [];
                                $choiceTheme = trim((string)($choiceUi['theme'] ?? $choiceKey));
                                ?>
                                <span class="omo-decision-consent__distribution-label omo-decision-consent__distribution-label--<?= $escape($choiceTheme) ?>"><?= $escape((string)$choiceLabel) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($consultationProposalPanel !== ''): ?>
                    <?= $consultationProposalPanel ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <script>
        (function () {
            window.omoDecisionConsentInit = function (root) {
                const scope = root instanceof Element ? root : document;
                const getScopedMatches = function (selector) {
                    const nodes = [];
                    if (scope && typeof scope.matches === 'function' && scope.matches(selector)) {
                        nodes.push(scope);
                    }
                    if (scope && typeof scope.querySelectorAll === 'function') {
                        Array.prototype.forEach.call(scope.querySelectorAll(selector), function (node) {
                            nodes.push(node);
                        });
                    }
                    return nodes;
                };

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-consent-form]'), function (form) {
                    if (form.dataset.omoDecisionConsentReady === '1') {
                        return;
                    }

                    const payloadNode = form.querySelector('[data-omo-decision-consent-data]');
                    const submitButton = form.querySelector('[data-omo-decision-consent-submit]');
                    const feedbackNode = form.querySelector('[data-omo-decision-consent-feedback]');
                    const proposalList = form.querySelector('[data-omo-decision-consent-proposal-list]');
                    const proposalAddButton = form.querySelector('[data-omo-decision-consent-proposal-add]');
                    const settingsOpenButton = form.querySelector('[data-omo-decision-consent-settings-open]');
                    const invitationOpenButton = form.querySelector('[data-omo-decision-invitations-open]');
                    const invitationSendOpenButton = form.querySelector('[data-omo-decision-invitations-send-open]');
                    const settingsTemplate = form.querySelector('[data-omo-decision-consent-settings-template]');
                    const hiddenAnonymousInput = form.querySelector('[data-omo-decision-consent-hidden-anonymous]');
                    const hiddenAllowAnonymousVotesInput = form.querySelector('[data-omo-decision-consent-hidden-allow-anonymous-votes]');
                    const hiddenConsultationInput = form.querySelector('[data-omo-decision-consent-hidden-consultation-proposals]');
                    const hiddenProposalDiscussionsInput = form.querySelector('[data-omo-decision-consent-hidden-proposal-discussions]');
                    const hiddenVoteWeightEnabledInput = form.querySelector('[data-omo-decision-consent-hidden-vote-weight-enabled]');
                    const hiddenVoteWeightQuestionInput = form.querySelector('[data-omo-decision-consent-hidden-vote-weight-question]');
                    const hiddenVoteWeightOptionsInput = form.querySelector('[data-omo-decision-consent-hidden-vote-weight-options]');
                    const anonymousSummary = form.querySelector('[data-omo-decision-consent-anonymous-summary]');
                    const allowAnonymousVotesSummary = form.querySelector('[data-omo-decision-consent-allow-anonymous-votes-summary]');
                    const consultationSummary = form.querySelector('[data-omo-decision-consent-consultation-summary]');
                    const discussionsSummary = form.querySelector('[data-omo-decision-consent-discussions-summary]');
                    const voteWeightSummary = form.querySelector('[data-omo-decision-consent-vote-weight-summary]');
                    if (!payloadNode || !proposalList) {
                        return;
                    }

                    if (typeof window.omoDecisionInitInvitationEditors === 'function') {
                        window.omoDecisionInitInvitationEditors(form);
                    }

                    let payload = {};
                    try {
                        payload = JSON.parse(payloadNode.textContent || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    const setFeedback = function (message, isError) {
                        if (!feedbackNode) {
                            return;
                        }
                        feedbackNode.textContent = String(message || '');
                        feedbackNode.classList.toggle('is-error', !!isError);
                        feedbackNode.classList.toggle('is-success', !isError && message !== '');
                    };

                    const normalizeVoteWeightNumber = function (rawValue) {
                        const normalized = String(rawValue || '').trim().replace(',', '.');
                        if (normalized === '') {
                            return '';
                        }

                        const value = Number(normalized);
                        if (!Number.isFinite(value) || value <= 0) {
                            return '';
                        }

                        return String(value).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
                    };

                    const parseVoteWeightOptions = function (rawValue, fallbackToDefault) {
                        let options = [];

                        if (Array.isArray(rawValue)) {
                            options = rawValue;
                        } else {
                            const source = String(rawValue || '').trim();
                            if (source !== '') {
                                try {
                                    const decoded = JSON.parse(source);
                                    if (Array.isArray(decoded)) {
                                        options = decoded;
                                    }
                                } catch (error) {
                                    options = source.split(/\r\n|\r|\n/).map(function (line) {
                                        const parts = String(line || '').split('|');
                                        return {
                                            weight: parts.length > 0 ? parts[0] : '',
                                            label: parts.length > 1 ? parts.slice(1).join('|') : '',
                                        };
                                    });
                                }
                            }
                        }

                        const normalizedOptions = [];
                        options.forEach(function (option) {
                            if (!option || typeof option !== 'object') {
                                return;
                            }

                            const weight = normalizeVoteWeightNumber(option.weight || option.value || '');
                            const label = String(option.label || '').trim();
                            if (weight === '' || label === '') {
                                return;
                            }

                            normalizedOptions.push({
                                weight: weight,
                                label: label,
                            });
                        });

                        if (normalizedOptions.length === 0 && fallbackToDefault && hiddenVoteWeightOptionsInput) {
                            return parseVoteWeightOptions(
                                hiddenVoteWeightOptionsInput.getAttribute('data-default-options-json') || '[]',
                                false
                            );
                        }

                        return normalizedOptions;
                    };

                    const buildVoteWeightOptionsText = function (options) {
                        return options.map(function (option) {
                            return String(option.weight || '') + ' | ' + String(option.label || '');
                        }).join('\n');
                    };

                    const buildVoteWeightSummaryText = function () {
                        if (!voteWeightSummary) {
                            return '';
                        }

                        const yesLabel = String(voteWeightSummary.getAttribute('data-yes-label') || 'Oui');
                        const noLabel = String(voteWeightSummary.getAttribute('data-no-label') || 'Non');
                        if (!hiddenVoteWeightEnabledInput || !hiddenVoteWeightEnabledInput.value) {
                            return noLabel;
                        }

                        const options = parseVoteWeightOptions(
                            hiddenVoteWeightOptionsInput ? hiddenVoteWeightOptionsInput.value : '[]',
                            true
                        );
                        if (options.length === 0) {
                            return yesLabel;
                        }

                        const weights = options.map(function (option) {
                            return Number(String(option.weight || '').replace(',', '.'));
                        }).filter(function (value) {
                            return Number.isFinite(value) && value > 0;
                        });
                        if (weights.length === 0) {
                            return yesLabel;
                        }

                        return yesLabel + ' (' + String(options.length) + ' options de ' + normalizeVoteWeightNumber(String(Math.min.apply(Math, weights))) + ' a ' + normalizeVoteWeightNumber(String(Math.max.apply(Math, weights))) + ')';
                    };

                    const syncSettingsSummary = function () {
                        const yesLabel = String(payload.texts && payload.texts.yesLabel ? payload.texts.yesLabel : 'Oui');
                        const noLabel = String(payload.texts && payload.texts.noLabel ? payload.texts.noLabel : 'Non');
                        if (anonymousSummary) {
                            anonymousSummary.textContent = hiddenAnonymousInput && hiddenAnonymousInput.value ? yesLabel : noLabel;
                        }
                        if (allowAnonymousVotesSummary) {
                            allowAnonymousVotesSummary.textContent = hiddenAllowAnonymousVotesInput && hiddenAllowAnonymousVotesInput.value ? yesLabel : noLabel;
                        }
                        if (consultationSummary) {
                            consultationSummary.textContent = hiddenConsultationInput && hiddenConsultationInput.value ? yesLabel : noLabel;
                        }
                        if (discussionsSummary) {
                            discussionsSummary.textContent = hiddenProposalDiscussionsInput && hiddenProposalDiscussionsInput.value ? yesLabel : noLabel;
                        }
                        if (voteWeightSummary) {
                            voteWeightSummary.textContent = buildVoteWeightSummaryText();
                        }
                    };

                    const refreshProposalLabels = function () {
                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]'), function (card, index) {
                            const label = card.querySelector('[data-omo-decision-consent-proposal-label]');
                            if (label) {
                                const template = payload.texts && payload.texts.proposalItemTemplate
                                    ? payload.texts.proposalItemTemplate
                                    : 'Proposition __INDEX__';
                                label.textContent = String(template).replace('__INDEX__', String(index + 1));
                            }
                        });
                    };

                    const closeProposalMenus = function (exceptCard) {
                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]'), function (menuCard) {
                            if (exceptCard && menuCard === exceptCard) {
                                return;
                            }

                            const menuPanel = menuCard.querySelector('[data-omo-decision-consent-proposal-menu-panel]');
                            const menuToggle = menuCard.querySelector('[data-omo-decision-consent-proposal-menu-toggle]');
                            if (menuPanel) {
                                menuPanel.hidden = true;
                            }
                            if (menuToggle) {
                                menuToggle.setAttribute('aria-expanded', 'false');
                            }
                        });
                    };

                    const bindProposalCard = function (card) {
                        if (!card || card.dataset.omoDecisionConsentProposalReady === '1') {
                            return;
                        }

                        const titleInput = card.querySelector('input[name="proposals[]"]');
                        const descriptionInput = card.querySelector('[data-omo-decision-consent-proposal-description]');
                        const infoUrlInput = card.querySelector('[data-omo-decision-consent-proposal-info-url]');
                        const detailsButton = card.querySelector('[data-omo-decision-consent-proposal-settings]');
                        const removeButton = card.querySelector('[data-omo-decision-consent-proposal-remove]');
                        const menuToggle = card.querySelector('[data-omo-decision-consent-proposal-menu-toggle]');
                        const menuPanel = card.querySelector('[data-omo-decision-consent-proposal-menu-panel]');

                        if (menuToggle && menuPanel) {
                            menuToggle.addEventListener('click', function (event) {
                                event.preventDefault();
                                event.stopPropagation();

                                const shouldOpen = menuPanel.hidden;
                                closeProposalMenus(card);
                                menuPanel.hidden = !shouldOpen;
                                menuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                            });

                            menuPanel.addEventListener('click', function (event) {
                                event.stopPropagation();
                            });
                        }

                        if (detailsButton) {
                            detailsButton.addEventListener('click', function () {
                                closeProposalMenus();
                                if (detailsButton.disabled || typeof window.commonTopbarOpenModal !== 'function') {
                                    return;
                                }

                                const proposalLabelNode = card.querySelector('[data-omo-decision-consent-proposal-label]');
                                const modalTitle = proposalLabelNode
                                    ? String(proposalLabelNode.textContent || '').trim()
                                    : String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');
                                const modalHtml = ''
                                    + '<div class="generic-section generic-section--stack" style="display:grid;gap:12px;">'
                                    + '  <label style="display:grid;gap:6px;">'
                                    + '    <span class="generic-card-title generic-card-title--small">' + String(payload.texts && payload.texts.proposalDescriptionLabel ? payload.texts.proposalDescriptionLabel : 'Description') + '</span>'
                                    + '    <textarea class="generic-form-control" rows="6" data-omo-decision-consent-proposal-modal-description></textarea>'
                                    + '  </label>'
                                    + '  <label style="display:grid;gap:6px;">'
                                    + '    <span class="generic-card-title generic-card-title--small">' + String(payload.texts && payload.texts.proposalInfoUrlLabel ? payload.texts.proposalInfoUrlLabel : 'URL') + '</span>'
                                    + '    <input type="url" class="generic-form-control" data-omo-decision-consent-proposal-modal-info-url placeholder="' + String(payload.texts && payload.texts.proposalInfoUrlPlaceholder ? payload.texts.proposalInfoUrlPlaceholder : 'https://...') + '">'
                                    + '  </label>'
                                    + '  <div style="display:flex;justify-content:flex-end;gap:8px;">'
                                    + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-consent-proposal-modal-cancel>Fermer</button>'
                                    + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-consent-proposal-modal-apply>' + String(payload.texts && payload.texts.proposalApply ? payload.texts.proposalApply : 'Enregistrer') + '</button>'
                                    + '  </div>'
                                    + '</div>';

                                window.commonTopbarOpenModal(modalTitle || 'Details', modalHtml, 'html');
                                const modalBody = document.getElementById('commonTopbarModalBody');
                                if (!modalBody) {
                                    return;
                                }

                                const modalDescription = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-description]');
                                const modalInfoUrl = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-info-url]');
                                const modalCancel = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-cancel]');
                                const modalApply = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-apply]');
                                if (modalDescription) {
                                    modalDescription.value = descriptionInput ? String(descriptionInput.value || '') : '';
                                }
                                if (modalInfoUrl) {
                                    modalInfoUrl.value = infoUrlInput ? String(infoUrlInput.value || '') : '';
                                }
                                if (modalCancel) {
                                    modalCancel.addEventListener('click', function () {
                                        if (typeof window.commonTopbarCloseModal === 'function') {
                                            window.commonTopbarCloseModal();
                                        }
                                    });
                                }
                                if (modalApply) {
                                    modalApply.addEventListener('click', function () {
                                        if (descriptionInput && modalDescription) {
                                            descriptionInput.value = String(modalDescription.value || '').trim();
                                        }
                                        if (infoUrlInput && modalInfoUrl) {
                                            infoUrlInput.value = String(modalInfoUrl.value || '').trim();
                                        }
                                        if (typeof window.commonTopbarCloseModal === 'function') {
                                            window.commonTopbarCloseModal();
                                        }
                                        if (titleInput) {
                                            titleInput.focus();
                                        }
                                    });
                                }
                            });
                        }

                        if (removeButton) {
                            removeButton.addEventListener('click', function () {
                                closeProposalMenus();
                                const cards = proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]');
                                if (cards.length <= 1) {
                                    if (titleInput) {
                                        titleInput.value = '';
                                        titleInput.focus();
                                    }
                                    if (descriptionInput) {
                                        descriptionInput.value = '';
                                    }
                                    if (infoUrlInput) {
                                        infoUrlInput.value = '';
                                    }
                                    return;
                                }

                                card.remove();
                                refreshProposalLabels();
                            });
                        }

                        card.dataset.omoDecisionConsentProposalReady = '1';
                    };

                    let sortable = null;

                    const createProposalCard = function (value) {
                        const card = document.createElement('div');
                        card.className = 'omo-decision-consent__proposal-card omo-decision-proposal-card generic-section';
                        card.setAttribute('data-omo-decision-consent-proposal-card', '');

                        const dragButton = document.createElement('button');
                        dragButton.type = 'button';
                        dragButton.className = 'omo-decision-consent__proposal-drag';
                        dragButton.setAttribute('data-omo-decision-consent-proposal-drag', '');
                        dragButton.textContent = '::';
                        dragButton.setAttribute('aria-label', String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Reordonner'));

                        const field = document.createElement('div');
                        field.className = 'omo-decision-consent__proposal-field';

                        const label = document.createElement('span');
                        label.className = 'generic-card-title generic-card-title--small';
                        label.setAttribute('data-omo-decision-consent-proposal-label', '');

                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'generic-form-control';
                        input.name = 'proposals[]';
                        input.value = String(value || '');
                        input.placeholder = String(payload.texts && payload.texts.proposalPlaceholder ? payload.texts.proposalPlaceholder : '');

                        const descriptionInput = document.createElement('input');
                        descriptionInput.type = 'hidden';
                        descriptionInput.name = 'proposal_descriptions[]';
                        descriptionInput.value = '';
                        descriptionInput.setAttribute('data-omo-decision-consent-proposal-description', '');

                        const infoUrlInput = document.createElement('input');
                        infoUrlInput.type = 'hidden';
                        infoUrlInput.name = 'proposal_info_urls[]';
                        infoUrlInput.value = '';
                        infoUrlInput.setAttribute('data-omo-decision-consent-proposal-info-url', '');

                        const proposalIdInput = document.createElement('input');
                        proposalIdInput.type = 'hidden';
                        proposalIdInput.name = 'proposal_ids[]';
                        proposalIdInput.value = '0';

                        const menu = document.createElement('div');
                        menu.className = 'omo-decision-consent__proposal-menu';
                        menu.setAttribute('data-omo-decision-consent-proposal-menu', '');

                        const menuToggle = document.createElement('button');
                        menuToggle.type = 'button';
                        menuToggle.className = 'generic-action-button generic-action-button--secondary omo-decision-consent__proposal-menu-toggle';
                        menuToggle.setAttribute('data-omo-decision-consent-proposal-menu-toggle', '');
                        menuToggle.setAttribute('aria-haspopup', 'menu');
                        menuToggle.setAttribute('aria-expanded', 'false');
                        menuToggle.setAttribute('aria-label', 'Actions');
                        menuToggle.textContent = '...';

                        const menuPanel = document.createElement('div');
                        menuPanel.className = 'omo-decision-consent__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel';
                        menuPanel.setAttribute('data-omo-decision-consent-proposal-menu-panel', '');
                        menuPanel.setAttribute('role', 'menu');
                        menuPanel.hidden = true;

                        const detailsButton = document.createElement('button');
                        detailsButton.type = 'button';
                        detailsButton.className = 'generic-action-button generic-action-button--secondary omo-decision-consent__proposal-menu-item';
                        detailsButton.setAttribute('data-omo-decision-consent-proposal-settings', '');
                        detailsButton.setAttribute('role', 'menuitem');
                        detailsButton.textContent = String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');

                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'generic-action-button generic-action-button--danger omo-decision-consent__proposal-menu-item';
                        removeButton.setAttribute('data-omo-decision-consent-proposal-remove', '');
                        removeButton.setAttribute('role', 'menuitem');
                        removeButton.textContent = String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer');

                        menuPanel.appendChild(detailsButton);
                        menuPanel.appendChild(removeButton);
                        menu.appendChild(menuToggle);
                        menu.appendChild(menuPanel);

                        field.appendChild(label);
                        field.appendChild(input);
                        field.appendChild(descriptionInput);
                        field.appendChild(infoUrlInput);
                        field.appendChild(proposalIdInput);
                        card.appendChild(dragButton);
                        card.appendChild(field);
                        card.appendChild(menu);

                        bindProposalCard(card);
                        if (sortable && typeof sortable.bindItem === 'function') {
                            sortable.bindItem(card);
                        }
                        return card;
                    };

                    const openSettingsModal = function () {
                        if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        const modalTitle = settingsOpenButton
                            ? String(settingsOpenButton.getAttribute('data-omo-decision-consent-settings-title') || settingsOpenButton.textContent || 'Parametres du scrutin')
                            : 'Parametres du scrutin';
                        window.commonTopbarOpenModal(modalTitle, settingsTemplate.innerHTML, 'html');
                        const modalBody = document.getElementById('commonTopbarModalBody');
                        if (!modalBody) {
                            return;
                        }

                        const popupAnonymous = modalBody.querySelector('[data-omo-decision-consent-popup-anonymous]');
                        const popupAllowAnonymousVotes = modalBody.querySelector('[data-omo-decision-consent-popup-allow-anonymous-votes]');
                        const popupConsultation = modalBody.querySelector('[data-omo-decision-consent-popup-consultation-proposals]');
                        const popupProposalDiscussions = modalBody.querySelector('[data-omo-decision-consent-popup-proposal-discussions]');
                        const popupVoteWeightRoot = modalBody.querySelector('[data-omo-decision-vote-weight-editor]');
                        const popupCancel = modalBody.querySelector('[data-omo-decision-consent-popup-cancel]');
                        const popupApply = modalBody.querySelector('[data-omo-decision-consent-popup-apply]');
                        const popupVoteWeightEditor = popupVoteWeightRoot && typeof window.omoDecisionInitVoteWeightEditor === 'function'
                            ? window.omoDecisionInitVoteWeightEditor(popupVoteWeightRoot)
                            : null;
                        if (!popupAnonymous || !popupAllowAnonymousVotes || !popupConsultation || !popupProposalDiscussions || !popupVoteWeightEditor || !popupApply) {
                            return;
                        }

                        popupAnonymous.checked = !!(hiddenAnonymousInput && hiddenAnonymousInput.value);
                        popupAllowAnonymousVotes.checked = !!(hiddenAllowAnonymousVotesInput && hiddenAllowAnonymousVotesInput.value);
                        popupConsultation.checked = !!(hiddenConsultationInput && hiddenConsultationInput.value);
                        popupProposalDiscussions.checked = !!(hiddenProposalDiscussionsInput && hiddenProposalDiscussionsInput.value);
                        popupVoteWeightEditor.setState({
                            enabled: !!(hiddenVoteWeightEnabledInput && hiddenVoteWeightEnabledInput.value),
                            question: hiddenVoteWeightQuestionInput ? String(hiddenVoteWeightQuestionInput.value || '') : '',
                            options: parseVoteWeightOptions(hiddenVoteWeightOptionsInput ? hiddenVoteWeightOptionsInput.value : '[]', false),
                        });

                        if (popupCancel) {
                            popupCancel.addEventListener('click', function () {
                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                        }

                        popupApply.addEventListener('click', function () {
                            if (hiddenAnonymousInput) {
                                hiddenAnonymousInput.value = popupAnonymous.checked ? '1' : '';
                            }
                            if (hiddenAllowAnonymousVotesInput) {
                                hiddenAllowAnonymousVotesInput.value = popupAllowAnonymousVotes.checked ? '1' : '';
                            }
                            if (hiddenConsultationInput) {
                                hiddenConsultationInput.value = popupConsultation.checked ? '1' : '';
                            }
                            if (hiddenProposalDiscussionsInput) {
                                hiddenProposalDiscussionsInput.value = popupProposalDiscussions.checked ? '1' : '';
                            }
                            const popupVoteWeightState = popupVoteWeightEditor.getState();
                            if (hiddenVoteWeightEnabledInput) {
                                hiddenVoteWeightEnabledInput.value = popupVoteWeightState.enabled ? '1' : '';
                            }
                            if (hiddenVoteWeightQuestionInput) {
                                hiddenVoteWeightQuestionInput.value = String(popupVoteWeightState.question || '').trim();
                            }
                            if (hiddenVoteWeightOptionsInput) {
                                hiddenVoteWeightOptionsInput.value = JSON.stringify(Array.isArray(popupVoteWeightState.options) ? popupVoteWeightState.options : []);
                            }
                            syncSettingsSummary();
                            if (typeof window.commonTopbarCloseModal === 'function') {
                                window.commonTopbarCloseModal();
                            }
                        });
                    };

                    const openInvitationModal = function () {
                        if (!invitationOpenButton || typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        const invitationUrl = String(invitationOpenButton.getAttribute('data-omo-decision-invitations-url') || '');
                        if (invitationUrl === '') {
                            return;
                        }

                        const invitationTitle = String(
                            invitationOpenButton.getAttribute('data-omo-decision-invitations-title')
                            || invitationOpenButton.textContent
                            || 'Inviter des participants'
                        );

                        window.commonTopbarOpenModal(invitationTitle, invitationUrl, 'fetch');
                    };

                    const openInvitationSendModal = function () {
                        if (!invitationSendOpenButton || typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        const invitationUrl = String(invitationSendOpenButton.getAttribute('data-omo-decision-invitations-send-url') || '');
                        if (invitationUrl === '') {
                            return;
                        }

                        const invitationTitle = String(
                            invitationSendOpenButton.getAttribute('data-omo-decision-invitations-send-title')
                            || invitationSendOpenButton.textContent
                            || 'Envoyer les invitations'
                        );

                        window.commonTopbarOpenModal(invitationTitle, invitationUrl, 'fetch');
                    };

                    if (settingsOpenButton) {
                        settingsOpenButton.addEventListener('click', openSettingsModal);
                    }

                    if (invitationOpenButton) {
                        invitationOpenButton.addEventListener('click', openInvitationModal);
                    }

                    if (invitationSendOpenButton) {
                        invitationSendOpenButton.addEventListener('click', openInvitationSendModal);
                    }

                    if (payload.proposalEditable && typeof window.commonCreateVerticalSortableList === 'function') {
                        sortable = window.commonCreateVerticalSortableList({
                            list: proposalList,
                            itemSelector: '[data-omo-decision-consent-proposal-card]',
                            handleSelector: '[data-omo-decision-consent-proposal-drag]',
                            draggingClass: 'is-dragging',
                            dropTargetClass: 'is-drop-target',
                            placeholderClass: 'omo-decision-consent__proposal-placeholder',
                            createPlaceholder: function (card) {
                                const placeholder = document.createElement('div');
                                placeholder.className = 'omo-decision-consent__proposal-placeholder';
                                placeholder.style.height = Math.max(Number(card.getBoundingClientRect().height) || 0, 78) + 'px';
                                return placeholder;
                            },
                            onDragEnd: refreshProposalLabels,
                            onDrop: refreshProposalLabels
                        });
                    }

                    if (proposalAddButton && !proposalAddButton.disabled) {
                        proposalAddButton.addEventListener('click', function () {
                            const newCard = createProposalCard('');
                            proposalList.appendChild(newCard);
                            refreshProposalLabels();
                            const input = newCard.querySelector('input[name="proposals[]"]');
                            if (input) {
                                input.focus();
                            }
                        });
                    }

                    document.addEventListener('click', function (event) {
                        if (!proposalList.contains(event.target)) {
                            closeProposalMenus();
                        }
                    });

                    Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]'), bindProposalCard);
                    refreshProposalLabels();
                    syncSettingsSummary();

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        if (!payload.saveUrl) {
                            return;
                        }

                        const formData = new FormData(form);
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.dataset.originalText = submitButton.textContent;
                            submitButton.textContent = String(payload.texts && payload.texts.saving ? payload.texts.saving : 'Enregistrement...');
                        }
                        setFeedback('', false);

                        fetch(payload.saveUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(function (response) { return response.json(); })
                            .then(function (result) {
                                if (!result || !result.status) {
                                    throw new Error(result && result.message ? result.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'));
                                }
                                setFeedback(result.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);
                                if (result.redirectUrl) {
                                    if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                        window.omoDecisionOpenNestedDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, '');
                                    } else if (typeof window.commonTopbarOpenDrawer === 'function') {
                                        window.commonTopbarOpenDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, 'fetch');
                                    } else {
                                        window.location.href = result.redirectUrl;
                                    }
                                }
                            })
                            .catch(function (error) {
                                setFeedback(error && error.message ? error.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'), true);
                            })
                            .finally(function () {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = submitButton.dataset.originalText || submitButton.textContent;
                                }
                            });
                    });

                    form.dataset.omoDecisionConsentReady = '1';
                });

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-consent-response-form]'), function (form) {
                    if (form.dataset.omoDecisionConsentResponseReady === '1') {
                        return;
                    }

                    const payloadNode = form.querySelector('[data-omo-decision-consent-response-data]');
                    const submitButton = form.querySelector('[data-omo-decision-consent-response-submit]');
                    const feedbackNode = form.querySelector('[data-omo-decision-consent-response-feedback]');
                    if (!payloadNode) {
                        return;
                    }

                    let payload = {};
                    try {
                        payload = JSON.parse(payloadNode.textContent || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    const setFeedback = function (message, isError) {
                        if (!feedbackNode) {
                            return;
                        }
                        feedbackNode.textContent = String(message || '');
                        feedbackNode.classList.toggle('is-error', !!isError);
                        feedbackNode.classList.toggle('is-success', !isError && message !== '');
                    };

                    const syncChoiceState = function () {
                        Array.prototype.forEach.call(form.querySelectorAll('.omo-decision-consent__choice-option'), function (choiceOption) {
                            const radio = choiceOption.querySelector('input[type="radio"]');
                            const trigger = choiceOption.querySelector('[data-omo-decision-consent-choice-trigger]');
                            const isSelected = !!(radio && radio.checked);
                            choiceOption.classList.toggle('is-selected', isSelected);
                            if (trigger) {
                                trigger.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                            }
                        });
                    };

                    form.addEventListener('click', function (event) {
                        const trigger = event.target.closest('[data-omo-decision-consent-choice-trigger]');
                        if (!trigger || !form.contains(trigger)) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        const choiceOption = trigger.closest('.omo-decision-consent__choice-option');
                        const radio = choiceOption ? choiceOption.querySelector('input[type="radio"]') : null;
                        if (!radio || radio.disabled) {
                            return;
                        }

                        if (!radio.checked) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            syncChoiceState();
                        }
                    });

                    form.addEventListener('change', function (event) {
                        if (!event.target.matches('.omo-decision-consent__choice-input')) {
                            return;
                        }

                        syncChoiceState();
                    });

                    syncChoiceState();

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        if (!payload.saveUrl) {
                            return;
                        }

                        const formData = new FormData(form);
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.dataset.originalText = submitButton.textContent;
                            submitButton.textContent = String(payload.texts && payload.texts.saving ? payload.texts.saving : 'Enregistrement...');
                        }
                        setFeedback('', false);

                        fetch(payload.saveUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(function (response) { return response.json(); })
                            .then(function (result) {
                                if (!result || !result.status) {
                                    throw new Error(result && result.message ? result.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'));
                                }
                                setFeedback(result.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);
                                if (result.redirectUrl) {
                                    if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                        window.omoDecisionOpenNestedDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, '');
                                    } else if (typeof window.commonTopbarOpenDrawer === 'function') {
                                        window.commonTopbarOpenDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, 'fetch');
                                    } else {
                                        window.location.href = result.redirectUrl;
                                    }
                                }
                            })
                            .catch(function (error) {
                                setFeedback(error && error.message ? error.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'), true);
                            })
                            .finally(function () {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = submitButton.dataset.originalText || submitButton.textContent;
                                }
                            });
                    });

                    form.dataset.omoDecisionConsentResponseReady = '1';
                });
            };

            window.omoDecisionConsentInit(document.currentScript ? document.currentScript.parentElement : document);
        })();
        </script>

        <style>
        .omo-decision-consent__form {display:grid; gap:var(--generic-form-gap, var(--generic-space-4, 16px));}
        .omo-decision-consent {
            display: grid;
            gap: 16px;
        }

        .omo-decision-consent__head,
        .omo-decision-consent__field,
        .omo-decision-consent__settings-summary,
        .omo-decision-consent__settings-head,
        .omo-decision-consent__proposal-main,
        .omo-decision-consent__footer,
        .omo-decision-consent__result-head,
        .omo-decision-consent__choice-head,
        .omo-decision-consent__readonly-stats {
            display: grid;
            gap: 8px;
        }

        .omo-decision-consent__grid,
        .omo-decision-consent__summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .omo-decision-consent__textarea {
            min-height: 110px;
        }

        .omo-decision-consent__settings-head {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 12px;
        }

        .omo-decision-consent__readonly-stats--settings {
            gap: 10px;
        }

        .omo-decision-consent__readonly-stat {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: baseline;
            color: var(--color-text-light, #475569);
            font-size: 0.95rem;
        }

        .omo-decision-consent__readonly-stat strong {
            color: var(--color-text, #1f2937);
            font-size: 0.92rem;
        }

        .omo-decision-consent__proposal-list,
        .omo-decision-consent__choice-list,
        .omo-decision-consent__result-list {
            display: grid;
            gap: 12px;
        }

        .omo-decision-consent__proposal-placeholder {
            border: 1px dashed color-mix(in srgb, var(--color-primary, #2563eb) 32%, white);
            border-radius: var(--radius-md);
            background: color-mix(in srgb, var(--color-primary, #2563eb) 6%, white);
        }

        .omo-decision-consent__proposal-drag {
            border: 0;
            background: transparent;
            color: var(--color-text-light, #64748b);
            cursor: grab;
            font-size: 18px;
        }

        .omo-decision-consent__proposal-field {
            display: grid;
            gap: 8px;
        }

        .omo-decision-consent__proposal-menu {
            position: relative;
            align-self: start;
        }

        .omo-decision-consent__proposal-menu-toggle {
            min-width: 42px;
            padding-inline: 12px;
        }

        .omo-decision-consent__proposal-menu-item {
            width: 100%;
            justify-content: flex-start;
            box-shadow: none;
        }

        .omo-decision-consent__feedback {
            min-height: 20px;
            color: var(--color-text-light, #475569);
        }

        .omo-decision-consent__feedback.is-error {
            color: #b42318;
        }

        .omo-decision-consent__feedback.is-success {
            color: #027a48;
        }

        .omo-decision-consent__fieldset {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            border: 0;
            min-inline-size: 0;
        }

        .omo-decision-consent__choice-scale {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
        }

        .omo-decision-consent__choice-option {
            display: block;
            position: relative;
            --omo-consent-choice-bg: #ffffff;
            --omo-consent-choice-bg-selected: #f8fafc;
            --omo-consent-choice-border: color-mix(in srgb, var(--color-text-light, #64748b) 18%, white);
            --omo-consent-choice-border-selected: color-mix(in srgb, var(--color-primary, #2563eb) 40%, white);
            --omo-consent-choice-text: var(--color-text, #1f2937);
            --omo-consent-choice-shadow: none;
        }

        .omo-decision-consent__choice-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .omo-decision-consent__choice-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            min-height: 58px;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--omo-consent-choice-border);
            background: var(--omo-consent-choice-bg);
            text-align: left;
            cursor: pointer;
            font: inherit;
            color: var(--omo-consent-choice-text);
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .omo-decision-consent__choice-chip:hover,
        .omo-decision-consent__choice-chip:focus-visible {
            border-color: var(--omo-consent-choice-border-selected);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--omo-consent-choice-border-selected) 18%, transparent);
        }

        .omo-decision-consent__choice-label {
            flex: 1 1 auto;
        }

        .omo-decision-consent__choice-icon {
            display: inline-block;
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            background-color: currentColor;
            mask-image: var(--omo-consent-choice-icon);
            mask-repeat: no-repeat;
            mask-position: center;
            mask-size: contain;
            -webkit-mask-image: var(--omo-consent-choice-icon);
            -webkit-mask-repeat: no-repeat;
            -webkit-mask-position: center;
            -webkit-mask-size: contain;
        }

        .omo-decision-consent__choice-icon--small {
            width: 16px;
            height: 16px;
            flex-basis: 16px;
        }

        .omo-decision-consent__choice-option.is-selected .omo-decision-consent__choice-chip {
            border-color: var(--omo-consent-choice-border-selected);
            background: var(--omo-consent-choice-bg-selected);
            box-shadow: var(--omo-consent-choice-shadow);
            transform: translateY(-1px);
        }

        .omo-decision-consent__choice-option--objection {
            --omo-consent-choice-bg: #fff2f1;
            --omo-consent-choice-bg-selected: #ffdedd;
            --omo-consent-choice-border: #f5b4af;
            --omo-consent-choice-border-selected: #d84f45;
            --omo-consent-choice-text: #8a1c14;
            --omo-consent-choice-shadow: 0 10px 24px rgba(216, 79, 69, 0.16);
        }

        .omo-decision-consent__choice-option--no_objection {
            --omo-consent-choice-bg: #fff8e8;
            --omo-consent-choice-bg-selected: #ffe9b3;
            --omo-consent-choice-border: #f2cf75;
            --omo-consent-choice-border-selected: #d19710;
            --omo-consent-choice-text: #8a5a00;
            --omo-consent-choice-shadow: 0 10px 24px rgba(209, 151, 16, 0.18);
        }

        .omo-decision-consent__choice-option--favor {
            --omo-consent-choice-bg: #effaf1;
            --omo-consent-choice-bg-selected: #d8f2dd;
            --omo-consent-choice-border: #a6ddb0;
            --omo-consent-choice-border-selected: #2f8f46;
            --omo-consent-choice-text: #166534;
            --omo-consent-choice-shadow: 0 10px 24px rgba(47, 143, 70, 0.17);
        }

        .omo-decision-consent__result-card.is-selected {
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, white);
            background: color-mix(in srgb, var(--color-primary, #2563eb) 7%, white);
        }

        .omo-decision-consent__summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, white);
            color: var(--color-text-dark, #0f172a);
            font-size: 13px;
        }

        .omo-decision-consent__summary-badge--objection {
            background: #fff0ef;
            color: #8a1c14;
        }

        .omo-decision-consent__summary-badge--no_objection {
            background: #fff6df;
            color: #8a5a00;
        }

        .omo-decision-consent__summary-badge--favor {
            background: #edf9ef;
            color: #166534;
        }

        .omo-decision-consent__result-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: baseline;
            color: var(--color-text-light, #475569);
            font-size: 14px;
        }

        .omo-decision-consent__result-meta-label {
            color: var(--color-text-light, #475569);
        }

        .omo-decision-consent__distribution {
            display: flex;
            gap: 2px;
            align-items: stretch;
            min-height: 18px;
            padding: 4px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, white);
            overflow: hidden;
        }

        .omo-decision-consent__distribution-segment {
            display: block;
            min-width: 0;
            min-height: 10px;
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .omo-decision-consent__distribution-segment:hover {
            transform: scaleY(1.08);
            filter: brightness(0.96);
        }

        .omo-decision-consent__distribution-segment--favor {
            background: #2e7d32;
        }

        .omo-decision-consent__distribution-segment--no_objection {
            background: #d69e15;
        }

        .omo-decision-consent__distribution-segment--objection {
            background: #c62828;
        }

        .omo-decision-consent__distribution-scale {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--color-text-light, #475569);
            font-size: 12px;
        }

        .omo-decision-consent__distribution-label--objection {
            color: #8a1c14;
        }

        .omo-decision-consent__distribution-label--no_objection {
            color: #8a5a00;
        }

        .omo-decision-consent__distribution-label--favor {
            color: #166534;
        }

        .omo-decision-consent__modal {
            display: grid;
            gap: 14px;
        }

        .omo-decision-consent__modal-option {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .omo-decision-consent__modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        @media (max-width: 700px) {
            .omo-decision-consent__proposal-card {
                grid-template-columns: 1fr;
            }

            .omo-decision-consent__choice-scale {
                grid-template-columns: 1fr;
            }

            .omo-decision-consent__distribution-scale {
                display: grid;
                grid-template-columns: 1fr;
                gap: 4px;
            }
        }
        </style>
        <?php
    }
}
