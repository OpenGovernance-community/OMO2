<?php
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include 'inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once dirname(__DIR__, 3) . '/common/lms/navigation.php';

$parcours_id = (int)($_GET['idp'] ?? 0);
$initialMissionId = (int)($_GET['mid'] ?? 0);
$isEmbedded = !empty($_GET['embed']);
$user_id = (int)commonGetCurrentUserId();
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id, $user_id);
$canTrackProgress = lmsCanTrackProgress($accessContext);

if (empty($accessContext['exists'])) {
	http_response_code(404);
	echo omoLmsUiT('parcours_introuvable_2');
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(403);
	echo !empty($accessContext['blockedByPrerequisites'])
		? 'Prerequis non remplis'
		: 'Acces refuse';
	exit;
}

$parcoursRef = new \dbObject\Parcours();
$parcours = [
	'title' => omoLmsUiT('parcours_introuvable_2'),
	'description' => '',
];

if ($parcoursRef->load($parcours_id)) {
	$parcours = [
		'title' => (string)$parcoursRef->get('title'),
		'description' => (string)$parcoursRef->get('description'),
	];
}

$isAnonymousViewer = lmsIsAnonymousViewer($accessContext);
$isBasicCatalogMode = lmsIsBasicCatalogMode();
$completedParcoursIds = $user_id > 0 ? [] : lmsGetAnonymousCompletedParcoursIds();
$canEditCatalog = !$isBasicCatalogMode && lmsCurrentUserCanEditParcours((int)$org['id'], $user_id);
$catalogItems = [];
if ($isBasicCatalogMode) {
	$catalogItems = \dbObject\Parcours::fetchBasicCatalogWithProgress($user_id, $completedParcoursIds);
} elseif (!empty($accessContext['hasOrganizationAccess']) || commonCanAccessWithoutLogin($org)) {
	$catalogItems = \dbObject\Parcours::fetchForOrganizationWithProgress((int)$org['id'], $user_id, !empty($accessContext['hasOrganizationAccess']), $canEditCatalog);
}
$catalogUrl = '';
foreach ($catalogItems ?: [] as $catalogItem) {
	if (!empty($catalogItem['ispack']) && !$canEditCatalog && (int)($catalogItem['owner_organization_id'] ?? 0) !== (int)$org['id']) continue;
	$catalogUrl = lmsBuildLocalPath('/', ['embed' => $isEmbedded ? 1 : null, 'done_parcours_ids' => $completedParcoursIds ? implode(',', $completedParcoursIds) : null]);
	break;
}
$organizationColor = commonGetOrganizationExplicitColor($org);
$headerColor = $organizationColor;
$ownerOrganization = new \dbObject\Organization();
if ($ownerOrganization->load($parcoursRef->getOwnerOrganizationId())) {
	$headerColor = commonGetOrganizationExplicitColor(['color' => $ownerOrganization->get('color')]);
	if ($user_id <= 0) {
		$lmsTopbarBrand = [
			'brandLabel' => (string)$ownerOrganization->get('name'),
			'brandLogo' => (string)$ownerOrganization->get('logo'),
			'brandAlt' => (string)$ownerOrganization->get('name'),
			'showBrandLabelOnMobile' => true,
		];
	}
}
require_once dirname(__DIR__, 3) . '/common/avatar.php';
$headerTextColor = $headerColor !== '' ? commonAvatarResolveTextColor($headerColor) : '#ffffff';
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo htmlspecialchars($parcours['title']); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/common/assets/theme.css">
	<?= commonStylesheetTags('/shared_css.css') ?>
	<?php require dirname(__DIR__, 3) . '/common/lms/notifications.php'; ?>
	<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/std.css') ?>">
	<script src="<?= commonAssetUrl('/common/assets/components.js') ?>"></script>
	<script src="/shared_functions.js"></script>
	<script>
	sharedApplyDocumentTheme({
		preference: <?php echo $user_id > 0 ? 'undefined' : "'system'"; ?>
	});
	</script>
	<style>
		:root {
			<?php if ($organizationColor !== ''): ?>
			--primary: <?php echo htmlspecialchars($organizationColor); ?>;
			<?php endif; ?>
		}
	</style>

	<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/parcours.css') ?>">
	<link rel="stylesheet" href="<?= commonAssetUrl('/common/lms/navigation.css') ?>">
	<link rel="stylesheet" href="<?= commonAssetUrl('/common/lms/content-indicators.css') ?>">
</head>
<body class="<?php echo $isEmbedded ? 'lms-embed-mode' : ''; ?>">
<?php if (!$isEmbedded): ?>
<?php include 'inc/menu.php'; ?>
<header class="org-banner org-banner--compact" style="background-color: <?= htmlspecialchars($headerColor !== '' ? $headerColor : 'var(--primary)', ENT_QUOTES, 'UTF-8') ?>; color: <?= htmlspecialchars($headerTextColor, ENT_QUOTES, 'UTF-8') ?>">
<?php commonLmsRenderCatalogBackLink($catalogUrl); ?>
<h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
<?php if ($parcours['description'] !== ''): ?>
<p><?php echo htmlspecialchars($parcours['description']); ?></p>
<?php endif; ?>
</header>
<?php endif; ?>

<?php include 'inc/parcours_content.php'; ?>
</body>
</html>
