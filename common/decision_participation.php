<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/omo_public_pages.php';
require_once __DIR__ . '/topbar.php';
require_once __DIR__ . '/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/registry.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';

use dbObject\DecisionParticipant;
use dbObject\DecisionInvitation;
use dbObject\DecisionGroup;
use dbObject\DecisionProcess;
use dbObject\Holon;

if (!function_exists('omoApiEscape')) {
    function omoApiEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!function_exists('commonDecisionParticipationGetSourceLang')) {
    function commonDecisionParticipationGetSourceLang()
    {
        return [
            'decisions.public.default_organization' => ['text' => 'Organisation', 'context' => 'Fallback organization name on the public decision page.'],
            'decisions.public.default_title' => ['text' => 'Prise de décision', 'context' => 'Fallback decision title on the public page.'],
            'decisions.public.method.simple_vote' => ['text' => 'Vote simple', 'context' => 'Public label for the simple vote method.'],
            'decisions.public.method.majority_judgment' => ['text' => 'Jugement majoritaire', 'context' => 'Public label for the majority judgment method.'],
            'decisions.public.method.consent' => ['text' => 'Consentement', 'context' => 'Public label for the consent method.'],
            'decisions.public.method.unknown' => ['text' => 'Mode de décision', 'context' => 'Fallback public label for an unknown decision method.'],
            'decisions.public.type.consultation' => ['text' => 'consultation', 'context' => 'Public label for a consultation process.'],
            'decisions.public.type.decision' => ['text' => 'décision', 'context' => 'Public label for a decision process.'],
            'decisions.public.timeline.now' => ['text' => 'Maintenant', 'context' => 'Current moment label on the public decision timeline.'],
            'decisions.public.timeline.consultation_start' => ['text' => 'Début de la consultation', 'context' => 'Consultation start milestone label.'],
            'decisions.public.timeline.consultation_end' => ['text' => 'Fin de la consultation', 'context' => 'Consultation end milestone label.'],
            'decisions.public.timeline.evaluation_start' => ['text' => 'Début du vote', 'context' => 'Evaluation start milestone label.'],
            'decisions.public.timeline.evaluation_end' => ['text' => 'Fin du vote', 'context' => 'Evaluation end milestone label.'],
            'decisions.public.timeline.results' => ['text' => 'Résultats', 'context' => 'Results milestone label.'],
            'decisions.public.timeline.title' => ['text' => 'Étapes du scrutin', 'context' => 'Public timeline accordion title.'],
            'decisions.public.timeline.hint' => ['text' => 'Afficher la représentation graphique', 'context' => 'Public timeline accordion hint.'],
            'decisions.public.timeline.waiting_consultation' => ['text' => 'En attente du début de la consultation le {date}', 'context' => 'Timeline summary before consultation starts.'],
            'decisions.public.timeline.waiting_vote' => ['text' => 'En attente du début du vote le {date}', 'context' => 'Timeline summary before evaluation starts.'],
            'decisions.public.timeline.consultation_until' => ['text' => 'En consultation jusqu’au {date}', 'context' => 'Timeline summary while consultation is open with an end date.'],
            'decisions.public.timeline.consultation_open' => ['text' => 'En consultation', 'context' => 'Timeline summary while consultation is open without an end date.'],
            'decisions.public.timeline.vote_until' => ['text' => 'Vote ouvert jusqu’au {date}', 'context' => 'Timeline summary while voting is open with an end date.'],
            'decisions.public.timeline.vote_open' => ['text' => 'Vote ouvert', 'context' => 'Timeline summary while voting is open without an end date.'],
            'decisions.public.timeline.finished' => ['text' => 'Terminé', 'context' => 'Timeline summary after the decision is finished.'],
            'decisions.public.timeline.segment_consultation' => ['text' => 'Consultation', 'context' => 'Timeline segment label for consultation.'],
            'decisions.public.timeline.segment_vote' => ['text' => 'Vote', 'context' => 'Timeline segment label for evaluation.'],
            'decisions.public.timeline.segment_results' => ['text' => 'Résultats', 'context' => 'Timeline segment label for results.'],
            'decisions.public.options.responses_editable' => ['text' => 'Vos réponses sont modifiables.', 'context' => 'Public option indicating that responses can still be changed.'],
            'decisions.public.options.responses_locked' => ['text' => 'Vos réponses ne sont plus modifiables.', 'context' => 'Public option indicating that responses can no longer be changed.'],
            'decisions.public.options.results_hidden' => ['text' => 'Les résultats ne sont pas visibles avant la fin du vote.', 'context' => 'Public option indicating that results are hidden.'],
            'decisions.public.options.results_visible' => ['text' => 'Les résultats sont visibles.', 'context' => 'Public option indicating that results are visible.'],
            'decisions.public.options.anonymous' => ['text' => 'Ce scrutin est anonyme.', 'context' => 'Public option indicating that the decision is anonymous.'],
            'decisions.public.options.not_anonymous' => ['text' => 'Ce scrutin n’est pas anonyme.', 'context' => 'Public option indicating that the decision is not anonymous.'],
            'decisions.public.options.mixed_anonymity' => ['text' => 'Le caractère anonyme peut varier selon les blocs.', 'context' => 'Public option indicating mixed anonymity settings.'],
            'decisions.public.options.proposals_all' => ['text' => 'Vous pouvez faire des propositions durant la consultation.', 'context' => 'Public option when proposals are allowed in every block.'],
            'decisions.public.options.proposals_some' => ['text' => 'Vous pouvez faire des propositions durant la consultation pour certains blocs.', 'context' => 'Public option when proposals are allowed in some blocks.'],
            'decisions.public.options.discussions_all' => ['text' => 'Vous pouvez discuter les propositions pendant la consultation', 'context' => 'Public option when discussions are allowed in every block.'],
            'decisions.public.options.discussions_some' => ['text' => 'Vous pouvez discuter les propositions pendant la consultation pour certains blocs', 'context' => 'Public option when discussions are allowed in some blocks.'],
            'decisions.public.options.discussions_anonymous' => ['text' => ' de façon anonyme.', 'context' => 'Suffix when discussion anonymity is mandatory.'],
            'decisions.public.options.discussions_optional_anonymity' => ['text' => ', anonymement si vous le souhaitez.', 'context' => 'Suffix when participants may discuss anonymously.'],
            'decisions.public.context.organizer' => ['text' => 'Organisateur', 'context' => 'Public context card title for the organizer.'],
            'decisions.public.context.method' => ['text' => 'Méthode', 'context' => 'Public context card title for one method.'],
            'decisions.public.context.methods' => ['text' => 'Méthodes', 'context' => 'Public context card title for several methods.'],
            'decisions.public.context.invited' => ['text' => 'Invités', 'context' => 'Public context card title for invited participants.'],
            'decisions.public.context.options' => ['text' => 'Options', 'context' => 'Public context card title for decision options.'],
            'decisions.public.context.contact_organizer' => ['text' => 'Contacter l’organisateur :', 'context' => 'Public contact label for the organizer.'],
            'decisions.public.block' => ['text' => 'Bloc {index}', 'context' => 'Public label for a decision block.'],
            'decisions.public.block_unavailable' => ['text' => 'Ce bloc ne dispose pas encore d’une interface disponible.', 'context' => 'Fallback shown when a public decision block has no renderer.'],
            'decisions.public.access.invalid_decision' => ['text' => 'Cette prise de décision est introuvable.', 'context' => 'Public access error when the decision cannot be found.'],
            'decisions.public.access.invalid_email' => ['text' => 'Merci de saisir une adresse e-mail valide.', 'context' => 'Public access error for an invalid email address.'],
            'decisions.public.access.sync_failed' => ['text' => 'Impossible de vérifier les participants autorisés pour le moment.', 'context' => 'Public access error when participants cannot be synchronized.'],
            'decisions.public.access.participant_unavailable' => ['text' => 'Cette adresse e-mail est déjà liée à un participant qui ne peut plus utiliser ce scrutin.', 'context' => 'Public access error for an unavailable participant.'],
            'decisions.public.access.create_failed' => ['text' => 'Impossible de créer cette participation pour le moment.', 'context' => 'Public access error when participation creation fails.'],
            'decisions.public.access.not_allowed_open' => ['text' => 'Cette adresse e-mail ne peut pas être utilisée pour ce scrutin pour le moment.', 'context' => 'Public access error when open registration does not accept the email.'],
            'decisions.public.access.not_allowed_invited' => ['text' => 'Cette adresse e-mail ne fait pas partie des personnes autorisées à participer à ce scrutin.', 'context' => 'Public access error when the email is not invited.'],
            'decisions.public.access.empty_code' => ['text' => 'Merci de saisir le code reçu par e-mail.', 'context' => 'Public access error for an empty access code.'],
            'decisions.public.access.missing_code' => ['text' => 'Aucun code valide n’a été trouvé pour cette adresse. Demandez-en un nouveau.', 'context' => 'Public access error when no code exists.'],
            'decisions.public.access.expired_code' => ['text' => 'Ce code a expiré. Demandez-en un nouveau depuis cette page.', 'context' => 'Public access error for an expired code.'],
            'decisions.public.access.invalid_code' => ['text' => 'Le code saisi est incorrect.', 'context' => 'Public access error for an invalid code.'],
            'decisions.public.access.consume_failed' => ['text' => 'Le code est correct, mais l’accès n’a pas pu être finalisé. Réessayez dans un instant.', 'context' => 'Public access error when code consumption fails.'],
            'decisions.public.access.code_verification_failed' => ['text' => 'Impossible de vérifier ce code pour le moment.', 'context' => 'Fallback public access error during code verification.'],
            'decisions.public.access.finalize_failed' => ['text' => 'Le lien personnel n’a pas pu être finalisé. Réessayez dans un instant.', 'context' => 'Public access error when the personal link cannot be finalized.'],
            'decisions.public.access.code_valid' => ['text' => 'Code valide. Redirection en cours…', 'context' => 'Public access success after code verification.'],
            'decisions.public.access.code_sent' => ['text' => 'Un code personnel et un lien direct viennent d’être envoyés à {email}.', 'context' => 'Public access success after sending an access code.'],
            'decisions.public.status.unavailable' => ['text' => 'Ce lien ne permet pas d’accéder à cette prise de décision.', 'context' => 'Public status when the decision cannot be accessed.'],
            'decisions.public.status.request_open' => ['text' => 'Entrez votre adresse e-mail pour recevoir un code personnel de participation.', 'context' => 'Public access prompt when self-registration is open.'],
            'decisions.public.status.request_invited' => ['text' => 'Entrez votre adresse e-mail autorisée pour recevoir un code personnel de participation.', 'context' => 'Public access prompt when only invited emails may request access.'],
            'decisions.public.status.can_participate' => ['text' => 'Vous pouvez participer à ce scrutin depuis cette page publique.', 'context' => 'Public status when participation is available.'],
            'decisions.public.access.title_eyebrow' => ['text' => 'Accès public', 'context' => 'Eyebrow title on the public access request form.'],
            'decisions.public.access.title' => ['text' => 'Recevoir mon accès personnel', 'context' => 'Title on the public access request form.'],
            'decisions.public.access.open_description' => ['text' => 'Saisissez votre adresse e-mail pour recevoir un code personnel ainsi qu’un lien direct personnel. Si cette adresse n’est pas encore associée à ce scrutin, une participation sera créée automatiquement.', 'context' => 'Description of open public access registration.'],
            'decisions.public.access.invited_description' => ['text' => 'Saisissez l’adresse e-mail autorisée pour ce scrutin afin de recevoir un code personnel ainsi qu’un lien direct de participation.', 'context' => 'Description of invited public access registration.'],
            'decisions.public.access.email' => ['text' => 'Adresse e-mail', 'context' => 'Email field label on the public access form.'],
            'decisions.public.access.email_placeholder' => ['text' => 'nom@exemple.org', 'context' => 'Email field placeholder on the public access form.'],
            'decisions.public.access.code' => ['text' => 'Code reçu par e-mail', 'context' => 'Access code field label on the public access form.'],
            'decisions.public.access.code_placeholder' => ['text' => '123456', 'context' => 'Access code field placeholder on the public access form.'],
            'decisions.public.access.send' => ['text' => 'Envoyer mon accès', 'context' => 'Button requesting a public access code.'],
            'decisions.public.access.resend' => ['text' => 'Renvoyer le code', 'context' => 'Button resending a public access code.'],
            'decisions.public.access.enter' => ['text' => 'Accéder au scrutin', 'context' => 'Button validating the public access code.'],
            'decisions.public.navigation.help' => ['text' => 'Aide', 'context' => 'Public decision page help label.'],
            'decisions.public.help.webmaster' => ['text' => 'Webmaster : {email}', 'context' => 'Server administrator email link shown below the public decision help items.'],
            'decisions.public.navigation.aria' => ['text' => 'Navigation du scrutin', 'context' => 'Public decision page mobile navigation label.'],
            'decisions.public.navigation.info' => ['text' => 'Infos', 'context' => 'Public decision page mobile information tab.'],
            'decisions.public.navigation.decision' => ['text' => 'Scrutin', 'context' => 'Public decision page mobile decision tab.'],
            'decisions.public.banner' => ['text' => 'Scrutin public organisé pour {organization}', 'context' => 'Public decision page banner.'],
            'decisions.public.banner_prefix' => ['text' => 'Scrutin public organisé pour', 'context' => 'Prefix in the public decision page banner before the organization name.'],
            'decisions.public.banner_personal_access' => ['text' => ' · accès personnel de {participant}', 'context' => 'Personal access suffix in the public decision page banner.'],
            'decisions.public.js.invalid_response' => ['text' => 'Réponse invalide du serveur.', 'context' => 'JavaScript fallback when the proposal response is invalid.'],
            'decisions.public.js.proposal_add_failed' => ['text' => 'Impossible d’ajouter la proposition pour le moment.', 'context' => 'JavaScript fallback when adding a consultation proposal fails.'],
            'decisions.public.js.access_process_failed' => ['text' => 'Impossible de traiter cette demande pour le moment.', 'context' => 'JavaScript fallback when processing a public access request fails.'],
            'decisions.public.js.code_sent' => ['text' => 'Code envoyé.', 'context' => 'JavaScript fallback after sending a public access code.'],
            'decisions.public.js.proposal_placeholder' => ['text' => 'Proposition', 'context' => 'JavaScript prefix for a consultation proposal input placeholder.'],
            'decisions.public.js.remove_proposal' => ['text' => 'Supprimer', 'context' => 'JavaScript label for removing a consultation proposal input.'],
        ];
    }
}

