<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function commonInvitationEscape($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function commonInvitationBuildRedirectUrl(\dbObject\Organization $organization)
{
	$targetHost = commonGetRequestHost();
	$shortname = trim((string)$organization->get('shortname'));
	if ($shortname !== '') {
		$builtHost = commonBuildOrganizationHost($shortname, commonGetRootHost($targetHost));
		if (trim((string)$builtHost) !== '') {
			$targetHost = $builtHost;
		}
	}

	return commonBuildUrl('/omo/', $targetHost);
}

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$decision = trim((string)($_POST['decision'] ?? ''));
$invitation = $token !== '' ? \dbObject\Invitation::findByToken($token) : false;
$validInvitation = $token !== '' ? \dbObject\Invitation::findValidByToken($token) : false;
$statusMessage = '';
$statusType = 'info';
$acceptedRedirectUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$validInvitation) {
		$statusMessage = "Cette invitation n'est plus valide.";
		$statusType = 'error';
	} elseif ($decision === 'accept') {
		$result = $validInvitation->accept();
		if (!($result['status'] ?? false)) {
			$statusMessage = (string)($result['message'] ?? "L'invitation n'a pas pu être acceptée.");
			$statusType = 'error';
		} else {
			$user = new \dbObject\User();
			$organization = new \dbObject\Organization();
			if ($user->load((int)$result['userId']) && $organization->load((int)$result['organizationId'])) {
				session_regenerate_id(true);
				$_SESSION['currentUser'] = (int)$user->getId();
				$_SESSION['userRef'] = $user;
				$_SESSION['currentOrganization'] = (int)$organization->getId();
				$acceptedRedirectUrl = commonInvitationBuildRedirectUrl($organization);
			}

			$statusMessage = 'Invitation acceptée. Vous pouvez maintenant accéder à votre organisation.';
			$statusType = 'success';
			$invitation = \dbObject\Invitation::findByToken($token);
			$validInvitation = false;
		}
	} elseif ($decision === 'decline') {
		$result = $validInvitation->decline();
		if (!($result['status'] ?? false)) {
			$statusMessage = (string)($result['message'] ?? "L'invitation n'a pas pu être refusée.");
			$statusType = 'error';
		} else {
			$statusMessage = 'Invitation refusée.';
			$statusType = 'success';
			$invitation = \dbObject\Invitation::findByToken($token);
			$validInvitation = false;
		}
	}
}

$organization = null;
$pendingHolons = [];
$invitedUser = null;

if ($invitation) {
	$organization = new \dbObject\Organization();
	if (!$organization->load((int)$invitation->get('IDorganization'))) {
		$organization = null;
	}

	$invitedUser = new \dbObject\User();
	if (!$invitedUser->load((int)$invitation->get('IDuser'))) {
		$invitedUser = null;
	}

	$pendingHolons = $invitation->getPendingHolons();
}

$organizationName = $organization ? (string)$organization->get('name') : 'Organisation';
$organizationColor = $organization ? (string)($organization->get('color') ?: '#4CAF50') : '#4CAF50';
$organizationLogo = $organization ? trim((string)$organization->get('logo')) : '';
$organizationBanner = $organization ? trim((string)$organization->get('banner')) : '';
$invitedEmail = $invitation ? trim((string)$invitation->get('email')) : '';
if ($invitedEmail === '' && $invitedUser) {
	$invitedEmail = trim((string)$invitedUser->get('email'));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Invitation</title>
	<link rel="stylesheet" href="/common/assets/auth.css">
	<style>
		:root {
			--invitation-primary: <?= commonInvitationEscape($organizationColor) ?>;
		}
		.invitation-page {
			min-height: 100vh;
			margin: 0;
			background: #f3f4f6;
			font-family: Arial, Helvetica, sans-serif;
			color: #111827;
		}
		.invitation-shell {
			max-width: 760px;
			margin: 0 auto;
			padding: 32px 18px 48px;
		}
		.invitation-card {
			overflow: hidden;
			border-radius: 24px;
			background: #ffffff;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
			border: 1px solid rgba(148, 163, 184, 0.22);
		}
		.invitation-hero {
			position: relative;
			padding: 34px 26px;
			background: <?= commonInvitationEscape($organizationColor) ?>;
			color: #ffffff;
			text-align: center;
		}
		.invitation-hero::before {
			content: "";
			position: absolute;
			inset: 0;
			background: <?= $organizationBanner !== '' ? 'url(' . commonInvitationEscape($organizationBanner) . ') center/cover' : 'linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,0))' ?>;
			opacity: 0.2;
		}
		.invitation-hero > * {
			position: relative;
		}
		.invitation-logo {
			width: 82px;
			height: 82px;
			margin: 0 auto 14px;
			border-radius: 999px;
			background: rgba(255,255,255,0.96);
			padding: 6px;
			box-sizing: border-box;
		}
		.invitation-logo img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			border-radius: 999px;
		}
		.invitation-hero h1 {
			margin: 0;
			font-size: 2rem;
			line-height: 1.1;
		}
		.invitation-body {
			padding: 28px 26px 30px;
			display: grid;
			gap: 20px;
		}
		.invitation-section {
			display: grid;
			gap: 10px;
		}
		.invitation-section h2 {
			margin: 0;
			font-size: 1.05rem;
		}
		.invitation-copy {
			margin: 0;
			color: #475569;
			line-height: 1.6;
		}
		.invitation-list {
			margin: 0;
			padding-left: 20px;
			display: grid;
			gap: 8px;
		}
		.invitation-list li {
			line-height: 1.45;
		}
		.invitation-list span {
			color: #64748b;
		}
		.invitation-status {
			padding: 14px 16px;
			border-radius: 14px;
			font-weight: 600;
			line-height: 1.45;
		}
		.invitation-status--info {
			background: #eff6ff;
			color: #1d4ed8;
			border: 1px solid rgba(37, 99, 235, 0.2);
		}
		.invitation-status--success {
			background: #f0fdf4;
			color: #166534;
			border: 1px solid rgba(34, 197, 94, 0.2);
		}
		.invitation-status--error {
			background: #fef2f2;
			color: #b91c1c;
			border: 1px solid rgba(239, 68, 68, 0.2);
		}
		.invitation-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
		}
		.invitation-actions button,
		.invitation-actions a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 46px;
			padding: 0 18px;
			border-radius: 999px;
			font-weight: 700;
			text-decoration: none;
			cursor: pointer;
			border: 0;
		}
		.invitation-actions__accept {
			background: var(--invitation-primary);
			color: #ffffff;
		}
		.invitation-actions__decline {
			background: #e2e8f0;
			color: #0f172a;
		}
		.invitation-actions__continue {
			background: #111827;
			color: #ffffff;
		}
	</style>
