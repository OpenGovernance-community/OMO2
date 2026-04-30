<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");

$connected = checklogin();
if (!$connected) {
	die("Login requis");
}

$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION["currentUser"] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
	die("Utilisateur inconnu");
}

$scope = 'general';
$organizationMembership = null;

if ($requestedScope === 'organization' && $currentOrganizationId > 0) {
	$organization = new \dbObject\Organization();
	if ($organization->load($currentOrganizationId)) {
		$organizationMembership = $user->getOrganizationMembership($currentOrganizationId);
		if (!$organizationMembership) {
			$organizationMembership = new \dbObject\UserOrganization();
			$organizationMembership->set('IDuser', (int)$user->getId());
			$organizationMembership->set('IDorganization', $currentOrganizationId);
			$organizationMembership->set('active', true);
		}
		$scope = 'organization';
	}
}
?>
<div class="profile-panel__scope-fragment" data-profile-loaded-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
	<?php if ($scope === 'organization' && $organizationMembership): ?>
	<p class="profile-panel__scope-help">
		Ces données remplacent le profil général uniquement dans cette organisation. Laissez un champ vide pour continuer à utiliser la valeur générale.
	</p>
	<?php
	$params = array(
		"buttons" => false,
		"action" => "/ajax/saveaccount_organization.php?origin=profil&scope=organization",
		"success" => "profileHandleOrganizationSaved()",
		"fields" => array(
			"image",
			"username",
			"email",
		),
	);
	$organizationMembership->display("adminEdit.php", $params);
	?>
	<div class="profile-panel__actions">
		<button type="button" id="updateprofil-organization">Mettre à jour les données de l'organisation</button>
	</div>
	<script>
	(function () {
		var updateButton = document.getElementById("updateprofil-organization");
		if (!updateButton) {
			return;
		}

		updateButton.addEventListener("click", function () {
			if (window.jQuery && window.jQuery("#formulaire-edit").length) {
				window.jQuery("#formulaire-edit").trigger("submit");
				return;
			}

			alert("jQuery est requis pour ce formulaire.");
		});
	})();
	</script>
	<?php else: ?>
	<?php
	$params = array(
		"buttons" => false,
		"action" => "/ajax/saveaccount.php?origin=profil&scope=general",
		"success" => "profileHandleGeneralSaved()",
	);
	$user->display("adminEdit.php", $params);
	?>
	<div class="profile-panel__actions">
		<button type="button" id="updateprofil-general">Mettre à jour les données générales</button>
	</div>
	<script>
	(function () {
		var updateButton = document.getElementById("updateprofil-general");
		if (!updateButton) {
			return;
		}

		updateButton.addEventListener("click", function () {
			if (window.jQuery && window.jQuery("#formulaire-edit").length) {
				window.jQuery("#formulaire-edit").trigger("submit");
				return;
			}

			alert("jQuery est requis pour ce formulaire.");
		});
	})();
	</script>
	<?php endif; ?>
</div>
