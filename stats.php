<?php

require_once __DIR__ . '/shared_functions.php';
require_once __DIR__ . '/common/translation_bundles.php';
require_once __DIR__ . '/common/topbar.php';
require_once __DIR__ . '/omo/topbar.php';

function siteStatsSourceLang(): array
{
    return [
        'stats.page.title' => ['text' => 'Statistiques du site', 'context' => 'Page title for the global site statistics dashboard.'],
        'stats.page.eyebrow' => ['text' => 'Vue globale', 'context' => 'Small eyebrow above the global site statistics title.'],
        'stats.page.intro' => ['text' => 'Un premier aperçu de la croissance et de l activité de la plateforme.', 'context' => 'Introductory copy for the global site statistics dashboard.'],
        'stats.period.label' => ['text' => 'Période des graphiques', 'context' => 'Label above the global chart period selector.'],
        'stats.period.days_30' => ['text' => '30 jours', 'context' => 'Thirty-day chart period option.'],
        'stats.period.days_90' => ['text' => '90 jours', 'context' => 'Ninety-day chart period option.'],
        'stats.period.days_365' => ['text' => '1 an', 'context' => 'One-year chart period option.'],
        'stats.period.daily' => ['text' => 'par jour', 'context' => 'Chart aggregation label when bars represent days.'],
        'stats.period.weekly' => ['text' => 'par semaine', 'context' => 'Chart aggregation label when bars represent weeks.'],
        'stats.organizations.title' => ['text' => 'Organisations', 'context' => 'Title of the organizations statistics block.'],
        'stats.organizations.total' => ['text' => 'Total d organisations', 'context' => 'Metric label for the total number of organizations.'],
        'stats.organizations.new_month' => ['text' => 'Nouvelles ce mois', 'context' => 'Metric label for organizations created during the current month.'],
        'stats.organizations.chart' => ['text' => 'Nouvelles organisations par date', 'context' => 'Chart title for organization creation counts.'],
        'stats.users.title' => ['text' => 'Utilisateurs', 'context' => 'Title of the users statistics block.'],
        'stats.users.total' => ['text' => 'Total d utilisateurs', 'context' => 'Metric label for the total number of users.'],
        'stats.users.active_30' => ['text' => 'Connectés ces 30 derniers jours', 'context' => 'Metric label for users with a recent connection.'],
        'stats.users.chart' => ['text' => 'Connexions par date', 'context' => 'Chart title for user connection activity.'],
        'stats.users.chart_note' => ['text' => 'Le graphique repose sur la dernière connexion connue de chaque utilisateur. L historique détaillé de chaque connexion sera ajouté plus tard.', 'context' => 'Note explaining the current limitation of the user activity chart.'],
        'stats.holons.title' => ['text' => 'Holons', 'context' => 'Title of the holons statistics block.'],
        'stats.holons.total' => ['text' => 'Total de holons', 'context' => 'Metric label for the total number of active holons.'],
        'stats.holons.modified_30' => ['text' => 'Modifiés ces 30 derniers jours', 'context' => 'Metric label for holons modified during the last thirty days.'],
        'stats.holons.chart' => ['text' => 'Créations et modifications de holons', 'context' => 'Chart title for holon creations and modifications.'],
        'stats.holons.created' => ['text' => 'Créations', 'context' => 'Legend label for holon creation bars.'],
        'stats.holons.modified' => ['text' => 'Modifications', 'context' => 'Legend label for distinct modified holon bars.'],
        'stats.holons.chart_note' => ['text' => 'Les modifications sont lues dans les événements d historique holon_updated et comptées une fois par holon et par période.', 'context' => 'Note explaining the source and aggregation of the holon modification series.'],
        'stats.chart.empty' => ['text' => 'Aucune donnée sur cette période.', 'context' => 'Empty state for a statistics bar chart.'],
        'stats.chart.value' => ['one' => '{count} élément', 'other' => '{count} éléments', 'context' => 'Accessible chart bar value label.'],
        'stats.access.title' => ['text' => 'Accès réservé', 'context' => 'Title shown when global site statistics are not available.'],
        'stats.access.message' => ['text' => 'Activez le mode super admin depuis le menu de profil pour consulter ces statistiques.', 'context' => 'Message shown when site admin mode is not active.'],
        'stats.access.back' => ['text' => 'Retour à OMO', 'context' => 'Back link shown on the statistics access page.'],
    ];
}

