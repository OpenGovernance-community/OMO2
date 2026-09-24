<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");

header('Content-Type: application/json; charset=utf-8');

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
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Methode non autorisee.'), 405);
}
if (!faqQuestionRequestSameOrigin()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Requete refusee.'), 403);
}
if (!checkLogin()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Connectez-vous pour poser une question.'), 401);
}

$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);
$context = \dbObject\FAQ::resolvePopupRequestContext($_GET);
if ($context === false || $currentUserId <= 0 || !\dbObject\FAQ::hasFaqTable()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Contexte FAQ indisponible.'), 400);
}
if (!\dbObject\FAQ::hasRequestColumns()) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'La migration des demandes FAQ n’est pas entièrement appliquée. Contactez l’administrateur.'), 503);
}

$question = trim((string)($_POST['question'] ?? ''));
$description = trim((string)($_POST['request_description'] ?? ''));
if ($question === '' || mb_strlen($question, 'UTF-8') > 255 || $description === '' || mb_strlen($description, 'UTF-8') > 10000) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Saisissez une question et une description (255 et 10 000 caractères maximum).'), 422);
}

$user = new \dbObject\User();
if (!$user->load($currentUserId)) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Votre compte est introuvable.'), 400);
}
$organizationId = (int)($context['organizationId'] ?? 0);
$authorEmail = trim((string)$user->getScopedEmail($organizationId));
$authorName = trim((string)$user->getScopedDisplayName($organizationId));
if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Ajoutez une adresse e-mail valide à votre profil pour recevoir la réponse.'), 422);
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
	faqQuestionRequestRespond(array('success' => false, 'message' => 'Impossible d’enregistrer votre question.'), 500);
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
$escape = function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$mailBody = '<p>Une nouvelle question attend une réponse dans l’administration de la FAQ.</p>'
	. '<p><strong>Question :</strong> ' . $escape($question) . '</p>'
	. '<p><strong>Description :</strong><br>' . nl2br($escape($description)) . '</p>'
	. '<p><strong>Auteur :</strong> ' . $escape($authorName) . ' (' . $escape($authorEmail) . ')</p>'
	. '<p><a href="' . $escape($faqUrl) . '">Ouvrir la question dans la FAQ</a></p>';
$emailSent = false;
foreach ($adminEmails as $adminEmail) {
	if (myHTMLMail($mailFrom, $adminEmail, 'Nouvelle question dans la FAQ', $mailBody)) {
		$emailSent = true;
	}
}

faqQuestionRequestRespond(array(
	'success' => true,
	'message' => $emailSent
		? 'Votre question a bien été envoyée. Vous recevrez la réponse à l’adresse associée à votre compte.'
		: (count($adminEmails) === 0
			? 'Votre question est enregistrée, mais aucun administrateur du site actif n’a d’adresse e-mail valide.'
			: 'Votre question est enregistrée. La notification par e-mail à l’administrateur n’a pas pu être envoyée.'),
	'id' => (int)$faq->getId(),
));
