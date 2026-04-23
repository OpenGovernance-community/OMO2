<?php
require_once __DIR__ . '/bootstrap.php';

$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = commonGetCurrentUserId();

$applications = new \dbObject\ArrayApplication();
if ($currentOrganizationId > 0) {
    $applications->loadEnabledForOrganization($currentOrganizationId, $currentUserId);
}

$escape = static function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$renderMenuItem = static function (array $item) use ($escape) {
    $attributes = [
        'class' => 'menu-item',
        'data-hash' => $item['hash'] ?? '',
        'data-navigation-mode' => $item['navigationmode'] ?? 'drawer',
    ];

    if (!empty($item['drawer'])) {
        $attributes['data-drawer'] = $item['drawer'];
    }

    if (!empty($item['url'])) {
        $attributes['data-url'] = $item['url'];
    }

    if (!empty($item['directory'])) {
        $attributes['data-directory'] = $item['directory'];
    }

    $attributeParts = [];
    foreach ($attributes as $name => $value) {
        $attributeParts[] = $name . '="' . $escape($value) . '"';
    }
    ?>
<div <?= implode(PHP_EOL . '     ', $attributeParts) ?>>

    <span class="icon">
        <img src="<?= $escape($item['icon'] ?? '') ?>" class="icon-img">
    </span>
    <span class="label"><?= $escape($item['label'] ?? '') ?></span>
</div>
<?php
};
?>

<div class="menu-primary">
<?php foreach ($applications as $application): ?>
    <?php
    $renderMenuItem([
        'label' => $application->get('label'),
        'hash' => $application->getRouteHash(),
        'directory' => $application->get('directory'),
        'icon' => $application->get('icon'),
        'drawer' => $application->getResolvedDrawer(),
        'url' => $application->getResolvedUrl(),
        'navigationmode' => $application->getNavigationMode(),
    ]);
    ?>
<?php endforeach; ?>
</div>

<div class="menu-secondary">
<?php
$renderMenuItem([
    'label' => 'Paramètres',
    'hash' => 'parameters',
    'directory' => 'parameters',
    'icon' => '/img/settings.png',
    'drawer' => 'drawer_parameters',
    'url' => 'api/parameters/index.php',
    'navigationmode' => 'drawer',
]);
?>
</div>