$commonDecisionParticipationSourceLang = commonDecisionParticipationGetSourceLang();
$commonDecisionParticipationLang = omoLoadTranslationBundle('omo_decision_public', $commonDecisionParticipationSourceLang);

if (!function_exists('commonDecisionParticipationT')) {
    function commonDecisionParticipationT($key, array $variables = [])
    {
        global $commonDecisionParticipationLang, $commonDecisionParticipationSourceLang;
        return t($key, $variables, $commonDecisionParticipationLang, $commonDecisionParticipationSourceLang);
    }
}

function commonDecisionParticipationParseDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $exception) {
        return null;
    }
}

function commonDecisionParticipationFormatDateTime($value)
{
    $dateTime = $value instanceof DateTimeInterface
        ? $value
        : commonDecisionParticipationParseDateTime($value);

    if (!$dateTime instanceof DateTimeInterface) {
        return '';
    }

    return $dateTime->format('d.m.Y H:i');
}

function commonDecisionParticipationGetRenderableGroups(DecisionProcess $decision)
{
    $groups = [];
    foreach ($decision->getDecisionGroups(false) as $group) {
        if (!($group instanceof DecisionGroup)) {
            continue;
        }

        if ((int)$group->get('active') !== 1) {
            continue;
        }

        $groups[] = $group;
    }

    if (count($groups) === 0) {
        $primaryGroup = $decision->getPrimaryGroup(false);
        if ($primaryGroup instanceof DecisionGroup) {
            $groups[] = $primaryGroup;
        }
    }

    return $groups;
}

function commonDecisionParticipationGetMethodLabel($method)
{
    $method = DecisionProcess::normalizeEvaluationMethod($method);
    if ($method === DecisionProcess::METHOD_SIMPLE_VOTE) {
        return commonDecisionParticipationT('decisions.public.method.simple_vote');
    }
    if ($method === DecisionProcess::METHOD_MAJORITY_JUDGMENT) {
        return commonDecisionParticipationT('decisions.public.method.majority_judgment');
    }
    if ($method === DecisionProcess::METHOD_CONSENT) {
        return commonDecisionParticipationT('decisions.public.method.consent');
    }

    return commonDecisionParticipationT('decisions.public.method.unknown');
}

function commonDecisionParticipationGetDecisionTypeLabel($decisionType)
{
    return trim((string)$decisionType) === DecisionProcess::TYPE_CONSULTATION
        ? commonDecisionParticipationT('decisions.public.type.consultation')
        : commonDecisionParticipationT('decisions.public.type.decision');
}

function commonDecisionParticipationGetTimelineDates(DecisionProcess $decision)
{
    $status = DecisionProcess::normalizeStatus($decision->get('status'));
    $dates = [
        'consultation_start' => commonDecisionParticipationParseDateTime($decision->get('consultation_start_at')),
        'consultation_end' => commonDecisionParticipationParseDateTime($decision->get('consultation_end_at')),
        'evaluation_start' => commonDecisionParticipationParseDateTime($decision->get('evaluation_start_at')),
        'evaluation_end' => commonDecisionParticipationParseDateTime($decision->get('evaluation_end_at')),
        'results' => commonDecisionParticipationParseDateTime($decision->get('results_published_at')),
    ];

    if (
        !($dates['results'] instanceof DateTimeInterface)
        && DecisionProcess::getStatusRank($status) >= DecisionProcess::getStatusRank(DecisionProcess::STATUS_RESULTS)
    ) {
        $dates['results'] = $dates['evaluation_end'] instanceof DateTimeInterface
            ? $dates['evaluation_end']
            : null;
    }

    return $dates;
}

function commonDecisionParticipationBuildTimelineItems(DecisionProcess $decision)
{
    $dates = commonDecisionParticipationGetTimelineDates($decision);
    $hasAnyDate = false;
    foreach ($dates as $dateValue) {
        if ($dateValue instanceof DateTimeInterface) {
            $hasAnyDate = true;
            break;
        }
    }

    if (!$hasAnyDate) {
        return [];
    }

    $items = [
        [
            'label' => commonDecisionParticipationT('decisions.public.timeline.now'),
            'date' => new DateTimeImmutable('now'),
            'is_now' => true,
            'show_date' => true,
        ],
    ];

    $milestones = [
        ['label' => commonDecisionParticipationT('decisions.public.timeline.consultation_start'), 'value' => $dates['consultation_start']],
        ['label' => commonDecisionParticipationT('decisions.public.timeline.consultation_end'), 'value' => $dates['consultation_end']],
        ['label' => commonDecisionParticipationT('decisions.public.timeline.evaluation_start'), 'value' => $dates['evaluation_start']],
        ['label' => commonDecisionParticipationT('decisions.public.timeline.evaluation_end'), 'value' => $dates['evaluation_end']],
    ];

    foreach ($milestones as $milestone) {
        $dateTime = commonDecisionParticipationParseDateTime($milestone['value']);
        if (!$dateTime instanceof DateTimeInterface) {
            continue;
        }

        $items[] = [
            'label' => $milestone['label'],
            'date' => $dateTime,
            'is_now' => false,
            'show_date' => true,
        ];
    }

    $resultsDate = $dates['results'];
    if ($resultsDate instanceof DateTimeInterface) {
        $items[] = [
            'label' => commonDecisionParticipationT('decisions.public.timeline.results'),
            'date' => $resultsDate,
            'is_now' => false,
            'show_date' => true,
        ];
    }

    $now = new DateTimeImmutable('now');
    foreach ($items as $index => $item) {
        if (!empty($item['is_now'])) {
            $items[$index]['state'] = 'current';
            continue;
        }

        $itemDate = $item['date'] ?? null;
        $items[$index]['state'] = ($itemDate instanceof DateTimeInterface && $itemDate <= $now)
            ? 'past'
            : 'upcoming';
    }

    return $items;
}

function commonDecisionParticipationBuildTimelineSummary(DecisionProcess $decision)
{
    $dates = commonDecisionParticipationGetTimelineDates($decision);
    $now = new DateTimeImmutable('now');

    $consultationStart = $dates['consultation_start'];
    $consultationEnd = $dates['consultation_end'];
    $evaluationStart = $dates['evaluation_start'];
    $evaluationEnd = $dates['evaluation_end'];
    $resultsDate = $dates['results'];
    $status = DecisionProcess::normalizeStatus($decision->get('status'));

    $summary = [
        'title' => commonDecisionParticipationT('decisions.public.timeline.title'),
        'hint' => commonDecisionParticipationT('decisions.public.timeline.hint'),
    ];

    if ($consultationStart instanceof DateTimeInterface && $now < $consultationStart) {
        $summary['title'] = commonDecisionParticipationT('decisions.public.timeline.waiting_consultation', ['date' => commonDecisionParticipationFormatDateTime($consultationStart)]);
        return $summary;
    }

    if (
        !($consultationStart instanceof DateTimeInterface)
        && $evaluationStart instanceof DateTimeInterface
        && $now < $evaluationStart
    ) {
        $summary['title'] = commonDecisionParticipationT('decisions.public.timeline.waiting_vote', ['date' => commonDecisionParticipationFormatDateTime($evaluationStart)]);
        return $summary;
    }

    $consultationLimit = $consultationEnd instanceof DateTimeInterface ? $consultationEnd : $evaluationStart;
    if (
        $consultationStart instanceof DateTimeInterface
        && $consultationStart <= $now
        && (
            !($consultationLimit instanceof DateTimeInterface)
            || $now < $consultationLimit
        )
        && !($evaluationStart instanceof DateTimeInterface && $evaluationStart <= $now)
    ) {
        $summary['title'] = $consultationLimit instanceof DateTimeInterface
            ? commonDecisionParticipationT('decisions.public.timeline.consultation_until', ['date' => commonDecisionParticipationFormatDateTime($consultationLimit)])
            : commonDecisionParticipationT('decisions.public.timeline.consultation_open');
        return $summary;
    }

    if (
        $consultationStart instanceof DateTimeInterface
        && $evaluationStart instanceof DateTimeInterface
        && $consultationStart <= $now
        && $now < $evaluationStart
    ) {
        $summary['title'] = commonDecisionParticipationT('decisions.public.timeline.waiting_vote', ['date' => commonDecisionParticipationFormatDateTime($evaluationStart)]);
        return $summary;
    }

    if ($evaluationStart instanceof DateTimeInterface && $now < $evaluationStart) {
        $summary['title'] = commonDecisionParticipationT('decisions.public.timeline.waiting_vote', ['date' => commonDecisionParticipationFormatDateTime($evaluationStart)]);
        return $summary;
    }

    if (
        $evaluationStart instanceof DateTimeInterface
        && $evaluationStart <= $now
        && !($evaluationEnd instanceof DateTimeInterface && $evaluationEnd <= $now)
        && !($resultsDate instanceof DateTimeInterface && $resultsDate <= $now)
    ) {
        $evaluationLimit = $evaluationEnd instanceof DateTimeInterface ? $evaluationEnd : $resultsDate;
        $summary['title'] = $evaluationLimit instanceof DateTimeInterface
            ? commonDecisionParticipationT('decisions.public.timeline.vote_until', ['date' => commonDecisionParticipationFormatDateTime($evaluationLimit)])
            : commonDecisionParticipationT('decisions.public.timeline.vote_open');
        return $summary;
    }

    if (DecisionProcess::getStatusRank($status) >= DecisionProcess::getStatusRank(DecisionProcess::STATUS_RESULTS)) {
        $summary['title'] = commonDecisionParticipationT('decisions.public.timeline.finished');
        return $summary;
    }

    if ($evaluationEnd instanceof DateTimeInterface && $evaluationEnd <= $now) {
        $summary['title'] = commonDecisionParticipationT('decisions.public.timeline.finished');
        return $summary;
    }

    return $summary;
}

function commonDecisionParticipationBuildOrganizerData($decision, $organization, array $context)
{
    $displayName = '';
    $email = '';

    if ($decision instanceof DecisionProcess) {
        $ownerUserId = (int)$decision->get('IDuser');
        if ($ownerUserId > 0) {
            $ownerUser = new \dbObject\user();
            if ($ownerUser->load($ownerUserId)) {
                $firstname = trim((string)$ownerUser->get('firstname'));
                $lastname = trim((string)$ownerUser->get('lastname'));
                $displayName = trim($firstname . ' ' . $lastname);
                if ($displayName === '') {
                    $displayName = trim((string)$ownerUser->get('username'));
                }

                $organizationId = $organization ? (int)$organization->getId() : 0;
                $email = trim((string)$ownerUser->getScopedEmail($organizationId));
            }
        }
    }

    $effectiveHolon = $context['effectiveHolon'] ?? null;
    $rootHolon = $organization && method_exists($organization, 'getEnabledStructuralRootHolon')
        ? $organization->getEnabledStructuralRootHolon()
        : null;
    $isRootHolon = $effectiveHolon instanceof Holon
        && $rootHolon instanceof Holon
        && (int)$effectiveHolon->getId() === (int)$rootHolon->getId();

    $scopeParts = [];
    if ($isRootHolon) {
        $rootHolonName = trim((string)$effectiveHolon->getDisplayName());
        $scopeParts[] = $rootHolonName !== ''
            ? $rootHolonName
            : ($organization ? trim((string)$organization->get('name')) : '');
    } else {
        if ($organization) {
            $scopeParts[] = trim((string)$organization->get('name'));
        }

        if ($effectiveHolon instanceof Holon) {
            $scopeParts[] = trim((string)$effectiveHolon->getTemplateLabel(true)) . ' ' . trim((string)$effectiveHolon->getDisplayName());
        }
    }

    return [
        'label' => $displayName,
        'email' => $email,
        'scope' => implode(', ', array_filter($scopeParts, static function ($value) {
            return trim((string)$value) !== '';
        })),
    ];
}

