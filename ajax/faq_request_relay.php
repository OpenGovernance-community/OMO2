<?php
require_once __DIR__ . '/../common/faq_popup_helper.php';

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
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('methode_non_autorisee')), 405);
}
if (!checklogin()) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('login_requis_2')), 401);
}
if (!\dbObject\FAQ::hasRequestRelayColumn()) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('la_migration_du_relais_des_demandes_faq_n_est_pas_entierement_appliquee')), 503);
}

$faqId = (int)($_GET['id'] ?? 0);
$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);
if ($faqId <= 0 || $faqContext === false) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('contexte_faq_invalide')), 400);
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || (int)$faq->getId() <= 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('faq_introuvable')), 404);
}
$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
if (!$faq->canRelayRequest()) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('vous_n_avez_pas_le_droit_de_relayer_cette_question')), 403);
}
if (!$faq->isPendingRequest()) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('seule_une_question_en_attente_peut_etre_relayee')), 422);
}
if ($faq->hasRequestBeenRelayed()) {
	faqRequestRelayRespond(array('success' => true, 'message' => faqPopupT('cette_question_a_deja_ete_relayee_aux_administrateurs_de_l_organisation')));
}

$organization = $faq->getResolvedOrganization();
$organizationId = $organization instanceof \dbObject\Organization ? (int)$organization->getId() : 0;
if ($organizationId <= 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('cette_question_n_est_rattachee_a_aucune_organisation')), 422);
}

$recipients = \dbObject\FAQ::getOrganizationAdminEmails($organizationId, (int)($viewerAccess['userId'] ?? 0));
if (count($recipients) === 0) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('aucun_autre_administrateur_actif_de_cette_organisation_n_a_d_adresse_e_mail_valide')), 422);
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
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('la_question_n_a_pas_pu_etre_relayee_par_e_mail')), 502);
}

$faq->set('request_relayed_at', date('Y-m-d H:i:s'));
$saveResult = $faq->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
	faqRequestRelayRespond(array('success' => false, 'message' => faqPopupT('les_administrateurs_ont_ete_informes_mais_le_relais_n_a_pas_pu_etre_enregistre')), 500);
}

faqRequestRelayRespond(array(
	'success' => true,
	'message' => faqPopupT('relay_success', ['count' => $sentCount]),
));