function siteStatsLoadBundle(): array
{
    static $bundle = null;
    if ($bundle === null) {
        $bundle = loadTranslationBundle('site_stats', translationBundleResolveRequestLocale('lang'), siteStatsSourceLang());
    }

    return $bundle;
}

function siteStatsT(string $key, array $variables = []): string
{
    return t($key, $variables, siteStatsLoadBundle(), siteStatsSourceLang());
}

function siteStatsEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function siteStatsBuildSeries(array $rows, DateTimeImmutable $startDate, DateTimeImmutable $endDate, string $mode): array
{
    $series = [];
    $cursor = $mode === 'weekly'
        ? $startDate->modify('monday this week')
        : $startDate;
    $lastBucket = $mode === 'weekly'
        ? $endDate->modify('monday this week')
        : $endDate;

    while ($cursor <= $lastBucket) {
        $key = $cursor->format('Y-m-d');
        $series[$key] = [
            'date' => $cursor,
            'value' => 0,
        ];
        $cursor = $cursor->modify($mode === 'weekly' ? '+7 days' : '+1 day');
    }

    foreach ($rows as $row) {
        $rawDate = trim((string)($row['period_date'] ?? ''));
        if ($rawDate === '') {
            continue;
        }

        try {
            $rowDate = new DateTimeImmutable($rawDate . ' 00:00:00');
        } catch (Exception $exception) {
            continue;
        }

        if ($mode === 'weekly') {
            $rowDate = $rowDate->modify('monday this week');
        }

        $key = $rowDate->format('Y-m-d');
        if (isset($series[$key])) {
            $series[$key]['value'] += (int)($row['total'] ?? 0);
        }
    }

    return array_values($series);
}

