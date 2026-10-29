<?php
require_once __DIR__ . '/../common/faq_popup_helper.php';
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/faq_mail.php");
require_once("../common/faq_ai.php");
require_once("../omo/api/lms/inc/access.php");

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function faqQuestionRequestRespond(array $payload, $status = 200)
{
	http_response_code((int)$status);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function faqQuestionRequestSameOrigin()
{
	$currentHost = strtolower((string)preg_replace('/:\\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
	foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $key) {
		$value = trim((string)($_SERVER[$key] ?? ''));
		if ($value === '') continue;
		$requestHost = strtolower((string)parse_url($value, PHP_URL_HOST));
		return $currentHost === '' || $requestHost === $currentHost;
	}
	return true;
}

if ((string)($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('methode_non_autorisee')), 405);
}
if (!faqQuestionRequestSameOrigin()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('requete_refusee')), 403);
}
if (!checkLogin()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('connectez_vous_pour_poser_une_question')), 401);
}

$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);
$context = \dbObject\FAQ::resolvePopupRequestContext($_GET);
if ($context === false || $currentUserId <= 0 || !\dbObject\FAQ::hasFaqTable()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('contexte_faq_indisponible')), 400);
}
$question = trim((string)($_POST['question'] ?? ''));
$description = trim((string)($_POST['request_description'] ?? ''));
if ($question === '' || mb_strlen($question, 'UTF-8') > 255 || $description === '' || mb_strlen($description, 'UTF-8') > 10000) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('saisissez_une_question_et_une_description_255_et_10_000_caracteres_maximum')), 422);
}
$action = (string)($_POST['action'] ?? 'search');
if (!in_array($action, ['search', 'send_admin'], true)) {
	faqQuestionRequestRespond(['success' => false, 'message' => faqAiT('error')], 422);
}
$organizationId = (int)($context['organizationId'] ?? 0);
if ($action === 'search') {
	commonAiSetUsageContext($currentUserId, $organizationId, 'faq_search');
	$answer = ['answer' => '', 'sources' => []];
	try {
		if (commonAiUserCanUse($currentUserId, commonOpenAiGetRewriteModel())) {
			$scope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $context);
			$answer = faqAiFindAnswer($question, $description, \dbObject\FAQ::loadAiSources($context, $scope), null, $currentUserId);
		}
	} catch (\Throwable $exception) {
		error_log('FAQ AI search unavailable (' . get_class($exception) . ').');
	}
	if ($answer['answer'] !== '') {
		// A successful search is not a pending request and sends no administrator email.
		faqQuestionRequestRespond(['success' => true, 'status' => 'answered'] + $answer);
	}
}
if (!\dbObject\FAQ::hasRequestColumns()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('la_migration_des_demandes_faq_n_est_pas_entierement_appliquee_contactez_l_administrateur')), 503);
}

$user = new \dbObject\User();
if (!$user->load($currentUserId)) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('votre_compte_est_introuvable')), 400);
}
$authorEmail = trim((string)$user->getScopedEmail($organizationId));
$authorName = trim((string)$user->getScopedDisplayName($organizationId));
if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('ajoutez_une_adresse_e_mail_valide_a_votre_profil_pour_recevoir_la_reponse')), 422);
}

$faq = new \dbObject\FAQ();
$faq->set('question', $question);
$faq->set('answer', '');
$faq->set('request_description', $description);
$faq->set('request_user_id', $currentUserId);
$faq->set('request_author_name', $authorName);
$faq->set('request_author_email', $authorEmail);
$faq->set('IDorganization', $organizationId > 0 ? $organizationId : null);
$currentHolon = $context['currentHolon'] ?? null;
$faq->set('IDholon', $currentHolon instanceof \dbObject\Holon ? (int)$currentHolon->getId() : null);
if (\dbObject\FAQ::hasParcoursColumn()) $faq->set('IDparcours', null);
if (\dbObject\FAQ::hasApplicationColumn()) $faq->set('IDapplication', null);
$faq->set('isactive', false);
$saveResult = $faq->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
	$dbError = \dbObject\FAQ::getLastDbError();
	if (is_array($dbError)) {
		error_log('FAQ question request save failed (SQLSTATE ' . (int)($dbError['code'] ?? 0) . '): ' . trim((string)($dbError['message'] ?? 'database write error')));
	}
	faqQuestionRequestRespond(array('success' => false, 'message' => faqPopupT('impossible_d_enregistrer_votre_question')), 500);
}

$adminEmails = \dbObject\FAQ::getSystemAdminEmails();
$mailFrom = trim((string)($GLOBALS['mailUser'] ?? ''));
if ($mailFrom === '') $mailFrom = 'info@systemdd.ch';
$organization = $context['organization'] ?? null;
$faqBaseUrl = $organization instanceof \dbObject\Organization
	? commonBuildOrganizationHomeUrl($organizationId, (string)$organization->get('shortname'), commonGetRootHost())
	: appBuildAbsoluteUrl('/omo/');
$faqUrl = rtrim($faqBaseUrl, '/');
$rootHolon = $context['rootHolon'] ?? null;
$currentHolonId = $currentHolon instanceof \dbObject\Holon ? (int)$currentHolon->getId() : 0;
$rootHolonId = $rootHolon instanceof \dbObject\Holon ? (int)$rootHolon->getId() : 0;
if ($currentHolonId > 0 && $currentHolonId !== $rootHolonId) {
	$faqUrl .= '/c/' . $currentHolonId;
}
$faqUrl .= '/#|faq-' . (int)$faq->getId();
$mailBody = faqMailRenderQuestionRequest($faq, $faqUrl, $organization);
$emailSent = false;
foreach ($adminEmails as $adminEmail) {
	if (myHTMLMail($mailFrom, $adminEmail, 'Nouvelle question dans la FAQ', $mailBody)) {
		$emailSent = true;
	}
}

faqQuestionRequestRespond(array(
	'success' => true,
	'status' => 'submitted',
	'message' => $emailSent
		? faqAiT('sent')
		: (count($adminEmails) === 0
			? 'Votre question est enregistrée, mais aucun administrateur du site actif n’a d’adresse e-mail valide.'
			: 'Votre question est enregistrée. La notification par e-mail à l’administrateur n’a pas pu être envoyée.'),
	'id' => (int)$faq->getId(),
));
