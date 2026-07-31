<?php

use dbObject\DecisionProcess;
use dbObject\DecisionGroup;
use dbObject\DecisionResponse;

require_once __DIR__ . '/shared.php';

if (!function_exists('omoDecisionVoteModuleGetSourceLang')) {
    function omoDecisionVoteModuleGetSourceLang()
    {
        return [
            'decisions.vote.title' => [
                'text' => 'Configurer un vote simple',
                'context' => 'Title of the simple vote management screen.',
            ],
            'decisions.vote.description' => [
                'text' => 'Créez un scrutin où chaque participant choisit une proposition parmi plusieurs options.',
                'context' => 'Description of the simple vote management screen.',
            ],
            'decisions.vote.view_title' => [
                'text' => 'Voir le scrutin',
                'context' => 'Title of the read-only simple vote screen.',
            ],
            'decisions.vote.view_description' => [
                'text' => 'Consultez le scrutin, ses dates et ses propositions sans modifier sa configuration.',
                'context' => 'Description of the read-only simple vote screen.',
            ],
            'decisions.vote.participate_title' => [
                'text' => 'Participer au vote',
                'context' => 'Title of the simple vote participation screen.',
            ],
            'decisions.vote.participate_description' => [
                'text' => 'Choisissez une proposition et enregistrez votre vote pour ce scrutin.',
                'context' => 'Description of the simple vote participation screen.',
            ],
            'decisions.vote.change_method' => [
                'text' => 'Changer de méthode',
                'context' => 'Secondary action to go back to the method chooser.',
            ],
            'decisions.vote.notice.started' => [
                'text' => 'Le scrutin a commencé. Le titre, la description, les questions et les paramètres sont désormais verrouillés.',
                'context' => 'Notice shown when the vote configuration is locked after evaluation starts.',
            ],
            'decisions.vote.notice.responses' => [
                'text' => 'Au moins une réponse a déjà été soumise. Seuls le statut et les dates de fin restent ajustables.',
                'context' => 'Notice shown when some schedule fields are also locked after submitted votes.',
            ],
            'decisions.vote.notice.consultation_proposals' => [
                'text' => 'Les propositions restent ajustables pendant la consultation tant qu aucune reponse n a ete soumise.',
                'context' => 'Notice shown when proposal editing remains allowed during consultation.',
            ],
            'decisions.vote.notice.results' => [
                'text' => 'Ce scrutin est terminé. Seule la consultation des résultats reste disponible.',
                'context' => 'Notice shown when the vote is in results or archived mode.',
            ],
            'decisions.vote.field.title' => [
                'text' => 'Question',
                'context' => 'Label for the group title field.',
            ],
            'decisions.vote.field.description' => [
                'text' => 'Description de la question',
                'context' => 'Label for the group description field.',
            ],
            'decisions.vote.field.process_title' => [
                'text' => 'Titre du processus',
                'context' => 'Label for the decision process title field.',
            ],
            'decisions.vote.field.process_description' => [
                'text' => 'Description du contexte',
                'context' => 'Label for the decision process description field.',
            ],
            'decisions.vote.field.process_section' => [
                'text' => 'Contexte du processus',
                'context' => 'Section title for process-level context fields.',
            ],
            'decisions.vote.field.group_section' => [
                'text' => 'Question de ce groupe',
                'context' => 'Section title for group-level question fields.',
            ],
            'decisions.vote.field.type' => [
                'text' => 'Type de prise de décision',
                'context' => 'Label for the decision type field.',
            ],
            'decisions.vote.field.status' => [
                'text' => 'Statut',
                'context' => 'Label for the status field.',
            ],
            'decisions.vote.field.consultation_start' => [
                'text' => 'Début de consultation',
                'context' => 'Label for the consultation start date field.',
            ],
            'decisions.vote.field.consultation_end' => [
                'text' => 'Fin de consultation',
                'context' => 'Label for the consultation end date field.',
            ],
            'decisions.vote.field.evaluation_start' => [
                'text' => 'Début du vote',
                'context' => 'Label for the vote start date field.',
            ],
            'decisions.vote.field.evaluation_end' => [
                'text' => 'Clôture du vote',
                'context' => 'Label for the vote end date field.',
            ],
            'decisions.vote.field.proposals' => [
                'text' => 'Propositions',
                'context' => 'Label for the proposals list.',
            ],
            'decisions.vote.field.proposals_hint' => [
                'text' => 'Ajoutez une proposition par ligne, puis réorganisez-les par glisser-déposer. Au moins deux propositions sont nécessaires, sauf si les propositions sont autorisées pendant une période de consultation.',
                'context' => 'Hint under the proposals list.',
            ],
            'decisions.vote.field.proposals_add' => [
                'text' => 'Ajouter une proposition',
                'context' => 'Button label to append a new proposal field.',
            ],
            'decisions.vote.field.proposals_remove' => [
                'text' => 'Supprimer',
                'context' => 'Button label to remove a proposal field.',
            ],
            'decisions.vote.field.proposals_reorder' => [
                'text' => 'Réordonner',
                'context' => 'Aria label for the proposal drag handle.',
            ],
            'decisions.vote.field.proposals_item' => [
                'text' => 'Proposition {index}',
                'context' => 'Visible label prefix for one proposal input row.',
            ],
            'decisions.vote.field.proposal_details' => [
                'text' => 'Details',
                'context' => 'Button label opening the proposal detail popup.',
            ],
            'decisions.vote.field.proposal_description' => [
                'text' => 'Description de la proposition',
                'context' => 'Label for the proposal description field.',
            ],
            'decisions.vote.field.proposal_info_url' => [
                'text' => 'URL d information',
                'context' => 'Label for the proposal info URL field.',
            ],
            'decisions.vote.field.settings' => [
                'text' => 'Parametres du vote',
                'context' => 'Section title for vote-specific settings.',
            ],
            'decisions.vote.field.choice_mode' => [
                'text' => 'Mode de choix',
                'context' => 'Label for the single or multiple choice setting.',
            ],
            'decisions.vote.field.allow_anonymous_votes' => [
                'text' => 'Autoriser les votes anonymes',
                'context' => 'Label for allowing participants to choose anonymity for their own vote.',
            ],
            'decisions.vote.field.max_choices' => [
                'text' => 'Nombre maximal de choix',
                'context' => 'Label for the maximum number of selectable proposals in multiple choice mode.',
            ],
            'decisions.vote.field.max_choices_unlimited' => [
                'text' => 'Sans limite',
                'context' => 'Label used when the maximum number of choices is unlimited.',
            ],
            'decisions.vote.field.anonymous' => [
                'text' => 'Vote anonyme',
                'context' => 'Label for the anonymity setting.',
            ],
            'decisions.vote.field.allow_consultation_proposals' => [
                'text' => 'Autoriser les propositions pendant la consultation',
                'context' => 'Label for allowing proposals to evolve during consultation before any response.',
            ],
            'decisions.vote.field.allow_proposal_discussions' => [
                'text' => 'Autoriser les discussions des propositions',
                'context' => 'Label for allowing account users to discuss proposals.',
            ],
            'decisions.vote.field.multiple_hint' => [
                'text' => 'Choisissez jusqu a {count} propositions.',
                'context' => 'Help text shown when a participant can select several proposals.',
            ],
            'decisions.vote.field.multiple_hint_unlimited' => [
                'text' => 'Choisissez autant de propositions que vous voulez.',
                'context' => 'Help text shown when a participant can select multiple proposals without limit.',
            ],
            'decisions.vote.field.your_choice' => [
                'text' => 'Votre choix',
                'context' => 'Label for the voting choice fieldset.',
            ],
            'decisions.vote.field.current_response' => [
                'text' => 'Vote enregistré',
                'context' => 'Label shown when a previous vote exists.',
            ],
            'decisions.vote.field.total_votes' => [
                'text' => 'Votes enregistrés',
                'context' => 'Label for the total number of submitted votes.',
            ],
            'decisions.vote.field.proposal_votes' => [
                'text' => 'Votes',
                'context' => 'Label for the number of votes received by one proposal.',
            ],
            'decisions.vote.field.proposal_share' => [
                'text' => 'Part',
                'context' => 'Label for the vote share of one proposal.',
            ],
            'decisions.vote.field.distribution' => [
                'text' => 'Repartition graphique des votes',
                'context' => 'Label for the vote result percentage bar.',
            ],
            'decisions.vote.results_compare.toggle' => [
                'text' => 'Afficher le resultat non pondere',
                'context' => 'Checkbox label used to reveal the unweighted result comparison.',
            ],
            'decisions.vote.results_compare.unweighted' => [
                'text' => 'Resultat non pondere',
                'context' => 'Title shown above the unweighted comparison block.',
            ],
            'decisions.vote.results_sort.aria' => [
                'text' => 'Ordre d affichage des resultats',
                'context' => 'Aria label for the simple vote results sort switch.',
            ],
            'decisions.vote.results_sort.rank' => [
                'text' => 'Classement',
                'context' => 'Button label used to sort simple vote results by ranking.',
            ],
            'decisions.vote.results_sort.initial' => [
                'text' => 'Ordre initial',
                'context' => 'Button label used to sort simple vote results by saved proposal order.',
            ],
            'decisions.vote.results_sort.alpha' => [
                'text' => 'Alphabetique',
                'context' => 'Button label used to sort simple vote results alphabetically.',
            ],
            'decisions.vote.option.type.decision' => [
                'text' => 'Décisionnaire',
                'context' => 'Select option for a decision-oriented process.',
            ],
            'decisions.vote.option.type.consultation' => [
                'text' => 'Consultative',
                'context' => 'Select option for a consultation-oriented process.',
            ],
            'decisions.vote.option.status.draft' => [
                'text' => 'En préparation',
                'context' => 'Draft status option in the simple vote editor.',
            ],
            'decisions.vote.option.status.scheduled' => [
                'text' => 'Planifiée',
                'context' => 'Scheduled status option in the simple vote editor.',
            ],
            'decisions.vote.option.status.consultation' => [
                'text' => 'En consultation',
                'context' => 'Consultation status option in the simple vote editor.',
            ],
            'decisions.vote.option.status.evaluation' => [
                'text' => 'En évaluation',
                'context' => 'Evaluation status option in the simple vote editor.',
            ],
            'decisions.vote.option.status.results' => [
                'text' => 'Résultats',
                'context' => 'Results status option in the simple vote editor.',
            ],
            'decisions.vote.option.status.archived' => [
                'text' => 'Archivée',
                'context' => 'Archived status option in the simple vote editor.',
            ],
            'decisions.vote.option.choice_mode.single' => [
                'text' => 'Une seule reponse',
                'context' => 'Option label for a single choice vote.',
            ],
            'decisions.vote.option.choice_mode.multiple' => [
                'text' => 'Plusieurs reponses',
                'context' => 'Option label for a multiple choice vote.',
            ],
            'decisions.vote.option.common.yes' => [
                'text' => 'Oui',
                'context' => 'Generic yes option label.',
            ],
            'decisions.vote.option.common.no' => [
                'text' => 'Non',
                'context' => 'Generic no option label.',
            ],
            'decisions.vote.placeholder.title' => [
                'text' => 'Ex. Quelle option preferez-vous ?',
                'context' => 'Placeholder for the group title field.',
            ],
            'decisions.vote.placeholder.description' => [
                'text' => 'Precisez la question, les nuances et les criteres utiles...',
                'context' => 'Placeholder for the group description field.',
            ],
            'decisions.vote.placeholder.process_title' => [
                'text' => 'Ex. Preparation de la sortie annuelle',
                'context' => 'Placeholder for the decision process title field.',
            ],
            'decisions.vote.placeholder.process_description' => [
                'text' => 'Contexte global, informations communes, cadre de la consultation...',
                'context' => 'Placeholder for the decision process description field.',
            ],
            'decisions.vote.placeholder.proposals' => [
                'text' => 'Nom de la proposition',
                'context' => 'Placeholder for one proposal input.',
            ],
            'decisions.vote.placeholder.proposal_info_url' => [
                'text' => 'https://...',
                'context' => 'Placeholder for one proposal info URL input.',
            ],
            'decisions.vote.action.create' => [
                'text' => 'Créer le scrutin',
                'context' => 'Submit label for a new simple vote.',
            ],
            'decisions.vote.action.save' => [
                'text' => 'Enregistrer le scrutin',
                'context' => 'Submit label for an existing simple vote.',
            ],
            'decisions.vote.action.saving' => [
                'text' => 'Enregistrement...',
                'context' => 'Temporary label while the simple vote form is saving.',
            ],
            'decisions.vote.action.proposal_apply' => [
                'text' => 'Enregistrer les details',
                'context' => 'Button label used to save proposal detail popup fields.',
            ],
            'decisions.vote.action.submit_response' => [
                'text' => 'Enregistrer mon vote',
                'context' => 'Submit label for a new vote response.',
            ],
            'decisions.vote.action.update_response' => [
                'text' => 'Mettre à jour mon vote',
                'context' => 'Submit label when updating an existing vote response.',
            ],
            'decisions.vote.action.submitting_response' => [
                'text' => 'Enregistrement du vote...',
                'context' => 'Temporary label while the vote response is saving.',
            ],
            'decisions.vote.feedback.success' => [
                'text' => 'Scrutin enregistré.',
                'context' => 'Generic success feedback after saving a simple vote.',
            ],
            'decisions.vote.feedback.error' => [
                'text' => 'Impossible d’enregistrer ce scrutin pour le moment.',
                'context' => 'Generic error feedback after saving a simple vote.',
            ],
            'decisions.vote.feedback.response_success' => [
                'text' => 'Vote enregistré.',
                'context' => 'Generic success feedback after saving a vote response.',
            ],
            'decisions.vote.feedback.response_error' => [
                'text' => 'Impossible d’enregistrer votre vote pour le moment.',
                'context' => 'Generic error feedback after saving a vote response.',
            ],
            'decisions.vote.empty_proposals' => [
                'text' => 'Aucune proposition active pour le moment.',
                'context' => 'Fallback text when a vote has no active proposal to display.',
            ],
            'decisions.vote.empty_results' => [
                'text' => 'Aucun vote n’a encore été enregistré pour ce scrutin.',
                'context' => 'Fallback text when no submitted vote exists yet.',
            ],
            'decisions.vote.drawer_title' => [
                'text' => 'Prises de décision',
                'context' => 'Drawer title reused after saving a simple vote.',
            ],
        ];
    }
}

