<?php
// Included only by the authorized organization editor; no connection secrets enter the markup.
require_once dirname(__DIR__) . '/patreon.php';
if (!isset($organizationId, $currentUserId) || !patreonUserCanManageOrganization((int)$currentUserId, (int)$organizationId)) return;
$patreonSourceLang = [
    'title' => ['text' => 'Patreon et IA', 'context' => 'Organization subscription section.'],
    'help' => ['text' => 'Une contribution active de {amount} minimum ouvre les fonctions IA aux membres dans cette organisation. Le seuil utilise le montant Patreon sans conversion de devise. Le compte créateur configuré est dispense du montant minimum. Un compte Patreon peut financer une personne et une organisation.', 'context' => 'Organization Patreon help capsule.'],
    'account' => ['text' => 'Compte Patreon', 'context' => 'Linked Patreon identity label.'],
    'connect' => ['text' => 'Connecter Patreon', 'context' => 'Connect organization Patreon account.'],
    'sync' => ['text' => 'Synchroniser', 'context' => 'Refresh Patreon subscription.'],
    'disconnect' => ['text' => 'Déconnecter', 'context' => 'Disconnect organization subscription.'],
    'confirm' => ['text' => 'Déconnecter Patreon de cette organisation ?', 'context' => 'Confirm organization Patreon disconnection.'],
    'active' => ['text' => 'IA disponible pour les membres', 'context' => 'Organization AI entitlement granted.'],
    'inactive' => ['text' => 'Abonnement organisation inactif ou insuffisant', 'context' => 'Organization entitlement unavailable.'],
    'unlinked' => ['text' => 'Aucun compte Patreon connecte', 'context' => 'Organization Patreon absent.'],
    'error' => ['text' => 'Impossible de mettre à jour Patreon.', 'context' => 'Organization Patreon request failure.'],
    'saved' => ['text' => 'Connexion Patreon mise à jour.', 'context' => 'Patreon action success notification.'],
];
$patreonLang = translationBundleInit('patreon_organization', omoGetTranslationLocale(), $patreonSourceLang);
$pt = static fn ($key, $args = []) => htmlspecialchars(t($key, $args, $patreonLang, $patreonSourceLang), ENT_QUOTES, 'UTF-8');
$_SESSION['patreon_organization_csrf'] ??= bin2hex(random_bytes(32));
$patreonConnection = \dbObject\OrganizationPatreon::findByOrganizationId($organizationId);
$patreonLinked = $patreonConnection && $patreonConnection->isConnected();
$patreonConfig = ['organizationId' => $organizationId, 'csrf' => $_SESSION['patreon_organization_csrf'],
    'connectUrl' => '/common/patreon_connect.php?organization_id=' . $organizationId . '&csrf=' . rawurlencode($_SESSION['patreon_organization_csrf']),
    'hubOrigin' => patreonGetConnectOrigin(),
    'text' => array_map(static fn ($key) => t($key, [], $patreonLang, $patreonSourceLang), array_combine(['active', 'inactive', 'unlinked', 'error', 'saved', 'confirm'], ['active', 'inactive', 'unlinked', 'error', 'saved', 'confirm']))];
?>
<section class="generic-section generic-section--stack" data-patreon-organization data-config="<?= htmlspecialchars(json_encode($patreonConfig), ENT_QUOTES, 'UTF-8') ?>">
    <div class="generic-form-section__heading">
        <h2 class="generic-card-title generic-card-title--section"><?= $pt('title') ?></h2>
        <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover><summary aria-label="<?= $pt('title') ?>">?</summary><div class="generic-context-help__content"><p><?= $pt('help', ['amount' => (string)($GLOBALS['patreonOrganizationMinimumAmount'] ?? '40')]) ?></p></div></details>
    </div>
    <p data-patreon-status><?= $pt($patreonLinked ? (patreonOrganizationCanUseAi($organizationId) ? 'active' : 'inactive') : 'unlinked') ?></p>
    <p class="generic-meta-text" data-patreon-account <?= $patreonLinked ? '' : 'hidden' ?>><?= $pt('account') ?> : <span><?= htmlspecialchars($patreonLinked ? ((string)$patreonConnection->get('full_name') ?: (string)$patreonConnection->get('patreon_user_id')) : '', ENT_QUOTES, 'UTF-8') ?></span></p>
    <div class="generic-form-actions">
        <button type="button" class="generic-action-button generic-action-button--main" data-patreon-connect><?= $pt('connect') ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-patreon-action="sync" <?= $patreonLinked ? '' : 'hidden' ?>><?= $pt('sync') ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-patreon-action="disconnect" <?= $patreonLinked ? '' : 'hidden' ?>><?= $pt('disconnect') ?></button>
    </div>
</section>
<script src="<?= commonAssetUrl('/common/patreon/organization.js') ?>"></script>
