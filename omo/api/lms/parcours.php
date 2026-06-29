<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include 'inc/org.php';
require_once __DIR__ . '/inc/access.php';

$parcours_id = (int)($_GET['idp'] ?? 0);
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
	<link rel="stylesheet" href="/shared_css.css">
	<link rel="stylesheet" href="<?php echo htmlspecialchars(omoLmsBuildPath('/css/std.css')); ?>">
	<script src="/shared_functions.js"></script>
	<script>
	sharedApplyDocumentTheme({
		preference: <?php echo $user_id > 0 ? 'undefined' : "'system'"; ?>
	});
	</script>
	<style>
		:root {
			<?php if ($organizationColor !== ''): ?>
			--color-primary: <?php echo htmlspecialchars($organizationColor); ?>;
			<?php endif; ?>
		}
	</style>

	<style>
		.missions {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			justify-content: left;
		}

		.card {
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius);
			overflow: hidden;
			background: var(--bg-card);
			box-shadow: var(--shadow);
			flex: 1 1 calc(100% - 10px);
			max-width: 400px;
			display: flex;
			flex-direction: column;
			cursor: pointer;
			transition: 0.2s;
		}

		@media (min-width: 600px) {
			.card {
				flex: 1 1 calc(50% - 50px);
			}
		}

		@media (min-width: 900px) {
			.card {
				flex: 1 1 calc(33% - 50px);
			}
		}

		.card:hover {
			transform: translateY(-3px);
			box-shadow: 0 18px 34px rgba(15,23,42,0.12);
		}

		.card-content {
			padding: 15px;
			display: flex;
			flex-direction: column;
			flex: 1;
		}

		.card-content h3 {
			margin-top: 0;
		}

		.card-content p {
			margin-top: 0;
		}

		.card-footer {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
			margin-top: auto;
		}

		.card-meta {
			color: var(--text-light);
			font-size: 0.85rem;
			line-height: 1.4;
		}

		.card button,
		.view-switch button {
			margin-top: 0;
		}

		.branch {
			margin-bottom: 25px;
			width: 100dvw;
		}

		.branch-header {
			width: 100%;
			background: var(--primary-light);
			padding: 12px;
			border: 1px solid color-mix(in srgb, var(--primary) 16%, var(--border-color));
			border-bottom: 0;
			border-radius: var(--border-radius) var(--border-radius) 0 0;
			cursor: pointer;
			box-sizing: border-box;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.branch-header h2 {
			margin: 0;
			font-size: 1.2em;
		}

		.branch.closed .branch-header {
			border-radius: var(--border-radius);
			border-bottom: 1px solid color-mix(in srgb, var(--primary) 16%, var(--border-color));
		}

		.branch-header::after {
			content: "\25B6";
			float: right;
			transition: transform 0.2s ease;
		}

		.branch:not(.closed) .branch-header::after {
			transform: rotate(90deg);
		}

		.branch.closed .missions {
			display: none;
		}

		.branch .missions {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			padding: 10px;
			border: 1px solid var(--border-color);
			border-radius: 0 0 var(--border-radius) var(--border-radius);
			background: var(--bg-branch);
			transition: transform 0.2s ease;
		}

		.progress-container {
			width: 100%;
			height: 20px;
			background: var(--progress-bg);
			border-radius: 10px;
			overflow: hidden;
			margin-bottom: 20px;
		}

		.progress-bar {
			height: 100%;
			width: 0%;
			background: var(--primary);
			transition: width 0.3s ease;
		}

		.lms-pack-children {
			display: grid;
			gap: 18px;
		}

		.lms-pack-children__intro p,
		.lms-pack-children__empty {
			color: var(--text-light);
			line-height: 1.5;
		}

		.lms-pack-children__empty {
			padding: 16px 18px;
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius);
			background: var(--bg-card);
		}

		.view-switch {
			display: flex;
			gap: 10px;
			margin-bottom: 15px;
		}

		.view-switch button {
			background: var(--border-color);
			color: var(--text-main);
		}

		.view-switch button.active {
			background: var(--primary);
			color: var(--color-text-inverse, #ffffff);
		}

		.card.locked {
			opacity: 0.6;
			cursor: default;
			transform: none;
			box-shadow: var(--shadow);
		}

		.card.locked button {
			background: var(--disabled);
			cursor: not-allowed;
		}

		body.lms-embed-mode {
			background: var(--bg-main);
		}

		.lms-parcours-content--embed {
			padding-top: 20px;
		}

		.lms-parcours-embed-header {
			margin-bottom: 20px;
		}

		.lms-parcours-embed-header h1 {
			margin: 0 0 8px;
			text-align: left;
		}

		.lms-parcours-embed-header p {
			margin: 0;
			color: var(--text-light);
			line-height: 1.5;
		}

		.lms-anonymous-note {
			margin: 16px 0 0;
			padding: 12px 16px;
			border-radius: var(--border-radius);
			background: color-mix(in srgb, var(--primary) 10%, var(--bg-card));
			border: 1px solid color-mix(in srgb, var(--primary) 26%, var(--border-color));
			color: var(--text-main);
		}
	</style>
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
