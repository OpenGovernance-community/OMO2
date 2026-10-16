<?php

require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/translation_bundles.php';
require_once dirname(__DIR__) . '/topbar.php';
require_once dirname(__DIR__, 2) . '/omo/topbar.php';

function siteStatsSourceLang(): array
{
    $monitoring = [
        'monitor.title' => 'Performance et charge', 'monitor.intro' => 'Temps de reponse, consommation et activite de la plateforme.',
        'monitor.range' => 'Periode', 'monitor.organization' => 'Organisation', 'monitor.all' => 'Toutes',
        'monitor.kind' => 'Mesures', 'monitor.web' => 'Pages HTTP', 'monitor.cron' => 'Executions du cron', 'monitor.task' => 'Taches du cron',
        'monitor.cron_filter' => 'Execution du cron', 'monitor.any' => 'Tous les cas', 'monitor.none' => 'Sans appel au cron',
        'monitor.called' => 'Cron sollicite', 'monitor.ran' => 'Cron execute', 'monitor.skipped' => 'Cron ignore (verrou ou delai)',
        'monitor.sort' => 'Classement des pages / taches', 'monitor.slow' => 'Duree moyenne', 'monitor.cpu' => 'CPU PHP cumule',
        'monitor.total' => 'Duree cumulee', 'monitor.volume' => 'Nombre d appels', 'monitor.apply' => 'Afficher',
        'monitor.requests' => 'Appels', 'monitor.users' => 'Utilisateurs actifs', 'monitor.p95' => 'p95 estime', 'monitor.median' => 'Mediane estimee',
        'monitor.errors' => 'Erreurs serveur', 'monitor.sql' => 'Temps SQL', 'monitor.memory' => 'Pic memoire PHP',
        'monitor.daily_requests' => 'Appels par jour', 'monitor.daily_users' => 'Utilisateurs actifs par jour',
        'monitor.daily_cpu' => 'CPU PHP par jour (secondes)', 'monitor.daily_per_user' => 'CPU PHP par utilisateur actif (secondes / jour)',
        'monitor.daily_latency' => 'Reponse moyenne et p95 estime (ms)', 'monitor.hourly' => 'Duree et CPU par heure UTC (secondes)',
        'monitor.daily_errors' => 'Erreurs serveur par jour', 'monitor.daily_cron' => 'Temps du cron inclus dans les pages (secondes / jour)',
        'monitor.mean' => 'Moyenne', 'monitor.routes' => 'Pages et traitements', 'monitor.orgs' => 'Charge par organisation',
        'monitor.name' => 'Page / traitement', 'monitor.per_user' => 'Duree / utilisateur actif', 'monitor.cron_calls' => 'Appels cron',
        'monitor.cron_time' => 'Temps cron inclus', 'monitor.unassigned' => 'Sans organisation', 'monitor.unknown_org' => 'Organisation #{id}',
        'monitor.notes' => 'Heures et jours en UTC. Le p95 est la borne haute d une tranche de duree, pas une mesure exacte. Les utilisateurs actifs sont les comptes connectes distincts ; les visiteurs anonymes restent inclus dans les appels.',
        'monitor.cost_note' => 'La duree des pages inclut les attentes et le cron appele pendant la requete. La maintenance du navigateur passe par runtime_maintenance.php : son temps est attribue a cet appel distinct. CPU PHP et temps SQL sont des mesures differentes. Les vues Pages, Cron et Taches se recouvrent : ne pas additionner leurs totaux. Le pic memoire concerne le processus PHP, y compris pour les taches. Le cron global reste sans organisation.',
        'monitor.retention' => 'Details : 30 jours. Agregats horaires : 13 mois. Les mesures en attente sont incluses dans les graphiques.',
        'monitor.pending' => '{count} mesures en attente d agregation. Plus ancienne : {date}.',
        'monitor.ready' => 'Toutes les mesures ont ete agregees.', 'monitor.cpu_coverage' => 'CPU disponible pour {count} appels sur {total}.',
        'monitor.missing' => 'Les tables de suivi ne sont pas encore installees. Appliquer les migrations du site.',
        'monitor.disabled' => 'La collecte des performances est desactivee dans la configuration.',
        'monitor.growth' => 'Croissance globale du site', 'monitor.unavailable' => 'Indisponible',
        'monitor.days' => '{count} jours', 'monitor.error_rate' => 'Taux d erreurs serveur',
        'monitor.sql_queries' => 'Requetes SQL', 'monitor.client_errors' => 'Erreurs HTTP 4xx',
        'ai.title' => 'Consommation IA, API et MCP', 'ai.origin' => 'Origine des appels IA / integrations',
        'ai.source' => 'Origine', 'ai.errors' => 'Appels en echec', 'ai.site' => 'Site', 'ai.api' => 'API', 'ai.mcp' => 'MCP', 'ai.telegram' => 'Telegram', 'ai.background' => 'Traitements automatiques',
        'ai.model_requests' => 'Appels aux modeles', 'ai.exchange_requests' => 'Requetes API / MCP', 'ai.tokens' => 'Jetons des modeles',
        'ai.feature' => 'Fonction / fournisseur / modele', 'ai.input_tokens' => 'Jetons entrants', 'ai.output_tokens' => 'Jetons sortants',
        'ai.characters' => 'Caracteres entree / sortie', 'ai.audio_bytes' => 'Octets audio entrants', 'ai.coverage' => 'Appels avec jetons fournis',
        'ai.help' => 'Les jetons sont ceux renvoyes par les modeles appeles par le site, y compris les tours internes de l assistant. API et MCP comptent uniquement leurs requetes et caracteres echanges, independamment des jetons du modele externe. Les caracteres couvrent les donnees JSON echangees ; les fichiers audio entrants sont comptes en octets. Aucun contenu de question ou reponse n est conserve dans ces mesures. Totaux complets ; tableau limite aux 200 combinaisons les plus consommatrices sur la periode choisie (UTC). Les filtres Mesures, Cron et CalDAV ne concernent pas ce suivi.',
        'caldav.title' => 'Suivi CalDAV', 'caldav.sort' => 'Classement CalDAV',
        'caldav.requests' => 'Appels CalDAV', 'caldav.cpu' => 'CPU PHP CalDAV (secondes)',
        'caldav.users' => 'Utilisateurs les plus consommateurs de CalDAV',
        'caldav.contact' => 'Utilisateur / e-mail', 'caldav.unknown' => 'Compte #{id}',
        'caldav.anonymous' => 'Non identifie', 'caldav.no_email' => 'E-mail indisponible',
        'caldav.notes' => 'Periode et organisation selectionnees ; tous les appels HTTP CalDAV, independamment des filtres Mesures et Cron. Classement limite a 25 comptes, e-mails actuels. Les appels non identifies restent inclus.',
        'caldav.scope_note' => 'Choisir Toutes les organisations pour inclure la decouverte des calendriers et les appels historiquement sans organisation. Les flux ICS sont exclus. Les erreurs 4xx et 5xx ne comprennent pas les refus par propriete dans un HTTP 207.',
        'caldav.period_note' => 'Aujourd hui : detail par heure UTC ; autres periodes : par jour UTC. La derniere tranche est en cours. Le nombre d appels ne donne pas directement le nombre de synchronisations.',
        'caldav.today' => 'Aujourd hui (UTC)', 'caldav.propfind' => 'PROPFIND', 'caldav.proppatch' => 'PROPPATCH',
    ];
    return [
        'stats.page.title' => ['text' => 'Statistiques du site', 'context' => 'Page title for the global site statistics dashboard.'],
        'stats.page.eyebrow' => ['text' => 'Vue globale', 'context' => 'Small eyebrow above the global site statistics title.'],
        'stats.page.intro' => ['text' => 'Un premier aperçu de la croissance et de l activité de la plateforme.', 'context' => 'Introductory copy for the global site statistics dashboard.'],
        'stats.period.label' => ['text' => 'Période des graphiques', 'context' => 'Label above the global chart period selector.'],
        'stats.period.days_30' => ['text' => '30 jours', 'context' => 'Thirty-day chart period option.'],
        'stats.period.days_90' => ['text' => '90 jours', 'context' => 'Ninety-day chart period option.'],
        'stats.period.days_365' => ['text' => '1 an', 'context' => 'One-year chart period option.'],
        'stats.period.daily' => ['text' => 'par jour', 'context' => 'Chart aggregation label when bars represent days.'],
        'stats.period.hourly' => ['text' => 'par heure UTC', 'context' => 'Chart aggregation label for hourly CalDAV activity.'],
        'stats.period.weekly' => ['text' => 'par semaine', 'context' => 'Chart aggregation label when bars represent weeks.'],
        'stats.organizations.title' => ['text' => 'Organisations', 'context' => 'Title of the organizations statistics block.'],
        'stats.organizations.total' => ['text' => 'Total d’organisations', 'context' => 'Metric label for the total number of organizations.'],
        'stats.organizations.new_month' => ['text' => 'Nouvelles ce mois', 'context' => 'Metric label for organizations created during the current month.'],
        'stats.organizations.chart' => ['text' => 'Nouvelles organisations par date', 'context' => 'Chart title for organization creation counts.'],
        'stats.users.title' => ['text' => 'Utilisateurs', 'context' => 'Title of the users statistics block.'],
        'stats.users.total' => ['text' => 'Total d utilisateurs', 'context' => 'Metric label for the total number of users.'],
        'stats.users.active_30' => ['text' => 'Connectés ces 30 derniers jours', 'context' => 'Metric label for users with a recent connection.'],
        'stats.users.chart' => ['text' => 'Connexions par date', 'context' => 'Chart title for user connection activity.'],
        'stats.users.chart_note' => ['text' => 'Ce graphique repose sur la derniere connexion connue. L activite HTTP figure dans les graphiques de performance.', 'context' => 'Note explaining the current limitation of the user activity chart.'],
		'stats.holons.title' => ['text' => 'Espaces', 'context' => 'Title of the spaces statistics block.'],
        'stats.holons.total' => ['text' => 'Total d’espaces', 'context' => 'Metric label for the total number of active spaces.'],
        'stats.holons.modified_30' => ['text' => 'Modifiés ces 30 derniers jours', 'context' => 'Metric label for holons modified during the last thirty days.'],
        'stats.holons.chart' => ['text' => 'Créations et modifications d’espaces', 'context' => 'Chart title for space creations and modifications.'],
        'stats.holons.created' => ['text' => 'Créations', 'context' => 'Legend label for holon creation bars.'],
        'stats.holons.modified' => ['text' => 'Modifications', 'context' => 'Legend label for distinct modified holon bars.'],
        'stats.holons.chart_note' => ['text' => 'Les modifications sont lues dans les événements d’historique holon_updated et comptées une fois par espace et par période.', 'context' => 'Note explaining the source and aggregation of the space modification series.'],
        'stats.chart.empty' => ['text' => 'Aucune donnée sur cette période.', 'context' => 'Empty state for a statistics bar chart.'],
        'stats.chart.value' => ['one' => '{count} élément', 'other' => '{count} éléments', 'context' => 'Accessible chart bar value label.'],
        'stats.access.title' => ['text' => 'Accès réservé', 'context' => 'Title shown when global site statistics are not available.'],
        'stats.access.message' => ['text' => 'Cette page est reservee aux super administrateurs du site.', 'context' => 'Message shown to users without the site administrator role.'],
        'stats.access.back' => ['text' => 'Retour à OMO', 'context' => 'Back link shown on the statistics access page.'],
    ] + array_map(static fn($text) => ['text' => $text, 'context' => 'Site performance dashboard.'], $monitoring);
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
    $width = 640;
    $height = 230;
    $paddingLeft = 60;
    $paddingRight = 18;
    $paddingTop = 20;
    $paddingBottom = 48;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;
    $maxValue = 0;
    $seriesCount = count($seriesList);
    foreach ($seriesList as $seriesItem) {
        foreach (($seriesItem['series'] ?? []) as $point) {
            $maxValue = max($maxValue, (float)$point['value']);
        }
    }

    if ($maxValue <= 0) {
        return '<div class="site-stats-chart-empty">' . siteStatsEscape(siteStatsT('stats.chart.empty')) . '</div>';
    }

    $series = $seriesList[0]['series'] ?? [];
    $count = count($series);
    $slotWidth = $plotWidth / max(1, $count);
    $barGroupWidth = $slotWidth * 0.72;
    $barWidth = max(0.5, ($barGroupWidth - (($seriesCount - 1) * 3)) / max(1, $seriesCount));
    $labelStep = max(1, (int)ceil($count / 6));
    $svg = '<svg class="site-stats-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-labelledby="' . siteStatsEscape($chartId) . '-title ' . siteStatsEscape($chartId) . '-description">';
    $svg .= '<title id="' . siteStatsEscape($chartId) . '-title">' . siteStatsEscape($ariaLabel) . '</title>';
    $svg .= '<desc id="' . siteStatsEscape($chartId) . '-description">' . siteStatsEscape(siteStatsT('stats.period.' . (in_array($mode, ['weekly', 'hourly'], true) ? $mode : 'daily'))) . '</desc>';
    $svg .= '<line class="site-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . $paddingTop . '" x2="' . ($width - $paddingRight) . '" y2="' . $paddingTop . '" />';
    $svg .= '<line class="site-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . ($paddingTop + $plotHeight / 2) . '" x2="' . ($width - $paddingRight) . '" y2="' . ($paddingTop + $plotHeight / 2) . '" />';
    $svg .= '<line class="site-stats-chart__axis" x1="' . $paddingLeft . '" y1="' . ($paddingTop + $plotHeight) . '" x2="' . ($width - $paddingRight) . '" y2="' . ($paddingTop + $plotHeight) . '" />';
    $svg .= '<text class="site-stats-chart__scale" x="' . ($paddingLeft - 8) . '" y="' . ($paddingTop + 4) . '" text-anchor="end">' . siteStatsEscape(number_format($maxValue, $maxValue < 10 ? 1 : 0, ',', ' ')) . '</text>';

    foreach ($series as $index => $point) {
        $dateLabel = isset($point['label']) ? (string)$point['label'] : ($mode === 'weekly'
            ? 'Semaine du ' . $point['date']->format('d.m.Y')
            : $point['date']->format('d.m.Y'));
        foreach ($seriesList as $seriesIndex => $seriesItem) {
            $secondaryPoint = ($seriesItem['series'] ?? [])[$index] ?? ['value' => 0];
            $value = (float)($secondaryPoint['value'] ?? 0);
            $barHeight = $value > 0 ? max(2, ($value / $maxValue) * $plotHeight) : 1;
            $x = $paddingLeft + ($index * $slotWidth) + (($slotWidth - $barGroupWidth) / 2) + ($seriesIndex * ($barWidth + 3));
            $y = $paddingTop + $plotHeight - $barHeight;
            $seriesLabel = trim((string)($seriesItem['label'] ?? ''));
            $barLabel = ($seriesLabel !== '' ? $seriesLabel . ' - ' : '') . $dateLabel . ' : ' . number_format($value, $value < 10 ? 2 : 0, ',', ' ') . ' ' . ($seriesItem['unit'] ?? '');
            $barClass = trim((string)($seriesItem['class'] ?? ''));
            $svg .= '<rect class="site-stats-chart__bar ' . siteStatsEscape($barClass) . ($value == 0 ? ' site-stats-chart__bar--empty' : '') . '" x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($barHeight, 2) . '" tabindex="0"><title>' . siteStatsEscape($barLabel) . '</title></rect>';
        }
        if ($index % $labelStep === 0 || $index === $count - 1) {
            $labelX = $paddingLeft + ($index * $slotWidth) + ($slotWidth / 2);
            $svg .= '<text class="site-stats-chart__label" x="' . round($labelX, 2) . '" y="' . ($height - 15) . '" text-anchor="middle">' . siteStatsEscape(($point['label'] ?? $point['date']->format('d.m'))) . '</text>';
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

function siteStatsNumber($value, int $decimals = 0): string
{
    return number_format((float)$value, $decimals, ',', ' ');
}

function siteStatsPerformanceTable(array $rows, bool $organizations, array $names): void
{
    if (!$rows) { echo '<p>' . siteStatsEscape(siteStatsT('stats.chart.empty')) . '</p>'; return; }
    echo '<div class="site-stats-table-scroll" tabindex="0"><table class="site-stats-table"><thead><tr>';
    foreach ([$organizations ? 'monitor.organization' : 'monitor.name', 'monitor.requests', 'monitor.users', 'monitor.mean',
        'monitor.p95', 'monitor.total', 'monitor.cpu', 'monitor.sql', 'monitor.errors', 'monitor.cron_calls', 'monitor.cron_time', 'monitor.per_user'] as $key) {
        echo '<th scope="col">' . siteStatsEscape(siteStatsT($key)) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $count = max(1, (int)$row['requests']);
        $name = $organizations ? ((int)$row['organization_id'] === 0 ? siteStatsT('monitor.unassigned')
            : ($names[(int)$row['organization_id']] ?? siteStatsT('monitor.unknown_org', ['id' => $row['organization_id']])))
            : $row['method'] . ' ' . $row['label'];
        $values = [siteStatsNumber($row['requests']), siteStatsNumber($row['active_users']),
            siteStatsNumber($row['duration_ms'] / $count, 1) . ' ms',
            siteStatsNumber(\dbObject\PerformanceHourly::percentile($row)) . ' ms',
            siteStatsNumber($row['duration_ms'] / 1000, 2) . ' s',
            $row['cpu_samples'] > 0 ? siteStatsNumber($row['cpu_ms'] / 1000, 2) . ' s' : siteStatsT('monitor.unavailable'),
            siteStatsNumber($row['sql_ms'] / 1000, 2) . ' s', siteStatsNumber($row['errors']),
            siteStatsNumber($row['cron_calls']), siteStatsNumber($row['cron_ms'] / 1000, 2) . ' s',
            $row['active_users'] > 0 ? siteStatsNumber($row['duration_ms'] / $row['active_users'] / 1000, 2) . ' s' : '-'];
        echo '<tr><th scope="row">' . siteStatsEscape($name) . '</th>';
        foreach ($values as $value) { echo '<td>' . siteStatsEscape($value) . '</td>'; }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function siteStatsCalDavUsers(array $rows): void
{
    if (!$rows) {
        echo '<p>' . siteStatsEscape(siteStatsT('stats.chart.empty')) . '</p>';
        return;
    }
    echo '<div class="site-stats-table-scroll"><table class="site-stats-table" id="caldav-users"><thead><tr>';
    foreach (['caldav.contact', 'monitor.requests', 'caldav.propfind', 'caldav.proppatch', 'monitor.total', 'monitor.cpu',
        'monitor.mean', 'monitor.sql', 'monitor.sql_queries', 'monitor.client_errors', 'monitor.errors'] as $label) {
        echo '<th scope="col">' . siteStatsEscape(siteStatsT($label)) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $userId = (int)$row['user_id'];
        $name = $userId > 0 ? (trim((string)$row['name']) ?: siteStatsT('caldav.unknown', ['id' => $userId])) : siteStatsT('caldav.anonymous');
        $email = trim((string)($row['email'] ?? ''));
        echo '<tr><th scope="row">' . siteStatsEscape($name) . ($userId > 0 && trim((string)$row['name']) !== '' ? ' (#' . $userId . ')' : '') . '<br>';
        echo $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)
            ? '<a href="mailto:' . siteStatsEscape(rawurlencode($email)) . '">' . siteStatsEscape($email) . '</a>'
            : siteStatsEscape(siteStatsT('caldav.no_email'));
        echo '</th>';
        $values = [siteStatsNumber($row['requests']), siteStatsNumber($row['propfind']), siteStatsNumber($row['proppatch']),
            siteStatsNumber($row['duration_ms'] / 1000, 2) . ' s',
            $row['cpu_samples'] > 0 ? siteStatsNumber($row['cpu_ms'] / 1000, 2) . ' s' : siteStatsT('monitor.unavailable'),
            siteStatsNumber($row['duration_ms'] / max(1, $row['requests']), 1) . ' ms',
            siteStatsNumber($row['sql_ms'] / 1000, 2) . ' s', siteStatsNumber($row['query_count']),
            siteStatsNumber($row['client_errors']), siteStatsNumber($row['errors'])];
        foreach ($values as $value) echo '<td>' . siteStatsEscape($value) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

$allowed = commonGetCurrentUserId() > 0 && commonCurrentUserIsSiteAdmin();
header('Cache-Control: no-store');
if (!$allowed) { http_response_code(403); }
$chartRange = (int)($_GET['range'] ?? 30);
if (!in_array($chartRange, [1, 7, 30, 90, 365], true)) { $chartRange = 30; }
$organizationId = max(0, (int)($_GET['organization'] ?? 0));
$kind = in_array($_GET['kind'] ?? '', ['web', 'cron', 'task'], true) ? $_GET['kind'] : 'web';
if ($kind !== 'web') { $organizationId = 0; } // Global maintenance is not owned by the invoking organization.
$cron = in_array($_GET['cron'] ?? '', ['none', 'called', 'ran', 'skipped'], true) ? $_GET['cron'] : 'all';
$sort = in_array($_GET['sort'] ?? '', ['cpu', 'total', 'volume'], true) ? $_GET['sort'] : 'slow';
$aiOrigin = in_array($_GET['ai_origin'] ?? '', ['site', 'api', 'mcp', 'telegram', 'background'], true) ? $_GET['ai_origin'] : '';
$calDavSort = in_array($_GET['caldav_sort'] ?? '', ['cpu', 'total', 'volume'], true) ? $_GET['caldav_sort'] : 'total';
$chartEndDate = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$chartStartDate = $chartEndDate->modify('-' . ($chartRange - 1) . ' days');
$chartMode = $chartRange > 90 ? 'weekly' : 'daily';
$pageTitle = siteStatsT('stats.page.title');
$performance = ['available' => false];
$organizationNames = [];
if ($allowed) {
    $organizationContext = commonResolveOrganizationContext(1);
    if (!is_array($organizationContext) || empty($organizationContext['isValid'])) {
        $organizationContext = ['id' => 1, 'name' => 'OpenMyOrganization', 'host' => commonGetRequestHost(), 'logo' => '/img/logo-OGC.png', 'isValid' => true];
    }
    commonReleaseReadOnlySession();
    $aiUsage = \dbObject\AiUsage::dashboard($chartStartDate->format('Y-m-d H:i:s'), $chartEndDate->modify('+1 day')->format('Y-m-d H:i:s'), $organizationId, $aiOrigin);
    $growth = \dbObject\SiteStatistics::growth($chartStartDate, $chartEndDate);
    $performance = \dbObject\PerformanceHourly::dashboard($chartStartDate->format('Y-m-d H:i:s'),
        $chartEndDate->modify('+1 day')->format('Y-m-d H:i:s'), $organizationId, $kind, $cron, $sort);
    $calDav = \dbObject\PerformanceHourly::calDavDashboard($chartStartDate->format('Y-m-d H:i:s'),
        $chartEndDate->modify('+1 day')->format('Y-m-d H:i:s'), $organizationId, $calDavSort, $chartRange === 1);
    foreach ($performance['organizationNames'] ?? [] as $organization) { $organizationNames[(int)$organization['id']] = $organization['name']; }
}
?>
<!DOCTYPE html>
<html lang="<?= siteStatsEscape(translationBundleResolveRequestLocale('lang')) ?>" data-omo-organization-accent>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= siteStatsEscape($pageTitle) ?></title>
    <?= commonStylesheetTags('/shared_css.css') ?>
    <?= commonStylesheetTags('/common/assets/components.css') ?>
    <?= commonStylesheetTags('/common/admin/stats.css') ?>
</head>
<body class="site-stats-body">
<?php if (!$allowed): ?>
    <main class="site-stats-access generic-section">
        <h1 class="generic-card-title"><?= siteStatsEscape(siteStatsT('stats.access.title')) ?></h1>
        <p><?= siteStatsEscape(siteStatsT('stats.access.message')) ?></p>
        <a class="generic-action-button" href="/omo/"><?= siteStatsEscape(siteStatsT('stats.access.back')) ?></a>
    </main>
<?php else: ?>
    <?php commonRenderTopbar(omoBuildTopbarOptions($organizationContext, ['variant' => 'hub', 'isDemoGuest' => false, 'logoutReturnTo' => '/common/admin/stats.php'])); ?>
    <main class="site-stats-page">
        <header class="generic-hero-panel">
            <span class="generic-card-title generic-card-title--eyebrow"><?= siteStatsEscape(siteStatsT('stats.page.eyebrow')) ?></span>
            <h1 class="generic-card-title generic-card-title--large"><?= siteStatsEscape($pageTitle) ?></h1>
            <p><?= siteStatsEscape(siteStatsT('monitor.intro')) ?></p>
        </header>
        <section class="generic-section"><form class="site-stats-filters" method="get">
            <?php $filters = [
                'range' => ['monitor.range', [1 => siteStatsT('caldav.today')] + array_combine([7, 30, 90, 365], array_map(static fn($days) => siteStatsT('monitor.days', ['count' => $days]), [7, 30, 90, 365])), $chartRange],
                'organization' => ['monitor.organization', [0 => siteStatsT('monitor.all')] + $organizationNames, $organizationId],
                'kind' => ['monitor.kind', ['web' => siteStatsT('monitor.web'), 'cron' => siteStatsT('monitor.cron'), 'task' => siteStatsT('monitor.task')], $kind],
                'cron' => ['monitor.cron_filter', array_combine(['all', 'none', 'called', 'ran', 'skipped'], array_map('siteStatsT', ['monitor.any', 'monitor.none', 'monitor.called', 'monitor.ran', 'monitor.skipped'])), $cron],
                'sort' => ['monitor.sort', array_combine(['slow', 'cpu', 'total', 'volume'], array_map('siteStatsT', ['monitor.slow', 'monitor.cpu', 'monitor.total', 'monitor.volume'])), $sort],
                'ai_origin' => ['ai.origin', ['' => siteStatsT('monitor.any')] + array_combine(['site', 'api', 'mcp', 'telegram', 'background'], array_map(static fn ($origin) => siteStatsT('ai.' . $origin), ['site', 'api', 'mcp', 'telegram', 'background'])), $aiOrigin],
                'caldav_sort' => ['caldav.sort', array_combine(['total', 'volume', 'cpu'], array_map('siteStatsT', ['monitor.total', 'monitor.volume', 'monitor.cpu'])), $calDavSort],
            ]; foreach ($filters as $field => [$label, $options, $selected]): ?>
                <label><?= siteStatsEscape(siteStatsT($label)) ?>
                    <select class="generic-form-control" name="<?= siteStatsEscape($field) ?>">
                        <?php foreach ($options as $value => $text): ?><option value="<?= siteStatsEscape($value) ?>"<?= (string)$value === (string)$selected ? ' selected' : '' ?>><?= siteStatsEscape($text) ?></option><?php endforeach; ?>
                    </select>
                </label>
            <?php endforeach; ?>
            <button class="generic-action-button generic-action-button--main" type="submit"><?= siteStatsEscape(siteStatsT('monitor.apply')) ?></button>
        </form></section>
        <h2 class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('monitor.title')) ?> — <?= siteStatsEscape(siteStatsT('monitor.' . $kind)) ?></h2>
        <?php if (!commonPerformanceEnabled()): ?><p class="generic-soft-panel"><?= siteStatsEscape(siteStatsT('monitor.disabled')) ?></p><?php endif; ?>
        <?php if (!$performance['available']): ?><p class="generic-soft-panel"><?= siteStatsEscape(siteStatsT('monitor.missing')) ?></p>
        <?php else:
            $totals = $performance['totals'];
            $metrics = [
                'monitor.requests' => siteStatsNumber($totals['requests']),
                'monitor.users' => siteStatsNumber($totals['active_users']),
                'monitor.p95' => siteStatsNumber(\dbObject\PerformanceHourly::percentile($totals)) . ' ms',
                'monitor.median' => siteStatsNumber(\dbObject\PerformanceHourly::percentile($totals, 0.5)) . ' ms',
                'monitor.cpu' => $totals['cpu_samples'] > 0 ? siteStatsNumber($totals['cpu_ms'] / 1000, 2) . ' s' : siteStatsT('monitor.unavailable'),
                'monitor.sql' => siteStatsNumber($totals['sql_ms'] / 1000, 2) . ' s',
                'monitor.sql_queries' => siteStatsNumber($totals['query_count']),
                'monitor.ran' => siteStatsNumber($totals['cron_runs']),
                'monitor.skipped' => siteStatsNumber($totals['cron_skips']),
                'monitor.client_errors' => siteStatsNumber($totals['client_errors']),
                'monitor.error_rate' => siteStatsNumber(100 * $totals['errors'] / max(1, $totals['requests']), 1) . ' %',
                'monitor.memory' => siteStatsNumber($totals['peak_memory'] / 1048576, 1) . ' MiB',
            ]; ?>
            <div class="site-stats-metrics">
                <?php foreach ($metrics as $label => $value): ?><div class="site-stats-metric generic-soft-panel generic-soft-panel--stack"><span><?= siteStatsEscape(siteStatsT($label)) ?></span><strong><?= siteStatsEscape($value) ?></strong></div><?php endforeach; ?>
            </div>
            <?php
            $daily = array_column($performance['daily'], null, 'label');
            $dailyPoints = [];
            for ($date = $chartStartDate; $date <= $chartEndDate; $date = $date->modify('+1 day')) {
                $row = $daily[$date->format('Y-m-d')] ?? [];
                $requests = (int)($row['requests'] ?? 0);
                $dailyPoints[] = ['date' => $date, 'requests' => $requests, 'users' => (int)($row['active_users'] ?? 0),
                    'cpu' => ($row['cpu_ms'] ?? 0) / 1000, 'per_user' => ($row['active_users'] ?? 0) > 0 ? ($row['cpu_ms'] ?? 0) / $row['active_users'] / 1000 : 0,
                    'mean' => $requests ? ($row['duration_ms'] ?? 0) / $requests : 0, 'p95' => \dbObject\PerformanceHourly::percentile($row),
                    'errors' => (int)($row['errors'] ?? 0), 'cron' => ($row['cron_ms'] ?? 0) / 1000];
            }
            $series = static fn($field) => array_map(static fn($row) => ['date' => $row['date'], 'value' => $row[$field]], $dailyPoints);
            $hours = array_column($performance['hours'], null, 'label');
            $hourSeries = [];
            $hourCpuSeries = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $hourSeries[] = ['label' => sprintf('%02dh', $hour), 'value' => ($hours[$hour]['duration_ms'] ?? 0) / 1000];
                $hourCpuSeries[] = ['label' => sprintf('%02dh', $hour), 'value' => ($hours[$hour]['cpu_ms'] ?? 0) / 1000];
            }
            $charts = [
                'daily_requests' => [['series' => $series('requests')]], 'daily_users' => [['series' => $series('users')]],
                'daily_cpu' => [['series' => $series('cpu'), 'unit' => 's']], 'daily_per_user' => [['series' => $series('per_user'), 'unit' => 's']],
                'daily_latency' => [['series' => $series('mean'), 'unit' => 'ms', 'label' => siteStatsT('monitor.mean')], ['series' => $series('p95'), 'unit' => 'ms', 'label' => siteStatsT('monitor.p95'), 'class' => 'site-stats-chart__bar--secondary']],
                'hourly' => [['series' => $hourSeries, 'unit' => 's', 'label' => siteStatsT('monitor.total')],
                    ['series' => $hourCpuSeries, 'unit' => 's', 'label' => siteStatsT('monitor.cpu'), 'class' => 'site-stats-chart__bar--secondary']],
                'daily_errors' => [['series' => $series('errors')]],
            ];
            if ($kind === 'web') { $charts['daily_cron'] = [['series' => $series('cron'), 'unit' => 's']]; }
            ?>
            <div class="site-stats-grid">
                <?php foreach ($charts as $id => $chart): ?><figure class="site-stats-chart-panel generic-section generic-section--stack"><figcaption class="generic-card-title"><?= siteStatsEscape(siteStatsT('monitor.' . $id)) ?></figcaption><?= siteStatsRenderBarChart('perf-' . $id, $chart, siteStatsT('monitor.' . $id), 'daily') ?></figure><?php endforeach; ?>
            </div>
            <section class="generic-section">
                <h3 class="generic-card-title"><?= siteStatsEscape(siteStatsT('monitor.routes')) ?></h3>
                <?php siteStatsPerformanceTable($performance['routes'], false, $organizationNames); ?>
            </section>
            <section class="generic-section">
                <h3 class="generic-card-title"><?= siteStatsEscape(siteStatsT('monitor.orgs')) ?></h3>
                <?php siteStatsPerformanceTable($performance['organizations'], true, $organizationNames); ?>
            </section>
            <div class="generic-soft-panel site-stats-notes">
                <p><?= siteStatsEscape(siteStatsT('monitor.notes')) ?></p><p><?= siteStatsEscape(siteStatsT('monitor.cost_note')) ?></p>
                <p><?= siteStatsEscape(siteStatsT('monitor.cpu_coverage', ['count' => siteStatsNumber($totals['cpu_samples']), 'total' => siteStatsNumber($totals['requests'])])) ?></p>
                <p><?= siteStatsEscape(siteStatsT('monitor.retention')) ?></p>
                <p><?= siteStatsEscape($performance['collection']['pending'] > 0 ? siteStatsT('monitor.pending', ['count' => siteStatsNumber($performance['collection']['pending']), 'date' => $performance['collection']['oldest'] . ' UTC']) : siteStatsT('monitor.ready')) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($calDav['available']): ?>
            <h2 id="caldav-monitoring" class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('caldav.title')) ?></h2>
            <div class="generic-soft-panel site-stats-notes">
                <?php foreach (['caldav.notes', 'caldav.scope_note', 'caldav.period_note'] as $note): ?><p><?= siteStatsEscape(siteStatsT($note)) ?></p><?php endforeach; ?>
            </div>
            <?php $calDavTotals = $calDav['totals']; ?>
            <div class="site-stats-metrics">
                <?php foreach (['monitor.requests' => siteStatsNumber($calDavTotals['requests']),
                    'monitor.users' => siteStatsNumber($calDavTotals['active_users']),
                    'monitor.total' => siteStatsNumber($calDavTotals['duration_ms'] / 1000, 2) . ' s',
                    'monitor.cpu' => $calDavTotals['cpu_samples'] > 0 ? siteStatsNumber($calDavTotals['cpu_ms'] / 1000, 2) . ' s' : siteStatsT('monitor.unavailable')] as $label => $value): ?>
                    <div class="site-stats-metric generic-soft-panel generic-soft-panel--stack"><span><?= siteStatsEscape(siteStatsT($label)) ?></span><strong><?= siteStatsEscape($value) ?></strong></div>
                <?php endforeach; ?>
            </div>
            <?php
            $calDavTimeline = array_column($calDav['timeline'], null, 'label');
            $calDavRequests = $calDavCpu = [];
            $calDavHourly = $chartRange === 1;
            $calDavLast = $calDavHourly ? new DateTimeImmutable('now', new DateTimeZone('UTC')) : $chartEndDate;
            foreach (new DatePeriod($chartStartDate, new DateInterval($calDavHourly ? 'PT1H' : 'P1D'), $calDavLast, DatePeriod::INCLUDE_END_DATE) as $date) {
                $row = $calDavTimeline[$date->format($calDavHourly ? 'Y-m-d H:00:00' : 'Y-m-d')] ?? [];
                $point = ['label' => $date->format($calDavHourly ? 'H\h' : 'd.m')];
                $calDavRequests[] = $point + ['value' => (int)($row['requests'] ?? 0)];
                $calDavCpu[] = $point + ['value' => ($row['cpu_ms'] ?? 0) / 1000];
            }
            ?>
            <div class="site-stats-grid">
                <?php foreach (['requests' => [$calDavRequests, ''], 'cpu' => [$calDavCpu, 's']] as $id => [$points, $unit]): ?>
                    <figure class="site-stats-chart-panel generic-section generic-section--stack"><figcaption class="generic-card-title"><?= siteStatsEscape(siteStatsT('caldav.' . $id)) ?></figcaption><?= siteStatsRenderBarChart('caldav-' . $id, [['series' => $points, 'unit' => $unit]], siteStatsT('caldav.' . $id), $calDavHourly ? 'hourly' : 'daily') ?></figure>
                <?php endforeach; ?>
            </div>
            <section class="generic-section">
                <h3 class="generic-card-title"><?= siteStatsEscape(siteStatsT('caldav.users')) ?></h3>
                <?php siteStatsCalDavUsers($calDav['users']); ?>
            </section>
        <?php endif; ?>
        <section class="generic-section generic-section--stack" id="ai-usage">
            <div class="generic-heading-with-help"><h2 class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('ai.title')) ?></h2>
                <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover><summary aria-label="<?= siteStatsEscape(siteStatsT('ai.title')) ?>">?</summary><div class="generic-context-help__content"><p><?= siteStatsEscape(siteStatsT('ai.help')) ?></p></div></details>
            </div>
            <?php if (empty($aiUsage['available'])): ?><p><?= siteStatsEscape(siteStatsT('monitor.missing')) ?></p>
            <?php else: $aiTotals = $aiUsage['totals']; ?>
            <div class="site-stats-metrics">
                <?php foreach (['ai.model_requests' => $aiTotals['model_requests'], 'ai.exchange_requests' => $aiTotals['exchange_requests'], 'ai.tokens' => $aiTotals['tokens'], 'ai.errors' => $aiTotals['errors']] as $label => $value): ?>
                <div class="site-stats-metric generic-soft-panel generic-soft-panel--stack"><span><?= siteStatsEscape(siteStatsT($label)) ?></span><strong><?= $label === 'ai.tokens' && $value === null && (int)$aiTotals['model_requests'] > 0 ? siteStatsEscape(siteStatsT('monitor.unavailable')) : siteStatsNumber($value ?? 0) ?></strong></div>
                <?php endforeach; ?>
            </div>
            <p class="site-stats-notes"><?= siteStatsEscape(siteStatsT('ai.coverage')) ?> : <?= siteStatsNumber($aiTotals['measured_requests']) ?> / <?= siteStatsNumber($aiTotals['model_requests'] ?? 0) ?></p>
            <?php if (!$aiUsage['rows']): ?><p><?= siteStatsEscape(siteStatsT('stats.chart.empty')) ?></p><?php else: ?>
            <div class="site-stats-table-scroll"><table class="site-stats-table" id="ai-usage-table"><thead><tr>
                <?php foreach (['caldav.contact', 'monitor.organization', 'ai.source', 'ai.feature', 'monitor.requests', 'ai.errors', 'ai.input_tokens', 'ai.output_tokens', 'ai.tokens', 'ai.characters', 'ai.audio_bytes'] as $label): ?><th><?= siteStatsEscape(siteStatsT($label)) ?></th><?php endforeach; ?>
            </tr></thead><tbody>
            <?php foreach ($aiUsage['rows'] as $row): ?>
                <tr><th><?= siteStatsEscape(trim((string)$row['name']) ?: ($row['IDuser'] ? siteStatsT('caldav.unknown', ['id' => $row['IDuser']]) : siteStatsT('ai.background'))) ?><br><?= siteStatsEscape($row['email'] ?? '') ?></th>
                    <td><?= siteStatsEscape($row['organization_name'] ?? siteStatsT('monitor.unassigned')) ?></td>
                    <td><?= siteStatsEscape(siteStatsT('ai.' . $row['origin'])) ?></td>
                    <td><?= siteStatsEscape(implode(' / ', array_filter([$row['feature'], $row['provider'], $row['model']]))) ?></td>
                    <td><?= siteStatsNumber($row['requests']) ?></td><td><?= siteStatsNumber($row['errors']) ?></td>
                    <?php foreach (['input_tokens', 'output_tokens', 'total_tokens'] as $field): ?><td><?= $row['kind'] !== 'model' ? '-' : ($row[$field] === null ? siteStatsEscape(siteStatsT('monitor.unavailable')) : siteStatsNumber($row[$field])) ?></td><?php endforeach; ?>
                    <td><?= siteStatsNumber($row['input_chars']) ?> / <?= siteStatsNumber($row['output_chars']) ?></td><td><?= siteStatsNumber($row['audio_bytes']) ?></td>
                </tr>
            <?php endforeach; ?></tbody></table></div><?php endif; endif; ?>
        </section>
        <h2 class="generic-card-title generic-card-title--section"><?= siteStatsEscape(siteStatsT('monitor.growth')) ?></h2>
        <div class="site-stats-grid">
            <?php foreach ([
                ['organizations', 'totalOrganizations', 'newOrganizationsThisMonth', 'new_month', 'organizationRows'],
                ['users', 'totalUsers', 'connectedUsers30', 'active_30', 'userRows'],
                ['holons', 'totalHolons', 'modifiedHolons30', 'modified_30', 'holonCreationRows'],
            ] as [$section, $totalKey, $recentKey, $recentLabel, $rowsKey]): ?>
                <section class="generic-section generic-section--stack site-stats-growth">
                    <h3 class="generic-card-title"><?= siteStatsEscape(siteStatsT('stats.' . $section . '.title')) ?></h3>
                    <strong><?= siteStatsNumber($growth[$totalKey]) ?></strong>
                    <p><?= siteStatsEscape(siteStatsT('stats.' . $section . '.' . $recentLabel)) ?> : <?= siteStatsNumber($growth[$recentKey]) ?></p>
                    <figure class="site-stats-chart-panel">
                        <figcaption><?= siteStatsEscape(siteStatsT('stats.' . $section . '.chart')) ?></figcaption>
                        <?php $points = siteStatsBuildSeries($growth[$rowsKey] ?: [], $chartStartDate, $chartEndDate, $chartMode);
                        echo $section === 'holons' ? siteStatsRenderDualChart('growth-' . $section, $points,
                            siteStatsBuildSeries($growth['holonModificationRows'] ?: [], $chartStartDate, $chartEndDate, $chartMode),
                            siteStatsT('stats.holons.chart'), $chartMode, siteStatsT('stats.holons.created'), siteStatsT('stats.holons.modified'))
                            : siteStatsRenderChart('growth-' . $section, $points, siteStatsT('stats.' . $section . '.chart'), $chartMode); ?>
                        <?php if ($section !== 'organizations'): ?><p class="site-stats-notes"><?= siteStatsEscape(siteStatsT('stats.' . $section . '.chart_note')) ?></p><?php endif; ?>
                    </figure>
                </section>
            <?php endforeach; ?>
        </div>
    </main>
<?php endif; ?>
</body>
</html>
