<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';
require_once BASE_PATH . '/common/topbar.php';
require_once BASE_PATH . '/common/choice/decision_cards.php';
require_once BASE_PATH . '/omo/translations.php';
require_once BASE_PATH . '/omo/topbar.php';
require_once BASE_PATH . '/omo/api/decision/modules/context.php';

use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\user;

if (!function_exists('choiceEscape')) {
    function choiceEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('choiceBuildParticipantDrawerUrl')) {
    function choiceBuildParticipantDrawerUrl(DecisionParticipant $participant, $intent = 'view')
    {
        $existingToken = trim((string)$participant->get('access_token'));
        $token = $participant->ensureAccessToken();
        if ($token === '') {
            return '';
        }

        if ($existingToken === '') {
            $saveResult = $participant->save();
            if (empty($saveResult['status'])) {
                return '';
            }
        }

        $path = DecisionParticipant::buildPublicAccessPathFromToken($token, $intent);
        return $path . '?embedded=1';
    }
}

if (!function_exists('choiceResolveScopeLabel')) {
    function choiceResolveScopeLabel($organizationName, $holonId, $holonName)
    {
        $organizationName = trim((string)$organizationName);
        $holonId = (int)$holonId;
        $holonName = trim((string)$holonName);

        if ($holonId <= 0) {
            return $organizationName;
        }

        $holon = new Holon();
        if ($holon->load($holonId)) {
            return trim((string)$holon->getTemplateLabel(true)) . ' ' . trim((string)$holon->getDisplayName());
        }

        return $holonName !== '' ? $holonName : $organizationName;
    }
}

if (!function_exists('choiceResolveOwnerLabel')) {
    function choiceResolveOwnerLabel($userId, $organizationId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return '';
        }

        $owner = new user();
        if (!$owner->load($userId)) {
            return '';
        }

        return trim((string)$owner->getScopedDisplayName((int)$organizationId));
    }
}

if (!function_exists('choiceBuildInitials')) {
    function choiceBuildInitials($label)
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
}

if (!function_exists('choiceResolveOwnerCard')) {
    function choiceResolveOwnerCard($userId, $organizationId)
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $card = [
            'displayName' => '',
            'photoUrl' => '',
            'initials' => 'P',
        ];

        if ($userId <= 0) {
            return $card;
        }

        $owner = new user();
        if (!$owner->load($userId)) {
            return $card;
        }

        $displayName = trim((string)$owner->getScopedDisplayName($organizationId));
        $photoUrl = trim((string)$owner->getScopedProfilePhotoUrl($organizationId));

        $card['displayName'] = $displayName;
        $card['photoUrl'] = $photoUrl;
        $card['initials'] = choiceBuildInitials($displayName !== '' ? $displayName : (string)$owner->getScopedEmail($organizationId));

        return $card;
    }
}

if (!function_exists('choiceToDateTime')) {
    function choiceToDateTime($value)
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
}

commonRestoreRememberedUser();

