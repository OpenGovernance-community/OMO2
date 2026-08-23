<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../omo/api/lms/inc/access.php");
require_once("../common/faq_popup_helper.php");

if (!checklogin()) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Login requis',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faqId = (int)($_GET['id'] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

if ($faqId <= 0 || $faqContext === false) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Contexte FAQ invalide.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || !(int)$faq->getId()) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'FAQ introuvable.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$faq->canBeDeletedInContext($faqContext ?: array())) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Vous n avez pas le droit de supprimer cette FAQ.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$faq->delete()) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Impossible de supprimer cette FAQ.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$popupReloadUrl = '/popup/faq.php';
$popupReloadQuery = array();
if ((int)($faqContext['organizationId'] ?? 0) > 0) {
	$popupReloadQuery[] = 'oid=' . rawurlencode((string)$faqContext['organizationId']);
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

echo json_encode(array(
	'status' => true,
	'success' => true,
	'message' => 'FAQ supprimee.',
	'reloadUrl' => $popupReloadUrl,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
