<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/organization_applications_access.php';

$sourceLang = [
    'sidebar.applications.manage_label' => [
        'text' => 'Gérer',
        'context' => 'Label of the sidebar item used to open the application management picker.'
    ],
    'sidebar.applications.manage_title' => [
        'text' => 'Gérer les applications',
        'context' => 'Tooltip of the sidebar item used to open the application management picker.'
    ],
    'sidebar.parameters.label' => [
        'text' => 'Paramètres',
        'context' => 'Label of the parameters entry in the sidebar.'
    ],
];

$lang = omoLoadTranslationBundle('omo_get_sidebar_panel', $sourceLang);

$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = commonGetCurrentUserId();
$canManageApplications = omoCurrentUserCanManageOrganizationApplications($currentOrganizationId, $currentUserId);

$applications = new \dbObject\ArrayApplication();
if ($currentOrganizationId > 0) {
    $applications->loadEnabledForOrganization($currentOrganizationId, $currentUserId);
}

$escape = 'omoApiEscape';

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
        <img src="<?= $escape($item['icon'] ?? '') ?>" class="icon-img black-icon">
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
<?php if ($canManageApplications): ?>
    <div
        class="menu-item menu-item--add"
        data-omo-open-app-picker="1"
        title="<?= $escape(t('sidebar.applications.manage_title', [], $lang, $sourceLang)) ?>"
    >
        <span class="icon"><img src="images/tools/plus.png" class="icon-img black-icon" style='width:20px;height:20px; margin:2px'></span>
        <span class="label"><?= $escape(t('sidebar.applications.manage_label', [], $lang, $sourceLang)) ?></span>
    </div>
<?php endif; ?>
</div>

<div class="menu-secondary">
<?php
$renderMenuItem([
    'label' => t('sidebar.parameters.label', [], $lang, $sourceLang),
    'hash' => 'parameters',
    'directory' => 'parameters',
    'icon' => '/img/settings.png',
    'drawer' => 'drawer_parameters',
    'url' => 'api/parameters/index.php',
    'navigationmode' => 'drawer',
]);
?>
</div>
