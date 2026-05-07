<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/patreon.php';

$currentUserId = (int)commonGetCurrentUserId();
$patreonConfigured = patreonIsConfigured('oauth');
$patreonConfigurationMessage = patreonGetConfigurationMessage('oauth');
$patreonConnection = false;
$patreonConnected = false;

if ($currentUserId > 0 && $patreonConfigured) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}
?>
<div class="omo-patreon-welcome">
    <div class="omo-patreon-welcome__hero">
        <div class="omo-patreon-welcome__eyebrow">OpenGovernance</div>
        <h3 class="omo-patreon-welcome__title">Contribuez au developpement du logiciel</h3>
        <p class="omo-patreon-welcome__text">
            Si cet outil vous est utile, vous pouvez soutenir son evolution sur
            <a href="https://www.patreon.com/cw/OpenGovernance" target="_blank" rel="noopener noreferrer">Patreon</a>.
            La video ci-dessous presente le projet et sa direction.
        </p>
    </div>

    <div class="omo-patreon-welcome__video">
        <iframe
            src="https://player.vimeo.com/video/1188749847"
            title="Presentation OpenGovernance"
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
        >Voir Patreon</a>

        <?php if (!$patreonConnected): ?>
        <button type="button" class="omo-patreon-welcome__button" id="omoPatreonWelcomeConnect">
            Se connecter avec Patreon
        </button>
        <?php elseif ($patreonConnected): ?>
        <div class="omo-patreon-welcome__status omo-patreon-welcome__status--success">
            Votre compte Patreon est deja connecte.
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.omo-patreon-welcome {
    display: grid;
    gap: 18px;
    color: #0f172a;
}

.omo-patreon-welcome__hero {
    display: grid;
    gap: 10px;
}

.omo-patreon-welcome__eyebrow {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0f766e;
}

.omo-patreon-welcome__title {
    margin: 0;
    font-size: 26px;
    line-height: 1.2;
}

.omo-patreon-welcome__text {
    margin: 0;
    color: #475569;
    line-height: 1.6;
}

.omo-patreon-welcome__text a {
    color: #0f766e;
    font-weight: 700;
}

.omo-patreon-welcome__video {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    background: #020617;
    aspect-ratio: 16 / 9;
}

.omo-patreon-welcome__video iframe {
    width: 100%;
    height: 100%;
    border: 0;
}

.omo-patreon-welcome__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.omo-patreon-welcome__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 18px;
    border: 0;
    border-radius: 999px;
    background: #0f766e;
    color: #ffffff;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.omo-patreon-welcome__button--ghost {
    background: #e2e8f0;
    color: #0f172a;
}

.omo-patreon-welcome__status {
    padding: 11px 14px;
    border-radius: 14px;
    font-weight: 600;
}

.omo-patreon-welcome__status--success {
    background: #dcfce7;
    color: #166534;
}

.omo-patreon-welcome__status--warning {
    background: #fff7ed;
    color: #9a3412;
}

@media (max-width: 640px) {
    .omo-patreon-welcome__title {
        font-size: 22px;
    }

    .omo-patreon-welcome__actions {
        flex-direction: column;
        align-items: stretch;
    }

    .omo-patreon-welcome__button {
        width: 100%;
    }
}
</style>

<script>
(function () {
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
        if (event.origin !== window.location.origin) {
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