$sourceLang = [
    'choice.page.title' => [
        'text' => 'Choice',
        'context' => 'HTML title of the standalone decision module.',
    ],
    'choice.page.heading' => [
        'text' => 'Mes prises de décision',
        'context' => 'Main heading of the standalone decision module.',
    ],
    'choice.page.intro' => [
        'text' => 'Retrouvez ici tous les scrutins que vous avez créés ou pour lesquels vous êtes invité.',
        'context' => 'Intro text on the standalone decision module page.',
    ],
    'choice.page.connected_as' => [
        'text' => 'Connecté en tant que',
        'context' => 'Label showing the currently authenticated user.',
    ],
    'choice.empty.title' => [
        'text' => 'Aucun scrutin pour le moment',
        'context' => 'Empty state title when the user has no linked decisions.',
    ],
    'choice.empty.text' => [
        'text' => 'Les prises de décision que vous créez ou auxquelles vous êtes invité apparaîtront ici.',
        'context' => 'Empty state helper text when the user has no linked decisions.',
    ],
    'choice.card.owner' => [
        'text' => 'Créée par vous',
        'context' => 'Badge shown when the current user created the decision.',
    ],
    'choice.card.invited' => [
        'text' => 'Invité',
        'context' => 'Badge shown when the current user is invited to the decision.',
    ],
    'choice.card.organization' => [
        'text' => 'Organisation',
        'context' => 'Meta label for the linked organization.',
    ],
    'choice.card.scope' => [
        'text' => 'Contexte',
        'context' => 'Meta label for the linked holon or organization scope.',
    ],
    'choice.card.method' => [
        'text' => 'Méthode',
        'context' => 'Meta label for the decision method.',
    ],
    'choice.card.status' => [
        'text' => 'Statut',
        'context' => 'Meta label for the decision status.',
    ],
    'choice.card.owner_label' => [
        'text' => 'Organisateur',
        'context' => 'Meta label for the decision organizer.',
    ],
    'choice.card.updated' => [
        'text' => 'Mise à jour',
        'context' => 'Meta label for the last update date.',
    ],
    'choice.card.proposals' => [
        'text' => 'Propositions',
        'context' => 'Stat label for proposal count on a decision card.',
    ],
    'choice.card.participants' => [
        'text' => 'Participants',
        'context' => 'Stat label for participant count on a decision card.',
    ],
    'choice.card.responses' => [
        'text' => 'Réponses',
        'context' => 'Stat label for submitted response count on a decision card.',
    ],
    'choice.card.fallback_title' => [
        'text' => 'Prise de décision',
        'context' => 'Fallback title when a decision has no explicit title.',
    ],
    'choice.action.preview' => [
        'text' => 'Prévisualiser',
        'context' => 'Button opening the preview drawer for a decision.',
    ],
    'choice.action.participate' => [
        'text' => 'Participer',
        'context' => 'Button opening the participation drawer for a decision.',
    ],
    'choice.action.results' => [
        'text' => 'Voir les résultats',
        'context' => 'Button opening the results drawer for a finished decision.',
    ],
    'choice.action.manage' => [
        'text' => 'Gérer',
        'context' => 'Button opening the management drawer for a decision.',
    ],
    'choice.drawer.default_title' => [
        'text' => 'Prises de décision',
        'context' => 'Default drawer title in the standalone decision module.',
    ],
    'choice.drawer.close' => [
        'text' => 'Fermer',
        'context' => 'Close label for drawer and modal controls.',
    ],
    'choice.drawer.loading' => [
        'text' => 'Chargement...',
        'context' => 'Loading text while fetching remote drawer or modal content.',
    ],
    'choice.drawer.error' => [
        'text' => 'Erreur de chargement',
        'context' => 'Error text shown when remote content could not be loaded.',
    ],
    'choice.status.draft' => [
        'text' => 'En préparation',
        'context' => 'Status label for a draft decision.',
    ],
    'choice.status.scheduled' => [
        'text' => 'Planifiée',
        'context' => 'Status label for a scheduled decision.',
    ],
    'choice.status.consultation' => [
        'text' => 'En consultation',
        'context' => 'Status label for a consultation phase.',
    ],
    'choice.status.evaluation' => [
        'text' => 'En vote',
        'context' => 'Status label for an evaluation phase.',
    ],
    'choice.status.results' => [
        'text' => 'Résultats',
        'context' => 'Status label for a results phase.',
    ],
    'choice.status.archived' => [
        'text' => 'Archivée',
        'context' => 'Status label for an archived decision.',
    ],
    'choice.method.simple_vote' => [
        'text' => 'Vote simple',
        'context' => 'Method label for simple vote.',
    ],
    'choice.method.majority_judgment' => [
        'text' => 'Jugement majoritaire',
        'context' => 'Method label for majority judgment.',
    ],
    'choice.method.consent' => [
        'text' => 'Consentement',
        'context' => 'Method label for consent.',
    ],
];

$lang = omoLoadTranslationBundle('choice_index', $sourceLang);
$escape = 'choiceEscape';

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    commonRenderMagicLoginPage([
        'title' => t('choice.page.title', [], $lang, $sourceLang),
        'appName' => 'Choice',
        'intro' => 'Connectez-vous pour retrouver toutes les prises de decision qui vous concernent.',
        'returnTo' => '/choice/',
    ]);
}

