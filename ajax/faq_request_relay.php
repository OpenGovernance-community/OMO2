<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/faq_mail.php");
require_once("../omo/api/lms/inc/access.php");

header('Content-Type: application/json; charset=utf-8');

function faqRequestRelayRespond(array $payload, $status = 200)
{
	http_response_code((int)$status);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ((string)($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Methode non autorisee.'), 405);
}
if (!checklogin()) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Login requis.'), 401);
}
if (!\dbObject\FAQ::hasRequestRelayColumn()) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'La migration du relais des demandes FAQ n’est pas entièrement appliquée.'), 503);
}

$faqId = (int)($_GET['id'] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);
if ($faqId <= 0 || $faqContext === false) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Contexte FAQ invalide.'), 400);
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || (int)$faq->getId() <= 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'FAQ introuvable.'), 404);
}
$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
if (!$faq->canRelayRequest()) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Vous n’avez pas le droit de relayer cette question.'), 403);
}
if (!$faq->isPendingRequest()) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Seule une question en attente peut être relayée.'), 422);
}
if ($faq->hasRequestBeenRelayed()) {
	faqRequestRelayRespond(array('success' => true, 'message' => 'Cette question a déjà été relayée aux administrateurs de l’organisation.'));
}

$organization = $faq->getResolvedOrganization();
$organizationId = $organization instanceof \dbObject\Organization ? (int)$organization->getId() : 0;
if ($organizationId <= 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Cette question n’est rattachée à aucune organisation.'), 422);
}

$recipients = \dbObject\FAQ::getOrganizationAdminEmails($organizationId, (int)($viewerAccess['userId'] ?? 0));
if (count($recipients) === 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Aucun autre administrateur actif de cette organisation n’a d’adresse e-mail valide.'), 422);
}

$faqUrl = rtrim(commonBuildOrganizationHomeUrl(
	$organizationId,
	(string)$organization->get('shortname'),
	commonGetRootHost()
), '/') . '/#|faq-' . (int)$faq->getId();
$mailFrom = trim((string)($GLOBALS['mailUser'] ?? ''));
if ($mailFrom === '') {
	$mailFrom = 'info@systemdd.ch';
}
$brand = faqMailBrandOptions($organization);
$mailBody = faqMailRenderOrganizationRelay($faq, $faqUrl, $organization);
$sentCount = 0;
foreach ($recipients as $recipient) {
	if (myHTMLMail(array($mailFrom, (string)$brand['brand_name']), $recipient, 'Question FAQ à traiter', $mailBody)) {
		$sentCount++;
	}
}
if ($sentCount <= 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'La question n’a pas pu être relayée par e-mail.'), 502);
}

$faq->set('request_relayed_at', date('Y-m-d H:i:s'));
$saveResult = $faq->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
	faqRequestRelayRespond(array('success' => false, 'message' => 'Les administrateurs ont été informés, mais le relais n’a pas pu être enregistré.'), 500);
}

faqRequestRelayRespond(array(
	'success' => true,
	'message' => 'Question relayée à ' . $sentCount . ' administrateur' . ($sentCount > 1 ? 's' : '') . ' de l’organisation.',
));