function siteStatsRenderBarChart(string $chartId, array $seriesList, string $ariaLabel, string $mode): string
{
    $width = 1000;
    $height = 300;
    $paddingLeft = 44;
    $paddingRight = 18;
    $paddingTop = 20;
    $paddingBottom = 48;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;
    $maxValue = 0;
    $seriesCount = count($seriesList);
    foreach ($seriesList as $seriesItem) {
        foreach (($seriesItem['series'] ?? []) as $point) {
            $maxValue = max($maxValue, (int)$point['value']);
        }
    }

    if ($maxValue <= 0) {
        return '<div class="site-stats-chart-empty">' . siteStatsEscape(siteStatsT('stats.chart.empty')) . '</div>';
    }

    $series = $seriesList[0]['series'] ?? [];
    $count = count($series);
    $slotWidth = $plotWidth / max(1, $count);
    $barGroupWidth = $slotWidth * 0.72;
    $barWidth = max(2, ($barGroupWidth - (($seriesCount - 1) * 3)) / max(1, $seriesCount));
    $labelStep = max(1, (int)ceil($count / 12));
    $svg = '<svg class="site-stats-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-labelledby="' . siteStatsEscape($chartId) . '-title ' . siteStatsEscape($chartId) . '-description">';
    $svg .= '<title id="' . siteStatsEscape($chartId) . '-title">' . siteStatsEscape($ariaLabel) . '</title>';
    $svg .= '<desc id="' . siteStatsEscape($chartId) . '-description">' . siteStatsEscape(siteStatsT('stats.period.' . ($mode === 'weekly' ? 'weekly' : 'daily'))) . '</desc>';
    $svg .= '<line class="site-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . $paddingTop . '" x2="' . ($width - $paddingRight) . '" y2="' . $paddingTop . '" />';
    $svg .= '<line class="site-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . ($paddingTop + $plotHeight / 2) . '" x2="' . ($width - $paddingRight) . '" y2="' . ($paddingTop + $plotHeight / 2) . '" />';
    $svg .= '<line class="site-stats-chart__axis" x1="' . $paddingLeft . '" y1="' . ($paddingTop + $plotHeight) . '" x2="' . ($width - $paddingRight) . '" y2="' . ($paddingTop + $plotHeight) . '" />';
    $svg .= '<text class="site-stats-chart__scale" x="' . ($paddingLeft - 8) . '" y="' . ($paddingTop + 4) . '" text-anchor="end">' . siteStatsEscape($maxValue) . '</text>';

    foreach ($series as $index => $point) {
        $dateLabel = $mode === 'weekly'
            ? 'Semaine du ' . $point['date']->format('d.m.Y')
            : $point['date']->format('d.m.Y');
        foreach ($seriesList as $seriesIndex => $seriesItem) {
            $secondaryPoint = ($seriesItem['series'] ?? [])[$index] ?? ['value' => 0];
            $value = (int)($secondaryPoint['value'] ?? 0);
            $barHeight = $value > 0 ? max(2, ($value / $maxValue) * $plotHeight) : 1;
            $x = $paddingLeft + ($index * $slotWidth) + (($slotWidth - $barGroupWidth) / 2) + ($seriesIndex * ($barWidth + 3));
            $y = $paddingTop + $plotHeight - $barHeight;
            $seriesLabel = trim((string)($seriesItem['label'] ?? ''));
            $barLabel = ($seriesLabel !== '' ? $seriesLabel . ' - ' : '') . $dateLabel . ' : ' . siteStatsT('stats.chart.value', ['count' => $value]);
            $barClass = trim((string)($seriesItem['class'] ?? ''));
            $svg .= '<rect class="site-stats-chart__bar ' . siteStatsEscape($barClass) . ($value === 0 ? ' site-stats-chart__bar--empty' : '') . '" x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($barHeight, 2) . '" tabindex="0"><title>' . siteStatsEscape($barLabel) . '</title></rect>';
        }
        if ($index % $labelStep === 0 || $index === $count - 1) {
            $labelX = $paddingLeft + ($index * $slotWidth) + ($slotWidth / 2);
            $svg .= '<text class="site-stats-chart__label" x="' . round($labelX, 2) . '" y="' . ($height - 15) . '" text-anchor="middle">' . siteStatsEscape($point['date']->format('d.m')) . '</text>';
        }
    }

    $svg .= '</svg>';
    if ($seriesCount <= 1) {
        return $svg;
    }

    $legend = '<div class="site-stats-chart__legend">';
    foreach ($seriesList as $seriesItem) {
        $legend .= '<span><i class="site-stats-chart__legend-swatch ' . siteStatsEscape((string)($seriesItem['class'] ?? '')) . '"></i>' . siteStatsEscape((string)($seriesItem['label'] ?? '')) . '</span>';
    }
    $legend .= '</div>';

    return '<div class="site-stats-chart-shell">' . $svg . $legend . '</div>';
}

function siteStatsRenderChart(string $chartId, array $series, string $ariaLabel, string $mode): string
{
    return siteStatsRenderBarChart($chartId, [
        [
            'label' => '',
            'series' => $series,
            'class' => 'site-stats-chart__bar--primary',
        ],
    ], $ariaLabel, $mode);
}

function siteStatsRenderDualChart(string $chartId, array $primarySeries, array $secondarySeries, string $ariaLabel, string $mode, string $primaryLabel, string $secondaryLabel): string
{
    return siteStatsRenderBarChart($chartId, [
        [
            'label' => $primaryLabel,
            'series' => $primarySeries,
            'class' => 'site-stats-chart__bar--primary',
        ],
        [
            'label' => $secondaryLabel,
            'series' => $secondarySeries,
            'class' => 'site-stats-chart__bar--secondary',
        ],
    ], $ariaLabel, $mode);
}