$currentUser = new user();
if (!$currentUser->load($currentUserId)) {
    commonRenderMagicLoginPage([
        'title' => t('choice.page.title', [], $lang, $sourceLang),
        'appName' => 'Choice',
        'intro' => 'Connectez-vous pour retrouver toutes les prises de decision qui vous concernent.',
        'returnTo' => '/choice/',
    ]);
}

$userEmail = trim(mb_strtolower((string)$currentUser->get('email'), 'UTF-8'));
$rows = DecisionProcess::fetchRelevantRowsForUser($currentUserId, $userEmail);
$statusLabels = [
    DecisionProcess::STATUS_DRAFT => t('choice.status.draft', [], $lang, $sourceLang),
    DecisionProcess::STATUS_SCHEDULED => t('choice.status.scheduled', [], $lang, $sourceLang),
    DecisionProcess::STATUS_CONSULTATION => t('choice.status.consultation', [], $lang, $sourceLang),
    DecisionProcess::STATUS_EVALUATION => t('choice.status.evaluation', [], $lang, $sourceLang),
    DecisionProcess::STATUS_RESULTS => t('choice.status.results', [], $lang, $sourceLang),
    DecisionProcess::STATUS_ARCHIVED => t('choice.status.archived', [], $lang, $sourceLang),
];
$methodLabels = [
    DecisionProcess::METHOD_SIMPLE_VOTE => t('choice.method.simple_vote', [], $lang, $sourceLang),
    DecisionProcess::METHOD_MAJORITY_JUDGMENT => t('choice.method.majority_judgment', [], $lang, $sourceLang),
    DecisionProcess::METHOD_CONSENT => t('choice.method.consent', [], $lang, $sourceLang),
];
$topbarOptions = omoBuildTopbarOptions(
    ['isValid' => false],
    [
        'variant' => 'embedded',
        'appKey' => 'choice',
        'appLabel' => 'Choice',
        'brandHref' => '/choice/',
        'brandLabel' => 'Choice',
        'brandAlt' => 'Choice',
        'logoutReturnTo' => '/choice/',
        'search' => [
            'enabled' => false,
        ],
        'bugReport' => [
            'enabled' => false,
        ],
    ]
);

