<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\PropertyFormat;
use dbObject\Rule;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function ($success, $message, $statusCode = 200) { http_response_code($statusCode); echo json_encode(['success' => (bool)$success, 'message' => (string)$message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; };
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') $respond(false, omoPolicyT('policy.error.method'), 405);
$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$currentHolonId = isset($_POST['cid']) && is_numeric($_POST['cid']) ? (int)$_POST['cid'] : 0;
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status']) || !omoPolicyCanCreateLocalRule($context)) $respond(false, omoPolicyT('policy.error.forbidden'), 403);
$rule = new Rule();
$rule->set('IDholon', (int)$context['currentHolon']->getId());
$rule->set('title', trim((string)($_POST['title'] ?? '')));
$rule->set('intention', PropertyFormat::sanitizeHtml((string)($_POST['intention'] ?? '')));
$rule->set('description', PropertyFormat::sanitizeHtml((string)($_POST['description'] ?? '')));
$rule->set('review_date', (string)($_POST['review_date'] ?? ''));
$rule->set('expiration_date', (string)($_POST['expiration_date'] ?? ''));
$result = $rule->save();
if (!is_array($result) || empty($result['status'])) $respond(false, (string)($result['text'] ?? omoPolicyT('policy.error.save')), 422);
$respond(true, omoPolicyT('policy.success.save'));
