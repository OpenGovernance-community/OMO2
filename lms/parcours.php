<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';

include 'inc/org.php';

$parcours_id = (int)($_GET["idp"] ?? 0);
$isEmbedded = !empty($_GET['embed']);

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

?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($parcours['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="/lms/css/std.css">

 <style>
 

.missions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: left;
}

.card {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 15px;
    background: var(--bg-card);
    box-shadow: var(--shadow);

    flex: 1 1 calc(100% - 10px);
    max-width: 400px;
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



.branch {
    margin-bottom: 25px;
    width: 100dvw;
}

.branch-header {
    width: 100%;
    background: var(--primary-light);
    padding: 12px;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    cursor: pointer;
    box-sizing: border-box;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.branch-header h2{
    margin: 0;
    font-size: 1.2em;
}

.branch.closed .branch-header {
    border-radius: var(--border-radius);
}
.branch-header::after {
    content: "▶";
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
    border-radius: 0px 0px var(--border-radius) var(--border-radius);
    background: var(--bg-branch);

    transition: transform 0.2s ease;
}

/* Progress */
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

/* Switch */
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
    color: white;
}

/* Locked */
.card.locked {
    opacity: 0.6;
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
    </style>
</head>
<body class="<?= $isEmbedded ? 'lms-embed-mode' : '' ?>">
<?php if (!$isEmbedded): ?>
<div class="org-banner" style="background-color: <?=$org['color']??"#CCC" ?>">
<?php include 'inc/menu.php'; ?>
<h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
<p><?php echo htmlspecialchars($parcours['description']); ?></p>
</div>
<?php endif; ?>

<?php include 'inc/parcours_content.php'; ?>
</body>
</html>
