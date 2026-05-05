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
$showPublicCatalog = false;
$hiddenParcoursCount = 0;

if ($user_id <= 0 && !$isGuestAllowed) {
    $parcours = \dbObject\Parcours::fetchEverybodyForOrganizationWithProgress($org['id'], 0);
    $showPublicCatalog = count($parcours) > 0;
    $hiddenParcoursCount = \dbObject\Parcours::countRestrictedForPublicCatalog($org['id']);
} else {
    $parcours = \dbObject\Parcours::fetchForOrganizationWithProgress($org['id'], $user_id, $hasOrganizationAccess);
}

if ($user_id <= 0 && !$isGuestAllowed && !$showPublicCatalog) {
    commonRenderMagicLoginPage([
        'title' => $org['name'] . ' - LMS',
        'appName' => 'LMS',
        'intro' => 'Connectez-vous pour acceder a vos parcours de formation.',
        'returnTo' => '/lms/' . ($isEmbedded ? '?embed=1' : ''),
    ]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($org['name']); ?></title>
    <link rel="stylesheet" href="/lms/css/std.css">
    
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

        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            flex-direction: column;
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            background: white;
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
            stroke: #ddd;
        }

        .progress {
            stroke: #4caf50;
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
            background: #fff8e5;
            border: 1px solid #f0d995;
            border-radius: 10px;
            color: #5f4a11;
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
    </style>
</head>
<body class="<?php echo $isEmbedded ? 'lms-embed-mode' : ''; ?>">
<?php
$showLoginDrawerButton = $user_id <= 0 && !$isGuestAllowed && $showPublicCatalog;
$loginDrawerReturnTo = '/lms/' . ($isEmbedded ? '?embed=1' : '');
if (!$isEmbedded) {
    include 'inc/menu.php';
}
?>
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
        Connectez-vous pour acceder aux <?php echo (int)$hiddenParcoursCount; ?> autre<?php echo $hiddenParcoursCount > 1 ? 's' : ''; ?> parcours.
    <?php else: ?>
        Connectez-vous pour ouvrir ces parcours et enregistrer votre avancement sur votre profil.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="container">
<?php foreach ($parcours as $p):
    $total = (int)$p['total_missions'];
    $done = (int)$p['done_missions'];
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;
?>
<div
    class="card"
    data-parcours-id="<?php echo (int)$p['id']; ?>"
    data-total-missions="<?php echo $total; ?>"
    data-local-progress="<?php echo ($user_id <= 0 && ($isGuestAllowed || !empty($p['anonymous']))) ? '1' : '0'; ?>"
    onclick="goToParcours(<?php echo (int)$p['id']; ?>)"
>
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
</div>

<script>
const lmsIndexViewer = {
    userId: <?php echo (int)$user_id; ?>,
    organizationId: <?php echo (int)$org['id']; ?>,
    isGuestAllowed: <?php echo $isGuestAllowed ? 'true' : 'false'; ?>,
    isEmbedded: <?php echo $isEmbedded ? 'true' : 'false'; ?>
};

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

function goToParcours(id) {
    const params = new URLSearchParams({ idp: String(id) });
    if (lmsIndexViewer.isEmbedded) {
        params.set('embed', '1');
    }
    window.location.href = "/lms/parcours.php?" + params.toString();
}
</script>
<?php include 'inc/drawer.php'; ?>
</body>
</html>
