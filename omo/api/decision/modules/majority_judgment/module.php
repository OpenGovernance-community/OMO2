<?php

use dbObject\DecisionProcess;
use dbObject\DecisionGroup;
use dbObject\DecisionResponse;

require_once __DIR__ . '/shared.php';

if (!function_exists('omoDecisionMajorityJudgmentModuleGetSourceLang')) {
    function omoDecisionMajorityJudgmentModuleGetSourceLang()
    {
        return [
            'decisions.majority_judgment.title' => ['text' => 'Configurer un jugement majoritaire', 'context' => 'Title of the majority judgment management screen.'],
            'decisions.majority_judgment.description' => ['text' => 'Creez un scrutin ou chaque participant attribue une mention a chaque proposition sur une echelle commune.', 'context' => 'Description of the majority judgment management screen.'],
            'decisions.majority_judgment.view_title' => ['text' => 'Voir le scrutin', 'context' => 'Title of the read-only majority judgment screen.'],
            'decisions.majority_judgment.view_description' => ['text' => 'Consultez le scrutin, ses reglages et ses propositions sans modifier sa configuration.', 'context' => 'Description of the read-only majority judgment screen.'],
            'decisions.majority_judgment.participate_title' => ['text' => 'Participer au scrutin', 'context' => 'Title of the majority judgment participation screen.'],
            'decisions.majority_judgment.participate_description' => ['text' => 'Attribuez une mention a chaque proposition sur l echelle du jugement majoritaire.', 'context' => 'Description of the majority judgment participation screen.'],
            'decisions.majority_judgment.change_method' => ['text' => 'Changer de methode', 'context' => 'Secondary action to go back to the method chooser.'],
            'decisions.majority_judgment.notice.started' => ['text' => 'La consultation a commence. Le titre, la description, le type et les propositions sont desormais verrouilles.', 'context' => 'Notice shown when the core structure is locked after start.'],
            'decisions.majority_judgment.notice.responses' => ['text' => 'Au moins une reponse a deja ete soumise. Seuls le statut et les dates de fin restent ajustables.', 'context' => 'Notice shown when some schedule fields are also locked.'],
            'decisions.majority_judgment.notice.consultation_proposals' => ['text' => 'Les propositions restent ajustables pendant la consultation tant qu aucune reponse n a ete soumise.', 'context' => 'Notice shown when proposal editing remains allowed.'],
            'decisions.majority_judgment.notice.results' => ['text' => 'Ce scrutin est termine. Seule la consultation des resultats reste disponible.', 'context' => 'Notice shown when the vote is in results or archived mode.'],
            'decisions.majority_judgment.field.title' => ['text' => 'Question', 'context' => 'Label for the group title field.'],
            'decisions.majority_judgment.field.description' => ['text' => 'Description de la question', 'context' => 'Label for the group description field.'],
            'decisions.majority_judgment.field.process_title' => ['text' => 'Titre du processus', 'context' => 'Label for the process title field.'],
            'decisions.majority_judgment.field.process_description' => ['text' => 'Description du contexte', 'context' => 'Label for the process description field.'],
            'decisions.majority_judgment.field.process_section' => ['text' => 'Contexte du processus', 'context' => 'Section title for process-level context fields.'],
            'decisions.majority_judgment.field.group_section' => ['text' => 'Question de ce groupe', 'context' => 'Section title for group-level question fields.'],
            'decisions.majority_judgment.field.type' => ['text' => 'Type de prise de decision', 'context' => 'Label for the decision type field.'],
            'decisions.majority_judgment.field.status' => ['text' => 'Statut', 'context' => 'Label for the status field.'],
            'decisions.majority_judgment.field.consultation_start' => ['text' => 'Debut de consultation', 'context' => 'Label for the consultation start field.'],
            'decisions.majority_judgment.field.consultation_end' => ['text' => 'Fin de consultation', 'context' => 'Label for the consultation end field.'],
            'decisions.majority_judgment.field.evaluation_start' => ['text' => 'Debut de vote', 'context' => 'Label for the evaluation start field.'],
            'decisions.majority_judgment.field.evaluation_end' => ['text' => 'Cloture du vote', 'context' => 'Label for the evaluation end field.'],
            'decisions.majority_judgment.field.proposals' => ['text' => 'Propositions', 'context' => 'Label for the proposals list.'],
            'decisions.majority_judgment.field.proposals_hint' => ['text' => 'Ajoutez une proposition par ligne, puis reorganisez-les par glisser-deposer. Au moins deux propositions sont necessaires.', 'context' => 'Hint under the proposals list.'],
            'decisions.majority_judgment.field.proposals_add' => ['text' => 'Ajouter une proposition', 'context' => 'Button label to append a new proposal field.'],
            'decisions.majority_judgment.field.proposals_remove' => ['text' => 'Supprimer', 'context' => 'Button label to remove a proposal field.'],
            'decisions.majority_judgment.field.proposals_reorder' => ['text' => 'Reordonner', 'context' => 'Aria label for the proposal drag handle.'],
            'decisions.majority_judgment.field.proposals_item' => ['text' => 'Proposition {index}', 'context' => 'Visible label prefix for one proposal row.'],
            'decisions.majority_judgment.field.proposal_details' => ['text' => 'Details', 'context' => 'Button label opening the proposal detail popup.'],
            'decisions.majority_judgment.field.proposal_description' => ['text' => 'Description de la proposition', 'context' => 'Label for the proposal description field.'],
            'decisions.majority_judgment.field.proposal_info_url' => ['text' => 'URL d information', 'context' => 'Label for the proposal info URL field.'],
            'decisions.majority_judgment.field.settings' => ['text' => 'Parametres du scrutin', 'context' => 'Section title for judgment-specific settings.'],
            'decisions.majority_judgment.field.scale' => ['text' => 'Echelle de mentions', 'context' => 'Label for the scale summary.'],
            'decisions.majority_judgment.field.scale_summary' => ['text' => 'Echelle configurable jusqu a 7 mentions', 'context' => 'Summary label for the configurable majority judgment scale.'],
            'decisions.majority_judgment.field.scale_slot' => ['text' => 'Mention {index}', 'context' => 'Label for one configurable slot of the majority judgment scale.'],
            'decisions.majority_judgment.field.scale_label' => ['text' => 'Libelle', 'context' => 'Label for one majority judgment mention label input.'],
            'decisions.majority_judgment.field.scale_active' => ['text' => 'Active', 'context' => 'Label for one majority judgment mention activation toggle.'],
            'decisions.majority_judgment.field.scale_empty' => ['text' => 'Aucune mention active', 'context' => 'Fallback text when no mention is active in the configured scale.'],
            'decisions.majority_judgment.field.scale_center_hint' => ['text' => 'La mention centrale reste hors calcul si elle est active.', 'context' => 'Hint explaining that the central mention stays excluded from the majority computation when enabled.'],
            'decisions.majority_judgment.field.anonymous' => ['text' => 'Vote anonyme', 'context' => 'Label for the anonymity setting.'],
            'decisions.majority_judgment.field.allow_consultation_proposals' => ['text' => 'Autoriser les propositions pendant la consultation', 'context' => 'Label for allowing proposals during consultation.'],
            'decisions.majority_judgment.field.your_scores' => ['text' => 'Vos mentions', 'context' => 'Legend for the participant scoring fieldset.'],
            'decisions.majority_judgment.field.current_response' => ['text' => 'Vote enregistre', 'context' => 'Label shown when a previous response exists.'],
            'decisions.majority_judgment.field.total_votes' => ['text' => 'Votes enregistres', 'context' => 'Label for the total number of submitted votes.'],
            'decisions.majority_judgment.field.majority_mention' => ['text' => 'Mention majoritaire', 'context' => 'Label for the majority mention of one proposal.'],
            'decisions.majority_judgment.field.proposal_votes' => ['text' => 'Mentions recues', 'context' => 'Label for the number of received mentions.'],
            'decisions.majority_judgment.field.counted_mentions' => ['text' => 'Mentions prises en compte', 'context' => 'Label for the number of mentions counted in the majority judgment ranking.'],
            'decisions.majority_judgment.field.no_opinion_count' => ['text' => 'Sans avis', 'context' => 'Label for the number of no-opinion answers excluded from the majority judgment calculation.'],
            'decisions.majority_judgment.field.distribution' => ['text' => 'Repartition des mentions', 'context' => 'Label for the graphical distribution of mentions.'],
            'decisions.majority_judgment.results_sort.aria' => ['text' => 'Ordre d affichage des resultats du jugement majoritaire', 'context' => 'Aria label for the majority judgment results sort switch.'],
            'decisions.majority_judgment.results_sort.rank' => ['text' => 'Classement', 'context' => 'Button label used to sort majority judgment results by majority ranking.'],
            'decisions.majority_judgment.results_sort.initial' => ['text' => 'Ordre initial', 'context' => 'Button label used to sort majority judgment results by saved proposal order.'],
            'decisions.majority_judgment.results_sort.alpha' => ['text' => 'Alphabetique', 'context' => 'Button label used to sort majority judgment results alphabetically.'],
            'decisions.majority_judgment.field.select_all' => ['text' => 'Attribuez une mention a chaque proposition.', 'context' => 'Help text for the participation form.'],
            'decisions.majority_judgment.option.type.decision' => ['text' => 'Decisionnaire', 'context' => 'Select option for a decision-oriented process.'],
            'decisions.majority_judgment.option.type.consultation' => ['text' => 'Consultative', 'context' => 'Select option for a consultation-oriented process.'],
            'decisions.majority_judgment.option.status.draft' => ['text' => 'En preparation', 'context' => 'Draft status option.'],
            'decisions.majority_judgment.option.status.scheduled' => ['text' => 'Planifiee', 'context' => 'Scheduled status option.'],
            'decisions.majority_judgment.option.status.consultation' => ['text' => 'En consultation', 'context' => 'Consultation status option.'],
            'decisions.majority_judgment.option.status.evaluation' => ['text' => 'En evaluation', 'context' => 'Evaluation status option.'],
            'decisions.majority_judgment.option.status.results' => ['text' => 'Resultats', 'context' => 'Results status option.'],
            'decisions.majority_judgment.option.status.archived' => ['text' => 'Archivee', 'context' => 'Archived status option.'],
            'decisions.majority_judgment.option.common.yes' => ['text' => 'Oui', 'context' => 'Generic yes option label.'],
            'decisions.majority_judgment.option.common.no' => ['text' => 'Non', 'context' => 'Generic no option label.'],
            'decisions.majority_judgment.placeholder.title' => ['text' => 'Ex. Quelle option preferez-vous ?', 'context' => 'Placeholder for the group title field.'],
            'decisions.majority_judgment.placeholder.description' => ['text' => 'Precisez la question, les nuances et les criteres utiles...', 'context' => 'Placeholder for the group description field.'],
            'decisions.majority_judgment.placeholder.process_title' => ['text' => 'Ex. Organisation du repas de fin d annee', 'context' => 'Placeholder for the process title field.'],
            'decisions.majority_judgment.placeholder.process_description' => ['text' => 'Contexte global, informations communes, cadre de la consultation...', 'context' => 'Placeholder for the process description field.'],
            'decisions.majority_judgment.placeholder.proposals' => ['text' => 'Nom de la proposition', 'context' => 'Placeholder for one proposal input.'],
            'decisions.majority_judgment.placeholder.proposal_info_url' => ['text' => 'https://...', 'context' => 'Placeholder for one proposal info URL input.'],
            'decisions.majority_judgment.action.create' => ['text' => 'Creer le scrutin', 'context' => 'Submit label for a new majority judgment.'],
            'decisions.majority_judgment.action.save' => ['text' => 'Enregistrer le scrutin', 'context' => 'Submit label for an existing majority judgment.'],
            'decisions.majority_judgment.action.saving' => ['text' => 'Enregistrement...', 'context' => 'Temporary label while saving.'],
            'decisions.majority_judgment.action.configure' => ['text' => 'Configurer', 'context' => 'Button label to open the settings modal.'],
            'decisions.majority_judgment.action.close' => ['text' => 'Fermer', 'context' => 'Button label to close the settings modal.'],
            'decisions.majority_judgment.action.apply' => ['text' => 'Appliquer', 'context' => 'Button label to apply the settings modal changes.'],
            'decisions.majority_judgment.action.proposal_apply' => ['text' => 'Enregistrer les details', 'context' => 'Button label used to save proposal detail popup fields.'],
            'decisions.majority_judgment.action.submit_response' => ['text' => 'Enregistrer mes mentions', 'context' => 'Submit label for a new response.'],
            'decisions.majority_judgment.action.update_response' => ['text' => 'Mettre a jour mes mentions', 'context' => 'Submit label when updating an existing response.'],
            'decisions.majority_judgment.action.submitting_response' => ['text' => 'Enregistrement du vote...', 'context' => 'Temporary label while saving a response.'],
            'decisions.majority_judgment.feedback.success' => ['text' => 'Scrutin enregistre.', 'context' => 'Generic success feedback after saving.'],
            'decisions.majority_judgment.feedback.error' => ['text' => 'Impossible d enregistrer ce scrutin pour le moment.', 'context' => 'Generic error feedback after saving.'],
            'decisions.majority_judgment.feedback.response_success' => ['text' => 'Vote enregistre.', 'context' => 'Generic success feedback after saving a response.'],
            'decisions.majority_judgment.feedback.response_error' => ['text' => 'Impossible d enregistrer votre vote pour le moment.', 'context' => 'Generic error feedback after saving a response.'],
            'decisions.majority_judgment.empty_proposals' => ['text' => 'Aucune proposition active pour le moment.', 'context' => 'Fallback text when no active proposal exists.'],
            'decisions.majority_judgment.empty_results' => ['text' => 'Aucune mention n a encore ete enregistree pour ce scrutin.', 'context' => 'Fallback text when no submitted response exists yet.'],
            'decisions.majority_judgment.tooltip.segment' => ['text' => '{mention}: {count} mention(s) ({percent} %)', 'context' => 'Tooltip shown on one segment of the majority judgment result bar.'],
            'decisions.majority_judgment.drawer_title' => ['text' => 'Prises de decision', 'context' => 'Drawer title reused after saving.'],
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentFormatDateTimeLocal')) {
    function omoDecisionMajorityJudgmentFormatDateTimeLocal($value)
    {
        if (!$value instanceof DateTimeInterface) {
            return '';
        }

        return $value->format('Y-m-d\TH:i');
    }
}

if (!function_exists('omoDecisionMajorityJudgmentModuleRender')) {
    function omoDecisionMajorityJudgmentModuleRender(array $renderContext)
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
        $proposalItems = [];
        if ($decisionGroup instanceof DecisionGroup) {
            foreach ($decisionGroup->getProposals(true) as $proposal) {
                $proposalObjects[] = $proposal;
                $proposalItems[] = [
                    'title' => trim((string)$proposal->get('title')),
                    'description' => trim((string)$proposal->get('description')) ?: null,
                    'info_url' => omoDecisionNormalizeProposalInfoUrl($proposal->get('info_url')),
                ];
            }
        }

        if (count($proposalItems) === 0) {
            $proposalItems = [
                ['title' => '', 'description' => null, 'info_url' => null],
                ['title' => '', 'description' => null, 'info_url' => null],
            ];
        } elseif (count($proposalItems) === 1) {
            $proposalItems[] = ['title' => '', 'description' => null, 'info_url' => null];
        }

        $config = $decisionGroup instanceof DecisionGroup
            ? omoDecisionMajorityJudgmentBuildConfig($decisionGroup)
            : omoDecisionMajorityJudgmentBuildConfig([]);
        $isAnonymous = !empty($config['is_anonymous']);
        $allowConsultationProposals = !empty($config['allow_consultation_proposals']);
        $mentions = $config['mentions'];
        $allMentions = $config['all_mentions'];
        $mentionOptions = $config['mention_options'];
        $scaleSummary = (string)($config['scale_summary'] ?? t('decisions.majority_judgment.field.scale_empty', [], $lang, $sourceLang));
        $noOpinionLabel = (string)($config['no_opinion_label'] ?? '');

        $consultationStarted = $decision instanceof DecisionProcess ? $decision->hasConsultationStarted() : false;
        $hasSubmittedResponses = $decision instanceof DecisionProcess ? $decision->hasSubmittedResponses() : false;
        $resultsMode = $decision instanceof DecisionProcess
            && in_array($status, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true);
        $coreLocked = $decision instanceof DecisionProcess && $consultationStarted;
        $startDatesLocked = $decision instanceof DecisionProcess && $hasSubmittedResponses;
        $isEditable = $isManageMode && !$resultsMode;
        $canEditStructure = $isEditable && !$coreLocked;
        $canEditProposals = $isEditable && (!$consultationStarted || (!$hasSubmittedResponses && $allowConsultationProposals));
        $canEditStartDates = $isEditable && !$startDatesLocked;
        $publicLayout = (($context['accessMode'] ?? '') === 'public') || !empty($context['previewLayout']);

        $participant = $context['participant'] ?? null;
        $selectedResponse = null;
        $selectedScores = [];
        if ($decision instanceof DecisionProcess && $participant && (int)$participant->getId() > 0) {
            $selectedResponse = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId(), $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0);
            $selectedScores = omoDecisionMajorityJudgmentExtractScores($selectedResponse);
        }

        $submittedResponses = [];
        $submittedVoteCount = 0;
        if ($decision instanceof DecisionProcess) {
            foreach (($decisionGroup instanceof DecisionGroup ? $decisionGroup->getResponses(DecisionResponse::STATUS_SUBMITTED) : $decision->getResponses(DecisionResponse::STATUS_SUBMITTED)) as $submittedResponse) {
                $submittedResponses[] = $submittedResponse;
                $submittedVoteCount++;
            }
        }
        $proposalStats = omoDecisionMajorityJudgmentBuildStats($proposalObjects, $submittedResponses, $config);
        $proposalOriginalOrder = [];
        foreach ($proposalObjects as $proposalIndex => $proposal) {
            $proposalId = ($proposal instanceof \dbObject\DecisionProposal) ? (int)$proposal->getId() : 0;
            $proposalOriginalOrder[$proposalId] = ($proposal instanceof \dbObject\DecisionProposal && (int)$proposal->get('position') > 0)
                ? (int)$proposal->get('position')
                : ($proposalIndex + 1);
        }
        $resultProposalObjects = $proposalObjects;
        if ($resultsMode && count($resultProposalObjects) > 1) {
            usort($resultProposalObjects, function ($left, $right) use ($proposalStats, $proposalOriginalOrder, $config) {
                $leftId = $left instanceof \dbObject\DecisionProposal ? (int)$left->getId() : 0;
                $rightId = $right instanceof \dbObject\DecisionProposal ? (int)$right->getId() : 0;
                $comparison = omoDecisionMajorityJudgmentCompareStats(
                    (array)($proposalStats[$leftId] ?? []),
                    (array)($proposalStats[$rightId] ?? []),
                    $config
                );

                if ($comparison !== 0) {
                    return $comparison;
                }

                return (int)($proposalOriginalOrder[$leftId] ?? 0) <=> (int)($proposalOriginalOrder[$rightId] ?? 0);
            });
        }
        $showResultsSortSwitch = $resultsMode && count($proposalObjects) > 1;

        $headerTitleKey = 'decisions.majority_judgment.title';
        $headerDescriptionKey = 'decisions.majority_judgment.description';
        if ($isParticipateMode) {
            $headerTitleKey = 'decisions.majority_judgment.participate_title';
            $headerDescriptionKey = 'decisions.majority_judgment.participate_description';
        } elseif ($isViewMode) {
            $headerTitleKey = 'decisions.majority_judgment.view_title';
            $headerDescriptionKey = 'decisions.majority_judgment.view_description';
        }

        $managePayload = [
            'saveUrl' => '/omo/api/decision/modules/majority_judgment/save.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'manage'),
            'drawerTitle' => t('decisions.majority_judgment.drawer_title', [], $lang, $sourceLang),
            'proposalEditable' => $canEditProposals,
            'texts' => [
                'save' => $decision instanceof DecisionProcess
                    ? t('decisions.majority_judgment.action.save', [], $lang, $sourceLang)
                    : t('decisions.majority_judgment.action.create', [], $lang, $sourceLang),
                'saving' => t('decisions.majority_judgment.action.saving', [], $lang, $sourceLang),
                'success' => t('decisions.majority_judgment.feedback.success', [], $lang, $sourceLang),
                'error' => t('decisions.majority_judgment.feedback.error', [], $lang, $sourceLang),
                'proposalPlaceholder' => t('decisions.majority_judgment.placeholder.proposals', [], $lang, $sourceLang),
                'proposalInfoUrlPlaceholder' => t('decisions.majority_judgment.placeholder.proposal_info_url', [], $lang, $sourceLang),
                'proposalRemove' => t('decisions.majority_judgment.field.proposals_remove', [], $lang, $sourceLang),
                'proposalReorder' => t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang),
                'proposalDetails' => t('decisions.majority_judgment.field.proposal_details', [], $lang, $sourceLang),
                'proposalDescriptionLabel' => t('decisions.majority_judgment.field.proposal_description', [], $lang, $sourceLang),
                'proposalInfoUrlLabel' => t('decisions.majority_judgment.field.proposal_info_url', [], $lang, $sourceLang),
                'proposalApply' => t('decisions.majority_judgment.action.proposal_apply', [], $lang, $sourceLang),
                'proposalItemTemplate' => t('decisions.majority_judgment.field.proposals_item', ['index' => '__INDEX__'], $lang, $sourceLang),
                'yesLabel' => t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang),
                'noLabel' => t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang),
                'scaleSummaryEmpty' => t('decisions.majority_judgment.field.scale_empty', [], $lang, $sourceLang),
            ],
        ];

        $responsePayload = [
            'saveUrl' => '/omo/api/decision/modules/majority_judgment/respond.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'participate'),
            'drawerTitle' => t('decisions.majority_judgment.drawer_title', [], $lang, $sourceLang),
            'texts' => [
                'save' => $selectedResponse instanceof DecisionResponse
                    ? t('decisions.majority_judgment.action.update_response', [], $lang, $sourceLang)
                    : t('decisions.majority_judgment.action.submit_response', [], $lang, $sourceLang),
                'saving' => t('decisions.majority_judgment.action.submitting_response', [], $lang, $sourceLang),
                'success' => t('decisions.majority_judgment.feedback.response_success', [], $lang, $sourceLang),
                'error' => t('decisions.majority_judgment.feedback.response_error', [], $lang, $sourceLang),
            ],
        ];

        $managePayloadJson = omoDecisionModuleEncodeJsonPayload(
            $managePayload,
            '{"saveUrl":"","redirectUrl":"","drawerTitle":"","proposalEditable":false,"texts":{}}'
        );
        $responsePayloadJson = omoDecisionModuleEncodeJsonPayload(
            $responsePayload,
            '{"saveUrl":"","redirectUrl":"","drawerTitle":"","texts":{}}'
        );
        $consultationProposalPanel = ($publicLayout && $decision instanceof DecisionProcess)
            ? omoDecisionRenderConsultationProposalPublicPanel($decision, $context, $escape, 'omo-decision-majority-judgment__consultation-panel')
            : '';
        ?>
        <section class="generic-section generic-section--stack omo-decision-majority-judgment">
            <?php if (!$publicLayout): ?>
            <div class="omo-decision-majority-judgment__head">
                <div class="omo-decision-majority-judgment__copy">
                    <h3 class="generic-card-title generic-card-title--section"><?= $escape(t($headerTitleKey, [], $lang, $sourceLang)) ?></h3>
                    <p class="omo-decision-majority-judgment__text"><?= $escape(t($headerDescriptionKey, [], $lang, $sourceLang)) ?></p>
                </div>

                <?php if (!$decision instanceof DecisionProcess): ?>
                <a
                    class="generic-action-button generic-action-button--secondary"
                    href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'])) ?>"
                    data-omo-decision-editor-link
                    data-omo-decision-editor-title="<?= $escape(t('decisions.edit.create_title', [], $lang, $sourceLang)) ?>"
                >
                    <?= $escape(t('decisions.majority_judgment.change_method', [], $lang, $sourceLang)) ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($resultsMode && !$publicLayout): ?>
            <div class="generic-soft-panel generic-soft-panel--stack omo-decision-majority-judgment__notice">
                <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.notice.results', [], $lang, $sourceLang)) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($isManageMode): ?>
                <?php if ($coreLocked): ?>
                <div class="generic-soft-panel generic-soft-panel--stack omo-decision-majority-judgment__notice">
                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.notice.started', [], $lang, $sourceLang)) ?></p>
                    <?php if ($canEditProposals && !$hasSubmittedResponses): ?>
                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.notice.consultation_proposals', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                    <?php if ($hasSubmittedResponses): ?>
                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.notice.responses', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form class="omo-decision-majority-judgment__form" action="/omo/api/decision/modules/majority_judgment/save.php" method="post" data-omo-decision-majority-judgment-form>
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                    <input type="hidden" name="intent" value="manage">
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>
                    <input type="hidden" name="evaluation_method" value="<?= $escape(DecisionProcess::METHOD_MAJORITY_JUDGMENT) ?>">

                    <div class="generic-card-title"><?= $escape(t('decisions.majority_judgment.field.process_section', [], $lang, $sourceLang)) ?></div>

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.process_title', [], $lang, $sourceLang)) ?></span>
                        <input type="text" name="process_title" class="generic-form-control" required maxlength="190" value="<?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.process_title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>>
                    </label>

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.process_description', [], $lang, $sourceLang)) ?></span>
                        <textarea name="process_description" class="generic-form-control omo-decision-majority-judgment__textarea" rows="3" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.process_description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('description')) : '') ?></textarea>
                    </label>

                    <div class="omo-decision-majority-judgment__grid">
                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.status', [], $lang, $sourceLang)) ?></span>
                            <select name="status" class="generic-form-control" <?= $isEditable ? '' : 'disabled' ?>>
                                <?php foreach ([
                                    DecisionProcess::STATUS_DRAFT => 'decisions.majority_judgment.option.status.draft',
                                    DecisionProcess::STATUS_SCHEDULED => 'decisions.majority_judgment.option.status.scheduled',
                                    DecisionProcess::STATUS_CONSULTATION => 'decisions.majority_judgment.option.status.consultation',
                                    DecisionProcess::STATUS_EVALUATION => 'decisions.majority_judgment.option.status.evaluation',
                                    DecisionProcess::STATUS_RESULTS => 'decisions.majority_judgment.option.status.results',
                                    DecisionProcess::STATUS_ARCHIVED => 'decisions.majority_judgment.option.status.archived',
                                ] as $statusKey => $statusLabel): ?>
                                <option value="<?= $escape($statusKey) ?>"<?= $status === $statusKey ? ' selected' : '' ?>><?= $escape(t($statusLabel, [], $lang, $sourceLang)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.consultation_start', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="consultation_start_at" class="generic-form-control" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_start_at')) : '') ?>" <?= $canEditStartDates ? '' : 'disabled' ?>>
                        </label>

                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.consultation_end', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="consultation_end_at" class="generic-form-control" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_end_at')) : '') ?>" <?= $isEditable ? '' : 'disabled' ?>>
                        </label>

                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.evaluation_start', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="evaluation_start_at" class="generic-form-control" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_start_at')) : '') ?>" <?= $canEditStartDates ? '' : 'disabled' ?>>
                        </label>

                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.evaluation_end', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="evaluation_end_at" class="generic-form-control" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_end_at')) : '') ?>" <?= $isEditable ? '' : 'disabled' ?>>
                        </label>
                    </div>

                    <?php if (function_exists('omoDecisionRenderEditorGroupSwitch')) {
                        omoDecisionRenderEditorGroupSwitch($context, $decision instanceof DecisionProcess ? $decision : null, $decisionGroup instanceof DecisionGroup ? $decisionGroup : null, $decision instanceof DecisionProcess ? $decision->getDecisionGroups(false) : [], $lang, $sourceLang, $escape);
                    } ?>

                    <div class="generic-card-title"><?= $escape(t('decisions.majority_judgment.field.group_section', [], $lang, $sourceLang)) ?></div>

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.title', [], $lang, $sourceLang)) ?></span>
                        <input type="text" name="title" class="generic-form-control" required maxlength="190" value="<?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>>
                    </label>

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.description', [], $lang, $sourceLang)) ?></span>
                        <textarea name="description" class="generic-form-control omo-decision-majority-judgment__textarea" rows="4" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('description')) : '') ?></textarea>
                    </label>

                    <div class="omo-decision-majority-judgment__grid">
                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.type', [], $lang, $sourceLang)) ?></span>
                            <select name="decision_type" class="generic-form-control" <?= $canEditStructure ? '' : 'disabled' ?>>
                                <option value="<?= $escape(DecisionProcess::TYPE_DECISION) ?>"<?= $decisionType === DecisionProcess::TYPE_DECISION ? ' selected' : '' ?>><?= $escape(t('decisions.majority_judgment.option.type.decision', [], $lang, $sourceLang)) ?></option>
                                <option value="<?= $escape(DecisionProcess::TYPE_CONSULTATION) ?>"<?= $decisionType === DecisionProcess::TYPE_CONSULTATION ? ' selected' : '' ?>><?= $escape(t('decisions.majority_judgment.option.type.consultation', [], $lang, $sourceLang)) ?></option>
                            </select>
                        </label>
                    </div>

                    <div class="generic-soft-panel generic-soft-panel--stack omo-decision-majority-judgment__settings-summary">
                        <input type="hidden" name="is_anonymous" value="<?= $isAnonymous ? '1' : '' ?>" data-omo-decision-mj-hidden-anonymous>
                        <input type="hidden" name="allow_consultation_proposals" value="<?= $allowConsultationProposals ? '1' : '' ?>" data-omo-decision-mj-hidden-consultation-proposals>
                        <?php foreach ($mentionOptions as $mentionScore => $mentionOption): ?>
                        <input
                            type="hidden"
                            name="mention_labels[<?= $escape((string)$mentionScore) ?>]"
                            value="<?= $escape((string)$mentionOption['label']) ?>"
                            data-omo-decision-mj-hidden-mention-label="<?= $escape((string)$mentionScore) ?>"
                            data-default-label="<?= $escape((string)$mentionOption['default_label']) ?>"
                        >
                        <input
                            type="hidden"
                            name="mention_active[<?= $escape((string)$mentionScore) ?>]"
                            value="<?= !empty($mentionOption['active']) ? '1' : '' ?>"
                            data-omo-decision-mj-hidden-mention-active="<?= $escape((string)$mentionScore) ?>"
                        >
                        <?php endforeach; ?>
                        <div class="omo-decision-majority-judgment__settings-head">
                            <div class="omo-decision-majority-judgment__field">
                                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.settings', [], $lang, $sourceLang)) ?></span>
                                <div class="omo-decision-majority-judgment__readonly-stats">
                                    <span class="omo-decision-majority-judgment__readonly-stat">
                                        <strong><?= $escape(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang)) ?></strong>
                                        <span data-omo-decision-mj-scale-summary><?= $escape($scaleSummary) ?></span>
                                    </span>
                                    <span class="omo-decision-majority-judgment__readonly-stat">
                                        <strong><?= $escape(t('decisions.majority_judgment.field.anonymous', [], $lang, $sourceLang)) ?></strong>
                                        <span data-omo-decision-mj-anonymous-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($isAnonymous ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-decision-majority-judgment__readonly-stat">
                                        <strong><?= $escape(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></strong>
                                        <span data-omo-decision-mj-consultation-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($allowConsultationProposals ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                </div>
                            </div>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-mj-settings-open data-omo-decision-mj-settings-title="<?= $escape(t('decisions.majority_judgment.field.settings', [], $lang, $sourceLang)) ?>"><?= $escape(t('decisions.majority_judgment.action.configure', [], $lang, $sourceLang)) ?></button>
                        </div>
                        <template data-omo-decision-mj-settings-template>
                            <div class="omo-decision-majority-judgment-popup__grid">
                                <div class="generic-soft-panel generic-soft-panel--stack">
                                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang)) ?></span>
                                    <div class="omo-decision-majority-judgment-popup__mention-list">
                                        <?php foreach ($mentionOptions as $mentionScore => $mentionOption): ?>
                                        <div class="omo-decision-majority-judgment-popup__mention-row">
                                            <label class="omo-decision-majority-judgment-popup__field">
                                                <span class="generic-card-title generic-card-title--small"><?= $escape(str_replace('{index}', (string)($mentionScore + 1), t('decisions.majority_judgment.field.scale_slot', ['index' => (string)($mentionScore + 1)], $lang, $sourceLang))) ?></span>
                                                <input
                                                    type="text"
                                                    class="generic-form-control"
                                                    value="<?= $escape((string)$mentionOption['label']) ?>"
                                                    placeholder="<?= $escape((string)$mentionOption['default_label']) ?>"
                                                    data-omo-decision-mj-popup-mention-label="<?= $escape((string)$mentionScore) ?>"
                                                    data-default-label="<?= $escape((string)$mentionOption['default_label']) ?>"
                                                    maxlength="90"
                                                    <?= $canEditStructure ? '' : 'disabled' ?>
                                                >
                                            </label>
                                            <label class="omo-decision-majority-judgment-popup__field omo-decision-majority-judgment-popup__field--toggle">
                                                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.scale_active', [], $lang, $sourceLang)) ?></span>
                                                <span class="omo-decision-majority-judgment__toggle">
                                                    <input type="checkbox" data-omo-decision-mj-popup-mention-active="<?= $escape((string)$mentionScore) ?>" <?= !empty($mentionOption['active']) ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                                    <span><?= $escape(t('decisions.majority_judgment.field.scale_active', [], $lang, $sourceLang)) ?></span>
                                                </span>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.field.scale_center_hint', [], $lang, $sourceLang)) ?></p>
                                </div>
                                <label class="omo-decision-majority-judgment-popup__field">
                                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.anonymous', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-decision-majority-judgment__toggle">
                                        <input type="checkbox" data-omo-decision-mj-popup-anonymous <?= $isAnonymous ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                        <span><?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                </label>
                                <label class="omo-decision-majority-judgment-popup__field">
                                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-decision-majority-judgment__toggle">
                                        <input type="checkbox" data-omo-decision-mj-popup-consultation-proposals <?= $allowConsultationProposals ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                        <span><?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                </label>
                                <div class="omo-decision-majority-judgment-popup__actions">
                                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-mj-popup-cancel><?= $escape(t('decisions.majority_judgment.action.close', [], $lang, $sourceLang)) ?></button>
                                    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-mj-popup-apply <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape(t('decisions.majority_judgment.action.apply', [], $lang, $sourceLang)) ?></button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.proposals', [], $lang, $sourceLang)) ?></span>
                        <div class="omo-decision-majority-judgment__proposal-list" data-omo-decision-mj-proposal-list>
                            <?php foreach ($proposalItems as $proposalIndex => $proposalItem): ?>
                            <div class="omo-decision-majority-judgment__proposal-card<?= $canEditProposals ? '' : ' omo-decision-majority-judgment__proposal-card--locked' ?>" data-omo-decision-mj-proposal-card draggable="<?= $canEditProposals ? 'true' : 'false' ?>">
                                <?php if ($canEditProposals): ?>
                                <button type="button" class="omo-decision-majority-judgment__proposal-drag" data-omo-decision-mj-proposal-drag title="<?= $escape(t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang)) ?>" aria-label="<?= $escape(t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang)) ?>">&#8942;&#8942;</button>
                                <?php endif; ?>
                                <div class="omo-decision-majority-judgment__proposal-main">
                                    <span class="omo-decision-majority-judgment__proposal-label" data-omo-decision-mj-proposal-label><?= $escape(str_replace('{index}', (string)($proposalIndex + 1), t('decisions.majority_judgment.field.proposals_item', ['index' => (string)($proposalIndex + 1)], $lang, $sourceLang))) ?></span>
                                    <input type="text" name="proposals[]" class="generic-form-control" value="<?= $escape((string)$proposalItem['title']) ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.proposals', [], $lang, $sourceLang)) ?>" <?= $canEditProposals ? '' : 'disabled' ?>>
                                    <input type="hidden" name="proposal_descriptions[]" value="<?= $escape((string)($proposalItem['description'] ?? '')) ?>" data-omo-decision-mj-proposal-description>
                                    <input type="hidden" name="proposal_info_urls[]" value="<?= $escape((string)($proposalItem['info_url'] ?? '')) ?>" data-omo-decision-mj-proposal-info-url>
                                </div>
                                <div class="omo-decision-majority-judgment__proposal-menu" data-omo-decision-mj-proposal-menu>
                                    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-toggle" data-omo-decision-mj-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Actions">...</button>
                                    <div class="omo-decision-majority-judgment__proposal-menu-panel" data-omo-decision-mj-proposal-menu-panel role="menu" hidden>
                                        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-settings role="menuitem"><?= $escape(t('decisions.majority_judgment.field.proposal_details', [], $lang, $sourceLang)) ?></button>
                                        <?php if ($canEditProposals): ?>
                                        <button type="button" class="generic-action-button generic-action-button--danger omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-remove role="menuitem"><?= $escape(t('decisions.majority_judgment.field.proposals_remove', [], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-add" data-omo-decision-mj-proposal-add <?= $canEditProposals ? '' : 'disabled' ?>><?= $escape(t('decisions.majority_judgment.field.proposals_add', [], $lang, $sourceLang)) ?></button>
                        <small class="omo-decision-majority-judgment__hint"><?= $escape(t('decisions.majority_judgment.field.proposals_hint', [], $lang, $sourceLang)) ?></small>
                    </label>

                    <?= omoDecisionRenderInvitationSection($decision, $context, $lang, $sourceLang, $escape, 'omo-decision-majority-judgment__invitation-summary') ?>

                    <?php if ($isEditable): ?>
                    <div class="omo-decision-majority-judgment__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-mj-submit><?= $escape($decision instanceof DecisionProcess ? t('decisions.majority_judgment.action.save', [], $lang, $sourceLang) : t('decisions.majority_judgment.action.create', [], $lang, $sourceLang)) ?></button>
                        <div class="omo-decision-majority-judgment__feedback" data-omo-decision-mj-feedback aria-live="polite"></div>
                    </div>
                    <?php endif; ?>

                    <script type="application/json" data-omo-decision-mj-data><?= $managePayloadJson ?></script>
                </form>
            <?php else: ?>
                <?php if (!$publicLayout): ?>
                <div class="omo-decision-majority-judgment__summary-grid">
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.status', [], $lang, $sourceLang), t('decisions.majority_judgment.option.status.' . $status, [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.type', [], $lang, $sourceLang), $decisionType === DecisionProcess::TYPE_CONSULTATION ? t('decisions.majority_judgment.option.type.consultation', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.type.decision', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang), $scaleSummary, $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.anonymous', [], $lang, $sourceLang), $isAnonymous ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang), $allowConsultationProposals ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.consultation_start', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_start_at')) : '', $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.evaluation_end', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_end_at')) : '', $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php if ($resultsMode): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.total_votes', [], $lang, $sourceLang), (string)$submittedVoteCount, $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$publicLayout && $decision instanceof DecisionProcess && trim((string)$decision->get('description')) !== ''): ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.description', [], $lang, $sourceLang)) ?></span>
                    <p class="omo-decision-majority-judgment__text"><?= nl2br($escape(trim((string)$decision->get('description')))) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($isParticipateMode): ?>
                <form class="omo-decision-majority-judgment__form" action="/omo/api/decision/modules/majority_judgment/respond.php" method="post" data-omo-decision-mj-response-form>
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                    <input type="hidden" name="method" value="<?= $escape(DecisionProcess::METHOD_MAJORITY_JUDGMENT) ?>">
                    <input type="hidden" name="intent" value="participate">
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>


                        <?php if (count($proposalObjects) === 0): ?>
                        <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.empty_proposals', [], $lang, $sourceLang)) ?></p>
                        <?php else: ?>
                        <div class="omo-decision-majority-judgment__rating-list">
                            <?php foreach ($proposalObjects as $proposal): ?>
                            <?php $proposalId = (int)$proposal->getId(); ?>
                            <div class="generic-soft-panel generic-soft-panel--stack omo-decision-majority-judgment__rating-card">
                                <div class="omo-decision-majority-judgment__rating-head">
                                    <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                    <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-majority-judgment__text', 'omo-decision-majority-judgment__link') ?>
                                </div>
                                <div class="omo-decision-majority-judgment__rating-scale" role="radiogroup" aria-label="<?= $escape(t('decisions.majority_judgment.field.your_scores', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($mentions as $score => $mentionLabel): ?>
                                    <div class="omo-decision-majority-judgment__rating-option omo-decision-majority-judgment__rating-option--<?= $escape((string)$score) ?><?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? ' is-selected' : '' ?>">
                                        <input class="omo-decision-majority-judgment__rating-input" type="radio" name="scores[<?= $escape($proposalId) ?>]" value="<?= $escape($score) ?>" <?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? 'checked' : '' ?> required>
                                        <label
                                            class="omo-decision-majority-judgment__rating-chip"
                                            data-omo-decision-mj-rating-trigger
                                            title="<?= $escape($mentionLabel) ?>"
                                            aria-label="<?= $escape($mentionLabel) ?>"
                                            aria-checked="<?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? 'true' : 'false' ?>"
                                            role="radio"
                                            tabindex="0"
                                        >
                                            <span class="omo-decision-majority-judgment__rating-chip-check" aria-hidden="true">L</span>
                                            <span class="omo-decision-majority-judgment__sr-only"><?= $escape($mentionLabel) ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                    <?php if ($consultationProposalPanel !== ''): ?>
                    <?= $consultationProposalPanel ?>
                    <?php endif; ?>

                    <div class="omo-decision-majority-judgment__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-mj-response-submit><?= $escape($selectedResponse instanceof DecisionResponse ? t('decisions.majority_judgment.action.update_response', [], $lang, $sourceLang) : t('decisions.majority_judgment.action.submit_response', [], $lang, $sourceLang)) ?></button>
                        <div class="omo-decision-majority-judgment__feedback" data-omo-decision-mj-response-feedback aria-live="polite"></div>
                    </div>

                    <script type="application/json" data-omo-decision-mj-response-data><?= $responsePayloadJson ?></script>
                </form>
                <?php else: ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.proposals', [], $lang, $sourceLang)) ?></span>
                    <?php if (count($proposalObjects) === 0): ?>
                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.empty_proposals', [], $lang, $sourceLang)) ?></p>
                    <?php elseif ($resultsMode && $submittedVoteCount === 0): ?>
                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.empty_results', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>

                    <div class="omo-decision-majority-judgment__results-panel" data-omo-decision-mj-results-panel>
                        <?php if ($showResultsSortSwitch): ?>
                        <div class="omo-decision-majority-judgment__results-controls omo-panel-controls">
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(t('decisions.majority_judgment.results_sort.aria', [], $lang, $sourceLang)) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-decision-mj-results-sort="rank" aria-pressed="true"><?= $escape(t('decisions.majority_judgment.results_sort.rank', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decision-mj-results-sort="initial" aria-pressed="false"><?= $escape(t('decisions.majority_judgment.results_sort.initial', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decision-mj-results-sort="alpha" aria-pressed="false"><?= $escape(t('decisions.majority_judgment.results_sort.alpha', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="omo-decision-majority-judgment__result-list" data-omo-decision-mj-results-list>
                        <?php foreach ($resultProposalObjects as $resultRankIndex => $proposal): ?>
                        <?php
                        $proposalId = (int)$proposal->getId();
                        $proposalPosition = (int)($proposalOriginalOrder[$proposalId] ?? ($resultRankIndex + 1));
                        $stat = $proposalStats[$proposalId] ?? ['distribution' => omoDecisionMajorityJudgmentGetEmptyDistribution(), 'count' => 0, 'counted_count' => 0, 'no_opinion_count' => 0, 'majority_score' => null, 'majority_label' => ''];
                        $countedMentions = (int)($stat['counted_count'] ?? 0);
                        $noOpinionCount = (int)($stat['no_opinion_count'] ?? 0);
                        ?>
                        <div
                            class="omo-decision-majority-judgment__result-card<?= array_key_exists($proposalId, $selectedScores) ? ' is-selected' : '' ?>"
                            data-omo-decision-mj-result-item
                            data-omo-decision-mj-result-rank="<?= $escape((string)($resultRankIndex + 1)) ?>"
                            data-omo-decision-mj-result-position="<?= $escape((string)$proposalPosition) ?>"
                            data-omo-decision-mj-result-title="<?= $escape(trim((string)$proposal->get('title'))) ?>"
                        >
                            <div class="omo-decision-majority-judgment__result-head">
                                <div>
                                    <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                    <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-majority-judgment__text', 'omo-decision-majority-judgment__link') ?>
                                </div>
                                <?php if ($resultsMode && $countedMentions > 0): ?>
                                <span class="omo-decision-majority-judgment__majority-badge omo-decision-majority-judgment__majority-badge--<?= $escape((string)$stat['majority_score']) ?>">
                                    <?= $escape(t('decisions.majority_judgment.field.majority_mention', [], $lang, $sourceLang)) ?>: <?= $escape((string)$stat['majority_label']) ?>
                                </span>
                                <?php elseif (array_key_exists($proposalId, $selectedScores)): ?>
                                <span class="omo-decision-majority-judgment__majority-badge omo-decision-majority-judgment__majority-badge--<?= $escape((string)$selectedScores[$proposalId]) ?>">
                                    <?= $escape((string)($allMentions[(int)$selectedScores[$proposalId]] ?? '')) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($resultsMode): ?>
                            <div class="omo-decision-majority-judgment__result-meta">
                                <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape(t('decisions.majority_judgment.field.counted_mentions', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)$countedMentions) ?></strong>
                            </div>
                            <?php if (!empty($config['has_no_opinion'])): ?>
                            <div class="omo-decision-majority-judgment__result-meta">
                                <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape($noOpinionLabel !== '' ? $noOpinionLabel : t('decisions.majority_judgment.field.no_opinion_count', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)$noOpinionCount) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if ($countedMentions > 0): ?>
                            <div class="omo-decision-majority-judgment__distribution" aria-label="<?= $escape(t('decisions.majority_judgment.field.distribution', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($mentions as $score => $mentionLabel): ?>
                                <?php
                                if ((int)$score === omoDecisionMajorityJudgmentGetNoOpinionScore()) {
                                    continue;
                                }
                                $segmentCount = (int)($stat['distribution'][$score] ?? 0);
                                $segmentPercent = $countedMentions > 0 ? ($segmentCount / $countedMentions) * 100 : 0;
                                $segmentPercentLabel = number_format($segmentPercent, 1, ',', ' ');
                                $segmentWidth = $segmentPercent > 0 ? number_format($segmentPercent, 4, '.', '') : '0';
                                $segmentTooltip = t(
                                    'decisions.majority_judgment.tooltip.segment',
                                    [
                                        'mention' => (string)$mentionLabel,
                                        'count' => (string)$segmentCount,
                                        'percent' => $segmentPercentLabel,
                                    ],
                                    $lang,
                                    $sourceLang
                                );
                                ?>
                                <span
                                    class="omo-decision-majority-judgment__distribution-segment omo-decision-majority-judgment__distribution-segment--<?= $escape((string)$score) ?>"
                                    style="width: <?= $escape($segmentWidth) ?>%;"
                                    title="<?= $escape($segmentTooltip) ?>"
                                    aria-label="<?= $escape($segmentTooltip) ?>"
                                ></span>
                                <?php endforeach; ?>
                                <span
                                    class="omo-decision-majority-judgment__distribution-marker"
                                    aria-hidden="true"
                                ></span>
                            </div>
                            <div class="omo-decision-majority-judgment__distribution-scale" aria-hidden="true">
                                <span><?= $escape((string)$mentions[0]) ?></span>
                                <span><?= $escape((string)$mentions[6]) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        </div>
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
            window.omoDecisionMajorityJudgmentInit = function (root) {
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

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-majority-judgment-form]'), function (form) {
                    if (form.dataset.omoDecisionMjReady === '1') {
                        return;
                    }

                        const payloadNode = form.querySelector('[data-omo-decision-mj-data]');
                        const submitButton = form.querySelector('[data-omo-decision-mj-submit]');
                        const feedbackNode = form.querySelector('[data-omo-decision-mj-feedback]');
                        const proposalList = form.querySelector('[data-omo-decision-mj-proposal-list]');
                        const proposalAddButton = form.querySelector('[data-omo-decision-mj-proposal-add]');
                        const settingsOpenButton = form.querySelector('[data-omo-decision-mj-settings-open]');
                        const invitationOpenButton = form.querySelector('[data-omo-decision-invitations-open]');
                        const invitationSendOpenButton = form.querySelector('[data-omo-decision-invitations-send-open]');
                        const settingsTemplate = form.querySelector('[data-omo-decision-mj-settings-template]');
                        const hiddenAnonymousInput = form.querySelector('[data-omo-decision-mj-hidden-anonymous]');
                        const hiddenConsultationInput = form.querySelector('[data-omo-decision-mj-hidden-consultation-proposals]');
                        const scaleSummaryNode = form.querySelector('[data-omo-decision-mj-scale-summary]');
                        const anonymousSummary = form.querySelector('[data-omo-decision-mj-anonymous-summary]');
                        const consultationSummary = form.querySelector('[data-omo-decision-mj-consultation-summary]');
                        const hiddenMentionLabelInputs = {};
                        const hiddenMentionActiveInputs = {};

                        if (!payloadNode || !proposalList) {
                            return;
                        }

                        if (typeof window.omoDecisionInitInvitationEditors === 'function') {
                            window.omoDecisionInitInvitationEditors(form);
                        }

                        Array.prototype.forEach.call(form.querySelectorAll('[data-omo-decision-mj-hidden-mention-label]'), function (input) {
                            const score = String(input.getAttribute('data-omo-decision-mj-hidden-mention-label') || '').trim();
                            if (score !== '') {
                                hiddenMentionLabelInputs[score] = input;
                            }
                        });
                        Array.prototype.forEach.call(form.querySelectorAll('[data-omo-decision-mj-hidden-mention-active]'), function (input) {
                            const score = String(input.getAttribute('data-omo-decision-mj-hidden-mention-active') || '').trim();
                            if (score !== '') {
                                hiddenMentionActiveInputs[score] = input;
                            }
                        });

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

                        const mentionScores = Object.keys(hiddenMentionLabelInputs).sort(function (left, right) {
                            return Number(left) - Number(right);
                        });

                        const normalizeMentionLabel = function (input, rawValue) {
                            const defaultLabel = input
                                ? String(input.getAttribute('data-default-label') || '').trim()
                                : '';
                            const nextValue = String(rawValue || '').trim();
                            return nextValue !== '' ? nextValue : defaultLabel;
                        };

                        const buildScaleSummary = function () {
                            const labels = [];
                            mentionScores.forEach(function (score) {
                                const labelInput = hiddenMentionLabelInputs[score];
                                const activeInput = hiddenMentionActiveInputs[score];
                                if (!labelInput || !activeInput || !activeInput.value) {
                                    return;
                                }
                                labels.push(normalizeMentionLabel(labelInput, labelInput.value));
                            });

                            if (labels.length === 0) {
                                return String(payload.texts && payload.texts.scaleSummaryEmpty ? payload.texts.scaleSummaryEmpty : 'Aucune mention active');
                            }

                            return labels.join(' / ');
                        };

                        const syncSettingsSummary = function () {
                            const yesLabel = String(payload.texts && payload.texts.yesLabel ? payload.texts.yesLabel : 'Oui');
                            const noLabel = String(payload.texts && payload.texts.noLabel ? payload.texts.noLabel : 'Non');
                            if (scaleSummaryNode) {
                                scaleSummaryNode.textContent = buildScaleSummary();
                            }
                            if (anonymousSummary) {
                                anonymousSummary.textContent = hiddenAnonymousInput && hiddenAnonymousInput.value ? yesLabel : noLabel;
                            }
                            if (consultationSummary) {
                                consultationSummary.textContent = hiddenConsultationInput && hiddenConsultationInput.value ? yesLabel : noLabel;
                            }
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

                        const openSettingsModal = function () {
                            if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                                return;
                            }

                            const modalTitle = settingsOpenButton
                                ? String(settingsOpenButton.getAttribute('data-omo-decision-mj-settings-title') || settingsOpenButton.textContent || 'Parametres du scrutin')
                                : 'Parametres du scrutin';
                            window.commonTopbarOpenModal(modalTitle, settingsTemplate.innerHTML, 'html');
                            const modalBody = document.getElementById('commonTopbarModalBody');
                            if (!modalBody) {
                                return;
                            }

                            const popupAnonymous = modalBody.querySelector('[data-omo-decision-mj-popup-anonymous]');
                            const popupConsultation = modalBody.querySelector('[data-omo-decision-mj-popup-consultation-proposals]');
                            const popupCancel = modalBody.querySelector('[data-omo-decision-mj-popup-cancel]');
                            const popupApply = modalBody.querySelector('[data-omo-decision-mj-popup-apply]');
                            const popupMentionLabelInputs = {};
                            const popupMentionActiveInputs = {};

                            Array.prototype.forEach.call(modalBody.querySelectorAll('[data-omo-decision-mj-popup-mention-label]'), function (input) {
                                const score = String(input.getAttribute('data-omo-decision-mj-popup-mention-label') || '').trim();
                                if (score !== '') {
                                    popupMentionLabelInputs[score] = input;
                                }
                            });
                            Array.prototype.forEach.call(modalBody.querySelectorAll('[data-omo-decision-mj-popup-mention-active]'), function (input) {
                                const score = String(input.getAttribute('data-omo-decision-mj-popup-mention-active') || '').trim();
                                if (score !== '') {
                                    popupMentionActiveInputs[score] = input;
                                }
                            });

                            if (!popupAnonymous || !popupConsultation || !popupApply) {
                                return;
                            }

                            popupAnonymous.checked = !!(hiddenAnonymousInput && hiddenAnonymousInput.value);
                            popupConsultation.checked = !!(hiddenConsultationInput && hiddenConsultationInput.value);
                            mentionScores.forEach(function (score) {
                                const hiddenLabelInput = hiddenMentionLabelInputs[score];
                                const hiddenActiveInput = hiddenMentionActiveInputs[score];
                                const popupLabelInput = popupMentionLabelInputs[score];
                                const popupActiveInput = popupMentionActiveInputs[score];

                                if (popupLabelInput && hiddenLabelInput) {
                                    popupLabelInput.value = normalizeMentionLabel(hiddenLabelInput, hiddenLabelInput.value);
                                }
                                if (popupActiveInput && hiddenActiveInput) {
                                    popupActiveInput.checked = !!hiddenActiveInput.value;
                                }
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
                                if (hiddenConsultationInput) {
                                    hiddenConsultationInput.value = popupConsultation.checked ? '1' : '';
                                }
                                mentionScores.forEach(function (score) {
                                    const hiddenLabelInput = hiddenMentionLabelInputs[score];
                                    const hiddenActiveInput = hiddenMentionActiveInputs[score];
                                    const popupLabelInput = popupMentionLabelInputs[score];
                                    const popupActiveInput = popupMentionActiveInputs[score];

                                    if (hiddenLabelInput && popupLabelInput) {
                                        hiddenLabelInput.value = normalizeMentionLabel(popupLabelInput, popupLabelInput.value);
                                        popupLabelInput.value = hiddenLabelInput.value;
                                    }
                                    if (hiddenActiveInput && popupActiveInput) {
                                        hiddenActiveInput.value = popupActiveInput.checked ? '1' : '';
                                    }
                                });
                                syncSettingsSummary();
                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                        };

                        const refreshProposalLabels = function () {
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]'),
                                function (card, index) {
                                    const label = card.querySelector('[data-omo-decision-mj-proposal-label]');
                                    if (label) {
                                        const template = payload.texts && payload.texts.proposalItemTemplate
                                            ? payload.texts.proposalItemTemplate
                                            : 'Proposition __INDEX__';
                                        label.textContent = String(template).replace('__INDEX__', String(index + 1));
                                    }
                                }
                            );
                        };

                        const closeProposalMenus = function (exceptCard) {
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]'),
                                function (menuCard) {
                                    if (exceptCard && menuCard === exceptCard) {
                                        return;
                                    }

                                    const menuPanel = menuCard.querySelector('[data-omo-decision-mj-proposal-menu-panel]');
                                    const menuToggle = menuCard.querySelector('[data-omo-decision-mj-proposal-menu-toggle]');
                                    if (menuPanel) {
                                        menuPanel.hidden = true;
                                    }
                                    if (menuToggle) {
                                        menuToggle.setAttribute('aria-expanded', 'false');
                                    }
                                }
                            );
                        };

                        let sortable = null;
                        const bindProposalCard = function (card) {
                            if (!card || card.dataset.omoDecisionMjProposalReady === '1') {
                                return;
                            }

                            const removeButton = card.querySelector('[data-omo-decision-mj-proposal-remove]');
                            const detailsButton = card.querySelector('[data-omo-decision-mj-proposal-settings]');
                            const menuToggle = card.querySelector('[data-omo-decision-mj-proposal-menu-toggle]');
                            const menuPanel = card.querySelector('[data-omo-decision-mj-proposal-menu-panel]');
                            const titleInput = card.querySelector('input[name="proposals[]"]');
                            const descriptionInput = card.querySelector('[data-omo-decision-mj-proposal-description]');
                            const infoUrlInput = card.querySelector('[data-omo-decision-mj-proposal-info-url]');

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

                                    const proposalLabelNode = card.querySelector('[data-omo-decision-mj-proposal-label]');
                                    const modalTitle = proposalLabelNode
                                        ? String(proposalLabelNode.textContent || '').trim()
                                        : String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');
                                    const modalHtml = ''
                                        + '<div class="generic-section generic-section--stack" style="display:grid;gap:12px;">'
                                        + '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-card-title generic-card-title--small">' + String(payload.texts && payload.texts.proposalDescriptionLabel ? payload.texts.proposalDescriptionLabel : 'Description') + '</span>'
                                        + '    <textarea class="generic-form-control" rows="6" data-omo-decision-mj-proposal-modal-description></textarea>'
                                        + '  </label>'
                                        + '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-card-title generic-card-title--small">' + String(payload.texts && payload.texts.proposalInfoUrlLabel ? payload.texts.proposalInfoUrlLabel : 'URL') + '</span>'
                                        + '    <input type="url" class="generic-form-control" data-omo-decision-mj-proposal-modal-info-url placeholder="' + String(payload.texts && payload.texts.proposalInfoUrlPlaceholder ? payload.texts.proposalInfoUrlPlaceholder : 'https://...') + '">'
                                        + '  </label>'
                                        + '  <div style="display:flex;justify-content:flex-end;gap:8px;">'
                                        + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-mj-proposal-modal-cancel>Fermer</button>'
                                        + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-mj-proposal-modal-apply>' + String(payload.texts && payload.texts.proposalApply ? payload.texts.proposalApply : 'Enregistrer') + '</button>'
                                        + '  </div>'
                                        + '</div>';

                                    window.commonTopbarOpenModal(modalTitle || 'Details', modalHtml, 'html');
                                    const modalBody = document.getElementById('commonTopbarModalBody');
                                    if (!modalBody) {
                                        return;
                                    }

                                    const modalDescription = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-description]');
                                    const modalInfoUrl = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-info-url]');
                                    const modalCancel = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-cancel]');
                                    const modalApply = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-apply]');
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

                            if (removeButton && !removeButton.disabled) {
                                removeButton.addEventListener('click', function () {
                                    closeProposalMenus();
                                    card.parentNode.removeChild(card);
                                    if (proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]').length < 2) {
                                        proposalList.appendChild(createProposalCard(''));
                                    }
                                    refreshProposalLabels();
                                    setFeedback('', false);
                                });
                            }

                            card.dataset.omoDecisionMjProposalReady = '1';
                        };

                        const createProposalCard = function (value) {
                            const card = document.createElement('div');
                            card.className = 'omo-decision-majority-judgment__proposal-card';
                            card.setAttribute('data-omo-decision-mj-proposal-card', '1');
                            card.setAttribute('draggable', 'true');
                            card.innerHTML = ''
                                + '<button type="button" class="omo-decision-majority-judgment__proposal-drag" data-omo-decision-mj-proposal-drag title="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Reordonner') + '" aria-label="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Reordonner') + '">&#8942;&#8942;</button>'
                                + '<div class="omo-decision-majority-judgment__proposal-main">'
                                + '    <span class="omo-decision-majority-judgment__proposal-label" data-omo-decision-mj-proposal-label></span>'
                                + '    <input type="text" name="proposals[]" class="generic-form-control" placeholder="' + String(payload.texts && payload.texts.proposalPlaceholder ? payload.texts.proposalPlaceholder : 'Nom de la proposition') + '">'
                                + '    <input type="hidden" name="proposal_descriptions[]" value="" data-omo-decision-mj-proposal-description>'
                                + '    <input type="hidden" name="proposal_info_urls[]" value="" data-omo-decision-mj-proposal-info-url>'
                                + '</div>'
                                + '<div class="omo-decision-majority-judgment__proposal-menu" data-omo-decision-mj-proposal-menu>'
                                + '    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-toggle" data-omo-decision-mj-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Actions">...</button>'
                                + '    <div class="omo-decision-majority-judgment__proposal-menu-panel" data-omo-decision-mj-proposal-menu-panel role="menu" hidden>'
                                + '        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-settings role="menuitem">' + String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details') + '</button>'
                                + '        <button type="button" class="generic-action-button generic-action-button--danger omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-remove role="menuitem">' + String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer') + '</button>'
                                + '    </div>'
                                + '</div>';

                            const input = card.querySelector('input[name="proposals[]"]');
                            if (input) {
                                input.value = String(value || '');
                            }

                            bindProposalCard(card);
                            if (sortable && typeof sortable.bindItem === 'function') {
                                sortable.bindItem(card);
                            }
                            refreshProposalLabels();
                            return card;
                        };

                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]'), bindProposalCard);
                        refreshProposalLabels();
                        syncSettingsSummary();

                        document.addEventListener('click', function (event) {
                            if (!proposalList.contains(event.target)) {
                                closeProposalMenus();
                            }
                        });

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
                                itemSelector: '[data-omo-decision-mj-proposal-card]',
                                handleSelector: '[data-omo-decision-mj-proposal-drag]',
                                draggingClass: 'is-dragging',
                                dropTargetClass: 'is-drop-target',
                                placeholderClass: 'omo-decision-majority-judgment__proposal-placeholder',
                                createPlaceholder: function (card) {
                                    const placeholder = document.createElement('div');
                                    placeholder.className = 'omo-decision-majority-judgment__proposal-placeholder';
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
                                const input = newCard.querySelector('input[name="proposals[]"]');
                                if (input) {
                                    input.focus();
                                }
                            });
                        }

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

                    form.dataset.omoDecisionMjReady = '1';
                });

                    Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-mj-response-form]'), function (form) {
                        if (form.dataset.omoDecisionMjResponseReady === '1') {
                            return;
                        }

                        const payloadNode = form.querySelector('[data-omo-decision-mj-response-data]');
                        const submitButton = form.querySelector('[data-omo-decision-mj-response-submit]');
                        const feedbackNode = form.querySelector('[data-omo-decision-mj-response-feedback]');
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

                        const syncRatingState = function () {
                            Array.prototype.forEach.call(form.querySelectorAll('.omo-decision-majority-judgment__rating-option'), function (ratingOption) {
                                const radio = ratingOption.querySelector('input[type="radio"]');
                                const trigger = ratingOption.querySelector('[data-omo-decision-mj-rating-trigger]');
                                const isSelected = !!(radio && radio.checked);
                                ratingOption.classList.toggle('is-selected', isSelected);
                                if (trigger) {
                                    trigger.setAttribute('aria-checked', isSelected ? 'true' : 'false');
                                }
                            });
                        };

                        form.addEventListener('click', function (event) {
                            const trigger = event.target.closest('[data-omo-decision-mj-rating-trigger]');
                            if (!trigger || !form.contains(trigger)) {
                                return;
                            }

                            event.preventDefault();
                            event.stopPropagation();

                            const ratingOption = trigger.closest('.omo-decision-majority-judgment__rating-option');
                            const radio = ratingOption.querySelector('input[type="radio"]');
                            if (!radio || radio.disabled) {
                                return;
                            }

                            if (!radio.checked) {
                                radio.checked = true;
                                radio.dispatchEvent(new Event('change', { bubbles: true }));
                            } else {
                                syncRatingState();
                            }
                        });

                        form.addEventListener('change', function (event) {
                            if (!event.target.matches('.omo-decision-majority-judgment__rating-input')) {
                                return;
                            }

                            syncRatingState();
                        });

                        form.addEventListener('keydown', function (event) {
                            const trigger = event.target.closest('[data-omo-decision-mj-rating-trigger]');
                            if (!trigger || !form.contains(trigger)) {
                                return;
                            }

                            if (event.key !== ' ' && event.key !== 'Enter') {
                                return;
                            }

                            event.preventDefault();
                            trigger.click();
                        });

                        syncRatingState();

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

                    form.dataset.omoDecisionMjResponseReady = '1';
                });

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-mj-results-panel]'), function (panel) {
                    if (panel.dataset.omoDecisionMjResultsReady === '1') {
                        return;
                    }

                    const list = panel.querySelector('[data-omo-decision-mj-results-list]');
                    const buttons = panel.querySelectorAll('[data-omo-decision-mj-results-sort]');
                    if (!list || !buttons.length) {
                        panel.dataset.omoDecisionMjResultsReady = '1';
                        return;
                    }

                    const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
                        ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
                        : null;

                    const compareText = function (left, right) {
                        const normalizedLeft = String(left || '');
                        const normalizedRight = String(right || '');

                        if (collator) {
                            return collator.compare(normalizedLeft, normalizedRight);
                        }

                        return normalizedLeft.localeCompare(normalizedRight);
                    };

                    const normalizeSortMode = function (value) {
                        const normalizedValue = String(value || '').trim().toLowerCase();
                        if (normalizedValue === 'initial' || normalizedValue === 'alpha') {
                            return normalizedValue;
                        }

                        return 'rank';
                    };

                    const sortItems = function (sortMode) {
                        const items = Array.prototype.slice.call(list.querySelectorAll('[data-omo-decision-mj-result-item]'));
                        if (items.length < 2) {
                            return;
                        }

                        items.sort(function (left, right) {
                            const leftRank = Number(left.getAttribute('data-omo-decision-mj-result-rank') || 0);
                            const rightRank = Number(right.getAttribute('data-omo-decision-mj-result-rank') || 0);
                            const leftPosition = Number(left.getAttribute('data-omo-decision-mj-result-position') || 0);
                            const rightPosition = Number(right.getAttribute('data-omo-decision-mj-result-position') || 0);
                            const leftTitle = String(left.getAttribute('data-omo-decision-mj-result-title') || '');
                            const rightTitle = String(right.getAttribute('data-omo-decision-mj-result-title') || '');

                            if (sortMode === 'initial') {
                                return leftPosition - rightPosition;
                            }

                            if (sortMode === 'alpha') {
                                const titleDiff = compareText(leftTitle, rightTitle);
                                if (titleDiff !== 0) {
                                    return titleDiff;
                                }

                                return leftPosition - rightPosition;
                            }

                            return leftRank - rightRank;
                        });

                        items.forEach(function (item) {
                            list.appendChild(item);
                        });
                    };

                    const applySortMode = function (sortMode) {
                        const normalizedSortMode = normalizeSortMode(sortMode);
                        buttons.forEach(function (button) {
                            const isActive = normalizeSortMode(button.getAttribute('data-omo-decision-mj-results-sort')) === normalizedSortMode;
                            button.classList.toggle('is-active', isActive);
                            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                        });

                        sortItems(normalizedSortMode);
                    };

                    buttons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            applySortMode(button.getAttribute('data-omo-decision-mj-results-sort'));
                        });
                    });

                    applySortMode('rank');
                    panel.dataset.omoDecisionMjResultsReady = '1';
                });
            };

            window.omoDecisionMajorityJudgmentInit(document.currentScript ? document.currentScript.parentElement : document);
        })();
        </script>

        <style>
        .omo-decision-majority-judgment {
            display: grid;
            gap: 16px;
            touch-action: pan-y pinch-zoom;
        }

        .omo-decision-majority-judgment__head,
        .omo-decision-majority-judgment__copy,
        .omo-decision-majority-judgment__field,
        .omo-decision-majority-judgment__settings-head,
        .omo-decision-majority-judgment__proposal-main,
        .omo-decision-majority-judgment__footer,
        .omo-decision-majority-judgment__result-head,
        .omo-decision-majority-judgment__rating-head {
            display: grid;
            gap: 8px;
        }

        .omo-decision-majority-judgment__grid,
        .omo-decision-majority-judgment__summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .omo-decision-majority-judgment__text {
            margin: 0;
            color: var(--color-text-light, #475569);
            line-height: 1.6;
        }

        .omo-decision-majority-judgment__textarea {
            min-height: 110px;
        }

        .omo-decision-majority-judgment__readonly-stats {
            display: grid;
            gap: 8px;
        }

        .omo-decision-majority-judgment__readonly-stat {
            display: grid;
            gap: 4px;
            padding: 10px 12px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, white);
        }

        .omo-decision-majority-judgment__proposal-list,
        .omo-decision-majority-judgment__rating-list,
        .omo-decision-majority-judgment__result-list {
            display: grid;
            gap: 12px;
            touch-action: pan-y pinch-zoom;
        }

        .omo-decision-majority-judgment__results-panel {
            display: grid;
            gap: 12px;
        }

        .omo-decision-majority-judgment__results-controls {
            justify-content: flex-end;
        }

        .omo-decision-majority-judgment__proposal-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 14%, white);
            background: white;
        }

        .omo-decision-majority-judgment__proposal-card--locked {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .omo-decision-majority-judgment__proposal-drag {
            border: 0;
            background: transparent;
            color: var(--color-text-light, #64748b);
            cursor: grab;
            font-size: 18px;
            padding: 4px 6px;
        }

        .omo-decision-majority-judgment__proposal-label {
            font-size: 13px;
            color: var(--color-text-light, #64748b);
        }

        .omo-decision-majority-judgment__proposal-menu {
            position: relative;
            align-self: start;
        }

        .omo-decision-majority-judgment__proposal-menu-toggle {
            min-width: 42px;
            padding-inline: 12px;
        }

        .omo-decision-majority-judgment__proposal-menu-panel {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 180px;
            display: grid;
            gap: 6px;
            padding: 8px;
            border: 1px solid var(--color-border, #d1d5db);
            border-radius: 12px;
            background: var(--color-surface, #ffffff);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.14);
            z-index: 5;
        }

        .omo-decision-majority-judgment__proposal-menu-panel[hidden] {
            display: none;
        }

        .omo-decision-majority-judgment__proposal-menu-item {
            width: 100%;
            justify-content: flex-start;
            box-shadow: none;
        }

        .omo-decision-majority-judgment__proposal-placeholder {
            border: 1px dashed color-mix(in srgb, var(--color-primary, #2563eb) 35%, white);
            border-radius: 14px;
            background: color-mix(in srgb, var(--color-primary, #2563eb) 8%, white);
        }

        .omo-decision-majority-judgment__toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .omo-decision-majority-judgment-popup__grid,
        .omo-decision-majority-judgment-popup__field,
        .omo-decision-majority-judgment-popup__actions {
            display: grid;
            gap: 12px;
        }

        .omo-decision-majority-judgment-popup__mention-list {
            display: grid;
            gap: 12px;
        }

        .omo-decision-majority-judgment-popup__mention-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .omo-decision-majority-judgment-popup__field--toggle {
            min-width: 120px;
        }

        .omo-decision-majority-judgment__feedback {
            min-height: 20px;
            color: var(--color-text-light, #475569);
        }

        .omo-decision-majority-judgment__feedback.is-error {
            color: var(--color-danger, #b42318);
        }

        .omo-decision-majority-judgment__feedback.is-success {
            color: var(--color-success, #027a48);
        }

        .omo-decision-majority-judgment__rating-scale {
            display: flex;
            align-items: stretch;
            width: 100%;
            overflow: hidden;
            touch-action: pan-y pinch-zoom;
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 20%, var(--color-surface, #ffffff));
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, var(--color-surface, #ffffff));
            box-shadow: inset 0 1px 0 color-mix(in srgb, var(--color-surface, #ffffff) 78%, transparent);
        }

        .omo-decision-majority-judgment__rating-option {
            display: block;
            position: relative;
            flex: 1 1 0;
        }

        .omo-decision-majority-judgment__rating-option--0 { --omo-decision-mj-score: var(--color-palette-red, #c62828); }
        .omo-decision-majority-judgment__rating-option--1 { --omo-decision-mj-score: var(--color-palette-orange, #ef6c00); }
        .omo-decision-majority-judgment__rating-option--2 { --omo-decision-mj-score: var(--color-palette-yellow, #f9a825); }
        .omo-decision-majority-judgment__rating-option--3 { --omo-decision-mj-score: var(--color-palette-gray, #9ca3af); }
        .omo-decision-majority-judgment__rating-option--4 { --omo-decision-mj-score: var(--color-palette-green-light, #9ccc65); }
        .omo-decision-majority-judgment__rating-option--5 { --omo-decision-mj-score: var(--color-palette-green-medium, #43a047); }
        .omo-decision-majority-judgment__rating-option--6 { --omo-decision-mj-score: var(--color-palette-green-dark, #1b5e20); }

        .omo-decision-majority-judgment__rating-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .omo-decision-majority-judgment__rating-chip {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: var(--omo-decision-mj-score, var(--color-primary, #2563eb));
            cursor: pointer;
            font: inherit;
            color: var(--color-surface, #ffffff);
            transition: filter 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            position: relative;
            touch-action: pan-y pinch-zoom;
        }

        .omo-decision-majority-judgment__rating-chip::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background:
                radial-gradient(circle at 50% 32%, rgba(255, 255, 255, 0.22), transparent 52%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0) 38%, rgba(0, 0, 0, 0.12) 100%);
            pointer-events: none;
        }

        .omo-decision-majority-judgment__rating-option + .omo-decision-majority-judgment__rating-option .omo-decision-majority-judgment__rating-chip {
            box-shadow: inset 1px 0 0 color-mix(in srgb, var(--color-surface, #ffffff) 30%, transparent);
        }

        .omo-decision-majority-judgment__rating-chip:hover,
        .omo-decision-majority-judgment__rating-chip:focus-visible {
            filter: brightness(1.05);
            outline: none;
        }

        .omo-decision-majority-judgment__rating-chip-check {
            font-size: 30px;
            font-weight: 900;
            line-height: 1;
            opacity: 0;
            transform: scaleX(-1) rotate(-45deg) scale(0.7);
            transform-origin: center;
            transition: opacity 0.15s ease, transform 0.15s ease;
            text-shadow:
                0 1px 2px rgba(0, 0, 0, 0.25),
                0 0 8px rgba(255, 255, 255, 0.18);
            position: relative;
            z-index: 1;
            color: #ffffff;
        }

        .omo-decision-majority-judgment__rating-option.is-selected .omo-decision-majority-judgment__rating-chip {
            filter: brightness(0.92);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.18),
                inset 0 0 10px rgba(255, 255, 255, 0.12),
                inset 0 0 22px rgba(0, 0, 0, 0.10),
                inset 0 -12px 20px rgba(0, 0, 0, 0.14);
        }

        .omo-decision-majority-judgment__rating-option.is-selected .omo-decision-majority-judgment__rating-chip-check {
            opacity: 1;
            transform: scaleX(-1) rotate(-45deg) scale(1);
        }

        .omo-decision-majority-judgment__sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .omo-decision-majority-judgment__result-card,
        .omo-decision-majority-judgment__rating-card {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 14%, white);
            background: var(--color-surface, #ffffff);
            touch-action: pan-y pinch-zoom;
        }

        .omo-decision-majority-judgment__result-card.is-selected {
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, var(--color-surface, #ffffff));
            background: color-mix(in srgb, var(--color-primary, #2563eb) 7%, var(--color-surface, #ffffff));
        }

        .omo-decision-majority-judgment__result-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: baseline;
            color: var(--color-text-light, #475569);
            font-size: 14px;
        }

        .omo-decision-majority-judgment__result-meta-label {
            color: var(--color-text-light, #475569);
        }

        .omo-decision-majority-judgment__majority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, white);
            color: var(--color-text, #0f172a);
            font-size: 13px;
        }

        .omo-decision-majority-judgment__majority-badge--0 { background: color-mix(in srgb, var(--color-palette-red, #c62828) 18%, var(--color-surface, #ffffff)); }
        .omo-decision-majority-judgment__majority-badge--1 { background: color-mix(in srgb, var(--color-palette-orange, #ef6c00) 18%, var(--color-surface, #ffffff)); }
        .omo-decision-majority-judgment__majority-badge--2 { background: color-mix(in srgb, var(--color-palette-yellow, #f9a825) 20%, var(--color-surface, #ffffff)); }
        .omo-decision-majority-judgment__majority-badge--3 { background: color-mix(in srgb, var(--color-palette-gray, #9ca3af) 22%, var(--color-surface, #ffffff)); }
        .omo-decision-majority-judgment__majority-badge--4 { background: color-mix(in srgb, var(--color-palette-green-light, #9ccc65) 20%, var(--color-surface, #ffffff)); }
        .omo-decision-majority-judgment__majority-badge--5 { background: color-mix(in srgb, var(--color-palette-green-medium, #43a047) 18%, var(--color-surface, #ffffff)); }
        .omo-decision-majority-judgment__majority-badge--6 { background: color-mix(in srgb, var(--color-palette-green-dark, #1b5e20) 20%, var(--color-surface, #ffffff)); color: var(--color-text, #0f172a); }

        .omo-decision-majority-judgment__distribution {
            --omo-decision-mj-distribution-padding: 4px;
            display: flex;
            position: relative;
            gap: 2px;
            align-items: stretch;
            min-height: 18px;
            padding: var(--omo-decision-mj-distribution-padding);
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, white);
            overflow: hidden;
        }

        .omo-decision-majority-judgment__distribution-segment {
            display: block;
            min-width: 0;
            min-height: 10px;
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .omo-decision-majority-judgment__distribution-segment:hover {
            transform: scaleY(1.08);
            filter: brightness(0.96);
        }

        .omo-decision-majority-judgment__distribution-marker {
            position: absolute;
            top: 50%;
            left: calc(
                var(--omo-decision-mj-distribution-padding)
                + ((100% - (var(--omo-decision-mj-distribution-padding) * 2)) * 0.5)
            );
            width: 16px;
            height: 16px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.96);
            background: rgba(255, 255, 255, 0.08);
            box-shadow:
                0 0 0 1px rgba(15, 23, 42, 0.28),
                0 4px 10px rgba(15, 23, 42, 0.18);
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 2;
        }

        .omo-decision-majority-judgment__distribution-segment--0 {
            background: var(--color-palette-red, #c62828);
        }

        .omo-decision-majority-judgment__distribution-segment--1 {
            background: var(--color-palette-orange, #ef6c00);
        }

        .omo-decision-majority-judgment__distribution-segment--2 {
            background: var(--color-palette-yellow, #f9a825);
        }

        .omo-decision-majority-judgment__distribution-segment--3 {
            background: var(--color-palette-gray, #9ca3af);
        }

        .omo-decision-majority-judgment__distribution-segment--4 {
            background: var(--color-palette-green-light, #9ccc65);
        }

        .omo-decision-majority-judgment__distribution-segment--5 {
            background: var(--color-palette-green-medium, #43a047);
        }

        .omo-decision-majority-judgment__distribution-segment--6 {
            background: var(--color-palette-green-dark, #1b5e20);
        }

        .omo-decision-majority-judgment__distribution-scale {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--color-text-light, #475569);
            font-size: 12px;
        }

        @media (max-width: 680px) {
            .omo-decision-majority-judgment__proposal-card {
                grid-template-columns: 1fr;
            }

            .omo-decision-majority-judgment__rating-chip {
                min-height: 42px;
            }

            .omo-decision-majority-judgment-popup__mention-row {
                grid-template-columns: 1fr;
            }

            .omo-decision-majority-judgment-popup__field--toggle {
                min-width: 0;
            }
        }
        </style>
        <?php
    }
}