function commonDecisionParticipationBuildMethodSummary($decision)
{
    if (!$decision instanceof DecisionProcess) {
        return [
            'label' => commonDecisionParticipationT('decisions.public.context.method'),
            'value' => '',
        ];
    }

    $groups = commonDecisionParticipationGetRenderableGroups($decision);
    if (count($groups) === 0) {
        return [
            'label' => commonDecisionParticipationT('decisions.public.context.method'),
            'value' => '',
        ];
    }

    $parts = [];
    foreach ($groups as $group) {
        $parts[] = commonDecisionParticipationGetMethodLabel($group->get('evaluation_method'))
            . ' (' . commonDecisionParticipationGetDecisionTypeLabel($group->get('decision_type')) . ')';
    }

    return [
        'label' => count($parts) > 1
            ? commonDecisionParticipationT('decisions.public.context.methods')
            : commonDecisionParticipationT('decisions.public.context.method'),
        'value' => implode(' / ', array_values(array_unique($parts))),
    ];
}

function commonDecisionParticipationBuildInvitationSummary($decision, $organization, array $context)
{
    if (!$decision instanceof DecisionProcess) {
        return '';
    }

    $organizationName = $organization ? trim((string)$organization->get('name')) : commonDecisionParticipationT('decisions.public.default_organization');
    $holonLabels = [];
    $additionalPeopleCount = 0;

    foreach ($decision->getInvitations(true) as $invitation) {
        if (!($invitation instanceof DecisionInvitation)) {
            continue;
        }

        if (DecisionInvitation::normalizeStatus($invitation->get('status')) === DecisionInvitation::STATUS_REVOKED) {
            continue;
        }

        $type = DecisionInvitation::normalizeType($invitation->get('invitation_type'));
        if ($type === DecisionInvitation::TYPE_HOLON) {
            $holonLabel = trim((string)$invitation->get('display_name'));
            $holonId = (int)$invitation->get('IDholon');
            if ($holonId > 0) {
                $holon = new Holon();
                if ($holon->load($holonId)) {
                    $holonLabel = trim((string)$holon->getTemplateLabel(true)) . ' ' . trim((string)$holon->getDisplayName());
                }
            }

            if ($holonLabel !== '') {
                $holonLabels[] = $holonLabel;
            }
            continue;
        }

        $additionalPeopleCount++;
    }

    if (count($holonLabels) === 0) {
        $effectiveHolon = $context['effectiveHolon'] ?? null;
        if ($effectiveHolon instanceof Holon) {
            $holonLabels[] = trim((string)$effectiveHolon->getTemplateLabel(true)) . ' ' . trim((string)$effectiveHolon->getDisplayName());
        }
    }

    $summary = $organizationName;
    $holonLabels = array_values(array_unique(array_filter($holonLabels, static function ($value) {
        return trim((string)$value) !== '';
    })));
    if (count($holonLabels) > 0) {
        $summary .= ' (' . implode(', ', $holonLabels) . ')';
    }
    if ($additionalPeopleCount > 0) {
        $summary .= ' + ' . $additionalPeopleCount . ' personne' . ($additionalPeopleCount > 1 ? 's' : '');
    }

    return $summary;
}

function commonDecisionParticipationBuildOptionLines($decision, array $context)
{
    if (!$decision instanceof DecisionProcess) {
        return [];
    }

    $groups = commonDecisionParticipationGetRenderableGroups($decision);
    $lines = [];
    $status = DecisionProcess::normalizeStatus($decision->get('status'));

    if ($status === DecisionProcess::STATUS_RESULTS || $status === DecisionProcess::STATUS_ARCHIVED) {
        $lines[] = commonDecisionParticipationT('decisions.public.options.responses_locked');
    } else {
        $lines[] = commonDecisionParticipationT('decisions.public.options.responses_editable');
    }

    if (DecisionProcess::getStatusRank($status) < DecisionProcess::getStatusRank(DecisionProcess::STATUS_RESULTS)) {
        $lines[] = commonDecisionParticipationT('decisions.public.options.results_hidden');
    } else {
        $lines[] = commonDecisionParticipationT('decisions.public.options.results_visible');
    }

    $anonymousFlags = [];
    $consultationProposalFlags = [];
    $proposalDiscussionAnonymousFlags = [];
    foreach ($groups as $group) {
        $config = omoDecisionBuildMethodConfig($group);
        if (array_key_exists('is_anonymous', $config)) {
            $anonymousFlags[] = !empty($config['is_anonymous']);
        }
        if (!empty($config['allow_consultation_proposals'])) {
            $consultationProposalFlags[] = true;
        }
        if (!empty($config['allow_proposal_discussions'])) {
            $proposalDiscussionAnonymousFlags[] = !empty($config['is_anonymous']);
        }
    }

    if (count($anonymousFlags) > 0) {
        $uniqueFlags = array_values(array_unique($anonymousFlags));
        if (count($uniqueFlags) === 1) {
            $lines[] = $uniqueFlags[0]
                ? commonDecisionParticipationT('decisions.public.options.anonymous')
                : commonDecisionParticipationT('decisions.public.options.not_anonymous');
        } else {
            $lines[] = commonDecisionParticipationT('decisions.public.options.mixed_anonymity');
        }
    }

    if (count($consultationProposalFlags) > 0) {
        $lines[] = count($consultationProposalFlags) === count($groups)
            ? commonDecisionParticipationT('decisions.public.options.proposals_all')
            : commonDecisionParticipationT('decisions.public.options.proposals_some');
    }

    if (count($proposalDiscussionAnonymousFlags) > 0) {
        $allGroupsAllowDiscussions = count($proposalDiscussionAnonymousFlags) === count($groups);
        $uniqueDiscussionAnonymousFlags = array_values(array_unique($proposalDiscussionAnonymousFlags));
        $discussionLine = $allGroupsAllowDiscussions
            ? commonDecisionParticipationT('decisions.public.options.discussions_all')
            : commonDecisionParticipationT('decisions.public.options.discussions_some');

        if (count($uniqueDiscussionAnonymousFlags) === 1) {
            $discussionLine .= $uniqueDiscussionAnonymousFlags[0]
                ? commonDecisionParticipationT('decisions.public.options.discussions_anonymous')
                : commonDecisionParticipationT('decisions.public.options.discussions_optional_anonymity');
        } else {
            $discussionLine .= '.';
        }

        $lines[] = $discussionLine;
    }

    return $lines;
}

function commonDecisionParticipationRenderMajorityJudgmentLegend(DecisionGroup $group)
{
    if (!function_exists('omoDecisionMajorityJudgmentGetLegendItems')) {
        return '';
    }

    $legendItems = omoDecisionMajorityJudgmentGetLegendItems($group);
    if (count($legendItems) === 0) {
        return '';
    }

    ob_start();
    ?>
    <div class="decision-public-group__legend" style="--decision-public-group-legend-count: <?= omoApiEscape((string)count($legendItems)) ?>;">
        <?php foreach ($legendItems as $legendItem): ?>
        <?php
        $legendScore = (int)($legendItem['score'] ?? 0);
        $legendShortLabel = !empty($legendItem['is_no_opinion'])
            ? '-'
            : (string)($legendScore < 3 ? $legendScore + 1 : $legendScore);
        ?>
        <span
            class="decision-public-group__legend-item"
            style="--decision-public-group-legend-color: <?= omoApiEscape((string)$legendItem['color']) ?>; --decision-public-group-legend-text: <?= omoApiEscape((string)$legendItem['text_color']) ?>;"
            title="<?= omoApiEscape((string)$legendItem['label']) ?>"
        >
            <span class="decision-public-group__legend-label decision-public-group__legend-label--full"><?= omoApiEscape((string)$legendItem['label']) ?></span>
            <span class="decision-public-group__legend-label decision-public-group__legend-label--short" aria-hidden="true"><?= omoApiEscape($legendShortLabel) ?></span>
        </span>
        <?php endforeach; ?>
    </div>
    <?php

    return (string)ob_get_clean();
}

function commonDecisionParticipationRenderGroupBlocks(DecisionProcess $decision, array $context)
{
    $groups = commonDecisionParticipationGetRenderableGroups($decision);
    if (count($groups) === 0) {
        return '';
    }

    $registry = omoDecisionGetModuleRegistry();
    ob_start();
    ?>
    <div class="decision-public-groups">
        <?php foreach ($groups as $groupIndex => $group): ?>
            <?php
            $method = DecisionProcess::normalizeEvaluationMethod($group->get('evaluation_method'));
            $definition = $registry[$method] ?? null;
            $groupTitle = trim((string)$group->get('title'));
            if ($groupTitle === '') {
                $groupTitle = commonDecisionParticipationT('decisions.public.block', ['index' => (string)($groupIndex + 1)]);
            }
            $groupDescription = trim((string)$group->get('description'));
            $groupContext = $context;
            $groupContext['decisionGroup'] = $group;
            $groupContext['decisionGroupId'] = (int)$group->getId();
            $groupContext['method'] = $method;
            if ($definition && !empty($definition['shared_file']) && is_file((string)$definition['shared_file'])) {
                require_once (string)$definition['shared_file'];
            }
            $majorityLegend = $method === DecisionProcess::METHOD_MAJORITY_JUDGMENT
                ? commonDecisionParticipationRenderMajorityJudgmentLegend($group)
                : '';
            ?>
            <section class="generic-noborder-section generic-section--stack decision-public-group">
                <div class="generic-soft-panel-square generic-soft-panel--stack decision-public-group__header decision-public-group__header--sticky">
                    <div class="decision-public-group__header-top">
                        <span class="decision-public-group__badge">
                            <?= omoApiEscape(commonDecisionParticipationT('decisions.public.block', ['index' => (string)($groupIndex + 1)])) ?> · <?= omoApiEscape(commonDecisionParticipationGetMethodLabel($method)) ?> · <?= omoApiEscape(commonDecisionParticipationGetDecisionTypeLabel($group->get('decision_type'))) ?>
                        </span>
                    </div>
                    <h2 class="decision-public-group__title"><?= omoApiEscape($groupTitle) ?></h2>
                    <?php if ($groupDescription !== ''): ?>
                    <p class="decision-public-group__description"><?= nl2br(omoApiEscape($groupDescription)) ?></p>
                    <?php endif; ?>
                    <?php if ($majorityLegend !== ''): ?>
                    <?= $majorityLegend ?>
                    <?php endif; ?>
                </div>

                <?php if ($definition && !empty($definition['render_function'])): ?>
                    <?php
                    if (!empty($definition['editor_file']) && is_file((string)$definition['editor_file'])) {
                        require_once (string)$definition['editor_file'];
                    }

                    $groupSourceLang = [];
                    $sourceLangFunction = (string)($definition['source_lang_function'] ?? '');
                    if ($sourceLangFunction !== '' && function_exists($sourceLangFunction)) {
                        $groupSourceLang = $sourceLangFunction();
                    }
                    $groupLang = omoLoadTranslationBundle('omo_decision_group_' . $method, $groupSourceLang);
                    $renderFunction = (string)$definition['render_function'];
                    if (function_exists($renderFunction)) {
                        $renderFunction([
                            'context' => $groupContext,
                            'decision' => $decision,
                            'organization' => $context['organization'] ?? null,
                            'effectiveHolon' => $context['effectiveHolon'] ?? null,
                            'lang' => $groupLang,
                            'sourceLang' => $groupSourceLang,
                            'escape' => 'omoApiEscape',
                            'selectedMethod' => $method,
                        ]);
                    }
                    ?>
                <?php else: ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <p style="margin:0;line-height:1.6;"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.block_unavailable')) ?></p>
                </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <?php

    return (string)ob_get_clean();
}

function commonDecisionParticipationGetPublicAccessRequestDeniedMessage($reason, $allowPublicSelfRegistration = false)
{
    switch (trim((string)$reason)) {
        case 'invalid_email':
            return commonDecisionParticipationT('decisions.public.access.invalid_email');
        case 'sync_failed':
            return commonDecisionParticipationT('decisions.public.access.sync_failed');
        case 'participant_unavailable':
            return commonDecisionParticipationT('decisions.public.access.participant_unavailable');
        case 'create_failed':
            return commonDecisionParticipationT('decisions.public.access.create_failed');
        case 'not_allowed':
        default:
            return $allowPublicSelfRegistration
                ? commonDecisionParticipationT('decisions.public.access.not_allowed_open')
                : commonDecisionParticipationT('decisions.public.access.not_allowed_invited');
    }
}

