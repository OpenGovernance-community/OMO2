<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include 'inc/org.php';
require_once __DIR__ . '/inc/access.php';

$isEmbedded = !empty($_GET['embed']);
$user_id = (int)($_SESSION['currentUser'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($user_id, (int)$org['id']);
$isGuestAllowed = commonCanAccessWithoutLogin($org);
$organizationColor = commonGetOrganizationExplicitColor($org);
$showPublicCatalog = false;
$hiddenParcoursCount = 0;

if ($hasOrganizationAccess) {
    $parcours = \dbObject\Parcours::fetchForOrganizationWithProgress($org['id'], $user_id, true);
    $hiddenParcoursCount = 0;
} else {
    $parcours = \dbObject\Parcours::fetchPublicForOrganizationWithProgress($org['id'], $user_id);
    $showPublicCatalog = true;
    $hiddenParcoursCount = \dbObject\Parcours::countRestrictedForPublicCatalog($org['id']);
}

$parcours = is_array($parcours) ? $parcours : [];

if ($user_id <= 0 && !$isGuestAllowed && count($parcours) === 0) {
    $loginReturnTo = lmsBuildLocalPath('/lms/', $isEmbedded ? ['embed' => 1] : []);
    commonRenderMagicLoginPage([
        'title' => $org['name'] . ' - LMS',
        'appName' => 'LMS',
        'intro' => 'Connectez-vous pour acceder aux parcours de cette organisation.',
        'returnTo' => $loginReturnTo,
        'topbar' => [
            'appKey' => 'lms',
            'appLabel' => 'LMS',
            'organization' => $org,
            'brandLabel' => (string)($org['name'] ?? 'LMS'),
            'profile' => [
                'enabled' => false,
            ],
            'search' => [
                'enabled' => false,
            ],
            'helpLabel' => 'Aide',
        ],
    ]);
    exit;
}

$pendingParcours = [];
$completedParcours = [];
foreach ($parcours as $parcoursItem) {
    if (!empty($parcoursItem['ispack'])) {
        continue;
    }

    $totalMissions = (int)($parcoursItem['total_missions'] ?? 0);
    $doneMissions = (int)($parcoursItem['done_missions'] ?? 0);
    $percent = $totalMissions > 0 ? (int)round(($doneMissions / $totalMissions) * 100) : 0;

    if ($totalMissions > 0 && $percent >= 100) {
        $completedParcours[] = $parcoursItem;
        continue;
    }

    $pendingParcours[] = $parcoursItem;
}

$showLoginDrawerButton = $user_id <= 0;
$loginDrawerReturnTo = lmsBuildLocalPath('/lms/', $isEmbedded ? ['embed' => 1] : []);
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($org['name']); ?></title>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/lms/css/std.css">
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
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(15,23,42,0.12);
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

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
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
    </style>
</head>
<body class="<?php echo $isEmbedded ? 'lms-embed-mode' : ''; ?>">
<?php if (!$isEmbedded): ?>
<?php include 'inc/menu.php'; ?>
<?php endif; ?>

<div class="content<?php echo $isEmbedded ? ' lms-index-content--embed' : ''; ?>">
<?php if ($isEmbedded): ?>
<div class="lms-index-embed-header">
    <h1><?php echo htmlspecialchars($org['name']); ?></h1>
    <p>Parcours de formation</p>
</div>
<?php endif; ?>

<?php if (!$isEmbedded): ?>
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

<h1>Parcours de formation</h1>

<?php if ($showPublicCatalog): ?>
<div class="lms-access-note">
    <strong>Une partie du LMS est accessible publiquement.</strong>
    <?php if ($hiddenParcoursCount > 0): ?>
        Connectez-vous pour acceder aux <?php echo (int)$hiddenParcoursCount; ?> autre<?php echo $hiddenParcoursCount > 1 ? 's' : ''; ?> parcours reserves aux membres de l organisation.
    <?php else: ?>
        Connectez-vous pour enregistrer votre avancement et retrouver vos parcours sur votre profil.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="lms-parcours-sections">
    <section class="lms-parcours-section" id="lms-parcours-section-pending">
        <div class="container" id="lms-parcours-pending-grid">
            <?php foreach ($pendingParcours as $p): ?>
            <?php $total = (int)($p['total_missions'] ?? 0); $done = (int)($p['done_missions'] ?? 0); $percent = $total > 0 ? round(($done / $total) * 100) : 0; ?>
            <div
                class="card"
                data-parcours-card="1"
                data-is-pack="0"
                data-parcours-id="<?php echo (int)$p['id']; ?>"
                data-total-missions="<?php echo $total; ?>"
                data-local-progress="<?php echo ($user_id <= 0 && !empty($p['anonymous'])) ? '1' : '0'; ?>"
                onclick="goToParcours(<?php echo (int)$p['id']; ?>)"
            >
                <?php if (!empty($p['image'])): ?>
                <div class="card-image">
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                </div>
                <?php endif; ?>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars((string)$p['title']); ?></h3>
                    <div><?php echo htmlspecialchars((string)$p['description']); ?></div>
                    <div class="card-footer">
                        <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
                        <button class="open-btn">Ouvrir</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
            <?php foreach ($completedParcours as $p): ?>
            <?php $total = (int)($p['total_missions'] ?? 0); $done = (int)($p['done_missions'] ?? 0); $percent = $total > 0 ? round(($done / $total) * 100) : 0; ?>
            <div
                class="card"
                data-parcours-card="1"
                data-is-pack="0"
                data-parcours-id="<?php echo (int)$p['id']; ?>"
                data-total-missions="<?php echo $total; ?>"
                data-local-progress="<?php echo ($user_id <= 0 && !empty($p['anonymous'])) ? '1' : '0'; ?>"
                onclick="goToParcours(<?php echo (int)$p['id']; ?>)"
            >
                <?php if (!empty($p['image'])): ?>
                <div class="card-image">
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                </div>
                <?php endif; ?>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars((string)$p['title']); ?></h3>
                    <div><?php echo htmlspecialchars((string)$p['description']); ?></div>
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
    isEmbedded: <?php echo $isEmbedded ? 'true' : 'false'; ?>
};
const lmsParcoursBasePath = <?php echo json_encode(lmsBuildLocalPath('/lms/parcours.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

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
            .map((value) => Number(value))
            .filter((value) => Number.isInteger(value) && value > 0);
    } catch (error) {
        return [];
    }
}

function renderProgressCircle(el, percent) {
    const radius = 26;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percent / 100) * circumference;

    el.innerHTML = `
        <svg width="60" height="60">
            <circle class="bg" cx="30" cy="30" r="${radius}" />
            <circle class="progress" cx="30" cy="30" r="${radius}" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}" />
        </svg>
        <div class="label">${percent}%</div>
    `;
}

function resolveCardPercent(card, fallbackPercent) {
    const usesLocalProgress = card.getAttribute('data-local-progress') === '1';
    if (!usesLocalProgress) {
        return fallbackPercent;
    }

    const parcoursId = Number(card.getAttribute('data-parcours-id') || 0);
    const total = Number(card.getAttribute('data-total-missions') || 0);
    if (parcoursId <= 0 || total <= 0) {
        return fallbackPercent;
    }

    const done = getAnonymousDoneMissionIds(parcoursId).length;
    return Math.max(0, Math.min(100, Math.round((done / total) * 100)));
}

document.querySelectorAll('.progress-circle').forEach((el) => {
    const card = el.closest('[data-parcours-card="1"]');
    const percent = card ? resolveCardPercent(card, Number(el.getAttribute('data-percent') || 0)) : Number(el.getAttribute('data-percent') || 0);
    renderProgressCircle(el, percent);
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
        if (card.getAttribute('data-is-pack') === '1') {
            return;
        }

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
</script>
</body>
</html>
