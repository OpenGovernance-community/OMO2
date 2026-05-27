<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");

if (!checklogin()) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'Login requis',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION["currentUser"] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);

if ($currentUserId <= 0 || $faqContext === false) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'Contexte FAQ invalide.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$contextHolon = $faqContext['currentHolon'] ?? null;
$contextOrganizationId = (int)($faqContext['organizationId'] ?? 0);
if (
	!$contextHolon
	|| !\dbObject\FAQ::canCreateContextualForHolon($contextHolon, $currentUserId, $contextOrganizationId)
) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => "Vous n'avez pas le droit d'ajouter une FAQ dans ce contexte.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faq = new \dbObject\FAQ();
$data = $_POST;
unset($data['id']);
$faq->loadFromArray($data);
$faq->set('IDholon', (int)$contextHolon->getId());
$faq->set('isactive', true);

if (trim((string)$faq->get('question')) === '' || trim((string)$faq->get('answer')) === '') {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'La question et la reponse courte sont obligatoires.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$saveResult = $faq->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => is_array($saveResult) && !empty($saveResult['text']) ? (string)$saveResult['text'] : "Impossible d'enregistrer cette FAQ.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$popupReloadUrl = '/popup/faq.php?oid=' . rawurlencode((string)$contextOrganizationId) . '&cid=' . rawurlencode((string)$contextHolon->getId());

echo json_encode([
	'status' => true,
	'success' => true,
	'message' => 'FAQ enregistree.',
	'script' => "if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('" . $popupReloadUrl . "'); }",
	'id' => $saveResult['id'] ?? ('0' . (int)$faq->getId()),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
