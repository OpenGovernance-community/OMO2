<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/faq_mail.php");
require_once("../common/faq_popup_helper.php");

faqPopupRequirePostRequest();

if (!checklogin()) {
	http_response_code(401);
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'Login requis',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}
$responderRequest = \dbObject\FAQ::loadRequestForResponder((int)($_GET['id'] ?? 0));
if (!$responderRequest) require_once("../omo/api/lms/inc/access.php");

$faqId = (int)($_GET['id'] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET, $responderRequest);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

if ($faqId <= 0 || $faqContext === false) {
	http_response_code(403);
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'Contexte FAQ invalide.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$faq = $responderRequest ?? new \dbObject\FAQ();
if ((!$responderRequest && !$faq->load($faqId)) || !(int)$faq->getId()) {
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => 'FAQ introuvable.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$isPendingRequest = $faq->isPendingRequest();
if (!empty($_POST['faq_answer_request']) && !$isPendingRequest) {
	http_response_code(409);
	echo json_encode(['status' => false, 'success' => false, 'message' => faqPopupT('request.already_answered')], JSON_UNESCAPED_UNICODE);
	exit;
}
if (!$faq->canAnswerRequest() && !$faq->canBeEditedInContext($faqContext ?: array())) {
	http_response_code(403);
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => "Vous n'avez pas le droit d'editer cette FAQ.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$viewerAccess = $isPendingRequest ? \dbObject\FAQ::resolveRequestViewerAccess($faqContext) : \dbObject\FAQ::resolveViewerAccess($faqContext);
$canManageFaqCollection = !empty($viewerAccess['canManageAllFaqs']) || !empty($viewerAccess['canManageOrganizationFaqs']);
$canManageParcoursFaqs = \dbObject\FAQ::canManageParcoursInContext($faqContext ?: array(), (int)($viewerAccess['userId'] ?? 0), false);
$publishAnswer = $isPendingRequest && (string)($_POST['faq_publish_answer'] ?? '') === '1';
$originalScope = [];
foreach (['IDorganization', 'IDholon', 'IDparcours', 'IDapplication'] as $field) $originalScope[$field] = $faq->get($field);
$requestOrganization = $faq->getResolvedOrganization();
$preservedScope = ['status' => true, 'organizationId' => $faq->get('IDorganization'), 'holonId' => $faq->get('IDholon'), 'parcoursId' => $faq->get('IDparcours')];
$requesterEmail = trim((string)$faq->get('request_author_email'));
if ($isPendingRequest && !filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
	http_response_code(422);
	echo json_encode(['status' => false, 'success' => false, 'message' => faqPopupT('request.email_required')], JSON_UNESCAPED_UNICODE);
	exit;
}

// Ordinary editors change content, never the attachment or application.
$scope = $isPendingRequest
	? ($publishAnswer ? \dbObject\FAQ::resolveRequestPublicationScope($_POST) : $preservedScope)
	: (!$canManageFaqCollection ? array(
		'status' => true,
		'organizationId' => $faq->get('IDorganization'),
		'holonId' => $faq->get('IDholon'),
		'parcoursId' => $faq->get('IDparcours'),
	) : faqPopupResolveSubmittedScope($faqContext ?: array(), $_POST, array(
		'allowParcoursCreate' => $canManageParcoursFaqs,
	)));
if (empty($scope['status'])) {
	if ($isPendingRequest) http_response_code(403);
	echo json_encode([
		'status' => false,
		'success' => false,
		'message' => $isPendingRequest ? faqPopupT('request.scope_denied') : (string)($scope['message'] ?? "Impossible de resoudre le scope de la FAQ."),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$data = $_POST;
unset($data['id']);
unset($data['request_ai_draft']);
$requestAlreadyAnswered = $faq->hasRequestBeenAnswered();
$linkedApplicationId = $isPendingRequest ? ($publishAnswer ? ($scope['applicationId'] ?? 0) : (int)$faq->get('IDapplication')) : 0;
if (!$isPendingRequest && \dbObject\FAQ::hasApplicationColumn() && $canManageFaqCollection) {
	$linkedApplicationId = isset($data['IDapplication']) && is_numeric($data['IDapplication'])
		? (int)$data['IDapplication']
		: 0;
}
unset($data['IDapplication']);
$previousScope = [(int)$faq->getResolvedOrganizationId(), (int)$faq->get('IDholon'), (int)$faq->get('IDparcours')];
$faq->set('IDorganization', $scope['organizationId'] ?? null);
$faq->set('IDholon', $scope['holonId'] ?? null);
$faq->set('IDparcours', $scope['parcoursId'] ?? null);
$nextScope = [(int)$faq->getResolvedOrganizationId(), (int)$faq->get('IDholon'), (int)$faq->get('IDparcours')];
if (!$isPendingRequest && $previousScope !== $nextScope && !$faq->canBeCreatedInContext($faqContext)) {
	http_response_code(403);
	echo json_encode(['status' => false, 'success' => false, 'message' => faqPopupT('request.create_denied')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}
$faq->loadEditorInput($isPendingRequest ? array_intersect_key($data, array_flip(['question', 'answer', 'detail', 'image', 'video'])) : $data);
if ($isPendingRequest) $faq->set('isactive', $publishAnswer);
if (\dbObject\FAQ::hasApplicationColumn() && ($canManageFaqCollection || $isPendingRequest)) {
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

$notifyRequester = $isPendingRequest && (int)$faq->get('request_user_id') > 0
	&& !$requestAlreadyAnswered
	&& trim((string)$faq->get('answer')) !== '';
if ($notifyRequester) {
	$faq->set('request_answered_at', date('Y-m-d H:i:s'));
}
if (\dbObject\FAQ::hasAiDraftColumn()) {
	$faq->set('request_ai_draft', false);
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
if ($notifyRequester && filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
	$mailFrom = trim((string)($GLOBALS['mailUser'] ?? ''));
	if ($mailFrom === '') $mailFrom = 'info@systemdd.ch';
	$organization = $requestOrganization;
	$requesterNotified = myHTMLMail(
		[$mailFrom, faqMailBrandOptions($organization)['brand_name']],
		$requesterEmail,
		'Réponse à votre question dans la FAQ',
		faqMailRenderAnswer($faq, $organization)
	);
	if (!$requesterNotified) {
		$faq->set('request_answered_at', null);
		$faq->set('isactive', false);
		foreach ($originalScope as $field => $value) $faq->set($field, $value);
		$restoreResult = $faq->save();
		if (empty($restoreResult['status'])) error_log('FAQ answer: could not restore pending request ' . (int)$faq->getId());
		http_response_code(502);
		echo json_encode(['status' => false, 'success' => false, 'message' => faqPopupT('request.email_failed')], JSON_UNESCAPED_UNICODE);
		exit;
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

$focusId = !($isPendingRequest && !$publishAnswer) && $faq->canBeViewedInContext($faqContext ?: array(), $faqScope)
	? (int)$faq->getId()
	: null;

$script = "if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('" . $popupReloadUrl . "'); }";

echo json_encode([
	'status' => true,
	'success' => true,
	'message' => $requesterNotified ? faqPopupT($publishAnswer ? 'request.replied_published' : 'request.replied') : 'FAQ mise à jour.',
	'reloadUrl' => $popupReloadUrl,
	'focusId' => $focusId,
	'script' => $script,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