$currentUserId = (int)commonGetCurrentUserId();
$siteAdminModeActive = $currentUserId > 0 && commonCurrentUserIsSiteAdminModeEnabled();
$organizationContext = commonResolveOrganizationContext(1);
if (!is_array($organizationContext) || empty($organizationContext['isValid'])) {
    $organizationContext = [
        'id' => 1,
        'name' => 'OpenMyOrganization',
        'host' => commonGetRequestHost(),
        'logo' => '/img/logo-OGC.png',
        'isValid' => true,
    ];
}
$chartRange = (int)($_GET['range'] ?? 365);
if (!in_array($chartRange, [30, 90, 365], true)) {
    $chartRange = 365;
}

$chartEndDate = new DateTimeImmutable('today');
$chartStartDate = $chartEndDate->modify('-' . ($chartRange - 1) . ' days');
$chartEndExclusive = $chartEndDate->modify('+1 day');
$chartMode = $chartRange > 90 ? 'weekly' : 'daily';
$chartStartSql = $chartStartDate->format('Y-m-d H:i:s');
$chartEndSql = $chartEndExclusive->format('Y-m-d H:i:s');
$monthStartSql = $chartEndDate->modify('first day of this month')->format('Y-m-d 00:00:00');

$totalOrganizations = (int)\dbObject\DbObject::fetchValue('SELECT COUNT(*) FROM organization');
$newOrganizationsThisMonth = (int)\dbObject\DbObject::fetchValue(
    'SELECT COUNT(*) FROM organization WHERE datecreation >= :month_start AND datecreation < :period_end',
    ['month_start' => $monthStartSql, 'period_end' => $chartEndSql]
);
$totalUsers = (int)\dbObject\DbObject::fetchValue('SELECT COUNT(*) FROM `user`');
$connectedUsers30 = (int)\dbObject\DbObject::fetchValue(
    'SELECT COUNT(*) FROM `user` WHERE dateconnexion >= :recent_start AND dateconnexion < :period_end',
    ['recent_start' => $chartEndDate->modify('-29 days')->format('Y-m-d 00:00:00'), 'period_end' => $chartEndSql]
);
$totalHolons = (int)\dbObject\DbObject::fetchValue('SELECT COUNT(*) FROM holon WHERE active = 1');
$modifiedHolons30 = (int)\dbObject\DbObject::fetchValue(
    "SELECT COUNT(DISTINCT CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED))
     FROM history h
     WHERE h.active = 1
       AND h.action = 'holon_updated'
       AND h.datecreation >= :recent_start
       AND h.datecreation < :period_end
       AND JSON_VALID(h.parameters) = 1
       AND CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED) > 0",
    ['recent_start' => $chartEndDate->modify('-29 days')->format('Y-m-d 00:00:00'), 'period_end' => $chartEndSql]
);
$organizationRows = \dbObject\DbObject::fetchAll(
    'SELECT DATE(datecreation) AS period_date, COUNT(*) AS total
     FROM organization
     WHERE datecreation >= :period_start AND datecreation < :period_end
     GROUP BY DATE(datecreation)
     ORDER BY period_date ASC',
    ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
);
$userRows = \dbObject\DbObject::fetchAll(
    'SELECT DATE(dateconnexion) AS period_date, COUNT(*) AS total
     FROM `user`
     WHERE dateconnexion IS NOT NULL
       AND dateconnexion >= :period_start
       AND dateconnexion < :period_end
     GROUP BY DATE(dateconnexion)
     ORDER BY period_date ASC',
    ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
);
$holonCreationRows = \dbObject\DbObject::fetchAll(
    'SELECT DATE(datecreation) AS period_date, COUNT(*) AS total
     FROM holon
     WHERE datecreation >= :period_start AND datecreation < :period_end
     GROUP BY DATE(datecreation)
     ORDER BY period_date ASC',
    ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
);
$holonModificationRows = \dbObject\DbObject::fetchAll(
    "SELECT DATE(h.datecreation) AS period_date,
            COUNT(DISTINCT CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED)) AS total
     FROM history h
     WHERE h.active = 1
       AND h.action = 'holon_updated'
       AND h.datecreation >= :period_start
       AND h.datecreation < :period_end
       AND JSON_VALID(h.parameters) = 1
       AND CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED) > 0
     GROUP BY DATE(h.datecreation)
     ORDER BY period_date ASC",
    ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
);
$organizationSeries = siteStatsBuildSeries(is_array($organizationRows) ? $organizationRows : [], $chartStartDate, $chartEndDate, $chartMode);
$userSeries = siteStatsBuildSeries(is_array($userRows) ? $userRows : [], $chartStartDate, $chartEndDate, $chartMode);
$holonCreationSeries = siteStatsBuildSeries(is_array($holonCreationRows) ? $holonCreationRows : [], $chartStartDate, $chartEndDate, $chartMode);
$holonModificationSeries = siteStatsBuildSeries(is_array($holonModificationRows) ? $holonModificationRows : [], $chartStartDate, $chartEndDate, $chartMode);
$pageTitle = siteStatsT('stats.page.title');
?>
<!DOCTYPE html>
<html lang="fr" data-omo-organization-accent>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= siteStatsEscape($pageTitle) ?></title>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/common/assets/components.css">
    <style>
        .site-stats-body {
            min-height: 100vh;
            margin: 0;
            background: var(--color-background, #f4f7f8);
            color: var(--color-text, #1f2937);
        }
        .site-stats-page {
            display: grid;
            gap: 20px;
            max-width: 1440px;
            margin: 0 auto;
            padding: 28px clamp(16px, 4vw, 48px) 48px;
        }
        .site-stats-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #fff)), var(--color-surface, #fff));
        }
        .site-stats-hero h1 { margin: 6px 0 8px; }
        .site-stats-hero p { max-width: 620px; margin: 0; color: var(--color-text-light, #64748b); }
        .site-stats-period { display: grid; gap: 8px; justify-items: end; }
        .site-stats-period__options { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; }
        .site-stats-period__options .generic-action-button { min-height: 34px; padding: 7px 10px; font-size: 0.82rem; }
        .site-stats-period__options .generic-action-button.is-active { border-color: var(--color-primary, #2563eb); background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #fff)); color: var(--color-primary, #2563eb); }
        .site-stats-period__mode { color: var(--color-text-light, #64748b); font-size: 0.8rem; }
        .site-stats-block { display: grid; gap: 16px; }
        .site-stats-block__heading h2 { margin: 0; }
        .site-stats-row { display: grid; grid-template-columns: minmax(190px, 0.28fr) minmax(0, 1fr); gap: 16px; align-items: stretch; }
        .site-stats-metric { display: grid; align-content: center; gap: 12px; min-height: 220px; box-sizing: border-box; aspect-ratio: 1 / 1; background: linear-gradient(160deg, color-mix(in srgb, var(--color-primary, #2563eb) 11%, var(--color-surface-alt, #f8fafc)), var(--color-surface-alt, #f8fafc)); }
        .site-stats-metric strong { color: var(--color-primary, #2563eb); font-size: clamp(2.2rem, 5vw, 4.3rem); line-height: 0.95; }
        .site-stats-metric > span:last-child { color: var(--color-text-light, #64748b); font-size: 0.88rem; }
        .site-stats-metric b { color: var(--color-text, #1f2937); }
        .site-stats-chart-panel { display: grid; gap: 8px; min-width: 0; margin: 0; padding: 14px; }
        .site-stats-chart-panel figcaption { color: var(--color-text, #1f2937); font-size: 1rem; font-weight: 700; }
        .site-stats-chart-shell { display: grid; gap: 4px; min-width: 0; }
        .site-stats-chart { display: block; width: 100%; min-height: 220px; overflow: visible; }
        .site-stats-chart__grid { stroke: color-mix(in srgb, var(--color-border, #dbe3e8) 65%, transparent); stroke-dasharray: 4 6; stroke-width: 1; }
        .site-stats-chart__axis { stroke: var(--color-border, #dbe3e8); stroke-width: 1; }
        .site-stats-chart__scale, .site-stats-chart__label { fill: var(--color-text-light, #64748b); font-family: inherit; font-size: 12px; }
        .site-stats-chart__bar { fill: var(--color-primary, #2563eb); opacity: 0.78; outline: none; transition: opacity 140ms ease, fill 140ms ease; }
        .site-stats-chart__bar--primary { fill: var(--color-primary, #2563eb); }
        .site-stats-chart__bar--secondary { fill: #d97706; }
        .site-stats-chart__bar:hover, .site-stats-chart__bar:focus-visible { fill: var(--color-primary-dark, #1d4ed8); opacity: 1; }
        .site-stats-chart__bar--secondary:hover, .site-stats-chart__bar--secondary:focus-visible { fill: #b45309; }
        .site-stats-chart__bar--empty { opacity: 0.12; }
        .site-stats-chart__legend { display: flex; flex-wrap: wrap; gap: 12px 18px; color: var(--color-text-light, #64748b); font-size: 0.78rem; }
        .site-stats-chart__legend span { display: inline-flex; align-items: center; gap: 6px; }
        .site-stats-chart__legend-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: var(--color-primary, #2563eb); }
        .site-stats-chart__legend-swatch.site-stats-chart__bar--secondary { background: #d97706; }
        .site-stats-chart-empty { display: grid; min-height: 220px; place-items: center; color: var(--color-text-light, #64748b); font-size: 0.9rem; }
        .site-stats-chart-panel__note { margin: 0; color: var(--color-text-light, #64748b); font-size: 0.78rem; line-height: 1.45; }
        .site-stats-access { display: grid; gap: 14px; max-width: 520px; margin: 14vh auto; }
        .site-stats-access h1, .site-stats-access p { margin: 0; }
        @media (max-width: 760px) {
            .site-stats-hero { align-items: stretch; flex-direction: column; }
            .site-stats-period { justify-items: start; }
            .site-stats-period__options { justify-content: flex-start; }
            .site-stats-row { grid-template-columns: 1fr; }
            .site-stats-metric { min-height: 0; aspect-ratio: auto; }
            .site-stats-chart { min-height: 190px; }
        }
    </style>
</head>
<body class="site-stats-body">
<?php if ($siteAdminModeActive): ?>
    <?php commonRenderTopbar(omoBuildTopbarOptions($organizationContext, [
        'variant' => 'hub',
        'isDemoGuest' => false,
        'logoutReturnTo' => '/stats.php',
    ])); ?>
    <main class="site-stats-page">
        <header class="site-stats-hero generic-hero-panel">
            <div>
                <span class="generic-card-title generic-card-title--eyebrow"><?= siteStatsEscape(siteStatsT('stats.page.eyebrow')) ?></span>
                <h1 class="generic-card-title generic-card-title--large"><?= siteStatsEscape($pageTitle) ?></h1>
                <p><?= siteStatsEscape(siteStatsT('stats.page.intro')) ?></p>
            </div>
            <div class="site-stats-period">
                <span class="generic-card-title generic-card-title--small"><?= siteStatsEscape(siteStatsT('stats.period.label')) ?></span>
                <div class="site-stats-period__options" role="group" aria-label="<?= siteStatsEscape(siteStatsT('stats.period.label')) ?>">
                    <?php foreach ([30, 90, 365] as $period): ?>
                        <a class="generic-action-button generic-action-button--secondary<?= $period === $chartRange ? ' is-active' : '' ?>" href="/stats.php?range=<?= $period ?>"><?= siteStatsEscape(siteStatsT('stats.period.days_' . $period)) ?></a>
                    <?php endforeach; ?>
                </div>
                <span class="site-stats-period__mode"><?= siteStatsEscape(siteStatsT('stats.period.' . ($chartMode === 'weekly' ? 'weekly' : 'daily'))) ?></span>
            </div>
        </header>

        <section class="site-stats-block generic-section" aria-labelledby="site-stats-organizations-title">
            <div class="site-stats-block__heading">
                <h2 id="site-stats-organizations-title" class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('stats.organizations.title')) ?></h2>
            </div>
            <div class="site-stats-row">
                <div class="site-stats-metric generic-soft-panel-square">
                    <span class="generic-card-title generic-card-title--small"><?= siteStatsEscape(siteStatsT('stats.organizations.total')) ?></span>
                    <strong><?= number_format($totalOrganizations, 0, ',', ' ') ?></strong>
                    <span><?= siteStatsEscape(siteStatsT('stats.organizations.new_month')) ?> : <b><?= number_format($newOrganizationsThisMonth, 0, ',', ' ') ?></b></span>
                </div>
                <figure class="site-stats-chart-panel generic-soft-panel">
                    <figcaption><?= siteStatsEscape(siteStatsT('stats.organizations.chart')) ?></figcaption>
                    <?= siteStatsRenderChart('site-stats-organizations-chart', $organizationSeries, siteStatsT('stats.organizations.chart'), $chartMode) ?>
                </figure>
            </div>
        </section>

        <section class="site-stats-block generic-section" aria-labelledby="site-stats-users-title">
            <div class="site-stats-block__heading">
                <h2 id="site-stats-users-title" class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('stats.users.title')) ?></h2>
            </div>
            <div class="site-stats-row">
                <div class="site-stats-metric generic-soft-panel-square">
                    <span class="generic-card-title generic-card-title--small"><?= siteStatsEscape(siteStatsT('stats.users.total')) ?></span>
                    <strong><?= number_format($totalUsers, 0, ',', ' ') ?></strong>
                    <span><?= siteStatsEscape(siteStatsT('stats.users.active_30')) ?> : <b><?= number_format($connectedUsers30, 0, ',', ' ') ?></b></span>
                </div>
                <figure class="site-stats-chart-panel generic-soft-panel">
                    <figcaption><?= siteStatsEscape(siteStatsT('stats.users.chart')) ?></figcaption>
                    <?= siteStatsRenderChart('site-stats-users-chart', $userSeries, siteStatsT('stats.users.chart'), $chartMode) ?>
                    <p class="site-stats-chart-panel__note"><?= siteStatsEscape(siteStatsT('stats.users.chart_note')) ?></p>
                </figure>
            </div>
        </section>

        <section class="site-stats-block generic-section" aria-labelledby="site-stats-holons-title">
            <div class="site-stats-block__heading">
                <h2 id="site-stats-holons-title" class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('stats.holons.title')) ?></h2>
            </div>
            <div class="site-stats-row">
                <div class="site-stats-metric generic-soft-panel-square">
                    <span class="generic-card-title generic-card-title--small"><?= siteStatsEscape(siteStatsT('stats.holons.total')) ?></span>
                    <strong><?= number_format($totalHolons, 0, ',', ' ') ?></strong>
                    <span><?= siteStatsEscape(siteStatsT('stats.holons.modified_30')) ?> : <b><?= number_format($modifiedHolons30, 0, ',', ' ') ?></b></span>
                </div>
                <figure class="site-stats-chart-panel generic-soft-panel">
                    <figcaption><?= siteStatsEscape(siteStatsT('stats.holons.chart')) ?></figcaption>
                    <?= siteStatsRenderDualChart(
                        'site-stats-holons-chart',
                        $holonCreationSeries,
                        $holonModificationSeries,
                        siteStatsT('stats.holons.chart'),
                        $chartMode,
                        siteStatsT('stats.holons.created'),
                        siteStatsT('stats.holons.modified')
                    ) ?>
                    <p class="site-stats-chart-panel__note"><?= siteStatsEscape(siteStatsT('stats.holons.chart_note')) ?></p>
                </figure>
            </div>
        </section>
    </main>
<?php else: ?>
    <main class="site-stats-access generic-section">
        <span class="generic-card-title generic-card-title--eyebrow"><?= siteStatsEscape(siteStatsT('stats.page.eyebrow')) ?></span>
        <h1 class="generic-card-title generic-card-title--large"><?= siteStatsEscape(siteStatsT('stats.access.title')) ?></h1>
        <p><?= siteStatsEscape(siteStatsT('stats.access.message')) ?></p>
        <a class="generic-action-button generic-action-button--main" href="/omo/"><?= siteStatsEscape(siteStatsT('stats.access.back')) ?></a>
    </main>
<?php endif; ?>
</body>
</html>
