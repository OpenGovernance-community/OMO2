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
            'decisions.majority_judgment.description' => ['text' => 'Créez un scrutin où chaque participant attribue une mention à chaque proposition sur une échelle commune.', 'context' => 'Description of the majority judgment management screen.'],
            'decisions.majority_judgment.view_title' => ['text' => 'Voir le scrutin', 'context' => 'Title of the read-only majority judgment screen.'],
            'decisions.majority_judgment.view_description' => ['text' => 'Consultez le scrutin, ses réglages et ses propositions sans modifier sa configuration.', 'context' => 'Description of the read-only majority judgment screen.'],
            'decisions.majority_judgment.participate_title' => ['text' => 'Participer au scrutin', 'context' => 'Title of the majority judgment participation screen.'],
            'decisions.majority_judgment.participate_description' => ['text' => 'Attribuez une mention à chaque proposition sur l’échelle du jugement majoritaire.', 'context' => 'Description of the majority judgment participation screen.'],
            'decisions.majority_judgment.change_method' => ['text' => 'Changer de méthode', 'context' => 'Secondary action to go back to the method chooser.'],
            'decisions.majority_judgment.notice.started' => ['text' => 'Le scrutin a commencé. Le titre, la description, les questions et les paramètres sont désormais verrouillés.', 'context' => 'Notice shown when the configuration is locked after evaluation starts.'],
            'decisions.majority_judgment.notice.responses' => ['text' => 'Au moins une réponse a déjà été soumise. Seuls le statut et les dates de fin restent ajustables.', 'context' => 'Notice shown when some schedule fields are also locked.'],
            'decisions.majority_judgment.notice.consultation_proposals' => ['text' => 'Les propositions restent ajustables pendant la consultation tant qu’aucune réponse n’a été soumise.', 'context' => 'Notice shown when proposal editing remains allowed.'],
            'decisions.majority_judgment.notice.results' => ['text' => 'Ce scrutin est terminé. Seule la consultation des résultats reste disponible.', 'context' => 'Notice shown when the vote is in results or archived mode.'],
            'decisions.majority_judgment.field.title' => ['text' => 'Question', 'context' => 'Label for the group title field.'],
            'decisions.majority_judgment.field.description' => ['text' => 'Description de la question', 'context' => 'Label for the group description field.'],
            'decisions.majority_judgment.field.process_title' => ['text' => 'Titre du processus', 'context' => 'Label for the process title field.'],
            'decisions.majority_judgment.field.process_description' => ['text' => 'Description du contexte', 'context' => 'Label for the process description field.'],
            'decisions.majority_judgment.field.process_section' => ['text' => 'Contexte du processus', 'context' => 'Section title for process-level context fields.'],
            'decisions.majority_judgment.field.group_section' => ['text' => 'Question de ce groupe', 'context' => 'Section title for group-level question fields.'],
            'decisions.majority_judgment.field.type' => ['text' => 'Type de prise de décision', 'context' => 'Label for the decision type field.'],
            'decisions.majority_judgment.field.status' => ['text' => 'Statut', 'context' => 'Label for the status field.'],
            'decisions.majority_judgment.field.schedule' => ['text' => 'Planification', 'context' => 'Heading for the optional process schedule fields.'],
            'decisions.majority_judgment.field.schedule_help' => ['text' => 'Vous pouvez laisser ces réglages par défaut et planifier le scrutin plus tard.', 'context' => 'Help for the optional process schedule fields.'],
            'decisions.majority_judgment.field.consultation_start' => ['text' => 'Début de la consultation', 'context' => 'Label for the consultation start field.'],
            'decisions.majority_judgment.field.consultation_end' => ['text' => 'Fin de la consultation', 'context' => 'Label for the consultation end field.'],
            'decisions.majority_judgment.field.evaluation_start' => ['text' => 'Début du vote', 'context' => 'Label for the evaluation start field.'],
            'decisions.majority_judgment.field.evaluation_end' => ['text' => 'Clôture du vote', 'context' => 'Label for the evaluation end field.'],
            'decisions.majority_judgment.field.proposals' => ['text' => 'Propositions', 'context' => 'Label for the proposals list.'],
            'decisions.majority_judgment.field.proposals_hint' => ['text' => 'Ajoutez une proposition par ligne, puis réorganisez-les par glisser-déposer. Au moins deux propositions sont nécessaires, sauf si les propositions sont autorisées pendant une période de consultation.', 'context' => 'Hint under the proposals list.'],
            'decisions.majority_judgment.field.proposals_add' => ['text' => 'Ajouter une proposition', 'context' => 'Button label to append a new proposal field.'],
            'decisions.majority_judgment.field.proposals_remove' => ['text' => 'Supprimer', 'context' => 'Button label to remove a proposal field.'],
            'decisions.majority_judgment.field.proposals_reorder' => ['text' => 'Réordonner', 'context' => 'Aria label for the proposal drag handle.'],
            'decisions.majority_judgment.field.proposals_item' => ['text' => 'Proposition {index}', 'context' => 'Visible label prefix for one proposal row.'],
            'decisions.majority_judgment.field.proposal_details' => ['text' => 'Détails', 'context' => 'Button label opening the proposal detail popup.'],
            'decisions.majority_judgment.field.proposal_description' => ['text' => 'Description de la proposition', 'context' => 'Label for the proposal description field.'],
            'decisions.majority_judgment.field.proposal_info_url' => ['text' => 'URL d’information', 'context' => 'Label for the proposal info URL field.'],
            'decisions.majority_judgment.field.proposal_actions' => ['text' => 'Actions', 'context' => 'Accessibility label for the proposal actions menu.'],
            'decisions.majority_judgment.field.settings' => ['text' => 'Paramètres du scrutin', 'context' => 'Section title for judgment-specific settings.'],
            'decisions.majority_judgment.field.question_settings' => ['text' => 'Paramètres de la question', 'context' => 'Section title for settings specific to one question.'],
            'decisions.majority_judgment.field.scale' => ['text' => 'Échelle de mentions', 'context' => 'Label for the scale summary.'],
            'decisions.majority_judgment.field.scale_summary' => ['text' => 'Échelle configurable jusqu’à 7 mentions', 'context' => 'Summary label for the configurable majority judgment scale.'],
            'decisions.majority_judgment.field.scale_default_summary' => ['text' => 'Valeurs par défaut', 'context' => 'Summary shown in the settings recap when majority judgment mention customization is disabled.'],
            'decisions.majority_judgment.field.scale_slot' => ['text' => 'Mention {index}', 'context' => 'Label for one configurable slot of the majority judgment scale.'],
            'decisions.majority_judgment.field.scale_slot_prefix' => ['text' => 'Mention', 'context' => 'Visible prefix shown before the colored dot for one configurable majority judgment mention slot.'],
            'decisions.majority_judgment.field.scale_label' => ['text' => 'Libellé', 'context' => 'Label for one majority judgment mention label input.'],
            'decisions.majority_judgment.field.scale_active' => ['text' => 'Active', 'context' => 'Label for one majority judgment mention activation toggle.'],
            'decisions.majority_judgment.field.scale_empty' => ['text' => 'Aucune mention active', 'context' => 'Fallback text when no mention is active in the configured scale.'],
            'decisions.majority_judgment.field.scale_center_hint' => ['text' => 'La mention centrale reste hors calcul si elle est active.', 'context' => 'Hint explaining that the central mention stays excluded from the majority computation when enabled.'],
            'decisions.majority_judgment.field.scale_customize' => ['text' => 'Redéfinir les mentions', 'context' => 'Toggle label used to reveal mention customization controls in majority judgment settings.'],
            'decisions.majority_judgment.field.anonymous' => ['text' => 'Vote anonyme', 'context' => 'Label for the anonymity setting.'],
            'decisions.majority_judgment.field.named' => ['text' => 'Vote nominatif', 'context' => 'Label for enabling a named vote.'],
            'decisions.majority_judgment.field.allow_anonymous_votes' => ['text' => 'Permettre le vote anonyme individuel', 'context' => 'Label for allowing participants to choose anonymity for their own vote.'],
            'decisions.majority_judgment.field.allow_anonymous_votes_help' => ['text' => 'Les personnes qui souhaitent rester anonymes peuvent le choisir. Leur nom ne sera jamais affiché dans les résultats.', 'context' => 'Help for the individual anonymous vote option.'],
            'decisions.majority_judgment.field.allow_consultation_proposals' => ['text' => 'Autoriser les propositions pendant la consultation', 'context' => 'Label for allowing proposals during consultation.'],
            'decisions.majority_judgment.field.allow_proposal_discussions' => ['text' => 'Autoriser les discussions des propositions', 'context' => 'Label for allowing account users to discuss proposals.'],
            'decisions.majority_judgment.field.live_results' => ['text' => 'Afficher les résultats pendant le scrutin', 'context' => 'Label for showing intermediate majority judgment results.'],
            'decisions.majority_judgment.field.random_order' => ['text' => 'Ordre aléatoire des propositions', 'context' => 'Label for shuffling proposal order during voting.'],
            'decisions.majority_judgment.field.one_proposal_at_a_time' => ['text' => 'Une proposition à la fois', 'context' => 'Label for displaying one proposal at a time during voting.'],
            'decisions.majority_judgment.action.previous_proposal' => ['text' => 'Précédente', 'context' => 'Button to show the previous proposal in one-at-a-time voting.'],
            'decisions.majority_judgment.action.next_proposal' => ['text' => 'Suivante', 'context' => 'Button to show the next proposal in one-at-a-time voting.'],
            'decisions.majority_judgment.field.live_results_summary' => ['text' => 'Résultats en cours', 'context' => 'Summary label for intermediate majority judgment results setting.'],
            'decisions.majority_judgment.field.live_results_heading' => ['text' => 'Mentions en cours', 'context' => 'Heading for intermediate majority judgment results.'],
            'decisions.majority_judgment.option.live_results.named' => ['text' => 'Oui, nominatifs', 'context' => 'Summary for named intermediate majority judgment results.'],
            'decisions.majority_judgment.option.live_results.anonymous' => ['text' => 'Oui, anonymes', 'context' => 'Summary for anonymous intermediate majority judgment results.'],
            'decisions.majority_judgment.field.your_scores' => ['text' => 'Vos mentions', 'context' => 'Legend for the participant scoring fieldset.'],
            'decisions.majority_judgment.field.current_response' => ['text' => 'Vote enregistré', 'context' => 'Label shown when a previous response exists.'],
            'decisions.majority_judgment.field.total_votes' => ['text' => 'Votes enregistrés', 'context' => 'Label for the total number of submitted votes.'],
            'decisions.majority_judgment.field.majority_mention' => ['text' => 'Mention majoritaire', 'context' => 'Label for the majority mention of one proposal.'],
            'decisions.majority_judgment.field.proposal_votes' => ['text' => 'Mentions reçues', 'context' => 'Label for the number of received mentions.'],
            'decisions.majority_judgment.field.counted_mentions' => ['text' => 'Mentions prises en compte', 'context' => 'Label for the number of mentions counted in the majority judgment ranking.'],
            'decisions.majority_judgment.field.counted_weight' => ['text' => 'Poids cumulé pris en compte', 'context' => 'Label for the weighted sum of counted mentions when vote weighting is enabled.'],
            'decisions.majority_judgment.field.no_opinion_weight' => ['text' => 'Poids cumulé sans avis', 'context' => 'Label for the weighted sum of no-opinion answers when vote weighting is enabled.'],
            'decisions.majority_judgment.field.distribution' => ['text' => 'Répartition des mentions', 'context' => 'Label for the graphical distribution of mentions.'],
            'decisions.majority_judgment.results_compare.toggle' => ['text' => 'Afficher le résultat non pondéré', 'context' => 'Checkbox label used to reveal the unweighted result comparison.'],
            'decisions.majority_judgment.results_compare.unweighted' => ['text' => 'Résultat non pondéré', 'context' => 'Title shown above the unweighted majority judgment comparison block.'],
            'decisions.majority_judgment.results_sort.aria' => ['text' => 'Ordre d’affichage des résultats du jugement majoritaire', 'context' => 'Aria label for the majority judgment results sort switch.'],
            'decisions.majority_judgment.results_sort.rank' => ['text' => 'Classement', 'context' => 'Button label used to sort majority judgment results by majority ranking.'],
            'decisions.majority_judgment.results_sort.initial' => ['text' => 'Ordre initial', 'context' => 'Button label used to sort majority judgment results by saved proposal order.'],
            'decisions.majority_judgment.results_sort.alpha' => ['text' => 'Alphabétique', 'context' => 'Button label used to sort majority judgment results alphabetically.'],
            'decisions.majority_judgment.field.select_all' => ['text' => 'Attribuez une mention à chaque proposition.', 'context' => 'Help text for the participation form.'],
            'decisions.majority_judgment.option.type.decision' => ['text' => 'Décisionnaire', 'context' => 'Select option for a decision-oriented process.'],
            'decisions.majority_judgment.option.type.consultation' => ['text' => 'Indicative', 'context' => 'Select option for a consultation-oriented process.'],
            'decisions.majority_judgment.option.status.draft' => ['text' => 'En préparation', 'context' => 'Draft status option.'],
            'decisions.majority_judgment.option.status.scheduled' => ['text' => 'Planifiée', 'context' => 'Scheduled status option.'],
            'decisions.majority_judgment.option.status.consultation' => ['text' => 'En élaboration', 'context' => 'Elaboration status option.'],
            'decisions.majority_judgment.option.status.evaluation' => ['text' => 'En évaluation', 'context' => 'Evaluation status option.'],
            'decisions.majority_judgment.option.status.results' => ['text' => 'Résultats', 'context' => 'Results status option.'],
            'decisions.majority_judgment.option.status.archived' => ['text' => 'Archivée', 'context' => 'Archived status option.'],
            'decisions.majority_judgment.lifecycle.evaluation_start_confirmation' => ['text' => 'Le statut « En évaluation » n’est pas compatible avec la date de début d’évaluation définie au {date}. Voulez-vous vraiment commencer maintenant ? Les dates des phases d’élaboration et d’évaluation seront ajustées.', 'context' => 'Confirmation before manually starting a majority judgment before its scheduled date.'],
            'decisions.majority_judgment.lifecycle.consultation_start_confirmation' => ['text' => 'Le statut « En élaboration » n’est pas compatible avec la date de début d’élaboration définie au {date}. Voulez-vous vraiment commencer maintenant ? La date de début de la phase d’élaboration sera ajustée.', 'context' => 'Confirmation before manually starting a majority judgment consultation before its scheduled date.'],
            'decisions.majority_judgment.option.common.yes' => ['text' => 'Oui', 'context' => 'Generic yes option label.'],
            'decisions.majority_judgment.option.common.no' => ['text' => 'Non', 'context' => 'Generic no option label.'],
            'decisions.majority_judgment.placeholder.title' => ['text' => 'Ex. Quelle option préférez-vous ?', 'context' => 'Placeholder for the group title field.'],
            'decisions.majority_judgment.placeholder.description' => ['text' => 'Précisez la question, les nuances et les critères utiles…', 'context' => 'Placeholder for the group description field.'],
            'decisions.majority_judgment.placeholder.process_title' => ['text' => 'Ex. Organisation du repas de fin d’année', 'context' => 'Placeholder for the process title field.'],
            'decisions.majority_judgment.placeholder.process_description' => ['text' => 'Contexte global, informations communes, cadre de la consultation…', 'context' => 'Placeholder for the process description field.'],
            'decisions.majority_judgment.placeholder.proposals' => ['text' => 'Nom de la proposition', 'context' => 'Placeholder for one proposal input.'],
            'decisions.majority_judgment.placeholder.proposal_info_url' => ['text' => 'https://...', 'context' => 'Placeholder for one proposal info URL input.'],
            'decisions.majority_judgment.action.create' => ['text' => 'Créer le scrutin', 'context' => 'Submit label for a new majority judgment.'],
            'decisions.majority_judgment.action.save' => ['text' => 'Enregistrer le scrutin', 'context' => 'Submit label for an existing majority judgment.'],
            'decisions.majority_judgment.action.saving' => ['text' => 'Enregistrement…', 'context' => 'Temporary label while saving.'],
            'decisions.majority_judgment.action.configure' => ['text' => 'Configurer', 'context' => 'Button label to open the settings modal.'],
            'decisions.majority_judgment.action.close' => ['text' => 'Fermer', 'context' => 'Button label to close the settings modal.'],
            'decisions.majority_judgment.action.apply' => ['text' => 'Appliquer', 'context' => 'Button label to apply the settings modal changes.'],
            'decisions.majority_judgment.action.proposal_apply' => ['text' => 'Enregistrer les détails', 'context' => 'Button label used to save proposal detail popup fields.'],
            'decisions.majority_judgment.action.submit_response' => ['text' => 'Enregistrer mes mentions', 'context' => 'Submit label for a new response.'],
            'decisions.majority_judgment.action.update_response' => ['text' => 'Mettre à jour mes mentions', 'context' => 'Submit label when updating an existing response.'],
            'decisions.majority_judgment.action.submitting_response' => ['text' => 'Enregistrement du vote…', 'context' => 'Temporary label while saving a response.'],
            'decisions.majority_judgment.feedback.success' => ['text' => 'Scrutin enregistré.', 'context' => 'Generic success feedback after saving.'],
            'decisions.majority_judgment.feedback.error' => ['text' => 'Impossible d’enregistrer ce scrutin pour le moment.', 'context' => 'Generic error feedback after saving.'],
            'decisions.majority_judgment.feedback.response_success' => ['text' => 'Vote enregistré.', 'context' => 'Generic success feedback after saving a response.'],
            'decisions.majority_judgment.feedback.response_error' => ['text' => 'Impossible d’enregistrer votre vote pour le moment.', 'context' => 'Generic error feedback after saving a response.'],
            'decisions.majority_judgment.empty_proposals' => ['text' => 'Aucune proposition active pour le moment.', 'context' => 'Fallback text when no active proposal exists.'],
            'decisions.majority_judgment.empty_results' => ['text' => 'Aucune mention n’a encore été enregistrée pour ce scrutin.', 'context' => 'Fallback text when no submitted response exists yet.'],
            'decisions.majority_judgment.tooltip.segment' => ['text' => '{mention}: {count} mention(s) ({percent} %)', 'context' => 'Tooltip shown on one segment of the majority judgment result bar.'],
            'decisions.majority_judgment.drawer_title' => ['text' => 'Prises de décision', 'context' => 'Drawer title reused after saving.'],
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
        $forceNewGroup = !empty($renderContext['forceNewGroup']);
        $includeAssets = !array_key_exists('includeAssets', $renderContext) || !empty($renderContext['includeAssets']);
        $embeddedQuestion = !empty($renderContext['embeddedQuestion']);
        $duplicateDecision = ($context['duplicateDecision'] ?? null) instanceof DecisionProcess
            ? $context['duplicateDecision']
            : null;
        $isDuplicate = !($decision instanceof DecisionProcess)
            && $duplicateDecision instanceof DecisionProcess
            && !empty($context['isDuplicate']);
        $duplicateDecisionGroup = ($context['duplicateDecisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['duplicateDecisionGroup']
            : null;
        $decisionGroup = !$forceNewGroup && ($context['decisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['decisionGroup']
            : (!$forceNewGroup && $isDuplicate && $duplicateDecisionGroup instanceof DecisionGroup
                ? $duplicateDecisionGroup
                : (!$forceNewGroup && $decision instanceof DecisionProcess ? $decision->getPrimaryGroup(false) : null));
        $settingsDecision = $decision instanceof DecisionProcess ? $decision : $duplicateDecision;
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
                    'id' => (int)$proposal->getId(),
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
        if ($isDuplicate) {
            foreach ($proposalItems as &$proposalItem) {
                $proposalItem['id'] = 0;
            }
            unset($proposalItem);
        }

        $config = $decisionGroup instanceof DecisionGroup
            ? omoDecisionMajorityJudgmentBuildConfig($decisionGroup)
            : omoDecisionMajorityJudgmentBuildConfig([]);
        $isAnonymous = !empty($config['is_anonymous']);
        $allowAnonymousVotes = !empty($config['allow_anonymous_votes']);
        $allowConsultationProposals = !empty($config['allow_consultation_proposals']);
        $allowProposalDiscussions = !empty($config['allow_proposal_discussions']);
        $ownerIntermediateResultsAccess = $settingsDecision instanceof DecisionProcess
            && $settingsDecision->hasOwnerIntermediateResultsAccess();
        $participantIntermediateResultsAccess = $settingsDecision instanceof DecisionProcess
            && $settingsDecision->hasParticipantIntermediateResultsAccess();
        $participantResponsesEditable = !($settingsDecision instanceof DecisionProcess)
            || $settingsDecision->areParticipantResponsesEditable();
        $showLiveResults = $participantIntermediateResultsAccess;
        $randomizeProposalOrder = !empty($config['randomize_proposal_order']);
        $oneProposalAtATime = !empty($config['one_proposal_at_a_time']);
        $proposalContent = omoDecisionNormalizeProposalContent($config['proposal_content'] ?? null);
        $proposalContentSummary = omoDecisionBuildProposalContentSummary($proposalContent, $lang, $sourceLang);
        $proposalContentUrlEnabled = !empty($proposalContent['url']);
        $liveResultsAnonymous = $isAnonymous;
        $mentions = $config['mentions'];
        $allMentions = $config['all_mentions'];
        $mentionOptions = $config['mention_options'];
        $mentionCustomizationEnabled = !empty($config['mention_customization_enabled']);
        $scaleSummary = (string)($config['scale_summary'] ?? t('decisions.majority_judgment.field.scale_empty', [], $lang, $sourceLang));
        $settingsScaleSummary = $mentionCustomizationEnabled
            ? $scaleSummary
            : t('decisions.majority_judgment.field.scale_default_summary', [], $lang, $sourceLang);
        $noOpinionLabel = (string)($config['no_opinion_label'] ?? '');
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
        if ($isParticipateMode && $evaluationStarted && $randomizeProposalOrder) {
            $proposalObjects = omoDecisionShuffleProposalsForParticipant(
                $proposalObjects,
                $context,
                'majority_judgment:' . ($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0)
            );
        }
        $hasSubmittedResponses = $decision instanceof DecisionProcess ? $decision->hasSubmittedResponses() : false;
        $resultsMode = $decision instanceof DecisionProcess
            && in_array($status, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true);
        $isConsultationPhase = $decision instanceof DecisionProcess
            && $consultationStarted
            && !$evaluationStarted
            && !$resultsMode;
        $liveResultsMode = false;
        $showOwnerIntermediateResults = $isManageMode
            && !empty($context['isOwner'])
            && !$resultsMode
            && $evaluationStarted
            && $ownerIntermediateResultsAccess;
        $coreLocked = $decision instanceof DecisionProcess && $evaluationStarted;
        $startDatesLocked = $coreLocked || ($decision instanceof DecisionProcess && $hasSubmittedResponses);
        $isEditable = $isManageMode && !$resultsMode;
        $canEditStructure = $isEditable && !$coreLocked;
        $canEditProposals = $isEditable && !$coreLocked;
        $canEditStartDates = $isEditable && !$startDatesLocked;
        $canEnableNamedVote = !$isAnonymous
            || !($decision instanceof DecisionProcess)
            || $decision->canEnableNamedVote();
        $publicLayout = (($context['accessMode'] ?? '') === 'public') || !empty($context['previewLayout']);
        $visibilityState = function_exists('omoDecisionResolveVisibilityEditorState')
            ? omoDecisionResolveVisibilityEditorState($settingsDecision instanceof DecisionProcess ? $settingsDecision : null, $context)
            : array(
                'selectedVisibilityType' => DecisionProcess::getDefaultVisibilityType(),
                'visibilityOptions' => DecisionProcess::getVisibilityTypeOptions(),
                'disabledVisibilityTypes' => array(),
                'visibilityHelpText' => '',
            );

        $participant = $context['participant'] ?? null;
        $selectedResponse = null;
        $selectedResponseIsAnonymous = false;
        $selectedScores = [];
        $selectedVoteWeight = '1';
        if ($decision instanceof DecisionProcess && $participant && (int)$participant->getId() > 0) {
            $selectedResponse = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId(), $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0);
            $selectedScores = omoDecisionMajorityJudgmentExtractScores($selectedResponse);
            $selectedVoteWeightSelection = omoDecisionMajorityJudgmentExtractVoteWeightSelection($selectedResponse, $config);
            $selectedVoteWeight = (string)($selectedVoteWeightSelection['weight'] ?? '1');
            $selectedResponseIsAnonymous = omoDecisionResponseIsAnonymous($selectedResponse, omoDecisionMajorityJudgmentGetMethodKey());
        }
        $participantHasCompletedResponse = $selectedResponse instanceof DecisionResponse
            && DecisionResponse::normalizeStatus($selectedResponse->get('status')) === DecisionResponse::STATUS_SUBMITTED;
        $canEditSubmittedResponse = !$participantHasCompletedResponse || $participantResponsesEditable;
        $liveResultsMode = !$resultsMode
            && $isParticipateMode
            && $evaluationStarted
            && $showLiveResults
            && $participantHasCompletedResponse;
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
        $proposalStats = omoDecisionMajorityJudgmentBuildStats($proposalObjects, $submittedResponses, $config, $voteWeightEnabled);
        $proposalStatsUnweighted = $voteWeightEnabled
            ? omoDecisionMajorityJudgmentBuildStats($proposalObjects, $submittedResponses, $config, false)
            : $proposalStats;
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

        $managePayload = [
            'saveUrl' => '/omo/api/decision/modules/majority_judgment/save.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'manage'),
            'drawerTitle' => t('decisions.majority_judgment.drawer_title', [], $lang, $sourceLang),
            'proposalEditable' => $canEditProposals,
            'proposalContent' => $proposalContent,
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
                'proposalActions' => t('decisions.majority_judgment.field.proposal_actions', [], $lang, $sourceLang),
                'proposalDescriptionLabel' => t('decisions.majority_judgment.field.proposal_description', [], $lang, $sourceLang),
                'proposalInfoUrlLabel' => t('decisions.majority_judgment.field.proposal_info_url', [], $lang, $sourceLang),
                'proposalApply' => t('decisions.majority_judgment.action.proposal_apply', [], $lang, $sourceLang),
                'proposalItemTemplate' => t('decisions.majority_judgment.field.proposals_item', ['index' => '__INDEX__'], $lang, $sourceLang),
                'yesLabel' => t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang),
                'noLabel' => t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang),
                'scaleSummaryEmpty' => t('decisions.majority_judgment.field.scale_empty', [], $lang, $sourceLang),
                'scaleSummaryDefault' => t('decisions.majority_judgment.field.scale_default_summary', [], $lang, $sourceLang),
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
        <section class="omo-decision-majority-judgment<?= $isManageMode && !$embeddedQuestion ? '' : ' generic-section generic-section--stack' ?>">
            <?= omoDecisionRenderVoteWeightEditorAssets() ?>
            <?= omoDecisionRenderProposalDiscussionAssets() ?>
            <?= omoDecisionRenderOneProposalAtATimeAssets() ?>
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

                <?php $manageFormId = !$embeddedQuestion ? 'omoDecisionMajorityJudgmentManageForm' : ''; ?>
                <form
                    <?= $manageFormId !== '' ? 'id="' . $escape($manageFormId) . '"' : '' ?>
                    class="omo-decision-majority-judgment__form generic-form-stack generic-form-stack--compact"
                    action="/omo/api/decision/modules/majority_judgment/save.php"
                    method="post"
                    data-omo-decision-majority-judgment-form
                    data-omo-decision-embedded-question="<?= $embeddedQuestion ? '1' : '0' ?>"
                    <?php if ($manageFormId !== ''): ?>
                    data-omo-decision-editor-header-form
                    data-omo-decision-lifecycle-confirm-template="<?= $escape(t('decisions.majority_judgment.lifecycle.evaluation_start_confirmation', [], $lang, $sourceLang)) ?>"
                    data-omo-decision-lifecycle-consultation-confirm-template="<?= $escape(t('decisions.majority_judgment.lifecycle.consultation_start_confirmation', [], $lang, $sourceLang)) ?>"
                    data-omo-decision-editor-header-title="<?= $escape(t($decision instanceof DecisionProcess ? 'decisions.edit.edit_title' : 'decisions.edit.create_title', [], $lang, $sourceLang)) ?>"
                    data-omo-decision-editor-header-submit-label="<?= $isEditable ? $escape($decision instanceof DecisionProcess ? t('decisions.majority_judgment.action.save', [], $lang, $sourceLang) : t('decisions.majority_judgment.action.create', [], $lang, $sourceLang)) : '' ?>"
                    <?php endif; ?>
                >
                    <script src="/omo/api/decision/modules/lifecycle_status.js"></script>
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($isDuplicate ? 0 : ($decision instanceof DecisionProcess ? (int)$decision->getId() : 0)) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($isDuplicate ? 0 : ($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0)) ?>">
                    <input type="hidden" name="intent" value="manage">
                    <?php if ($forceNewGroup): ?><input type="hidden" name="group_action" value="create"><?php endif; ?>
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>
                    <input type="hidden" name="evaluation_method" value="<?= $escape(DecisionProcess::METHOD_MAJORITY_JUDGMENT) ?>">

                    <?php if (!$embeddedQuestion): ?>
                    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-decision-edit__process-settings">
                    <h3 class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.multi.process_title', [], $lang, $sourceLang)) ?></h3>

                    <label class="generic-form-field omo-decision-majority-judgment__field">
                        <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.process_title', [], $lang, $sourceLang)) ?></span>
                        <input type="text" name="process_title" class="generic-form-control generic-form-control--compact" required maxlength="190" value="<?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.process_title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>>
                    </label>

                    <label class="generic-form-field omo-decision-majority-judgment__field">
                        <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.process_description', [], $lang, $sourceLang)) ?></span>
                        <textarea name="process_description" class="generic-form-control generic-form-control--compact omo-decision-majority-judgment__textarea" rows="2" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.process_description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('description')) : '') ?></textarea>
                    </label>

                    <div class="generic-accordion generic-accordion--card generic-accordion--collapsible<?= $decision instanceof DecisionProcess && !$isDuplicate && (int)$decision->getId() > 0 ? '' : ' is-collapsed' ?>" data-generic-accordion>
                        <div class="generic-accordion__header">
                            <div class="generic-heading-with-help">
                                <h4 class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.majority_judgment.field.schedule', [], $lang, $sourceLang)) ?></h4>
                                <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover><summary aria-label="<?= $escape(t('decisions.majority_judgment.field.schedule_help', [], $lang, $sourceLang)) ?>">?</summary><div class="generic-context-help__content"><?= $escape(t('decisions.majority_judgment.field.schedule_help', [], $lang, $sourceLang)) ?></div></details>
                            </div>
                            <button type="button" class="generic-accordion__toggle" data-generic-accordion-toggle aria-expanded="<?= $decision instanceof DecisionProcess && !$isDuplicate && (int)$decision->getId() > 0 ? 'true' : 'false' ?>" aria-label="<?= $escape(t('decisions.majority_judgment.field.schedule', [], $lang, $sourceLang)) ?>">&#9662;</button>
                        </div>
                        <div class="generic-accordion__content generic-form-stack generic-form-stack--compact">
                    <div class="omo-decision-majority-judgment__grid omo-decision-schedule__primary generic-form-grid generic-form-grid--compact">
                    <div class="generic-form-field omo-decision-majority-judgment__field">
                        <div class="generic-heading-with-help">
                            <label class="generic-form-label" for="omo-decision-majority-judgment-visibility"><?= $escape(t('decisions.edit.visibility.label', [], $lang, $sourceLang)) ?></label>
                            <?php if (trim((string)($visibilityState['visibilityHelpText'] ?? '')) !== ''): ?>
                            <details class="generic-context-help"><summary aria-label="<?= $escape((string)$visibilityState['visibilityHelpText']) ?>">?</summary><div class="generic-context-help__content"><?= $escape((string)$visibilityState['visibilityHelpText']) ?></div></details>
                            <?php endif; ?>
                        </div>
                        <select name="visibility_type" id="omo-decision-majority-judgment-visibility" class="generic-form-control generic-form-control--compact" <?= $canEditStructure ? '' : 'disabled' ?>>
                            <?php foreach (($visibilityState['visibilityOptions'] ?? array()) as $optionValue => $optionLabel): ?>
                            <option
                                value="<?= $escape($optionValue) ?>"
                                <?= $optionValue === ($visibilityState['selectedVisibilityType'] ?? DecisionProcess::getDefaultVisibilityType()) ? ' selected' : '' ?>
                                <?= !empty(($visibilityState['disabledVisibilityTypes'] ?? array())[$optionValue]) ? ' disabled' : '' ?>
                            ><?= $escape($optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.status', [], $lang, $sourceLang)) ?></span>
                            <select name="status" class="generic-form-control generic-form-control--compact" <?= $isEditable ? '' : 'disabled' ?>>
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
                    </div>

                    <div class="generic-form-grid">
                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.consultation_start', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="consultation_start_at" class="generic-form-control generic-form-control--compact" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_start_at')) : '') ?>" <?= $canEditStartDates ? '' : 'disabled' ?>>
                        </label>

                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.consultation_end', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="consultation_end_at" class="generic-form-control generic-form-control--compact" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_end_at')) : '') ?>" <?= $isEditable ? '' : 'disabled' ?>>
                        </label>

                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.evaluation_start', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="evaluation_start_at" class="generic-form-control generic-form-control--compact" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_start_at')) : '') ?>" <?= $canEditStartDates ? '' : 'disabled' ?>>
                        </label>

                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.evaluation_end', [], $lang, $sourceLang)) ?></span>
                            <input type="datetime-local" name="evaluation_end_at" class="generic-form-control generic-form-control--compact" value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_end_at')) : '') ?>" <?= $isEditable ? '' : 'disabled' ?>>
                        </label>
                    </div>
                        </div>
                    </div>

                    <?= omoDecisionRenderInvitationSection($decision, array_merge($context, ['method' => DecisionProcess::METHOD_MAJORITY_JUDGMENT]), $lang, $sourceLang, $escape, 'omo-decision-majority-judgment__invitation-summary') ?>

                    </section>
                    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-decision-edit__questions-section">
                    <h3 class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.multi.questions_title', [], $lang, $sourceLang)) ?></h3>

                    <?php if (function_exists('omoDecisionRenderEditorGroupSwitch')) {
                        omoDecisionRenderEditorGroupSwitch($context, $decision instanceof DecisionProcess ? $decision : null, $decisionGroup instanceof DecisionGroup ? $decisionGroup : null, $decision instanceof DecisionProcess ? $decision->getDecisionGroups(false) : [], $lang, $sourceLang, $escape);
                    } ?>
                    <?php endif; ?>

                    <label class="generic-form-field omo-decision-majority-judgment__field">
                        <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.title', [], $lang, $sourceLang)) ?></span>
                        <input type="text" name="title" class="generic-form-control generic-form-control--compact" required maxlength="190" value="<?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('title')) : '') ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.title', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>>
                    </label>

                    <label class="generic-form-field omo-decision-majority-judgment__field">
                        <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.description', [], $lang, $sourceLang)) ?></span>
                        <textarea name="description" class="generic-form-control generic-form-control--compact omo-decision-majority-judgment__textarea" rows="4" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.description', [], $lang, $sourceLang)) ?>" <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('description')) : '') ?></textarea>
                    </label>

                    <div class="omo-decision-majority-judgment__grid generic-form-grid generic-form-grid--compact">
                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.edit.field.evaluation_method', [], $lang, $sourceLang)) ?></span>
                            <select class="generic-form-control generic-form-control--compact" disabled>
                                <option selected><?= $escape(t('decisions.edit.method.majority_judgment.label', [], $lang, $sourceLang)) ?></option>
                            </select>
                        </label>
                        <label class="generic-form-field omo-decision-majority-judgment__field">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.type', [], $lang, $sourceLang)) ?></span>
                            <select name="decision_type" class="generic-form-control generic-form-control--compact" <?= $canEditStructure ? '' : 'disabled' ?>>
                                <option value="<?= $escape(DecisionProcess::TYPE_DECISION) ?>"<?= $decisionType === DecisionProcess::TYPE_DECISION ? ' selected' : '' ?>><?= $escape(t('decisions.majority_judgment.option.type.decision', [], $lang, $sourceLang)) ?></option>
                                <option value="<?= $escape(DecisionProcess::TYPE_CONSULTATION) ?>"<?= $decisionType === DecisionProcess::TYPE_CONSULTATION ? ' selected' : '' ?>><?= $escape(t('decisions.majority_judgment.option.type.consultation', [], $lang, $sourceLang)) ?></option>
                            </select>
                        </label>
                    </div>

                    <div class="generic-form-field omo-decision-majority-judgment__field">
                        <div class="omo-decision-settings-title-row">
                            <span class="generic-form-label"><?= $escape(t($embeddedQuestion ? 'decisions.majority_judgment.field.question_settings' : 'decisions.majority_judgment.field.settings', [], $lang, $sourceLang)) ?></span>
                            <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-settings-button" data-omo-decision-mj-settings-open data-omo-decision-mj-settings-title="<?= $escape(t($embeddedQuestion ? 'decisions.majority_judgment.field.question_settings' : 'decisions.majority_judgment.field.settings', [], $lang, $sourceLang)) ?>"><?= $escape(t('decisions.majority_judgment.action.configure', [], $lang, $sourceLang)) ?></button>
                        </div>
                        <div class="generic-soft-panel generic-soft-panel--stack generic-soft-panel--summary omo-decision-majority-judgment__settings-summary">
                        <?= omoDecisionRenderVoteWeightEditorAssets() ?>
                        <input type="hidden" name="is_anonymous" value="<?= $isAnonymous ? '1' : '' ?>" data-omo-decision-mj-hidden-anonymous>
                        <input type="hidden" name="allow_anonymous_votes" value="<?= $allowAnonymousVotes ? '1' : '' ?>" data-omo-decision-mj-hidden-allow-anonymous-votes>
                        <input type="hidden" name="allow_consultation_proposals" value="<?= $allowConsultationProposals ? '1' : '' ?>" data-omo-decision-mj-hidden-consultation-proposals>
                        <input type="hidden" name="allow_proposal_discussions" value="<?= $allowProposalDiscussions ? '1' : '' ?>" data-omo-decision-mj-hidden-proposal-discussions>
                        <input type="hidden" name="show_live_results" value="<?= $showLiveResults ? '1' : '' ?>" data-omo-decision-mj-hidden-live-results>
                        <?php if (!$embeddedQuestion): ?>
                        <input type="hidden" name="owner_intermediate_results_access" value="<?= $ownerIntermediateResultsAccess ? '1' : '0' ?>">
                        <label class="generic-form-checkbox">
                            <input type="checkbox" name="<?= $canEditStructure ? 'owner_intermediate_results_access' : '' ?>" value="1" <?= $ownerIntermediateResultsAccess ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                            <span><?= $escape(t('decisions.edit.owner_intermediate_results_access_explicit', [], $lang, $sourceLang)) ?></span>
                        </label>
                        <input type="hidden" name="participant_intermediate_results_access" value="<?= $participantIntermediateResultsAccess ? '1' : '0' ?>">
                        <label class="generic-form-checkbox">
                            <input type="checkbox" name="<?= $canEditStructure ? 'participant_intermediate_results_access' : '' ?>" value="1" <?= $participantIntermediateResultsAccess ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                            <span><?= $escape(t('decisions.edit.participant_intermediate_results_access', [], $lang, $sourceLang)) ?></span>
                        </label>
                        <input type="hidden" name="participant_responses_editable" value="<?= $participantResponsesEditable ? '1' : '0' ?>">
                        <label class="generic-form-checkbox">
                            <input type="checkbox" name="<?= $canEditStructure ? 'participant_responses_editable' : '' ?>" value="1" <?= $participantResponsesEditable ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                            <span><?= $escape(t('decisions.edit.participant_responses_editable', [], $lang, $sourceLang)) ?></span>
                        </label>
                        <?php endif; ?>
                        <input type="hidden" name="randomize_proposal_order" value="<?= $randomizeProposalOrder ? '1' : '' ?>" data-omo-decision-mj-hidden-random-order>
                        <input type="hidden" name="one_proposal_at_a_time" value="<?= $oneProposalAtATime ? '1' : '' ?>" data-omo-decision-mj-hidden-one-proposal-at-a-time>
                        <?= omoDecisionRenderProposalContentSettings($proposalContent, $lang, $sourceLang, $escape, $canEditStructure, 'hidden') ?>
                        <input type="hidden" name="vote_weight_enabled" value="<?= $voteWeightEnabled ? '1' : '' ?>" data-omo-decision-mj-hidden-vote-weight-enabled>
                        <input type="hidden" name="vote_weight_question" value="<?= $escape($voteWeightQuestion) ?>" data-omo-decision-mj-hidden-vote-weight-question>
                        <input
                            type="hidden"
                            name="vote_weight_options_json"
                            value="<?= $escape($voteWeightOptionsJson) ?>"
                            data-omo-decision-mj-hidden-vote-weight-options
                            data-default-options-json="<?= $escape($defaultVoteWeightOptionsJson) ?>"
                        >
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
                            data-default-active="<?= !empty($mentionOption['default_active']) ? '1' : '' ?>"
                        >
                        <?php endforeach; ?>
                        <input type="hidden" name="mention_customization_enabled" value="<?= $mentionCustomizationEnabled ? '1' : '' ?>" data-omo-decision-mj-hidden-mention-customization-enabled>
                        <div class="omo-decision-majority-judgment__settings-head omo-decision-settings-head">
                            <div class="generic-form-field omo-decision-majority-judgment__field">
                                <div class="omo-decision-settings-overview">
                                    <section class="omo-decision-settings-overview__group">
                                        <span class="omo-decision-settings-overview__title"><?= $escape(t('decisions.edit.settings.behavior', [], $lang, $sourceLang)) ?></span>
                                        <div class="omo-decision-settings-overview__items">
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.edit.proposal_content.summary_label', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-proposal-content-summary data-title-label="<?= $escape(t('decisions.edit.proposal_content.title_field', [], $lang, $sourceLang)) ?>" data-description-label="<?= $escape(t('decisions.edit.proposal_content.description_field', [], $lang, $sourceLang)) ?>" data-url-label="<?= $escape(t('decisions.edit.proposal_content.url_field', [], $lang, $sourceLang)) ?>"><?= $escape($proposalContentSummary) ?></span></span>
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-scale-summary><?= $escape($settingsScaleSummary) ?></span></span>
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-vote-weight-summary data-yes-label="<?= $escape(t('decisions.edit.block_settings.vote_weighting_summary_yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.edit.block_settings.vote_weighting_summary_no', [], $lang, $sourceLang)) ?>"><?= $escape($voteWeightSummaryText) ?></span></span>
                                        </div>
                                    </section>
                                    <section class="omo-decision-settings-overview__group">
                                        <span class="omo-decision-settings-overview__title"><?= $escape(t('decisions.edit.settings.participation', [], $lang, $sourceLang)) ?></span>
                                        <div class="omo-decision-settings-overview__items">
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-consultation-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($allowConsultationProposals ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span></span>
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.majority_judgment.field.allow_proposal_discussions', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-discussions-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($allowProposalDiscussions ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span></span>
                                        </div>
                                    </section>
                                    <section class="omo-decision-settings-overview__group">
                                        <span class="omo-decision-settings-overview__title"><?= $escape(t('decisions.edit.settings.presentation', [], $lang, $sourceLang)) ?></span>
                                        <div class="omo-decision-settings-overview__items">
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.majority_judgment.field.random_order', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-random-order-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($randomizeProposalOrder ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span></span>
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.majority_judgment.field.one_proposal_at_a_time', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-one-proposal-at-a-time-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($oneProposalAtATime ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span></span>
                                        </div>
                                    </section>
                                    <section class="omo-decision-settings-overview__group">
                                        <span class="omo-decision-settings-overview__title"><?= $escape(t('decisions.edit.settings.privacy', [], $lang, $sourceLang)) ?></span>
                                        <div class="omo-decision-settings-overview__items">
                                            <span class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(t('decisions.majority_judgment.field.named', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-anonymous-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape(!$isAnonymous ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span></span>
                                            <span class="omo-decision-majority-judgment__readonly-stat" data-omo-decision-mj-allow-anonymous-votes-stat<?= $isAnonymous ? ' hidden' : '' ?>><strong><?= $escape(t('decisions.majority_judgment.field.allow_anonymous_votes', [], $lang, $sourceLang)) ?></strong><span data-omo-decision-mj-allow-anonymous-votes-summary data-yes-label="<?= $escape(t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($allowAnonymousVotes ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang)) ?></span></span>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <template data-omo-decision-mj-settings-template>
                            <div class="omo-decision-settings-popup omo-decision-majority-judgment-popup__grid" data-topbar-modal-max-width="820px">
                                <div class="generic-soft-panel generic-soft-panel--stack">
                                    <label class="omo-decision-majority-judgment-popup__field">
                                        <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-majority-judgment__toggle">
                                            <input type="checkbox" data-omo-decision-mj-popup-mention-customization <?= $mentionCustomizationEnabled ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.majority_judgment.field.scale_customize', [], $lang, $sourceLang)) ?></span>
                                        </span>
                                    </label>
                                    <div class="omo-decision-majority-judgment-popup__mention-settings" data-omo-decision-mj-popup-mention-settings<?= $mentionCustomizationEnabled ? '' : ' hidden' ?>>
                                    <div class="omo-decision-majority-judgment-popup__mention-list">
                                        <?php foreach ($mentionOptions as $mentionScore => $mentionOption): ?>
                                        <div class="omo-decision-majority-judgment-popup__mention-row">
                                            <label class="omo-decision-majority-judgment-popup__field">
                                                <span class="generic-card-title generic-card-title--small omo-decision-majority-judgment-popup__mention-title">
                                                    <span><?= $escape(t('decisions.majority_judgment.field.scale_slot_prefix', [], $lang, $sourceLang)) ?></span>
                                                    <span class="omo-decision-majority-judgment-popup__mention-dot omo-decision-majority-judgment-popup__mention-dot--<?= $escape((string)$mentionScore) ?>" aria-hidden="true"></span>
                                                    <span class="omo-decision-majority-judgment__sr-only generic-visually-hidden"><?= $escape(str_replace('{index}', (string)($mentionScore + 1), t('decisions.majority_judgment.field.scale_slot', ['index' => (string)($mentionScore + 1)], $lang, $sourceLang))) ?></span>
                                                </span>
                                                <input
                                                    type="text"
                                                    class="generic-form-control generic-form-control--compact"
                                                    value="<?= $escape((string)$mentionOption['label']) ?>"
                                                    placeholder="<?= $escape((string)$mentionOption['default_label']) ?>"
                                                    data-omo-decision-mj-popup-mention-label="<?= $escape((string)$mentionScore) ?>"
                                                    data-default-label="<?= $escape((string)$mentionOption['default_label']) ?>"
                                                    maxlength="90"
                                                    <?= $canEditStructure ? '' : 'disabled' ?>
                                                >
                                            </label>
                                            <label class="omo-decision-majority-judgment-popup__field omo-decision-majority-judgment-popup__field--toggle">
                                                <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.scale_active', [], $lang, $sourceLang)) ?></span>
                                                <span class="omo-decision-majority-judgment__toggle">
                                                    <input type="checkbox" data-omo-decision-mj-popup-mention-active="<?= $escape((string)$mentionScore) ?>" data-default-active="<?= !empty($mentionOption['default_active']) ? '1' : '' ?>" <?= !empty($mentionOption['active']) ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                                    <span><?= $escape(t('decisions.majority_judgment.field.scale_active', [], $lang, $sourceLang)) ?></span>
                                                </span>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.field.scale_center_hint', [], $lang, $sourceLang)) ?></p>
                                    </div>
                                </div>
                                <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                                    <span class="omo-decision-settings-popup__group-title"><?= $escape(t('decisions.edit.settings.participation', [], $lang, $sourceLang)) ?></span>
                                    <div class="omo-decision-settings-popup__options">
                                        <label class="omo-decision-settings-popup__option">
                                            <input type="checkbox" data-omo-decision-mj-popup-consultation-proposals <?= $allowConsultationProposals ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                        <label class="omo-decision-settings-popup__option">
                                            <input type="checkbox" data-omo-decision-mj-popup-proposal-discussions <?= $allowProposalDiscussions ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.majority_judgment.field.allow_proposal_discussions', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                    </div>
                                </section>
                                <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                                    <span class="omo-decision-settings-popup__group-title"><?= $escape(t('decisions.edit.settings.presentation', [], $lang, $sourceLang)) ?></span>
                                    <div class="omo-decision-settings-popup__options">
                                        <label class="omo-decision-settings-popup__option">
                                            <input type="checkbox" data-omo-decision-mj-popup-random-order <?= $randomizeProposalOrder ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.majority_judgment.field.random_order', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                        <label class="omo-decision-settings-popup__option">
                                            <input type="checkbox" data-omo-decision-mj-popup-one-proposal-at-a-time <?= $oneProposalAtATime ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.majority_judgment.field.one_proposal_at_a_time', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                    </div>
                                </section>
                                <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                                    <span class="omo-decision-settings-popup__group-title"><?= $escape(t('decisions.edit.settings.privacy', [], $lang, $sourceLang)) ?></span>
                                    <div class="omo-decision-settings-popup__options">
                                        <label class="omo-decision-settings-popup__option">
                                            <input type="checkbox" data-omo-decision-mj-popup-anonymous <?= !$isAnonymous ? 'checked' : '' ?> <?= $canEditStructure && $canEnableNamedVote ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.majority_judgment.field.named', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                        <div class="omo-decision-settings-popup__option-with-help" data-omo-decision-mj-popup-allow-anonymous-votes-option hidden>
                                            <label class="omo-decision-settings-popup__option">
                                                <input type="checkbox" data-omo-decision-mj-popup-allow-anonymous-votes <?= $allowAnonymousVotes ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                                <span><?= $escape(t('decisions.majority_judgment.field.allow_anonymous_votes', [], $lang, $sourceLang)) ?></span>
                                            </label>
                                            <details class="generic-context-help">
                                                <summary aria-label="<?= $escape(t('decisions.majority_judgment.field.allow_anonymous_votes_help', [], $lang, $sourceLang)) ?>">?</summary>
                                                <div class="generic-context-help__content"><?= $escape(t('decisions.majority_judgment.field.allow_anonymous_votes_help', [], $lang, $sourceLang)) ?></div>
                                            </details>
                                        </div>
                                        <div<?= $embeddedQuestion ? ' hidden' : '' ?>>
                                        <label class="omo-decision-settings-popup__option omo-decision-settings-popup__option--wide">
                                            <input type="checkbox" data-omo-decision-mj-popup-owner-intermediate-results <?= $ownerIntermediateResultsAccess ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.edit.owner_intermediate_results_access_explicit', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                        <label class="omo-decision-settings-popup__option omo-decision-settings-popup__option--wide">
                                            <input type="checkbox" data-omo-decision-mj-popup-participant-intermediate-results <?= $participantIntermediateResultsAccess ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.edit.participant_intermediate_results_access', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                        <label class="omo-decision-settings-popup__option omo-decision-settings-popup__option--wide">
                                            <input type="checkbox" data-omo-decision-mj-popup-participant-responses-editable <?= $participantResponsesEditable ? 'checked' : '' ?> <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.edit.participant_responses_editable', [], $lang, $sourceLang)) ?></span>
                                        </label>
                                        </div>
                                    </div>
                                </section>
                                <?= omoDecisionRenderProposalContentSettings($proposalContent, $lang, $sourceLang, $escape, $canEditStructure, 'popup') ?>
                                <?= omoDecisionRenderVoteWeightEditor($lang, $sourceLang, $escape, [
                                    'canEdit' => $canEditStructure,
                                    'enabled' => $voteWeightEnabled,
                                    'question' => $voteWeightQuestion,
                                    'options' => $voteWeightOptions,
                                ]) ?>
                                <div class="omo-decision-settings-popup__actions omo-decision-majority-judgment-popup__actions">
                                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-mj-popup-cancel><?= $escape(t('decisions.majority_judgment.action.close', [], $lang, $sourceLang)) ?></button>
                                    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-mj-popup-apply <?= $canEditStructure ? '' : 'disabled' ?>><?= $escape(t('decisions.majority_judgment.action.apply', [], $lang, $sourceLang)) ?></button>
                                </div>
                            </div>
                        </template>
                        </div>
                    </div>

                    <div class="generic-form-field omo-decision-majority-judgment__field">
                        <div class="generic-heading-with-help">
                            <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.proposals', [], $lang, $sourceLang)) ?></span>
                            <details class="generic-context-help">
                                <summary aria-label="<?= $escape(t('decisions.majority_judgment.field.proposals_hint', [], $lang, $sourceLang)) ?>">?</summary>
                                <div class="generic-context-help__content"><?= $escape(t('decisions.majority_judgment.field.proposals_hint', [], $lang, $sourceLang)) ?></div>
                            </details>
                        </div>
                        <div class="omo-decision-majority-judgment__proposal-list" data-omo-decision-mj-proposal-list>
                            <?php foreach ($proposalItems as $proposalIndex => $proposalItem): ?>
                            <div class="omo-decision-majority-judgment__proposal-card omo-decision-proposal-card generic-section<?= $canEditProposals ? '' : ' omo-decision-majority-judgment__proposal-card--locked omo-decision-proposal-card--locked' ?>" data-omo-decision-mj-proposal-card draggable="<?= $canEditProposals ? 'true' : 'false' ?>">
                                <?php if ($canEditProposals): ?>
                                <button type="button" class="omo-decision-majority-judgment__proposal-drag generic-drag-handle generic-drag-handle--stretch" data-omo-decision-mj-proposal-drag title="<?= $escape(t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang)) ?>" aria-label="<?= $escape(t('decisions.majority_judgment.field.proposals_reorder', [], $lang, $sourceLang)) ?>">&#8942;&#8942;</button>
                                <?php endif; ?>
                                <div class="omo-decision-majority-judgment__proposal-main">
                                    <span class="omo-decision-majority-judgment__proposal-label" data-omo-decision-mj-proposal-label><?= $escape(str_replace('{index}', (string)($proposalIndex + 1), t('decisions.majority_judgment.field.proposals_item', ['index' => (string)($proposalIndex + 1)], $lang, $sourceLang))) ?></span>
                                    <?php if ($proposalContent['title']): ?>
                                    <input type="text" name="proposals[]" class="generic-form-control generic-form-control--compact" value="<?= $escape((string)$proposalItem['title']) ?>" placeholder="<?= $escape(t('decisions.majority_judgment.placeholder.proposals', [], $lang, $sourceLang)) ?>" <?= $canEditProposals ? '' : 'disabled' ?>>
                                    <?php else: ?>
                                    <input type="hidden" name="proposals[]" value="<?= $escape((string)$proposalItem['title']) ?>">
                                    <?php endif; ?>
                                    <?php if (!$proposalContent['title'] && $proposalContent['description']): ?>
                                    <div data-omo-proposal-html-field>
                                        <div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-decision-mj-proposal-description-editor<?= $canEditProposals ? '' : ' data-omo-proposal-html-disabled="1"' ?>></div>
                                        <textarea hidden aria-hidden="true" name="proposal_descriptions[]" data-omo-proposal-html-value data-omo-decision-mj-proposal-description><?= $escape((string)($proposalItem['description'] ?? '')) ?></textarea>
                                    </div>
                                    <?php else: ?>
                                    <textarea hidden aria-hidden="true" name="proposal_descriptions[]" data-omo-decision-mj-proposal-description><?= $escape((string)($proposalItem['description'] ?? '')) ?></textarea>
                                    <?php endif; ?>
                                    <input type="hidden" name="proposal_info_urls[]" value="<?= $escape((string)($proposalItem['info_url'] ?? '')) ?>" data-omo-decision-mj-proposal-info-url>
                                    <input type="hidden" name="proposal_ids[]" value="<?= $escape((int)($proposalItem['id'] ?? 0)) ?>">
                                    <?php if ($showOwnerIntermediateResults): ?>
                                    <?php
                                    $ownerProposalId = (int)($proposalItem['id'] ?? 0);
                                    $ownerStat = $proposalStats[$ownerProposalId] ?? [
                                        'distribution' => omoDecisionMajorityJudgmentGetEmptyDistribution(),
                                        'counted_count' => 0,
                                        'scale' => 1,
                                    ];
                                    $ownerCountedMentions = (int)($ownerStat['counted_count'] ?? 0);
                                    $ownerStatScale = max(1, (int)($ownerStat['scale'] ?? 1));
                                    ?>
                                    <?php if ($ownerCountedMentions > 0): ?>
                                    <div class="omo-decision-majority-judgment__distribution" aria-label="<?= $escape(t('decisions.majority_judgment.field.distribution', [], $lang, $sourceLang)) ?>">
                                        <?php foreach ($mentions as $score => $mentionLabel): ?>
                                        <?php
                                        if ((int)$score === omoDecisionMajorityJudgmentGetNoOpinionScore()) {
                                            continue;
                                        }
                                        $ownerSegmentCount = (int)($ownerStat['distribution'][$score] ?? 0);
                                        $ownerSegmentPercent = ($ownerSegmentCount / $ownerCountedMentions) * 100;
                                        $ownerSegmentPercentLabel = number_format($ownerSegmentPercent, 1, ',', ' ');
                                        $ownerSegmentWidth = $ownerSegmentPercent > 0 ? number_format($ownerSegmentPercent, 4, '.', '') : '0';
                                        $ownerSegmentTooltip = t(
                                            'decisions.majority_judgment.tooltip.segment',
                                            [
                                                'mention' => (string)$mentionLabel,
                                                'count' => (string)omoDecisionBlockSettingsVoteWeightUnitsToValue($ownerSegmentCount, $ownerStatScale),
                                                'percent' => $ownerSegmentPercentLabel,
                                            ],
                                            $lang,
                                            $sourceLang
                                        );
                                        ?>
                                        <span
                                            class="omo-decision-majority-judgment__distribution-segment omo-decision-majority-judgment__distribution-segment--<?= $escape((string)$score) ?>"
                                            style="width: <?= $escape($ownerSegmentWidth) ?>%;"
                                            title="<?= $escape($ownerSegmentTooltip) ?>"
                                            aria-label="<?= $escape($ownerSegmentTooltip) ?>"
                                        ></span>
                                        <?php endforeach; ?>
                                        <span class="omo-decision-majority-judgment__distribution-marker" aria-hidden="true"></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="omo-decision-majority-judgment__distribution-scale omo-decision-majority-judgment__distribution-scale--with-count">
                                        <span><?= $escape((string)$mentions[0]) ?></span>
                                        <span><strong><?= $escape(t('decisions.majority_judgment.field.proposal_votes', [], $lang, $sourceLang)) ?></strong> <?= $escape((string)omoDecisionBlockSettingsVoteWeightUnitsToValue($ownerCountedMentions, $ownerStatScale)) ?></span>
                                        <span><?= $escape((string)$mentions[6]) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="omo-decision-majority-judgment__proposal-menu" data-omo-decision-mj-proposal-menu>
                                        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-toggle" data-omo-decision-mj-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= $escape(t('decisions.majority_judgment.field.proposal_actions', [], $lang, $sourceLang)) ?>">...</button>
                                    <div class="omo-decision-majority-judgment__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel" data-omo-decision-mj-proposal-menu-panel role="menu" hidden>
                                        <?php if ($proposalContentUrlEnabled): ?><button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-settings role="menuitem"><?= $escape(t('decisions.majority_judgment.field.proposal_details', [], $lang, $sourceLang)) ?></button><?php endif; ?>
                                        <?php if ($canEditProposals): ?>
                                        <button type="button" class="generic-action-button generic-action-button--danger omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-remove role="menuitem"><?= $escape(t('decisions.majority_judgment.field.proposals_remove', [], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-add" data-omo-decision-mj-proposal-add <?= $canEditProposals ? '' : 'disabled' ?>><?= $escape(t('decisions.majority_judgment.field.proposals_add', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if (!$embeddedQuestion): ?>
                    <div class="omo-decision-majority-judgment__feedback" data-omo-decision-mj-feedback aria-live="polite"></div>
                    </section>
                    <?php endif; ?>

                    <script type="application/json" data-omo-decision-mj-data><?= $managePayloadJson ?></script>
                </form>
            <?php else: ?>
                <?php if (!$publicLayout): ?>
                <div class="omo-decision-majority-judgment__summary-grid generic-form-grid generic-form-grid--compact">
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.status', [], $lang, $sourceLang), t('decisions.majority_judgment.option.status.' . $status, [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.type', [], $lang, $sourceLang), $decisionType === DecisionProcess::TYPE_CONSULTATION ? t('decisions.majority_judgment.option.type.consultation', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.type.decision', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php if (!$isConsultationPhase): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.scale', [], $lang, $sourceLang), $scaleSummary, $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php endif; ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.named', [], $lang, $sourceLang), !$isAnonymous ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.allow_consultation_proposals', [], $lang, $sourceLang), $allowConsultationProposals ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.allow_proposal_discussions', [], $lang, $sourceLang), $allowProposalDiscussions ? t('decisions.majority_judgment.option.common.yes', [], $lang, $sourceLang) : t('decisions.majority_judgment.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang), $voteWeightSummaryText, $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.consultation_start', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('consultation_start_at')) : '', $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.evaluation_end', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionMajorityJudgmentFormatDateTimeLocal($decision->get('evaluation_end_at')) : '', $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php if ($resultsMode): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.majority_judgment.field.total_votes', [], $lang, $sourceLang), (string)$submittedVoteCount, $escape, 'omo-decision-majority-judgment__meta-card') ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$publicLayout && $decision instanceof DecisionProcess && trim((string)$decision->get('description')) !== ''): ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.description', [], $lang, $sourceLang)) ?></span>
                    <p class="omo-decision-majority-judgment__text"><?= nl2br($escape(trim((string)$decision->get('description')))) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($isParticipateMode): ?>
                <?php if ($isConsultationPhase): ?>
                <div class="omo-decision-majority-judgment__form generic-form-stack generic-form-stack--compact">
                <?php else: ?>
                <form class="omo-decision-majority-judgment__form generic-form-stack generic-form-stack--compact" action="/omo/api/decision/modules/majority_judgment/respond.php" method="post" data-omo-decision-mj-response-form>
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                    <input type="hidden" name="method" value="<?= $escape(DecisionProcess::METHOD_MAJORITY_JUDGMENT) ?>">
                    <input type="hidden" name="intent" value="participate">
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>
                <?php endif; ?>


                        <?php if (count($proposalObjects) === 0): ?>
                        <p class="omo-decision-majority-judgment__text"><?= $escape(t('decisions.majority_judgment.empty_proposals', [], $lang, $sourceLang)) ?></p>
                        <?php else: ?>
                        <div class="omo-decision-majority-judgment__rating-list"<?= $oneProposalAtATime && !$isConsultationPhase ? ' data-omo-decision-one-at-a-time' : '' ?><?= $oneProposalAtATime && !$isConsultationPhase && (!($selectedResponse instanceof DecisionResponse) || DecisionResponse::normalizeStatus($selectedResponse->get('status')) !== DecisionResponse::STATUS_SUBMITTED) ? ' data-omo-decision-one-at-a-time-draft-url="/omo/api/decision/modules/majority_judgment/respond.php"' : '' ?>>
                            <?php foreach ($proposalObjects as $proposal): ?>
                            <?php $proposalId = (int)$proposal->getId(); ?>
                            <div class="generic-section generic-section--stack omo-decision-majority-judgment__rating-card"<?= $oneProposalAtATime && !$isConsultationPhase ? ' data-omo-decision-one-at-a-time-item' : '' ?>>
                                <div class="omo-decision-majority-judgment__rating-head">
                                    <?php if (omoDecisionProposalTitleIsVisible($proposalContent, $proposal->get('title'))): ?><strong data-omo-proposal-title><?= $escape(trim((string)$proposal->get('title'))) ?></strong><?php endif; ?>
                                    <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-majority-judgment__text', 'omo-decision-majority-judgment__link') ?>
                                    <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
                                </div>
                                <?php if (!$isConsultationPhase): ?>
                                <div class="omo-decision-majority-judgment__rating-scale" role="radiogroup" aria-label="<?= $escape(t('decisions.majority_judgment.field.your_scores', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($mentions as $score => $mentionLabel): ?>
                                    <div class="omo-decision-majority-judgment__rating-option omo-decision-majority-judgment__rating-option--<?= $escape((string)$score) ?><?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? ' is-selected' : '' ?>">
                                        <input class="omo-decision-majority-judgment__rating-input" type="radio" name="scores[<?= $escape($proposalId) ?>]" value="<?= $escape($score) ?>" <?= array_key_exists($proposalId, $selectedScores) && (int)$selectedScores[$proposalId] === (int)$score ? 'checked' : '' ?><?= $canEditSubmittedResponse ? '' : ' disabled' ?> required>
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
                                            <span class="omo-decision-majority-judgment__sr-only generic-visually-hidden"><?= $escape($mentionLabel) ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if ($oneProposalAtATime && !$isConsultationPhase): ?>
                            <div class="omo-decision-one-at-a-time__navigation">
                                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-one-at-a-time-previous><?= $escape(t('decisions.majority_judgment.action.previous_proposal', [], $lang, $sourceLang)) ?></button>
                                <div class="omo-decision-one-at-a-time__dots" data-omo-decision-one-at-a-time-dots aria-label="Propositions"></div>
                                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-one-at-a-time-next><?= $escape(t('decisions.majority_judgment.action.next_proposal', [], $lang, $sourceLang)) ?></button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php if (!$isConsultationPhase): ?>
                    <?= omoDecisionRenderVoteWeightResponseSelector($lang, $sourceLang, $escape, [
                        'enabled' => $voteWeightEnabled,
                        'question' => $voteWeightQuestion,
                        'options' => $voteWeightOptions,
                        'selected_weight' => $selectedVoteWeight,
                        'disabled' => !$canEditSubmittedResponse,
                    ]) ?>
                    <label class="omo-decision-majority-judgment__toggle">
                        <input type="checkbox" name="is_anonymous" value="1"<?= $anonymousVoteChecked ? ' checked' : '' ?><?= $anonymousVoteDisabled || !$canEditSubmittedResponse ? ' disabled' : '' ?>>
                        <span><?= $escape(t('decisions.majority_judgment.field.anonymous', [], $lang, $sourceLang)) ?></span>
                    </label>
                    <?php endif; ?>
                    <?php if ($consultationProposalPanel !== ''): ?>
                    <?= $consultationProposalPanel ?>
                    <?php endif; ?>

                    <?php if (!$isConsultationPhase): ?>
                    <div class="omo-decision-majority-judgment__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-mj-response-submit<?= $canEditSubmittedResponse ? '' : ' disabled' ?>><?= $escape($selectedResponse instanceof DecisionResponse ? t('decisions.majority_judgment.action.update_response', [], $lang, $sourceLang) : t('decisions.majority_judgment.action.submit_response', [], $lang, $sourceLang)) ?></button>
                        <div class="omo-decision-majority-judgment__feedback" data-omo-decision-mj-response-feedback aria-live="polite"></div>
                    </div>

                    <script type="application/json" data-omo-decision-mj-response-data><?= $responsePayloadJson ?></script>
                    <?php endif; ?>
                <?php if ($isConsultationPhase): ?>
                </div>
                <?php else: ?>
                </form>
                <?php endif; ?>
                <?php if ($liveResultsMode): ?>
                <section class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.live_results_heading', [], $lang, $sourceLang)) ?></span>
                    <?php foreach ($proposalObjects as $liveProposal): ?>
                    <?php $liveStat = $proposalStats[(int)$liveProposal->getId()] ?? []; ?>
                    <div class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape(omoDecisionGetProposalLabel($liveProposal, $proposalContent)) ?></strong><span><?= $escape((string)($liveStat['majority_label'] ?? '')) ?> (<?= $escape((string)($liveStat['count'] ?? 0)) ?>)</span></div>
                    <?php endforeach; ?>
                    <?php if (!$liveResultsAnonymous && !$isAnonymous): ?>
                    <?php foreach ($submittedResponses as $liveResponse): ?>
                    <?php
                    if (omoDecisionResponseIsAnonymous($liveResponse, omoDecisionMajorityJudgmentGetMethodKey())) continue;
                    $liveName = omoDecisionResolveResponseParticipantName($decision, $liveResponse);
                    $liveScores = omoDecisionMajorityJudgmentExtractScores($liveResponse);
                    $liveParts = [];
                    foreach ($proposalObjects as $liveProposal) {
                        $liveProposalId = (int)$liveProposal->getId();
                        if (array_key_exists($liveProposalId, $liveScores)) $liveParts[] = omoDecisionGetProposalLabel($liveProposal, $proposalContent) . ' : ' . (string)($allMentions[(int)$liveScores[$liveProposalId]] ?? '');
                    }
                    ?>
                    <?php if ($liveName !== '' && count($liveParts) > 0): ?><div class="omo-decision-majority-judgment__readonly-stat"><strong><?= $escape($liveName) ?></strong><span><?= $escape(implode(' · ', $liveParts)) ?></span></div><?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
                <?php else: ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-form-label"><?= $escape(t('decisions.majority_judgment.field.proposals', [], $lang, $sourceLang)) ?></span>
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
                        <?php if ($voteWeightEnabled): ?>
                        <label class="omo-decision-majority-judgment__results-compare">
                            <input type="checkbox" data-omo-decision-mj-results-compare-toggle>
                            <span><?= $escape(t('decisions.majority_judgment.results_compare.toggle', [], $lang, $sourceLang)) ?></span>
                        </label>
                        <?php endif; ?>
                        <div class="omo-decision-majority-judgment__result-list" data-omo-decision-mj-results-list>
                        <?php foreach ($resultProposalObjects as $resultRankIndex => $proposal): ?>
                        <?php
                        $proposalId = (int)$proposal->getId();
                        $proposalPosition = (int)($proposalOriginalOrder[$proposalId] ?? ($resultRankIndex + 1));
                        $stat = $proposalStats[$proposalId] ?? ['distribution' => omoDecisionMajorityJudgmentGetEmptyDistribution(), 'count' => 0, 'counted_count' => 0, 'no_opinion_count' => 0, 'majority_score' => null, 'majority_label' => ''];
                        $statUnweighted = $proposalStatsUnweighted[$proposalId] ?? ['distribution' => omoDecisionMajorityJudgmentGetEmptyDistribution(), 'count' => 0, 'counted_count' => 0, 'no_opinion_count' => 0, 'majority_score' => null, 'majority_label' => ''];
                        $statScale = max(1, (int)($stat['scale'] ?? 1));
                        $countedMentions = (int)($stat['counted_count'] ?? 0);
                        $countedMentionsWeightLabel = omoDecisionBlockSettingsVoteWeightUnitsToValue($countedMentions, $statScale);
                        $noOpinionCount = (int)($stat['no_opinion_count'] ?? 0);
                        $noOpinionCountWeightLabel = omoDecisionBlockSettingsVoteWeightUnitsToValue($noOpinionCount, $statScale);
                        $countedMentionsUnweighted = (int)($statUnweighted['counted_count'] ?? 0);
                        $noOpinionCountUnweighted = (int)($statUnweighted['no_opinion_count'] ?? 0);
                        ?>
                        <div
                            class="omo-decision-majority-judgment__result-card generic-section generic-section--stack<?= array_key_exists($proposalId, $selectedScores) ? ' is-selected' : '' ?>"
                            data-omo-decision-mj-result-item
                            data-omo-decision-mj-result-rank="<?= $escape((string)($resultRankIndex + 1)) ?>"
                            data-omo-decision-mj-result-position="<?= $escape((string)$proposalPosition) ?>"
                            data-omo-decision-mj-result-title="<?= $escape(omoDecisionGetProposalLabel($proposal, $proposalContent)) ?>"
                        >
                            <div class="omo-decision-majority-judgment__result-head">
                                <div>
                                    <?php if (omoDecisionProposalTitleIsVisible($proposalContent, $proposal->get('title'))): ?><strong data-omo-proposal-title><?= $escape(trim((string)$proposal->get('title'))) ?></strong><?php endif; ?>
                                    <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-majority-judgment__text', 'omo-decision-majority-judgment__link') ?>
                                    <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
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
                                <strong><?= $escape((string)($voteWeightEnabled ? $countedMentionsUnweighted : $countedMentionsWeightLabel)) ?></strong>
                            </div>
                            <?php if ($voteWeightEnabled): ?>
                            <div class="omo-decision-majority-judgment__result-meta">
                                <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape(t('decisions.majority_judgment.field.counted_weight', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)$countedMentionsWeightLabel) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($config['has_no_opinion'])): ?>
                            <div class="omo-decision-majority-judgment__result-meta">
                                <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape($noOpinionLabel !== '' ? $noOpinionLabel : t('decisions.majority_judgment.field.no_opinion_count', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)($voteWeightEnabled ? $noOpinionCountUnweighted : $noOpinionCountWeightLabel)) ?></strong>
                            </div>
                            <?php if ($voteWeightEnabled): ?>
                            <div class="omo-decision-majority-judgment__result-meta">
                                <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape(t('decisions.majority_judgment.field.no_opinion_weight', [], $lang, $sourceLang)) ?></span>
                                <strong><?= $escape((string)$noOpinionCountWeightLabel) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($countedMentions > 0): ?>
                            <div class="omo-decision-majority-judgment__distribution" aria-label="<?= $escape(t('decisions.majority_judgment.field.distribution', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($mentions as $score => $mentionLabel): ?>
                                <?php
                                if ((int)$score === omoDecisionMajorityJudgmentGetNoOpinionScore()) {
                                    continue;
                                }
                                $segmentCount = (int)($stat['distribution'][$score] ?? 0);
                                $segmentCountLabel = omoDecisionBlockSettingsVoteWeightUnitsToValue($segmentCount, $statScale);
                                $segmentPercent = $countedMentions > 0 ? ($segmentCount / $countedMentions) * 100 : 0;
                                $segmentPercentLabel = number_format($segmentPercent, 1, ',', ' ');
                                $segmentWidth = $segmentPercent > 0 ? number_format($segmentPercent, 4, '.', '') : '0';
                                $segmentTooltip = t(
                                    'decisions.majority_judgment.tooltip.segment',
                                    [
                                        'mention' => (string)$mentionLabel,
                                        'count' => (string)$segmentCountLabel,
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
                            <?php if ($voteWeightEnabled): ?>
                            <div class="omo-decision-majority-judgment__comparison" data-omo-decision-mj-results-compare-block hidden>
                                <span class="omo-decision-majority-judgment__comparison-title"><?= $escape(t('decisions.majority_judgment.results_compare.unweighted', [], $lang, $sourceLang)) ?></span>
                                <?php if ($countedMentionsUnweighted > 0 && $statUnweighted['majority_score'] !== null): ?>
                                <span class="omo-decision-majority-judgment__majority-badge omo-decision-majority-judgment__majority-badge--<?= $escape((string)$statUnweighted['majority_score']) ?>">
                                    <?= $escape(t('decisions.majority_judgment.field.majority_mention', [], $lang, $sourceLang)) ?>: <?= $escape((string)$statUnweighted['majority_label']) ?>
                                </span>
                                <?php endif; ?>
                                <div class="omo-decision-majority-judgment__result-meta">
                                    <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape(t('decisions.majority_judgment.field.counted_mentions', [], $lang, $sourceLang)) ?></span>
                                    <strong><?= $escape((string)$countedMentionsUnweighted) ?></strong>
                                </div>
                                <?php if (!empty($config['has_no_opinion'])): ?>
                                <div class="omo-decision-majority-judgment__result-meta">
                                    <span class="omo-decision-majority-judgment__result-meta-label"><?= $escape($noOpinionLabel !== '' ? $noOpinionLabel : t('decisions.majority_judgment.field.no_opinion_count', [], $lang, $sourceLang)) ?></span>
                                    <strong><?= $escape((string)$noOpinionCountUnweighted) ?></strong>
                                </div>
                                <?php endif; ?>
                                <?php if ($countedMentionsUnweighted > 0): ?>
                                <div class="omo-decision-majority-judgment__distribution" aria-label="<?= $escape(t('decisions.majority_judgment.field.distribution', [], $lang, $sourceLang)) ?>">
                                    <?php foreach ($mentions as $score => $mentionLabel): ?>
                                    <?php
                                    if ((int)$score === omoDecisionMajorityJudgmentGetNoOpinionScore()) {
                                        continue;
                                    }
                                    $segmentCount = (int)($statUnweighted['distribution'][$score] ?? 0);
                                    $segmentPercent = $countedMentionsUnweighted > 0 ? ($segmentCount / $countedMentionsUnweighted) * 100 : 0;
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
                                        class="omo-decision-majority-judgment__distribution-segment omo-decision-majority-judgment__distribution-segment--<?= $escape((string)$score) ?> omo-decision-majority-judgment__distribution-segment--secondary"
                                        style="width: <?= $escape($segmentWidth) ?>%;"
                                        title="<?= $escape($segmentTooltip) ?>"
                                        aria-label="<?= $escape($segmentTooltip) ?>"
                                    ></span>
                                    <?php endforeach; ?>
                                    <span class="omo-decision-majority-judgment__distribution-marker" aria-hidden="true"></span>
                                </div>
                                <div class="omo-decision-majority-judgment__distribution-scale" aria-hidden="true">
                                    <span><?= $escape((string)$mentions[0]) ?></span>
                                    <span><?= $escape((string)$mentions[6]) ?></span>
                                </div>
                                <?php endif; ?>
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

        <?php if ($includeAssets): ?>
        <script src="<?= commonAssetUrl('/omo/api/decision/modules/majority_judgment/module.js') ?>"></script>

        <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/decision/modules/majority_judgment/module.css') ?>">
        <?php endif; ?>
        <?php
    }
}
