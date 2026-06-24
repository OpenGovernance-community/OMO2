<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include 'inc/org.php';
require_once __DIR__ . '/inc/access.php';

$isEmbedded = !empty($_GET['embed']);
$isBasicCatalogMode = lmsIsBasicCatalogMode();
$user_id = (int)($_SESSION['currentUser'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($user_id, (int)$org['id']);
$canCreateParcours = !$isBasicCatalogMode && $user_id > 0 && $hasOrganizationAccess;
$organizationColor = commonGetOrganizationExplicitColor($org);
$parcours = $isBasicCatalogMode
    ? \dbObject\Parcours::fetchBasicCatalogWithProgress($user_id)
    : \dbObject\Parcours::fetchForOrganizationWithProgress($org['id'], $user_id, $hasOrganizationAccess);
$parcours = is_array($parcours) ? $parcours : [];
$pendingParcours = [];
$completedParcours = [];

foreach ($parcours as $parcoursItem) {
    $totalMissions = (int)($parcoursItem['total_missions'] ?? 0);
    $doneMissions = (int)($parcoursItem['done_missions'] ?? 0);
    $percent = $totalMissions > 0 ? (int)round(($doneMissions / $totalMissions) * 100) : 0;

    if ($totalMissions > 0 && $percent >= 100) {
        $completedParcours[] = $parcoursItem;
        continue;
    }

    $pendingParcours[] = $parcoursItem;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($isBasicCatalogMode ? 'Tutoriels OMO' : $org['name']); ?></title>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(omoLmsBuildPath('/css/std.css')); ?>">
    <?php if ($isEmbedded && $canCreateParcours): ?>
    <script src="/common/assets/components.js"></script>
    <?php endif; ?>
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
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 350px));
            justify-content: center;
            gap: 20px;
        }

        .lms-parcours-sections {
            display: grid;
            gap: 34px;
        }

        .lms-parcours-section {
            display: grid;
            gap: 18px;
        }

        .lms-parcours-section[hidden] {
            display: none !important;
        }

        .lms-parcours-separator {
            max-width: 960px;
            width: 100%;
            margin: 0 auto;
            padding-top: 28px;
            border-top: 1px solid color-mix(in srgb, var(--primary) 20%, var(--border-color));
        }

        .lms-parcours-section__intro {
            max-width: 960px;
            margin: 0 auto;
            text-align: center;
        }

        .lms-parcours-section__intro h2 {
            margin: 0 0 8px;
        }

        .lms-parcours-section__intro p {
            margin: 0;
            color: var(--text-light);
            line-height: 1.5;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            background: var(--bg-card);
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow);
            position: relative;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
        }

        .card-image {
            width: 100%;
            aspect-ratio: 16/6;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(15,23,42,0.12);
        }

        .card--create {
            border-style: dashed;
            border-width: 2px;
            border-color: color-mix(in srgb, var(--primary) 38%, var(--border-color));
            overflow: hidden;
            cursor: default;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--primary) 16%, transparent), transparent 38%),
                linear-gradient(180deg, color-mix(in srgb, var(--primary) 7%, var(--bg-card)), var(--bg-card));
        }

        .card--create .card-content {
            justify-content: space-between;
            padding: 0;
        }

        .card--create:hover {
            border-color: color-mix(in srgb, var(--primary) 72%, var(--border-color));
        }

        .card-create-visual {
            position: relative;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            border-bottom: 1px solid color-mix(in srgb, var(--primary) 18%, var(--border-color));
            background:
                linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.4)),
                color-mix(in srgb, var(--primary) 6%, #ffffff);
        }

        .card-create-visual img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            filter: grayscale(1) contrast(1.02) brightness(1.02);
        }

        .card-create-visual::after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 46%;
            background: linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,0.86));
        }

        .card-create-plus {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 2;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 300;
            background: rgba(255,255,255,0.92);
            color: var(--primary);
            box-shadow: 0 10px 25px rgba(15,23,42,0.12);
        }

        .card-create-body {
            padding: 18px 18px 16px;
        }

        .card-create-copy {
            color: var(--text-light);
            line-height: 1.5;
        }

        .card-create-footer {
            margin-top: 24px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
        }

        .card-create-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .card-create-actions button {
            margin-top: 0;
        }

        .card-create-import {
            background: transparent;
            color: var(--primary);
            border-color: color-mix(in srgb, var(--primary) 34%, var(--border-color));
        }

        .card-create-import:hover {
            background: color-mix(in srgb, var(--primary) 10%, var(--bg-card));
        }

        .card-create-kicker {
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--primary);
        }

        .card-create-body h3 {
            margin: 6px 0 10px;
        }

        .card-menu-wrap {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 3;
        }

        .card-menu-trigger {
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.94);
            color: var(--text-main);
            font-size: 24px;
            line-height: 1;
            box-shadow: 0 10px 25px rgba(15,23,42,0.12);
            cursor: pointer;
        }

        .card-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 170px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--bg-card);
            box-shadow: 0 16px 40px rgba(15,23,42,0.16);
            display: none;
        }

        .card-menu.is-open {
            display: block;
        }

        .card-menu-item {
            width: 100%;
            border: 0;
            background: transparent;
            color: var(--text-main);
            text-align: left;
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
        }

        .card-menu-item:hover {
            background: color-mix(in srgb, var(--primary) 10%, var(--bg-card));
        }

        .banner-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0.4;
        }

        .banner-content {
            position: relative;
            z-index: 2;
        }

        .logo-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--bg-card);
            padding: 5px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .progress-circle {
            position: relative;
            width: 60px;
            height: 60px;
            display: inline-block;
            margin: 5px;
        }

        .progress-circle svg {
            transform: rotate(-90deg);
        }

        circle {
            fill: none;
            stroke-width: 5;
        }

        .bg {
            stroke: var(--progress-bg);
        }

        .progress {
            stroke: var(--primary);
            stroke-linecap: round;
            transition: stroke-dashoffset 0.6s ease;
        }

        .label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
        }

        .lms-access-note {
            max-width: 860px;
            margin: 0 auto 24px;
            padding: 14px 18px;
            background: color-mix(in srgb, var(--primary) 10%, var(--bg-card));
            border: 1px solid color-mix(in srgb, var(--primary) 26%, var(--border-color));
            border-radius: var(--border-radius);
            color: var(--text-main);
        }

        .lms-access-note strong {
            display: block;
            margin-bottom: 6px;
        }

        body.lms-embed-mode {
            background: var(--bg-main);
        }

        .lms-index-content--embed {
            padding-top: 20px;
        }

        .lms-index-embed-header {
            max-width: 960px;
            margin: 0 auto 24px;
        }

        .lms-index-embed-header h1 {
            margin: 0 0 8px;
            text-align: left;
        }

        .lms-index-embed-header p {
            margin: 0;
            color: var(--text-light);
            line-height: 1.5;
        }

        @media (max-width: 720px) {
            .card-create-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .card-create-actions {
                width: 100%;
            }

            .card-create-actions button {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body class="<?php echo $isEmbedded ? 'lms-embed-mode' : ''; ?>">
<?php
if (!$isEmbedded) {
    include 'inc/menu.php';
}
?>
<div class="content<?php echo $isEmbedded ? ' lms-index-content--embed' : ''; ?>">
<?php if ($isEmbedded): ?>
<div class="lms-index-embed-header">
    <h1><?php echo htmlspecialchars($isBasicCatalogMode ? 'Tutoriels OMO' : $org['name']); ?></h1>
    <p><?php echo htmlspecialchars($isBasicCatalogMode ? 'Parcours de prise en main' : 'Parcours de formation'); ?></p>
</div>
<?php endif; ?>
<?php if (!$isEmbedded && !$isBasicCatalogMode): ?>
<div class="org-banner" style="background-color: <?php echo htmlspecialchars($org['color']); ?>">

    <?php if (!empty($org['banner'])): ?>
        <div class="banner-bg" style="background-image: url('<?php echo htmlspecialchars($org['banner']); ?>')"></div>
    <?php endif; ?>

    <div class="banner-content">
        <?php if (!empty($org['logo'])): ?>
            <div class="logo-wrapper">
                <img src="<?php echo htmlspecialchars($org['logo']); ?>" alt="logo">
            </div>
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($org['name']); ?></h1>
    </div>
</div>
<?php endif; ?>

<h1><?php echo htmlspecialchars($isBasicCatalogMode ? 'Tutoriels de prise en main' : 'Parcours de formation'); ?></h1>

<div class="lms-parcours-sections">
<section class="lms-parcours-section" id="lms-parcours-section-pending">
<div class="container" id="lms-parcours-pending-grid">
<?php foreach ($pendingParcours as $p):
    $total = (int)$p['total_missions'];
    $done = (int)$p['done_missions'];
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;
    $canEditThisParcours = $canCreateParcours && (int)($p['owner_organization_id'] ?? 0) === (int)$org['id'];
?>
<div
    class="card"
    data-parcours-card="1"
    data-parcours-id="<?php echo (int)$p['id']; ?>"
    data-can-edit="<?php echo $canEditThisParcours ? '1' : '0'; ?>"
    data-total-missions="<?php echo $total; ?>"
    data-local-progress="<?php echo $user_id > 0 ? '0' : '1'; ?>"
    onclick="goToParcours(<?php echo (int)$p['id']; ?>)"
>
    <?php if ($canEditThisParcours): ?>
        <div class="card-menu-wrap" onclick="event.stopPropagation()">
            <button
                type="button"
                class="card-menu-trigger"
                aria-label="Actions"
                onclick="toggleParcoursCardMenu(event, <?php echo (int)$p['id']; ?>)"
            >...</button>
            <div class="card-menu" id="parcours-card-menu-<?php echo (int)$p['id']; ?>">
                <button type="button" class="card-menu-item" onclick="openEditParcoursDrawer(event, <?php echo (int)$p['id']; ?>)">Editer</button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($p['image'])): ?>
        <div class="card-image">
            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
        </div>
    <?php endif; ?>

    <div class="card-content">
        <h3><?php echo htmlspecialchars($p['title']); ?></h3>
        <div><?php echo htmlspecialchars($p['description']); ?></div>

        <div class="card-footer">
            <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
            <button class="open-btn">Ouvrir</button>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if ($canCreateParcours): ?>
<div
    class="card card--create"
    data-parcours-create-card="1"
>
    <div class="card-content">
        <div class="card-create-visual">
            <img src="<?php echo htmlspecialchars(omoLmsBuildPath('/img/create-parcours-card.png')); ?>" alt="">
            <div class="card-create-plus" aria-hidden="true">+</div>
        </div>
        <div class="card-create-body">
            <div class="card-create-kicker">Parcours</div>
            <h3>Ajouter un parcours</h3>
            <div class="card-create-copy">Creer un nouveau parcours ou importer un parcours deja partage comme public ou basic.</div>

            <div class="card-create-footer">
                <span class="card-create-copy">Les deux actions s ouvrent directement dans le drawer.</span>
                <div class="card-create-actions">
                    <button class="card-create-import" type="button" onclick="openImportParcoursDrawer(event)">Importer</button>
                    <button class="open-btn" type="button" onclick="openCreateParcoursDrawer(event)">Nouveau</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>
</section>

<section class="lms-parcours-section" id="lms-parcours-section-completed" <?php echo count($completedParcours) === 0 ? 'hidden' : ''; ?>>
    <div class="lms-parcours-separator">
        <div class="lms-parcours-section__intro">
            <h2>Parcours termines</h2>
            <p>Retrouvez ici les parcours deja completes a 100%.</p>
        </div>
    </div>

    <div class="container" id="lms-parcours-completed-grid">
    <?php foreach ($completedParcours as $p):
        $total = (int)$p['total_missions'];
        $done = (int)$p['done_missions'];
        $percent = $total > 0 ? round(($done / $total) * 100) : 0;
        $canEditThisParcours = $canCreateParcours && (int)($p['owner_organization_id'] ?? 0) === (int)$org['id'];
    ?>
    <div
        class="card"
        data-parcours-card="1"
        data-parcours-id="<?php echo (int)$p['id']; ?>"
        data-can-edit="<?php echo $canEditThisParcours ? '1' : '0'; ?>"
        data-total-missions="<?php echo $total; ?>"
        data-local-progress="<?php echo $user_id > 0 ? '0' : '1'; ?>"
        onclick="goToParcours(<?php echo (int)$p['id']; ?>)"
    >
        <?php if ($canEditThisParcours): ?>
            <div class="card-menu-wrap" onclick="event.stopPropagation()">
                <button
                    type="button"
                    class="card-menu-trigger"
                    aria-label="Actions"
                    onclick="toggleParcoursCardMenu(event, <?php echo (int)$p['id']; ?>)"
                >...</button>
                <div class="card-menu" id="parcours-card-menu-<?php echo (int)$p['id']; ?>">
                    <button type="button" class="card-menu-item" onclick="openEditParcoursDrawer(event, <?php echo (int)$p['id']; ?>)">Editer</button>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($p['image'])): ?>
            <div class="card-image">
                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
            </div>
        <?php endif; ?>

        <div class="card-content">
            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
            <div><?php echo htmlspecialchars($p['description']); ?></div>

            <div class="card-footer">
                <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
                <button class="open-btn">Ouvrir</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</section>
