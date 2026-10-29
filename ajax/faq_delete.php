<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../omo/api/lms/inc/access.php");
require_once("../common/faq_popup_helper.php");

faqPopupRequirePostRequest();

if (!checklogin()) {
	http_response_code(401);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => faqPopupT('login_requis'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faqId = (int)($_GET['id'] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

if ($faqId <= 0 || $faqContext === false) {
	http_response_code(403);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => faqPopupT('contexte_faq_invalide'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || !(int)$faq->getId()) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => faqPopupT('faq_introuvable'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$faq->canBeDeletedInContext($faqContext ?: array())) {
	http_response_code(403);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => faqPopupT('vous_n_avez_pas_le_droit_de_supprimer_cette_faq'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$faq->delete()) {
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => faqPopupT('impossible_de_supprimer_cette_faq'),
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
	'message' => faqPopupT('faq_supprimee'),
	'reloadUrl' => $popupReloadUrl,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