function commonDecisionParticipationGetPublicAccessCodeErrorMessage($reason)
{
    switch (trim((string)$reason)) {
        case 'empty_code':
            return commonDecisionParticipationT('decisions.public.access.empty_code');
        case 'missing_code':
            return commonDecisionParticipationT('decisions.public.access.missing_code');
        case 'expired_code':
            return commonDecisionParticipationT('decisions.public.access.expired_code');
        case 'invalid_code':
            return commonDecisionParticipationT('decisions.public.access.invalid_code');
        case 'consume_failed':
            return commonDecisionParticipationT('decisions.public.access.consume_failed');
        default:
            return commonDecisionParticipationT('decisions.public.access.code_verification_failed');
    }
}

function commonDecisionParticipationBuildTimelineData(array $items, ?DecisionProcess $decision = null)
{
    $datedItems = [];
    foreach ($items as $index => $item) {
        $dateTime = commonDecisionParticipationParseDateTime($item['date'] ?? null);
        if (!$dateTime instanceof DateTimeInterface) {
            continue;
        }

        $item['date'] = $dateTime;
        $item['timestamp'] = (int)$dateTime->format('U');
        $item['lane'] = $index % 2 === 0 ? 'top' : 'bottom';
        $datedItems[] = $item;
    }

    if (count($datedItems) === 0) {
        return [
            'items' => [],
            'start' => null,
            'end' => null,
        ];
    }

    $timestamps = array_column($datedItems, 'timestamp');
    $minTimestamp = min($timestamps);
    $maxTimestamp = max($timestamps);

    if ($minTimestamp === $maxTimestamp) {
        $minTimestamp -= 3600;
        $maxTimestamp += 3600;
    }

    if ($decision instanceof DecisionProcess) {
        $dates = commonDecisionParticipationGetTimelineDates($decision);
        $hasOpenConsultation = $dates['consultation_start'] instanceof DateTimeInterface
            && !($dates['consultation_end'] instanceof DateTimeInterface)
            && !($dates['evaluation_start'] instanceof DateTimeInterface);
        $hasOpenEvaluation = $dates['evaluation_start'] instanceof DateTimeInterface
            && !($dates['evaluation_end'] instanceof DateTimeInterface)
            && !($dates['results'] instanceof DateTimeInterface);

        if ($hasOpenConsultation || $hasOpenEvaluation) {
            $rangePadding = (int)ceil(max(3600, ($maxTimestamp - $minTimestamp) * 0.1));
            $maxTimestamp += $rangePadding;
        }
    }

    $range = max(1, $maxTimestamp - $minTimestamp);
    foreach ($datedItems as $index => $item) {
        $position = 10 + ((($item['timestamp'] - $minTimestamp) / $range) * 80);
        if ($position < 10) {
            $position = 10;
        } elseif ($position > 90) {
            $position = 90;
        }

        $datedItems[$index]['position_percent'] = $position;
        $datedItems[$index]['card_align'] = 'center';
    }

    return [
        'items' => $datedItems,
        'start' => (new DateTimeImmutable())->setTimestamp($minTimestamp),
        'end' => (new DateTimeImmutable())->setTimestamp($maxTimestamp),
    ];
}

function commonDecisionParticipationBuildTimelineSegments(DecisionProcess $decision, array $timelineData)
{
    $start = $timelineData['start'] ?? null;
    $end = $timelineData['end'] ?? null;
    if (!$start instanceof DateTimeInterface || !$end instanceof DateTimeInterface) {
        return [];
    }

    $minTimestamp = (int)$start->format('U');
    $maxTimestamp = (int)$end->format('U');
    $range = max(1, $maxTimestamp - $minTimestamp);

    $buildPosition = static function ($value) use ($minTimestamp, $range) {
        $dateTime = commonDecisionParticipationParseDateTime($value);
        if (!$dateTime instanceof DateTimeInterface) {
            return null;
        }

        $position = 10 + ((((int)$dateTime->format('U') - $minTimestamp) / $range) * 80);
        if ($position < 10) {
            $position = 10;
        } elseif ($position > 90) {
            $position = 90;
        }

        return $position;
    };

    $segments = [];
    $dates = commonDecisionParticipationGetTimelineDates($decision);
    $currentPosition = $buildPosition(new DateTimeImmutable('now'));
    $openStubWidth = 10.0;
    $openDotsGap = 0.8;
    $openDotsSpacing = 1.2;
    $buildOpenDots = static function (float $startPosition) use ($openDotsGap, $openDotsSpacing) {
        $dotPositions = [];
        for ($dotIndex = 0; $dotIndex < 3; $dotIndex++) {
            $dotPosition = $startPosition + $openDotsGap + ($dotIndex * $openDotsSpacing);


            $dotPositions[] = $dotPosition;
        }

        return $dotPositions;
    };

    $consultationStart = $buildPosition($dates['consultation_start']);
    $consultationEnd = $buildPosition(
        $dates['consultation_end'] instanceof DateTimeInterface
            ? $dates['consultation_end']
            : $dates['evaluation_start']
    );
    if ($consultationStart !== null && $consultationEnd !== null && $consultationEnd > $consultationStart) {
        $segments[] = [
            'label' => commonDecisionParticipationT('decisions.public.timeline.segment_consultation'),
            'class' => 'consultation',
            'left' => $consultationStart,
            'width' => $consultationEnd - $consultationStart,
            'center' => $consultationStart + (($consultationEnd - $consultationStart) / 2),
            'open_end' => false,
        ];
    } elseif ($consultationStart !== null && !($dates['consultation_end'] instanceof DateTimeInterface)) {
        $availableWidth = max(3.5, 90 - $consultationStart);
        $openWidth = $openStubWidth;
        if ($currentPosition !== null && $currentPosition > $consultationStart) {
            $openWidth = max($openWidth, ($currentPosition - $consultationStart) + $openStubWidth);
        }

        $openWidth = min($openWidth, $availableWidth);
        $dotPositions = $buildOpenDots($consultationStart + $openWidth);
        $segments[] = [
            'label' => commonDecisionParticipationT('decisions.public.timeline.segment_consultation'),
            'class' => 'consultation',
            'left' => $consultationStart,
            'width' => $openWidth,
            'center' => $consultationStart + ($openWidth / 2),
            'open_end' => true,
            'dot_positions' => $dotPositions,
        ];
    }

    $evaluationStart = $buildPosition($dates['evaluation_start']);
    $evaluationEnd = $buildPosition(
        $dates['evaluation_end'] instanceof DateTimeInterface
            ? $dates['evaluation_end']
            : $dates['results']
    );
    if ($evaluationStart !== null && $evaluationEnd !== null && $evaluationEnd > $evaluationStart) {
        $segments[] = [
            'label' => commonDecisionParticipationT('decisions.public.timeline.segment_vote'),
            'class' => 'evaluation',
            'left' => $evaluationStart,
            'width' => $evaluationEnd - $evaluationStart,
            'center' => $evaluationStart + (($evaluationEnd - $evaluationStart) / 2),
            'open_end' => false,
        ];
    } elseif ($evaluationStart !== null && !($dates['evaluation_end'] instanceof DateTimeInterface)) {
        $availableWidth = max(3.5, 90 - $evaluationStart);
        $openWidth = $openStubWidth;
        if ($currentPosition !== null && $currentPosition > $evaluationStart) {
            $openWidth = max($openWidth, ($currentPosition - $evaluationStart) + $openStubWidth);
        }

        $openWidth = min($openWidth, $availableWidth);
        $dotPositions = $buildOpenDots($evaluationStart + $openWidth);
        $segments[] = [
            'label' => commonDecisionParticipationT('decisions.public.timeline.segment_vote'),
            'class' => 'evaluation',
            'left' => $evaluationStart,
            'width' => $openWidth,
            'center' => $evaluationStart + ($openWidth / 2),
            'open_end' => true,
            'dot_positions' => $dotPositions,
        ];
    }

    $resultsStart = $buildPosition($dates['results']);
    if ($resultsStart !== null && 90 > $resultsStart) {
        $segments[] = [
            'label' => commonDecisionParticipationT('decisions.public.timeline.segment_results'),
            'class' => 'results',
            'left' => $resultsStart,
            'width' => 90 - $resultsStart,
            'center' => $resultsStart + ((90 - $resultsStart) / 2),
            'open_end' => false,
        ];
    }

    return $segments;
}

$omoDecisionInput = $_GET;
$isEmbedded = !empty($omoDecisionInput['embedded']);
$context = omoDecisionResolveEditorContext($omoDecisionInput);
$context['previewLayout'] = true;
$decision = !empty($context['decision']) && $context['decision'] instanceof DecisionProcess
    ? $context['decision']
    : null;
$participant = !empty($context['participant']) && $context['participant'] instanceof DecisionParticipant
    ? $context['participant']
    : null;
$organization = !empty($context['organization']) ? $context['organization'] : null;
$requiresPublicAccessEmail = (($context['accessMode'] ?? '') === 'public_request');
$allowPublicSelfRegistration = $decision instanceof DecisionProcess
    ? $decision->isPublicSelfRegistrationEnabled()
    : false;

if ($requiresPublicAccessEmail && (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
    header('Content-Type: application/json; charset=UTF-8');

    if (!$decision instanceof DecisionProcess) {
        omoDecisionModuleJsonResponse(404, [
            'status' => false,
            'message' => commonDecisionParticipationT('decisions.public.access.invalid_decision'),
        ]);
    }

    $requestAction = trim((string)($_POST['public_access_action'] ?? 'request_code'));
    $requestedEmail = trim((string)($_POST['email'] ?? ''));
    if ($requestedEmail === '' || !filter_var($requestedEmail, FILTER_VALIDATE_EMAIL)) {
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => commonDecisionParticipationT('decisions.public.access.invalid_email'),
        ]);
    }

    if ($requestAction === 'verify_code') {
        $resolveResult = $decision->resolvePublicRequestParticipantByEmail($requestedEmail, false);
        if (empty($resolveResult['status']) || !($resolveResult['participant'] instanceof DecisionParticipant)) {
            omoDecisionModuleJsonResponse(403, [
                'status' => false,
                'message' => commonDecisionParticipationGetPublicAccessRequestDeniedMessage(
                    $resolveResult['reason'] ?? 'not_allowed',
                    $allowPublicSelfRegistration
                ),
            ]);
        }

        $verificationResult = $resolveResult['participant']->verifyPublicAccessCode($_POST['code'] ?? '', true);
        if (empty($verificationResult['status'])) {
            omoDecisionModuleJsonResponse(422, [
                'status' => false,
                'message' => commonDecisionParticipationGetPublicAccessCodeErrorMessage($verificationResult['reason'] ?? ''),
            ]);
        }

        $redirectIntent = $decision->isParticipationOpen() ? 'participate' : 'view';
        $redirectUrl = trim((string)$resolveResult['participant']->getPublicAccessUrl($redirectIntent));
        if ($redirectUrl === '') {
            omoDecisionModuleJsonResponse(500, [
                'status' => false,
                'message' => commonDecisionParticipationT('decisions.public.access.finalize_failed'),
            ]);
        }

        omoDecisionModuleJsonResponse(200, [
            'status' => true,
            'message' => commonDecisionParticipationT('decisions.public.access.code_valid'),
            'redirectUrl' => $redirectUrl,
        ]);
    }

    $resolveResult = $decision->resolvePublicRequestParticipantByEmail($requestedEmail, true);
    if (empty($resolveResult['status']) || !($resolveResult['participant'] instanceof DecisionParticipant)) {
        omoDecisionModuleJsonResponse(403, [
            'status' => false,
            'message' => commonDecisionParticipationGetPublicAccessRequestDeniedMessage(
                $resolveResult['reason'] ?? 'not_allowed',
                $allowPublicSelfRegistration
            ),
        ]);
    }

    $publicRequestUrl = $decision->getGenericPublicAccessUrl('participate');
    $sendResult = omoDecisionSendParticipantAccessCodeEmail($decision, $resolveResult['participant'], $publicRequestUrl);
    if (empty($sendResult['status'])) {
        omoDecisionModuleJsonResponse(500, [
            'status' => false,
            'message' => trim((string)($sendResult['message'] ?? commonDecisionParticipationT('decisions.public.access.finalize_failed'))),
        ]);
    }

    omoDecisionModuleJsonResponse(200, [
        'status' => true,
        'message' => commonDecisionParticipationT('decisions.public.access.code_sent', ['email' => trim((string)($sendResult['email'] ?? $requestedEmail))]),
        'nextAction' => 'verify_code',
    ]);
}

if ((($context['accessMode'] ?? '') === 'public') && $participant instanceof DecisionParticipant) {
    $openedAt = $participant->get('invitation_opened_at');
    $hasOpenedAt = $openedAt instanceof DateTimeInterface
        || trim((string)$openedAt) !== '';

    if (!$hasOpenedAt) {
        $participant->markInvitationOpened();
    }
}

