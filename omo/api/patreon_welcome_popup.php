<?php
require_once __DIR__ . '/../ui_translations.php';
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/patreon.php';

$currentUserId = (int)commonGetCurrentUserId();
$patreonConfigured = patreonSupportUiIsEnabled();
$patreonConfigurationMessage = patreonGetConfigurationMessage('oauth');
$patreonConnection = false;
$patreonConnected = false;

if ($currentUserId > 0 && $patreonConfigured) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}

if (!$patreonConfigured) {
    http_response_code(404);
    ?>
<div class="generic-section generic-section--stack">
    <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoInterfaceUiT('fonction_indisponible'), ENT_QUOTES, 'UTF-8') ?></h3>
    <p><?= htmlspecialchars(omoInterfaceUiT('le_module_patreon_n_est_pas_configure_sur_ce_serveur'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php
    exit;
}
?>
<div class="omo-patreon-welcome generic-stack generic-stack--flush">
    <div class="omo-patreon-welcome__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-patreon-welcome__header-copy">
            <div class="omo-patreon-welcome__eyebrow">OpenGovernance</div>
            <h3 class="omo-patreon-welcome__title generic-title generic-title--large"><?= htmlspecialchars(omoInterfaceUiT('contribuez_au_developpement_du_logiciel'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-patreon-welcome__text generic-description generic-description--relaxed">
                <?= htmlspecialchars(omoInterfaceUiT('si_cet_outil_vous_est_utile_vous_pouvez_soutenir_son_evolution_sur'), ENT_QUOTES, 'UTF-8') ?>
                <a href="https://www.patreon.com/cw/OpenGovernance" target="_blank" rel="noopener noreferrer">Patreon</a><?= htmlspecialchars(omoInterfaceUiT('la_video_ci_dessous_presente_le_projet_et_sa_direction'), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    </div>
    <div class="omo-patreon-welcome__shell generic-drawer-content">

    <div class="omo-patreon-welcome__video">
        <iframe
            src="https://player.vimeo.com/video/1200446731"
            title="<?= htmlspecialchars(omoInterfaceUiT('presentation_opengovernance'), ENT_QUOTES, 'UTF-8') ?>"
            loading="lazy"
            allow="autoplay; fullscreen; picture-in-picture"
            allowfullscreen
        ></iframe>
    </div>

    <div class="omo-patreon-welcome__actions">
        <a
            class="omo-patreon-welcome__button omo-patreon-welcome__button--ghost"
            href="https://www.patreon.com/cw/OpenGovernance"
            target="_blank"
            rel="noopener noreferrer"
        ><?= htmlspecialchars(omoInterfaceUiT('voir_patreon'), ENT_QUOTES, 'UTF-8') ?></a>

        <?php if (!$patreonConnected): ?>
        <button type="button" class="omo-patreon-welcome__button" id="omoPatreonWelcomeConnect">
            <?= htmlspecialchars(omoInterfaceUiT('se_connecter_avec_patreon'), ENT_QUOTES, 'UTF-8') ?>
        </button>
        <?php elseif ($patreonConnected): ?>
        <div class="omo-patreon-welcome__status omo-patreon-welcome__status--success generic-soft-panel">
            <?= htmlspecialchars(omoInterfaceUiT('votre_compte_patreon_est_deja_connecte'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
    </div>
    </div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/patreon_welcome_popup.css') ?>">

<script>
(function () {
    var patreonConnectOrigin = <?= json_encode(patreonGetConnectOrigin(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var connectButton = document.getElementById('omoPatreonWelcomeConnect');

    function markPromptAsHandled() {
        if (!window.omoConfig || !window.omoConfig.patreonPrompt) {
            return;
        }

        window.omoConfig.patreonPrompt.shouldShow = false;
    }

    function handleConnectClick() {
        var width = 720;
        var height = 860;
        var left = Math.max(0, (window.screen.width - width) / 2);
        var top = Math.max(0, (window.screen.height - height) / 2);

        window.open(
            '/common/patreon_connect.php',
            'patreon_connect',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
        );
    }

    function handleMessage(event) {
        if (patreonConnectOrigin === '' || event.origin !== patreonConnectOrigin) {
            return;
        }

        if (!event.data || event.data.type !== 'patreon-connected') {
            return;
        }

        markPromptAsHandled();

        if (typeof window.commonTopbarCloseModal === 'function') {
            window.commonTopbarCloseModal();
        }
    }

    if (connectButton) {
        connectButton.addEventListener('click', handleConnectClick);
    }

    window.addEventListener('message', handleMessage);
    window.__omoPopupCleanup = function () {
        window.removeEventListener('message', handleMessage);
        if (connectButton) {
            connectButton.removeEventListener('click', handleConnectClick);
        }
    };
})();
</script>
