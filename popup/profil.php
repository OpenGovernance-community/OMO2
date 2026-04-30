<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/patreon.php");

$connected = checklogin();
if (!$connected) {
	die("Login requis");
}

$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION["currentUser"] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
	die("Utilisateur inconnu");
}

$organization = null;
$organizationMembership = null;
$hasOrganizationScope = false;

if ($currentOrganizationId > 0) {
	$organization = new \dbObject\Organization();
	if ($organization->load($currentOrganizationId)) {
		$organizationMembership = $user->getOrganizationMembership($currentOrganizationId);
		if (!$organizationMembership) {
			$organizationMembership = new \dbObject\UserOrganization();
			$organizationMembership->set('IDuser', (int)$user->getId());
			$organizationMembership->set('IDorganization', $currentOrganizationId);
			$organizationMembership->set('active', true);
		}
		$hasOrganizationScope = true;
	}
}

$activeEmail = $user->getScopedEmail($currentOrganizationId);
$activeUsername = $user->getScopedUsername($currentOrganizationId);
$activePhotoUrl = $user->getScopedProfilePhotoUrl($currentOrganizationId);
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';
$initialScope = $hasOrganizationScope ? $requestedScope : 'general';

$patreonConnection = \dbObject\UserPatreon::findByUserId((int)$user->getId());
$patreonConfigured = patreonIsConfigured('oauth');
$patreonConfigurationMessage = patreonGetConfigurationMessage('oauth');
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
	.profile-panel button[type='button'],
	.profile-panel button[type='submit'] {
		min-height: 44px;
		padding: 10px 18px;
		border: 0;
		border-radius: 12px;
		background: #2563eb;
		color: #fff;
		font-weight: 700;
		cursor: pointer;
	}
	.profile-panel button[type='button'].profile-panel__button-secondary,
	.profile-panel button[type='submit'].profile-panel__button-secondary {
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
	.profile-panel__scope-switch {
		display: inline-flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-bottom: 16px;
	}
	.profile-panel__scope-button {
		background: #e2e8f0 !important;
		color: #0f172a !important;
	}
	.profile-panel__scope-button.is-active {
		background: #2563eb !important;
		color: #ffffff !important;
	}
	.profile-panel__scope-panel[hidden] {
		display: none !important;
	}
	.profile-panel__photo {
		width: 72px;
		height: 72px;
		border-radius: 999px;
		background: #dbe4ee center center / cover no-repeat;
		border: 1px solid #cbd5e1;
	}
	.profile-panel__form {
		display: grid;
		gap: 14px;
	}
	.profile-panel__field {
		display: grid;
		gap: 6px;
	}
	.profile-panel__field span {
		font-size: 13px;
		font-weight: 700;
		color: #334155;
	}
	.profile-panel__field input {
		width: 100%;
		min-height: 44px;
		padding: 10px 12px;
		border: 1px solid #dbe4ee;
		border-radius: 12px;
		background: #fff;
		color: inherit;
		font: inherit;
		box-sizing: border-box;
	}
	.profile-panel__field small,
	.profile-panel__scope-help,
	.profile-panel__feedback {
		color: #64748b;
		line-height: 1.45;
	}
	.profile-panel__feedback {
		min-height: 22px;
		font-weight: 600;
	}
	.profile-panel__feedback.is-success {
		color: #15803d;
	}
	.profile-panel__feedback.is-error {
		color: #b91c1c;
	}
	.profile-panel__photo-preview {
		display: flex;
		align-items: center;
		gap: 12px;
		flex-wrap: wrap;
	}
	.profile-panel__photo-empty {
		color: #64748b;
		font-size: 0.92rem;
	}
</style>

<div class="profile-panel" id="profilePanelRoot">
	<div class="profile-panel__sections">
		<section class="profile-panel__section">
			<h3><?= T_("Votre profil actif") ?></h3>
			<div class="profile-panel__summary">
				<div class="profile-panel__item">
					<strong>Contexte</strong>
					<?= htmlspecialchars($hasOrganizationScope && $organization ? 'Organisation : ' . (string)$organization->get('name') : 'Profil général') ?>
				</div>
				<div class="profile-panel__item">
					<strong>Photo affichée</strong>
					<div class="profile-panel__photo"<?= $activePhotoUrl !== '' ? ' style="background-image:url(' . htmlspecialchars($activePhotoUrl, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>></div>
				</div>
				<div class="profile-panel__item">
					<strong>E-mail affiché</strong>
					<?= htmlspecialchars($activeEmail !== '' ? $activeEmail : 'Non renseigné') ?>
				</div>
				<div class="profile-panel__item">
					<strong>Identifiant affiché</strong>
					<?= htmlspecialchars($activeUsername !== '' ? $activeUsername : 'Non renseigné') ?>
				</div>
			</div>
		</section>

		<section class="profile-panel__section">
			<h3><?= T_("Modifier votre profil") ?></h3>

			<?php if ($hasOrganizationScope): ?>
			<div class="profile-panel__scope-switch" role="tablist" aria-label="Choix du contexte de profil">
				<button type="button" class="profile-panel__scope-button<?= $initialScope === 'general' ? ' is-active' : '' ?>" data-profile-scope-target="general">Données générales</button>
				<button type="button" class="profile-panel__scope-button<?= $initialScope === 'organization' ? ' is-active' : '' ?>" data-profile-scope-target="organization">Données spécifiques à l'organisation</button>
			</div>
			<div class="profile-panel__scope-help">
				Vous pouvez compléter votre profil général, puis définir si besoin une présentation différente pour cette organisation.
			</div>
			<?php endif; ?>

			<div
				id="profileScopeContent"
				class="profile-panel__scope-panel"
				data-profile-scope-panel="<?= htmlspecialchars($initialScope, ENT_QUOTES, 'UTF-8') ?>"
				data-profile-scope-active="<?= htmlspecialchars($initialScope, ENT_QUOTES, 'UTF-8') ?>"
				data-profile-scope-url-general="/popup/profil_scope.php?scope=general"
				data-profile-scope-url-organization="/popup/profil_scope.php?scope=organization"
			>
				<div class="profile-panel__feedback">Chargement du formulaire…</div>
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
				La connexion Patreon n'est pas disponible sur cet environnement.
				<?php if ($patreonConfigurationMessage !== ''): ?>
				<br><?= htmlspecialchars($patreonConfigurationMessage) ?>
				<?php endif; ?>
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

	var scopeContainer = document.getElementById("profileScopeContent");
	var initialScope = scopeContainer ? (scopeContainer.getAttribute("data-profile-scope-active") || "general") : "general";

	function executeEmbeddedScripts(container) {
		if (!container) {
			return;
		}

		Array.prototype.forEach.call(container.querySelectorAll("script"), function (script) {
			var replacement = document.createElement("script");

			Array.prototype.forEach.call(script.attributes, function (attribute) {
				replacement.setAttribute(attribute.name, attribute.value);
			});

			if (!replacement.src) {
				replacement.textContent = script.textContent || "";
			}

			script.parentNode.replaceChild(replacement, script);
		});
	}

	function setActiveScopeButtons(target) {
		document.querySelectorAll("[data-profile-scope-target]").forEach(function (item) {
			item.classList.toggle("is-active", item.getAttribute("data-profile-scope-target") === target);
		});
	}

	function buildProfileModalUrl(scope) {
		return "/popup/profil.php?scope=" + encodeURIComponent(scope === "organization" ? "organization" : "general");
	}

	function loadProfileScope(target) {
		if (!scopeContainer) {
			return;
		}

		var normalizedTarget = target === "organization" ? "organization" : "general";
		var scopeUrl = scopeContainer.getAttribute(
			normalizedTarget === "organization"
				? "data-profile-scope-url-organization"
				: "data-profile-scope-url-general"
		);

		scopeContainer.setAttribute("data-profile-scope-active", normalizedTarget);
		scopeContainer.setAttribute("data-profile-scope-panel", normalizedTarget);
		scopeContainer.innerHTML = '<div class="profile-panel__feedback">Chargement du formulaire…</div>';
		setActiveScopeButtons(normalizedTarget);

		fetch(scopeUrl, {
			credentials: "same-origin",
			headers: {
				"X-Requested-With": "XMLHttpRequest"
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error("Erreur de chargement");
				}

				return response.text();
			})
			.then(function (html) {
				scopeContainer.innerHTML = html;
				executeEmbeddedScripts(scopeContainer);
			})
			.catch(function () {
				scopeContainer.innerHTML = '<div class="profile-panel__feedback is-error">Impossible de charger ce formulaire pour le moment.</div>';
			});
	}

	window.profileHandleGeneralSaved = function () {
		var targetUrl = buildProfileModalUrl("general");
		if (window.commonTopbarRefreshModalContent) {
			window.commonTopbarRefreshModalContent(targetUrl);
		}
		if (window.jQuery && document.getElementById("popup_content")) {
			window.jQuery("#popup_content").load(targetUrl);
		}
	};

	window.profileHandleOrganizationSaved = function () {
		var targetUrl = buildProfileModalUrl("organization");
		if (window.commonTopbarRefreshModalContent) {
			window.commonTopbarRefreshModalContent(targetUrl);
		}
		if (window.jQuery && document.getElementById("popup_content")) {
			window.jQuery("#popup_content").load(targetUrl);
		}
	};

	document.querySelectorAll("[data-profile-scope-target]").forEach(function (button) {
		button.addEventListener("click", function () {
			loadProfileScope(button.getAttribute("data-profile-scope-target"));
		});
	});

	if (scopeContainer) {
		loadProfileScope(initialScope);
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
			var currentScope = scopeContainer ? (scopeContainer.getAttribute("data-profile-scope-active") || "general") : "general";
			var targetUrl = buildProfileModalUrl(currentScope);
			if (window.commonTopbarRefreshModalContent) {
				window.commonTopbarRefreshModalContent(targetUrl);
			}
			if (window.jQuery && document.getElementById("popup_content")) {
				window.jQuery("#popup_content").load(targetUrl);
			}
		}
	});
})();
</script>