$organizationName = $organization ? trim((string)$organization->get('name')) : commonDecisionParticipationT('decisions.public.default_organization');
$decisionTitle = $decision ? trim((string)$decision->get('title')) : commonDecisionParticipationT('decisions.public.default_title');
$decisionPublicLocale = function_exists('omoGetTranslationLocale') ? omoGetTranslationLocale() : 'fr';
$participantLabel = $participant
    ? trim((string)$participant->getIdentityLabel($organization ? (int)$organization->getId() : 0))
    : '';
$accentColor = $organization ? trim((string)$organization->get('color')) : '';
$organizationContext = commonBuildOmoPublicOrganizationContext($organization);
$publicHelpItems = commonBuildOmoPublicHelpItems('decision', $organizationName);
$publicHelpLinks = [];
$publicAdminEmail = trim((string)($GLOBALS['siteAdminEmail'] ?? ''));
if ($publicAdminEmail === '' && function_exists('envValue')) {
    $publicAdminEmail = trim((string)envValue('INSTALL_ADMIN_EMAIL', ''));
}
if ($publicAdminEmail === '' && function_exists('envValue')) {
    $publicAdminEmail = trim((string)envValue('MAIL_USER', ''));
}
if ($publicAdminEmail !== '' && filter_var($publicAdminEmail, FILTER_VALIDATE_EMAIL)) {
    $publicHelpLinks[] = [
        'label' => commonDecisionParticipationT('decisions.public.help.webmaster', ['email' => $publicAdminEmail]),
        'href' => 'mailto:' . $publicAdminEmail,
    ];
}
$publicSiteBaseHref = function_exists('envValue')
    ? trim((string)envValue('SITE_URL', ''))
    : '';
if ($publicSiteBaseHref === '') {
    $publicSiteBaseHref = '/';
}
$publicOrganizationHref = '/omo/';
if ($organization && method_exists($organization, 'getId') && (int)$organization->getId() > 0) {
    $publicOrganizationHref = commonBuildOrganizationHomeUrl(
        (int)$organization->getId(),
        (string)$organization->get('shortname'),
        commonGetRootHost()
    );
}
$decisionGroups = $decision ? commonDecisionParticipationGetRenderableGroups($decision) : [];
$timelineItems = $decision ? commonDecisionParticipationBuildTimelineItems($decision) : [];
$timelineData = commonDecisionParticipationBuildTimelineData($timelineItems, $decision);
$timelineSegments = $decision ? commonDecisionParticipationBuildTimelineSegments($decision, $timelineData) : [];
$timelineSummary = $decision ? commonDecisionParticipationBuildTimelineSummary($decision) : [
    'title' => commonDecisionParticipationT('decisions.public.timeline.title'),
    'hint' => commonDecisionParticipationT('decisions.public.timeline.hint'),
];
$timelineSummary['hint'] = commonDecisionParticipationT('decisions.public.timeline.hint');
$escape = 'omoApiEscape';
$organizerData = commonDecisionParticipationBuildOrganizerData($decision, $organization, $context);
$methodSummary = commonDecisionParticipationBuildMethodSummary($decision);
$invitationSummary = commonDecisionParticipationBuildInvitationSummary($decision, $organization, $context);
$optionLines = commonDecisionParticipationBuildOptionLines($decision, $context);
$decisionDescription = $decision instanceof DecisionProcess ? trim((string)$decision->get('description')) : '';
$decisionStatus = $decision instanceof DecisionProcess ? DecisionProcess::normalizeStatus($decision->get('status')) : '';
$isResultsDisplay = $decision instanceof DecisionProcess
    && DecisionProcess::getStatusRank($decisionStatus) >= DecisionProcess::getStatusRank(DecisionProcess::STATUS_RESULTS);
$decisionPublicOrganizerContactHtml = '';
if (trim((string)($organizerData['email'] ?? '')) !== '') {
    ob_start();
    ?>
        <section class="generic-soft-panel-square decision-public-context-contact<?= $requiresPublicAccessEmail ? ' decision-public-context-contact--footer' : '' ?>">
            <span><?= omoApiEscape(commonDecisionParticipationT('decisions.public.context.contact_organizer')) ?> </span>
            <a href="mailto:<?= omoApiEscape((string)$organizerData['email']) ?>"><?= omoApiEscape((string)$organizerData['email']) ?></a>
        </section>
    <?php
    $decisionPublicOrganizerContactHtml = (string)ob_get_clean();
}
$statusCopy = commonDecisionParticipationT('decisions.public.status.unavailable');
if (!empty($context['status'])) {
    if ($requiresPublicAccessEmail) {
        $statusCopy = $allowPublicSelfRegistration
            ? commonDecisionParticipationT('decisions.public.status.request_open')
            : commonDecisionParticipationT('decisions.public.status.request_invited');
    } else {
        $statusCopy = !empty($context['canParticipate'])
            ? commonDecisionParticipationT('decisions.public.status.can_participate')
            : '';
    }
}

ob_start();
?>
        <section class="generic-hero-panel fill accent decision-public-hero">
            <?php if (!$isResultsDisplay): ?>
            <div class="decision-public-eyebrow">
                <strong><?= omoApiEscape($organizationName !== '' ? $organizationName : commonDecisionParticipationT('decisions.public.default_organization')) ?></strong>
                <?php if ($participantLabel !== ''): ?>
                <span>&middot;</span>
                <span><?= omoApiEscape($participantLabel) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <h1><?= omoApiEscape($decisionTitle !== '' ? $decisionTitle : commonDecisionParticipationT('decisions.public.default_title')) ?></h1>
            <?php if (!$isResultsDisplay && $statusCopy !== ''): ?>
            <p class="decision-public-status"><?= omoApiEscape($statusCopy) ?></p>
            <?php endif; ?>
        </section>

        <?php if ($decisionDescription !== ''): ?>
        <section class="generic-title-section generic-section--stack decision-public-title-block">
            <p><?= nl2br(omoApiEscape($decisionDescription)) ?></p>
        </section>
        <?php endif; ?>

        <section class="generic-title-section generic-section--stack generic-accordion--card generic-accordion--collapsible is-collapsed decision-public-timeline-accordion" data-decision-public-timeline>
            <button
                type="button"
                class="decision-public-timeline-summary-button generic-accordion__header"
                data-decision-public-timeline-toggle
                aria-expanded="false"
            >
                <span class="decision-public-timeline-summary-copy">
                    <span class="decision-public-timeline-summary-title"><?= omoApiEscape((string)($timelineSummary['title'] ?? commonDecisionParticipationT('decisions.public.timeline.title'))) ?></span>
                    <span class="decision-public-timeline-summary-hint"><?= omoApiEscape((string)($timelineSummary['hint'] ?? commonDecisionParticipationT('decisions.public.timeline.hint'))) ?></span>
                </span>
                <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
            </button>
            <div class="generic-accordion__content">
                <div class="generic-accordion__content-inner">
                    <?php if (count($timelineData['items']) > 0): ?>
                    <div class="decision-public-timeline">
                        <div class="decision-public-timeline-axis">
                            <?php foreach ($timelineSegments as $timelineSegment): ?>
                            <div
                                class="decision-public-timeline-segment decision-public-timeline-segment--<?= omoApiEscape((string)$timelineSegment['class']) ?><?= !empty($timelineSegment['open_end']) ? ' is-open-ended' : '' ?>"
                                title="<?= omoApiEscape((string)$timelineSegment['label']) ?>"
                                style="left: <?= omoApiEscape(number_format((float)$timelineSegment['left'], 4, '.', '')) ?>%; width: <?= omoApiEscape(number_format((float)$timelineSegment['width'], 4, '.', '')) ?>%;"
                            ></div>
                            <?php if (!empty($timelineSegment['open_end']) && !empty($timelineSegment['dot_positions']) && is_array($timelineSegment['dot_positions'])): ?>
                            <?php foreach ($timelineSegment['dot_positions'] as $dotPosition): ?>
                            <span
                                class="decision-public-timeline-open-dot decision-public-timeline-open-dot--<?= omoApiEscape((string)$timelineSegment['class']) ?>"
                                aria-hidden="true"
                                style="left: <?= omoApiEscape(number_format((float)$dotPosition, 4, '.', '')) ?>%;"
                            ></span>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ((float)$timelineSegment['width'] >= 8.0): ?>
                            <div
                                class="decision-public-timeline-segment-label"
                                style="left: <?= omoApiEscape(number_format((float)$timelineSegment['center'], 4, '.', '')) ?>%;"
                            >
                                <?= omoApiEscape((string)$timelineSegment['label']) ?>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php foreach ($timelineData['items'] as $timelineItem): ?>
                        <?php
                        $stateClass = 'is-' . trim((string)($timelineItem['state'] ?? 'upcoming'));
                        $timelineDate = !empty($timelineItem['show_date'])
                            ? commonDecisionParticipationFormatDateTime($timelineItem['date'] ?? null)
                            : commonDecisionParticipationFormatDateTime($timelineItem['date'] ?? null);
                        ?>
                        <div
                            class="decision-public-timeline-item <?= omoApiEscape($stateClass) ?>"
                            data-lane="<?= omoApiEscape((string)($timelineItem['lane'] ?? 'top')) ?>"
                            data-align="<?= omoApiEscape((string)($timelineItem['card_align'] ?? 'center')) ?>"
                            style="left: <?= omoApiEscape(number_format((float)$timelineItem['position_percent'], 4, '.', '')) ?>%;"
                        >
                            <div class="decision-public-timeline-card">
                                <div class="decision-public-timeline-label"><?= omoApiEscape((string)$timelineItem['label']) ?></div>
                                <?php if ($timelineDate !== ''): ?>
                                <div class="decision-public-timeline-date"><?= omoApiEscape($timelineDate) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="decision-public-timeline-connector"></div>
                            <div class="decision-public-timeline-marker"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="decision-public-context">
                        <div class="decision-public-context-grid">
                            <?php if (trim((string)($organizerData['label'] ?? '')) !== ''): ?>
                            <div class="generic-soft-panel generic-soft-panel--stack">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.context.organizer')) ?></span>
                                <span class="decision-public-context-organizer">
                                    <strong><?= omoApiEscape((string)$organizerData['label']) ?></strong><?php if (trim((string)($organizerData['scope'] ?? '')) !== ''): ?>, <span class="decision-public-context-scope"><?= omoApiEscape((string)$organizerData['scope']) ?></span><?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <?php if (trim((string)($methodSummary['value'] ?? '')) !== ''): ?>
                            <div class="generic-soft-panel generic-soft-panel--stack">
                                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape((string)($methodSummary['label'] ?? commonDecisionParticipationT('decisions.public.context.method'))) ?></span>
                                <strong><?= omoApiEscape((string)($methodSummary['value'] ?? '')) ?></strong>
                            </div>
                            <?php endif; ?>

                            <?php if ($invitationSummary !== ''): ?>
                            <div class="generic-soft-panel generic-soft-panel--stack">
                                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.context.invited')) ?></span>
                                <strong><?= omoApiEscape($invitationSummary) ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($optionLines) > 0): ?>
                        <div class="generic-soft-panel generic-soft-panel--stack">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.context.options')) ?></span>
                            <ul class="decision-public-context-list">
                                <?php foreach ($optionLines as $optionLine): ?>
                                <li><?= omoApiEscape((string)$optionLine) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

<?php
$decisionPublicContextHtml = (string)ob_get_clean();

