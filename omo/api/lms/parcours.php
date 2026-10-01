<?php
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
	echo 'Parcours introuvable';
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
	'title' => 'Parcours introuvable',
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
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo htmlspecialchars($parcours['title']); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/common/assets/theme.css">
	<?= commonStylesheetTags('/shared_css.css') ?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars(omoLmsBuildPath('/css/std.css')); ?>">
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
<div class="org-banner" style="background-color: <?php echo htmlspecialchars($org['color'] ?? '#CCC'); ?>">
<h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
<p><?php echo htmlspecialchars($parcours['description']); ?></p>
<?php if ($isAnonymousViewer): ?>
<div class="lms-anonymous-note">
	Votre avancement est memorise localement sur cet appareil tant que vous restez deconnecte.
</div>
<?php elseif (!$canTrackProgress): ?>
<div class="lms-anonymous-note">
	La validation de progression n est pas disponible dans ce contexte.
</div>
<?php endif; ?>
</div>
<?php endif; ?>

<?php include 'inc/parcours_content.php'; ?>
</body>
</html>
