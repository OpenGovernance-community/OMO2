<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/common/patreon.php';

$sourceLang = [
    'title' => ['text' => 'Ils soutiennent OMO2', 'context' => 'Public Patreon supporter gallery title.'],
    'intro' => ['text' => 'Merci aux organisations et aux personnes qui font grandir OMO2 et soutiennent un logiciel ouvert, libre et accessible.', 'context' => 'Public supporter gallery introduction.'],
    'organizations' => ['text' => 'Les organisations', 'context' => 'Heading above organization supporter cards.'],
    'people' => ['text' => 'Les personnes', 'context' => 'Heading above individual supporter portraits, below organizations.'],
    'empty.organizations' => ['text' => 'Les organisations qui soutiennent OMO2 via un abonnement Patreon de 40 euros ou plus apparaitront ici.', 'context' => 'Empty state for organization supporters.'],
    'empty.people' => ['text' => 'Les personnes ayant un abonnement Patreon actif connecte a OMO2 apparaitront ici.', 'context' => 'Empty state for individual supporters.'],
    'organization' => ['text' => 'Organisation partenaire', 'context' => 'Fallback for a supporter organization without a name.'],
    'person' => ['text' => 'Soutien Patreon', 'context' => 'Fallback for an individual supporter without a public name.'],
    'support' => ['text' => 'Soutenir OMO2 sur Patreon', 'context' => 'Supporter gallery call to action.'],
    'tier.400' => ['text' => 'Soutien libre', 'context' => 'Patreon tier starting at 4 euros; also groups smaller free contributions.'],
    'tier.800' => ['text' => 'Utilisateur', 'context' => 'Patreon tier starting at 8 euros.'],
    'tier.1600' => ['text' => 'Contributeur', 'context' => 'Patreon tier starting at 16 euros.'],
    'tier.4000' => ['text' => 'Nous utilisons et soutenons (social)', 'context' => 'Patreon tier starting at 40 euros.'],
    'tier.8000' => ['text' => 'Nous utilisons et soutenons', 'context' => 'Patreon tier starting at 80 euros.'],
    'tier.16000' => ['text' => 'Nous integrons et diffusons', 'context' => 'Patreon tier starting at 160 euros.'],
    'tier.30000' => ['text' => 'J’accelere le projet', 'context' => 'Patreon tier starting at 300 euros.'],
];
$locale = translationBundleResolveRequestLocale();
$lang = loadTranslationBundle('public_supporters', $locale, $sourceLang);
$t = static fn (string $key): string => t($key, [], $lang, $sourceLang);
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// Each list switches independently to tiers when it exceeds ten entries.
$tiers = [30000, 16000, 8000, 4000, 1600, 800, 400];
$groupItems = static function (array $items) use ($tiers): array {
    shuffle($items);
    if (count($items) <= 10) return $items === [] ? [] : [0 => $items];
    $groups = [];
    foreach ($items as $item) {
        $tier = 400;
        foreach ($tiers as $minimum) {
            if ($item['amountCents'] >= $minimum) { $tier = $minimum; break; }
        }
        $groups[$tier][] = $item;
    }
    krsort($groups, SORT_NUMERIC);
    return $groups;
};
$campaignId = patreonGetCreatorCampaignId();
$sections = [
    'organizations' => $groupItems(\dbObject\OrganizationPatreon::getSupporterGalleryItems($campaignId)),
    'people' => $groupItems(\dbObject\UserPatreon::getSupporterGalleryItems($campaignId)),
];
?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($t('title')) ?></title>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/supporters.css') ?>">
</head>
<body class="supporters-page">
<main class="generic-page-shell generic-stack generic-stack--roomy">
    <header class="generic-hero-panel generic-stack">
        <h1 class="generic-card-title generic-card-title--large"><?= $e($t('title')) ?></h1>
        <p><?= $e($t('intro')) ?></p>
    </header>
    <?php foreach ($sections as $type => $groups): ?>
    <section class="generic-stack generic-stack--roomy" aria-labelledby="supporters-<?= $e($type) ?>">
        <h2 id="supporters-<?= $e($type) ?>" class="generic-card-title generic-card-title--section"><?= $e($t($type)) ?></h2>
        <?php if ($groups === []): ?>
            <p class="generic-soft-panel"><?= $e($t('empty.' . $type)) ?></p>
        <?php endif; ?>
        <?php foreach ($groups as $tier => $items): ?>
        <div class="generic-stack">
            <?php if ($tier > 0): ?>
            <h3 class="generic-card-title generic-card-title--small"><?= $e($t('tier.' . $tier)) ?> <span class="supporters-tier-amount"><?= (int)($tier / 100) ?> €</span></h3>
            <?php endif; ?>
            <ul class="supporters-<?= $e($type) ?>">
            <?php foreach ($items as $item): ?>
                <?php
                $name = $item['displayName'] !== '' ? $item['displayName'] : $t($type === 'people' ? 'person' : 'organization');
                $photo = trim((string)$item['photoUrl']);
                // Only local image paths and HTTP(S) URLs are allowed in the public document.
                if ($photo !== '' && !preg_match('~^(?:/(?!/)|https?://)~i', $photo)) $photo = '';
                ?>
                <li class="<?= $type === 'organizations' ? 'generic-soft-panel generic-stack supporters-card' : 'supporters-person' ?>">
                    <span class="supporters-image<?= $type === 'people' ? ' supporters-image--round' : '' ?>" role="img" aria-label="<?= $e($name) ?>" title="<?= $e($name) ?>">
                        <span aria-hidden="true"><?= $e($item['initials']) ?></span>
                        <?php if ($photo !== ''): ?><img src="<?= $e($photo) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php endif; ?>
                    </span>
                    <?php if ($type === 'organizations'): ?>
                        <h3 class="generic-card-title generic-card-title--medium">
                        <?php if ($item['url'] !== ''): ?><a href="<?= $e($item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($name) ?></a><?php else: ?><?= $e($name) ?><?php endif; ?>
                        </h3>
                        <?php if ($item['description'] !== ''): ?><p class="supporters-description"><?= $e($item['description']) ?></p><?php endif; ?>
                    <?php else: ?>
                        <span class="supporters-person__name"><?= $e($name) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <p><a class="generic-action-button" href="https://www.patreon.com/cw/OpenGovernance" target="_blank" rel="noopener noreferrer"><?= $e($t('support')) ?></a></p>
</main>
<script src="<?= commonAssetUrl('/common/assets/supporters.js') ?>"></script>
</body>
</html>
