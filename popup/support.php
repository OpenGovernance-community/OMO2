<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/translations.php';

checklogin();
$sourceLang = [
    'support.title' => ['text' => 'Soutenir OpenGovernance', 'context' => 'Title of the support popup.'],
    'support.description' => ['text' => 'Votre soutien sur Patreon contribue au developpement des outils OpenGovernance.', 'context' => 'Explain how to support the project.'],
    'support.profile_hint' => ['text' => 'Ouvrez Mon profil > Patreon pour relier votre compte et synchroniser votre soutien.', 'context' => 'Explain where the Patreon account connection is available.'],
    'support.patreon' => ['text' => 'Soutenir sur Patreon', 'context' => 'Action opening the project Patreon campaign.'],
];
$lang = omoLoadTranslationBundle('support_popup', $sourceLang);
?>
<div class="generic-section generic-section--stack">
    <h2 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(t('support.title', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('support.description', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars(t('support.profile_hint', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
    <a class="generic-action-button generic-action-button--main" href="https://www.patreon.com/cw/OpenGovernance" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('support.patreon', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></a>
</div>
