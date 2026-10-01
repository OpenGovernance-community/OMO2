<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/modules/context.php';
require_once dirname(__DIR__) . '/params/shared.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 4) . '/common/patreon.php';
require_once dirname(__DIR__, 4) . '/common/openai_text.php';

use dbObject\DecisionProcess;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(405, ['status' => false, 'message' => 'Méthode non autorisée.']);
}
$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    $respond((int)($context['code'] ?? 403), ['status' => false, 'message' => 'Accès refusé.']);
}
$decision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$currentUserId = (int)($context['currentUserId'] ?? 0);
$settings = omoDecisionParamsGetConfig($context['organization'] ?? null);
if ((int)($context['targetHolonId'] ?? 0) <= 0
    || ($decision instanceof DecisionProcess
        ? (!$decision->isGovernanceWorkflow()
            || (int)$decision->get('IDuser') !== $currentUserId
            || $decision->hasConsultationEnded())
        : (empty($context['canCreate']) || empty($settings['governance']['enabled'])))) {
    $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
}
if (commonOpenAiGetApiKey() === '' || !patreonUserCanUseAi($currentUserId)) {
    $respond(403, ['status' => false, 'message' => omoDecisionGovernanceT('governance.proposal.summary.unavailable')]);
}

$raw = (string)($_POST['modifications'] ?? '');
if ($raw === '' || strlen($raw) > 50000) {
    $respond(422, ['status' => false, 'message' => omoDecisionGovernanceT('governance.proposal.summary.empty')]);
}
$input = json_decode($raw, true);
if (!is_array($input) || !array_is_list($input) || count($input) < 1 || count($input) > 50) {
    $respond(422, ['status' => false, 'message' => omoDecisionGovernanceT('governance.proposal.summary.empty')]);
}
$clean = static function ($value): string {
    if (is_array($value)) $value = implode('; ', array_map(static fn ($item): string => is_scalar($item) ? (string)$item : '', $value));
    if (!is_scalar($value)) return '';
    $text = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return mb_substr(trim((string)preg_replace('/\s+/u', ' ', $text)), 0, 500);
};
$modifications = [];
foreach ($input as $item) {
    if (!is_array($item)) continue;
    $fields = [];
    foreach (array_slice((array)($item['fields'] ?? []), 0, 40) as $field) {
        if (!is_array($field)) continue;
        $fields[] = [
            'champ' => $clean($field['name'] ?? ''),
            'avant' => $clean($field['before'] ?? ''),
            'après' => $clean($field['after'] ?? ''),
        ];
    }
    $modifications[] = ['action' => $clean($item['action'] ?? ''), 'champs' => $fields];
}
if (!$modifications) {
    $respond(422, ['status' => false, 'message' => omoDecisionGovernanceT('governance.proposal.summary.empty')]);
}
$result = commonOpenAiSummarizeGovernanceChanges($modifications, omoGetTranslationLocale());
if (empty($result['status'])) {
    $respond(422, ['status' => false, 'message' => omoDecisionGovernanceT('governance.proposal.summary.failed')]);
}
$respond(200, ['status' => true, 'text' => (string)$result['text']]);