if (!function_exists('omoDecisionVoteFormatDateTimeLocal')) {
    function omoDecisionVoteFormatDateTimeLocal($value)
    {
        if (!$value instanceof DateTimeInterface) {
            return '';
        }

        return $value->format('Y-m-d\TH:i');
    }
}

if (!function_exists('omoDecisionVoteModuleRender')) {
    function omoDecisionVoteModuleRender(array $renderContext)
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

        $voteConfig = $decisionGroup instanceof DecisionGroup
            ? omoDecisionVoteBuildConfig($decisionGroup)
            : omoDecisionVoteBuildConfig([]);
        $choiceMode = (string)$voteConfig['choice_mode'];
        $maxChoices = (int)$voteConfig['max_choices'];
        $isAnonymous = !empty($voteConfig['is_anonymous']);
        $allowAnonymousVotes = !empty($voteConfig['allow_anonymous_votes']);
        $allowConsultationProposals = !empty($voteConfig['allow_consultation_proposals']);
        $allowProposalDiscussions = !empty($voteConfig['allow_proposal_discussions']);
        $voteWeightEnabled = !empty($voteConfig['vote_weight_enabled']);
        $voteWeightQuestion = trim((string)($voteConfig['vote_weight_question'] ?? ''));
        $voteWeightOptions = is_array($voteConfig['vote_weight_options'] ?? null) ? array_values((array)$voteConfig['vote_weight_options']) : [];
        $voteWeightOptionsText = (string)($voteConfig['vote_weight_options_text'] ?? '');
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

        $selectedResponse = null;
        $selectedProposalIds = [];
        $selectedProposalId = 0;
        $selectedVoteWeight = '1';
        $selectedResponseIsAnonymous = false;
        $submittedVoteCount = 0;
        $weightedSubmittedVoteUnits = 0;
        $voteWeightScale = omoDecisionBlockSettingsGetVoteWeightScale(['options' => $voteWeightOptions]);
        $proposalVoteCounts = [];
        $proposalWeightedVoteUnits = [];
        $participant = $context['participant'] ?? null;
        if ($decision instanceof DecisionProcess && $participant && (int)$participant->getId() > 0) {
            $selectedResponse = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId(), $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0);
            $selectedProposalIds = omoDecisionVoteExtractSelectedProposalIds($selectedResponse);
            $selectedProposalId = count($selectedProposalIds) > 0 ? (int)$selectedProposalIds[0] : 0;
            $selectedVoteWeightSelection = omoDecisionVoteExtractVoteWeightSelection($selectedResponse, $voteConfig);
            $selectedVoteWeight = (string)($selectedVoteWeightSelection['weight'] ?? '1');
            $selectedResponseIsAnonymous = omoDecisionResponseIsAnonymous($selectedResponse, omoDecisionVoteGetMethodKey());
        }
        $anonymousVoteChecked = $isAnonymous || ($allowAnonymousVotes && $selectedResponseIsAnonymous);
        $anonymousVoteDisabled = $isAnonymous || !$allowAnonymousVotes;
        if ($decision instanceof DecisionProcess) {
            $voteTallies = omoDecisionVoteBuildTallies(
                $decisionGroup instanceof DecisionGroup ? $decisionGroup->getResponses(DecisionResponse::STATUS_SUBMITTED) : $decision->getResponses(DecisionResponse::STATUS_SUBMITTED),
                $voteConfig
            );
            $submittedVoteCount = (int)($voteTallies['unweighted_total_count'] ?? 0);
            $weightedSubmittedVoteUnits = (int)($voteTallies['weighted_total_units'] ?? 0);
            $voteWeightScale = max(1, (int)($voteTallies['scale'] ?? $voteWeightScale));
            $proposalVoteCounts = (array)($voteTallies['proposal_unweighted_counts'] ?? []);
            $proposalWeightedVoteUnits = (array)($voteTallies['proposal_weighted_units'] ?? []);
        }

        $resultProposalObjects = $proposalObjects;
        if ($resultsMode && count($resultProposalObjects) > 1) {
            $proposalOriginalOrder = [];
            foreach ($resultProposalObjects as $proposalIndex => $proposal) {
                $proposalId = ($proposal instanceof \dbObject\DecisionProposal) ? (int)$proposal->getId() : 0;
                $proposalOriginalOrder[$proposalId] = ($proposal instanceof \dbObject\DecisionProposal && (int)$proposal->get('position') > 0)
                    ? (int)$proposal->get('position')
                    : ($proposalIndex + 1);
            }

            usort($resultProposalObjects, function ($left, $right) use ($proposalVoteCounts, $proposalWeightedVoteUnits, $proposalOriginalOrder, $voteWeightEnabled) {
                $leftId = $left instanceof \dbObject\DecisionProposal ? (int)$left->getId() : 0;
                $rightId = $right instanceof \dbObject\DecisionProposal ? (int)$right->getId() : 0;
                $leftVotes = $voteWeightEnabled
                    ? (int)($proposalWeightedVoteUnits[$leftId] ?? 0)
                    : (int)($proposalVoteCounts[$leftId] ?? 0);
                $rightVotes = $voteWeightEnabled
                    ? (int)($proposalWeightedVoteUnits[$rightId] ?? 0)
                    : (int)($proposalVoteCounts[$rightId] ?? 0);

                if ($leftVotes !== $rightVotes) {
                    return $rightVotes <=> $leftVotes;
                }

                return (int)($proposalOriginalOrder[$leftId] ?? 0) <=> (int)($proposalOriginalOrder[$rightId] ?? 0);
            });
        }
        $showResultsSortSwitch = $resultsMode && count($proposalObjects) > 1;

        $headerTitleKey = 'decisions.vote.title';
        $headerDescriptionKey = 'decisions.vote.description';
        if ($isParticipateMode) {
            $headerTitleKey = 'decisions.vote.participate_title';
            $headerDescriptionKey = 'decisions.vote.participate_description';
        } elseif ($isViewMode) {
            $headerTitleKey = 'decisions.vote.view_title';
            $headerDescriptionKey = 'decisions.vote.view_description';
        }

        $managePayload = [
            'saveUrl' => '/omo/api/decision/modules/vote/save.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'manage'),
            'drawerTitle' => t('decisions.vote.drawer_title', [], $lang, $sourceLang),
            'proposalEditable' => $canEditProposals,
            'texts' => [
                'save' => $decision instanceof DecisionProcess
                    ? t('decisions.vote.action.save', [], $lang, $sourceLang)
                    : t('decisions.vote.action.create', [], $lang, $sourceLang),
                'saving' => t('decisions.vote.action.saving', [], $lang, $sourceLang),
                'success' => t('decisions.vote.feedback.success', [], $lang, $sourceLang),
                'error' => t('decisions.vote.feedback.error', [], $lang, $sourceLang),
                'proposalPlaceholder' => t('decisions.vote.placeholder.proposals', [], $lang, $sourceLang),
                'proposalInfoUrlPlaceholder' => t('decisions.vote.placeholder.proposal_info_url', [], $lang, $sourceLang),
                'proposalRemove' => t('decisions.vote.field.proposals_remove', [], $lang, $sourceLang),
                'proposalReorder' => t('decisions.vote.field.proposals_reorder', [], $lang, $sourceLang),
                'proposalDetails' => t('decisions.vote.field.proposal_details', [], $lang, $sourceLang),
                'proposalDescriptionLabel' => t('decisions.vote.field.proposal_description', [], $lang, $sourceLang),
                'proposalInfoUrlLabel' => t('decisions.vote.field.proposal_info_url', [], $lang, $sourceLang),
                'proposalApply' => t('decisions.vote.action.proposal_apply', [], $lang, $sourceLang),
                'proposalItemTemplate' => t('decisions.vote.field.proposals_item', ['index' => '__INDEX__'], $lang, $sourceLang),
            ],
        ];

        $responsePayload = [
            'saveUrl' => '/omo/api/decision/modules/vote/respond.php',
            'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'participate'),
            'drawerTitle' => t('decisions.vote.drawer_title', [], $lang, $sourceLang),
            'choiceMode' => $choiceMode,
            'maxChoices' => $maxChoices,
            'texts' => [
                'save' => $selectedResponse instanceof DecisionResponse
                    ? t('decisions.vote.action.update_response', [], $lang, $sourceLang)
                    : t('decisions.vote.action.submit_response', [], $lang, $sourceLang),
                'saving' => t('decisions.vote.action.submitting_response', [], $lang, $sourceLang),
                'success' => t('decisions.vote.feedback.response_success', [], $lang, $sourceLang),
                'error' => t('decisions.vote.feedback.response_error', [], $lang, $sourceLang),
                'multipleHint' => $maxChoices === 0
                    ? t('decisions.vote.field.multiple_hint_unlimited', [], $lang, $sourceLang)
                    : t('decisions.vote.field.multiple_hint', ['count' => (string)$maxChoices], $lang, $sourceLang),
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
            ? omoDecisionRenderConsultationProposalPublicPanel($decision, $context, $escape, 'omo-decision-vote__consultation-panel')
            : '';
        ?>
        <section class="generic-section generic-section--stack omo-decision-vote">
            <?= omoDecisionRenderVoteWeightEditorAssets() ?>
            <?= omoDecisionRenderProposalDiscussionAssets() ?>
            <?php if (!$publicLayout): ?>
            <div class="omo-decision-vote__head">
                <div class="omo-decision-vote__copy">
                    <h3 class="generic-card-title generic-card-title--section"><?= $escape(t($headerTitleKey, [], $lang, $sourceLang)) ?></h3>
                    <p class="omo-decision-vote__text"><?= $escape(t($headerDescriptionKey, [], $lang, $sourceLang)) ?></p>
                </div>

                <?php if (!$decision instanceof DecisionProcess): ?>
                <a
                    class="generic-action-button generic-action-button--secondary"
                    href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'])) ?>"
                    data-omo-decision-editor-link
                    data-omo-decision-editor-title="<?= $escape(t('decisions.edit.create_title', [], $lang, $sourceLang)) ?>"
                >
                    <?= $escape(t('decisions.vote.change_method', [], $lang, $sourceLang)) ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($resultsMode && !$publicLayout): ?>
            <div class="generic-soft-panel generic-soft-panel--stack omo-decision-vote__notice">
                <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.notice.results', [], $lang, $sourceLang)) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($isManageMode): ?>
                <?php if ($coreLocked): ?>
                <div class="generic-soft-panel generic-soft-panel--stack omo-decision-vote__notice">
                    <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.notice.started', [], $lang, $sourceLang)) ?></p>
                    <?php if ($canEditProposals && !$hasSubmittedResponses): ?>
                    <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.notice.consultation_proposals', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                    <?php if ($hasSubmittedResponses): ?>
                    <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.notice.responses', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form
                    class="omo-decision-vote__form generic-form-stack"
                    action="/omo/api/decision/modules/vote/save.php"
                    method="post"
                    data-omo-decision-vote-form
                >
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                    <input type="hidden" name="intent" value="manage">
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>
                    <input type="hidden" name="evaluation_method" value="<?= $escape(DecisionProcess::METHOD_SIMPLE_VOTE) ?>">

                    <div class="generic-card-title"><?= $escape(t('decisions.vote.field.process_section', [], $lang, $sourceLang)) ?></div>

                    <label class="omo-decision-vote__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.process_title', [], $lang, $sourceLang)) ?></span>
                        <input
                            type="text"
                            name="process_title"
                            class="generic-form-control"
                            required
                            maxlength="190"
                            value="<?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('title')) : '') ?>"
                            placeholder="<?= $escape(t('decisions.vote.placeholder.process_title', [], $lang, $sourceLang)) ?>"
                            <?= $canEditStructure ? '' : 'disabled' ?>
                        >
                    </label>

                    <label class="omo-decision-vote__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.process_description', [], $lang, $sourceLang)) ?></span>
                        <textarea
                            name="process_description"
                            class="generic-form-control omo-decision-vote__textarea"
                            rows="3"
                            placeholder="<?= $escape(t('decisions.vote.placeholder.process_description', [], $lang, $sourceLang)) ?>"
                            <?= $canEditStructure ? '' : 'disabled' ?>
                        ><?= $escape($decision instanceof DecisionProcess ? trim((string)$decision->get('description')) : '') ?></textarea>
                    </label>

                    <label class="omo-decision-vote__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.visibility.label', [], $lang, $sourceLang)) ?></span>
                        <select name="visibility_type" class="generic-form-control" <?= $canEditStructure ? '' : 'disabled' ?>>
                            <?php foreach (($visibilityState['visibilityOptions'] ?? array()) as $optionValue => $optionLabel): ?>
                            <option
                                value="<?= $escape($optionValue) ?>"
                                <?= $optionValue === ($visibilityState['selectedVisibilityType'] ?? DecisionProcess::getDefaultVisibilityType()) ? ' selected' : '' ?>
                                <?= !empty(($visibilityState['disabledVisibilityTypes'] ?? array())[$optionValue]) ? ' disabled' : '' ?>
                            ><?= $escape($optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (trim((string)($visibilityState['visibilityHelpText'] ?? '')) !== ''): ?>
                        <span class="omo-decision-vote__text"><?= $escape((string)$visibilityState['visibilityHelpText']) ?></span>
                        <?php endif; ?>
                    </label>

                    <div class="omo-decision-vote__grid">
                        <label class="omo-decision-vote__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.status', [], $lang, $sourceLang)) ?></span>
                            <select name="status" class="generic-form-control" <?= $isEditable ? '' : 'disabled' ?>>
                                <?php foreach ([
                                    DecisionProcess::STATUS_DRAFT => 'decisions.vote.option.status.draft',
                                    DecisionProcess::STATUS_SCHEDULED => 'decisions.vote.option.status.scheduled',
                                    DecisionProcess::STATUS_CONSULTATION => 'decisions.vote.option.status.consultation',
                                    DecisionProcess::STATUS_EVALUATION => 'decisions.vote.option.status.evaluation',
                                    DecisionProcess::STATUS_RESULTS => 'decisions.vote.option.status.results',
                                    DecisionProcess::STATUS_ARCHIVED => 'decisions.vote.option.status.archived',
                                ] as $statusKey => $statusLabel): ?>
                                <option value="<?= $escape($statusKey) ?>"<?= $status === $statusKey ? ' selected' : '' ?>><?= $escape(t($statusLabel, [], $lang, $sourceLang)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="omo-decision-vote__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.consultation_start', [], $lang, $sourceLang)) ?></span>
                            <input
                                type="datetime-local"
                                name="consultation_start_at"
                                class="generic-form-control"
                                value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionVoteFormatDateTimeLocal($decision->get('consultation_start_at')) : '') ?>"
                                <?= $canEditStartDates ? '' : 'disabled' ?>
                            >
                        </label>

                        <label class="omo-decision-vote__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.consultation_end', [], $lang, $sourceLang)) ?></span>
                            <input
                                type="datetime-local"
                                name="consultation_end_at"
                                class="generic-form-control"
                                value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionVoteFormatDateTimeLocal($decision->get('consultation_end_at')) : '') ?>"
                                <?= $isEditable ? '' : 'disabled' ?>
                            >
                        </label>

                        <label class="omo-decision-vote__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.evaluation_start', [], $lang, $sourceLang)) ?></span>
                            <input
                                type="datetime-local"
                                name="evaluation_start_at"
                                class="generic-form-control"
                                value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionVoteFormatDateTimeLocal($decision->get('evaluation_start_at')) : '') ?>"
                                <?= $canEditStartDates ? '' : 'disabled' ?>
                            >
                        </label>

                        <label class="omo-decision-vote__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.evaluation_end', [], $lang, $sourceLang)) ?></span>
                            <input
                                type="datetime-local"
                                name="evaluation_end_at"
                                class="generic-form-control"
                                value="<?= $escape($decision instanceof DecisionProcess ? omoDecisionVoteFormatDateTimeLocal($decision->get('evaluation_end_at')) : '') ?>"
                                <?= $isEditable ? '' : 'disabled' ?>
                            >
                        </label>
                    </div>

                    <?php if (function_exists('omoDecisionRenderEditorGroupSwitch')) {
                        omoDecisionRenderEditorGroupSwitch($context, $decision instanceof DecisionProcess ? $decision : null, $decisionGroup instanceof DecisionGroup ? $decisionGroup : null, $decision instanceof DecisionProcess ? $decision->getDecisionGroups(false) : [], $lang, $sourceLang, $escape);
                    } ?>

                    <div class="generic-card-title"><?= $escape(t('decisions.vote.field.group_section', [], $lang, $sourceLang)) ?></div>

                    <label class="omo-decision-vote__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.title', [], $lang, $sourceLang)) ?></span>
                        <input
                            type="text"
                            name="title"
                            class="generic-form-control"
                            required
                            maxlength="190"
                            value="<?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('title')) : '') ?>"
                            placeholder="<?= $escape(t('decisions.vote.placeholder.title', [], $lang, $sourceLang)) ?>"
                            <?= $canEditStructure ? '' : 'disabled' ?>
                        >
                    </label>

                    <label class="omo-decision-vote__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.description', [], $lang, $sourceLang)) ?></span>
                        <textarea
                            name="description"
                            class="generic-form-control omo-decision-vote__textarea"
                            rows="4"
                            placeholder="<?= $escape(t('decisions.vote.placeholder.description', [], $lang, $sourceLang)) ?>"
                            <?= $canEditStructure ? '' : 'disabled' ?>
                        ><?= $escape($decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('description')) : '') ?></textarea>
                    </label>

                    <div class="omo-decision-vote__grid">
                        <label class="omo-decision-vote__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.type', [], $lang, $sourceLang)) ?></span>
                            <select name="decision_type" class="generic-form-control" <?= $canEditStructure ? '' : 'disabled' ?>>
                                <option value="<?= $escape(DecisionProcess::TYPE_DECISION) ?>"<?= $decisionType === DecisionProcess::TYPE_DECISION ? ' selected' : '' ?>>
                                    <?= $escape(t('decisions.vote.option.type.decision', [], $lang, $sourceLang)) ?>
                                </option>
                                <option value="<?= $escape(DecisionProcess::TYPE_CONSULTATION) ?>"<?= $decisionType === DecisionProcess::TYPE_CONSULTATION ? ' selected' : '' ?>>
                                    <?= $escape(t('decisions.vote.option.type.consultation', [], $lang, $sourceLang)) ?>
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="generic-soft-panel generic-soft-panel--stack omo-decision-vote__settings-summary">
                        <?= omoDecisionRenderVoteWeightEditorAssets() ?>
                        <input type="hidden" name="choice_mode" value="<?= $escape($choiceMode) ?>" data-omo-decision-vote-hidden-choice-mode>
                        <input type="hidden" name="max_choices" value="<?= $escape((string)$maxChoices) ?>" data-omo-decision-vote-hidden-max-choices>
                        <input type="hidden" name="is_anonymous" value="<?= $isAnonymous ? '1' : '' ?>" data-omo-decision-vote-hidden-anonymous>
                        <input type="hidden" name="allow_anonymous_votes" value="<?= $allowAnonymousVotes ? '1' : '' ?>" data-omo-decision-vote-hidden-allow-anonymous-votes>
                        <input type="hidden" name="allow_consultation_proposals" value="<?= $allowConsultationProposals ? '1' : '' ?>" data-omo-decision-vote-hidden-consultation-proposals>
                        <input type="hidden" name="allow_proposal_discussions" value="<?= $allowProposalDiscussions ? '1' : '' ?>" data-omo-decision-vote-hidden-proposal-discussions>
                        <input type="hidden" name="vote_weight_enabled" value="<?= $voteWeightEnabled ? '1' : '' ?>" data-omo-decision-vote-hidden-vote-weight-enabled>
                        <input type="hidden" name="vote_weight_question" value="<?= $escape($voteWeightQuestion) ?>" data-omo-decision-vote-hidden-vote-weight-question>
                        <input
                            type="hidden"
                            name="vote_weight_options_json"
                            value="<?= $escape($voteWeightOptionsJson) ?>"
                            data-omo-decision-vote-hidden-vote-weight-options
                            data-default-options-json="<?= $escape($defaultVoteWeightOptionsJson) ?>"
                        >
                        <div class="omo-decision-vote__settings-head">
                            <div class="omo-decision-vote__field">
                                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.settings', [], $lang, $sourceLang)) ?></span>
                                <div class="omo-decision-vote__readonly-stats omo-decision-vote__readonly-stats--settings">
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.choice_mode', [], $lang, $sourceLang)) ?></strong>
                                        <span
                                            data-omo-decision-vote-choice-summary
                                            data-unlimited-label="<?= $escape(t('decisions.vote.field.max_choices_unlimited', [], $lang, $sourceLang)) ?>"
                                        ><?php
                                            if ($choiceMode === 'multiple') {
                                                $maxChoicesSummaryLabel = $maxChoices === 0
                                                    ? t('decisions.vote.field.max_choices_unlimited', [], $lang, $sourceLang)
                                                    : (string)$maxChoices;
                                                echo $escape(t('decisions.vote.option.choice_mode.multiple', [], $lang, $sourceLang) . ' (max ' . $maxChoicesSummaryLabel . ')');
                                            } else {
                                                echo $escape(t('decisions.vote.option.choice_mode.single', [], $lang, $sourceLang));
                                            }
                                        ?></span>
                                    </span>
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.anonymous', [], $lang, $sourceLang)) ?></strong>
                                        <span
                                            data-omo-decision-vote-anonymous-summary
                                            data-yes-label="<?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?>"
                                            data-no-label="<?= $escape(t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?>"
                                        ><?= $escape($isAnonymous ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.allow_anonymous_votes', [], $lang, $sourceLang)) ?></strong>
                                        <span data-omo-decision-vote-allow-anonymous-votes-summary data-yes-label="<?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?>" data-no-label="<?= $escape(t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?>"><?= $escape($allowAnonymousVotes ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></strong>
                                        <span
                                            data-omo-decision-vote-consultation-summary
                                            data-yes-label="<?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?>"
                                            data-no-label="<?= $escape(t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?>"
                                        ><?= $escape($allowConsultationProposals ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.allow_proposal_discussions', [], $lang, $sourceLang)) ?></strong>
                                        <span
                                            data-omo-decision-vote-discussions-summary
                                            data-yes-label="<?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?>"
                                            data-no-label="<?= $escape(t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?>"
                                        ><?= $escape($allowProposalDiscussions ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang)) ?></strong>
                                        <span
                                            data-omo-decision-vote-vote-weight-summary
                                            data-yes-label="<?= $escape(t('decisions.edit.block_settings.vote_weighting_summary_yes', [], $lang, $sourceLang)) ?>"
                                            data-no-label="<?= $escape(t('decisions.edit.block_settings.vote_weighting_summary_no', [], $lang, $sourceLang)) ?>"
                                        ><?= $escape($voteWeightSummaryText) ?></span>
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="generic-action-button generic-action-button--secondary"
                                data-omo-decision-vote-settings-open
                                data-omo-decision-vote-settings-title="<?= $escape(t('decisions.vote.field.settings', [], $lang, $sourceLang)) ?>"
                            >
                                <?= $escape(t('decisions.vote.field.settings', [], $lang, $sourceLang)) ?>
                            </button>
                        </div>
                        <template data-omo-decision-vote-settings-template>
                            <div class="omo-decision-settings-popup omo-decision-vote-popup generic-section generic-section--stack" data-topbar-modal-max-width="760px">
                                <div class="omo-decision-vote-popup__stack">
                                    <div class="generic-soft-panel generic-soft-panel--stack">
                                        <div class="omo-decision-vote-popup__choice-grid">
                                            <label class="omo-decision-vote-popup__field">
                                                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.choice_mode', [], $lang, $sourceLang)) ?></span>
                                                <select class="generic-form-control" data-omo-decision-vote-popup-choice-mode <?= $canEditStructure ? '' : 'disabled' ?>>
                                                    <option value="single"><?= $escape(t('decisions.vote.option.choice_mode.single', [], $lang, $sourceLang)) ?></option>
                                                    <option value="multiple"><?= $escape(t('decisions.vote.option.choice_mode.multiple', [], $lang, $sourceLang)) ?></option>
                                                </select>
                                            </label>

                                            <label class="omo-decision-vote-popup__field" data-omo-decision-vote-popup-max-choices-field hidden>
                                                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.max_choices', [], $lang, $sourceLang)) ?></span>
                                                <input type="number" min="0" class="generic-form-control" data-omo-decision-vote-popup-max-choices <?= $canEditStructure ? '' : 'disabled' ?>>
                                            </label>
                                        </div>
                                    </div>

                                    <label class="omo-decision-vote-popup__field omo-decision-vote-popup__field--checkbox">
                                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.anonymous', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-vote__check-row">
                                            <input type="checkbox" value="1" data-omo-decision-vote-popup-anonymous <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?></span>
                                        </span>
                                    </label>

                                    <label class="omo-decision-vote-popup__field omo-decision-vote-popup__field--checkbox">
                                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.allow_anonymous_votes', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-vote__check-row">
                                            <input type="checkbox" value="1" data-omo-decision-vote-popup-allow-anonymous-votes <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?></span>
                                        </span>
                                    </label>

                                    <label class="omo-decision-vote-popup__field omo-decision-vote-popup__field--checkbox">
                                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.allow_consultation_proposals', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-vote__check-row">
                                            <input type="checkbox" value="1" data-omo-decision-vote-popup-consultation-proposals <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?></span>
                                        </span>
                                    </label>

                                    <label class="omo-decision-vote-popup__field omo-decision-vote-popup__field--checkbox">
                                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.allow_proposal_discussions', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-vote__check-row">
                                            <input type="checkbox" value="1" data-omo-decision-vote-popup-proposal-discussions <?= $canEditStructure ? '' : 'disabled' ?>>
                                            <span><?= $escape(t('decisions.vote.option.common.yes', [], $lang, $sourceLang)) ?></span>
                                        </span>
                                    </label>

                                    <?= omoDecisionRenderVoteWeightEditor($lang, $sourceLang, $escape, [
                                        'canEdit' => $canEditStructure,
                                        'enabled' => $voteWeightEnabled,
                                        'question' => $voteWeightQuestion,
                                        'options' => $voteWeightOptions,
                                    ]) ?>
                                </div>
                                <div class="omo-decision-settings-popup__actions omo-decision-vote-popup__actions">
                                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-vote-popup-cancel>Fermer</button>
                                    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-vote-popup-apply <?= $canEditStructure ? '' : 'disabled' ?>>Appliquer</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <label class="omo-decision-vote__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.proposals', [], $lang, $sourceLang)) ?></span>
                        <div class="omo-decision-vote__proposal-list" data-omo-decision-vote-proposal-list>
                            <?php foreach ($proposalItems as $proposalIndex => $proposalItem): ?>
                            <div
                                class="omo-decision-vote__proposal-card omo-decision-proposal-card generic-section<?= $canEditProposals ? '' : ' omo-decision-vote__proposal-card--locked omo-decision-proposal-card--locked' ?>"
                                data-omo-decision-vote-proposal-card
                                draggable="<?= $canEditProposals ? 'true' : 'false' ?>"
                            >
                                <?php if ($canEditProposals): ?>
                                <button
                                    type="button"
                                    class="omo-decision-vote__proposal-drag"
                                    data-omo-decision-vote-proposal-drag
                                    title="<?= $escape(t('decisions.vote.field.proposals_reorder', [], $lang, $sourceLang)) ?>"
                                    aria-label="<?= $escape(t('decisions.vote.field.proposals_reorder', [], $lang, $sourceLang)) ?>"
                                >&#8942;&#8942;</button>
                                <?php endif; ?>

                                <div class="omo-decision-vote__proposal-main">
                                    <span class="omo-decision-vote__proposal-label" data-omo-decision-vote-proposal-label>
                                        <?= $escape(str_replace('{index}', (string)($proposalIndex + 1), t('decisions.vote.field.proposals_item', ['index' => (string)($proposalIndex + 1)], $lang, $sourceLang))) ?>
                                    </span>
                                    <input
                                        type="text"
                                        name="proposals[]"
                                        class="generic-form-control"
                                        value="<?= $escape((string)$proposalItem['title']) ?>"
                                        placeholder="<?= $escape(t('decisions.vote.placeholder.proposals', [], $lang, $sourceLang)) ?>"
                                        <?= $canEditProposals ? '' : 'disabled' ?>
                                    >
                                    <input type="hidden" name="proposal_descriptions[]" value="<?= $escape((string)($proposalItem['description'] ?? '')) ?>" data-omo-decision-vote-proposal-description>
                                    <input type="hidden" name="proposal_info_urls[]" value="<?= $escape((string)($proposalItem['info_url'] ?? '')) ?>" data-omo-decision-vote-proposal-info-url>
                                    <input type="hidden" name="proposal_ids[]" value="<?= $escape((int)($proposalItem['id'] ?? 0)) ?>">
                                </div>

                                <div class="omo-decision-vote__proposal-menu" data-omo-decision-vote-proposal-menu>
                                    <button
                                        type="button"
                                        class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-menu-toggle"
                                        data-omo-decision-vote-proposal-menu-toggle
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        aria-label="Actions"
                                    >...</button>
                                    <div class="omo-decision-vote__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel" data-omo-decision-vote-proposal-menu-panel role="menu" hidden>
                                        <button
                                            type="button"
                                            class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-menu-item"
                                            data-omo-decision-vote-proposal-settings
                                            role="menuitem"
                                        >
                                            <?= $escape(t('decisions.vote.field.proposal_details', [], $lang, $sourceLang)) ?>
                                        </button>
                                        <?php if ($canEditProposals): ?>
                                        <button
                                            type="button"
                                            class="generic-action-button generic-action-button--danger omo-decision-vote__proposal-menu-item"
                                            data-omo-decision-vote-proposal-remove
                                            role="menuitem"
                                        >
                                            <?= $escape(t('decisions.vote.field.proposals_remove', [], $lang, $sourceLang)) ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-add"
                            data-omo-decision-vote-proposal-add
                            <?= $canEditProposals ? '' : 'disabled' ?>
                        >
                            <?= $escape(t('decisions.vote.field.proposals_add', [], $lang, $sourceLang)) ?>
                        </button>
                        <small class="omo-decision-vote__hint"><?= $escape(t('decisions.vote.field.proposals_hint', [], $lang, $sourceLang)) ?></small>
                    </label>

                    <?= omoDecisionRenderInvitationSection($decision, $context, $lang, $sourceLang, $escape, 'omo-decision-vote__invitation-summary') ?>

                    <?php if ($isEditable): ?>
                    <div class="omo-decision-vote__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-vote-submit>
                            <?= $escape($decision instanceof DecisionProcess ? t('decisions.vote.action.save', [], $lang, $sourceLang) : t('decisions.vote.action.create', [], $lang, $sourceLang)) ?>
                        </button>
                        <div class="omo-decision-vote__feedback" data-omo-decision-vote-feedback aria-live="polite"></div>
                    </div>
                    <?php endif; ?>

                    <script type="application/json" data-omo-decision-vote-data><?= $managePayloadJson ?></script>
                </form>
            <?php else: ?>
                <?php if (!$publicLayout): ?>
                <div class="omo-decision-vote__summary-grid">
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.status', [], $lang, $sourceLang), t('decisions.vote.option.status.' . $status, [], $lang, $sourceLang), $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.type', [], $lang, $sourceLang), $decisionType === DecisionProcess::TYPE_CONSULTATION ? t('decisions.vote.option.type.consultation', [], $lang, $sourceLang) : t('decisions.vote.option.type.decision', [], $lang, $sourceLang), $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.choice_mode', [], $lang, $sourceLang), $choiceMode === 'multiple' ? t('decisions.vote.option.choice_mode.multiple', [], $lang, $sourceLang) : t('decisions.vote.option.choice_mode.single', [], $lang, $sourceLang), $escape, 'omo-decision-vote__meta-card') ?>
                    <?php if ($choiceMode === 'multiple'): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(
                        t('decisions.vote.field.max_choices', [], $lang, $sourceLang),
                        $maxChoices === 0 ? t('decisions.vote.field.max_choices_unlimited', [], $lang, $sourceLang) : (string)$maxChoices,
                        $escape,
                        'omo-decision-vote__meta-card'
                    ) ?>
                    <?php endif; ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.anonymous', [], $lang, $sourceLang), $isAnonymous ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.allow_consultation_proposals', [], $lang, $sourceLang), $allowConsultationProposals ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.allow_proposal_discussions', [], $lang, $sourceLang), $allowProposalDiscussions ? t('decisions.vote.option.common.yes', [], $lang, $sourceLang) : t('decisions.vote.option.common.no', [], $lang, $sourceLang), $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang), $voteWeightSummaryText, $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.consultation_start', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionVoteFormatDateTimeLocal($decision->get('consultation_start_at')) : '', $escape, 'omo-decision-vote__meta-card') ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.evaluation_end', [], $lang, $sourceLang), $decision instanceof DecisionProcess ? omoDecisionVoteFormatDateTimeLocal($decision->get('evaluation_end_at')) : '', $escape, 'omo-decision-vote__meta-card') ?>
                    <?php if ($resultsMode): ?>
                    <?= omoDecisionModuleRenderReadonlyMeta(t('decisions.vote.field.total_votes', [], $lang, $sourceLang), (string)$submittedVoteCount, $escape, 'omo-decision-vote__meta-card') ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$publicLayout && $decision instanceof DecisionProcess && trim((string)$decision->get('description')) !== ''): ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.description', [], $lang, $sourceLang)) ?></span>
                    <p class="omo-decision-vote__text"><?= nl2br($escape(trim((string)$decision->get('description')))) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($isParticipateMode): ?>
                <form
                    class="omo-decision-vote__form generic-form-stack"
                    action="/omo/api/decision/modules/vote/respond.php"
                    method="post"
                    data-omo-decision-vote-response-form
                >
                    <input type="hidden" name="oid" value="<?= $escape((int)$context['organizationId']) ?>">
                    <input type="hidden" name="cid" value="<?= $escape((int)$context['targetHolonId']) ?>">
                    <input type="hidden" name="id" value="<?= $escape($decision instanceof DecisionProcess ? (int)$decision->getId() : 0) ?>">
                    <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                    <input type="hidden" name="method" value="<?= $escape(DecisionProcess::METHOD_SIMPLE_VOTE) ?>">
                    <input type="hidden" name="intent" value="participate">
                    <?= omoDecisionRenderPublicTokenInput($context, $escape) ?>
                    <input type="hidden" name="choice_mode" value="<?= $escape($choiceMode) ?>">

                    <fieldset class="omo-decision-vote__fieldset">
                        <legend class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.your_choice', [], $lang, $sourceLang)) ?></legend>
                        <?php if ($choiceMode === 'multiple'): ?>
                        <p class="omo-decision-vote__text"><?= $escape($maxChoices === 0
                            ? t('decisions.vote.field.multiple_hint_unlimited', [], $lang, $sourceLang)
                            : str_replace('{count}', (string)$maxChoices, t('decisions.vote.field.multiple_hint', ['count' => (string)$maxChoices], $lang, $sourceLang))) ?></p>
                        <?php endif; ?>

                        <?php if (count($proposalObjects) === 0): ?>
                        <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.empty_proposals', [], $lang, $sourceLang)) ?></p>
                        <?php else: ?>
                            <?php foreach ($proposalObjects as $proposal): ?>
                            <label class="omo-decision-vote__option generic-section generic-section--stack">
                                <input
                                    type="<?= $choiceMode === 'multiple' ? 'checkbox' : 'radio' ?>"
                                    name="<?= $choiceMode === 'multiple' ? 'proposal_ids[]' : 'proposal_id' ?>"
                                    value="<?= $escape((int)$proposal->getId()) ?>"
                                    <?= in_array((int)$proposal->getId(), $selectedProposalIds, true) ? 'checked' : '' ?>
                                    <?= $choiceMode === 'single' ? 'required' : '' ?>
                                >
                                <span>
                                    <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                    <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-vote__text', 'omo-decision-vote__link') ?>
                                    <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </fieldset>
                    <?= omoDecisionRenderVoteWeightResponseSelector($lang, $sourceLang, $escape, [
                        'enabled' => $voteWeightEnabled,
                        'question' => $voteWeightQuestion,
                        'options' => $voteWeightOptions,
                        'selected_weight' => $selectedVoteWeight,
                    ]) ?>
                    <label class="omo-decision-vote__check-row">
                        <input type="checkbox" name="is_anonymous" value="1"<?= $anonymousVoteChecked ? ' checked' : '' ?><?= $anonymousVoteDisabled ? ' disabled' : '' ?>>
                        <span><?= $escape(t('decisions.vote.field.anonymous', [], $lang, $sourceLang)) ?></span>
                    </label>
                    <?php if ($consultationProposalPanel !== ''): ?>
                    <?= $consultationProposalPanel ?>
                    <?php endif; ?>

                    <div class="omo-decision-vote__footer">
                        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-vote-response-submit>
                            <?= $escape($selectedResponse instanceof DecisionResponse ? t('decisions.vote.action.update_response', [], $lang, $sourceLang) : t('decisions.vote.action.submit_response', [], $lang, $sourceLang)) ?>
                        </button>
                        <div class="omo-decision-vote__feedback" data-omo-decision-vote-response-feedback aria-live="polite"></div>
                    </div>

                    <script type="application/json" data-omo-decision-vote-response-data><?= $responsePayloadJson ?></script>
                </form>
                <?php else: ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.vote.field.proposals', [], $lang, $sourceLang)) ?></span>
                    <?php if (count($proposalObjects) === 0): ?>
                    <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.empty_proposals', [], $lang, $sourceLang)) ?></p>
                    <?php elseif ($resultsMode && $submittedVoteCount === 0): ?>
                    <p class="omo-decision-vote__text"><?= $escape(t('decisions.vote.empty_results', [], $lang, $sourceLang)) ?></p>
                    <div class="omo-decision-vote__results-panel" data-omo-decision-vote-results-panel>
                        <?php if ($showResultsSortSwitch): ?>
                        <div class="omo-decision-vote__results-controls omo-panel-controls">
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(t('decisions.vote.results_sort.aria', [], $lang, $sourceLang)) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-decision-vote-results-sort="rank" aria-pressed="true"><?= $escape(t('decisions.vote.results_sort.rank', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decision-vote-results-sort="initial" aria-pressed="false"><?= $escape(t('decisions.vote.results_sort.initial', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decision-vote-results-sort="alpha" aria-pressed="false"><?= $escape(t('decisions.vote.results_sort.alpha', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                    <div class="omo-decision-vote__readonly-list" data-omo-decision-vote-results-list>
                        <?php foreach ($resultProposalObjects as $proposal): ?>
                        <?php
                        $proposalId = (int)$proposal->getId();
                        $proposalPosition = (int)$proposal->get('position') > 0 ? (int)$proposal->get('position') : 0;
                        $proposalVoteShare = 0;
                        $proposalVoteShareRatio = 0;
                        $proposalVoteShareLabel = '0';
                        ?>
                        <div
                            class="omo-decision-vote__readonly-item generic-section generic-section--stack<?= in_array($proposalId, $selectedProposalIds, true) ? ' is-selected' : '' ?>"
                            data-omo-decision-vote-result-item
                            data-omo-decision-vote-result-position="<?= $escape((string)$proposalPosition) ?>"
                            data-omo-decision-vote-result-title="<?= $escape(trim((string)$proposal->get('title'))) ?>"
                            data-omo-decision-vote-result-votes="0"
                        >
                            <div>
                                <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-vote__text', 'omo-decision-vote__link') ?>
                                <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
                            </div>
                            <div class="omo-decision-vote__readonly-stats">
                                <span class="omo-decision-vote__readonly-stat">
                                    <strong><?= $escape(t('decisions.vote.field.proposal_votes', [], $lang, $sourceLang)) ?></strong>
                                    <span>0</span>
                                </span>
                                <span class="omo-decision-vote__readonly-stat">
                                    <strong><?= $escape(t('decisions.vote.field.proposal_share', [], $lang, $sourceLang)) ?></strong>
                                    <span><?= $escape($proposalVoteShareLabel) ?>%</span>
                                </span>
                            </div>
                            <div class="omo-decision-vote__distribution" aria-label="<?= $escape(t('decisions.vote.field.distribution', [], $lang, $sourceLang)) ?>">
                                <span class="omo-decision-vote__distribution-track">
                                    <span
                                        class="omo-decision-vote__distribution-fill"
                                        style="width: <?= $escape(number_format((float)$proposalVoteShare, 4, '.', '')) ?>%; --omo-decision-vote-share-ratio: <?= $escape(number_format((float)$proposalVoteShareRatio, 6, '.', '')) ?>;"
                                    ></span>
                                </span>
                            </div>
                            <div class="omo-decision-vote__distribution-scale" aria-hidden="true">
                                <span>0%</span>
                                <span>100%</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                    <?php else: ?>
                    <div class="omo-decision-vote__results-panel" data-omo-decision-vote-results-panel>
                        <?php if ($showResultsSortSwitch): ?>
                        <div class="omo-decision-vote__results-controls omo-panel-controls">
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(t('decisions.vote.results_sort.aria', [], $lang, $sourceLang)) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-decision-vote-results-sort="rank" aria-pressed="true"><?= $escape(t('decisions.vote.results_sort.rank', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decision-vote-results-sort="initial" aria-pressed="false"><?= $escape(t('decisions.vote.results_sort.initial', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decision-vote-results-sort="alpha" aria-pressed="false"><?= $escape(t('decisions.vote.results_sort.alpha', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($voteWeightEnabled): ?>
                        <label class="omo-decision-vote__results-compare">
                            <input type="checkbox" data-omo-decision-vote-results-compare-toggle>
                            <span><?= $escape(t('decisions.vote.results_compare.toggle', [], $lang, $sourceLang)) ?></span>
                        </label>
                        <?php endif; ?>
                    <div class="omo-decision-vote__readonly-list" data-omo-decision-vote-results-list>
                        <?php foreach ($resultProposalObjects as $proposal): ?>
                        <?php
                        $proposalId = (int)$proposal->getId();
                        $proposalPosition = (int)$proposal->get('position') > 0 ? (int)$proposal->get('position') : 0;
                        $proposalVoteCount = (int)($proposalVoteCounts[$proposalId] ?? 0);
                        $proposalVoteShare = $submittedVoteCount > 0
                            ? round(($proposalVoteCount / $submittedVoteCount) * 100, 1)
                            : 0;
                        $proposalWeightedVoteUnit = (int)($proposalWeightedVoteUnits[$proposalId] ?? 0);
                        $proposalWeightedVoteValue = omoDecisionBlockSettingsVoteWeightUnitsToValue($proposalWeightedVoteUnit, $voteWeightScale);
                        $proposalWeightedVoteShare = $weightedSubmittedVoteUnits > 0
                            ? round(($proposalWeightedVoteUnit / $weightedSubmittedVoteUnits) * 100, 1)
                            : 0;
                        $primaryVoteValue = $voteWeightEnabled ? $proposalWeightedVoteValue : (string)$proposalVoteCount;
                        $primaryVoteShare = $voteWeightEnabled ? $proposalWeightedVoteShare : $proposalVoteShare;
                        $primaryVoteShareRatio = max(0, min(1, $primaryVoteShare / 100));
                        $primaryVoteShareLabel = number_format($primaryVoteShare, $primaryVoteShare === floor($primaryVoteShare) ? 0 : 1, '.', '');
                        $proposalVoteShareRatio = max(0, min(1, $proposalVoteShare / 100));
                        $proposalVoteShareLabel = number_format($proposalVoteShare, $proposalVoteShare === floor($proposalVoteShare) ? 0 : 1, '.', '');
                        ?>
                        <div
                            class="omo-decision-vote__readonly-item generic-section generic-section--stack<?= in_array($proposalId, $selectedProposalIds, true) ? ' is-selected' : '' ?>"
                            data-omo-decision-vote-result-item
                            data-omo-decision-vote-result-position="<?= $escape((string)$proposalPosition) ?>"
                            data-omo-decision-vote-result-title="<?= $escape(trim((string)$proposal->get('title'))) ?>"
                            data-omo-decision-vote-result-votes="<?= $escape($voteWeightEnabled ? (string)$proposalWeightedVoteUnit : (string)$proposalVoteCount) ?>"
                        >
                            <div>
                                <strong><?= $escape(trim((string)$proposal->get('title'))) ?></strong>
                                <?= omoDecisionRenderProposalSupplementHtml($proposal->get('description'), $proposal->get('info_url'), $escape, 'omo-decision-vote__text', 'omo-decision-vote__link') ?>
                                <?= omoDecisionRenderProposalDiscussionActions($proposal, $context, $escape) ?>
                            </div>
                            <?php if ($resultsMode): ?>
                            <div class="omo-decision-vote__readonly-stats">
                                <span class="omo-decision-vote__readonly-stat">
                                    <strong><?= $escape(t('decisions.vote.field.proposal_votes', [], $lang, $sourceLang)) ?></strong>
                                    <span><?= $escape((string)$primaryVoteValue) ?></span>
                                </span>
                                <span class="omo-decision-vote__readonly-stat">
                                    <strong><?= $escape(t('decisions.vote.field.proposal_share', [], $lang, $sourceLang)) ?></strong>
                                    <span><?= $escape($primaryVoteShareLabel) ?>%</span>
                                </span>
                            </div>
                            <div class="omo-decision-vote__distribution" aria-label="<?= $escape(t('decisions.vote.field.distribution', [], $lang, $sourceLang)) ?>">
                                <span class="omo-decision-vote__distribution-track">
                                    <span
                                        class="omo-decision-vote__distribution-fill"
                                        style="width: <?= $escape(number_format((float)$primaryVoteShare, 4, '.', '')) ?>%; --omo-decision-vote-share-ratio: <?= $escape(number_format((float)$primaryVoteShareRatio, 6, '.', '')) ?>;"
                                    ></span>
                                </span>
                            </div>
                            <div class="omo-decision-vote__distribution-scale" aria-hidden="true">
                                <span>0%</span>
                                <span>100%</span>
                            </div>
                            <?php if ($voteWeightEnabled): ?>
                            <div class="omo-decision-vote__comparison" data-omo-decision-vote-results-compare-block hidden>
                                <span class="omo-decision-vote__comparison-title"><?= $escape(t('decisions.vote.results_compare.unweighted', [], $lang, $sourceLang)) ?></span>
                                <div class="omo-decision-vote__readonly-stats">
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.proposal_votes', [], $lang, $sourceLang)) ?></strong>
                                        <span><?= $escape((string)$proposalVoteCount) ?></span>
                                    </span>
                                    <span class="omo-decision-vote__readonly-stat">
                                        <strong><?= $escape(t('decisions.vote.field.proposal_share', [], $lang, $sourceLang)) ?></strong>
                                        <span><?= $escape($proposalVoteShareLabel) ?>%</span>
                                    </span>
                                </div>
                                <div class="omo-decision-vote__distribution" aria-label="<?= $escape(t('decisions.vote.field.distribution', [], $lang, $sourceLang)) ?>">
                                    <span class="omo-decision-vote__distribution-track">
                                        <span
                                            class="omo-decision-vote__distribution-fill omo-decision-vote__distribution-fill--secondary"
                                            style="width: <?= $escape(number_format((float)$proposalVoteShare, 4, '.', '')) ?>%; --omo-decision-vote-share-ratio: <?= $escape(number_format((float)$proposalVoteShareRatio, 6, '.', '')) ?>;"
                                        ></span>
                                    </span>
                                </div>
                                <div class="omo-decision-vote__distribution-scale" aria-hidden="true">
                                    <span>0%</span>
                                    <span>100%</span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($consultationProposalPanel !== ''): ?>
                    <?= $consultationProposalPanel ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <script>
        (function () {
            if (typeof window.omoDecisionVoteInit !== 'function') {
                window.omoDecisionVoteInit = function (root) {
                    const scope = root instanceof Element ? root : document;

                    scope.querySelectorAll('[data-omo-decision-vote-form]').forEach(function (form) {
                        if (form.dataset.omoDecisionVoteReady === '1') {
                            return;
                        }

                        const payloadNode = form.querySelector('[data-omo-decision-vote-data]');
                        const submitButton = form.querySelector('[data-omo-decision-vote-submit]');
                        const feedbackNode = form.querySelector('[data-omo-decision-vote-feedback]');
                        const proposalList = form.querySelector('[data-omo-decision-vote-proposal-list]');
                        const proposalAddButton = form.querySelector('[data-omo-decision-vote-proposal-add]');
                        const settingsOpenButton = form.querySelector('[data-omo-decision-vote-settings-open]');
                        const invitationOpenButton = form.querySelector('[data-omo-decision-invitations-open]');
                        const invitationSendOpenButton = form.querySelector('[data-omo-decision-invitations-send-open]');
                        const settingsTemplate = form.querySelector('[data-omo-decision-vote-settings-template]');
                        const hiddenChoiceModeInput = form.querySelector('[data-omo-decision-vote-hidden-choice-mode]');
                        const hiddenMaxChoicesInput = form.querySelector('[data-omo-decision-vote-hidden-max-choices]');
                        const hiddenAnonymousInput = form.querySelector('[data-omo-decision-vote-hidden-anonymous]');
                        const hiddenAllowAnonymousVotesInput = form.querySelector('[data-omo-decision-vote-hidden-allow-anonymous-votes]');
                        const hiddenConsultationProposalsInput = form.querySelector('[data-omo-decision-vote-hidden-consultation-proposals]');
                        const hiddenProposalDiscussionsInput = form.querySelector('[data-omo-decision-vote-hidden-proposal-discussions]');
                        const hiddenVoteWeightEnabledInput = form.querySelector('[data-omo-decision-vote-hidden-vote-weight-enabled]');
                        const hiddenVoteWeightQuestionInput = form.querySelector('[data-omo-decision-vote-hidden-vote-weight-question]');
                        const hiddenVoteWeightOptionsInput = form.querySelector('[data-omo-decision-vote-hidden-vote-weight-options]');
                        const choiceSummary = form.querySelector('[data-omo-decision-vote-choice-summary]');
                        const anonymousSummary = form.querySelector('[data-omo-decision-vote-anonymous-summary]');
                        const allowAnonymousVotesSummary = form.querySelector('[data-omo-decision-vote-allow-anonymous-votes-summary]');
                        const consultationSummary = form.querySelector('[data-omo-decision-vote-consultation-summary]');
                        const discussionsSummary = form.querySelector('[data-omo-decision-vote-discussions-summary]');
                        const voteWeightSummary = form.querySelector('[data-omo-decision-vote-vote-weight-summary]');

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

                        const clearFeedback = function () {
                            setFeedback('', false);
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

                        const syncChoiceModeFields = function () {
                            const choiceModeValue = String(hiddenChoiceModeInput && hiddenChoiceModeInput.value ? hiddenChoiceModeInput.value : 'single');
                            const isMultiple = choiceModeValue === 'multiple';
                            if (choiceSummary) {
                                if (isMultiple) {
                                    const unlimitedLabel = String(choiceSummary.getAttribute('data-unlimited-label') || 'Sans limite');
                                    const maxChoicesLabel = String(hiddenMaxChoicesInput && hiddenMaxChoicesInput.value ? hiddenMaxChoicesInput.value : '0') === '0'
                                        ? unlimitedLabel
                                        : String(hiddenMaxChoicesInput && hiddenMaxChoicesInput.value ? hiddenMaxChoicesInput.value : '1');
                                    choiceSummary.textContent = 'Plusieurs reponses (max ' + maxChoicesLabel + ')';
                                } else {
                                    choiceSummary.textContent = 'Une seule reponse';
                                }
                            }
                        };

                        const syncSettingsSummary = function () {
                            syncChoiceModeFields();
                            if (anonymousSummary && hiddenAnonymousInput) {
                                const yesLabel = String(anonymousSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(anonymousSummary.getAttribute('data-no-label') || 'Non');
                                anonymousSummary.textContent = hiddenAnonymousInput.value
                                    ? yesLabel
                                    : noLabel;
                            }
                            if (allowAnonymousVotesSummary && hiddenAllowAnonymousVotesInput) {
                                const yesLabel = String(allowAnonymousVotesSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(allowAnonymousVotesSummary.getAttribute('data-no-label') || 'Non');
                                allowAnonymousVotesSummary.textContent = hiddenAllowAnonymousVotesInput.value ? yesLabel : noLabel;
                            }
                            if (consultationSummary && hiddenConsultationProposalsInput) {
                                const yesLabel = String(consultationSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(consultationSummary.getAttribute('data-no-label') || 'Non');
                                consultationSummary.textContent = hiddenConsultationProposalsInput.value
                                    ? yesLabel
                                    : noLabel;
                            }
                            if (discussionsSummary && hiddenProposalDiscussionsInput) {
                                const yesLabel = String(discussionsSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(discussionsSummary.getAttribute('data-no-label') || 'Non');
                                discussionsSummary.textContent = hiddenProposalDiscussionsInput.value
                                    ? yesLabel
                                    : noLabel;
                            }
                            if (voteWeightSummary) {
                                voteWeightSummary.textContent = buildVoteWeightSummaryText();
                            }
                        };

                        const openSettingsModal = function () {
                            if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                                return;
                            }

                            const modalTitle = settingsOpenButton
                                ? String(settingsOpenButton.getAttribute('data-omo-decision-vote-settings-title') || settingsOpenButton.textContent || 'Parametres du vote')
                                : 'Parametres du vote';
                            window.commonTopbarOpenModal(modalTitle, settingsTemplate.innerHTML, 'html');
                            const modalBody = document.getElementById('commonTopbarModalBody');
                            if (!modalBody) {
                                return;
                            }

                            const popupChoiceMode = modalBody.querySelector('[data-omo-decision-vote-popup-choice-mode]');
                            const popupMaxChoicesField = modalBody.querySelector('[data-omo-decision-vote-popup-max-choices-field]');
                            const popupMaxChoices = modalBody.querySelector('[data-omo-decision-vote-popup-max-choices]');
                            const popupAnonymous = modalBody.querySelector('[data-omo-decision-vote-popup-anonymous]');
                            const popupAllowAnonymousVotes = modalBody.querySelector('[data-omo-decision-vote-popup-allow-anonymous-votes]');
                            const popupConsultationProposals = modalBody.querySelector('[data-omo-decision-vote-popup-consultation-proposals]');
                            const popupProposalDiscussions = modalBody.querySelector('[data-omo-decision-vote-popup-proposal-discussions]');
                            const popupVoteWeightRoot = modalBody.querySelector('[data-omo-decision-vote-weight-editor]');
                            const popupCancel = modalBody.querySelector('[data-omo-decision-vote-popup-cancel]');
                            const popupApply = modalBody.querySelector('[data-omo-decision-vote-popup-apply]');
                            const popupVoteWeightEditor = popupVoteWeightRoot && typeof window.omoDecisionInitVoteWeightEditor === 'function'
                                ? window.omoDecisionInitVoteWeightEditor(popupVoteWeightRoot)
                                : null;

                            if (!popupChoiceMode || !popupMaxChoices || !popupAnonymous || !popupAllowAnonymousVotes || !popupConsultationProposals || !popupProposalDiscussions || !popupVoteWeightEditor || !popupApply) {
                                return;
                            }

                            popupChoiceMode.value = String(hiddenChoiceModeInput && hiddenChoiceModeInput.value ? hiddenChoiceModeInput.value : 'single');
                            popupMaxChoices.value = String(hiddenMaxChoicesInput && hiddenMaxChoicesInput.value ? hiddenMaxChoicesInput.value : '1');
                            popupAnonymous.checked = !!(hiddenAnonymousInput && hiddenAnonymousInput.value);
                            popupAllowAnonymousVotes.checked = !!(hiddenAllowAnonymousVotesInput && hiddenAllowAnonymousVotesInput.value);
                            popupConsultationProposals.checked = !!(hiddenConsultationProposalsInput && hiddenConsultationProposalsInput.value);
                            popupProposalDiscussions.checked = !!(hiddenProposalDiscussionsInput && hiddenProposalDiscussionsInput.value);
                            popupVoteWeightEditor.setState({
                                enabled: !!(hiddenVoteWeightEnabledInput && hiddenVoteWeightEnabledInput.value),
                                question: hiddenVoteWeightQuestionInput ? String(hiddenVoteWeightQuestionInput.value || '') : '',
                                options: parseVoteWeightOptions(hiddenVoteWeightOptionsInput ? hiddenVoteWeightOptionsInput.value : '[]', false),
                            });

                            const syncPopup = function () {
                                const isMultiple = String(popupChoiceMode.value || 'single') === 'multiple';
                                if (popupMaxChoicesField) {
                                    popupMaxChoicesField.hidden = !isMultiple;
                                }
                            };

                            syncPopup();
                            popupChoiceMode.addEventListener('change', syncPopup);

                            if (popupCancel) {
                                popupCancel.addEventListener('click', function () {
                                    if (typeof window.commonTopbarCloseModal === 'function') {
                                        window.commonTopbarCloseModal();
                                    }
                                });
                            }

                            popupApply.addEventListener('click', function () {
                                const isMultiple = String(popupChoiceMode.value || 'single') === 'multiple';
                                const normalizedChoiceMode = isMultiple ? 'multiple' : 'single';
                                const rawMaxChoices = Number(popupMaxChoices.value || 0);
                                const normalizedMaxChoices = Number.isFinite(rawMaxChoices)
                                    ? Math.max(Math.floor(rawMaxChoices), 0)
                                    : 0;

                                if (hiddenChoiceModeInput) {
                                    hiddenChoiceModeInput.value = normalizedChoiceMode;
                                }
                                if (hiddenMaxChoicesInput) {
                                    hiddenMaxChoicesInput.value = String(isMultiple ? normalizedMaxChoices : 1);
                                }
                                if (hiddenAnonymousInput) {
                                    hiddenAnonymousInput.value = popupAnonymous.checked ? '1' : '';
                                }
                                if (hiddenAllowAnonymousVotesInput) {
                                    hiddenAllowAnonymousVotesInput.value = popupAllowAnonymousVotes.checked ? '1' : '';
                                }
                                if (hiddenConsultationProposalsInput) {
                                    hiddenConsultationProposalsInput.value = popupConsultationProposals.checked ? '1' : '';
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

                        const refreshProposalLabels = function () {
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'),
                                function (card, index) {
                                    const label = card.querySelector('[data-omo-decision-vote-proposal-label]');
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
                                proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'),
                                function (menuCard) {
                                    if (exceptCard && menuCard === exceptCard) {
                                        return;
                                    }

                                    const menuPanel = menuCard.querySelector('[data-omo-decision-vote-proposal-menu-panel]');
                                    const menuToggle = menuCard.querySelector('[data-omo-decision-vote-proposal-menu-toggle]');
                                    if (menuPanel) {
                                        menuPanel.hidden = true;
                                    }
                                    if (menuToggle) {
                                        menuToggle.setAttribute('aria-expanded', 'false');
                                    }
                                }
                            );
                        };

                        const bindProposalCard = function (card) {
                            if (!card || card.dataset.omoDecisionVoteProposalReady === '1') {
                                return;
                            }

                            const removeButton = card.querySelector('[data-omo-decision-vote-proposal-remove]');
                            const detailsButton = card.querySelector('[data-omo-decision-vote-proposal-settings]');
                            const menuToggle = card.querySelector('[data-omo-decision-vote-proposal-menu-toggle]');
                            const menuPanel = card.querySelector('[data-omo-decision-vote-proposal-menu-panel]');
                            const titleInput = card.querySelector('input[name="proposals[]"]');
                            const descriptionInput = card.querySelector('[data-omo-decision-vote-proposal-description]');
                            const infoUrlInput = card.querySelector('[data-omo-decision-vote-proposal-info-url]');

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

                                    const proposalLabelNode = card.querySelector('[data-omo-decision-vote-proposal-label]');
                                    const modalTitle = proposalLabelNode
                                        ? String(proposalLabelNode.textContent || '').trim()
                                        : String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');
                                    const modalHtml = ''
                                        + '<div class="generic-section generic-section--stack" style="display:grid;gap:12px;">'
                                        + '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-card-title generic-card-title--small">' + String(payload.texts && payload.texts.proposalDescriptionLabel ? payload.texts.proposalDescriptionLabel : 'Description') + '</span>'
                                        + '    <textarea class="generic-form-control" rows="6" data-omo-decision-vote-proposal-modal-description></textarea>'
                                        + '  </label>'
                                        + '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-card-title generic-card-title--small">' + String(payload.texts && payload.texts.proposalInfoUrlLabel ? payload.texts.proposalInfoUrlLabel : 'URL') + '</span>'
                                        + '    <input type="url" class="generic-form-control" data-omo-decision-vote-proposal-modal-info-url placeholder="' + String(payload.texts && payload.texts.proposalInfoUrlPlaceholder ? payload.texts.proposalInfoUrlPlaceholder : 'https://...') + '">'
                                        + '  </label>'
                                        + '  <div style="display:flex;justify-content:flex-end;gap:8px;">'
                                        + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-vote-proposal-modal-cancel>Fermer</button>'
                                        + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-vote-proposal-modal-apply>' + String(payload.texts && payload.texts.proposalApply ? payload.texts.proposalApply : 'Enregistrer') + '</button>'
                                        + '  </div>'
                                        + '</div>';

                                    window.commonTopbarOpenModal(modalTitle || 'Details', modalHtml, 'html');
                                    const modalBody = document.getElementById('commonTopbarModalBody');
                                    if (!modalBody) {
                                        return;
                                    }

                                    const modalDescription = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-description]');
                                    const modalInfoUrl = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-info-url]');
                                    const modalCancel = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-cancel]');
                                    const modalApply = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-apply]');
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
                                    if (proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]').length < 2) {
                                        proposalList.appendChild(createProposalCard(''));
                                    }
                                    refreshProposalLabels();
                                    clearFeedback();
                                });
                            }

                            card.dataset.omoDecisionVoteProposalReady = '1';
                        };

                        const createProposalCard = function (value) {
                            const card = document.createElement('div');
                            card.className = 'omo-decision-vote__proposal-card omo-decision-proposal-card generic-section';
                            card.setAttribute('data-omo-decision-vote-proposal-card', '1');
                            card.setAttribute('draggable', 'true');
                            card.innerHTML = ''
                                + '<button type="button" class="omo-decision-vote__proposal-drag" data-omo-decision-vote-proposal-drag title="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Réordonner') + '" aria-label="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Réordonner') + '">&#8942;&#8942;</button>'
                                + '<div class="omo-decision-vote__proposal-main">'
                                + '    <span class="omo-decision-vote__proposal-label" data-omo-decision-vote-proposal-label></span>'
                                + '    <input type="text" name="proposals[]" class="generic-form-control" placeholder="' + String(payload.texts && payload.texts.proposalPlaceholder ? payload.texts.proposalPlaceholder : 'Nom de la proposition') + '">'
                                + '    <input type="hidden" name="proposal_descriptions[]" value="" data-omo-decision-vote-proposal-description>'
                                + '    <input type="hidden" name="proposal_info_urls[]" value="" data-omo-decision-vote-proposal-info-url>'
                                + '    <input type="hidden" name="proposal_ids[]" value="0">'
                                + '</div>'
                                + '<div class="omo-decision-vote__proposal-menu" data-omo-decision-vote-proposal-menu>'
                                + '    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-menu-toggle" data-omo-decision-vote-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Actions">...</button>'
                                + '    <div class="omo-decision-vote__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel" data-omo-decision-vote-proposal-menu-panel role="menu" hidden>'
                                + '        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-menu-item" data-omo-decision-vote-proposal-settings role="menuitem">' + String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details') + '</button>'
                                + '        <button type="button" class="generic-action-button generic-action-button--danger omo-decision-vote__proposal-menu-item" data-omo-decision-vote-proposal-remove role="menuitem">' + String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer') + '</button>'
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

                        let sortable = null;
                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'), bindProposalCard);
                        refreshProposalLabels();
                        syncSettingsSummary();

                        document.addEventListener('click', function (event) {
                            if (!proposalList.contains(event.target)) {
                                closeProposalMenus();
                            }
                        });

                        if (settingsOpenButton) {
                            settingsOpenButton.addEventListener('click', function () {
                                openSettingsModal();
                            });
                        }

                        if (invitationOpenButton) {
                            invitationOpenButton.addEventListener('click', function () {
                                openInvitationModal();
                            });
                        }

                        if (invitationSendOpenButton) {
                            invitationSendOpenButton.addEventListener('click', function () {
                                openInvitationSendModal();
                            });
                        }

                        if (payload.proposalEditable && typeof window.commonCreateVerticalSortableList === 'function') {
                            sortable = window.commonCreateVerticalSortableList({
                                list: proposalList,
                                itemSelector: '[data-omo-decision-vote-proposal-card]',
                                handleSelector: '[data-omo-decision-vote-proposal-drag]',
                                draggingClass: 'is-dragging',
                                dropTargetClass: 'is-drop-target',
                                placeholderClass: 'omo-decision-vote__proposal-placeholder',
                                createPlaceholder: function (card) {
                                    const placeholder = document.createElement('div');
                                    placeholder.className = 'omo-decision-vote__proposal-placeholder';
                                    placeholder.style.height = Math.max(Number(card.getBoundingClientRect().height) || 0, 78) + 'px';
                                    return placeholder;
                                },
                                onDragStart: clearFeedback,
                                onDragEnd: refreshProposalLabels,
                                onDrop: function () {
                                    refreshProposalLabels();
                                    clearFeedback();
                                }
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
                                clearFeedback();
                            });
                        }

                        form.addEventListener('submit', function (event) {
                            if (!submitButton) {
                                return;
                            }

                            event.preventDefault();

                            const formData = new FormData(form);
                            const defaultLabel = payload.texts && payload.texts.save ? payload.texts.save : submitButton.textContent;
                            const savingLabel = payload.texts && payload.texts.saving ? payload.texts.saving : defaultLabel;

                            submitButton.disabled = true;
                            submitButton.textContent = savingLabel;
                            clearFeedback();

                            fetch(payload.saveUrl || form.action, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                                .then(function (response) {
                                    return response.json().catch(function () {
                                        return {
                                            status: false,
                                            message: payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'
                                        };
                                    });
                                })
                                .then(function (response) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = defaultLabel;

                                    if (!response || !response.status) {
                                        setFeedback(response && response.message ? response.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'), true);
                                        return;
                                    }

                                    setFeedback(response.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);

                                    if (response.redirectUrl) {
                                        window.setTimeout(function () {
                                            if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                                window.omoDecisionOpenNestedDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, '');
                                                return;
                                            }

                                            if (typeof window.commonTopbarOpenDrawer === 'function') {
                                                window.commonTopbarOpenDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, 'fetch');
                                                return;
                                            }

                                            window.location.href = response.redirectUrl;
                                        }, 250);
                                    }
                                })
                                .catch(function () {
                                    submitButton.disabled = false;
                                    submitButton.textContent = defaultLabel;
                                    setFeedback(payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.', true);
                                });
                        });

                        form.dataset.omoDecisionVoteReady = '1';
                    });

                    scope.querySelectorAll('[data-omo-decision-vote-response-form]').forEach(function (form) {
                        if (form.dataset.omoDecisionVoteResponseReady === '1') {
                            return;
                        }

                        const payloadNode = form.querySelector('[data-omo-decision-vote-response-data]');
                        const submitButton = form.querySelector('[data-omo-decision-vote-response-submit]');
                        const feedbackNode = form.querySelector('[data-omo-decision-vote-response-feedback]');
                        if (!payloadNode || !submitButton || !feedbackNode) {
                            return;
                        }

                        const voteWeightSelector = form.querySelector('[data-omo-decision-vote-weight-selector]');
                        if (voteWeightSelector && typeof window.omoDecisionInitVoteWeightSelector === 'function') {
                            window.omoDecisionInitVoteWeightSelector(voteWeightSelector);
                        }

                        let payload = {};
                        try {
                            payload = JSON.parse(payloadNode.textContent || '{}');
                        } catch (error) {
                            payload = {};
                        }

                        const setFeedback = function (message, isError) {
                            feedbackNode.textContent = String(message || '');
                            feedbackNode.classList.toggle('is-error', !!isError);
                            feedbackNode.classList.toggle('is-success', !isError && message !== '');
                        };

                        form.addEventListener('submit', function (event) {
                            event.preventDefault();

                            if (String(payload.choiceMode || 'single') === 'multiple') {
                                const checkedChoices = form.querySelectorAll('input[name="proposal_ids[]"]:checked');
                                const rawMaxChoices = Number(payload.maxChoices || 0);
                                const maxChoices = Number.isFinite(rawMaxChoices)
                                    ? Math.max(Math.floor(rawMaxChoices), 0)
                                    : 0;
                                if (checkedChoices.length === 0) {
                                    setFeedback(payload.texts && payload.texts.multipleHint ? payload.texts.multipleHint : 'Choisissez au moins une proposition.', true);
                                    return;
                                }
                                if (maxChoices > 0 && checkedChoices.length > maxChoices) {
                                    setFeedback(payload.texts && payload.texts.multipleHint ? payload.texts.multipleHint : 'Trop de propositions selectionnees.', true);
                                    return;
                                }
                            }

                            const formData = new FormData(form);
                            const defaultLabel = payload.texts && payload.texts.save ? payload.texts.save : submitButton.textContent;
                            const savingLabel = payload.texts && payload.texts.saving ? payload.texts.saving : defaultLabel;

                            submitButton.disabled = true;
                            submitButton.textContent = savingLabel;
                            setFeedback('', false);

                            fetch(payload.saveUrl || form.action, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                                .then(function (response) {
                                    return response.json().catch(function () {
                                        return {
                                            status: false,
                                            message: payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'
                                        };
                                    });
                                })
                                .then(function (response) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = defaultLabel;

                                    if (!response || !response.status) {
                                        setFeedback(response && response.message ? response.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'), true);
                                        return;
                                    }

                                    setFeedback(response.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);

                                    if (response.redirectUrl) {
                                        window.setTimeout(function () {
                                            if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                                window.omoDecisionOpenNestedDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, '');
                                                return;
                                            }

                                            if (typeof window.commonTopbarOpenDrawer === 'function') {
                                                window.commonTopbarOpenDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, 'fetch');
                                                return;
                                            }

                                            window.location.href = response.redirectUrl;
                                        }, 250);
                                    }
                                })
                                .catch(function () {
                                    submitButton.disabled = false;
                                    submitButton.textContent = defaultLabel;
                                    setFeedback(payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.', true);
                                });
                        });

                        form.dataset.omoDecisionVoteResponseReady = '1';
                    });

                    scope.querySelectorAll('[data-omo-decision-vote-results-panel]').forEach(function (panel) {
                        if (panel.dataset.omoDecisionVoteResultsReady === '1') {
                            return;
                        }

                        const list = panel.querySelector('[data-omo-decision-vote-results-list]');
                        const buttons = panel.querySelectorAll('[data-omo-decision-vote-results-sort]');
                        const compareToggle = panel.querySelector('[data-omo-decision-vote-results-compare-toggle]');
                        const compareBlocks = panel.querySelectorAll('[data-omo-decision-vote-results-compare-block]');
                        if (!list) {
                            panel.dataset.omoDecisionVoteResultsReady = '1';
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
                            const items = Array.prototype.slice.call(list.querySelectorAll('[data-omo-decision-vote-result-item]'));
                            if (items.length < 2) {
                                return;
                            }

                            items.sort(function (left, right) {
                                const leftPosition = Number(left.getAttribute('data-omo-decision-vote-result-position') || 0);
                                const rightPosition = Number(right.getAttribute('data-omo-decision-vote-result-position') || 0);
                                const leftTitle = String(left.getAttribute('data-omo-decision-vote-result-title') || '');
                                const rightTitle = String(right.getAttribute('data-omo-decision-vote-result-title') || '');
                                const leftVotes = Number(left.getAttribute('data-omo-decision-vote-result-votes') || 0);
                                const rightVotes = Number(right.getAttribute('data-omo-decision-vote-result-votes') || 0);

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

                                if (leftVotes !== rightVotes) {
                                    return rightVotes - leftVotes;
                                }

                                return leftPosition - rightPosition;
                            });

                            items.forEach(function (item) {
                                list.appendChild(item);
                            });
                        };

                        const syncComparison = function () {
                            const showComparison = !!(compareToggle && compareToggle.checked);
                            compareBlocks.forEach(function (block) {
                                block.hidden = !showComparison;
                                block.setAttribute('aria-hidden', showComparison ? 'false' : 'true');
                                block.style.display = showComparison ? '' : 'none';
                            });
                        };

                        const applySortMode = function (sortMode) {
                            const normalizedSortMode = normalizeSortMode(sortMode);
                            buttons.forEach(function (button) {
                                const isActive = normalizeSortMode(button.getAttribute('data-omo-decision-vote-results-sort')) === normalizedSortMode;
                                button.classList.toggle('is-active', isActive);
                                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            });

                            sortItems(normalizedSortMode);
                        };

                        buttons.forEach(function (button) {
                            button.addEventListener('click', function () {
                                applySortMode(button.getAttribute('data-omo-decision-vote-results-sort'));
                            });
                        });

                        if (compareToggle) {
                            compareToggle.addEventListener('change', syncComparison);
                        }

                        if (buttons.length) {
                            applySortMode('rank');
                        }
                        syncComparison();
                        panel.dataset.omoDecisionVoteResultsReady = '1';
                    });
                };
            }

            window.omoDecisionVoteInit(document);
        })();
        </script>

        <style>
        .omo-decision-vote {
            gap: 18px;
        }

        .omo-decision-vote__head,
        .omo-decision-vote__copy,
        .omo-decision-vote__field,
        .omo-decision-vote__notice {
            display: grid;
            gap: 8px;
        }

        .omo-decision-vote__form {
            display: grid;
            gap: var(--generic-form-gap, var(--generic-space-4, 16px));
        }

        .omo-decision-vote__head {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 12px;
        }

        .omo-decision-vote__grid,
        .omo-decision-vote__summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .omo-decision-vote__textarea {
            min-height: 120px;
            resize: vertical;
        }

        .omo-decision-vote__settings-summary,
        .omo-decision-vote__settings-head {
            display: grid;
            gap: 12px;
        }

        .omo-decision-vote__settings-head {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }

        .omo-decision-vote-popup__stack,
        .omo-decision-vote-popup__choice-grid,
        .omo-decision-vote-popup__actions {
            display: grid;
            gap: 12px;
        }

        .omo-decision-vote-popup__choice-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .omo-decision-vote-popup__field {
            display: grid;
            gap: 8px;
        }

        .omo-decision-vote-popup__actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .omo-decision-vote__field[hidden] {
            display: none !important;
        }

        .omo-decision-vote__check-row {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .omo-decision-vote__proposal-list,
        .omo-decision-vote__readonly-list {
            display: grid;
            gap: 10px;
        }

        .omo-decision-vote__results-panel {
            display: grid;
            gap: 12px;
        }

        .omo-decision-vote__results-controls {
            justify-content: flex-end;
        }

        .omo-decision-vote__results-compare {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--color-text-light, #475569);
            font-size: 0.92rem;
        }

        .omo-decision-vote__readonly-item,
        .omo-decision-vote__option {
            display: grid;
            gap: 10px;
            align-items: start;
            padding: 12px;
            border: 1px solid var(--color-border, #d1d5db);
            border-radius: var(--radius-md);
            background: var(--color-surface, #fff);
        }

        .omo-decision-vote__proposal-card {
            grid-template-columns: auto minmax(0, 1fr) auto;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease, transform 0.2s ease;
        }

        .omo-decision-vote__proposal-card.is-dragging {
            opacity: 0.55;
            transform: scale(0.995);
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
        }

        .omo-decision-vote__proposal-card.is-drop-target {
            border-color: var(--color-primary, #4f46e5);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.14);
        }

        .omo-decision-vote__proposal-placeholder {
            min-height: 78px;
            border: 2px dashed color-mix(in srgb, var(--color-primary, #4f46e5) 46%, var(--color-border, #d1d5db));
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(79, 70, 229, 0.03));
            box-sizing: border-box;
        }

        .omo-decision-vote__proposal-drag {
            width: 36px;
            min-width: 36px;
            min-height: 42px;
            border: 0;
            border-radius: var(--radius-md);
            background: rgba(148, 163, 184, 0.14);
            color: #475569;
            cursor: grab;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            line-height: 1;
            touch-action: none;
        }

        .omo-decision-vote__proposal-drag:disabled {
            cursor: default;
            opacity: 0.55;
        }

        .omo-decision-vote__proposal-main {
            display: grid;
            gap: 6px;
        }

        .omo-decision-vote__proposal-menu {
            position: relative;
            align-self: start;
        }

        .omo-decision-vote__proposal-menu-toggle {
            min-width: 42px;
            padding-inline: 12px;
        }

        .omo-decision-vote__proposal-menu-item {
            width: 100%;
            justify-content: flex-start;
            box-shadow: none;
        }

        .omo-decision-vote__proposal-label {
            font-size: 13px;
            color: var(--color-text-light, #475569);
            font-weight: 600;
        }

        .omo-decision-vote__proposal-add {
            align-self: start;
        }

        .omo-decision-vote__footer {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .omo-decision-vote__feedback {
            min-height: 20px;
            color: var(--color-text-light, #475569);
            font-size: 14px;
        }

        .omo-decision-vote__feedback.is-error {
            color: #b91c1c;
        }

        .omo-decision-vote__feedback.is-success {
            color: #166534;
        }

        .omo-decision-vote__fieldset {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            border: 0;
        }

        .omo-decision-vote__option {
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            cursor: pointer;
        }

        .omo-decision-vote__readonly-item.is-selected {
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 30%, var(--color-border, #d1d5db));
            background: color-mix(in srgb, var(--color-primary, #2563eb) 8%, var(--color-surface, #fff));
        }

        .omo-decision-vote__readonly-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .omo-decision-vote__readonly-stats--settings {
            display: grid;
            gap: 8px;
        }

        .omo-decision-vote__readonly-stat {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
            color: var(--color-text-light, #475569);
            font-size: 0.84rem;
        }

        .omo-decision-vote__readonly-stats--settings .omo-decision-vote__readonly-stat {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px 8px;
            align-items: baseline;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, white);
        }

        .omo-decision-vote__readonly-stat strong {
            color: var(--color-text, #1f2937);
            font-weight: 600;
        }

        .omo-decision-vote__distribution {
            --omo-decision-vote-distribution-padding: 4px;
            display: flex;
            align-items: stretch;
            min-height: 18px;
            padding: var(--omo-decision-vote-distribution-padding);
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, white);
            overflow: hidden;
        }

        .omo-decision-vote__distribution-track {
            display: block;
            flex: 1 1 auto;
            min-height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: color-mix(in srgb, var(--color-surface, #ffffff) 78%, var(--color-text-light, #64748b) 22%);
        }

        .omo-decision-vote__distribution-fill {
            display: block;
            height: 100%;
            min-height: 10px;
            border-radius: 999px;
            background: hsl(calc(var(--omo-decision-vote-share-ratio, 0) * 120deg) 72% 46%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.28);
        }

        .omo-decision-vote__distribution-scale {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--color-text-light, #475569);
            font-size: 12px;
        }

        .omo-decision-vote__comparison {
            display: grid;
            gap: 8px;
            padding-top: 4px;
            border-top: 1px dashed color-mix(in srgb, var(--color-border, #d1d5db) 82%, transparent);
        }

        .omo-decision-vote__comparison[hidden] {
            display: none !important;
        }

        .omo-decision-vote__comparison-title {
            color: var(--color-text-light, #475569);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .omo-decision-vote__distribution-fill--secondary {
            background: color-mix(in srgb, var(--color-primary, #2563eb) 42%, var(--color-surface-alt, #cbd5e1));
        }

        @media (max-width: 720px) {
            .omo-decision-vote__head {
                grid-template-columns: 1fr;
            }

            .omo-decision-vote__settings-head {
                grid-template-columns: 1fr;
            }

            .omo-decision-vote__results-controls {
                justify-content: flex-start;
            }

            .omo-decision-vote__proposal-card {
                grid-template-columns: auto 1fr;
            }

            .omo-decision-vote__proposal-remove {
                grid-column: 1 / -1;
                justify-self: start;
            }
        }
        </style>
        <?php
    }
}
