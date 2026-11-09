<?php
require_once dirname(__DIR__) . '/shared_functions.php';

function commonInvitationUiT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.la_demande_n_a_pas_pu_etre_validee' => ['text' => 'La demande n\'a pas pu être validée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.demande_validee_la_personne_a_recu_un_e_mail_de_confirmation' => ['text' => 'Demande validée. La personne a reçu un e-mail de confirmation.', 'context' => 'Interface text in common/invitation.php'],
        'copy.l_invitation_n_a_pas_pu_etre_acceptee' => ['text' => 'L\'invitation n\'a pas pu être acceptée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.invitation_acceptee_vous_pouvez_maintenant_acceder_a_votre_organisatio' => ['text' => 'Invitation acceptée. Vous pouvez maintenant accéder à votre organisation.', 'context' => 'Interface text in common/invitation.php'],
        'copy.l_invitation_n_a_pas_pu_etre_refusee' => ['text' => 'L\'invitation n\'a pas pu être refusée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.invitation_refusee' => ['text' => 'Invitation refusée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.desole_cette_demande_a_ete_annulee' => ['text' => 'Désolé, cette demande a été annulée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.desole_cette_invitation_a_ete_annulee' => ['text' => 'Désolé, cette invitation a été annulée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_demande_a_ete_refusee' => ['text' => 'Cette demande a été refusée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_invitation_a_ete_refusee' => ['text' => 'Cette invitation a été refusée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.demande_d_acces' => ['text' => 'Demande d’accès', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_demande_a_ete_annulee' => ['text' => 'Cette demande a été annulée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_invitation_a_ete_annulee' => ['text' => 'Cette invitation a été annulée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_demande_a_expire' => ['text' => 'Cette demande a expiré.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_invitation_a_expire' => ['text' => 'Cette invitation a expiré.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_demande_a_deja_ete_traitee' => ['text' => 'Cette demande a déjà été traitée.', 'context' => 'Interface text in common/invitation.php'],
        'copy.cette_invitation_a_deja_recu_une_reponse' => ['text' => 'Cette invitation a déjà reçu une réponse.', 'context' => 'Interface text in common/invitation.php'],

        'copy.demande_d_acces' => ['text' => 'Demande d\'accès', 'context' => 'Interface text rendered by common/invitation.php'],
        'copy.aucun_message_complementaire_n_a_ete_ajoute_a_cette_demande' => ['text' => 'Aucun message complémentaire n\'a été ajoute à cette demande.', 'context' => 'Interface text rendered by common/invitation.php'],
        'copy.votre_reponse' => ['text' => 'Votre réponse', 'context' => 'Interface text rendered by common/invitation.php'],
];
    require_once __DIR__ . '/translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_invitation', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}

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
	return commonBuildOrganizationHomeUrl((int)$organization->getId(), (string)$organization->get('shortname'), commonGetRequestHost());
}

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$decision = trim((string)($_POST['decision'] ?? ''));
$invitation = $token !== '' ? \dbObject\Invitation::findByToken($token) : false;
$validInvitation = $token !== '' ? \dbObject\Invitation::findValidByToken($token) : false;
$statusMessage = '';
$statusType = 'info';
$acceptedRedirectUrl = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $validInvitation instanceof \dbObject\Invitation && $validInvitation->isMemberInitiatedRequest()) {
	$result = $validInvitation->approveByAdmin([
		'sendConfirmationEmail' => true,
		'approvedByUserId' => (int)commonGetCurrentUserId(),
	]);

	if (!($result['status'] ?? false)) {
		$statusMessage = (string)($result['message'] ?? commonInvitationUiT('copy.la_demande_n_a_pas_pu_etre_validee'));
		$statusType = 'error';
	} else {
		$statusMessage = commonInvitationUiT('copy.demande_validee_la_personne_a_recu_un_e_mail_de_confirmation');
		$statusType = 'success';
	}

	$invitation = \dbObject\Invitation::findByToken($token);
	$validInvitation = false;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$validInvitation) {
		$statusMessage = "Cette invitation n'est plus valide.";
		$statusType = 'error';
	} elseif ($decision === 'accept') {
		$result = $validInvitation->accept();
		if (!($result['status'] ?? false)) {
			$statusMessage = (string)($result['message'] ?? commonInvitationUiT('copy.l_invitation_n_a_pas_pu_etre_acceptee'));
			$statusType = 'error';
		} else {
			$user = new \dbObject\User();
			$organization = new \dbObject\Organization();
			if ($user->load((int)$result['userId']) && $organization->load((int)$result['organizationId'])) {
				session_regenerate_id(true);
				$_SESSION['currentUser'] = (int)$user->getId();
				$_SESSION['auth_security_version'] = (int)$user->get('security_version');
				commonClearCurrentUserAllAdminModes();
				$_SESSION['permissionCacheByOrganization'] = array();
				$_SESSION['userRef'] = $user;
				$_SESSION['currentOrganization'] = (int)$organization->getId();
				$acceptedRedirectUrl = commonInvitationBuildRedirectUrl($organization);
			}

			$statusMessage = commonInvitationUiT('copy.invitation_acceptee_vous_pouvez_maintenant_acceder_a_votre_organisatio');
			$statusType = 'success';
			$invitation = \dbObject\Invitation::findByToken($token);
			$validInvitation = false;
		}
	} elseif ($decision === 'decline') {
		$result = $validInvitation->decline();
		if (!($result['status'] ?? false)) {
			$statusMessage = (string)($result['message'] ?? commonInvitationUiT('copy.l_invitation_n_a_pas_pu_etre_refusee'));
			$statusType = 'error';
		} else {
			$statusMessage = commonInvitationUiT('copy.invitation_refusee');
			$statusType = 'success';
			$invitation = \dbObject\Invitation::findByToken($token);
			$validInvitation = false;
		}
	}
}

if ($statusMessage === '' && $token !== '' && !$validInvitation && $invitation instanceof \dbObject\Invitation) {
	$invitationStatus = trim((string)$invitation->get('status'));
	if ($invitationStatus === 'canceled') {
		$statusMessage = $invitation->isMemberInitiatedRequest()
			? commonInvitationUiT('copy.desole_cette_demande_a_ete_annulee')
			: commonInvitationUiT('copy.desole_cette_invitation_a_ete_annulee');
		$statusType = 'error';
	} elseif ($invitationStatus === 'declined') {
		$statusMessage = $invitation->isMemberInitiatedRequest()
			? commonInvitationUiT('copy.cette_demande_a_ete_refusee')
			: commonInvitationUiT('copy.cette_invitation_a_ete_refusee');
		$statusType = 'error';
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
$organizationColor = '';
if ($organization) {
	$organizationColor = commonGetOrganizationExplicitColor([
		'color' => (string)$organization->get('color'),
	]);
}
$organizationAccentColor = $organizationColor !== '' ? $organizationColor : '#004663';
$organizationLogo = $organization ? trim((string)$organization->get('logo')) : '';
$organizationBanner = $organization ? trim((string)$organization->get('banner')) : '';
$invitedEmail = $invitation ? trim((string)$invitation->get('email')) : '';
if ($invitedEmail === '' && $invitedUser) {
	$invitedEmail = trim((string)$invitedUser->get('email'));
}

$isMemberRequest = $invitation instanceof \dbObject\Invitation && $invitation->isMemberInitiatedRequest();
$requestMessage = $isMemberRequest ? $invitation->getRequestMessage() : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= commonInvitationEscape($isMemberRequest ? commonInvitationUiT('copy.demande_d_acces') : 'Invitation') ?></title>
	<link rel="stylesheet" href="/common/assets/auth.css">
	<style>
		:root {
			<?php if ($organizationColor !== ''): ?>
			--color-primary: <?= commonInvitationEscape($organizationColor) ?>;
			--auth-primary: <?= commonInvitationEscape($organizationColor) ?>;
			<?php endif; ?>
			--invitation-primary: <?= commonInvitationEscape($organizationAccentColor) ?>;
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
			border-radius: var(--radius-md);
			background: #ffffff;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
			border: 1px solid rgba(148, 163, 184, 0.22);
		}
		.invitation-hero {
			position: relative;
			padding: 34px 26px;
			background: var(--invitation-primary);
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
			border-radius: var(--radius-md);
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
					<h2><?= commonInvitationEscape($isMemberRequest ? 'Demande introuvable' : 'Invitation introuvable') ?></h2>
					<p class="invitation-copy">Le lien demande ne correspond a aucun enregistrement connu.</p>
				</div>
				<?php else: ?>
				<div class="invitation-section">
					<h2>Adresse concernee</h2>
					<p class="invitation-copy"><?= commonInvitationEscape($invitedEmail !== '' ? $invitedEmail : 'Profil invite') ?></p>
				</div>

				<?php if ($isMemberRequest): ?>
				<div class="invitation-section">
					<h2><?= htmlspecialchars(commonInvitationUiT('copy.demande_d_acces'), ENT_QUOTES, 'UTF-8') ?></h2>
					<?php if ($requestMessage !== ''): ?>
						<p class="invitation-copy"><?= nl2br(commonInvitationEscape($requestMessage)) ?></p>
					<?php else: ?>
						<p class="invitation-copy"><?= htmlspecialchars(commonInvitationUiT('copy.aucun_message_complementaire_n_a_ete_ajoute_a_cette_demande'), ENT_QUOTES, 'UTF-8') ?></p>
					<?php endif; ?>
				</div>
				<?php else: ?>
				<div class="invitation-section">
					<h2><?= commonInvitationEscape(\dbObject\Organization::formatLexiconText('Holons concernes', $organization->getLexicon())) ?></h2>
					<?php if (count($pendingHolons) === 0): ?>
						<p class="invitation-copy"><?= commonInvitationEscape(\dbObject\Organization::formatLexiconText("Aucun holon en attente n'a été retrouve pour cette invitation.", $organization->getLexicon())) ?></p>
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
				<?php endif; ?>

				<?php if (!$isMemberRequest && $validInvitation): ?>
				<div class="invitation-section">
					<h2><?= htmlspecialchars(commonInvitationUiT('copy.votre_reponse'), ENT_QUOTES, 'UTF-8') ?></h2>
					<p class="invitation-copy"><?= commonInvitationEscape(\dbObject\Organization::formatLexiconText('En acceptant, votre adhésion sera confirmée pour tous les holons listes ci-dessus en une seule fois.', $organization->getLexicon())) ?></p>
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
				<?php elseif (!$isMemberRequest && $acceptedRedirectUrl !== ''): ?>
				<div class="invitation-actions">
					<a class="invitation-actions__continue" href="<?= commonInvitationEscape($acceptedRedirectUrl) ?>">Entrer dans l'organisation</a>
				</div>
				<?php else: ?>
				<div class="invitation-section">
					<h2>Statut</h2>
					<p class="invitation-copy">
						<?php $invitationStatus = trim((string)$invitation->get('status')); ?>
						<?php if ($invitationStatus === 'canceled'): ?>
							<?= commonInvitationEscape($isMemberRequest ? commonInvitationUiT('copy.cette_demande_a_ete_annulee') : commonInvitationUiT('copy.cette_invitation_a_ete_annulee')) ?>
						<?php elseif ($invitation->isExpired()): ?>
							<?= commonInvitationEscape($isMemberRequest ? commonInvitationUiT('copy.cette_demande_a_expire') : commonInvitationUiT('copy.cette_invitation_a_expire')) ?>
						<?php else: ?>
							<?= commonInvitationEscape($isMemberRequest ? commonInvitationUiT('copy.cette_demande_a_deja_ete_traitee') : commonInvitationUiT('copy.cette_invitation_a_deja_recu_une_reponse')) ?>
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