</div>
</div>

<script>
const lmsIndexViewer = {
    userId: <?php echo (int)$user_id; ?>,
    organizationId: <?php echo (int)$org['id']; ?>,
    isEmbedded: <?php echo $isEmbedded ? 'true' : 'false'; ?>,
    canCreateParcours: <?php echo $canCreateParcours ? 'true' : 'false'; ?>
};
const lmsParcoursBasePath = <?php echo json_encode(lmsBuildLocalPath('/parcours.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursCreatePath = <?php echo json_encode(lmsBuildLocalPath('/parcours_create.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursImportPath = <?php echo json_encode(lmsBuildLocalPath('/parcours_import.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursImportSavePath = <?php echo json_encode(lmsBuildLocalPath('/import_parcours.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursEditBasePath = <?php echo json_encode(lmsBuildLocalPath('/parcours_create.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsMissionEditBasePath = <?php echo json_encode(lmsBuildLocalPath('/mission_edit.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursMissionPanelBasePath = <?php echo json_encode(lmsBuildLocalPath('/parcours_missions_panel.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursMissionAddPath = <?php echo json_encode(lmsBuildLocalPath('/parcours_mission_add.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursMissionCreatePath = <?php echo json_encode(lmsBuildLocalPath('/parcours_mission_create.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsParcoursMissionReorderPath = <?php echo json_encode(lmsBuildLocalPath('/parcours_mission_reorder.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsMissionHomeworkReorderPath = <?php echo json_encode(lmsBuildLocalPath('/mission_homework_reorder.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const lmsMissionQuestionReorderPath = <?php echo json_encode(lmsBuildLocalPath('/mission_question_reorder.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

function getAnonymousProgressKey(parcoursId) {
    return `lms_progress_${lmsIndexViewer.organizationId}_${parcoursId}`;
}

function getAnonymousDoneMissionIds(parcoursId) {
    try {
        const rawValue = localStorage.getItem(getAnonymousProgressKey(parcoursId));
        if (!rawValue) {
            return [];
        }

        const parsed = JSON.parse(rawValue);
        const missions = parsed && parsed.missions && typeof parsed.missions === 'object'
            ? Object.keys(parsed.missions)
            : [];

        return missions
            .map(value => Number(value))
            .filter(value => Number.isInteger(value) && value > 0);
    } catch (error) {
        return [];
    }
}

function resolveCardPercent(card, fallbackPercent) {
    if (Number(lmsIndexViewer.userId || 0) > 0) {
        return fallbackPercent;
    }

    if (card.getAttribute('data-local-progress') !== '1') {
        return fallbackPercent;
    }

    const total = Number(card.getAttribute('data-total-missions') || 0);
    if (total <= 0) {
        return 0;
    }

    const parcoursId = Number(card.getAttribute('data-parcours-id') || 0);
    const done = getAnonymousDoneMissionIds(parcoursId).length;
    return Math.max(0, Math.min(100, Math.round((done / total) * 100)));
}

document.querySelectorAll('.progress-circle').forEach(el => {
    const card = el.closest('.card');
    const percent = resolveCardPercent(card, Number(el.getAttribute('data-percent') || 0));
    const radius = 25;
    const circumference = 2 * Math.PI * radius;

    el.innerHTML = `
        <svg width="60" height="60">
            <circle class="bg" cx="30" cy="30" r="${radius}"></circle>
            <circle class="progress" cx="30" cy="30" r="${radius}"></circle>
        </svg>
        <div class="label">${percent}%</div>
    `;

    const progressCircle = el.querySelector('.progress');
    progressCircle.style.strokeDasharray = circumference;
    progressCircle.style.strokeDashoffset = circumference * (1 - percent / 100);
});

function updateParcoursSectionsByProgress() {
    const pendingGrid = document.getElementById('lms-parcours-pending-grid');
    const completedGrid = document.getElementById('lms-parcours-completed-grid');
    const completedSection = document.getElementById('lms-parcours-section-completed');
    if (!pendingGrid || !completedGrid || !completedSection) {
        return;
    }

    const parcoursCards = Array.from(document.querySelectorAll('[data-parcours-card="1"]'));
    parcoursCards.forEach((card) => {
        const progressElement = card.querySelector('.progress-circle');
        const percent = resolveCardPercent(card, progressElement ? Number(progressElement.getAttribute('data-percent') || 0) : 0);
        const targetGrid = percent >= 100 ? completedGrid : pendingGrid;

        if (card.parentElement !== targetGrid) {
            targetGrid.appendChild(card);
        }
    });

    completedSection.hidden = completedGrid.querySelector('[data-parcours-card="1"]') === null;
}

updateParcoursSectionsByProgress();

function goToParcours(id) {
    const targetUrl = new URL(lmsParcoursBasePath, window.location.origin);
    targetUrl.searchParams.set('idp', String(id));
    if (lmsIndexViewer.isEmbedded) {
        targetUrl.searchParams.set('embed', '1');
    }
    window.location.href = targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

function closeAllParcoursCardMenus() {
    document.querySelectorAll('.card-menu.is-open').forEach((menu) => {
        menu.classList.remove('is-open');
    });
}

function closeAllMissionItemMenus() {
    document.querySelectorAll('.lms-parcours-mission-item__menu.is-open').forEach((menu) => {
        menu.classList.remove('is-open');
    });
}

function toggleParcoursCardMenu(event, parcoursId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`parcours-card-menu-${parcoursId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllParcoursCardMenus();
    menu.classList.toggle('is-open', willOpen);
}

function toggleMissionItemMenu(event, missionId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`lms-mission-item-menu-${missionId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllMissionItemMenus();
    menu.classList.toggle('is-open', willOpen);
}

function buildMissionEditUrl(parcoursId, missionId) {
    const targetUrl = new URL(lmsMissionEditBasePath, window.location.origin);
    targetUrl.searchParams.set('pid', String(parcoursId));
    targetUrl.searchParams.set('mid', String(missionId));
    return targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

function initLmsDrawerContent() {
    initMissionEditorDrawer();
    initParcoursEditorDrawer();
    initParcoursImportDrawer();
}

function lmsInitAdminEditHtmlFields(scopeElement) {
    if (typeof window.adminEditInitHtmlFields === 'function') {
        try {
            return window.adminEditInitHtmlFields(scopeElement || document);
        } catch (error) {
            return Promise.resolve();
        }
    }

    return Promise.resolve();
}

function lmsSyncAdminEditHtmlFields(scopeElement) {
    if (typeof window.adminEditSyncHtmlFields === 'function') {
        try {
            window.adminEditSyncHtmlFields(scopeElement || document);
        } catch (error) {
        }
    }
}

function initParcoursEditorDrawer() {
    const drawerContent = document.getElementById('drawer-content');
    const form = drawerContent ? drawerContent.querySelector('#formulaire-edit') : null;
    const submitButton = drawerContent ? drawerContent.querySelector('#lms-create-parcours-submit') : null;

    if (!form || !submitButton || form.dataset.lmsParcoursEditorBound === '1') {
        return;
    }

    let isSubmitting = false;
    form.dataset.lmsParcoursEditorBound = '1';
    lmsInitAdminEditHtmlFields(form);

    const submitParcoursEditorForm = async (event) => {
        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        if (isSubmitting) {
            return;
        }

        const titleField = form.querySelector('[name="title"]');
        if (titleField && String(titleField.value || '').trim() === '') {
            window.alert('Le titre est obligatoire.');
            titleField.focus();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;

        try {
            lmsSyncAdminEditHtmlFields(form);
            const formData = new FormData(form);

            if (window.croppedImages && typeof window.croppedImages === 'object') {
                Object.keys(window.croppedImages).forEach((key) => {
                    const blob = window.croppedImages[key];
                    if (!blob) {
                        return;
                    }

                    let extension = 'jpg';
                    if (blob.type === 'image/png') {
                        extension = 'png';
                    } else if (blob.type === 'image/webp') {
                        extension = 'webp';
                    }

                    formData.append(key, blob, `${key}.${extension}`);
                });
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const responseText = await response.text();
            let payload = null;

            try {
                payload = JSON.parse(responseText);
            } catch (error) {
                payload = null;
            }

            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible d enregistrer ce parcours.');
            }

            if (!payload || payload.success !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible d enregistrer ce parcours.');
            }

            closeDrawer();
            window.location.reload();
        } catch (error) {
            window.alert(error && error.message ? error.message : 'Impossible d enregistrer ce parcours.');
        } finally {
            isSubmitting = false;
            submitButton.disabled = false;
        }
    };

    submitButton.addEventListener('click', submitParcoursEditorForm);
    form.addEventListener('submit', submitParcoursEditorForm, true);

    initParcoursMissionManager();
}

function initParcoursImportDrawer() {
    const drawerContent = document.getElementById('drawer-content');
    const importer = drawerContent ? drawerContent.querySelector('[data-lms-parcours-importer="1"]') : null;
    if (!importer || importer.dataset.lmsParcoursImportBound === '1') {
        return;
    }

    importer.dataset.lmsParcoursImportBound = '1';
    const searchField = importer.querySelector('[data-lms-import-parcours-search="1"]');
    const items = Array.from(importer.querySelectorAll('[data-lms-import-parcours-item="1"]'));
    const emptySearch = importer.querySelector('[data-lms-import-parcours-empty-search="1"]');

    const applySearch = () => {
        if (!searchField) {
            return;
        }

        const rawNeedle = String(searchField.value || '').trim();
        const needle = rawNeedle.toLocaleLowerCase();
        let visibleCount = 0;

        items.forEach((item) => {
            const haystack = String(item.getAttribute('data-search-text') || '').toLocaleLowerCase();
            const isVisible = needle === '' || haystack.indexOf(needle) !== -1;
            item.hidden = !isVisible;
            if (isVisible) {
                visibleCount++;
            }
        });

        if (emptySearch) {
            emptySearch.hidden = visibleCount > 0 || needle === '';
        }
    };

    if (searchField) {
        searchField.addEventListener('input', applySearch);
        applySearch();
    }

    importer.querySelectorAll('[data-lms-import-parcours-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const parcoursId = Number(button.getAttribute('data-lms-import-parcours-id') || 0);
            if (parcoursId <= 0) {
                return;
            }

            button.disabled = true;

            try {
                const formData = new FormData();
                formData.set('parcours_id', String(parcoursId));

                const response = await fetch(lmsParcoursImportSavePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const responseText = await response.text();
                let payload = null;

                try {
                    payload = JSON.parse(responseText);
                } catch (error) {
                    payload = null;
                }

                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible d importer ce parcours.');
                }

                closeDrawer();
                window.location.reload();
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible d importer ce parcours.');
                button.disabled = false;
            }
        });
    });
}

function initMissionEditorDrawer() {
    const drawerContent = document.getElementById('drawer-content');
    const editor = drawerContent ? drawerContent.querySelector('[data-lms-mission-editor]') : null;
    const form = drawerContent ? drawerContent.querySelector('#formulaire-edit') : null;
    const submitButton = drawerContent ? drawerContent.querySelector('#lms-save-mission-submit') : null;

    if (!editor || !form || !submitButton || form.dataset.lmsMissionEditorBound === '1') {
        return;
    }

    const parcoursId = Number(editor.getAttribute('data-parcours-id') || 0);
    const missionId = Number(editor.getAttribute('data-mission-id') || 0);
    let isSubmitting = false;
    form.dataset.lmsMissionEditorBound = '1';
    lmsInitAdminEditHtmlFields(form);

    const submitMissionEditorForm = async function (event) {
        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        if (isSubmitting || parcoursId <= 0 || missionId <= 0) {
            return;
        }

        const titleField = form.querySelector('[name="title"]');
        const resumeField = form.querySelector('[name="resume"]');

        if (titleField && String(titleField.value || '').trim() === '') {
            window.alert('Le titre est obligatoire.');
            titleField.focus();
            return;
        }

        if (resumeField && String(resumeField.value || '').trim() === '') {
            window.alert('Le resume est obligatoire.');
            resumeField.focus();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;

        try {
            lmsSyncAdminEditHtmlFields(form);
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const payload = await response.json();
            if (!response.ok || !payload || payload.success !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible d enregistrer cette mission.');
            }

            openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), { simpleMode: true })
                .then(() => {
                    initLmsDrawerContent();
                })
                .catch(() => {
                    window.alert('Mission enregistree, mais impossible de recharger son editeur.');
                });
        } catch (error) {
            window.alert(error && error.message ? error.message : 'Impossible d enregistrer cette mission.');
        } finally {
            isSubmitting = false;
            submitButton.disabled = false;
        }
    };

    submitButton.addEventListener('click', submitMissionEditorForm);
    form.addEventListener('submit', submitMissionEditorForm, true);

    editor.querySelectorAll('[data-lms-back-to-parcours-editor]').forEach((button) => {
        button.addEventListener('click', function () {
            openParcoursEditorDrawer(parcoursId);
        });
    });

    initMissionRelatedManagers();
}

function buildParcoursMissionPanelUrl(parcoursId) {
    const targetUrl = new URL(lmsParcoursMissionPanelBasePath, window.location.origin);
    targetUrl.searchParams.set('pid', String(parcoursId));
    return targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

async function refreshParcoursMissionManager(parcoursId) {
    const drawerContent = document.getElementById('drawer-content');
    const currentManager = drawerContent ? drawerContent.querySelector('[data-lms-parcours-mission-manager]') : null;

    if (!drawerContent || !currentManager || parcoursId <= 0) {
        return;
    }

    const response = await fetch(buildParcoursMissionPanelUrl(parcoursId), { credentials: 'same-origin' });
    if (!response.ok) {
        throw new Error('Impossible de recharger les missions du parcours.');
    }

    const html = await response.text();
    currentManager.outerHTML = html;
    initParcoursMissionManager();
}

function initParcoursMissionManager() {
    const drawerContent = document.getElementById('drawer-content');
    const manager = drawerContent ? drawerContent.querySelector('[data-lms-parcours-mission-manager]') : null;

    if (!manager || manager.dataset.lmsMissionManagerBound === '1') {
        return;
    }

    manager.dataset.lmsMissionManagerBound = '1';
    const parcoursId = Number(manager.getAttribute('data-parcours-id') || 0);
    const list = manager.querySelector('[data-lms-parcours-mission-list]');
    const picker = manager.querySelector('[data-lms-mission-picker]');
    const searchInput = manager.querySelector('[data-lms-mission-picker-search]');
    const searchEmptyState = manager.querySelector('[data-lms-mission-picker-empty-search]');
    const pickerLibrary = manager.querySelector('[data-lms-mission-picker-library]');
    const creatorView = manager.querySelector('[data-lms-mission-creator-view]');
    const creatorForm = manager.querySelector('[data-lms-mission-create-form]');
    const creatorSubmit = manager.querySelector('[data-lms-mission-create-submit]');

    function normalizeMissionPickerSearch(value) {
        let normalized = String(value || '').trim().toLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized;
    }

    function showPickerLibrary() {
        if (pickerLibrary) {
            pickerLibrary.hidden = false;
        }
        if (creatorView) {
            creatorView.hidden = true;
        }
    }

    function showMissionCreator() {
        if (pickerLibrary) {
            pickerLibrary.hidden = true;
        }
        if (creatorView) {
            creatorView.hidden = false;
        }
    }

    function closePicker() {
        if (picker) {
            picker.hidden = true;
        }
        showPickerLibrary();
    }

    function openPicker() {
        if (picker) {
            picker.hidden = false;
        }
        showPickerLibrary();
    }

    manager.querySelectorAll('[data-lms-open-mission-picker]').forEach((button) => {
        button.addEventListener('click', openPicker);
    });

    manager.querySelectorAll('[data-lms-toggle-mission-menu]').forEach((button) => {
        button.addEventListener('click', function (event) {
            const missionId = Number(button.getAttribute('data-mission-id') || 0);
            if (missionId <= 0) {
                return;
            }
            toggleMissionItemMenu(event, missionId);
        });
    });

    manager.querySelectorAll('[data-lms-edit-mission]').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const missionId = Number(button.getAttribute('data-mission-id') || 0);
            if (parcoursId <= 0 || missionId <= 0) {
                return;
            }

            closeAllMissionItemMenus();
            openMissionEditorDrawer(null, parcoursId, missionId);
        });
    });

    manager.querySelectorAll('[data-lms-close-mission-picker]').forEach((button) => {
        button.addEventListener('click', closePicker);
    });

    manager.querySelectorAll('[data-lms-open-mission-creator]').forEach((button) => {
        button.addEventListener('click', showMissionCreator);
    });

    manager.querySelectorAll('[data-lms-back-to-mission-picker]').forEach((button) => {
        button.addEventListener('click', showPickerLibrary);
    });

    if (searchInput) {
        const applyMissionPickerSearch = function () {
            const normalizedQuery = normalizeMissionPickerSearch(searchInput.value || '');
            let visibleCount = 0;

            manager.querySelectorAll('[data-lms-mission-picker-item]').forEach((item) => {
                const haystack = normalizeMissionPickerSearch(item.getAttribute('data-search-text') || '');
                const isVisible = normalizedQuery === '' || haystack.indexOf(normalizedQuery) !== -1;
                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (searchEmptyState) {
                searchEmptyState.hidden = !(normalizedQuery !== '' && visibleCount === 0);
            }
        };

        searchInput.addEventListener('input', applyMissionPickerSearch);
        searchInput.addEventListener('search', applyMissionPickerSearch);
        applyMissionPickerSearch();
    }

    manager.querySelectorAll('[data-lms-add-mission-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const missionId = Number(button.getAttribute('data-lms-add-mission-id') || 0);
            if (parcoursId <= 0 || missionId <= 0) {
                return;
            }

            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('mission_id', String(missionId));

                const response = await fetch(lmsParcoursMissionAddPath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible d ajouter cette mission.');
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible d ajouter cette mission.');
            } finally {
                button.disabled = false;
            }
        });
    });

    if (creatorForm && creatorSubmit) {
        let isCreatingMission = false;

        creatorForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (isCreatingMission || parcoursId <= 0) {
                return;
            }

            const titleField = creatorForm.querySelector('[name="title"]');
            const resumeField = creatorForm.querySelector('[name="resume"]');

            if (titleField && String(titleField.value || '').trim() === '') {
                window.alert('Le titre est obligatoire.');
                titleField.focus();
                return;
            }

            if (resumeField && String(resumeField.value || '').trim() === '') {
                window.alert('Le resume est obligatoire.');
                resumeField.focus();
                return;
            }

            isCreatingMission = true;
            creatorSubmit.disabled = true;

            try {
                const formData = new FormData(creatorForm);
                formData.set('pid', String(parcoursId));

                const response = await fetch(lmsParcoursMissionCreatePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible de creer cette mission.');
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible de creer cette mission.');
            } finally {
                isCreatingMission = false;
                creatorSubmit.disabled = false;
            }
        });
    }

    if (list && typeof window.commonCreateVerticalSortableList === 'function') {
        window.commonCreateVerticalSortableList({
            list: list,
            itemSelector: '[data-mission-id]',
            handleSelector: '[data-lms-mission-drag-handle]',
            draggingClass: 'is-dragging',
            dropTargetClass: 'is-drop-target',
            onDrop: async function () {
                const missionIds = Array.from(list.querySelectorAll('[data-mission-id]'))
                    .map((item) => Number(item.getAttribute('data-mission-id') || 0))
                    .filter((id) => Number.isInteger(id) && id > 0);

                try {
                    const formData = new FormData();
                    formData.append('pid', String(parcoursId));
                    missionIds.forEach((missionId) => {
                        formData.append('mission_ids[]', String(missionId));
                    });

                    const response = await fetch(lmsParcoursMissionReorderPath, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload || payload.success !== true) {
                        throw new Error(payload && payload.message ? payload.message : 'Impossible de reordonner les missions.');
                    }

                    await refreshParcoursMissionManager(parcoursId);
                } catch (error) {
                    window.alert(error && error.message ? error.message : 'Impossible de reordonner les missions.');
                    await refreshParcoursMissionManager(parcoursId);
                }
            }
        });
    }
}

function createMissionQuestionChoiceRow(index) {
    const row = document.createElement('div');
    row.className = 'lms-question-choice-row';
    row.setAttribute('data-lms-question-choice-row', '1');
    row.innerHTML = `
        <label class="lms-question-choice-row__label">
            <span>Choix</span>
            <input type="text" name="choices[${index}][label]" required>
        </label>
        <label class="lms-question-choice-row__correct">
            <input type="checkbox" name="choices[${index}][is_correct]" value="1">
            <span>Bonne reponse</span>
        </label>
        <button type="button" class="lms-question-choice-row__remove" data-lms-remove-question-choice="1">Supprimer</button>
    `;
    return row;
}

function setMissionFormButtonLabel(button, text) {
    if (button) {
        button.textContent = text;
    }
}

function resetMissionHomeworkForm(homeworkForm, submitButton) {
    if (!homeworkForm) {
        return;
    }

    homeworkForm.reset();
    const idField = homeworkForm.querySelector('[name="id"]');
    if (idField) {
        idField.value = '';
    }

    homeworkForm.hidden = true;
    setMissionFormButtonLabel(submitButton, 'Creer le devoir');
}

function openMissionHomeworkFormForCreate(homeworkForm, submitButton) {
    if (!homeworkForm) {
        return;
    }

    homeworkForm.reset();
    const idField = homeworkForm.querySelector('[name="id"]');
    if (idField) {
        idField.value = '';
    }

    homeworkForm.hidden = false;
    setMissionFormButtonLabel(submitButton, 'Creer le devoir');
}

function openMissionHomeworkFormForEdit(homeworkForm, submitButton, item) {
    if (!homeworkForm || !item) {
        return;
    }

    const idField = homeworkForm.querySelector('[name="id"]');
    const titleField = homeworkForm.querySelector('[name="title"]');
    const detailField = homeworkForm.querySelector('[name="detail"]');

    if (idField) {
        idField.value = String(item.getAttribute('data-homework-id') || '');
    }
    if (titleField) {
        titleField.value = String(item.getAttribute('data-homework-title') || '');
    }
    if (detailField) {
        detailField.value = String(item.getAttribute('data-homework-detail') || '');
    }

    homeworkForm.hidden = false;
    setMissionFormButtonLabel(submitButton, 'Mettre a jour le devoir');
}

function resetMissionQuestionForm(questionForm, submitButton) {
    if (!questionForm) {
        return;
    }

    questionForm.reset();
    const idField = questionForm.querySelector('[name="id"]');
    if (idField) {
        idField.value = '';
    }

    const choiceList = questionForm.querySelector('[data-lms-question-choice-list]');
    if (choiceList) {
        choiceList.innerHTML = '';
        choiceList.appendChild(createMissionQuestionChoiceRow(0));
        choiceList.appendChild(createMissionQuestionChoiceRow(1));
        choiceList.dataset.nextIndex = '2';
        bindMissionQuestionChoiceRemoval(questionForm);
    }

    questionForm.hidden = true;
    setMissionFormButtonLabel(submitButton, 'Creer la question');
}

function openMissionQuestionFormForCreate(questionForm, submitButton) {
    if (!questionForm) {
        return;
    }

    resetMissionQuestionForm(questionForm, submitButton);
    questionForm.hidden = false;
    setMissionFormButtonLabel(submitButton, 'Creer la question');
}

function openMissionQuestionFormForEdit(questionForm, submitButton, item) {
    if (!questionForm || !item) {
        return;
    }

    const idField = questionForm.querySelector('[name="id"]');
    const questionField = questionForm.querySelector('[name="question"]');
    const answerField = questionForm.querySelector('[name="answer"]');
    const detailField = questionForm.querySelector('[name="detail"]');
    const choiceList = questionForm.querySelector('[data-lms-question-choice-list]');
    let choices = [];

    try {
        const rawChoices = String(item.getAttribute('data-question-choices') || '[]');
        const parsedChoices = JSON.parse(rawChoices);
        if (Array.isArray(parsedChoices)) {
            choices = parsedChoices;
        }
    } catch (error) {
        choices = [];
    }

    if (idField) {
        idField.value = String(item.getAttribute('data-question-id') || '');
    }
    if (questionField) {
        questionField.value = String(item.getAttribute('data-question-text') || '');
    }
    if (answerField) {
        answerField.value = String(item.getAttribute('data-question-answer') || '');
    }
    if (detailField) {
        detailField.value = String(item.getAttribute('data-question-detail') || '');
    }

    if (choiceList) {
        choiceList.innerHTML = '';
        const safeChoices = choices.length >= 2 ? choices : [{ label: '', is_correct: false }, { label: '', is_correct: false }];
        safeChoices.forEach((choice, index) => {
            const row = createMissionQuestionChoiceRow(index);
            const labelField = row.querySelector('input[type="text"]');
            const correctField = row.querySelector('input[type="checkbox"]');
            if (labelField) {
                labelField.value = String(choice.label || '');
            }
            if (correctField) {
                correctField.checked = !!choice.is_correct;
            }
            choiceList.appendChild(row);
        });
        choiceList.dataset.nextIndex = String(safeChoices.length);
        bindMissionQuestionChoiceRemoval(questionForm);
    }

    questionForm.hidden = false;
    setMissionFormButtonLabel(submitButton, 'Mettre a jour la question');
}

function bindMissionQuestionChoiceRemoval(scopeElement) {
    scopeElement.querySelectorAll('[data-lms-remove-question-choice]').forEach((button) => {
        if (button.dataset.lmsChoiceRemoveBound === '1') {
            return;
        }

        button.dataset.lmsChoiceRemoveBound = '1';
        button.addEventListener('click', function () {
            const list = button.closest('[data-lms-question-choice-list]');
            const row = button.closest('[data-lms-question-choice-row]');
            if (!list || !row) {
                return;
            }

            const rows = list.querySelectorAll('[data-lms-question-choice-row]');
            if (rows.length <= 2) {
                window.alert('Gardez au moins deux choix de reponse.');
                return;
            }

            row.remove();
        });
    });
}

function reloadMissionEditorDrawer(parcoursId, missionId) {
    return openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        });
}

function bindMissionRelatedSortableList(options) {
    if (!options || typeof window.commonCreateVerticalSortableList !== 'function') {
        return;
    }

    const list = options.list;
    if (!list || list.dataset.lmsSortableBound === '1') {
        return;
    }

    list.dataset.lmsSortableBound = '1';
    window.commonCreateVerticalSortableList({
        list: list,
        itemSelector: options.itemSelector,
        handleSelector: options.handleSelector,
        draggingClass: 'is-dragging',
        dropTargetClass: 'is-drop-target',
        onDrop: async function () {
            const itemIds = Array.from(list.querySelectorAll(options.itemSelector))
                .map((item) => Number(item.getAttribute(options.idAttribute) || 0))
                .filter((id) => Number.isInteger(id) && id > 0);

            try {
                const formData = new FormData();
                formData.append('pid', String(options.parcoursId || 0));
                formData.append('mid', String(options.missionId || 0));
                itemIds.forEach((itemId) => {
                    formData.append(options.arrayFieldName, String(itemId));
                });

                const response = await fetch(options.url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : options.errorMessage);
                }

                await reloadMissionEditorDrawer(options.parcoursId, options.missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : options.errorMessage);
                await reloadMissionEditorDrawer(options.parcoursId, options.missionId);
            }
        }
    });
}

function initMissionRelatedManagers() {
    const drawerContent = document.getElementById('drawer-content');
    const editor = drawerContent ? drawerContent.querySelector('[data-lms-mission-editor]') : null;
    if (!editor || editor.dataset.lmsMissionRelatedBound === '1') {
        return;
    }

    editor.dataset.lmsMissionRelatedBound = '1';
    const parcoursId = Number(editor.getAttribute('data-parcours-id') || 0);
    const missionId = Number(editor.getAttribute('data-mission-id') || 0);

    const homeworkForm = editor.querySelector('[data-lms-homework-create-form]');
    const questionForm = editor.querySelector('[data-lms-question-create-form]');
    const homeworkSubmitButton = homeworkForm ? homeworkForm.querySelector('[data-lms-homework-create-submit]') : null;
    const questionSubmitButton = questionForm ? questionForm.querySelector('[data-lms-question-create-submit]') : null;

    editor.querySelectorAll('[data-lms-open-homework-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            openMissionHomeworkFormForCreate(homeworkForm, homeworkSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-close-homework-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            resetMissionHomeworkForm(homeworkForm, homeworkSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-open-question-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            openMissionQuestionFormForCreate(questionForm, questionSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-close-question-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            resetMissionQuestionForm(questionForm, questionSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-edit-homework]').forEach((button) => {
        button.addEventListener('click', function () {
            const item = button.closest('[data-lms-homework-item]');
            openMissionHomeworkFormForEdit(homeworkForm, homeworkSubmitButton, item);
        });
    });

    editor.querySelectorAll('[data-lms-edit-question]').forEach((button) => {
        button.addEventListener('click', function () {
            const item = button.closest('[data-lms-question-item]');
            openMissionQuestionFormForEdit(questionForm, questionSubmitButton, item);
        });
    });

    const homeworkList = editor.querySelector('[data-lms-homework-list]');
    bindMissionRelatedSortableList({
        list: homeworkList,
        itemSelector: '[data-lms-homework-item]',
        handleSelector: '[data-lms-homework-drag-handle]',
        idAttribute: 'data-homework-id',
        arrayFieldName: 'homework_ids[]',
        url: lmsMissionHomeworkReorderPath,
        errorMessage: 'Impossible de reordonner les devoirs.',
        parcoursId: parcoursId,
        missionId: missionId
    });

    const questionList = editor.querySelector('[data-lms-question-list]');
    bindMissionRelatedSortableList({
        list: questionList,
        itemSelector: '[data-lms-question-item]',
        handleSelector: '[data-lms-question-drag-handle]',
        idAttribute: 'data-question-id',
        arrayFieldName: 'question_ids[]',
        url: lmsMissionQuestionReorderPath,
        errorMessage: 'Impossible de reordonner les questions.',
        parcoursId: parcoursId,
        missionId: missionId
    });

    if (homeworkForm) {
        let isSubmittingHomework = false;
        const submitButton = homeworkSubmitButton;

        homeworkForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (isSubmittingHomework || parcoursId <= 0 || missionId <= 0) {
                return;
            }

            const titleField = homeworkForm.querySelector('[name="title"]');
            if (titleField && String(titleField.value || '').trim() === '') {
                window.alert('Le titre est obligatoire.');
                titleField.focus();
                return;
            }

            isSubmittingHomework = true;
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const formData = new FormData(homeworkForm);
                formData.set('pid', String(parcoursId));
                formData.set('mid', String(missionId));

                const response = await fetch(homeworkForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible de creer ce devoir.');
                }

                await reloadMissionEditorDrawer(parcoursId, missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible de creer ce devoir.');
            } finally {
                isSubmittingHomework = false;
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    }

    if (questionForm) {
        let isSubmittingQuestion = false;
        const submitButton = questionSubmitButton;
        const choiceList = questionForm.querySelector('[data-lms-question-choice-list]');
        const addChoiceButton = questionForm.querySelector('[data-lms-add-question-choice]');

        if (choiceList && !choiceList.dataset.nextIndex) {
            choiceList.dataset.nextIndex = String(choiceList.querySelectorAll('[data-lms-question-choice-row]').length);
        }

        bindMissionQuestionChoiceRemoval(questionForm);

        if (addChoiceButton && choiceList) {
            addChoiceButton.addEventListener('click', function () {
                const nextIndex = Number(choiceList.dataset.nextIndex || choiceList.querySelectorAll('[data-lms-question-choice-row]').length || 0);
                choiceList.appendChild(createMissionQuestionChoiceRow(nextIndex));
                choiceList.dataset.nextIndex = String(nextIndex + 1);
                bindMissionQuestionChoiceRemoval(questionForm);
            });
        }

        questionForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (isSubmittingQuestion || parcoursId <= 0 || missionId <= 0) {
                return;
            }

            const questionField = questionForm.querySelector('[name="question"]');
            const answerField = questionForm.querySelector('[name="answer"]');
            const choiceRows = Array.from(questionForm.querySelectorAll('[data-lms-question-choice-row]'));
            const filledChoiceRows = choiceRows.filter((row) => {
                const input = row.querySelector('input[type="text"]');
                return input && String(input.value || '').trim() !== '';
            });
            const correctChoiceCount = filledChoiceRows.filter((row) => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                return !!(checkbox && checkbox.checked);
            }).length;

            if (questionField && String(questionField.value || '').trim() === '') {
                window.alert('La question est obligatoire.');
                questionField.focus();
                return;
            }

            if (answerField && String(answerField.value || '').trim() === '') {
                window.alert('La reponse est obligatoire.');
                answerField.focus();
                return;
            }

            if (filledChoiceRows.length < 2) {
                window.alert('Ajoutez au moins deux choix de reponse.');
                return;
            }

            if (correctChoiceCount <= 0) {
                window.alert('Indiquez au moins une bonne reponse.');
                return;
            }

            isSubmittingQuestion = true;
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const formData = new FormData(questionForm);
                formData.set('pid', String(parcoursId));
                formData.set('mid', String(missionId));

                const response = await fetch(questionForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible de creer cette question.');
                }

                await reloadMissionEditorDrawer(parcoursId, missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible de creer cette question.');
            } finally {
                isSubmittingQuestion = false;
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    }
}

function openCreateParcoursDrawer(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!lmsIndexViewer.canCreateParcours) {
        return;
    }

    closeAllParcoursCardMenus();
    openDrawerFromUrl(lmsParcoursCreatePath, { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert('Impossible de charger le formulaire de parcours.');
        });
}

function openImportParcoursDrawer(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!lmsIndexViewer.canCreateParcours) {
        return;
    }

    closeAllParcoursCardMenus();
    openDrawerFromUrl(lmsParcoursImportPath, { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert('Impossible de charger le catalogue de parcours.');
        });
}

function openParcoursEditorDrawer(parcoursId) {
    if (!lmsIndexViewer.canCreateParcours) {
        return Promise.resolve();
    }

    closeAllParcoursCardMenus();

    const targetUrl = new URL(lmsParcoursEditBasePath, window.location.origin);
    targetUrl.searchParams.set('pid', String(parcoursId));

    return openDrawerFromUrl(targetUrl.pathname + targetUrl.search + targetUrl.hash, { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert('Impossible de charger ce parcours.');
        });
}

function openEditParcoursDrawer(event, parcoursId) {
    event.preventDefault();
    event.stopPropagation();

    const card = document.querySelector(`[data-parcours-card="1"][data-parcours-id="${parcoursId}"]`);
    if (card && String(card.getAttribute('data-can-edit') || '0') !== '1') {
        return;
    }

    openParcoursEditorDrawer(parcoursId);
}

function openMissionEditorDrawer(event, parcoursId, missionId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!lmsIndexViewer.canCreateParcours || parcoursId <= 0 || missionId <= 0) {
        return;
    }

    closeAllMissionItemMenus();
    openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert('Impossible de charger cette mission.');
        });
}

document.addEventListener('click', function () {
    closeAllParcoursCardMenus();
    closeAllMissionItemMenus();
});
</script>
<?php include 'inc/drawer.php'; ?>
</body>
</html>