</head>
<body class="invitation-page">
	<div class="invitation-shell">
		<div class="invitation-card">
			<div class="invitation-hero">
				<?php if ($organizationLogo !== ''): ?>
				<div class="invitation-logo">
					<img src="<?= commonInvitationEscape($organizationLogo) ?>" alt="<?= commonInvitationEscape($organizationName) ?>">
				</div>
				<?php endif; ?>
				<h1><?= commonInvitationEscape($organizationName) ?></h1>
			</div>

			<div class="invitation-body">
				<?php if ($statusMessage !== ''): ?>
				<div class="invitation-status invitation-status--<?= commonInvitationEscape($statusType) ?>">
					<?= commonInvitationEscape($statusMessage) ?>
				</div>
				<?php endif; ?>

				<?php if (!$invitation): ?>
				<div class="invitation-section">
					<h2>Invitation introuvable</h2>
					<p class="invitation-copy">Le lien demandé ne correspond à aucune invitation connue.</p>
				</div>
				<?php else: ?>
				<div class="invitation-section">
					<h2>Adresse concernée</h2>
					<p class="invitation-copy"><?= commonInvitationEscape($invitedEmail !== '' ? $invitedEmail : 'Profil invité') ?></p>
				</div>

				<div class="invitation-section">
					<h2>Holons concernés</h2>
					<?php if (count($pendingHolons) === 0): ?>
						<p class="invitation-copy">Aucun holon en attente n'a été retrouvé pour cette invitation.</p>
					<?php else: ?>
						<ul class="invitation-list">
							<?php foreach ($pendingHolons as $holon): ?>
							<li>
								<strong><?= commonInvitationEscape($holon['name']) ?></strong>
								<span>(<?= commonInvitationEscape($holon['typeLabel']) ?>)</span>
							</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<?php if ($validInvitation): ?>
				<div class="invitation-section">
					<h2>Votre réponse</h2>
					<p class="invitation-copy">En acceptant, votre adhésion sera confirmée pour tous les holons listés ci-dessus en une seule fois.</p>
					<div class="invitation-actions">
						<form method="post" style="margin:0;">
							<input type="hidden" name="token" value="<?= commonInvitationEscape($token) ?>">
							<input type="hidden" name="decision" value="accept">
							<button type="submit" class="invitation-actions__accept">Accepter l'invitation</button>
						</form>
						<form method="post" style="margin:0;">
							<input type="hidden" name="token" value="<?= commonInvitationEscape($token) ?>">
							<input type="hidden" name="decision" value="decline">
							<button type="submit" class="invitation-actions__decline">Refuser</button>
						</form>
					</div>
				</div>
				<?php elseif ($acceptedRedirectUrl !== ''): ?>
				<div class="invitation-actions">
					<a class="invitation-actions__continue" href="<?= commonInvitationEscape($acceptedRedirectUrl) ?>">Entrer dans l'organisation</a>
				</div>
				<?php else: ?>
				<div class="invitation-section">
					<h2>Statut</h2>
					<p class="invitation-copy">
						<?php if ($invitation->isExpired()): ?>
							Cette invitation a expiré.
						<?php else: ?>
							Cette invitation a déjà reçu une réponse.
						<?php endif; ?>
					</p>
				</div>
				<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</body>
</html>
