<?php
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include 'inc/org.php';
require_once __DIR__ . '/inc/access.php';

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
$organizationColor = commonGetOrganizationExplicitColor($org);
$headerColor = $organizationColor;
$ownerOrganization = new \dbObject\Organization();
if ($ownerOrganization->load($parcoursRef->getOwnerOrganizationId())) {
	$headerColor = commonGetOrganizationExplicitColor(['color' => $ownerOrganization->get('color')]);
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
</head>
<body class="<?php echo $isEmbedded ? 'lms-embed-mode' : ''; ?>">
<?php if (!$isEmbedded): ?>
<?php include 'inc/menu.php'; ?>
<header class="org-banner org-banner--compact" style="background-color: <?= htmlspecialchars($headerColor !== '' ? $headerColor : 'var(--primary)', ENT_QUOTES, 'UTF-8') ?>; color: <?= htmlspecialchars($headerTextColor, ENT_QUOTES, 'UTF-8') ?>">
<h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
<?php if ($parcours['description'] !== ''): ?>
<p><?php echo htmlspecialchars($parcours['description']); ?></p>
<?php endif; ?>
</header>
<?php endif; ?>

<?php include 'inc/parcours_content.php'; ?>
</body>
</html>
