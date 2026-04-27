<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/patreon.php");

$connected = checklogin();
if (!$connected) {
	die("Login requis");
}

$user = new \dbObject\User();
$user->load($_SESSION["currentUser"]);
if (!($user->get("id") > 0)) {
	die("Utilisateur inconnu");
}

$patreonConnection = \dbObject\UserPatreon::findByUserId((int)$user->getId());
$patreonConfigured = patreonIsConfigured();
$patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();

function profilFormatDateTime($value)
{
	if ($value instanceof DateTimeInterface) {
		return $value->format('d.m.Y H:i');
	}

	return 'Non renseigné';
}

function profilFormatAmountCents($value)
{
	$amount = ((int)$value) / 100;
	return number_format($amount, 2, '.', "'");
}
?>
<style>
	.profile-panel {
		padding: 18px;
		background: #f8fafc;
		color: #0f172a;
		font-family: Arial, Helvetica, sans-serif;
	}
	.profile-panel__sections {
		display: grid;
		gap: 18px;
	}
	.profile-panel__section {
		padding: 18px;
		border: 1px solid #dbe4ee;
		border-radius: 18px;
		background: #ffffff;
	}
	.profile-panel__section h3 {
		margin: 0 0 14px;
		font-size: 20px;
	}
	.profile-panel__summary {
		display: grid;
		gap: 10px;
	}
	.profile-panel__item {
		padding: 12px 14px;
		border: 1px solid #dbe4ee;
		border-radius: 12px;
		background: #f8fafc;
	}
	.profile-panel__item strong {
		display: block;
		margin-bottom: 4px;
		font-size: 12px;
		text-transform: uppercase;
		letter-spacing: .04em;
		color: #475569;
	}
	.profile-panel__actions {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
		margin-top: 16px;
	}
	.profile-panel button[type='button'] {
		min-height: 44px;
		padding: 10px 18px;
		border: 0;
		border-radius: 12px;
		background: #2563eb;
		color: #fff;
		font-weight: 700;
		cursor: pointer;
	}
	.profile-panel button[type='button'].profile-panel__button-secondary {
		background: #0f172a;
	}
	.profile-panel button[type='button'].profile-panel__button-muted {
		background: #e2e8f0;
		color: #0f172a;
	}
	.profile-panel button[disabled] {
		cursor: not-allowed;
		opacity: .75;
	}
	.profile-panel__note {
		margin-top: 14px;
		padding: 12px 14px;
		border-radius: 12px;
		background: #fff7ed;
		border: 1px solid #fed7aa;
		color: #9a3412;
	}
	.profile-panel .dbobjecttable {
		width: 100%;
	}
	.profile-panel .dbobjecttable th,
	.profile-panel .dbobjecttable td {
		vertical-align: top;
	}
	.profile-panel .dbobjecttable th {
		padding-right: 12px;
		text-align: left;
		white-space: nowrap;
	}
</style>

