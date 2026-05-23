<?php

use dbObject\DecisionProcess;
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
            'decisions.majority_judgment.field.title' => ['text' => 'Titre du scrutin', 'context' => 'Label for the title field.'],
            'decisions.majority_judgment.field.description' => ['text' => 'Description', 'context' => 'Label for the description field.'],
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
            'decisions.majority_judgment.field.settings' => ['text' => 'Parametres du scrutin', 'context' => 'Section title for judgment-specific settings.'],
            'decisions.majority_judgment.field.scale' => ['text' => 'Echelle de mentions', 'context' => 'Label for the scale summary.'],
            'decisions.majority_judgment.field.scale_summary' => ['text' => '7 mentions de "A rejeter" a "Parfaitement"', 'context' => 'Summary label for the fixed majority judgment scale.'],
            'decisions.majority_judgment.field.anonymous' => ['text' => 'Vote anonyme', 'context' => 'Label for the anonymity setting.'],
            'decisions.majority_judgment.field.allow_consultation_proposals' => ['text' => 'Autoriser les propositions pendant la consultation', 'context' => 'Label for allowing proposals during consultation.'],
            'decisions.majority_judgment.field.your_scores' => ['text' => 'Vos mentions', 'context' => 'Legend for the participant scoring fieldset.'],
            'decisions.majority_judgment.field.current_response' => ['text' => 'Vote enregistre', 'context' => 'Label shown when a previous response exists.'],
            'decisions.majority_judgment.field.total_votes' => ['text' => 'Votes enregistres', 'context' => 'Label for the total number of submitted votes.'],
            'decisions.majority_judgment.field.majority_mention' => ['text' => 'Mention majoritaire', 'context' => 'Label for the majority mention of one proposal.'],
            'decisions.majority_judgment.field.proposal_votes' => ['text' => 'Mentions recues', 'context' => 'Label for the number of received mentions.'],
            'decisions.majority_judgment.field.distribution' => ['text' => 'Repartition des mentions', 'context' => 'Label for the graphical distribution of mentions.'],
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
            'decisions.majority_judgment.placeholder.title' => ['text' => 'Ex. Prioriser les prochains chantiers', 'context' => 'Placeholder for the title field.'],
            'decisions.majority_judgment.placeholder.description' => ['text' => 'Contexte, consignes, informations utiles...', 'context' => 'Placeholder for the description field.'],
            'decisions.majority_judgment.placeholder.proposals' => ['text' => 'Nom de la proposition', 'context' => 'Placeholder for one proposal input.'],
            'decisions.majority_judgment.action.create' => ['text' => 'Creer le scrutin', 'context' => 'Submit label for a new majority judgment.'],
            'decisions.majority_judgment.action.save' => ['text' => 'Enregistrer le scrutin', 'context' => 'Submit label for an existing majority judgment.'],
            'decisions.majority_judgment.action.saving' => ['text' => 'Enregistrement...', 'context' => 'Temporary label while saving.'],
            'decisions.majority_judgment.action.configure' => ['text' => 'Configurer', 'context' => 'Button label to open the settings modal.'],
            'decisions.majority_judgment.action.close' => ['text' => 'Fermer', 'context' => 'Button label to close the settings modal.'],
            'decisions.majority_judgment.action.apply' => ['text' => 'Appliquer', 'context' => 'Button label to apply the settings modal changes.'],
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
        $lang = $renderContext['lang'];
        $sourceLang = $renderContext['sourceLang'];
        $escape = $renderContext['escape'];
        $intent = (string)($context['intent'] ?? 'manage');

        $isManageMode = $intent === 'manage';
        $isParticipateMode = $intent === 'participate';
        $isViewMode = !$isManageMode && !$isParticipateMode;

        $decisionType = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeDecisionType($decision->get('decision_type'))
            : DecisionProcess::TYPE_DECISION;
        $status = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeStatus($decision->get('status'))
            : DecisionProcess::STATUS_DRAFT;

        $proposalObjects = [];
        $proposalLines = [];
        if ($decision instanceof DecisionProcess) {
            foreach ($decision->getProposals(true) as $proposal) {
                $proposalObjects[] = $proposal;
                $proposalLines[] = trim((string)$proposal->get('title'));
            }
        }

        if (count($proposalLines) === 0) {
            $proposalLines = ['', ''];
        } elseif (count($proposalLines) === 1) {
            $proposalLines[] = '';
        }

        $config = $decision instanceof DecisionProcess
            ? omoDecisionMajorityJudgmentBuildConfig($decision)
            : omoDecisionMajorityJudgmentBuildConfig([]);
        $isAnonymous = !empty($config['is_anonymous']);
        $allowConsultationProposals = !empty($config['allow_consultation_proposals']);
        $mentions = $config['mentions'];

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

        $participant = $context['participant'] ?? null;
        $selectedResponse = null;
        $selectedScores = [];
        if ($decision instanceof DecisionProcess && $participant && (int)$participant->getId() > 0) {
            $selectedResponse = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId());
            $selectedScores = omoDecisionMajorityJudgmentExtractScores($selectedResponse);
        }

        $submittedResponses = [];
        $submittedVoteCount = 0;
        if ($decision instanceof DecisionProcess) {
            foreach ($decision->getResponses(DecisionResponse::STATUS_SUBMITTED) as $submittedResponse) {
                $submittedResponses[] = $submittedResponse;
                $submittedVoteCount++;
            }
        }
        $proposalStats = omoDecisionMajorityJudgmentBuildStats($proposalObjects, $submittedResponses);

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
            'redirectUrl' => omoDecisionBuildEditorUrl(
                (int)$context['organizationId'],
                (int)$context['targetHolonId'],
                $decision instanceof DecisionProcess ? (int)$decision->getId() : 0,
                DecisionProcess::METHOD_MAJORITY_JUDGMENT,
                'manage'
            ),
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
                'proposalRemove' => t('decisions.majority_judgment.field.proposals_remove', [], $lang, $sourceLang),
                'proposalReorder' => t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang),
                'proposalItemTemplate' => t('decisions.majority_judgment.field.proposals_item', ['index' => '__INDEX__'], $lang, $sourceLang),
                'yesLabel' => t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang),
                'noLabel' => t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang),
            ],
        ];

        $responsePayload = [
            'saveUrl' => '/omo/api/decision/modules/majority_judgment/respond.php',
            'redirectUrl' => omoDecisionBuildEditorUrl(
                (int)$context['organizationId'],
                (int)$context['targetHolonId'],
                $decision instanceof DecisionProcess ? (int)$decision->getId() : 0,
                DecisionProcess::METHOD_MAJORITY_JUDGMENT,
                'participate'
            ),
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
        ?>
        <section class="generic-section generic-section--stack omo-decision-majority-judgment">
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

            <?php if ($resultsMode): ?>
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
                    <input type="hidden" name="intent" value="manage">
                    <input type="hidden" name="evaluation_method" value="<?= $escape(DecisionProcess::METHOD_MAJORITY_JUDGMENT) ?>">

                    <div class="omo-decision-majority-judgment__grid">
                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.title', [], $lang, $sourceLang)) ?></span>
                            <input type="text" name="title" class="generic-form-control" required maxlength="190" value="<?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>>
                        </label>

                        <label class="omo-decision-majority-judgment__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.type', [], $lang, $sourceLang)) ?></span>
                            <select name="decision_type" class="generic-form-control" <?= $canEditStructure ? '' : 'disabled' ?>>
                                <option value="<?= $escape(DecisionProcess::TYPE_DECISION) ?>"<?= $decisionType === DecisionProcess::TYPE_DECISION ? ' selected' : '' ?>><?= $escape(t('decisions.majority_judgment.option.type.decision', [], $lang, $sourceLang)) ?></option>
                                <option value="<?= $escape(DecisionProcess::TYPE_CONSULTATION) ?>"<?= $decisionType === DecisionProcess::TYPE_CONSULTATION ? ' selected' : '' ?>><?= $escape(t('decisions.majority_judgment.option.type.consultation', [], $lang, $sourceLang)) ?></option>
                            </select>
                        </label>

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

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.description', [], $lang, $sourceLang)) ?></span>
                        <textarea name="description" class="generic-form-control omo-decision-majority-judgment__textarea" rows="4" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('description')) : '') ?></textarea>
                    </label>

                    <div class="generic-soft-panel generic-soft-panel--stack omo-decision-majority-judgment__settings-summary">
                        <input type="hidden" name="is_anonymous" value="<?= $isAnonymous ? '1' : '' ?>" data-omo-decision-mj-hidden-anonymous>
                        <input type="hidden" name="allow_consultation_proposals" value="<?= $allowConsultationProposals ? '1' : '' ?>" data-omo-decision-mj-hidden-consultation-proposals>
                        <div class="omo-decision-majority-judgment__settings-head">
                            <div class="omo-decision-majority-judgment__field">
                                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.settings', [], $lang, $sourceLang)) ?></span>
                                <div class="omo-decision-majority-judgment__readonly-stats">
                                    <span class="omo-decision-majority-judgment__readonly-stat">
                                        <strong><?= $escape(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang)) ?></strong>
                                        <span><?= $escape(t('decisions.majority_judgment.field.scale_summary', [], $lang, $sourceLang)) ?></span>
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
                                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.field.scale_summary', [], $lang, $sourceLang)) ?></p>
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

                    <?= omoDecisionRenderInvitationSection($decision, $context, $lang, $sourceLang, $escape, 'omo-decision-majority-judgment__invitation-summary') ?>

                    <label class="omo-decision-majority-judgment__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.proposals', [], $lang, $sourceLang)) ?></span>
                        <div class="omo-decision-majority-judgment__proposal-list" data-omo-decision-mj-proposal-list>
                            <?php foreach ($proposalLines as $proposalIndex => $proposalLine): ?>
                            <div class="omo-decision-majority-judgment__proposal-card" data-omo-decision-mj-proposal-card draggable="<?= $canEditProposals ? 'true' : 'false' ?>">
                                <button type="button" class="omo-decision-majority-judgment__proposal-drag" data-omo-decision-mj-proposal-drag title="<?= $escape(t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang)) ?>" aria-label="<?= $escape(t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang)) ?>" <?= $canEditProposals ? '' : 'disabled' ?>>&#8942;&#8942;</button>
                                <div class="omo-decision-majority-judgment__proposal-main">
                                    <span class="omo-decision-majority-judgment__proposal-label" data-omo-decision-mj-proposal-label><?= $escape(str_replace('{index}', (string)($proposalIndex + 1), t('decisions.majority_judgment.field.proposals_item', ['index' => (string)($proposalIndex + 1)], $lang, $sourceLang))) ?></span>
                                    <input type="text" name="proposals[]" class="generic-form-control" value="<?= $escape($proposalLine) ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.proposals', [], $lang, $sourceLang)) ?>" <?= $canEditProposals ? '' : 'disabled' ?>>
                                </div>
                                <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-remove" data-omo-decision-mj-proposal-remove <?= $canEditProposals ? '' : 'disabled' ?>><?= $escape(t('decisions.majority_judgment.field.proposals_remove', [], $lang, $sourceLang)) ?></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-add" data-omo-decision-mj-proposal-add <?= $canEditProposals ? '' : 'disabled' ?>><?= $escape(t('decisions.majority_judgment.field.proposals_add', [], $lang, $sourceLang)) ?></button>
                        <small class="omo-decision-majority-judgment__hint"><?= $escape(t('decisions.majority_judgment.field.proposals_hint', [], $lang, $sourceLang)) ?></small>
                    </label>

                    <?php if ($isEditable): ?>
                    <div class="omo-decision-majority-judgment__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-mj-submit><?= $escape($decision instanceof DecisionProcess ? t('decisions.majority_judgment.action.save', [], $lang, $sourceLang) : t('decisions.majority_judgment.action.create', [], $lang, $sourceLang)) ?></button>
                        <div class="omo-decision-majority-judgment__feedback" data-omo-decision-mj-feedback aria-live="polite"></div>
                    </div>
                    <?php endif; ?>

                    <script type="application/json" data-omo-decision-mj-data><?= $managePayloadJson ?></script>
                </form>
            <?php else: ?>
                <div class="omo-decision-majority-judgment__summary-grid">
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.status', [], $lang, $sourceLang), t('decisions.majority_judgment.option.status.' . $status, [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.type', [], $lang, $sourceLang), $decisionType === DecisionProcess::TYPE_CONSULTATION ? t('decisions.majority_judgment.option.type.consultation', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.type.decision', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang), t('decisions.majority_judgment.field.scale_summary', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.anonymous', [], $lang, $sourceLang), $isAnonymous ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang), $allowConsultationProposals ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.consultation_start', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_start_at')) : '', $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.evaluation_end', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_end_at')) : '', $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php if ($resultsMode): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.total_votes', [], $lang, $sourceLang), (string)$submittedVoteCount, $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php endif; ?>
                </div>

                <?php if ($decision instanceof DecisionProcess && trim((string)$decision->get('description')) !== ''): ?>
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
                    <input type="hidden" name="intent" value="participate">

                    <fieldset class="omo-decision-majority-judgment__fieldset">
                        <legend class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.your_scores', [], $lang, $sourceLang)) ?></legend>
                        <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.field.select_all', [], $lang, $sourceLang)) ?></p>

                        <?php if (count($proposalObjects) === 0): ?>
                        <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.empty_proposals', [], $lang, $sourceLang)) ?></p>
                        <?php else: ?>
                        <div class="omo-decision-majority-judgment__rating-list">
                            <?php foreach ($proposalObjects as $proposal): ?>
                            <?php $proposalId = (int)$proposal->getId(); ?>
                            <div class="generic-soft-panel generic-soft-panel--stack omo-decision-majority-judgment__rating-card">
                                <div class="omo-decision-majority-judgment__rating-head">
                                    <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                </div>
                                <div class="omo-decision-majority-judgment__rating-scale">
                                    <?php foreach ($mentions as $score => $mentionLabel): ?>
                                    <div class="omo-decision-majority-judgment__rating-option<?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? ' is-selected' : '' ?>">
                                        <input class="omo-decision-majority-judgment__rating-input" type="radio" name="scores[<?= $escape($proposalId) ?>]" value="<?= $escape($score) ?>" <?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? 'checked' : '' ?> required>
                                        <button type="button" class="omo-decision-majority-judgment__rating-chip" data-omo-decision-mj-rating-trigger aria-pressed="<?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? 'true' : 'false' ?>">
                                            <strong><?= $escape((string)$score) ?></strong>
                                            <small><?= $escape($mentionLabel) ?></small>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </fieldset>

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

                    <div class="omo-decision-majority-judgment__result-list">
                        <?php foreach ($proposalObjects as $proposal): ?>
                        <?php
                        $proposalId = (int)$proposal->getId();
                        $stat = $proposalStats[$proposalId] ?? ['distribution' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0], 'count' => 0, 'majority_label' => ''];
                        ?>
                        <div class="omo-decision-majority-judgment__result-card<?= array_key_exists($proposalId, $selectedScores) ? ' is-selected' : '' ?>">
                            <div class="omo-decision-majority-judgment__result-head">
                                <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                <?php if ($resultsMode && $stat['count'] > 0): ?>
                                <span class="omo-decision-majority-judgment__majority-badge">
                                    <?= $escape(t('decisions.majority_judgment.field.majority_mention', [], $lang, $sourceLang)) ?>: <?= $escape((string)$stat['majority_label']) ?>
                                </span>
                                <?php elseif (array_key_exists($proposalId, $selectedScores)): ?>
                                <span class="omo-decision-majority-judgment__majority-badge">
                                    <?= $escape((string)$mentions[(int)$selectedScores[$proposalId]]) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($resultsMode): ?>
                            <div class="omo-decision-majority-judgment__result-meta">
                                <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape(t('decisions.majority_judgment.field.proposal_votes', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)$stat['count']) ?></strong>
                            </div>
                            <div class="omo-decision-majority-judgment__distribution" aria-label="<?= $escape(t('decisions.majority_judgment.field.distribution', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($mentions as $score => $mentionLabel): ?>
                                <?php
                                $segmentCount = (int)($stat['distribution'][$score] ?? 0);
                                $segmentPercent = $stat['count'] > 0 ? ($segmentCount / (int)$stat['count']) * 100 : 0;
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
                            </div>
                            <div class="omo-decision-majority-judgment__distribution-scale" aria-hidden="true">
                                <span><?= $escape((string)$mentions[0]) ?></span>
                                <span><?= $escape((string)$mentions[6]) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
                        const settingsTemplate = form.querySelector('[data-omo-decision-mj-settings-template]');
                        const hiddenAnonymousInput = form.querySelector('[data-omo-decision-mj-hidden-anonymous]');
                        const hiddenConsultationInput = form.querySelector('[data-omo-decision-mj-hidden-consultation-proposals]');
                        const anonymousSummary = form.querySelector('[data-omo-decision-mj-anonymous-summary]');
                        const consultationSummary = form.querySelector('[data-omo-decision-mj-consultation-summary]');

                        if (!payloadNode || !proposalList) {
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

                        const syncSettingsSummary = function () {
                            const yesLabel = String(payload.texts && payload.texts.yesLabel ? payload.texts.yesLabel : 'Oui');
                            const noLabel = String(payload.texts && payload.texts.noLabel ? payload.texts.noLabel : 'Non');
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

                            if (!popupAnonymous || !popupConsultation || !popupApply) {
                                return;
                            }

                            popupAnonymous.checked = !!(hiddenAnonymousInput && hiddenAnonymousInput.value);
                            popupConsultation.checked = !!(hiddenConsultationInput && hiddenConsultationInput.value);

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

                        let sortable = null;
                        const bindProposalCard = function (card) {
                            if (!card || card.dataset.omoDecisionMjProposalReady === '1') {
                                return;
                            }

                            const removeButton = card.querySelector('[data-omo-decision-mj-proposal-remove]');
                            if (removeButton && !removeButton.disabled) {
                                removeButton.addEventListener('click', function () {
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
                                + '</div>'
                                + '<button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-remove" data-omo-decision-mj-proposal-remove>' + String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer') + '</button>';

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

                        if (settingsOpenButton) {
                            settingsOpenButton.addEventListener('click', openSettingsModal);
                        }

                        if (invitationOpenButton) {
                            invitationOpenButton.addEventListener('click', openInvitationModal);
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
                                    trigger.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
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
            };

            window.omoDecisionMajorityJudgmentInit(document.currentScript ? document.currentScript.parentElement : document);
        })();
        </script>

        <style>
        .omo-decision-majority-judgment {
            display: grid;
            gap: 16px;
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
        }

        .omo-decision-majority-judgment__proposal-card {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 10px;
            align-items: center;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 14%, white);
            background: white;
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

        .omo-decision-majority-judgment__feedback {
            min-height: 20px;
            color: var(--color-text-light, #475569);
        }

        .omo-decision-majority-judgment__feedback.is-error {
            color: #b42318;
        }

        .omo-decision-majority-judgment__feedback.is-success {
            color: #027a48;
        }

        .omo-decision-majority-judgment__rating-scale {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(88px, 1fr));
            gap: 8px;
        }

        .omo-decision-majority-judgment__rating-option {
            display: block;
            position: relative;
        }

        .omo-decision-majority-judgment__rating-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .omo-decision-majority-judgment__rating-chip {
            display: grid;
            gap: 4px;
            width: 100%;
            min-height: 74px;
            padding: 10px;
            border-radius: 14px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 18%, white);
            background: white;
            text-align: left;
            cursor: pointer;
            font: inherit;
            color: inherit;
        }

        .omo-decision-majority-judgment__rating-chip strong {
            font-size: 16px;
        }

        .omo-decision-majority-judgment__rating-chip small {
            color: var(--color-text-light, #475569);
            line-height: 1.3;
        }

        .omo-decision-majority-judgment__rating-option.is-selected .omo-decision-majority-judgment__rating-chip {
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 40%, white);
            background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, white);
        }

        .omo-decision-majority-judgment__result-card,
        .omo-decision-majority-judgment__rating-card {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 14%, white);
            background: white;
        }

        .omo-decision-majority-judgment__result-card.is-selected {
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, white);
            background: color-mix(in srgb, var(--color-primary, #2563eb) 7%, white);
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
            color: var(--color-text-dark, #0f172a);
            font-size: 13px;
        }

        .omo-decision-majority-judgment__distribution {
            display: flex;
            gap: 2px;
            align-items: stretch;
            min-height: 18px;
            padding: 4px;
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

        .omo-decision-majority-judgment__distribution-segment--0 {
            background: #c62828;
        }

        .omo-decision-majority-judgment__distribution-segment--1 {
            background: #ef6c00;
        }

        .omo-decision-majority-judgment__distribution-segment--2 {
            background: #f9a825;
        }

        .omo-decision-majority-judgment__distribution-segment--3 {
            background: #9ca3af;
        }

        .omo-decision-majority-judgment__distribution-segment--4 {
            background: #9ccc65;
        }

        .omo-decision-majority-judgment__distribution-segment--5 {
            background: #43a047;
        }

        .omo-decision-majority-judgment__distribution-segment--6 {
            background: #1b5e20;
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

            .omo-decision-majority-judgment__rating-scale {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        </style>
        <?php
    }
}
