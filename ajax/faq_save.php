<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/faq_popup_helper.php");

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
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

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
$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
$canManageFaqCollection = !empty($viewerAccess['canManageAllFaqs']) || !empty($viewerAccess['canManageOrganizationFaqs']);
$canCreateContextualFaq = $contextHolon
	? \dbObject\FAQ::canCreateContextualForHolon($contextHolon, $currentUserId, $contextOrganizationId, false)
	: false;

if (!$canManageFaqCollection && !$canCreateContextualFaq) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => "Vous n'avez pas le droit d'ajouter une FAQ dans ce contexte.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$scope = faqPopupResolveSubmittedScope($faqContext ?: array(), $_POST, array(
	'allowContextualCreate' => $canCreateContextualFaq,
));
if (empty($scope['status'])) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => (string)($scope['message'] ?? "Impossible de resoudre le scope de la FAQ."),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faq = new \dbObject\FAQ();
$data = $_POST;
unset($data['id']);
$faq->loadFromArray($data);
$faq->set('IDorganization', $scope['organizationId'] ?? null);
$faq->set('IDholon', $scope['holonId'] ?? null);
if (!$canManageFaqCollection) {
	$faq->set('isactive', true);
}

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

$popupReloadUrl = '/popup/faq.php';
$popupReloadQuery = array();
if ($contextOrganizationId > 0) {
	$popupReloadQuery[] = 'oid=' . rawurlencode((string)$contextOrganizationId);
}
if ((int)($faqContext['currentHolonId'] ?? 0) > 0) {
	$popupReloadQuery[] = 'cid=' . rawurlencode((string)$faqContext['currentHolonId']);
}
if ($faqScope !== 'contextual') {
	$popupReloadQuery[] = 'faq_scope=' . rawurlencode($faqScope);
}
if (count($popupReloadQuery) > 0) {
	$popupReloadUrl .= '?' . implode('&', $popupReloadQuery);
}

echo json_encode([
	'status' => true,
	'success' => true,
	'message' => 'FAQ enregistree.',
	'reloadUrl' => $popupReloadUrl,
	'script' => "if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('" . $popupReloadUrl . "'); }",
	'id' => $saveResult['id'] ?? ('0' . (int)$faq->getId()),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