<div class="profile-panel" id="profilePanelRoot">
	<div class="profile-panel__sections">
		<section class="profile-panel__section">
			<h3><?= T_("Votre profil") ?></h3>
			<div class="profile-panel__summary">
				<div class="profile-panel__item">
					<strong>E-mail</strong>
					<?= htmlspecialchars((string)$user->get("email")) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Paramètre basic</strong>
					<?= htmlspecialchars((string)$user->getParameter("basic")) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Paramètre numeric</strong>
					<?= htmlspecialchars((string)$user->getParameter("numeric")) ?>
				</div>
			</div>
		</section>

		<section class="profile-panel__section">
			<h3><?= T_("Modifier votre profil") ?></h3>
			<?php
			$params = array(
				"buttons" => false,
				"action" => "/ajax/saveaccount.php?origin=profil",
			);
			$user->display("adminEdit.php", $params);
			?>
			<div class="profile-panel__actions">
				<button type="button" id="updateprofil">Mettre à jour</button>
			</div>
		</section>

		<section class="profile-panel__section">
			<h3>Patreon</h3>
			<div class="profile-panel__summary">
				<div class="profile-panel__item">
					<strong>Connexion</strong>
					<?= $patreonConnected ? 'Compte Patreon connecté' : 'Aucun compte Patreon connecté' ?>
				</div>
				<?php if ($patreonConnection !== false): ?>
				<div class="profile-panel__item">
					<strong>Nom Patreon</strong>
					<?= htmlspecialchars((string)($patreonConnection->get('full_name') ?: 'Non renseigné')) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Statut d’abonnement</strong>
					<?= htmlspecialchars((string)($patreonConnection->get('patron_status') ?: 'Non renseigné')) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Dernier paiement</strong>
					<?= htmlspecialchars((string)($patreonConnection->get('last_charge_status') ?: 'Non renseigné')) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Paliers actifs</strong>
					<?= nl2br(htmlspecialchars(trim((string)$patreonConnection->get('tier_titles')) !== '' ? (string)$patreonConnection->get('tier_titles') : 'Aucun')) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Montant actif</strong>
					<?= htmlspecialchars(profilFormatAmountCents((int)$patreonConnection->get('currently_entitled_amount_cents'))) ?>
				</div>
				<div class="profile-panel__item">
					<strong>Dernière synchronisation</strong>
					<?= htmlspecialchars(profilFormatDateTime($patreonConnection->get('last_sync_at'))) ?>
				</div>
				<?php if (trim((string)$patreonConnection->get('last_sync_error')) !== ''): ?>
				<div class="profile-panel__item">
					<strong>Dernière erreur</strong>
					<?= nl2br(htmlspecialchars((string)$patreonConnection->get('last_sync_error'))) ?>
				</div>
				<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="profile-panel__actions">
				<?php if ($patreonConfigured): ?>
				<button type="button" id="patreon_connect"><?= $patreonConnected ? 'Reconnecter Patreon' : 'Connecter Patreon' ?></button>
				<?php if ($patreonConnected): ?>
				<button type="button" id="patreon_sync" class="profile-panel__button-secondary">Rafraîchir maintenant</button>
				<button type="button" id="patreon_disconnect" class="profile-panel__button-muted">Déconnecter</button>
				<?php endif; ?>
				<?php else: ?>
				<button type="button" class="profile-panel__button-muted" disabled>Configuration Patreon manquante</button>
				<?php endif; ?>
			</div>

			<?php if (!$patreonConfigured): ?>
			<div class="profile-panel__note">
				La connexion Patreon sera disponible après configuration des variables d’environnement `PATREON_CLIENT_ID`, `PATREON_CLIENT_SECRET` et `PATREON_REDIRECT_URI`.
			</div>
			<?php endif; ?>
		</section>
	</div>
</div>

<script>
(function () {
	function parseJsonResponse(text) {
		try {
			return JSON.parse(text);
		} catch (error) {
			return {
				status: false,
				message: "Réponse serveur invalide."
			};
		}
	}

	function handleLegacyAjaxResponse(payload) {
		if (payload && payload.script) {
			try {
				eval(payload.script);
			} catch (error) {
				console.error(error);
			}
		}

		if (payload && payload.message) {
			alert(payload.message);
		}
	}

	function postUrl(url) {
		return fetch(url, {
			method: "POST",
			credentials: "same-origin",
			headers: {
				"X-Requested-With": "XMLHttpRequest"
			}
		}).then(function (response) {
			return response.text();
		}).then(parseJsonResponse);
	}

	var updateButton = document.getElementById("updateprofil");
	if (updateButton) {
		updateButton.addEventListener("click", function () {
			if (window.jQuery && window.jQuery("#formulaire-edit").length) {
				window.jQuery("#formulaire-edit").trigger("submit");
				return;
			}

			alert("jQuery est requis pour ce formulaire.");
		});
	}

	var connectButton = document.getElementById("patreon_connect");
	if (connectButton) {
		connectButton.addEventListener("click", function () {
			var width = 720;
			var height = 860;
			var left = Math.max(0, (window.screen.width - width) / 2);
			var top = Math.max(0, (window.screen.height - height) / 2);
			window.open(
				"/common/patreon_connect.php",
				"patreon_connect",
				"width=" + width + ",height=" + height + ",left=" + left + ",top=" + top + ",resizable=yes,scrollbars=yes"
			);
		});
	}

	var disconnectButton = document.getElementById("patreon_disconnect");
	if (disconnectButton) {
		disconnectButton.addEventListener("click", function () {
			if (!confirm("Déconnecter le compte Patreon de ce profil ?")) {
				return;
			}

			disconnectButton.disabled = true;
			postUrl("/ajax/patreon_disconnect.php")
				.then(handleLegacyAjaxResponse)
				.finally(function () {
					disconnectButton.disabled = false;
				});
		});
	}

	var syncButton = document.getElementById("patreon_sync");
	if (syncButton) {
		syncButton.addEventListener("click", function () {
			syncButton.disabled = true;
			postUrl("/ajax/patreon_sync.php")
				.then(handleLegacyAjaxResponse)
				.finally(function () {
					syncButton.disabled = false;
				});
		});
	}

	window.addEventListener("message", function (event) {
		if (event.origin !== window.location.origin) {
			return;
		}

		if (event.data && event.data.type === "patreon-connected") {
			if (window.commonTopbarRefreshModalContent) {
				window.commonTopbarRefreshModalContent("/popup/profil.php");
			}
			if (window.jQuery && document.getElementById("popup_content")) {
				refresh("#popup_content", "/popup/profil.php");
			}
		}
	});
})();
</script>
