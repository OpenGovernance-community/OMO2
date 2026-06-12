<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';

use dbObject\DecisionProcess;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;

function omoDecisionsIndexToDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $exception) {
        return null;
    }
}

function omoDecisionsIndexFormatDate($value, $formatter)
{
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if ($formatter instanceof IntlDateFormatter) {
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y');
}

function omoDecisionsIndexFormatDateTime($value, $formatter)
{
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if ($formatter instanceof IntlDateFormatter) {
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y H:i');
}

function omoDecisionsIndexBuildInitials($label)
{
    $label = trim((string)$label);
    if ($label === '') {
        return 'P';
    }

    $words = preg_split('/\s+/u', $label) ?: [];
    $initials = '';

    foreach ($words as $word) {
        $word = trim((string)$word);
        if ($word === '') {
            continue;
        }

        $initials .= mb_substr($word, 0, 1, 'UTF-8');
        if (mb_strlen($initials, 'UTF-8') >= 2) {
            break;
        }
    }

    if ($initials === '') {
        $initials = mb_substr($label, 0, 1, 'UTF-8');
    }

    return mb_strtoupper($initials !== '' ? $initials : 'P', 'UTF-8');
}

function omoDecisionsIndexResolveOwnerCard($userId, $organizationId)
{
    static $cache = [];

    $userId = (int)$userId;
    $organizationId = (int)$organizationId;
    $cacheKey = $organizationId . ':' . $userId;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $card = [
        'displayName' => '',
        'photoUrl' => '',
        'initials' => 'P',
    ];

    if ($userId <= 0) {
        $cache[$cacheKey] = $card;
        return $card;
    }

    $user = new User();
    if (!$user->load($userId)) {
        $cache[$cacheKey] = $card;
        return $card;
    }

    $displayName = trim((string)$user->getScopedDisplayName($organizationId));
    $photoUrl = trim((string)$user->getScopedProfilePhotoUrl($organizationId));
    $initials = '';
    $membership = $user->getOrganizationMembership($organizationId);
    if ($membership && method_exists($membership, 'getUserInitials')) {
        $initials = trim((string)$membership->getUserInitials());
    }
    if ($initials === '') {
        $initials = omoDecisionsIndexBuildInitials($displayName !== '' ? $displayName : (string)$user->getScopedEmail($organizationId));
    }

    $card = [
        'displayName' => $displayName,
        'photoUrl' => $photoUrl,
        'initials' => $initials !== '' ? $initials : 'P',
    ];

    $cache[$cacheKey] = $card;
    return $card;
}

function omoDecisionsIndexResolveScopeMeta(Organization $organization, $holonId, $holonLabel = '')
{
    static $cache = [];

    $organizationId = (int)$organization->getId();
    $holonId = (int)$holonId;
    $cacheKey = $organizationId . ':' . $holonId;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $organizationName = trim((string)$organization->get('name'));
    $rootHolon = $organization->getEnabledStructuralRootHolon();
    $organizationTypeLabel = $rootHolon ? trim((string)$rootHolon->getTemplateLabel(true)) : 'Organisation';

    if ($holonId <= 0) {
        $cache[$cacheKey] = [
            'typeLabel' => $organizationTypeLabel !== '' ? $organizationTypeLabel : 'Organisation',
            'value' => $organizationName !== '' ? $organizationName : trim((string)$holonLabel),
        ];
        return $cache[$cacheKey];
    }

    $holon = new Holon();
    if (!$holon->load($holonId) || !$organization->containsHolon($holon)) {
        $cache[$cacheKey] = [
            'typeLabel' => 'Structure',
            'value' => trim((string)$holonLabel),
        ];
        return $cache[$cacheKey];
    }

    $cache[$cacheKey] = [
        'typeLabel' => trim((string)$holon->getTemplateLabel(true)),
        'value' => trim((string)$holon->get('name')),
    ];

    return $cache[$cacheKey];
}

function omoDecisionsIndexResolvePrimaryActionLabel($status, $canManage, array $lang, array $sourceLang)
{
    switch ((string)$status) {
        case DecisionProcess::STATUS_DRAFT:
            return t('decisions.index.action.edit_continue', [], $lang, $sourceLang);
        case DecisionProcess::STATUS_SCHEDULED:
            return t('decisions.index.action.view_edit', [], $lang, $sourceLang);
        case DecisionProcess::STATUS_CONSULTATION:
            return t('decisions.index.action.open', [], $lang, $sourceLang);
        case DecisionProcess::STATUS_EVALUATION:
            return $canManage
                ? t('decisions.index.action.manage', [], $lang, $sourceLang)
                : t('decisions.index.action.participate', [], $lang, $sourceLang);
        case DecisionProcess::STATUS_RESULTS:
            return t('decisions.index.action.view_results', [], $lang, $sourceLang);
        case DecisionProcess::STATUS_ARCHIVED:
            return t('decisions.index.action.consult', [], $lang, $sourceLang);
        default:
            return $canManage
                ? t('decisions.index.action.manage', [], $lang, $sourceLang)
                : t('decisions.index.action.open', [], $lang, $sourceLang);
    }
}

$sourceLang = [
    'decisions.index.title' => [
        'text' => 'Prises de décision',
        'context' => 'Main title of the decisions drawer entry screen.',
    ],
    'decisions.index.description' => [
        'text' => 'Centralisez ici les consultations et prises de décision accessibles dans votre organisation, puis ouvrez le bon flux selon leur statut.',
        'context' => 'Short description shown under the decisions module title.',
    ],
    'decisions.index.new' => [
        'text' => 'Nouvelle prise de décision',
        'context' => 'Primary call to action opening the decision creation screen.',
    ],
    'decisions.index.count' => [
        'one' => '{count} prise de décision',
        'other' => '{count} prises de décision',
        'context' => 'Counter for decisions visible in the list after filtering.',
    ],
    'decisions.index.context.organization_invalid' => [
        'text' => 'Organisation invalide.',
        'context' => 'Error message when the organization context is missing or invalid.',
    ],
    'decisions.index.context.organization_not_found' => [
        'text' => 'Organisation introuvable.',
        'context' => 'Error message when the organization cannot be loaded.',
    ],
    'decisions.index.context.organization_denied' => [
        'text' => 'Accès refusé à cette organisation.',
        'context' => 'Error message when the current user cannot view the organization.',
    ],
    'decisions.index.context.holon_not_found' => [
        'text' => 'Holon introuvable pour cette organisation.',
        'context' => 'Error message when the requested holon context cannot be loaded.',
    ],
    'decisions.index.context.holon_denied' => [
        'text' => 'Accès refusé à ce holon.',
        'context' => 'Error message when the user cannot access the requested holon context.',
    ],
    'decisions.index.filters.status.all' => [
        'text' => 'Toutes',
        'context' => 'Status filter label showing every decision.',
    ],
    'decisions.index.filters.status.active' => [
        'text' => 'Actif',
        'context' => 'Status filter label for currently active decisions.',
    ],
    'decisions.index.filters.status.draft' => [
        'text' => 'En préparation',
        'context' => 'Status filter label for draft decisions.',
    ],
    'decisions.index.filters.status.scheduled' => [
        'text' => 'Planifiées',
        'context' => 'Status filter label for scheduled decisions.',
    ],
    'decisions.index.filters.status.consultation' => [
        'text' => 'En consultation',
        'context' => 'Status filter label for consultation decisions.',
    ],
    'decisions.index.filters.status.evaluation' => [
        'text' => 'En évaluation',
        'context' => 'Status filter label for evaluation decisions.',
    ],
    'decisions.index.filters.status.results' => [
        'text' => 'Résultats',
        'context' => 'Status filter label for decisions with published results.',
    ],
    'decisions.index.filters.status.archived' => [
        'text' => 'Archivées',
        'context' => 'Status filter label for archived decisions.',
    ],
    'decisions.index.filters.search.label' => [
        'text' => 'Recherche par titre',
        'context' => 'Accessible label for the decision search input.',
    ],
    'decisions.index.filters.search.placeholder' => [
        'text' => 'Rechercher une prise de décision',
        'context' => 'Placeholder inside the decision search input.',
    ],
    'decisions.index.filters.type.label' => [
        'text' => 'Type',
        'context' => 'Label for the type select filter.',
    ],
    'decisions.index.filters.type.all' => [
        'text' => 'Tous les types',
        'context' => 'Select option showing every decision type.',
    ],
    'decisions.index.filters.type.decision' => [
        'text' => 'Décisionnaire',
        'context' => 'UI label for a decision-oriented decision process.',
    ],
    'decisions.index.filters.type.consultation' => [
        'text' => 'Consultative',
        'context' => 'UI label for a consultation-oriented decision process.',
    ],
    'decisions.index.filters.method.label' => [
        'text' => 'Méthode',
        'context' => 'Label for the evaluation method select filter.',
    ],
    'decisions.index.filters.method.all' => [
        'text' => 'Toutes les méthodes',
        'context' => 'Select option showing every evaluation method.',
    ],
    'decisions.index.filters.method.simple_vote' => [
        'text' => 'Vote simple',
        'context' => 'UI label for the simple vote method.',
    ],
    'decisions.index.filters.method.majority_judgment' => [
        'text' => 'Jugement majoritaire',
        'context' => 'UI label for the majority judgment method.',
    ],
    'decisions.index.filters.method.consent' => [
        'text' => 'Consentement',
        'context' => 'UI label for the consent method.',
    ],
    'decisions.index.filters.holon.label' => [
        'text' => 'Structure',
        'context' => 'Label for the structure select filter.',
    ],
    'decisions.index.filters.holon.all' => [
        'text' => 'Toutes les structures',
        'context' => 'Select option showing every structure.',
    ],
    'decisions.index.filters.holon.none' => [
        'text' => 'Sans structure',
        'context' => 'Select option for organization-level decisions without a linked structure.',
    ],
    'decisions.index.filters.reset' => [
        'text' => 'Réinitialiser',
        'context' => 'Secondary button resetting the list filters.',
    ],
    'decisions.index.filters.toggle.show' => [
        'text' => 'Afficher les filtres',
        'context' => 'Button label used to reveal advanced filters below the status tabs.',
    ],
    'decisions.index.filters.toggle.hide' => [
        'text' => 'Masquer les filtres',
        'context' => 'Button label used to hide advanced filters below the status tabs.',
    ],
    'decisions.index.controls.sort.aria' => [
        'text' => 'Tri des prises de decision',
        'context' => 'Accessible label for the sort segmented control.',
    ],
    'decisions.index.controls.sort.time' => [
        'text' => 'Temporel',
        'context' => 'Label for time-based sorting in the decisions list.',
    ],
    'decisions.index.controls.sort.alpha' => [
        'text' => 'Alphabetique',
        'context' => 'Label for alphabetical sorting in the decisions list.',
    ],
    'decisions.index.controls.density.aria' => [
        'text' => 'Densite d affichage des prises de decision',
        'context' => 'Accessible label for the display density segmented control.',
    ],
    'decisions.index.controls.density.detail' => [
        'text' => 'Detail',
        'context' => 'Label for the detailed decisions list density.',
    ],
    'decisions.index.controls.density.compact' => [
        'text' => 'Compact',
        'context' => 'Label for the compact decisions list density.',
    ],
    'decisions.index.compact.header.name' => [
        'text' => 'Decision',
        'context' => 'Column header for the compact decisions list main column.',
    ],
    'decisions.index.compact.header.status' => [
        'text' => 'Statut',
        'context' => 'Column header for the compact decisions list status column.',
    ],
    'decisions.index.compact.header.scope' => [
        'text' => 'Structure',
        'context' => 'Column header for the compact decisions list scope column.',
    ],
    'decisions.index.compact.header.activity' => [
        'text' => 'Activite',
        'context' => 'Column header for the compact decisions list activity column.',
    ],
    'decisions.index.group.today' => [
        'text' => 'Aujourd hui',
        'context' => 'Relative date group title for decisions updated today.',
    ],
    'decisions.index.group.yesterday' => [
        'text' => 'Hier',
        'context' => 'Relative date group title for decisions updated yesterday.',
    ],
    'decisions.index.group.this_week' => [
        'text' => 'Cette semaine',
        'context' => 'Relative date group title for decisions updated this week.',
    ],
    'decisions.index.group.last_week' => [
        'text' => 'La semaine passee',
        'context' => 'Relative date group title for decisions updated last week.',
    ],
    'decisions.index.group.this_month' => [
        'text' => 'Ce mois',
        'context' => 'Relative date group title for decisions updated this month.',
    ],
    'decisions.index.group.last_month' => [
        'text' => 'Le mois passe',
        'context' => 'Relative date group title for decisions updated last month.',
    ],
    'decisions.index.group.this_year' => [
        'text' => 'Cette annee',
        'context' => 'Relative date group title for decisions updated this year.',
    ],
    'decisions.index.group.last_year' => [
        'text' => 'L annee passee',
        'context' => 'Relative date group title for decisions updated last year.',
    ],
    'decisions.index.group.earlier' => [
        'text' => 'Precedemment',
        'context' => 'Relative date group title for older decisions.',
    ],
    'decisions.index.group.too_far' => [
        'text' => 'Trop loin',
        'context' => 'Fallback relative date group title for decisions with missing dates.',
    ],
    'decisions.index.type_label' => [
        'text' => 'Type',
        'context' => 'Card metadata label for the decision type.',
    ],
    'decisions.index.method_label' => [
        'text' => 'Méthode',
        'context' => 'Card metadata label for the evaluation method.',
    ],
    'decisions.index.scope_label' => [
        'text' => 'Structure',
        'context' => 'Fallback card metadata label for the related structure.',
    ],
    'decisions.index.owner_label' => [
        'text' => 'En charge',
        'context' => 'Card metadata label for the person in charge of the decision.',
    ],
    'decisions.index.proposals_label' => [
        'text' => 'Propositions',
        'context' => 'Card stat label for proposal count.',
    ],
    'decisions.index.participants_label' => [
        'text' => 'Participants',
        'context' => 'Card stat label for participant count.',
    ],
    'decisions.index.responses_label' => [
        'text' => 'Réponses',
        'context' => 'Card stat label for submitted response count.',
    ],
    'decisions.index.deadline_label' => [
        'text' => 'Échéance',
        'context' => 'Card metadata label for a closing date or deadline.',
    ],
    'decisions.index.last_activity_label' => [
        'text' => 'Dernière activité',
        'context' => 'Card metadata label for last activity.',
    ],
    'decisions.index.no_holon' => [
        'text' => 'Sans structure liee',
        'context' => 'Fallback label when a decision has no associated structure.',
    ],
    'decisions.index.loading' => [
        'text' => 'Chargement des prises de décision…',
        'context' => 'Temporary loading label displayed while the decision list initializes.',
    ],
    'decisions.index.error' => [
        'text' => 'Impossible de charger la liste pour le moment.',
        'context' => 'Fallback error label when the client rendering of the decision list fails.',
    ],
    'decisions.index.empty.title' => [
        'text' => 'Aucune prise de décision pour le moment',
        'context' => 'Empty state title when the organization has no decisions yet.',
    ],
    'decisions.index.empty.text' => [
        'text' => 'Créez votre première prise de décision pour préparer un vote, un jugement majoritaire, un consentement ou une consultation.',
        'context' => 'Empty state body when no decision exists yet.',
    ],
    'decisions.index.empty.cta' => [
        'text' => 'Créer la première prise de décision',
        'context' => 'Call to action inside the empty state.',
    ],
    'decisions.index.no_results.title' => [
        'text' => 'Aucun résultat avec ces filtres',
        'context' => 'State title when filters hide every decision.',
    ],
    'decisions.index.no_results.text' => [
        'text' => 'Essayez un autre statut, élargissez la recherche ou réinitialisez les filtres.',
        'context' => 'State body when filters hide every decision.',
    ],
    'decisions.index.action.edit_continue' => [
        'text' => 'Continuer l’édition',
        'context' => 'Primary action label for a draft decision.',
    ],
    'decisions.index.action.view_edit' => [
        'text' => 'Voir / modifier',
        'context' => 'Primary action label for a scheduled decision.',
    ],
    'decisions.index.action.open' => [
        'text' => 'Ouvrir',
        'context' => 'Primary action label for a consultation decision.',
    ],
    'decisions.index.action.view' => [
        'text' => 'Voir',
        'context' => 'Secondary action label for viewing an ongoing decision.',
    ],
    'decisions.index.action.participate' => [
        'text' => 'Participer',
        'context' => 'Primary action label for an evaluation decision when the user is a participant.',
    ],
    'decisions.index.action.manage' => [
        'text' => 'Gérer',
        'context' => 'Primary action label for an evaluation decision when the user can manage it.',
    ],
    'decisions.index.action.view_results' => [
        'text' => 'Voir les résultats',
        'context' => 'Primary action label for a results decision.',
    ],
    'decisions.index.action.consult' => [
        'text' => 'Consulter',
        'context' => 'Primary action label for an archived decision.',
    ],
    'decisions.index.action.open_editor_title' => [
        'text' => 'Prises de décision',
        'context' => 'Drawer title used when opening the decision editor from the list.',
    ],
    'decisions.index.card.invited_email' => [
        'text' => 'Invitation par e-mail',
        'context' => 'Badge shown when the current access comes from an e-mail invitation.',
    ],
    'decisions.index.card.owner' => [
        'text' => 'Créée par vous',
        'context' => 'Badge shown when the current user created the decision.',
    ],
    'decisions.index.card.manage' => [
        'text' => 'Gestion',
        'context' => 'Badge shown when the user can manage the decision.',
    ],
];

$lang = omoLoadTranslationBundle('omo_decisions_index', $sourceLang);
$escape = 'omoApiEscape';

$currentOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$decisionScope = strtolower(trim((string)($_GET['decision_scope'] ?? 'contextual')));
if ($decisionScope !== 'global') {
    $decisionScope = 'contextual';
}
$initialOpenDecisionId = isset($_GET['open_decision_id']) ? (int)$_GET['open_decision_id'] : 0;

$refreshUrl = trim((string)($_SERVER['REQUEST_URI'] ?? ''));
if ($refreshUrl !== '') {
    $refreshUrlParts = parse_url($refreshUrl);
    if (!is_array($refreshUrlParts)) {
        $refreshUrlParts = [];
    }

    $refreshPath = isset($refreshUrlParts['path']) ? (string)$refreshUrlParts['path'] : '';
    $refreshQueryParams = [];
    if (!empty($refreshUrlParts['query'])) {
        parse_str((string)$refreshUrlParts['query'], $refreshQueryParams);
    }

    unset($refreshQueryParams['open_decision_id']);

    $refreshUrl = $refreshPath;
    $refreshQuery = http_build_query($refreshQueryParams);
    if ($refreshQuery !== '') {
        $refreshUrl .= '?' . $refreshQuery;
    }
    if (!empty($refreshUrlParts['fragment'])) {
        $refreshUrl .= '#' . (string)$refreshUrlParts['fragment'];
    }
}
$currentUserId = (int)commonGetCurrentUserId();
$currentUserEmail = '';
$currentUser = null;

if ($currentUserId > 0) {
    $currentUser = new User();
    if ($currentUser->load($currentUserId)) {
        $currentUserEmail = trim(mb_strtolower((string)$currentUser->getScopedEmail($currentOrganizationId), 'UTF-8'));
    }
}

$organization = new Organization();
if ($currentOrganizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-decisions omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-empty-state"><?= $escape(t('decisions.index.context.organization_invalid', [], $lang, $sourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

if (!$organization->load($currentOrganizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-decisions omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-empty-state"><?= $escape(t('decisions.index.context.organization_not_found', [], $lang, $sourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-decisions omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-empty-state"><?= $escape(t('decisions.index.context.organization_denied', [], $lang, $sourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$holonContext = omoDecisionResolveOrganizationHolonContext($organization, $currentHolonId);
if (empty($holonContext['status'])) {
    http_response_code((int)($holonContext['code'] ?? 404));
    $errorKey = (string)($holonContext['error_key'] ?? 'decisions.edit.context.holon_not_found');
    $messageKey = $errorKey === 'decisions.edit.context.holon_denied'
        ? 'decisions.index.context.holon_denied'
        : 'decisions.index.context.holon_not_found';
    ?>
    <div class="omo-decisions omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-empty-state"><?= $escape(t($messageKey, [], $lang, $sourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$currentContextHolon = $holonContext['holon'] ?? null;
$allowedContextHolonIds = $currentContextHolon
    ? array_fill_keys($currentContextHolon->getVisibleDescendantIds(true), true)
    : [];
$canToggleDecisionScope = $organization->getEnabledStructuralRootHolon() !== null;
$normalizedCurrentHolonId = omoDecisionNormalizeContextHolonId($organization, $currentHolonId);

$statusLabels = [
    DecisionProcess::STATUS_DRAFT => t('decisions.index.filters.status.draft', [], $lang, $sourceLang),
    DecisionProcess::STATUS_SCHEDULED => t('decisions.index.filters.status.scheduled', [], $lang, $sourceLang),
    DecisionProcess::STATUS_CONSULTATION => t('decisions.index.filters.status.consultation', [], $lang, $sourceLang),
    DecisionProcess::STATUS_EVALUATION => t('decisions.index.filters.status.evaluation', [], $lang, $sourceLang),
    DecisionProcess::STATUS_RESULTS => t('decisions.index.filters.status.results', [], $lang, $sourceLang),
    DecisionProcess::STATUS_ARCHIVED => t('decisions.index.filters.status.archived', [], $lang, $sourceLang),
];

$typeLabels = [
    DecisionProcess::TYPE_DECISION => t('decisions.index.filters.type.decision', [], $lang, $sourceLang),
    DecisionProcess::TYPE_CONSULTATION => t('decisions.index.filters.type.consultation', [], $lang, $sourceLang),
];

$methodLabels = [
    DecisionProcess::METHOD_SIMPLE_VOTE => t('decisions.index.filters.method.simple_vote', [], $lang, $sourceLang),
    DecisionProcess::METHOD_MAJORITY_JUDGMENT => t('decisions.index.filters.method.majority_judgment', [], $lang, $sourceLang),
    DecisionProcess::METHOD_CONSENT => t('decisions.index.filters.method.consent', [], $lang, $sourceLang),
];

$dateFormatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_CH', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;
$dateTimeFormatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_CH', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT)
    : null;
$today = new DateTimeImmutable('today');
$decisionGroups = sharedGetRelativeDateGroups($today, [
    'today' => t('decisions.index.group.today', [], $lang, $sourceLang),
    'yesterday' => t('decisions.index.group.yesterday', [], $lang, $sourceLang),
    'this_week' => t('decisions.index.group.this_week', [], $lang, $sourceLang),
    'last_week' => t('decisions.index.group.last_week', [], $lang, $sourceLang),
    'this_month' => t('decisions.index.group.this_month', [], $lang, $sourceLang),
    'last_month' => t('decisions.index.group.last_month', [], $lang, $sourceLang),
    'this_year' => t('decisions.index.group.this_year', [], $lang, $sourceLang),
    'last_year' => t('decisions.index.group.last_year', [], $lang, $sourceLang),
    'earlier' => t('decisions.index.group.earlier', [], $lang, $sourceLang),
    'too_far' => t('decisions.index.group.too_far', [], $lang, $sourceLang),
]);

$organizationCanEdit = omoDecisionCanCreateAtOrganizationLevel($organization, $currentUserId);
$canCreateDecision = $currentContextHolon ? $currentContextHolon->canEdit() : $organizationCanEdit;
$decisionRows = DecisionProcess::fetchListRowsForOrganization($currentOrganizationId, $currentUserId, $currentUserEmail);
$decisionEntries = [];
$holonFilterOptions = [];
$statusCounts = [
    'active' => 0,
    DecisionProcess::STATUS_DRAFT => 0,
    DecisionProcess::STATUS_SCHEDULED => 0,
    DecisionProcess::STATUS_CONSULTATION => 0,
    DecisionProcess::STATUS_EVALUATION => 0,
    DecisionProcess::STATUS_RESULTS => 0,
    DecisionProcess::STATUS_ARCHIVED => 0,
];

foreach ($decisionRows as $row) {
    $decision = new DecisionProcess();
    $decision->loadFromArray($row);
    $decision->setId((int)($row['id'] ?? 0));

    $decisionId = (int)$decision->getId();
    if ($decisionId <= 0) {
        continue;
    }

    $holonId = (int)$decision->get('IDholon');
    if ($decisionScope !== 'global' && $currentContextHolon && $holonId > 0 && !isset($allowedContextHolonIds[$holonId])) {
        continue;
    }

    $status = DecisionProcess::normalizeStatus($decision->get('status'));
    $decisionType = DecisionProcess::normalizeDecisionType($decision->get('decision_type'));
    $method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));

    $isOwner = $currentUserId > 0 && (int)$decision->get('IDuser') === $currentUserId;
    $hasUserParticipation = (int)($row['has_user_participation'] ?? 0) > 0;
    $hasEmailParticipation = !$hasUserParticipation && (int)($row['has_email_participation'] ?? 0) > 0;

    $canManage = $isOwner;

    $canView = $canManage || $isOwner || $hasUserParticipation || $hasEmailParticipation || $status !== DecisionProcess::STATUS_DRAFT;
    if (!$canView) {
        continue;
    }

    $consultationStarted = $decision->hasConsultationStarted();
    $participationOpen = $decision->isParticipationOpen();
    $canParticipate = $participationOpen && ($isOwner || $hasUserParticipation || $hasEmailParticipation);

    $deadline = $decision->get('evaluation_end_at');
    if (!$deadline instanceof DateTimeInterface) {
        $deadline = $decision->get('consultation_end_at');
    }

    $activityCandidates = [
        omoDecisionsIndexToDateTime($decision->get('updated_at')),
        omoDecisionsIndexToDateTime($decision->get('created_at')),
        omoDecisionsIndexToDateTime($row['proposals_updated_at'] ?? ''),
        omoDecisionsIndexToDateTime($row['participants_updated_at'] ?? ''),
        omoDecisionsIndexToDateTime($row['responses_updated_at'] ?? ''),
        omoDecisionsIndexToDateTime($row['responses_submitted_at'] ?? ''),
        omoDecisionsIndexToDateTime($deadline),
    ];

    $lastActivity = null;
    foreach ($activityCandidates as $candidateDate) {
        if (!$candidateDate instanceof DateTimeInterface) {
            continue;
        }

        if ($lastActivity === null || $candidateDate > $lastActivity) {
            $lastActivity = $candidateDate;
        }
    }

    $holonLabel = trim((string)($row['holon_name'] ?? ''));
    if ($holonId > 0 && $holonLabel !== '') {
        $holonFilterOptions[$holonId] = [
            'id' => (int)$holonId,
            'label' => $holonLabel,
        ];
    }

    $scopeMeta = omoDecisionsIndexResolveScopeMeta($organization, $holonId, $holonLabel);
    $ownerCard = omoDecisionsIndexResolveOwnerCard((int)$decision->get('IDuser'), $currentOrganizationId);

    $viewUrl = omoDecisionBuildParticipationPreviewUrl(
        $currentOrganizationId,
        $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
        $decisionId,
        $method,
        'view',
        true
    );
    $manageUrl = omoDecisionBuildEditorUrl(
        $currentOrganizationId,
        $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
        $decisionId,
        $method,
        'manage'
    );
    $participateUrl = omoDecisionBuildParticipationPreviewUrl(
        $currentOrganizationId,
        $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
        $decisionId,
        $method,
        'participate',
        true
    );

    $badges = [];
    if ($isOwner) {
        $badges[] = t('decisions.index.card.owner', [], $lang, $sourceLang);
    } elseif ($hasEmailParticipation) {
        $badges[] = t('decisions.index.card.invited_email', [], $lang, $sourceLang);
    }

    $actions = [];
    if (in_array($status, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true)) {
        $actions[] = [
            'label' => t('decisions.index.action.view_results', [], $lang, $sourceLang),
            'url' => $viewUrl,
            'variant' => 'main',
        ];
    } elseif ($consultationStarted) {
        $actions[] = [
            'label' => t('decisions.index.action.view', [], $lang, $sourceLang),
            'url' => $viewUrl,
            'variant' => 'secondary',
        ];

        if ($canManage) {
            $actions[] = [
                'label' => t('decisions.index.action.manage', [], $lang, $sourceLang),
                'url' => $manageUrl,
                'variant' => 'secondary',
            ];
        }

        if ($canParticipate) {
            $actions[] = [
                'label' => t('decisions.index.action.participate', [], $lang, $sourceLang),
                'url' => $participateUrl,
                'variant' => 'main',
            ];
        }
    } else {
        if ($canManage) {
            $actions[] = [
                'label' => omoDecisionsIndexResolvePrimaryActionLabel($status, $canManage, $lang, $sourceLang),
                'url' => $manageUrl,
                'variant' => 'main',
            ];
        } else {
            $actions[] = [
                'label' => t('decisions.index.action.view', [], $lang, $sourceLang),
                'url' => $viewUrl,
                'variant' => 'secondary',
            ];
        }
    }

    $statusCounts[$status] = isset($statusCounts[$status]) ? $statusCounts[$status] + 1 : 1;
    $isActiveDefault = $status !== DecisionProcess::STATUS_ARCHIVED
        && (
            $isOwner
            || in_array($status, [
                DecisionProcess::STATUS_CONSULTATION,
                DecisionProcess::STATUS_EVALUATION,
                DecisionProcess::STATUS_RESULTS,
            ], true)
        );
    if ($isActiveDefault) {
        $statusCounts['active']++;
    }

    $activityGroupIndex = sharedGetRelativeDateGroupIndexForDate($lastActivity, $decisionGroups, $today);
    $activityGroup = $decisionGroups[$activityGroupIndex] ?? ['key' => 'too_far', 'label' => t('decisions.index.group.too_far', [], $lang, $sourceLang)];

    $decisionEntries[] = [
        'id' => $decisionId,
        'title' => trim((string)$decision->get('title')),
        'sortTitle' => omoApiSortKey(trim((string)$decision->get('title'))),
        'description' => trim((string)$decision->get('description')),
        'status' => $status,
        'statusLabel' => $statusLabels[$status] ?? ucfirst($status),
        'decisionType' => $decisionType,
        'decisionTypeLabel' => $typeLabels[$decisionType] ?? $decisionType,
        'evaluationMethod' => $method,
        'evaluationMethodLabel' => $methodLabels[$method] ?? $method,
        'holonId' => $holonId,
        'holonLabel' => trim((string)($scopeMeta['value'] ?? '')) !== ''
            ? (string)$scopeMeta['value']
            : t('decisions.index.no_holon', [], $lang, $sourceLang),
        'scopeTypeLabel' => trim((string)($scopeMeta['typeLabel'] ?? '')) !== ''
            ? (string)$scopeMeta['typeLabel']
            : t('decisions.index.scope_label', [], $lang, $sourceLang),
        'owner' => $ownerCard,
        'proposalCount' => (int)($row['proposal_count'] ?? 0),
        'participantCount' => (int)($row['participant_count'] ?? 0),
        'responseCount' => (int)($row['response_count'] ?? 0),
        'deadlineLabel' => omoDecisionsIndexFormatDate($deadline, $dateFormatter),
        'deadlineTimestamp' => $deadline instanceof DateTimeInterface ? (int)$deadline->format('U') : 0,
        'lastActivityLabel' => omoDecisionsIndexFormatDateTime($lastActivity, $dateTimeFormatter),
        'lastActivityTimestamp' => $lastActivity instanceof DateTimeInterface ? (int)$lastActivity->format('U') : 0,
        'activityGroupKey' => (string)($activityGroup['key'] ?? 'too_far'),
        'activityGroupLabel' => (string)($activityGroup['label'] ?? t('decisions.index.group.too_far', [], $lang, $sourceLang)),
        'badges' => $badges,
        'isOwner' => $isOwner,
        'isActiveDefault' => $isActiveDefault,
        'canManage' => $canManage,
        'canParticipate' => $canParticipate,
        'consultationStarted' => $consultationStarted,
        'actions' => $actions,
        'searchIndex' => omoApiSortKey(
            trim((string)$decision->get('title')) . ' ' .
            trim((string)$decision->get('description')) . ' ' .
            ($holonLabel !== '' ? $holonLabel : '') . ' ' .
            trim((string)($scopeMeta['typeLabel'] ?? '')) . ' ' .
            trim((string)($ownerCard['displayName'] ?? '')) . ' ' .
            ($typeLabels[$decisionType] ?? $decisionType) . ' ' .
            ($methodLabels[$method] ?? $method)
        ),
    ];
}

usort($decisionEntries, static function (array $left, array $right): int {
    $activityDiff = (int)($right['lastActivityTimestamp'] ?? 0) <=> (int)($left['lastActivityTimestamp'] ?? 0);
    if ($activityDiff !== 0) {
        return $activityDiff;
    }

    return strcmp(
        omoApiSortKey($left['title'] ?? ''),
        omoApiSortKey($right['title'] ?? '')
    );
});

usort($holonFilterOptions, static function (array $left, array $right): int {
    return strcmp(
        omoApiSortKey($left['label'] ?? ''),
        omoApiSortKey($right['label'] ?? '')
    );
});

$statusFilterCatalog = [
    ['key' => 'active', 'label' => t('decisions.index.filters.status.active', [], $lang, $sourceLang)],
    ['key' => DecisionProcess::STATUS_DRAFT, 'label' => $statusLabels[DecisionProcess::STATUS_DRAFT]],
    ['key' => DecisionProcess::STATUS_SCHEDULED, 'label' => $statusLabels[DecisionProcess::STATUS_SCHEDULED]],
    ['key' => DecisionProcess::STATUS_CONSULTATION, 'label' => $statusLabels[DecisionProcess::STATUS_CONSULTATION]],
    ['key' => DecisionProcess::STATUS_EVALUATION, 'label' => $statusLabels[DecisionProcess::STATUS_EVALUATION]],
    ['key' => DecisionProcess::STATUS_RESULTS, 'label' => $statusLabels[DecisionProcess::STATUS_RESULTS]],
    ['key' => DecisionProcess::STATUS_ARCHIVED, 'label' => $statusLabels[DecisionProcess::STATUS_ARCHIVED]],
    ['key' => 'all', 'label' => t('decisions.index.filters.status.all', [], $lang, $sourceLang)],
];

$payload = [
    'items' => array_values($decisionEntries),
    'openDecisionId' => $initialOpenDecisionId > 0 ? $initialOpenDecisionId : 0,
    'groups' => array_map(static function (array $group): array {
        return [
            'key' => (string)($group['key'] ?? ''),
            'label' => (string)($group['label'] ?? ''),
        ];
    }, $decisionGroups),
    'statusFilters' => $statusFilterCatalog,
    'statusCounts' => $statusCounts,
    'typeOptions' => [
        ['value' => 'all', 'label' => t('decisions.index.filters.type.all', [], $lang, $sourceLang)],
        ['value' => DecisionProcess::TYPE_DECISION, 'label' => $typeLabels[DecisionProcess::TYPE_DECISION]],
        ['value' => DecisionProcess::TYPE_CONSULTATION, 'label' => $typeLabels[DecisionProcess::TYPE_CONSULTATION]],
    ],
    'methodOptions' => [
        ['value' => 'all', 'label' => t('decisions.index.filters.method.all', [], $lang, $sourceLang)],
        ['value' => DecisionProcess::METHOD_SIMPLE_VOTE, 'label' => $methodLabels[DecisionProcess::METHOD_SIMPLE_VOTE]],
        ['value' => DecisionProcess::METHOD_MAJORITY_JUDGMENT, 'label' => $methodLabels[DecisionProcess::METHOD_MAJORITY_JUDGMENT]],
        ['value' => DecisionProcess::METHOD_CONSENT, 'label' => $methodLabels[DecisionProcess::METHOD_CONSENT]],
    ],
    'holonOptions' => array_merge(
        [
            ['value' => 'all', 'label' => t('decisions.index.filters.holon.all', [], $lang, $sourceLang)],
            ['value' => '__none__', 'label' => t('decisions.index.filters.holon.none', [], $lang, $sourceLang)],
        ],
        array_map(static function (array $holon): array {
            return [
                'value' => (string)$holon['id'],
                'label' => (string)$holon['label'],
            ];
        }, array_values($holonFilterOptions))
    ),
    'text' => [
        'countOne' => t('decisions.index.count', ['count' => '1'], $lang, $sourceLang),
        'countOtherTemplate' => t('decisions.index.count', ['count' => '__COUNT__'], $lang, $sourceLang),
        'loading' => t('decisions.index.loading', [], $lang, $sourceLang),
        'error' => t('decisions.index.error', [], $lang, $sourceLang),
        'emptyTitle' => t('decisions.index.empty.title', [], $lang, $sourceLang),
        'emptyText' => t('decisions.index.empty.text', [], $lang, $sourceLang),
        'emptyCta' => t('decisions.index.empty.cta', [], $lang, $sourceLang),
        'noResultsTitle' => t('decisions.index.no_results.title', [], $lang, $sourceLang),
        'noResultsText' => t('decisions.index.no_results.text', [], $lang, $sourceLang),
        'typeLabel' => t('decisions.index.type_label', [], $lang, $sourceLang),
        'methodLabel' => t('decisions.index.method_label', [], $lang, $sourceLang),
        'scopeLabel' => t('decisions.index.scope_label', [], $lang, $sourceLang),
        'ownerLabel' => t('decisions.index.owner_label', [], $lang, $sourceLang),
        'proposalsLabel' => t('decisions.index.proposals_label', [], $lang, $sourceLang),
        'participantsLabel' => t('decisions.index.participants_label', [], $lang, $sourceLang),
        'responsesLabel' => t('decisions.index.responses_label', [], $lang, $sourceLang),
        'deadlineLabel' => t('decisions.index.deadline_label', [], $lang, $sourceLang),
        'lastActivityLabel' => t('decisions.index.last_activity_label', [], $lang, $sourceLang),
        'drawerTitle' => t('decisions.index.action.open_editor_title', [], $lang, $sourceLang),
        'filtersToggleShow' => t('decisions.index.filters.toggle.show', [], $lang, $sourceLang),
        'filtersToggleHide' => t('decisions.index.filters.toggle.hide', [], $lang, $sourceLang),
        'sortAriaLabel' => t('decisions.index.controls.sort.aria', [], $lang, $sourceLang),
        'sortTimeLabel' => t('decisions.index.controls.sort.time', [], $lang, $sourceLang),
        'sortAlphaLabel' => t('decisions.index.controls.sort.alpha', [], $lang, $sourceLang),
        'densityAriaLabel' => t('decisions.index.controls.density.aria', [], $lang, $sourceLang),
        'densityDetailLabel' => t('decisions.index.controls.density.detail', [], $lang, $sourceLang),
        'densityCompactLabel' => t('decisions.index.controls.density.compact', [], $lang, $sourceLang),
        'compactHeaderName' => t('decisions.index.compact.header.name', [], $lang, $sourceLang),
        'compactHeaderStatus' => t('decisions.index.compact.header.status', [], $lang, $sourceLang),
        'compactHeaderScope' => t('decisions.index.compact.header.scope', [], $lang, $sourceLang),
        'compactHeaderActivity' => t('decisions.index.compact.header.activity', [], $lang, $sourceLang),
    ],
    'newUrl' => $canCreateDecision
        ? '/omo/api/decision/edit.php?oid=' . $currentOrganizationId . ($normalizedCurrentHolonId > 0 ? '&cid=' . $normalizedCurrentHolonId : '')
        : '',
    'refreshUrl' => $refreshUrl,
];

$payloadJson = json_encode(
    $payload,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if (!is_string($payloadJson)) {
    $payloadJson = '{"items":[],"openDecisionId":0,"groups":[],"statusFilters":[],"statusCounts":{},"typeOptions":[],"methodOptions":[],"holonOptions":[],"text":{},"newUrl":"","refreshUrl":""}';
}
?>
<div
    class="omo-decisions omo-panel-view"
    id="omo-decisions-root"
    data-omo-decision-scope="<?= $escape($decisionScope) ?>"
    data-omo-decision-oid="<?= (int)$currentOrganizationId ?>"
    data-omo-decision-cid="<?= (int)$normalizedCurrentHolonId ?>"
>
    <script type="application/json" data-omo-decisions-payload><?= $payloadJson ?></script>
    <div class="omo-panel-view__header omo-panel-view__header--stacked omo-decisions__hero">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-decisions__app-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M7 6.5h10"></path>
                        <path d="M7 11.5h6"></path>
                        <path d="M7 16.5h5"></path>
                        <path d="M15 15.5l1.6 1.6L20 13.7"></path>
                        <rect x="4" y="3.5" width="16" height="17" rx="3"></rect>
                    </svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-decisions__title-row">
                        <h2 class="omo-panel-view__title"><?= $escape(t('decisions.index.title', [], $lang, $sourceLang)) ?></h2>
                        <span class="omo-panel-view__count" data-omo-decisions-count><?= $escape((string)count($decisionEntries)) ?></span>
                    </div>
                </div>
            </div>
            <div class="omo-panel-view__aside omo-decisions__header-actions">
                <?php if ($canCreateDecision): ?>
                <button type="button" class="generic-action-button generic-action-button--main" data-omo-decisions-new>
                    <?= $escape(t('decisions.index.new', [], $lang, $sourceLang)) ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($canToggleDecisionScope): ?>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-decisions__scope-slot">
                <div
                    class="omo-scope-toggle"
                    role="tablist"
                    aria-label="Portee des decisions"
                    data-omo-scope-switch="<?= $escape($decisionScope) ?>"
                >
                    <button
                        type="button"
                        class="omo-scope-toggle__button<?= $decisionScope === 'contextual' ? ' is-active' : '' ?>"
                        data-omo-decision-scope-toggle="contextual"
                        aria-pressed="<?= $decisionScope === 'contextual' ? 'true' : 'false' ?>"
                    >Contextuel</button>
                    <button
                        type="button"
                        class="omo-scope-toggle__button<?= $decisionScope === 'global' ? ' is-active' : '' ?>"
                        data-omo-decision-scope-toggle="global"
                        aria-pressed="<?= $decisionScope === 'global' ? 'true' : 'false' ?>"
                    >Global</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content">
            <div class="omo-decisions__filters" aria-label="Filtres">
                <div class="omo-decisions__status-bar generic-section">
                    <div class="omo-decisions__status-tabs" data-omo-decisions-status-tabs></div>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary omo-decisions__filters-toggle"
                        data-omo-decisions-filters-toggle
                        aria-expanded="false"
                    >
                        <?= $escape(t('decisions.index.filters.toggle.show', [], $lang, $sourceLang)) ?>
                    </button>
                </div>

                <section class="omo-decisions__filters-panel generic-section generic-section--stack" data-omo-decisions-filters-panel hidden>
                    <div class="omo-decisions__filters-grid">
                        <label class="omo-decisions__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.filters.search.label', [], $lang, $sourceLang)) ?></span>
                            <input
                                type="search"
                                class="generic-form-control"
                                data-omo-decisions-search
                                placeholder="<?= $escape(t('decisions.index.filters.search.placeholder', [], $lang, $sourceLang)) ?>"
                            >
                        </label>

                        <label class="omo-decisions__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.filters.type.label', [], $lang, $sourceLang)) ?></span>
                            <select class="generic-form-control" data-omo-decisions-type></select>
                        </label>

                        <label class="omo-decisions__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.filters.method.label', [], $lang, $sourceLang)) ?></span>
                            <select class="generic-form-control" data-omo-decisions-method></select>
                        </label>

                        <label class="omo-decisions__field">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.filters.holon.label', [], $lang, $sourceLang)) ?></span>
                            <select class="generic-form-control" data-omo-decisions-holon></select>
                        </label>
                    </div>

                    <div class="omo-decisions__filter-actions">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decisions-reset>
                            <?= $escape(t('decisions.index.filters.reset', [], $lang, $sourceLang)) ?>
                        </button>
                    </div>
                </section>
            </div>

            <div class="omo-decisions__state generic-section" data-omo-decisions-state>
                <?= $escape(t('decisions.index.loading', [], $lang, $sourceLang)) ?>
            </div>

            <div class="omo-decisions__list" data-omo-decisions-list hidden></div>

            <div class="omo-overlay-drawer omo-decisions__editor-drawer" data-omo-decision-editor-drawer hidden>
                <div class="omo-overlay-drawer__backdrop" data-omo-decision-editor-close></div>
                <div class="omo-overlay-drawer__panel">
                    <div class="omo-overlay-drawer__header">
                        <div class="omo-overlay-drawer__header-copy">
                            <h3 class="omo-overlay-drawer__title" data-omo-decision-editor-title><?= $escape(t('decisions.index.action.open_editor_title', [], $lang, $sourceLang)) ?></h3>
                            <p class="omo-overlay-drawer__description" data-omo-decision-editor-description><?= $escape(t('decisions.index.description', [], $lang, $sourceLang)) ?></p>
                        </div>
                        <button type="button" class="omo-overlay-drawer__close" data-omo-decision-editor-close>Fermer</button>
                    </div>
                    <div class="omo-overlay-drawer__body omo-decisions__editor-body" data-omo-decision-editor-body></div>
                </div>
            </div>
        </div>
</div>
</div>

<link rel="stylesheet" href="/common/choice/decision_cards.css">
<script src="/common/choice/decision_cards.js"></script>

<style>
.omo-decisions {
    min-height: 100%;
}

.omo-decisions__title-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
    flex-wrap: wrap;
}

.omo-decisions__app-icon {
    --omo-panel-view-app-icon-accent: #0f766e;
}

.omo-decisions__header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}

.omo-decisions__scope-slot {
    min-width: 0;
}

.omo-decisions__filters {
    display: grid;
    gap: 12px;
    margin-bottom: 16px;
}

.omo-decisions__status-bar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}

.omo-decisions__status-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    flex: 1 1 auto;
}

.omo-decisions__filters-toggle {
    flex: 0 0 auto;
}

.omo-decisions__filters-toggle.is-active {
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 28%, var(--color-border, #d1d5db));
}

.omo-decisions__filters-panel[hidden] {
    display: none !important;
}

.omo-decisions__status-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 9px 14px;
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: 999px;
    background: var(--color-surface-alt, #f8fafc);
    color: var(--color-text-light, #475569);
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
}

.omo-decisions__status-tab:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, var(--color-border, #d1d5db));
}

.omo-decisions__status-tab.is-active {
    background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 24%, var(--color-border, #d1d5db));
    color: var(--color-text, #1f2937);
}

.omo-decisions__status-count {
    display: inline-flex;
    min-width: 24px;
    min-height: 24px;
    align-items: center;
    justify-content: center;
    padding: 0 7px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.16);
    color: inherit;
    font-size: 0.82rem;
}

.omo-decisions__filters-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.omo-decisions__field {
    display: grid;
    gap: 8px;
}

.omo-decisions__filter-actions {
    display: flex;
    justify-content: flex-end;
}

.omo-decisions__state {
    color: var(--color-text-light, #475569);
    line-height: 1.6;
}

.omo-decisions__list {
    display: grid;
    gap: 18px;
}

.omo-decisions__list[hidden] {
    display: none !important;
}

.omo-decisions__list.generic-file-list {
    --generic-file-list-columns: minmax(0, 2.7fr) minmax(130px, 0.95fr) minmax(150px, 1.1fr) minmax(132px, 0.95fr);
    --generic-file-list-title-gap: 18px;
    --generic-file-list-table-margin-inline: 12px;
    --generic-file-list-padding-inline-start: 16px;
    --generic-file-list-padding-inline-end: 18px;
    --generic-file-list-header-padding-block: 14px;
    --generic-file-list-row-padding-block: 12px;
    --generic-file-list-menu-space: 0px;
}

.omo-decisions__list.generic-file-list .generic-file-list__group-title {
    padding: 15px 12px;
    font-size: 0.9rem;
}

.omo-decisions__list.generic-file-list .generic-file-list__table {
    margin-inline: 10px;
}

.omo-decisions__group {
    position: relative;
}

.omo-decisions__card-list {
    display: grid;
    gap: 10px;
}

.omo-decisions__compact-row {
    width: 100%;
    border: 0;
    background: transparent;
    color: inherit;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.omo-decisions__compact-row:hover,
.omo-decisions__compact-row:focus-visible {
    background: color-mix(in srgb, var(--color-primary, #2563eb) 4%, var(--color-surface, #ffffff));
}

.omo-decisions__compact-avatar,
.omo-decisions__compact-avatar-photo,
.omo-decisions__compact-avatar-placeholder {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    flex: 0 0 auto;
}

.omo-decisions__compact-avatar-photo {
    display: block;
    object-fit: cover;
}

.omo-decisions__compact-avatar-placeholder {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface-alt, #f8fafc));
    color: var(--color-text, #1f2937);
    font-size: 0.8rem;
    font-weight: 700;
}

.omo-decisions__compact-name-main {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
}

.omo-decisions__compact-title-block {
    display: grid;
    gap: 6px;
    min-width: 0;
}

.omo-decisions__compact-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 8px;
    color: var(--color-text-light, #475569);
    font-size: 0.78rem;
    line-height: 1.35;
}

.omo-decisions__compact-meta-separator {
    opacity: 0.5;
}

.omo-decisions__compact-status {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface-alt, #f8fafc));
    color: var(--color-text, #1f2937);
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.2;
}

.omo-decisions__compact-scope {
    color: var(--color-text, #1f2937);
}

.omo-decisions__compact-activity {
    color: var(--color-text-light, #475569);
}

.omo-decisions__state[hidden] {
    display: none !important;
}

.omo-decisions__empty-title,
.omo-decisions__no-results-title {
    margin: 0;
    font-size: 1.1rem;
    color: var(--color-text, #1f2937);
}

.omo-decisions__empty-text,
.omo-decisions__no-results-text {
    margin: 0;
    color: var(--color-text-light, #475569);
    line-height: 1.6;
}

@media (max-width: 720px) {
    .omo-decisions__status-bar,
    .omo-decisions-card__header {
        flex-direction: column;
        align-items: stretch;
    }

    .omo-decisions__header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .omo-decisions__filters-grid {
        grid-template-columns: 1fr;
    }

    .omo-decisions__header-actions .generic-action-button {
        width: 100%;
    }

    .omo-decisions__filters-toggle {
        width: 100%;
    }

    .omo-decisions__list.generic-file-list .generic-file-list__table {
        margin-inline: 0;
    }

    .omo-decisions__compact-name-main {
        gap: 10px;
    }

    .omo-decisions__compact-title-block {
        gap: 5px;
    }
}
</style>

<script>
(() => {
const root = document.getElementById('omo-decisions-root');
if (!root) {
    return;
}

function omoDecisionParseIndexPayload(node) {
    if (!node) {
        return null;
    }

    try {
        const parsed = JSON.parse(node.textContent || '{}');
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (error) {
        return null;
    }
}

const payloadNode = root.querySelector('[data-omo-decisions-payload]');
const payload = omoDecisionParseIndexPayload(payloadNode) || <?= $payloadJson ?>;
const omoDecisionsPreferencesStorageKey = 'omoDecisionsDisplayPreferences';
const elements = {
    newButton: root.querySelector('[data-omo-decisions-new]'),
    count: root.querySelector('[data-omo-decisions-count]'),
    displayControls: root.querySelector('[data-omo-decisions-display-controls]'),
    statusTabs: root.querySelector('[data-omo-decisions-status-tabs]'),
    filtersToggle: root.querySelector('[data-omo-decisions-filters-toggle]'),
    filtersPanel: root.querySelector('[data-omo-decisions-filters-panel]'),
    search: root.querySelector('[data-omo-decisions-search]'),
    type: root.querySelector('[data-omo-decisions-type]'),
    method: root.querySelector('[data-omo-decisions-method]'),
    holon: root.querySelector('[data-omo-decisions-holon]'),
    reset: root.querySelector('[data-omo-decisions-reset]'),
    state: root.querySelector('[data-omo-decisions-state]'),
    list: root.querySelector('[data-omo-decisions-list]'),
    editorDrawer: root.querySelector('[data-omo-decision-editor-drawer]'),
    editorTitle: root.querySelector('[data-omo-decision-editor-title]'),
    editorDescription: root.querySelector('[data-omo-decision-editor-description]'),
    editorBody: root.querySelector('[data-omo-decision-editor-body]')
};
const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
    ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
    : null;
const savedPreferences = omoDecisionsReadPreferences();
const state = {
    status: 'active',
    search: '',
    type: 'all',
    method: 'all',
    holon: 'all',
    filtersExpanded: false,
    sort: savedPreferences.sort,
    density: savedPreferences.density
};
let omoDecisionIndexRefreshToken = 0;
let omoDecisionScopeRefreshToken = 0;

function omoDecisionsNormalizeSortPreference(value) {
    return String(value || '').trim().toLowerCase() === 'alpha'
        ? 'alpha'
        : 'time';
}

function omoDecisionsNormalizeDensityPreference(value) {
    return String(value || '').trim().toLowerCase() === 'compact'
        ? 'compact'
        : 'detail';
}

function omoDecisionsReadPreferences() {
    let rawValue = '';

    try {
        rawValue = window.localStorage
            ? String(window.localStorage.getItem(omoDecisionsPreferencesStorageKey) || '')
            : '';
    } catch (error) {
        rawValue = '';
    }

    if (rawValue === '') {
        return {
            sort: 'time',
            density: 'detail'
        };
    }

    try {
        const parsed = JSON.parse(rawValue);

        return {
            sort: omoDecisionsNormalizeSortPreference(parsed && parsed.sort ? parsed.sort : null),
            density: omoDecisionsNormalizeDensityPreference(parsed && parsed.density ? parsed.density : null)
        };
    } catch (error) {
        return {
            sort: 'time',
            density: 'detail'
        };
    }
}

function omoDecisionsWritePreferences(preferences) {
    const normalizedPreferences = {
        sort: omoDecisionsNormalizeSortPreference(preferences && preferences.sort ? preferences.sort : null),
        density: omoDecisionsNormalizeDensityPreference(preferences && preferences.density ? preferences.density : null)
    };

    try {
        if (window.localStorage) {
            window.localStorage.setItem(
                omoDecisionsPreferencesStorageKey,
                JSON.stringify(normalizedPreferences)
            );
        }
    } catch (error) {
    }

    window.dispatchEvent(new CustomEvent('omo-decisions-preferences-change', {
        detail: normalizedPreferences
    }));
}

function omoDecisionGetCurrentScope() {
    return String(root.getAttribute('data-omo-decision-scope') || 'contextual').trim().toLowerCase() === 'global'
        ? 'global'
        : 'contextual';
}

function omoDecisionBuildScopeUrl(scope) {
    const resolvedScope = String(scope || '').trim().toLowerCase() === 'global' ? 'global' : 'contextual';
    const query = [];
    const organizationId = Number(root.getAttribute('data-omo-decision-oid') || 0);
    let holonId = Number(root.getAttribute('data-omo-decision-cid') || 0);

    if (typeof window.omoNormalizeRouteCid === 'function') {
        holonId = Number(window.omoNormalizeRouteCid(holonId) || 0);
    }

    if (organizationId > 0) {
        query.push('oid=' + encodeURIComponent(String(organizationId)));
    }
    if (holonId > 0) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }
    if (resolvedScope !== 'contextual') {
        query.push('decision_scope=' + encodeURIComponent(resolvedScope));
    }
    query.push('_=' + String(Date.now()));

    return '/omo/api/decision/index.php' + (query.length ? ('?' + query.join('&')) : '');
}

function omoDecisionSetScopeLoadingState(isLoading, nextScope) {
    root.classList.toggle('is-loading', !!isLoading);
    root.setAttribute('aria-busy', isLoading ? 'true' : 'false');

    const scopeSwitch = root.querySelector('[data-omo-scope-switch]');
    if (scopeSwitch && nextScope) {
        scopeSwitch.setAttribute('data-omo-scope-switch', String(nextScope).trim().toLowerCase() === 'global' ? 'global' : 'contextual');
    }

    root.querySelectorAll('[data-omo-decision-scope-toggle]').forEach(function (button) {
        const buttonScope = String(button.getAttribute('data-omo-decision-scope-toggle') || '').trim().toLowerCase() === 'global'
            ? 'global'
            : 'contextual';
        const isActive = buttonScope === (String(nextScope || '').trim().toLowerCase() === 'global' ? 'global' : 'contextual');

        button.disabled = !!isLoading;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

function omoDecisionReloadForScope(scope) {
    const targetScope = String(scope || '').trim().toLowerCase() === 'global' ? 'global' : 'contextual';
    const requestId = ++omoDecisionScopeRefreshToken;

    if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
        window.location.href = omoDecisionBuildScopeUrl(targetScope);
        return Promise.resolve(null);
    }

    return window.omoReplaceFetchedPanelRoot({
        rootSelector: '#omo-decisions-root',
        currentRoot: root,
        url: omoDecisionBuildScopeUrl(targetScope),
        setLoadingState: function (isLoading) {
            if (requestId !== omoDecisionScopeRefreshToken && !isLoading) {
                return;
            }
            omoDecisionSetScopeLoadingState(isLoading, targetScope);
        }
    });
}

window.omoToggleDecisionsScope = function (button, event) {
    if (event) {
        if (typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (typeof event.stopPropagation === 'function') {
            event.stopPropagation();
        }
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
    }

    if (!(button instanceof Element)) {
        return false;
    }

    const panel = button.closest('#omo-decisions-root');
    if (!panel) {
        return false;
    }

    const currentScope = String(panel.getAttribute('data-omo-decision-scope') || 'contextual').trim().toLowerCase() === 'global'
        ? 'global'
        : 'contextual';
    const targetScope = String(button.getAttribute('data-omo-decision-scope-toggle') || '').trim().toLowerCase() === 'global'
        ? 'global'
        : 'contextual';

    if (targetScope === currentScope) {
        return false;
    }

    omoDecisionSetScopeLoadingState(true, targetScope);

    omoDecisionReloadForScope(targetScope).catch(function () {
        omoDecisionSetScopeLoadingState(false, currentScope);
    });

    return false;
};

function normalizeText(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCountLabel(count) {
    if (Number(count) === 1) {
        return String(payload.text && payload.text.countOne ? payload.text.countOne : '1 prise de décision');
    }

    return String(payload.text && payload.text.countOtherTemplate ? payload.text.countOtherTemplate : '__COUNT__ prises de décision')
        .replace('__COUNT__', String(Number(count) || 0));
}

function restoreSelectValue(select, preferredValue) {
    if (!select) {
        return 'all';
    }

    const normalizedPreferredValue = String(preferredValue || 'all');
    const hasPreferredOption = Array.prototype.some.call(select.options || [], function (option) {
        return String(option.value || '') === normalizedPreferredValue;
    });

    select.value = hasPreferredOption ? normalizedPreferredValue : 'all';
    return String(select.value || 'all');
}

function replacePayload(nextPayload) {
    Object.keys(payload).forEach(function (key) {
        delete payload[key];
    });

    if (nextPayload && typeof nextPayload === 'object') {
        Object.keys(nextPayload).forEach(function (key) {
            payload[key] = nextPayload[key];
        });
    }
}

function applyDecisionIndexPayload(nextPayload) {
    if (!nextPayload || typeof nextPayload !== 'object') {
        return;
    }

    const preservedState = {
        status: state.status,
        search: state.search,
        type: state.type,
        method: state.method,
        holon: state.holon,
        filtersExpanded: state.filtersExpanded,
        sort: state.sort,
        density: state.density
    };

    replacePayload(nextPayload);

    populateSelect(elements.type, payload.typeOptions);
    populateSelect(elements.method, payload.methodOptions);
    populateSelect(elements.holon, payload.holonOptions);

    state.status = preservedState.status;
    state.search = preservedState.search;
    state.type = restoreSelectValue(elements.type, preservedState.type);
    state.method = restoreSelectValue(elements.method, preservedState.method);
    state.holon = restoreSelectValue(elements.holon, preservedState.holon);
    state.filtersExpanded = preservedState.filtersExpanded;
    state.sort = omoDecisionsNormalizeSortPreference(preservedState.sort);
    state.density = omoDecisionsNormalizeDensityPreference(preservedState.density);

    if (elements.search) {
        elements.search.value = state.search;
    }

    if (elements.newButton) {
        const hasNewUrl = String(payload.newUrl || '').trim() !== '';
        elements.newButton.hidden = !hasNewUrl;
        elements.newButton.disabled = !hasNewUrl;
    }

    if (elements.displayControls) {
        elements.displayControls.hidden = !Array.isArray(payload.items) || payload.items.length === 0;
    }

    if (payloadNode) {
        payloadNode.textContent = JSON.stringify(payload);
    }

    renderList();
}

function openDecisionEditor(url, title) {
    const resolvedTitle = title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision');
    if (typeof window.commonTopbarOpenDrawer === 'function') {
        window.commonTopbarOpenDrawer(resolvedTitle, url, 'fetch');
        return;
    }

    window.location.href = url;
}

let omoDecisionEditorRequestToken = 0;
function omoDecisionRefreshIndex(options) {
    if (!document.body.contains(root)) {
        return Promise.resolve(false);
    }

    const refreshUrl = String(payload.refreshUrl || '').trim();
    if (refreshUrl === '') {
        return Promise.resolve(false);
    }

    const settings = options && typeof options === 'object' ? options : {};
    const requestToken = ++omoDecisionIndexRefreshToken;
    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(refreshUrl)
        : refreshUrl;

    return fetch(resolvedUrl, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store'
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('refresh_failed');
            }

            return response.text();
        })
        .then(function (html) {
            if (requestToken !== omoDecisionIndexRefreshToken || !document.body.contains(root)) {
                return false;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(String(html || ''), 'text/html');
            const nextPayload = omoDecisionParseIndexPayload(doc.querySelector('[data-omo-decisions-payload]'));
            if (!nextPayload) {
                throw new Error('payload_missing');
            }

            applyDecisionIndexPayload(nextPayload);
            return true;
        })
        .catch(function () {
            if (!settings.silent) {
                showStateContainer(
                    '<div class="omo-decisions__state-message">' + escapeHtml(payload.text && payload.text.error ? payload.text.error : 'Impossible de charger la liste pour le moment.') + '</div>',
                    'default'
                );
            }

            return false;
        });
}

function omoDecisionOpenNestedEditorDrawer() {
    if (!elements.editorDrawer) {
        return;
    }

    elements.editorDrawer.hidden = false;
    requestAnimationFrame(function () {
        elements.editorDrawer.classList.add('is-open');
    });
}

function omoDecisionCloseNestedEditorDrawer(options) {
    if (!elements.editorDrawer) {
        return;
    }

    const wasOpen = !elements.editorDrawer.hidden || elements.editorDrawer.classList.contains('is-open');
    const settings = options && typeof options === 'object' ? options : {};
    elements.editorDrawer.classList.remove('is-open');
    window.setTimeout(function () {
        if (!elements.editorDrawer.classList.contains('is-open')) {
            elements.editorDrawer.hidden = true;
        }
    }, 200);

    if (wasOpen && settings.refreshIndex !== false) {
        window.setTimeout(function () {
            omoDecisionRefreshIndex({ silent: true });
        }, 0);
    }
}

function omoDecisionRenderNestedEditorLoading() {
    if (!elements.editorBody) {
        return;
    }

    if (typeof window.getSkeleton === 'function') {
        elements.editorBody.innerHTML = window.getSkeleton('panel');
        return;
    }

    elements.editorBody.innerHTML = '<div class="loading">Chargement...</div>';
}

function omoDecisionRenderNestedEditorError() {
    if (!elements.editorBody) {
        return;
    }

    elements.editorBody.innerHTML = '<div class="omo-empty-state">Impossible de charger ce module.</div>';
}

openDecisionEditor = function (url, title, description) {
    if (!url) {
        return;
    }

    const resolvedTitle = title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision');

    if (!elements.editorDrawer || !elements.editorBody) {
        if (typeof window.commonTopbarOpenDrawer === 'function') {
            window.commonTopbarOpenDrawer(resolvedTitle, url, 'fetch');
            return;
        }

        window.location.href = url;
        return;
    }

    if (elements.editorTitle) {
        elements.editorTitle.textContent = resolvedTitle;
    }
    if (elements.editorDescription) {
        elements.editorDescription.textContent = description || '';
    }

    omoDecisionRenderNestedEditorLoading();
    omoDecisionOpenNestedEditorDrawer();

    const requestToken = ++omoDecisionEditorRequestToken;
    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(url)
        : url;

    $.ajax({
        url: resolvedUrl,
        method: 'GET',
        cache: false,
        success: function (data) {
            if (requestToken !== omoDecisionEditorRequestToken || !elements.editorBody) {
                return;
            }

            if (typeof window.jQuery === 'function') {
                window.jQuery(elements.editorBody).html(data);
            } else {
                elements.editorBody.innerHTML = data;
            }

            if (typeof window.omoDecisionVoteInit === 'function') {
                window.omoDecisionVoteInit(elements.editorBody);
            }

            if (typeof window.omoDecisionMajorityJudgmentInit === 'function') {
                window.omoDecisionMajorityJudgmentInit(elements.editorBody);
            }

            if (typeof window.omoDecisionConsentInit === 'function') {
                window.omoDecisionConsentInit(elements.editorBody);
            }
        },
        error: function () {
            if (requestToken !== omoDecisionEditorRequestToken) {
                return;
            }

            omoDecisionRenderNestedEditorError();
        }
    });
};

function findDecisionItemById(decisionId) {
    const resolvedDecisionId = Number(decisionId);
    if (!Number.isInteger(resolvedDecisionId) || resolvedDecisionId <= 0 || !Array.isArray(payload.items)) {
        return null;
    }

    for (let index = 0; index < payload.items.length; index += 1) {
        const item = payload.items[index];
        if (Number(item && item.id ? item.id : 0) === resolvedDecisionId) {
            return item;
        }
    }

    return null;
}

function resolveDecisionAutoOpenAction(item) {
    const actions = Array.isArray(item && item.actions) ? item.actions : [];
    let fallbackAction = null;

    for (let index = 0; index < actions.length; index += 1) {
        const action = actions[index];
        const actionUrl = String(action && action.url ? action.url : '').trim();
        if (actionUrl === '') {
            continue;
        }

        if (!fallbackAction) {
            fallbackAction = action;
        }

        if (String(action && action.variant ? action.variant : '').trim() === 'main') {
            return action;
        }
    }

    return fallbackAction;
}

function openInitialDecisionFromPayload() {
    const decisionId = Number(payload.openDecisionId || 0);
    if (!Number.isInteger(decisionId) || decisionId <= 0) {
        return false;
    }

    payload.openDecisionId = 0;

    const item = findDecisionItemById(decisionId);
    if (!item) {
        return false;
    }

    const action = resolveDecisionAutoOpenAction(item);
    const actionUrl = String(action && action.url ? action.url : '').trim();
    if (actionUrl === '') {
        return false;
    }

    openDecisionEditor(
        actionUrl,
        String(item.title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision')),
        String(item.description || '')
    );

    return true;
}

function populateSelect(select, options) {
    if (!select) {
        return;
    }

    select.innerHTML = '';
    (Array.isArray(options) ? options : []).forEach(function (option) {
        const node = document.createElement('option');
        node.value = String(option && option.value !== undefined ? option.value : '');
        node.textContent = String(option && option.label !== undefined ? option.label : node.value);
        select.appendChild(node);
    });
}

function renderStatusTabs() {
    if (!elements.statusTabs) {
        return;
    }

    elements.statusTabs.innerHTML = '';
    (Array.isArray(payload.statusFilters) ? payload.statusFilters : []).forEach(function (filter) {
        const key = String(filter && filter.key ? filter.key : 'all');
        const count = key === 'all'
            ? (Array.isArray(payload.items) ? payload.items.length : 0)
            : (key === 'active'
                ? Number(payload.statusCounts && payload.statusCounts.active ? payload.statusCounts.active : 0)
                : Number(payload.statusCounts && payload.statusCounts[key] ? payload.statusCounts[key] : 0));
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'omo-decisions__status-tab' + (state.status === key ? ' is-active' : '');
        button.setAttribute('data-status', key);
        button.setAttribute('aria-pressed', state.status === key ? 'true' : 'false');
        button.innerHTML = '<span>' + escapeHtml(filter && filter.label ? filter.label : key) + '</span>'
            + '<span class="omo-decisions__status-count">' + String(count) + '</span>';
        elements.statusTabs.appendChild(button);
    });
}

function compareText(left, right) {
    const normalizedLeft = String(left || '');
    const normalizedRight = String(right || '');

    if (collator) {
        return collator.compare(normalizedLeft, normalizedRight);
    }

    return normalizedLeft.localeCompare(normalizedRight);
}

function sortItemsByTime(items) {
    return items.slice().sort(function (left, right) {
        const activityDiff = Number(right && right.lastActivityTimestamp ? right.lastActivityTimestamp : 0)
            - Number(left && left.lastActivityTimestamp ? left.lastActivityTimestamp : 0);

        if (activityDiff !== 0) {
            return activityDiff;
        }

        return compareText(
            left && left.sortTitle ? left.sortTitle : (left && left.title ? left.title : ''),
            right && right.sortTitle ? right.sortTitle : (right && right.title ? right.title : '')
        );
    });
}

function sortItemsByAlpha(items) {
    return items.slice().sort(function (left, right) {
        const titleDiff = compareText(
            left && left.sortTitle ? left.sortTitle : (left && left.title ? left.title : ''),
            right && right.sortTitle ? right.sortTitle : (right && right.title ? right.title : '')
        );

        if (titleDiff !== 0) {
            return titleDiff;
        }

        return Number(right && right.lastActivityTimestamp ? right.lastActivityTimestamp : 0)
            - Number(left && left.lastActivityTimestamp ? left.lastActivityTimestamp : 0);
    });
}

function getSortedItems(items, sortMode) {
    return sortMode === 'alpha'
        ? sortItemsByAlpha(items)
        : sortItemsByTime(items);
}

function getOrderedGroups() {
    return Array.isArray(payload.groups) ? payload.groups : [];
}

function syncDisplayControlsVisibility() {
    if (!elements.displayControls) {
        return;
    }

    elements.displayControls.hidden = !Array.isArray(payload.items) || payload.items.length === 0;
}

function syncDisplayButtons() {
    root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
        const isActive = String(button.getAttribute('data-omo-decisions-sort') || '') === state.sort;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    root.querySelectorAll('[data-omo-decisions-density]').forEach(function (button) {
        const isActive = String(button.getAttribute('data-omo-decisions-density') || '') === state.density;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

function resetListPresentation() {
    if (!elements.list) {
        return;
    }

    elements.list.className = 'omo-decisions__list';
    elements.list.removeAttribute('data-generic-file-list');
}

function syncAdvancedFiltersVisibility() {
    const isVisible = !!state.filtersExpanded;

    setVisible(elements.filtersPanel, isVisible);

    if (!elements.filtersToggle) {
        return;
    }

    elements.filtersToggle.textContent = isVisible
        ? String(payload.text && payload.text.filtersToggleHide ? payload.text.filtersToggleHide : 'Masquer les filtres')
        : String(payload.text && payload.text.filtersToggleShow ? payload.text.filtersToggleShow : 'Afficher les filtres');
    elements.filtersToggle.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    elements.filtersToggle.classList.toggle('is-active', state.search !== '' || state.type !== 'all' || state.method !== 'all' || state.holon !== 'all');
}

function getFilteredItems() {
    const items = Array.isArray(payload.items) ? payload.items : [];
    const searchNeedle = normalizeText(state.search);

    return items.filter(function (item) {
        if (state.status === 'active') {
            if (!item.isActiveDefault) {
                return false;
            }
        } else if (state.status !== 'all' && String(item.status || '') !== state.status) {
            return false;
        }

        if (state.type !== 'all' && String(item.decisionType || '') !== state.type) {
            return false;
        }

        if (state.method !== 'all' && String(item.evaluationMethod || '') !== state.method) {
            return false;
        }

        if (state.holon === '__none__' && Number(item.holonId || 0) > 0) {
            return false;
        }

        if (state.holon !== 'all' && state.holon !== '__none__' && String(Number(item.holonId || 0)) !== state.holon) {
            return false;
        }

        if (searchNeedle !== '') {
            const haystack = String(item.searchIndex || '');
            if (haystack.indexOf(searchNeedle) === -1) {
                return false;
            }
        }

        return true;
    });
}

function setVisible(node, isVisible) {
    if (!node) {
        return;
    }

    node.hidden = !isVisible;
}

function resetStateContainer() {
    if (!elements.state) {
        return;
    }

    elements.state.className = 'omo-decisions__state generic-section';
    elements.state.innerHTML = '';
    setVisible(elements.state, false);
}

function showStateContainer(html, variant) {
    if (!elements.state) {
        return;
    }

    elements.state.className = 'omo-decisions__state ' + (variant === 'empty'
        ? 'generic-hero-panel generic-hero-panel--accent'
        : 'generic-section');
    elements.state.innerHTML = String(html || '');
    setVisible(elements.state, true);
}

function buildMetaItem(label, value) {
    const normalizedLabel = String(label || '').trim();
    const normalizedValue = String(value || '').trim();
    if (normalizedLabel === '' || normalizedValue === '') {
        return '';
    }

    return '<span class="omo-decisions-card__meta-item"><strong>'
        + escapeHtml(normalizedLabel)
        + '</strong><span>'
        + escapeHtml(normalizedValue)
        + '</span></span>';
}

function renderCard(item) {
    const sharedCards = window.commonChoiceDecisionCards;
    if (sharedCards && typeof sharedCards.renderCard === 'function') {
        const owner = item && item.owner ? item.owner : {};
        const ownerName = String(owner.displayName || '').trim();
        const metaItems = [
            {
                label: payload.text.typeLabel || 'Type',
                value: String(item.decisionTypeLabel || '')
            },
            {
                label: payload.text.methodLabel || 'Methode',
                value: String(item.evaluationMethodLabel || '')
            },
            {
                label: String(item.scopeTypeLabel || payload.text.scopeLabel || 'Structure'),
                value: String(item.holonLabel || '')
            }
        ];
        const stats = [
            {
                value: String(Number(item.proposalCount || 0)),
                label: payload.text.proposalsLabel || 'Propositions'
            },
            {
                value: String(Number(item.participantCount || 0)),
                label: payload.text.participantsLabel || 'Participants'
            },
            {
                value: String(Number(item.responseCount || 0)),
                label: payload.text.responsesLabel || 'Reponses'
            }
        ];
        const dateItems = [];

        if (ownerName !== '') {
            metaItems.unshift({
                label: payload.text.ownerLabel || 'En charge',
                value: ownerName
            });
        }

        if (item.deadlineLabel) {
            dateItems.push({
                label: payload.text.deadlineLabel || 'Echeance',
                value: item.deadlineLabel
            });
        }

        if (item.lastActivityLabel) {
            dateItems.push({
                label: payload.text.lastActivityLabel || 'Derniere activite',
                value: item.lastActivityLabel
            });
        }

        return sharedCards.renderCard({
            title: String(item.title || ''),
            description: String(item.description || '').trim(),
            statusLabel: String(item.statusLabel || ''),
            owner: owner,
            badges: Array.isArray(item.badges) ? item.badges : [],
            actions: Array.isArray(item.actions) ? item.actions : [],
            metaItems: metaItems,
            stats: stats,
            dateItems: dateItems
        }, {
            fallbackTitle: payload.text.drawerTitle || 'Prises de decision',
            openUrlAttribute: 'data-open-url',
            openTitleAttribute: 'data-open-title'
        });
    }

    const article = document.createElement('article');
    article.className = 'omo-decisions-card generic-section generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed';
    article.setAttribute('data-generic-accordion', '1');

    const description = String(item.description || '').trim();
    const badges = Array.isArray(item.badges) ? item.badges : [];
    const actions = Array.isArray(item.actions) ? item.actions : [];
    const owner = item && item.owner ? item.owner : {};
    const ownerName = String(owner.displayName || '').trim();
    const ownerInitials = String(owner.initials || 'P').trim() || 'P';
    const ownerPhotoUrl = String(owner.photoUrl || '').trim();
    const metaBits = [
        buildMetaItem(payload.text.typeLabel || 'Type', String(item.decisionTypeLabel || '')),
        buildMetaItem(payload.text.methodLabel || 'Méthode', String(item.evaluationMethodLabel || '')),
        buildMetaItem(String(item.scopeTypeLabel || payload.text.scopeLabel || 'Structure'), String(item.holonLabel || ''))
    ];

    if (ownerName !== '') {
        metaBits.unshift(buildMetaItem(payload.text.ownerLabel || 'En charge', ownerName));
    }

    let badgesHtml = '';
    if (badges.length > 0) {
        badgesHtml = '<div class="omo-decisions-card__badges">';
        badges.forEach(function (badge) {
            badgesHtml += '<span class="omo-decisions-card__badge">' + escapeHtml(badge || '') + '</span>';
        });
        badgesHtml += '</div>';
    }

    let ownerAvatarHtml = '';
    if (ownerPhotoUrl !== '') {
        ownerAvatarHtml = '<img src="' + escapeHtml(ownerPhotoUrl) + '" alt="' + escapeHtml(ownerName !== '' ? ownerName : ownerInitials) + '" class="omo-decisions-card__owner-photo">';
    } else {
        ownerAvatarHtml = '<span class="omo-decisions-card__owner-placeholder">' + escapeHtml(ownerInitials) + '</span>';
    }

    let actionsHtml = '<div class="omo-decisions-card__actions">';
    actions.forEach(function (action) {
        const actionVariant = String(action && action.variant ? action.variant : 'secondary');
        actionsHtml += '<button type="button" class="generic-action-button generic-action-button--' + escapeHtml(actionVariant) + ' omo-decisions-card__action" data-open-url="' + escapeHtml(action && action.url ? action.url : '') + '">' + escapeHtml(action && action.label ? action.label : '') + '</button>';
    });
    actionsHtml += '</div>';

    article.innerHTML = '<div class="omo-decisions-card__header generic-accordion__header">'
        + '<button type="button" class="omo-decisions-card__summary" data-generic-accordion-toggle aria-expanded="false">'
            + '<span class="omo-decisions-card__owner-avatar">' + ownerAvatarHtml + '</span>'
            + '<span class="omo-decisions-card__summary-copy">'
                + '<span class="omo-decisions-card__summary-top">'
                    + '<span class="omo-decisions-card__title">' + escapeHtml(item.title || '') + '</span>'
                    + '<span class="omo-decisions-card__status">' + escapeHtml(item.statusLabel || '') + '</span>'
                + '</span>'
                + '<span class="omo-decisions-card__summary-bottom">'
                    + (ownerName !== '' ? '<span class="omo-decisions-card__owner-name">' + escapeHtml(ownerName) + '</span>' : '')
                    + badgesHtml
                + '</span>'
            + '</span>'
            + '<span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>'
        + '</button>'
        + actionsHtml
        + '</div>'
        + '<div class="omo-decisions-card__content generic-accordion__content">'
            + (description !== '' ? '<p class="omo-decisions-card__description">' + escapeHtml(description) + '</p>' : '')
            + '<div class="omo-decisions-card__meta">' + metaBits.join('') + '</div>'
            + '<div class="omo-decisions-card__stats">'
                + '<div class="omo-decisions-card__stat"><strong>' + String(Number(item.proposalCount || 0)) + '</strong><span>' + escapeHtml(payload.text.proposalsLabel || 'Propositions') + '</span></div>'
                + '<div class="omo-decisions-card__stat"><strong>' + String(Number(item.participantCount || 0)) + '</strong><span>' + escapeHtml(payload.text.participantsLabel || 'Participants') + '</span></div>'
                + '<div class="omo-decisions-card__stat"><strong>' + String(Number(item.responseCount || 0)) + '</strong><span>' + escapeHtml(payload.text.responsesLabel || 'Réponses') + '</span></div>'
            + '</div>'
            + '<div class="omo-decisions-card__dates">'
                + (item.deadlineLabel ? '<div class="omo-decisions-card__date"><strong>' + escapeHtml(payload.text.deadlineLabel || 'Échéance') + '</strong><span>' + escapeHtml(item.deadlineLabel) + '</span></div>' : '')
                + (item.lastActivityLabel ? '<div class="omo-decisions-card__date"><strong>' + escapeHtml(payload.text.lastActivityLabel || 'Dernière activité') + '</strong><span>' + escapeHtml(item.lastActivityLabel) + '</span></div>' : '')
            + '</div>'
        + '</div>';

    return article;
}

function buildStateCardHtml(title, text, buttonLabel, buttonUrl) {
    let html = '<div class="omo-decisions__state-content">';
    if (title) {
        html += '<h3 class="omo-decisions__empty-title">' + escapeHtml(title) + '</h3>';
    }
    if (text) {
        html += '<p class="omo-decisions__empty-text">' + escapeHtml(text) + '</p>';
    }
    if (buttonLabel && buttonUrl) {
        html += '<button type="button" class="generic-action-button generic-action-button--main" data-open-url="' + escapeHtml(buttonUrl) + '">' + escapeHtml(buttonLabel) + '</button>';
    }
    html += '</div>';
    return html;
}

function renderNoResults() {
    return '<h3 class="omo-decisions__no-results-title">' + escapeHtml(payload.text.noResultsTitle || '') + '</h3>'
        + '<p class="omo-decisions__no-results-text">' + escapeHtml(payload.text.noResultsText || '') + '</p>';
}

function renderList() {
    syncAdvancedFiltersVisibility();
    resetStateContainer();
    setVisible(elements.list, false);

    if (elements.list) {
        elements.list.innerHTML = '';
        resetListPresentation();
    }

    if (!Array.isArray(payload.items)) {
        showStateContainer(
            '<div class="omo-decisions__state-message">' + escapeHtml(payload.text.error || 'Impossible de charger la liste pour le moment.') + '</div>',
            'default'
        );
        return;
    }

    const filteredItems = getFilteredItems();
    if (elements.count) {
        elements.count.textContent = formatCountLabel(filteredItems.length);
    }

    renderStatusTabs();
    syncDisplayControlsVisibility();
    syncDisplayButtons();

    if (payload.items.length === 0) {
        showStateContainer(buildStateCardHtml(
            payload.text.emptyTitle || '',
            payload.text.emptyText || '',
            payload.text.emptyCta || '',
            payload.newUrl || ''
        ), 'empty');
        return;
    }

    if (filteredItems.length === 0) {
        showStateContainer(renderNoResults(), 'default');
        return;
    }

    if (elements.list) {
        const fragment = state.density === 'compact'
            ? renderCompactList(filteredItems, state.sort)
            : renderDetailedList(filteredItems, state.sort);
        elements.list.appendChild(fragment);
        if (typeof window.initGenericComponents === 'function') {
            window.initGenericComponents(elements.list);
        }
        if (typeof window.syncGenericFileLists === 'function') {
            window.syncGenericFileLists(elements.list);
        }
    }
    setVisible(elements.list, true);
}

function resetFilters() {
    state.status = 'active';
    state.search = '';
    state.type = 'all';
    state.method = 'all';
    state.holon = 'all';
    state.filtersExpanded = false;

    if (elements.search) {
        elements.search.value = '';
    }
    if (elements.type) {
        elements.type.value = 'all';
    }
    if (elements.method) {
        elements.method.value = 'all';
    }
    if (elements.holon) {
        elements.holon.value = 'all';
    }
}

try {
    populateSelect(elements.type, payload.typeOptions);
    populateSelect(elements.method, payload.methodOptions);
    populateSelect(elements.holon, payload.holonOptions);
    renderList();
    window.setTimeout(openInitialDecisionFromPayload, 0);
} catch (error) {
    showStateContainer(
        '<div class="omo-decisions__state-message">' + escapeHtml(payload.text.error || 'Impossible de charger la liste pour le moment.') + '</div>',
        'default'
    );
}

if (elements.newButton) {
    elements.newButton.addEventListener('click', function () {
        openDecisionEditor(payload.newUrl || '', payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision');
    });
}

if (elements.editorDrawer) {
    elements.editorDrawer.querySelectorAll('[data-omo-decision-editor-close]').forEach(function (button) {
        button.addEventListener('click', omoDecisionCloseNestedEditorDrawer);
    });
}

if (elements.filtersToggle) {
    elements.filtersToggle.addEventListener('click', function () {
        state.filtersExpanded = !state.filtersExpanded;
        syncAdvancedFiltersVisibility();
    });
}

root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
    button.addEventListener('click', function () {
        const nextSort = omoDecisionsNormalizeSortPreference(
            button.getAttribute('data-omo-decisions-sort')
        );

        if (nextSort === state.sort) {
            return;
        }

        state.sort = nextSort;
        omoDecisionsWritePreferences({
            sort: state.sort,
            density: state.density
        });
        renderList();
    });
});

root.querySelectorAll('[data-omo-decisions-density]').forEach(function (button) {
    button.addEventListener('click', function () {
        const nextDensity = omoDecisionsNormalizeDensityPreference(
            button.getAttribute('data-omo-decisions-density')
        );

        if (nextDensity === state.density) {
            return;
        }

        state.density = nextDensity;
        omoDecisionsWritePreferences({
            sort: state.sort,
            density: state.density
        });
        renderList();
    });
});

window.omoDecisionOpenNestedDrawer = function (title, url, description) {
    openDecisionEditor(url, title, description);
};

window.omoRefreshDecisionIndex = function (options) {
    return omoDecisionRefreshIndex(options);
};

window.omoRefreshDecisionView = function (url, options) {
    const targetUrl = String(url || '').trim();
    if (!targetUrl) {
        return;
    }

    const settings = options && typeof options === 'object' ? options : {};
    const title = String(settings.title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision')).trim();
    const description = String(settings.description || '').trim();
    openDecisionEditor(targetUrl, title, description);
};

window.omoDecisionCloseNestedDrawer = function () {
    omoDecisionCloseNestedEditorDrawer();
};

if (elements.statusTabs) {
    elements.statusTabs.addEventListener('click', function (event) {
        const button = event.target.closest('[data-status]');
        if (!button) {
            return;
        }

        state.status = String(button.getAttribute('data-status') || 'all');
        renderList();
    });
}

if (elements.search) {
    elements.search.addEventListener('input', function () {
        state.search = String(elements.search.value || '');
        renderList();
    });
}

if (elements.type) {
    elements.type.addEventListener('change', function () {
        state.type = String(elements.type.value || 'all');
        renderList();
    });
}

if (elements.method) {
    elements.method.addEventListener('change', function () {
        state.method = String(elements.method.value || 'all');
        renderList();
    });
}

if (elements.holon) {
    elements.holon.addEventListener('change', function () {
        state.holon = String(elements.holon.value || 'all');
        renderList();
    });
}

if (elements.reset) {
    elements.reset.addEventListener('click', function () {
        resetFilters();
        renderList();
    });
}

root.addEventListener('click', function (event) {
    const accordionToggle = event.target.closest('[data-generic-accordion-toggle]');
    if (accordionToggle) {
        window.setTimeout(function () {
            const accordion = accordionToggle.closest('[data-generic-accordion]');
            if (!accordion) {
                return;
            }

            accordionToggle.setAttribute('aria-expanded', accordion.classList.contains('is-collapsed') ? 'false' : 'true');
        }, 0);
    }

    const button = event.target.closest('[data-open-url]');
    if (!button) {
        return;
    }

    const targetUrl = String(button.getAttribute('data-open-url') || '').trim();
    if (targetUrl === '') {
        return;
    }

    const targetTitle = String(button.getAttribute('data-open-title') || '').trim();
    const targetDescription = String(button.getAttribute('data-open-description') || '').trim();

    openDecisionEditor(
        targetUrl,
        targetTitle !== '' ? targetTitle : (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision'),
        targetDescription
    );
    event.preventDefault();
});
})();
</script>
