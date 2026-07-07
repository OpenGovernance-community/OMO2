<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/faq_popup_helper.php");

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('faqVoteJsonResponse')) {
	function faqVoteJsonResponse(array $payload, $statusCode = 200)
	{
		http_response_code((int)$statusCode);
		echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
}

if (!function_exists('faqVoteIsSameOriginRequest')) {
	function faqVoteIsSameOriginRequest()
	{
		$currentHost = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
		if ($currentHost === '') {
			return true;
		}

		foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $serverKey) {
			$value = trim((string)($_SERVER[$serverKey] ?? ''));
			if ($value === '') {
				continue;
			}

			$requestHost = strtolower((string)parse_url($value, PHP_URL_HOST));
			if ($requestHost === '') {
				continue;
			}

			return $requestHost === $currentHost;
		}

		return true;
	}
}

if ((string)($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'Methode non autorisee.',
	), 405);
}

if (!faqVoteIsSameOriginRequest()) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'Requete refusee.',
	), 403);
}

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$faqId = isset($_POST['faq_id']) && is_numeric($_POST['faq_id'])
	? (int)$_POST['faq_id']
	: 0;
$vote = strtolower(trim((string)($_POST['vote'] ?? '')));
$faqContext = \dbObject\FAQ::resolvePopupRequestContext(array(
	'oid' => $_POST['oid'] ?? 0,
	'cid' => $_POST['cid'] ?? 0,
));
$faqScope = \dbObject\FAQ::normalizePopupScope($_POST['faq_scope'] ?? null, $faqContext ?: array());

if ($faqId <= 0) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'FAQ invalide.',
	), 400);
}

if ($vote !== 'up' && $vote !== 'down') {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'Vote invalide.',
	), 400);
}

if (!\dbObject\FAQ::hasVoteColumns()) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'Le schema de vote FAQ n est pas disponible.',
	), 500);
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || !(int)$faq->getId()) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'FAQ introuvable.',
	), 404);
}

if (!$faq->canBeViewedInContext($faqContext ?: array(), $faqScope)) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'Cette FAQ n est pas disponible.',
	), 403);
}

$today = date('Y-m-d');
$lastVoteDate = faqPopupGetVoteSessionDate($faqId);
if ($lastVoteDate === $today) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'alreadyVoted' => true,
		'faqId' => $faqId,
		'message' => 'Vous avez deja vote aujourd hui. Vous pourrez revoter demain.',
		'positiveScore' => (float)$faq->get('positive_score'),
		'negativeScore' => (float)$faq->get('negative_score'),
		'totalVotes' => (int)$faq->get('total_votes'),
		'reliability' => (float)$faq->get('reliability'),
		'voteDate' => $lastVoteDate,
	), 409);
}

if (!$faq->registerVote($vote)) {
	faqVoteJsonResponse(array(
		'status' => false,
		'success' => false,
		'message' => 'Impossible d enregistrer ce vote.',
	), 500);
}

if (!isset($_SESSION['faq_votes']) || !is_array($_SESSION['faq_votes'])) {
	$_SESSION['faq_votes'] = array();
}
$_SESSION['faq_votes'][$faqId] = $today;

faqVoteJsonResponse(array(
	'status' => true,
	'success' => true,
	'faqId' => $faqId,
	'message' => 'Vote enregistre. Merci pour votre retour.',
	'positiveScore' => (float)$faq->get('positive_score'),
	'negativeScore' => (float)$faq->get('negative_score'),
	'totalVotes' => (int)$faq->get('total_votes'),
	'reliability' => (float)$faq->get('reliability'),
	'voteDate' => $today,
));

?>
