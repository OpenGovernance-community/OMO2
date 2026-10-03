<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/common.php';
require_once dirname(__DIR__, 5) . '/common/notification_center.php';

use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

function omoDecisionConsultationProposalIsAjaxRequest()
{
    if ((int)($_POST['ajax'] ?? 0) === 1) {
        return true;
    }

    $requestedWith = trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    if ($requestedWith !== '') {
        return mb_strtolower($requestedWith, 'UTF-8') === 'fetch';
    }

    $accept = trim((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return strpos($accept, 'application/json') !== false;
}

function omoDecisionConsultationProposalNormalizeReturnUrl($value, array $context)
{
    $value = trim((string)$value);
    if ($value !== '' && strpos($value, '/') === 0) {
        return $value;
    }

    return omoDecisionBuildContextualEditorUrl($context, 'view');
}

function omoDecisionConsultationProposalBuildRedirectUrl(array $context, $status, $count = 0)
{
    $baseUrl = omoDecisionConsultationProposalNormalizeReturnUrl($_POST['return_url'] ?? '', $context);
    $query = [];

    $existingQuery = parse_url($baseUrl, PHP_URL_QUERY);
    if (is_string($existingQuery) && $existingQuery !== '') {
        parse_str($existingQuery, $query);
    }

    $query['consultation_proposal_status'] = trim((string)$status);
    if ((int)$count > 0) {
        $query['consultation_proposal_count'] = (int)$count;
    } else {
        unset($query['consultation_proposal_count']);
    }

    $path = parse_url($baseUrl, PHP_URL_PATH);
    $fragment = parse_url($baseUrl, PHP_URL_FRAGMENT);
    $rebuilt = is_string($path) && $path !== '' ? $path : '/common/decision_participation.php';
    if ($query !== []) {
        $rebuilt .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
    if (is_string($fragment) && $fragment !== '') {
        $rebuilt .= '#' . $fragment;
    }

    return $rebuilt;
}

function omoDecisionConsultationProposalRespond(array $context, $status, $count = 0, $statusCode = 200)
{
    $redirectUrl = omoDecisionConsultationProposalBuildRedirectUrl($context, $status, $count);
    if (omoDecisionConsultationProposalIsAjaxRequest()) {
        $deniedReason = trim((string)($context['consultationProposalDeniedReason'] ?? ''));
        $messages = [
            'success' => (int)$count > 1
                ? $count . ' propositions ajoutees a la consultation.'
                : 'Proposition ajoutee a la consultation.',
            'duplicate' => 'Toutes les propositions soumises existent deja.',
            'empty' => 'Ajoutez au moins une proposition.',
            'denied' => omoDecisionGetConsultationProposalDeniedMessage($deniedReason),
            'error' => 'Impossible d ajouter la proposition pour le moment.',
        ];
        omoDecisionModuleJsonResponse($statusCode, [
            'status' => $status === 'success',
            'feedbackStatus' => trim((string)$status),
            'count' => (int)$count,
            'message' => (string)($messages[$status] ?? 'Impossible d ajouter la proposition pour le moment.'),
            'redirectUrl' => $redirectUrl,
            'reason' => $deniedReason,
        ]);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

function omoDecisionConsultationProposalRedirect(array $context, $status, $count = 0)
{
    omoDecisionConsultationProposalRespond($context, $status, $count);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (omoDecisionConsultationProposalIsAjaxRequest()) {
        omoDecisionModuleJsonResponse(405, [
            'status' => false,
            'feedbackStatus' => 'error',
            'message' => 'Methode non autorisee.',
            'redirectUrl' => '',
        ]);
    }

    http_response_code(405);
    echo 'Methode non autorisee.';
    exit;
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    if (omoDecisionConsultationProposalIsAjaxRequest()) {
        omoDecisionModuleJsonResponse((int)($context['code'] ?? 403), [
            'status' => false,
            'feedbackStatus' => 'error',
            'message' => 'Accès refusé.',
            'redirectUrl' => '',
        ]);
    }

    http_response_code((int)($context['code'] ?? 403));
    echo 'Acces refuse.';
    exit;
}

$decision = $context['decision'] ?? null;
$availability = omoDecisionGetConsultationProposalAvailability($decision, $context);
if (!$decision instanceof DecisionProcess || empty($availability['allowed'])) {
    $context['consultationProposalDeniedReason'] = (string)($availability['reason'] ?? 'invalid_decision');
    omoDecisionConsultationProposalRedirect($context, 'denied');
}

$decisionGroup = ($context['decisionGroup'] ?? null) instanceof \dbObject\DecisionGroup
    ? $context['decisionGroup']
    : $decision->getPrimaryGroup(false);
$methodConfig = omoDecisionBuildMethodConfig($decisionGroup instanceof \dbObject\DecisionGroup ? $decisionGroup : $decision);
$proposalContent = omoDecisionNormalizeProposalContent($methodConfig['proposal_content'] ?? null);
$proposalTitle = $proposalContent['title'] ? trim((string)($_POST['consultation_proposal_title'] ?? '')) : '';
$proposalDescription = $proposalContent['description'] ? trim((string)($_POST['consultation_proposal_description'] ?? '')) : '';
$proposalInfoUrl = $proposalContent['url']
    ? omoDecisionNormalizeProposalInfoUrl($_POST['consultation_proposal_info_url'] ?? '')
    : null;
$range = DecisionProposal::normalizeCalendarRange($proposalContent['date'] ? ($_POST['consultation_proposal_start_at'] ?? null) : null, $proposalContent['date'] ? ($_POST['consultation_proposal_end_at'] ?? null) : null, $proposalContent['date'] ? ($_POST['consultation_proposal_timezone'] ?? null) : null);
if (empty($range['status'])) omoDecisionModuleJsonResponse(422, $range);
if ($proposalTitle === '' && $proposalDescription === '' && $proposalInfoUrl === null && empty($range['values']['start_at'])) {
    omoDecisionConsultationProposalRedirect($context, 'empty');
}

$rangeKey = static fn($start, $end) => ($start instanceof DateTimeInterface ? $start->format('Y-m-d H:i:s') : '') . '/' . ($end instanceof DateTimeInterface ? $end->format('Y-m-d H:i:s') : '');
$existingTitles = [];
$maxPosition = 0;
foreach ($decision->getProposals(false) as $proposal) {
    if (!$proposal instanceof DecisionProposal) {
        continue;
    }

    $maxPosition = max($maxPosition, (int)$proposal->get('position'));
    if ((int)$proposal->get('active') !== 1) {
        continue;
    }

    $normalizedTitle = omoApiNormalizeLabel(trim((string)$proposal->get('title')));
    if ($normalizedTitle !== '') {
        $existingTitles[$normalizedTitle . '/' . $rangeKey($proposal->get('start_at'), $proposal->get('end_at'))] = true;
    }
}

$normalizedTitle = omoApiNormalizeLabel($proposalTitle);
if ($normalizedTitle !== '' && isset($existingTitles[$normalizedTitle . '/' . $rangeKey($range['values']['start_at'], $range['values']['end_at'])])) {
    omoDecisionConsultationProposalRedirect($context, 'duplicate');
}

$participant = $context['participant'] ?? null;
$participantId = $participant ? (int)$participant->getId() : 0;
$authorUserId = omoDecisionGetContextAccountUserId($context);
$createdCount = 0;
$decisionGroup = $decision->ensurePrimaryGroup();
if (!$decisionGroup || (int)$decisionGroup->getId() <= 0) {
    omoDecisionConsultationProposalRedirect($context, 'error');
}

$maxPosition++;
$proposal = new DecisionProposal();
$proposal->set('IDdecision_process', (int)$decision->getId());
$proposal->set('IDdecision_group', (int)$decisionGroup->getId());
$proposal->set('IDuser_author', $authorUserId > 0 ? $authorUserId : null);
$proposal->set('title', $proposalTitle !== '' ? $proposalTitle : null);
$proposal->set('description', $proposalDescription !== '' ? $proposalDescription : null);
$proposal->set('info_url', $proposalInfoUrl);
foreach ($range['values'] as $field => $value) $proposal->set($field, $value);
$proposal->set('position', $maxPosition);
$proposal->set('parameters', [
    'source' => 'consultation_public',
    'added_by_participant_id' => $participantId,
    'added_at' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
]);
$proposal->set('active', 1);

$saveResult = $proposal->save();
if (empty($saveResult['status'])) {
    $dbError = \dbObject\DbObject::getLastDbError();
    if (is_array($dbError) && !empty($dbError['message'])) {
        error_log('decision_consultation_add_save_error: ' . json_encode($dbError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    omoDecisionConsultationProposalRedirect($context, 'error');
}

$createdCount++;
try {
    notificationCenterDispatchDecisionProposal($proposal);
} catch (\Throwable $exception) {
    error_log('decision_proposal_notification_failed: ' . $exception->getMessage());
}

omoDecisionConsultationProposalRedirect($context, 'success', $createdCount);
