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
	body {
		margin:0;
		padding:18px;
		background:#f8fafc;
		font-family:Arial, Helvetica, sans-serif;
		color:#0f172a;
	}
	.settings-accordion .ui-accordion-header {
		margin-top:10px;
		padding:14px 18px;
		border:1px solid #dbe4ee;
		border-radius:16px;
		background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
		color:#0f172a;
		font-weight:700;
	}
	.settings-accordion .ui-accordion-header.ui-state-active {
		border-bottom-left-radius:0;
		border-bottom-right-radius:0;
	}
	.settings-accordion .ui-accordion-content {
		padding:18px;
		border:1px solid #dbe4ee;
		border-top:0;
		border-radius:0 0 16px 16px;
		background:#ffffff;
		position:relative;
	}
	.settings-accordion button[type='button'] {
		min-height:44px;
		padding:10px 18px;
		border:0;
		border-radius:12px;
		background:#2563eb;
		color:#fff;
		font-weight:700;
		cursor:pointer;
		box-shadow:0 12px 24px rgba(37,99,235,.18);
	}
	.settings-summary {
		display:grid;
		gap:10px;
	}
	.settings-summary__item {
		padding:12px 14px;
		border:1px solid #dbe4ee;
		border-radius:12px;
		background:#f8fafc;
	}
	.settings-summary__item strong {
		display:block;
		margin-bottom:4px;
		font-size:12px;
		text-transform:uppercase;
		letter-spacing:.04em;
		color:#475569;
	}
	.settings-actions {
		display:flex;
		flex-wrap:wrap;
		gap:10px;
		margin-top:16px;
	}
	.settings-accordion .settings-actions button[type='button'] {
		box-shadow:none;
	}
	.settings-accordion .settings-actions button[type='button'].settings-btn-secondary {
		background:#0f172a;
	}
	.settings-accordion .settings-actions button[type='button'].settings-btn-muted {
		background:#e2e8f0;
		color:#0f172a;
	}
	.settings-note {
		margin-top:14px;
		padding:12px 14px;
		border-radius:12px;
		background:#fff7ed;
		border:1px solid #fed7aa;
		color:#9a3412;
	}
</style>
<div id="accordion" class="settings-accordion">
	<h3><?= T_("Votre profil") ?></h3>
	<div>
<?php
	echo "<div class='settings-summary'>";
	echo "<div class='settings-summary__item'><strong>E-mail</strong>" . htmlspecialchars((string)$user->get("email")) . "</div>";
	echo "<div class='settings-summary__item'><strong>Paramètre basic</strong>" . htmlspecialchars((string)$user->getParameter("basic")) . "</div>";
	echo "<div class='settings-summary__item'><strong>Paramètre numeric</strong>" . htmlspecialchars((string)$user->getParameter("numeric")) . "</div>";
	echo "</div>";
?>
	</div>
	<h3><?= T_("Modifier votre profil") ?></h3>
	<div>
<?php
	echo "<form name='formulaire' id='profil_formulaire' action='/ajax/saveaccount.php?origin=profil'>";
	$params = array(
		"buttons" => false,
		"form" => false,
	);
	$user->display("adminEdit.php", $params);
	echo "<button type='button' id='updateprofil'>Mettre à jour</button>";
	echo "</form>";
?>
	</div>
	<h3>Patreon</h3>
	<div>
		<div class="settings-summary">
			<div class="settings-summary__item">
				<strong>Connexion</strong>
				<?= $patreonConnected ? 'Compte Patreon connecté' : 'Aucun compte Patreon connecté' ?>
			</div>
			<?php if ($patreonConnection !== false): ?>
			<div class="settings-summary__item">
				<strong>Nom Patreon</strong>
				<?= htmlspecialchars((string)($patreonConnection->get('full_name') ?: 'Non renseigné')) ?>
			</div>
			<div class="settings-summary__item">
				<strong>Statut d’abonnement</strong>
				<?= htmlspecialchars((string)($patreonConnection->get('patron_status') ?: 'Non renseigné')) ?>
			</div>
			<div class="settings-summary__item">
				<strong>Dernier paiement</strong>
				<?= htmlspecialchars((string)($patreonConnection->get('last_charge_status') ?: 'Non renseigné')) ?>
			</div>
			<div class="settings-summary__item">
				<strong>Paliers actifs</strong>
				<?= nl2br(htmlspecialchars(trim((string)$patreonConnection->get('tier_titles')) !== '' ? (string)$patreonConnection->get('tier_titles') : 'Aucun')) ?>
			</div>
			<div class="settings-summary__item">
				<strong>Montant actif</strong>
				<?= htmlspecialchars(profilFormatAmountCents((int)$patreonConnection->get('currently_entitled_amount_cents'))) ?>
			</div>
			<div class="settings-summary__item">
				<strong>Dernière synchronisation</strong>
				<?= htmlspecialchars(profilFormatDateTime($patreonConnection->get('last_sync_at'))) ?>
			</div>
			<?php if (trim((string)$patreonConnection->get('last_sync_error')) !== ''): ?>
			<div class="settings-summary__item">
				<strong>Dernière erreur</strong>
				<?= nl2br(htmlspecialchars((string)$patreonConnection->get('last_sync_error'))) ?>
			</div>
			<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="settings-actions">
			<?php if ($patreonConfigured): ?>
			<button type="button" id="patreon_connect"><?= $patreonConnected ? 'Reconnecter Patreon' : 'Connecter Patreon' ?></button>
			<?php if ($patreonConnected): ?>
			<button type="button" id="patreon_sync" class="settings-btn-secondary">Rafraîchir maintenant</button>
			<button type="button" id="patreon_disconnect" class="settings-btn-muted">Déconnecter</button>
			<?php endif; ?>
			<?php else: ?>
			<button type="button" class="settings-btn-muted" disabled>Configuration Patreon manquante</button>
			<?php endif; ?>
		</div>

		<?php if (!$patreonConfigured): ?>
		<div class="settings-note">
			La connexion Patreon sera disponible après configuration des variables d’environnement `PATREON_CLIENT_ID`, `PATREON_CLIENT_SECRET` et `PATREON_REDIRECT_URI`.
		</div>
		<?php endif; ?>
	</div>
</div>
<script>
	$(function () {
		$("#updateprofil").click(function () {
			sendForm($("#profil_formulaire"), success);
		});

		$("#patreon_connect").click(function () {
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

		$("#patreon_disconnect").click(function () {
			if (!confirm("Déconnecter le compte Patreon de ce profil ?")) {
				return;
			}

			$.post("/ajax/patreon_disconnect.php").done(success);
		});

		$("#patreon_sync").click(function () {
			$.post("/ajax/patreon_sync.php").done(success);
		});

		$("#accordion").accordion({ heightStyle: "fill" });
		window.onresize = function () {
			$("#accordion").accordion("refresh");
		};

		window.addEventListener("message", function (event) {
			if (event.origin !== window.location.origin) {
				return;
			}

			if (event.data && event.data.type === "patreon-connected") {
				refresh('#popup_content', '/popup/profil.php');
			}
		});
	});
</script>
