<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') omoDecisionModuleJsonResponse(405, ['status' => false, 'message' => 'Methode non autorisee.']);
$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status']) || empty($context['canManage'])) omoDecisionModuleJsonResponse(403, ['status' => false, 'message' => 'Acces refuse.']);
$decision = $context['decision'];
$decision->syncLifecycleStatus();
$proposal = omoDecisionLoadProposalForContext((int)($_POST['proposal_id'] ?? 0), $context, true);
if (!$proposal) omoDecisionModuleJsonResponse(404, ['status' => false, 'message' => 'Proposition introuvable.']);
$result = $decision->setCalendarProposalStatus($proposal, (string)($_POST['calendar_status'] ?? ''));
$otherStatuses = [];
$calendars = [];
foreach ($proposal->getDecisionGroup()->getProposals(true) as $other) {
    $otherStatuses[(int)$other->getId()] = $other->getCalendarData()['calendarStatus'];
    $calendars[(int)$other->getId()] = omoDecisionRenderProposalCalendar($other, $context, 'omoApiEscape');
}
$statusLabels = [];
foreach (['option', 'confirmed', 'cancelled'] as $status) $statusLabels[$status] = omoDecisionProposalT('decisions.proposals.dates.' . $status);
omoDecisionModuleJsonResponse(empty($result['status']) ? 422 : 200, $result + ['proposal' => $proposal->getCalendarData(), 'otherStatuses' => $otherStatuses, 'statusLabels' => $statusLabels, 'calendars' => $calendars]);
