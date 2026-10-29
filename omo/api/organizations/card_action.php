<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__, 3) . '/shared_functions.php';
require_once dirname(__DIR__, 3) . '/common/auth.php';

commonRestoreRememberedUser();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_POST['oid'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

if ($currentUserId <= 0) {
	http_response_code(401);
	echo json_encode(array(
		'status' => false,
		'message' => omoOrganizationsUiT('connexion_requise'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ($organizationId <= 0 || $action === '') {
	http_response_code(400);
	echo json_encode(array(
		'status' => false,
		'message' => omoOrganizationsUiT('action_organisation_invalide'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) {
	http_response_code(404);
	echo json_encode(array(
		'status' => false,
		'message' => omoOrganizationsUiT('organisation_introuvable'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$organization->getMembership($currentUserId, true)) {
	http_response_code(403);
	echo json_encode(array(
		'status' => false,
		'message' => omoOrganizationsUiT('vous_n_avez_pas_acces_a_cette_organisation'),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$response = array(
	'status' => false,
	'message' => omoOrganizationsUiT('action_inconnue'),
);

switch ($action) {
	case 'toggle-model':
		$membership = $organization->getMembership($currentUserId, true);
		if (
			!$membership
			|| !$membership->isOrganizationAdmin()
			|| !commonCurrentUserIsAdminModeEnabled($organizationId)
			|| !$organization->getStructuralRootHolon()
		) {
			http_response_code(403);
			$response = array(
				'status' => false,
				'message' => omoOrganizationsUiT('le_partage_comme_modele_requiert_une_organisation_structuree_et_le_mode_administrateur_act'),
			);
			break;
		}

		$willShareAsModel = !$organization->isSharedAsTemplate();
		$organization->set('isModel', $willShareAsModel);
		$saveResult = $organization->save();
		$response = array(
			'status' => is_array($saveResult) && !empty($saveResult['status']),
			'message' => $willShareAsModel
				? 'Organisation partagee comme modele public.'
				: 'Organisation retiree des modeles publics.',
		);
		break;

	case 'leave':
		$response = $organization->removeMember($currentUserId, array(
			'actorUserId' => $currentUserId,
		));
		break;

	case 'delete':
		if ($organization->isSystemOrganization()) {
			http_response_code(403);
			$response = array(
				'status' => false,
				'message' => omoOrganizationsUiT('l_organisation_de_base_est_utilisee_par_le_systeme_et_ne_peut_pas_etre_supprimee'),
			);
			break;
		}

		if (!$organization->canDelete()) {
			http_response_code(403);
			$response = array(
				'status' => false,
				'message' => omoOrganizationsUiT('vous_n_avez_pas_le_droit_de_supprimer_cette_organisation'),
			);
			break;
		}

		$organizationName = trim((string)$organization->get('name'));
		$deleted = $organization->delete();
		$deleteError = method_exists($organization, 'getLastDeleteError')
			? trim((string)$organization->getLastDeleteError())
			: '';
		$response = array(
			'status' => (bool)$deleted,
			'message' => $deleted
				? (($organizationName !== '' ? $organizationName : 'L organisation') . ' a ete supprimee.')
				: ($deleteError !== '' ? $deleteError : "L'organisation n'a pas pu etre supprimee."),
		);
		break;
}

if (!($response['status'] ?? false)) {
	if (http_response_code() < 400) {
		http_response_code(422);
	}
	echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ((int)($_SESSION['currentOrganization'] ?? 0) === $organizationId) {
	$_SESSION['currentOrganization'] = -1;
}

$response['redirect'] = commonBuildUrl('/omo/', commonGetRootHost());
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