ob_start();
?>

        <section class="decision-public-content<?= $requiresPublicAccessEmail ? ' decision-public-content--centered' : '' ?>">
            <?php if ($requiresPublicAccessEmail): ?>
            <div class="decision-public-access-request-shell">
                <section class="generic-section generic-section--stack decision-public-access-request">
                    <div class="decision-public-access-request__header">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.title_eyebrow')) ?></span>
                        <span class="generic-card-title generic-card-title--section"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.title')) ?></span>
                        <p class="decision-public-access-request__text">
                            <?= $allowPublicSelfRegistration
                                ? omoApiEscape(commonDecisionParticipationT('decisions.public.access.open_description'))
                                : omoApiEscape(commonDecisionParticipationT('decisions.public.access.invited_description')) ?>
                        </p>
                    </div>
                    <form
                        class="decision-public-access-request__form"
                        id="decisionPublicAccessRequestForm"
                        action="<?= omoApiEscape((string)($_SERVER['REQUEST_URI'] ?? '')) ?>"
                        method="post"
                    >
                        <input type="hidden" name="public_access_action" id="decisionPublicAccessRequestAction" value="request_code">
                        <div class="decision-public-access-request__field">
                            <label class="generic-card-title generic-card-title--small" for="decisionPublicAccessRequestEmail"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.email')) ?></label>
                            <input
                                id="decisionPublicAccessRequestEmail"
                                name="email"
                                type="email"
                                class="generic-form-control"
                                autocomplete="email"
                                placeholder="<?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.email_placeholder')) ?>"
                                required
                            >
                        </div>
                        <div class="decision-public-access-request__field" id="decisionPublicAccessRequestCodeRow" hidden>
                            <label class="generic-card-title generic-card-title--small" for="decisionPublicAccessRequestCode"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.code')) ?></label>
                            <input
                                id="decisionPublicAccessRequestCode"
                                name="code"
                                type="text"
                                class="generic-form-control"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                placeholder="<?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.code_placeholder')) ?>"
                            >
                        </div>
                        <div id="decisionPublicAccessRequestFeedback" class="decision-public-access-request__feedback" aria-live="polite"></div>
                        <div class="decision-public-access-request__actions" id="decisionPublicAccessRequestSendActions">
                            <button type="submit" id="decisionPublicAccessRequestSendSubmit" class="generic-action-button generic-action-button--main">
                                <?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.send')) ?>
                            </button>
                        </div>
                        <div class="decision-public-access-request__actions" id="decisionPublicAccessRequestVerifyActions" hidden>
                            <button type="button" id="decisionPublicAccessRequestResend" class="generic-action-button generic-action-button--secondary">
                                <?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.resend')) ?>
                            </button>
                            <button type="submit" id="decisionPublicAccessRequestVerifySubmit" class="generic-action-button generic-action-button--main">
                                <?= omoApiEscape(commonDecisionParticipationT('decisions.public.access.enter')) ?>
                            </button>
                        </div>
                    </form>
                </section>
            </div>
            <?php else: ?>
            <?= $decision instanceof DecisionProcess ? commonDecisionParticipationRenderGroupBlocks($decision, $context) : '' ?>
            <?php endif; ?>
        </section>
        <?= $decisionPublicOrganizerContactHtml ?>
<?php
$decisionPublicMainHtml = (string)ob_get_clean();

if (empty($context['status'])) {
    http_response_code((int)($context['code'] ?? 403));
}
?>
<?php if (!$isEmbedded): ?>
<!DOCTYPE html>
<html lang="<?= omoApiEscape($decisionPublicLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= omoApiEscape($decisionTitle !== '' ? $decisionTitle : commonDecisionParticipationT('decisions.public.default_title')) ?></title>
    <script src="/shared_functions.js"></script>
    <script>if (typeof sharedApplyDocumentTheme === 'function') { sharedApplyDocumentTheme(); }</script>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/common/assets/omo_public_pages.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css?v=20260731-mobile-header-menu">