$decisionEntries = [];
foreach ($rows as $row) {
    if (!is_array($row) || empty($row['id'])) {
        continue;
    }

    $decision = new DecisionProcess();
    $decision->loadFromArray($row);
    $decision->setId((int)$row['id']);

    $organizationId = (int)($row['IDorganization'] ?? 0);
    $holonId = (int)($row['IDholon'] ?? 0);
    $method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
    $status = DecisionProcess::normalizeStatus($decision->get('status'));
    $participant = null;
    $participantId = (int)($row['participant_id'] ?? 0);
    if ($participantId > 0) {
        $participant = new DecisionParticipant();
        if (!$participant->load($participantId)) {
            $participant = null;
        }
    }

    $participantStatus = $participant instanceof DecisionParticipant
        ? DecisionParticipant::normalizeStatus($participant->get('status'))
        : '';
    $hasParticipation = $participant instanceof DecisionParticipant
        && (int)$participant->get('active') === 1
        && !in_array($participantStatus, [
            DecisionParticipant::STATUS_DECLINED,
            DecisionParticipant::STATUS_REVOKED,
        ], true);

    $isOwnerParticipant = $participant instanceof DecisionParticipant
        && (int)$participant->get('active') === 1
        && DecisionParticipant::normalizeRole($participant->get('role')) === DecisionParticipant::ROLE_OWNER;
    $isOwner = ((int)$decision->get('IDuser') === $currentUserId) || $isOwnerParticipant;
    $canParticipate = ($isOwner || $hasParticipation) && $decision->isParticipationOpen();
    $organizationName = trim((string)($row['organization_name'] ?? ''));
    $scopeLabel = choiceResolveScopeLabel($organizationName, $holonId, (string)($row['holon_name'] ?? ''));
    $ownerLabel = choiceResolveOwnerLabel((int)$decision->get('IDuser'), $organizationId);
    $ownerCard = choiceResolveOwnerCard((int)$decision->get('IDuser'), $organizationId);

    $activityCandidates = [
        choiceToDateTime($decision->get('updated_at')),
        choiceToDateTime($row['proposals_updated_at'] ?? null),
        choiceToDateTime($row['participants_updated_at'] ?? null),
        choiceToDateTime($row['responses_updated_at'] ?? null),
        choiceToDateTime($row['responses_submitted_at'] ?? null),
    ];
    $lastActivity = null;
    foreach ($activityCandidates as $candidate) {
        if (!$candidate instanceof DateTimeInterface) {
            continue;
        }
        if (!$lastActivity || $candidate > $lastActivity) {
            $lastActivity = $candidate;
        }
    }

    $previewUrl = '';
    $participateUrl = '';
    if ($participant instanceof DecisionParticipant) {
        $previewUrl = choiceBuildParticipantDrawerUrl($participant, 'view');
        $participateUrl = choiceBuildParticipantDrawerUrl($participant, 'participate');
    }

    if ($previewUrl === '') {
        $previewUrl = omoDecisionBuildParticipationPreviewUrl(
            $organizationId,
            $holonId,
            (int)$decision->getId(),
            $method,
            'view',
            true
        );
    }

    if ($participateUrl === '') {
        $participateUrl = omoDecisionBuildParticipationPreviewUrl(
            $organizationId,
            $holonId,
            (int)$decision->getId(),
            $method,
            'participate',
            true
        );
    }

    $manageUrl = '';
    if ($isOwner) {
        $manageUrl = omoDecisionBuildEditorUrl(
            $organizationId,
            $holonId,
            (int)$decision->getId(),
            $method,
            'manage'
        );
    }

    $actions = [];
    if (in_array($status, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true)) {
        $actions[] = [
            'label' => t('choice.action.results', [], $lang, $sourceLang),
            'url' => $previewUrl,
            'variant' => 'main',
            'title' => trim((string)$decision->get('title')) !== '' ? trim((string)$decision->get('title')) : t('choice.drawer.default_title', [], $lang, $sourceLang),
        ];
    } else {
        $actions[] = [
            'label' => t('choice.action.preview', [], $lang, $sourceLang),
            'url' => $previewUrl,
            'variant' => 'secondary',
            'title' => trim((string)$decision->get('title')) !== '' ? trim((string)$decision->get('title')) : t('choice.drawer.default_title', [], $lang, $sourceLang),
        ];
    }

    if ($canParticipate) {
        $actions[] = [
            'label' => t('choice.action.participate', [], $lang, $sourceLang),
            'url' => $participateUrl,
            'variant' => 'main',
            'title' => trim((string)$decision->get('title')) !== '' ? trim((string)$decision->get('title')) : t('choice.drawer.default_title', [], $lang, $sourceLang),
        ];
    }

    if ($manageUrl !== '') {
        $actions[] = [
            'label' => t('choice.action.manage', [], $lang, $sourceLang),
            'url' => $manageUrl,
            'variant' => 'secondary',
            'title' => trim((string)$decision->get('title')) !== '' ? trim((string)$decision->get('title')) : t('choice.drawer.default_title', [], $lang, $sourceLang),
        ];
    }

    $decisionEntries[] = [
        'id' => (int)$decision->getId(),
        'title' => trim((string)$decision->get('title')),
        'description' => trim((string)$decision->get('description')),
        'organization_name' => $organizationName,
        'scope_label' => $scopeLabel,
        'method_label' => $methodLabels[$method] ?? $method,
        'status_label' => $statusLabels[$status] ?? $status,
        'status_key' => $status,
        'owner_label' => $ownerLabel,
        'owner' => $ownerCard,
        'updated_at' => $lastActivity instanceof DateTimeInterface
            ? $lastActivity->format('d.m.Y H:i')
            : '',
        'badges' => [
            $isOwner
                ? t('choice.card.owner', [], $lang, $sourceLang)
                : t('choice.card.invited', [], $lang, $sourceLang),
        ],
        'proposal_count' => (int)($row['proposal_count'] ?? 0),
        'participant_count' => (int)($row['participant_count'] ?? 0),
        'response_count' => (int)($row['response_count'] ?? 0),
        'meta_items' => [
            [
                'label' => t('choice.card.organization', [], $lang, $sourceLang),
                'value' => $organizationName !== '' ? $organizationName : '-',
            ],
            [
                'label' => t('choice.card.scope', [], $lang, $sourceLang),
                'value' => $scopeLabel !== '' ? $scopeLabel : '-',
            ],
            [
                'label' => t('choice.card.method', [], $lang, $sourceLang),
                'value' => $methodLabels[$method] ?? $method,
            ],
        ],
        'stats' => [
            [
                'value' => (string)((int)($row['proposal_count'] ?? 0)),
                'label' => t('choice.card.proposals', [], $lang, $sourceLang),
            ],
            [
                'value' => (string)((int)($row['participant_count'] ?? 0)),
                'label' => t('choice.card.participants', [], $lang, $sourceLang),
            ],
            [
                'value' => (string)((int)($row['response_count'] ?? 0)),
                'label' => t('choice.card.responses', [], $lang, $sourceLang),
            ],
        ],
        'date_items' => $lastActivity instanceof DateTimeInterface
            ? [[
                'label' => t('choice.card.updated', [], $lang, $sourceLang),
                'value' => $lastActivity->format('d.m.Y H:i'),
            ]]
            : [],
        'actions' => $actions,
    ];
}

