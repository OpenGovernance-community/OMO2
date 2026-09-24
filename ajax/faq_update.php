<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../omo/api/lms/inc/access.php");
require_once("../common/faq_popup_helper.php");

if (!checklogin()) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'Login requis',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faqId = (int)($_GET['id'] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

if ($faqId <= 0 || $faqContext === false) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'Contexte FAQ invalide.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || !(int)$faq->getId()) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'FAQ introuvable.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$faq->canBeEditedInContext($faqContext ?: array())) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => "Vous n'avez pas le droit d'editer cette FAQ.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
$canManageFaqCollection = !empty($viewerAccess['canManageAllFaqs']) || !empty($viewerAccess['canManageOrganizationFaqs']);
$canManageParcoursFaqs = \dbObject\FAQ::canManageParcoursInContext($faqContext ?: array(), (int)($viewerAccess['userId'] ?? 0), false);

// Ordinary editors change content, never the attachment or application.
$scope = !$canManageFaqCollection ? array(
	'status' => true,
	'organizationId' => $faq->get('IDorganization'),
	'holonId' => $faq->get('IDholon'),
	'parcoursId' => $faq->get('IDparcours'),
) : faqPopupResolveSubmittedScope($faqContext ?: array(), $_POST, array(
	'allowParcoursCreate' => $canManageParcoursFaqs,
));
if (empty($scope['status'])) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => (string)($scope['message'] ?? "Impossible de resoudre le scope de la FAQ."),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$data = $_POST;
unset($data['id']);
$previousAnsweredAt = trim((string)$faq->get('request_answered_at'));
$linkedApplicationId = 0;
if (\dbObject\FAQ::hasApplicationColumn() && $canManageFaqCollection) {
	$linkedApplicationId = isset($data['IDapplication']) && is_numeric($data['IDapplication'])
		? (int)$data['IDapplication']
		: 0;
}
unset($data['IDapplication']);
$faq->loadFromArray($data);
$faq->set('IDorganization', $scope['organizationId'] ?? null);
$faq->set('IDholon', $scope['holonId'] ?? null);
$faq->set('IDparcours', $scope['parcoursId'] ?? null);
if (\dbObject\FAQ::hasApplicationColumn() && $canManageFaqCollection) {
	if ($linkedApplicationId > 0) {
		$application = new \dbObject\Application();
		if (!$application->load($linkedApplicationId) || (int)$application->getId() <= 0) {
			echo json_encode([
				'status' => false,
				'success' => false,
				'message' => 'Application invalide.',
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}

		$faq->set('IDapplication', $linkedApplicationId);
	} else {
		$faq->set('IDapplication', null);
	}
}

if (trim((string)$faq->get('question')) === '' || trim((string)$faq->get('answer')) === '') {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'La question et la reponse courte sont obligatoires.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$notifyRequester = (int)$faq->get('request_user_id') > 0
	&& $previousAnsweredAt === ''
	&& trim((string)$faq->get('answer')) !== '';
if ($notifyRequester) {
	$faq->set('request_answered_at', date('Y-m-d H:i:s'));
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

$requesterNotified = false;
$requesterEmail = trim((string)$faq->get('request_author_email'));
if ($notifyRequester && filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
	$mailFrom = trim((string)($GLOBALS['mailUser'] ?? ''));
	if ($mailFrom === '') $mailFrom = 'info@systemdd.ch';
	$requesterNotified = myHTMLMail(
		$mailFrom,
		$requesterEmail,
		'Réponse à votre question dans la FAQ',
		'<p>Bonjour ' . htmlspecialchars((string)$faq->get('request_author_name'), ENT_QUOTES, 'UTF-8') . ',</p>'
		. '<p>Un administrateur a répondu à votre question :</p>'
		. '<p><strong>' . htmlspecialchars((string)$faq->get('question'), ENT_QUOTES, 'UTF-8') . '</strong></p>'
		. '<p>' . nl2br(htmlspecialchars((string)$faq->get('answer'), ENT_QUOTES, 'UTF-8')) . '</p>'
		. (trim(strip_tags((string)$faq->get('detail'))) !== '' ? '<div>' . (string)$faq->get('detail') . '</div>' : '')
	);
	if (!$requesterNotified) {
		$faq->set('request_answered_at', null);
		$faq->save();
	}
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

$focusId = $faq->canBeViewedInContext($faqContext ?: array(), $faqScope)
	? (int)$faq->getId()
	: null;

$script = "if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('" . $popupReloadUrl . "'); }";

echo json_encode([
	'status' => true,
	'success' => true,
	'message' => $notifyRequester && !$requesterNotified
		? 'FAQ mise à jour, mais l’e-mail à la personne qui a posé la question n’a pas pu être envoyé.'
		: ($requesterNotified ? 'Réponse enregistrée et envoyée par e-mail.' : 'FAQ mise à jour.'),
	'reloadUrl' => $popupReloadUrl,
	'focusId' => $focusId,
	'script' => $script,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
