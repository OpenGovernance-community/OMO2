<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Authority;
use dbObject\Rule;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function ($success, $message, $statusCode = 200) { http_response_code($statusCode); echo json_encode(['success' => (bool)$success, 'message' => (string)$message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; };
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') $respond(false, omoPolicyT('policy.error.method'), 405);
$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$currentHolonId = isset($_POST['cid']) && is_numeric($_POST['cid']) ? (int)$_POST['cid'] : 0;
$ruleId = isset($_POST['rule_id']) && is_numeric($_POST['rule_id']) ? (int)$_POST['rule_id'] : 0;
$action = strtolower(trim((string)($_POST['action'] ?? 'save')));
$rule = new Rule();
if ($ruleId > 0) {
    if (!$rule->load($ruleId)) $respond(false, omoPolicyT('policy.error.load'), 404);
    $ruleHolon = $rule->getHolon();
    if (!($ruleHolon instanceof \dbObject\Holon)) $respond(false, omoPolicyT('policy.error.load'), 404);
    $currentHolonId = (int)$ruleHolon->getId();
    if (!$rule->canEdit()) $respond(false, omoPolicyT('policy.error.forbidden'), 403);
}
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status']) || !omoPolicyCanCreateLocalRule($context)) $respond(false, omoPolicyT('policy.error.forbidden'), 403);
if ($action === 'delete') {
    if ($ruleId <= 0 || !$rule->delete()) $respond(false, omoPolicyT('policy.error.delete'), 422);
    $respond(true, omoPolicyT('policy.success.delete'));
}
$authorityId = isset($_POST['authority_id']) && is_numeric($_POST['authority_id'])
    ? (int)$_POST['authority_id']
    : ($ruleId > 0 ? (int)$rule->get('IDauthority') : 0);
if ($authorityId > 0) {
    $authority = new Authority();
    if (!$authority->load($authorityId) || (int)$authority->get('IDholon') !== (int)$context['currentHolon']->getId()) {
        $respond(false, omoPolicyT('policy.error.authority'), 422);
    }
    $rule->set('IDauthority', $authorityId);
} else {
    $rule->set('IDholon', (int)$context['currentHolon']->getId());
}
$rule->set('title', trim((string)($_POST['title'] ?? '')));
$intention = Rule::sanitizeContentHtml((string)($_POST['intention'] ?? ''));
$rule->set('intention', $intention !== '' ? $intention : null);
$rule->set('description', Rule::sanitizeContentHtml((string)($_POST['description'] ?? '')));
$rule->set('review_date', (string)($_POST['review_date'] ?? ''));
$rule->set('expiration_date', (string)($_POST['expiration_date'] ?? ''));
$result = $rule->save();
if (!is_array($result) || empty($result['status'])) $respond(false, (string)($result['text'] ?? omoPolicyT('policy.error.save')), 422);
$respond(true, omoPolicyT($ruleId > 0 ? 'policy.success.update' : 'policy.success.save'));