<?php endif; ?>
    <style>
        :root {
            --decision-public-accent: <?= omoApiEscape($accentColor !== '' ? $accentColor : '#2563eb') ?>;
            --omo-public-accent: var(--decision-public-accent);
            --color-primary: var(--decision-public-accent);
            --decision-public-success: var(--color-success, #16a34a);
            --decision-public-warning: var(--color-warning, #f59e0b);
            --decision-public-danger: var(--color-danger, #b42318);
        }

        body.decision-public-page {
            color: var(--color-text, #0f172a);
        }

        .decision-public-page--embedded {
            color: var(--color-text, #0f172a);
        }

        .decision-public-shell {
            --decision-public-sticky-top: 0px;
            --decision-public-sticky-gap: 0px;
            display: grid;
            gap: 16px;
        }

        .decision-public-shell--embedded {
            width: min(100%, 1100px);
            padding: 0;
        }

        .decision-public-banner {
            margin: 0;
            border: 0;
            border-radius: 0;
        }

        .decision-public-app {
            width: 100%;
        }

        .decision-public-main {
            width: 100%;
        }

        .decision-public-workspace {
            width: 100%;
        }

        .decision-public-context-panel {
            background: var(--color-surface, #ffffff);
            overflow-x: hidden;
            overflow-y: auto;
        }

        .decision-public-main-panel {
            background: var(--color-surface, #ffffff);
        }

        .decision-public-panel-scroll {
            width: 100%;
            min-width: 0;
            min-height: 0;
            display: grid;
        }

        .decision-public-panel-scroll--main {
            flex: 1 auto 1;
            overflow-y: auto;
        }

        .decision-public-panel-scroll--context {
            flex: 0 0 auto;
            min-height: 0;
            align-content: start;
            align-items: start;
            overflow: visible;
        }

        .decision-public-panel-scroll--access-request {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 100%;
        }

        .decision-public-main-summary {
            display: none;
        }

        .decision-public-title-block {
            display: grid;
            gap: 10px;
        }

        .decision-public-hero {
            display: grid;
            gap: 10px;
        }

        .decision-public-page--embedded .decision-public-hero {
            display: none;
        }

        .decision-public-hero h1 {
            margin: 0;
        }

        .decision-public-title-block p {
            margin: 0;
            color: var(--color-text-light, #475569);
            line-height: 1.6;
        }

        .decision-public-context {
            display: grid;
            gap: 12px;
            padding-top: 10px;
        }

        .decision-public-context-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .decision-public-context-list {
            margin: 0;
            padding-left: 18px;
            color: var(--color-text-light, #475569);
            line-height: 1.7;
            font-size: 13px;
        }

        .decision-public-context-grid .generic-soft-panel > strong,
        .decision-public-context-organizer {
            font-size: 13px;
        }

        .decision-public-context-contact--footer {
            margin-top: auto;
            padding-top: 10px;
            padding-bottom: 10px;
            min-height: 0;
        }

        .decision-public-context-contact a {
            color: var(--decision-public-accent);
            text-decoration: none;
            font-weight: 700;
        }

        .decision-public-context-contact a:hover {
            text-decoration: underline;
        }

        .decision-public-timeline {
            --decision-public-timeline-axis-top: 100px;
            --decision-public-timeline-card-top: 6px;
            --decision-public-timeline-card-bottom: 154px;
            --decision-public-timeline-marker-size: 16px;
            --decision-public-timeline-marker-top: calc(var(--decision-public-timeline-axis-top) - 6px);
            position: relative;
            min-height: 218px;
            padding: 8px 10px 26px;
        }

        .decision-public-timeline-accordion {
            overflow: hidden;
        }

        .decision-public-timeline-summary-button {
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            text-align: left;
            color: inherit;
            font: inherit;
        }

        .decision-public-timeline-summary-copy {
            display: grid;
            gap: 4px;
            flex: 1 1 auto;
            min-width: 0;
        }

        .decision-public-timeline-summary-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--color-text, #0f172a);
        }

        .decision-public-timeline-summary-hint {
            font-size: 13px;
            color: var(--color-text-light, #64748b);
        }

        .decision-public-timeline-accordion .generic-accordion__content {
            margin-top: 14px;
            display: block;
            overflow: hidden;
            opacity: 1;
            max-height: 1200px;
            transition: max-height 0.28s ease, opacity 0.22s ease, margin-top 0.22s ease;
        }

        .decision-public-timeline-accordion .generic-accordion__content-inner {
            min-height: 0;
        }

        .decision-public-timeline-accordion.generic-accordion--collapsible.is-collapsed .generic-accordion__content {
            display: block;
            margin-top: 0;
            opacity: 0;
            max-height: 0;
        }

        .decision-public-timeline-axis {
            position: absolute;
            left: 10px;
            right: 10px;
            top: var(--decision-public-timeline-axis-top);
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(90deg, #d7e1ec, #c9d7e6, #d7e1ec);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.85);
            overflow: visible;
        }

        .decision-public-timeline-segment {
            position: absolute;
            top: -1px;
            height: 10px;
            border-radius: 999px;
            opacity: 0.95;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }

        .decision-public-timeline-segment--consultation {
            background: linear-gradient(90deg, #facc15, #f59e0b);
        }

        .decision-public-timeline-segment--evaluation {
            background: linear-gradient(90deg, #4ade80, #16a34a);
        }

        .decision-public-timeline-segment--results {
            background: linear-gradient(90deg, #60a5fa, #2563eb);
        }

        .decision-public-timeline-segment.is-open-ended {
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }

        .decision-public-timeline-open-dot {
            position: absolute;
            top: 50%;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            transform: translate(-50%, -50%);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        .decision-public-timeline-open-dot--consultation {
            background: var(--decision-public-warning);
        }

        .decision-public-timeline-open-dot--evaluation {
            background: var(--decision-public-success);
        }

        .decision-public-timeline-segment-label {
            position: absolute;
            top: -30px;
            transform: translateX(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-surface, #ffffff) 92%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 62%, transparent);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            color: var(--color-text, #334155);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .decision-public-timeline-item {
            position: absolute;
            left: 0;
            width: 0;
            top: 0;
        }

        .decision-public-timeline-connector {
            position: absolute;
            left: 0;
            width: 2px;
            margin-left: -1px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-border, #94a3b8) 58%, var(--color-surface, #ffffff));
            opacity: 0.9;
        }

        .decision-public-timeline-item[data-lane="top"] .decision-public-timeline-connector {
            top: calc(var(--decision-public-timeline-axis-top) - 34px);
            height: 28px;
        }

        .decision-public-timeline-item[data-lane="bottom"] .decision-public-timeline-connector {
            top: calc(var(--decision-public-timeline-axis-top) + 10px);
            height: calc(140px - var(--decision-public-timeline-axis-top));
        }

        .decision-public-timeline-marker {
            position: absolute;
            left: 0;
            top: var(--decision-public-timeline-marker-top);
            width: var(--decision-public-timeline-marker-size);
            height: var(--decision-public-timeline-marker-size);
            margin-left: calc(-0.5 * var(--decision-public-timeline-marker-size));
            border-radius: 999px;
            border: 4px solid var(--color-surface, #ffffff);
            background: var(--color-border, #cbd5e1);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.12);
        }

        .decision-public-timeline-item.is-past .decision-public-timeline-marker {
            background: var(--decision-public-success);
        }

        .decision-public-timeline-item.is-current .decision-public-timeline-marker {
            background: var(--decision-public-accent);
            box-shadow:
                0 0 0 10px color-mix(in srgb, var(--decision-public-accent) 14%, transparent),
                0 8px 22px rgba(15, 23, 42, 0.16);
        }

        .decision-public-timeline-card {
            position: absolute;
            left: 0;
            width: max-content;
            display: grid;
            gap: 4px;
            text-align: center;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            background: color-mix(in srgb, var(--color-surface, #ffffff) 92%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-border, #cbd5e1) 90%, transparent);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(8px);
        }

        .decision-public-timeline-item[data-align="center"] .decision-public-timeline-card {
            transform: translateX(-50%);
        }

        .decision-public-timeline-item[data-align="start"] .decision-public-timeline-card {
            transform: translateX(0);
        }

        .decision-public-timeline-item[data-align="end"] .decision-public-timeline-card {
            transform: translateX(-100%);
        }

        .decision-public-timeline-item[data-lane="top"] .decision-public-timeline-card {
            top: var(--decision-public-timeline-card-top);
        }

        .decision-public-timeline-item[data-lane="bottom"] .decision-public-timeline-card {
            top: var(--decision-public-timeline-card-bottom);
        }

        .decision-public-timeline-label {
            font-weight: 700;
            color: var(--color-text, #0f172a);
            line-height: 1.25;
            font-size: 10px;
            white-space: nowrap;
        }

        .decision-public-timeline-date {
            color: var(--color-text-light, #64748b);
            font-size: 10px;
            white-space: nowrap;
        }

        .decision-public-content {
            display: grid;
            gap: 16px;
        }

        .decision-public-content--centered {
            flex: 1 1 auto;
            min-height: 0;
            align-content: center;
            padding: clamp(12px, 3vw, 28px);
        }

        .decision-public-access-request-shell {
            width: min(100%, 680px);
            margin-inline: auto;
        }

        .decision-public-access-request {
            --generic-section-padding-block: clamp(22px, 3vw, 30px);
            --generic-section-padding-inline: clamp(18px, 3vw, 28px);
            --generic-section-radius: var(--radius-md);
            --generic-section-border: color-mix(in srgb, var(--decision-public-accent) 16%, var(--color-border, #d1d5db));
            --generic-section-background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--decision-public-accent) 12%, transparent), transparent 42%),
                linear-gradient(180deg, color-mix(in srgb, var(--color-surface, #ffffff) 94%, transparent), var(--color-surface, #ffffff));
            --generic-section-shadow: 0 22px 44px rgba(15, 23, 42, 0.08);
            display: grid;
            gap: 18px;
        }

        .decision-public-access-request__header {
            display: grid;
            gap: 10px;
        }

        .decision-public-access-request__field {
            display: grid;
            gap: 6px;
        }

        .decision-public-access-request__text {
            margin: 0;
            color: var(--color-text-light, #475569);
            line-height: 1.6;
            max-width: 60ch;
        }

        .decision-public-access-request__form {
            display: grid;
            gap: 14px;
        }

        .decision-public-access-request__form > div#decisionPublicAccessRequestCodeRow {
            display: grid;
            gap: 6px;
        }

        .decision-public-access-request__form > div#decisionPublicAccessRequestCodeRow[hidden] {
            display: none !important;
        }

        .decision-public-access-request__actions {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .decision-public-access-request__actions .generic-action-button {
            flex: 1 1 220px;
        }

        .decision-public-access-request__actions[hidden] {
            display: none !important;
        }

        .decision-public-access-request__feedback {
            min-height: 22px;
            font-weight: 600;
            color: var(--decision-public-danger);
        }

        .decision-public-access-request__feedback.is-success {
            color: var(--decision-public-success);
        }

        .decision-public-content .omo-panel-view__body {
            padding: 0;
        }

        .decision-public-content .omo-panel-view__body_content {
            padding: 0;
        }

        .decision-public-groups {
            display: grid;
            gap: 18px;
        }

        .decision-public-group {
            gap: 14px;
        }

        .decision-public-group__header {
            gap: 10px;
        }

        .decision-public-group__legend {
            display: grid;
            grid-template-columns: repeat(var(--decision-public-group-legend-count, 1), minmax(0, 1fr));
            gap: 0;
            overflow: hidden;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--color-text-light, #64748b) 16%, var(--color-surface, #ffffff));
            background: color-mix(in srgb, var(--color-text-light, #64748b) 8%, var(--color-surface, #ffffff));
            box-shadow: inset 0 1px 0 color-mix(in srgb, var(--color-surface, #ffffff) 72%, transparent);
        }

        .decision-public-group__legend-item {
            min-width: 0;
            min-height: 34px;
            padding: 7px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: var(--decision-public-group-legend-color, var(--color-primary, #2563eb));
            color: var(--decision-public-group-legend-text, #ffffff);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
        }

        .decision-public-group__legend-item + .decision-public-group__legend-item {
            box-shadow: inset 1px 0 0 rgba(255, 255, 255, 0.28);
        }

        .decision-public-group__legend-label {
            display: block;
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        .decision-public-group__legend-label--short {
            display: none;
        }

        .decision-public-group__header--sticky {
            position: sticky;
            top: calc(var(--decision-public-sticky-top) + var(--decision-public-sticky-gap));
            z-index: 9;
            box-shadow: 0 1px 0 color-mix(in srgb, var(--color-border, #d1d5db) 80%, transparent);
        }

        .decision-public-group__header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .decision-public-group__badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--decision-public-accent) 10%, var(--color-surface, #ffffff));
            color: var(--color-text-light, #475569);
            font-size: 13px;
            font-weight: 700;
        }

        .decision-public-group__title {
            margin: 0;
            font-size: clamp(22px, 3vw, 30px);
            line-height: 1.15;
            color: var(--color-text, #0f172a);
        }

        .decision-public-group__description {
            margin: 0;
            color: var(--color-text-light, #475569);
            line-height: 1.7;
        }

        .decision-public-status {
            margin: 0;
            color: var(--color-text-light, #475569);
        }

        .decision-public-context-scope {
            color: var(--color-text-light, #64748b);
            line-height: 1.5;
        }

        @media (min-width: 769px) {
            .decision-public-context-panel .decision-public-context-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .decision-public-content--centered {
                padding: 0;
            }

            .decision-public-access-request-shell {
                width: 100%;
            }

            .decision-public-access-request {
                --generic-section-radius: var(--radius-md);
                --generic-section-padding-block: 18px;
                --generic-section-padding-inline: 16px;
                --generic-section-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            }

            .decision-public-access-request__actions .generic-action-button {
                flex-basis: 100%;
            }

            .decision-public-main-summary {
                display: grid;
                gap: 8px;
            }

            .decision-public-group__legend-item {
                min-height: 30px;
                padding: 6px 4px;
                font-size: 10px;
            }

            .decision-public-group__legend-label--full {
                display: none;
            }

            .decision-public-group__legend-label--short {
                display: block;
                white-space: nowrap;
            }

            .decision-public-timeline {
                --decision-public-timeline-axis-top: 106px;
                --decision-public-timeline-card-top: 0px;
                --decision-public-timeline-card-bottom: 166px;
                min-height: 282px;
                padding-left: 6px;
                padding-right: 6px;
            }

            .decision-public-timeline-card {
                width: max-content;
            }

            .decision-public-timeline-summary-title {
                font-size: 14px;
            }
        }

        .decision-public-page.is-resizing,
        .decision-public-page.is-resizing * {
            cursor: col-resize !important;
            user-select: none !important;
        }
    </style>
<?php if (!$isEmbedded): ?>
</head>
<body class="omo-public-body decision-public-page view-right">
<?php endif; ?>
<?php if (!$isEmbedded): ?>
    <div class="app decision-public-app">
        <div class="main decision-public-main">
            <?php
            commonRenderTopbar([
                'appKey' => 'omo-decision-public',
                'appLabel' => 'OMO',
                'organization' => $organizationContext,
                'brandHref' => $publicSiteBaseHref,
                'brandLogoHref' => $publicSiteBaseHref,
                'brandLabelHref' => $publicOrganizationHref,
                'brandLabel' => $organizationName,
                'profile' => [
                    'enabled' => false,
                ],
                'search' => [
                    'enabled' => false,
                ],
                'helpItems' => $publicHelpItems,
                'helpLinks' => $publicHelpLinks,
                'helpLabel' => commonDecisionParticipationT('decisions.public.navigation.help'),
            ]);
            ?>
            <div class="omo-public-banner decision-public-banner">
                <?= omoApiEscape(commonDecisionParticipationT('decisions.public.banner_prefix')) ?> <strong><?= omoApiEscape($organizationName !== '' ? $organizationName : commonDecisionParticipationT('decisions.public.default_organization')) ?></strong>
                <?php if ($participantLabel !== ''): ?>
                <?= omoApiEscape(commonDecisionParticipationT('decisions.public.banner_personal_access', ['participant' => $participantLabel])) ?>
                <?php endif; ?>
            </div>
            <div class="content decision-public-workspace">
                <aside class="panel panel-left decision-public-context-panel" id="panel-left">
                    <div class="decision-public-panel-scroll decision-public-panel-scroll--context">
                        <?= $decisionPublicContextHtml ?>
                    </div>
                </aside>
                <div class="resizer" id="resizer"></div>
                <section class="panel panel-right decision-public-main-panel" id="panel-right">
                    <div class="decision-public-panel-scroll decision-public-panel-scroll--main<?= $requiresPublicAccessEmail ? ' decision-public-panel-scroll--access-request' : '' ?>">
                        <?= $decisionPublicMainHtml ?>
                    </div>
                </section>
            </div>
            <nav class="mobile-nav" id="omo-mobile-nav" aria-label="<?= omoApiEscape(commonDecisionParticipationT('decisions.public.navigation.aria')) ?>">
                <button type="button" data-view="left"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.navigation.info')) ?></button>
                <button type="button" data-view="right"><?= omoApiEscape(commonDecisionParticipationT('decisions.public.navigation.decision')) ?></button>
            </nav>
        </div>
    </div>
<?php else: ?>
    <div class="omo-public-page decision-public-page decision-public-page--embedded">
        <main class="decision-public-shell decision-public-shell--embedded">
            <?= $decisionPublicContextHtml ?>
            <?= $decisionPublicMainHtml ?>
        </main>
    </div>
<?php endif; ?>
    <script>
        (function () {
            var decisionPublicTranslations = <?= json_encode([
                'defaultTitle' => commonDecisionParticipationT('decisions.public.default_title'),
                'invalidResponse' => commonDecisionParticipationT('decisions.public.js.invalid_response'),
                'proposalAddFailed' => commonDecisionParticipationT('decisions.public.js.proposal_add_failed'),
                'accessProcessFailed' => commonDecisionParticipationT('decisions.public.js.access_process_failed'),
                'codeSent' => commonDecisionParticipationT('decisions.public.js.code_sent'),
                'proposalPlaceholderPrefix' => commonDecisionParticipationT('decisions.public.js.proposal_placeholder'),
                'removeProposal' => commonDecisionParticipationT('decisions.public.js.remove_proposal'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var body = document.body;
            var leftPanel = document.getElementById('panel-left');
            var content = document.querySelector('.decision-public-workspace');
            var resizer = document.getElementById('resizer');
            var mobileNav = document.getElementById('omo-mobile-nav');
            var storageKey = 'decisionPublicLeftPanelWidth';
            var isResizing = false;

            function setView(view) {
                if (!body) {
                    return;
                }

                var resolvedView = view === 'left' ? 'left' : 'right';
                body.classList.remove('view-left', 'view-right');
                body.classList.add('view-' + resolvedView);
            }

            function getViewportWidth() {
                return window.innerWidth || document.documentElement.clientWidth || 0;
            }

            function clampWidth(width) {
                if (!content) {
                    return width;
                }

                var maxWidth = Math.floor(content.clientWidth * 0.7);
                if (maxWidth < 250) {
                    maxWidth = 250;
                }

                return Math.max(250, Math.min(width, maxWidth));
            }

            function applyWidth(width) {
                if (!leftPanel || !content || getViewportWidth() <= 768) {
                    return;
                }

                var clampedWidth = clampWidth(width);
                leftPanel.style.width = String(clampedWidth) + 'px';
                leftPanel.style.flexBasis = String(clampedWidth) + 'px';
            }

            function clearWidth() {
                if (!leftPanel) {
                    return;
                }

                leftPanel.style.width = '';
                leftPanel.style.flexBasis = '';
            }

            function stopResizing() {
                if (!isResizing) {
                    return;
                }

                isResizing = false;
                body.classList.remove('is-resizing');

                if (!leftPanel || !window.localStorage || getViewportWidth() <= 768) {
                    return;
                }

                window.localStorage.setItem(storageKey, String(Math.round(leftPanel.getBoundingClientRect().width)));
            }

            if (mobileNav) {
                mobileNav.addEventListener('click', function (event) {
                    var button = event.target.closest('button[data-view]');
                    if (!button) {
                        return;
                    }

                    setView(button.getAttribute('data-view') || 'right');
                });
            }

            if (leftPanel && content && resizer) {
                if (window.localStorage) {
                    var savedWidth = parseInt(window.localStorage.getItem(storageKey) || '', 10);
                    if (!Number.isNaN(savedWidth)) {
                        applyWidth(savedWidth);
                    }
                }

                resizer.addEventListener('mousedown', function (event) {
                    if (event.button !== 0 || getViewportWidth() <= 768) {
                        return;
                    }

                    isResizing = true;
                    body.classList.add('is-resizing');
                    event.preventDefault();
                });

                document.addEventListener('mousemove', function (event) {
                    if (!isResizing || !content) {
                        return;
                    }

                    var contentRect = content.getBoundingClientRect();
                    var nextWidth = event.clientX - contentRect.left;
                    applyWidth(nextWidth);
                });

                document.addEventListener('mouseup', stopResizing);
                window.addEventListener('blur', stopResizing);
                window.addEventListener('resize', function () {
                    if (getViewportWidth() <= 768) {
                        clearWidth();
                        return;
                    }

                    if (window.localStorage) {
                        var storedWidth = parseInt(window.localStorage.getItem(storageKey) || '', 10);
                        if (!Number.isNaN(storedWidth)) {
                            applyWidth(storedWidth);
                        }
                    }
                });
            }
        })();

        (function () {
            if (typeof window.omoRefreshDecisionView !== 'function') {
                window.omoRefreshDecisionView = function (url) {
                    var targetUrl = String(url || '').trim();
                    if (targetUrl !== '') {
                        window.location.href = targetUrl;
                    }
                };
            }

            var accordions = document.querySelectorAll('[data-decision-public-timeline]');
            var desktopMedia = typeof window.matchMedia === 'function'
                ? window.matchMedia('(min-width: 769px)')
                : null;

            function syncAccordionState() {
                for (var syncIndex = 0; syncIndex < accordions.length; syncIndex += 1) {
                    var syncAccordion = accordions[syncIndex];
                    var syncToggle = syncAccordion.querySelector('[data-decision-public-timeline-toggle]');
                    if (!syncToggle) {
                        continue;
                    }

                    if (syncAccordion.dataset.decisionPublicTimelineTouched === '1') {
                        continue;
                    }

                    syncAccordion.classList.add('is-collapsed');
                    syncToggle.setAttribute('aria-expanded', 'false');
                }
            }

            for (var index = 0; index < accordions.length; index += 1) {
                var accordion = accordions[index];
                var toggle = accordion.querySelector('[data-decision-public-timeline-toggle]');
                if (!toggle) {
                    continue;
                }

                toggle.addEventListener('click', function () {
                    var parentAccordion = this.closest('[data-decision-public-timeline]');
                    if (!parentAccordion) {
                        return;
                    }

                    parentAccordion.dataset.decisionPublicTimelineTouched = '1';
                    var isCollapsed = parentAccordion.classList.toggle('is-collapsed');
                    this.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                });
            }

            syncAccordionState();
            if (desktopMedia) {
                if (typeof desktopMedia.addEventListener === 'function') {
                    desktopMedia.addEventListener('change', syncAccordionState);
                } else if (typeof desktopMedia.addListener === 'function') {
                    desktopMedia.addListener(syncAccordionState);
                }
            }
        })();

        (function () {
            var forms = document.querySelectorAll('[data-omo-decision-consultation-proposal-form]');

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setFeedback(form, type, message) {
                var container = form.querySelector('[data-omo-decision-consultation-proposal-feedback]');
                if (!container) {
                    return;
                }

                if (!message) {
                    container.hidden = true;
                    container.innerHTML = '';
                    return;
                }

                if (typeof window.commonNotify === 'function') {
                    window.commonNotify(String(message), type || 'error');
                    container.hidden = true;
                    container.innerHTML = '';
                    return;
                }

                var tint = 'var(--color-warning, #f59e0b)';
                if (type === 'success') {
                    tint = 'var(--color-success, #16a34a)';
                } else if (type === 'error') {
                    tint = 'var(--color-danger, #dc2626)';
                }

                container.hidden = false;
                container.innerHTML = ''
                    + '<div class="generic-soft-panel generic-soft-panel--stack"'
                    + ' style="background:color-mix(in srgb, ' + tint + ' 10%, var(--color-surface, #ffffff));'
                    + 'border-color:color-mix(in srgb, ' + tint + ' 28%, var(--color-surface, #ffffff));">'
                    + '<p style="margin:0;line-height:1.5;">' + escapeHtml(message) + '</p>'
                    + '</div>';
            }

            function showPendingConsultationProposalNotifications() {
                var notifications = document.querySelectorAll('[data-omo-decision-consultation-proposal-notification]');
                var handledByTopbar = false;
                for (var notificationIndex = 0; notificationIndex < notifications.length; notificationIndex += 1) {
                    var notification = notifications[notificationIndex];
                    var message = String(notification.getAttribute('data-omo-decision-consultation-proposal-notification-message') || '');
                    var type = String(notification.getAttribute('data-omo-decision-consultation-proposal-notification-type') || 'warning');

                    if (message === '') {
                        notification.remove();
                        continue;
                    }

                    if (typeof window.commonNotify === 'function') {
                        window.commonNotify(message, type);
                        notification.remove();
                        handledByTopbar = true;
                        continue;
                    }

                    notification.hidden = false;
                }

                if (handledByTopbar && typeof window.history !== 'undefined' && typeof window.history.replaceState === 'function') {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('consultation_proposal_status');
                    currentUrl.searchParams.delete('consultation_proposal_count');
                    window.history.replaceState({}, document.title, currentUrl.pathname + currentUrl.search + currentUrl.hash);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showPendingConsultationProposalNotifications, { once: true });
            } else {
                showPendingConsultationProposalNotifications();
            }

            function setSubmitting(form, isSubmitting) {
                var submitButtons = form.querySelectorAll('button[type="submit"]');
                for (var buttonIndex = 0; buttonIndex < submitButtons.length; buttonIndex += 1) {
                    submitButtons[buttonIndex].disabled = !!isSubmitting;
                }
            }

            function reloadDecisionView(form, redirectUrl) {
                var targetUrl = String(redirectUrl || form.getAttribute('data-omo-decision-return-url') || '').trim();
                if (targetUrl === '') {
                    return;
                }

                var drawerTitleNode = document.getElementById('commonTopbarDrawerTitle');
                var drawerTitle = drawerTitleNode ? String(drawerTitleNode.textContent || '').trim() : '';
                if (typeof window.omoRefreshDecisionView === 'function') {
                    window.omoRefreshDecisionView(targetUrl, {
                        title: drawerTitle || decisionPublicTranslations.defaultTitle,
                        source: 'consultation_proposal'
                    });
                    return;
                }

                var isEmbeddedTarget = /(?:\?|&)embedded=1(?:&|$)/.test(targetUrl);
                var drawer = document.getElementById('commonTopbarDrawer');
                if (
                    typeof window.commonTopbarOpenDrawer === 'function'
                    && (
                        isEmbeddedTarget
                        || (drawer && !drawer.hidden)
                    )
                ) {
                    window.commonTopbarOpenDrawer(drawerTitle || decisionPublicTranslations.defaultTitle, targetUrl, 'fetch');
                    return;
                }

                window.location.href = targetUrl;
            }

            function refreshRows(list) {
                var rows = list.querySelectorAll('[data-omo-decision-consultation-proposal-row]');
                for (var rowIndex = 0; rowIndex < rows.length; rowIndex += 1) {
                    var input = rows[rowIndex].querySelector('input[name="consultation_proposals[]"]');
                    var removeButton = rows[rowIndex].querySelector('[data-omo-decision-consultation-proposal-remove]');
                    if (input) {
                        input.setAttribute('placeholder', decisionPublicTranslations.proposalPlaceholderPrefix + ' ' + String(rowIndex + 1));
                    }
                    if (removeButton) {
                        removeButton.disabled = rows.length <= 1;
                    }
                }
            }

            function buildRow(list) {
                var row = document.createElement('div');
                row.style.display = 'grid';
                row.style.gridTemplateColumns = 'minmax(0,1fr) auto';
                row.style.gap = '8px';
                row.style.alignItems = 'start';
                row.setAttribute('data-omo-decision-consultation-proposal-row', '');

                var input = document.createElement('input');
                input.type = 'text';
                input.name = 'consultation_proposals[]';
                input.className = 'generic-form-control';
                input.value = '';

                var removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'generic-action-button generic-action-button--secondary';
                removeButton.textContent = decisionPublicTranslations.removeProposal;
                removeButton.setAttribute('data-omo-decision-consultation-proposal-remove', '');

                row.appendChild(input);
                row.appendChild(removeButton);
                list.appendChild(row);
                refreshRows(list);
                input.focus();
            }

            for (var formIndex = 0; formIndex < forms.length; formIndex += 1) {
                var form = forms[formIndex];
                var list = form.querySelector('[data-omo-decision-consultation-proposal-list]');
                var addButton = form.querySelector('[data-omo-decision-consultation-proposal-add]');
                if (list) {
                    refreshRows(list);
                }

                if (list && addButton) {
                    addButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        var targetForm = this.closest('[data-omo-decision-consultation-proposal-form]');
                        if (!targetForm) {
                            return;
                        }

                        var targetList = targetForm.querySelector('[data-omo-decision-consultation-proposal-list]');
                        if (!targetList) {
                            return;
                        }

                        buildRow(targetList);
                    });
                }

                if (list) {
                    list.addEventListener('click', function (event) {
                        var removeButton = event.target.closest('[data-omo-decision-consultation-proposal-remove]');
                        if (!removeButton) {
                            return;
                        }

                        var targetList = this;
                        var rows = targetList.querySelectorAll('[data-omo-decision-consultation-proposal-row]');
                        if (rows.length <= 1) {
                            return;
                        }

                        var row = removeButton.closest('[data-omo-decision-consultation-proposal-row]');
                        if (!row) {
                            return;
                        }

                        row.remove();
                        refreshRows(targetList);
                    });
                }

                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    var targetForm = this;
                    setFeedback(targetForm, '', '');
                    setSubmitting(targetForm, true);

                    var formData = new FormData(targetForm);
                    fetch(targetForm.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'fetch'
                        }
                    })
                        .then(function (response) {
                            return response.json().catch(function () {
                                return {
                                    status: false,
                                    feedbackStatus: 'error',
                                    message: decisionPublicTranslations.invalidResponse,
                                    redirectUrl: ''
                                };
                            });
                        })
                        .then(function (payload) {
                            setSubmitting(targetForm, false);

                            if (payload && payload.status) {
                                reloadDecisionView(targetForm, payload.redirectUrl || '');
                                return;
                            }

                            var feedbackType = payload && payload.feedbackStatus ? payload.feedbackStatus : 'error';
                            if (feedbackType !== 'success' && feedbackType !== 'warning') {
                                feedbackType = feedbackType === 'empty' || feedbackType === 'duplicate' ? 'warning' : 'error';
                            }

                            setFeedback(
                                targetForm,
                                feedbackType,
                                payload && payload.message ? payload.message : decisionPublicTranslations.proposalAddFailed
                            );
                        })
                        .catch(function () {
                            setSubmitting(targetForm, false);
                            setFeedback(targetForm, 'error', decisionPublicTranslations.proposalAddFailed);
                        });
                });
            }

            var accessRequestForm = document.getElementById('decisionPublicAccessRequestForm');
            var accessRequestAction = document.getElementById('decisionPublicAccessRequestAction');
            var accessRequestEmail = document.getElementById('decisionPublicAccessRequestEmail');
            var accessRequestCodeRow = document.getElementById('decisionPublicAccessRequestCodeRow');
            var accessRequestCode = document.getElementById('decisionPublicAccessRequestCode');
            var accessRequestSendActions = document.getElementById('decisionPublicAccessRequestSendActions');
            var accessRequestVerifyActions = document.getElementById('decisionPublicAccessRequestVerifyActions');
            var accessRequestFeedback = document.getElementById('decisionPublicAccessRequestFeedback');
            var accessRequestSendSubmit = document.getElementById('decisionPublicAccessRequestSendSubmit');
            var accessRequestVerifySubmit = document.getElementById('decisionPublicAccessRequestVerifySubmit');
            var accessRequestResend = document.getElementById('decisionPublicAccessRequestResend');

            function setAccessRequestMode(mode) {
                var verifyMode = mode === 'verify_code';
                if (accessRequestAction) {
                    accessRequestAction.value = verifyMode ? 'verify_code' : 'request_code';
                }
                if (accessRequestCodeRow) {
                    accessRequestCodeRow.hidden = !verifyMode;
                }
                if (accessRequestCode) {
                    accessRequestCode.required = verifyMode;
                    if (!verifyMode) {
                        accessRequestCode.value = '';
                    }
                }
                if (accessRequestSendActions) {
                    accessRequestSendActions.hidden = verifyMode;
                }
                if (accessRequestVerifyActions) {
                    accessRequestVerifyActions.hidden = !verifyMode;
                }
            }

            function setAccessRequestSubmitting(isSubmitting) {
                if (accessRequestSendSubmit) {
                    accessRequestSendSubmit.disabled = !!isSubmitting;
                }
                if (accessRequestVerifySubmit) {
                    accessRequestVerifySubmit.disabled = !!isSubmitting;
                }
                if (accessRequestResend) {
                    accessRequestResend.disabled = !!isSubmitting;
                }
            }

            function submitAccessRequest(action) {
                if (!accessRequestForm || !accessRequestFeedback) {
                    return;
                }

                if (accessRequestAction) {
                    accessRequestAction.value = action === 'verify_code' ? 'verify_code' : 'request_code';
                }

                accessRequestFeedback.textContent = '';
                accessRequestFeedback.classList.remove('is-success');
                setAccessRequestSubmitting(true);

                fetch(accessRequestForm.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    body: new FormData(accessRequestForm),
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return {
                                ok: response.ok,
                                data: data
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data || !result.data.status) {
                            accessRequestFeedback.textContent = result.data && result.data.message
                                ? result.data.message
                                : decisionPublicTranslations.accessProcessFailed;
                            setAccessRequestSubmitting(false);
                            return;
                        }

                        accessRequestFeedback.textContent = result.data.message || decisionPublicTranslations.codeSent;
                        accessRequestFeedback.classList.add('is-success');

                        if (result.data && result.data.redirectUrl) {
                            window.location.href = String(result.data.redirectUrl);
                            return;
                        }

                        if (result.data && result.data.nextAction === 'verify_code') {
                            setAccessRequestMode('verify_code');
                            if (accessRequestCode) {
                                accessRequestCode.focus();
                                accessRequestCode.select();
                            }
                        } else {
                            accessRequestForm.reset();
                            setAccessRequestMode('request_code');
                        }

                        setAccessRequestSubmitting(false);
                    })
                    .catch(function () {
                        accessRequestFeedback.textContent = decisionPublicTranslations.accessProcessFailed;
                        setAccessRequestSubmitting(false);
                    });
            }

            if (accessRequestForm && accessRequestFeedback && accessRequestSendSubmit && accessRequestVerifySubmit) {
                setAccessRequestMode('request_code');
                accessRequestForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitAccessRequest(accessRequestAction ? accessRequestAction.value : 'request_code');
                });

                accessRequestSendSubmit.addEventListener('click', function () {
                    if (accessRequestAction) {
                        accessRequestAction.value = 'request_code';
                    }
                });

                accessRequestVerifySubmit.addEventListener('click', function () {
                    if (accessRequestAction) {
                        accessRequestAction.value = 'verify_code';
                    }
                });

                if (accessRequestResend) {
                    accessRequestResend.addEventListener('click', function () {
                        submitAccessRequest('request_code');
                    });
                }
            }
        })();
    </script>
<?php if (!$isEmbedded): ?>
</body>
</html>
<?php endif; ?>