$currentUserLabel = trim((string)$currentUser->getScopedDisplayName());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape(t('choice.page.title', [], $lang, $sourceLang)) ?></title>
    <link href="/shared_css.css" rel="stylesheet">
    <link href="/common/choice/decision_cards.css" rel="stylesheet">
    <script src="/shared_functions.js"></script>
    <script>
        if (typeof sharedApplyDocumentTheme === 'function') {
            sharedApplyDocumentTheme();
        }
    </script>
    <style>
        html {
            height: auto;
            min-height: 100%;
            overflow-y: auto;
        }

        :root {
            --choice-accent-soft: color-mix(in srgb, var(--color-primary, #0f766e) 10%, var(--color-surface, #ffffff));
            --choice-border-strong: color-mix(in srgb, var(--color-primary, #0f766e) 18%, var(--color-border, #dbe3ef));
            --choice-page-bg:
                radial-gradient(circle at top right, color-mix(in srgb, var(--color-primary, #0f766e) 14%, transparent), transparent 28%),
                linear-gradient(180deg, var(--color-surface-alt, #f8fafc) 0%, color-mix(in srgb, var(--color-surface-alt, #eef2f7) 72%, var(--color-surface, #ffffff)) 100%);
            --choice-text-main: var(--color-text, #0f172a);
            --choice-text-muted: var(--color-text-light, #475569);
            --choice-card-surface: color-mix(in srgb, var(--color-surface, #ffffff) 94%, transparent);
            --choice-chip-surface: color-mix(in srgb, var(--color-surface, #ffffff) 88%, transparent);
        }

        body.choice-page {
            margin: 0;
            height: auto;
            min-height: 100vh;
            overflow-y: auto;
            background: var(--choice-page-bg);
            color: var(--choice-text-main);
            font-family: Arial, Helvetica, sans-serif;
        }

        .choice-page > .common-topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1100;
        }

        .choice-page .common-topbar-drawer__panel {
            width: min(1120px, calc(100vw - 20px));
            border-left: 1px solid var(--topbar-panel-border, var(--color-border, #dbe3ef));
        }

        .choice-page .common-topbar-drawer__body {
            overscroll-behavior: auto;
        }

        .choice-shell {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: calc(var(--topbar-height, 48px) + 28px) 0 40px;
            display: grid;
            gap: 18px;
        }

        .choice-hero {
            display: grid;
            gap: 8px;
        }

        .choice-hero__kicker {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-primary, #0f766e);
        }

        .choice-hero h1 {
            margin: 0;
            font-size: clamp(30px, 5vw, 48px);
            line-height: 1.02;
        }

        .choice-hero p {
            margin: 0;
            color: var(--choice-text-muted);
            line-height: 1.6;
            max-width: 760px;
        }

        .choice-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--choice-border-strong);
            background: var(--choice-chip-surface);
            color: var(--choice-text-main);
            font-weight: 600;
        }

        .omo-decisions-card {
            border-color: var(--choice-border-strong);
            background: var(--choice-card-surface);
        }

        .omo-decisions-card .generic-accordion__toggle {
            margin-left: auto;
            color: var(--choice-text-muted);
        }

        .choice-empty {
            text-align: center;
            padding: 36px 24px;
        }

        .choice-empty h2 {
            margin: 0;
            font-size: 28px;
        }

        .choice-empty p {
            margin: 0;
            color: var(--choice-text-muted);
            line-height: 1.6;
        }

        @media (max-width: 860px) {
            .omo-decisions-card__actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body class="choice-page">
    <?php commonRenderTopbar($topbarOptions); ?>
    <main class="choice-shell">
        <section class="generic-hero-panel accent choice-hero">
            <span class="choice-hero__kicker">Choice</span>
            <h1><?= $escape(t('choice.page.heading', [], $lang, $sourceLang)) ?></h1>
            <p><?= $escape(t('choice.page.intro', [], $lang, $sourceLang)) ?></p>
            <span class="choice-user-chip">
                <strong><?= $escape(t('choice.page.connected_as', [], $lang, $sourceLang)) ?></strong>
                <span><?= $escape($currentUserLabel !== '' ? $currentUserLabel : (string)$currentUser->get('email')) ?></span>
            </span>
        </section>

        <?php if (count($decisionEntries) === 0): ?>
        <section class="generic-section generic-section--stack choice-empty">
            <h2><?= $escape(t('choice.empty.title', [], $lang, $sourceLang)) ?></h2>
            <p><?= $escape(t('choice.empty.text', [], $lang, $sourceLang)) ?></p>
        </section>
        <?php else: ?>
        <section class="omo-decisions__list">
            <?php foreach ($decisionEntries as $entry): ?>
            <?= commonChoiceRenderDecisionCard([
                'title' => $entry['title'],
                'description' => $entry['description'],
                'statusLabel' => $entry['status_label'],
                'owner' => [
                    'displayName' => $entry['owner_label'],
                    'initials' => $entry['owner']['initials'] ?? 'P',
                    'photoUrl' => $entry['owner']['photoUrl'] ?? '',
                ],
                'badges' => $entry['badges'],
                'actions' => $entry['actions'],
                'metaItems' => $entry['meta_items'],
                'stats' => $entry['stats'],
                'dateItems' => $entry['date_items'],
            ], [
                'escape' => $escape,
                'fallbackTitle' => t('choice.card.fallback_title', [], $lang, $sourceLang),
                'openUrlAttribute' => 'data-open-url',
                'openTitleAttribute' => 'data-open-title',
                'preserveDescriptionBreaks' => true,
            ]) ?>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
    </main>
    <script>
        (function () {
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(document);
            }

            window.omoRefreshDecisionView = function (url, options) {
                var targetUrl = String(url || '').trim();
                if (targetUrl === '' || typeof window.commonTopbarOpenDrawer !== 'function') {
                    if (targetUrl !== '') {
                        window.location.href = targetUrl;
                    }
                    return;
                }

                var settings = options && typeof options === 'object' ? options : {};
                var title = String(
                    settings.title
                    || <?= json_encode(t('choice.drawer.default_title', [], $lang, $sourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                );
                window.commonTopbarOpenDrawer(title, targetUrl, 'fetch');
            };

            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-open-url]');
                if (!trigger) {
                    return;
                }

                event.preventDefault();
                var url = String(trigger.getAttribute('data-open-url') || '');
                if (url === '' || typeof window.commonTopbarOpenDrawer !== 'function') {
                    return;
                }

                var title = String(
                    trigger.getAttribute('data-choice-open-title')
                    || trigger.textContent
                    || <?= json_encode(t('choice.drawer.default_title', [], $lang, $sourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                );
                window.commonTopbarOpenDrawer(title, url, 'fetch');
            });
        }());
    </script>
</body>
</html>
