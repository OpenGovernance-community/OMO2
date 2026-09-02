<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/params/shared.php';

use dbObject\DecisionProcess;
use dbObject\Holon;
use dbObject\ObjectVisibility;
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
        'text' => 'Décisions',
        'context' => 'Main title of the decisions drawer entry screen.',
    ],
    'decisions.index.description' => [
        'text' => 'Centralisez ici les consultations et prises de décision accessibles dans votre organisation, puis ouvrez le bon flux selon leur statut.',
        'context' => 'Short description shown under the decisions module title.',
    ],
    'decisions.index.new' => [
        'text' => 'Nouveau scrutin',
        'context' => 'Primary call to action opening the decision creation screen.',
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
    'decisions.index.scope.contextual' => [
        'text' => 'Local',
        'context' => 'Label used to show only decisions from the current holon context.',
    ],
    'decisions.index.scope.children' => [
        'text' => 'Enfants directs',
        'context' => 'Label used to show decisions from the current holon and its direct children.',
    ],
    'decisions.index.scope.descendants' => [
        'text' => 'Descendants',
        'context' => 'Label used to show decisions from the current holon and its descendants.',
    ],
    'decisions.index.view_filter.aria' => [
        'text' => 'Filtres des prises de décision',
        'context' => 'Accessible label for the compact decision filters.',
    ],
    'decisions.index.view_filter.scope' => [
        'text' => 'Contexte',
        'context' => 'Heading for decision scope choices.',
    ],
    'decisions.index.view_filter.status' => [
        'text' => 'État',
        'context' => 'Heading for decision status choices.',
    ],
    'decisions.index.view_filter.presentation' => [
        'text' => 'Présentation',
        'context' => 'Heading for decision sort and density choices.',
    ],
    'decisions.index.view_filter.apply' => [
        'text' => 'Appliquer',
        'context' => 'Button applying temporary decision filters.',
    ],
    'decisions.index.view_filter.save' => [
        'text' => 'Enregistrer la vue',
        'context' => 'Button saving the decision view for current context.',
    ],
    'decisions.index.view_filter.more_actions' => [
        'text' => 'Autres options de vue',
        'context' => 'Accessible label for additional decision view preference actions.',
    ],
    'decisions.index.view_filter.apply_everywhere' => [
        'text' => 'Appliquer partout',
        'context' => 'Action setting the current decision view as the default and clearing specific views.',
    ],
    'decisions.index.view_filter.set_default' => [
        'text' => 'Définir comme vue par défaut',
        'context' => 'Action saving the current decision view as the default view.',
    ],
    'decisions.index.view_filter.restore_default' => [
        'text' => 'Restaurer la vue par défaut',
        'context' => 'Action removing the current holon specific decision view.',
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
        'text' => 'En élaboration',
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
        'text' => 'Indicative',
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
        'text' => 'Tri des prises de décision',
        'context' => 'Accessible label for the sort segmented control.',
    ],
    'decisions.index.controls.sort.time' => [
        'text' => 'Temporel',
        'context' => 'Label for time-based sorting in the decisions list.',
    ],
    'decisions.index.controls.sort.alpha' => [
        'text' => 'Alphabétique',
        'context' => 'Label for alphabetical sorting in the decisions list.',
    ],
    'decisions.index.controls.density.aria' => [
        'text' => "Densité d'affichage des prises de décision",
        'context' => 'Accessible label for the display density segmented control.',
    ],
    'decisions.index.controls.density.detail' => [
        'text' => 'Détail',
        'context' => 'Label for the detailed decisions list density.',
    ],
    'decisions.index.controls.density.compact' => [
        'text' => 'Compact',
        'context' => 'Label for the compact decisions list density.',
    ],
    'decisions.index.compact.header.name' => [
        'text' => 'Décision',
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
        'text' => 'Activité',
        'context' => 'Column header for the compact decisions list activity column.',
    ],
    'decisions.index.group.today' => [
        'text' => "Aujourd'hui",
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
        'text' => 'La semaine passée',
        'context' => 'Relative date group title for decisions updated last week.',
    ],
    'decisions.index.group.this_month' => [
        'text' => 'Ce mois',
        'context' => 'Relative date group title for decisions updated this month.',
    ],
    'decisions.index.group.last_month' => [
        'text' => 'Le mois passé',
        'context' => 'Relative date group title for decisions updated last month.',
    ],
    'decisions.index.group.this_year' => [
        'text' => 'Cette année',
        'context' => 'Relative date group title for decisions updated this year.',
    ],
    'decisions.index.group.last_year' => [
        'text' => "L'année passée",
        'context' => 'Relative date group title for decisions updated last year.',
    ],
    'decisions.index.group.earlier' => [
        'text' => 'Précédemment',
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
        'text' => 'Sans structure liée',
        'context' => 'Fallback label when a decision has no associated structure.',
    ],
    'decisions.index.loading' => [
        'text' => 'Chargement des décisions…',
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
    'decisions.index.action.archive' => [
        'text' => 'Archiver',
        'context' => 'Manager action used to archive a decision that already has votes.',
    ],
    'decisions.index.action.export' => [
        'text' => 'Export',
        'context' => 'Menu action used to open the export picker for one decision.',
    ],
    'decisions.index.action.move' => [
        'text' => 'Déplacer',
        'context' => 'Menu action used to move a decision to another holon.',
    ],
    'decisions.index.move.modal_title' => [
        'text' => 'Déplacer la prise de décision',
        'context' => 'Title of the decision move dialog.',
    ],
    'decisions.index.action.participant_qr_codes' => [
        'text' => 'Imprimer les codes QR',
        'context' => 'Menu action used to open a printable sheet of participant QR codes for one decision.',
    ],
    'decisions.index.action.delete' => [
        'text' => 'Supprimer',
        'context' => 'Manager action used to delete a decision that has no submitted votes yet.',
    ],
    'decisions.index.action.more' => [
        'text' => '...',
        'context' => 'Label of the secondary menu button shown on detailed decision cards.',
    ],
    'decisions.index.action.more_aria' => [
        'text' => "Plus d'actions pour cette prise de décision",
        'context' => 'Accessible label for the secondary action menu button shown on detailed cards.',
    ],
    'decisions.index.action.confirm_archive' => [
        'text' => 'Archiver cette prise de décision ?',
        'context' => 'Confirmation message before archiving a decision from the list.',
    ],
    'decisions.index.action.confirm_delete' => [
        'text' => 'Supprimer définitivement cette prise de décision et ses éléments liés ?',
        'context' => 'Confirmation message before deleting a decision from the list.',
    ],
    'decisions.index.action.error_update' => [
        'text' => 'Impossible de mettre à jour cette prise de décision pour le moment.',
        'context' => 'Fallback error message shown when a decision archive or delete action fails.',
    ],
    'decisions.index.export.modal_title' => [
        'text' => 'Exporter ce scrutin',
        'context' => 'Modal title shown when choosing one export format from the decision list.',
    ],
    'decisions.index.export.modal_intro' => [
        'text' => "Choisissez le format d'export adapté à ce mode de prise de décision.",
        'context' => 'Intro text shown inside the export picker modal.',
    ],
    'decisions.index.export.format.csv' => [
        'text' => 'CSV',
        'context' => 'Label for the CSV export format.',
    ],
    'decisions.index.export.format.csv_description' => [
        'text' => 'Tableau enrichi avec type, bloc, question, détails et résultats.',
        'context' => 'Description of the CSV export format.',
    ],
    'decisions.index.export.format.json' => [
        'text' => 'JSON',
        'context' => 'Label for the JSON export format.',
    ],
    'decisions.index.export.format.json_description' => [
        'text' => 'Blueprint du scrutin et résultats structurés, sans dump complet.',
        'context' => 'Description of the JSON export format.',
    ],
    'decisions.index.export.format.xml' => [
        'text' => 'XML',
        'context' => 'Label for the XML export format.',
    ],
    'decisions.index.export.format.xml_description' => [
        'text' => 'Même contenu structuré que le JSON, dans un format XML.',
        'context' => 'Description of the XML export format.',
    ],
    'decisions.index.export.format.pdf' => [
        'text' => 'PDF',
        'context' => 'Label for the PDF export format.',
    ],
    'decisions.index.export.format.pdf_description' => [
        'text' => 'Version de présentation préparée pour plus tard.',
        'context' => 'Description of the PDF export format.',
    ],
    'decisions.index.export.format.coming_soon' => [
        'text' => 'Bientôt disponible',
        'context' => 'Label shown for unavailable export formats.',
    ],
    'decisions.index.export.open' => [
        'text' => 'Télécharger',
        'context' => 'Button label used to trigger the selected export.',
    ],
    'decisions.index.action.open_editor_title' => [
        'text' => 'Décisions',
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
$requestedDecisionScope = $_GET['decision_scope'] ?? 'contextual';
$initialOpenDecisionId = isset($_GET['open_decision_id']) ? (int)$_GET['open_decision_id'] : 0;
$initialOpenDecisionMode = trim((string)($_GET['open_decision_mode'] ?? ''));
$initialOpenDecisionMode = in_array($initialOpenDecisionMode, ['view', 'manage', 'participate'], true)
    ? $initialOpenDecisionMode
    : 'default';

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
$rootHolon = $organization->getEnabledStructuralRootHolon();
$applicationViewPreferences = omoApplicationViewPreferencesGetContext(
    'decision',
    $organization,
    $currentContextHolon instanceof Holon ? $currentContextHolon : null,
    $currentUserId
);
$requestedDecisionScope = omoApplicationViewPreferencesGetInitialValue(
    $applicationViewPreferences,
    'decision_scope',
    'scope',
    'contextual'
);
$allowedContextHolonIds = $currentContextHolon
    ? [(int)$currentContextHolon->getId() => true]
    : [];
$canToggleDecisionScope = $rootHolon !== null;
$availableDecisionScopes = omoApiGetAvailableContextScopes($canToggleDecisionScope, $currentContextHolon, $rootHolon);
$decisionScope = omoApiNormalizeContextScope($requestedDecisionScope, $availableDecisionScopes);
$decisionScopeActiveIndex = omoApiResolveContextScopeIndex($decisionScope, $availableDecisionScopes);
$allowedDescendantHolonIds = omoApiGetDescendantHolonIdMap($currentContextHolon);
$allowedDirectChildHolonIds = omoApiGetDirectChildScopeHolonIdMap($currentContextHolon);
$normalizedCurrentHolonId = omoDecisionNormalizeContextHolonId($organization, $currentHolonId);
$isNonRootHolonContext = $currentContextHolon instanceof Holon
    && $rootHolon instanceof Holon
    && (int)$currentContextHolon->getId() !== (int)$rootHolon->getId();

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
$decisionVisibilityIconMap = [
    ObjectVisibility::TYPE_EVERYONE => '/omo/assets/images/documents/visibility/everyone.png',
    ObjectVisibility::TYPE_ORGANIZATION => '/omo/assets/images/documents/visibility/organization.png',
    ObjectVisibility::TYPE_CIRCLE => '/omo/assets/images/documents/visibility/circle.png',
    ObjectVisibility::TYPE_ROLE => '/omo/assets/images/documents/visibility/role.png',
    ObjectVisibility::TYPE_SELF => '/omo/assets/images/documents/visibility/me.png',
];
$resolveDecisionVisibilityIconUrl = static function ($visibilityType) use ($decisionVisibilityIconMap): string {
    $normalizedVisibilityType = DecisionProcess::normalizeVisibilityType($visibilityType);

    return (string)($decisionVisibilityIconMap[$normalizedVisibilityType] ?? $decisionVisibilityIconMap[DecisionProcess::getDefaultVisibilityType()]);
};
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
$canUseGovernance = omoDecisionParamsCanUseGovernance($organization);
$canCreateDecision = $currentContextHolon
    ? $currentContextHolon->isAllowed('CAN_CREATE_DECISION')
    : $organizationCanEdit;
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
    $decision->hydrateFromDatabaseRow($row, true);

    $decisionId = (int)$decision->getId();
    if ($decisionId <= 0) {
        continue;
    }

    if (!$canUseGovernance && $decision->isGovernanceWorkflow()) {
        continue;
    }

    $holonId = (int)$decision->get('IDholon');
    if ($isNonRootHolonContext && in_array($decisionScope, ['contextual', 'children', 'descendants'], true) && $holonId <= 0) {
        continue;
    }

    if ($decisionScope === 'contextual' && $currentContextHolon && $holonId > 0 && !isset($allowedContextHolonIds[$holonId])) {
        continue;
    }

    if ($decisionScope === 'descendants' && $currentContextHolon && $holonId > 0 && !isset($allowedDescendantHolonIds[$holonId])) {
        continue;
    }

    if ($decisionScope === 'children') {
        if ($isNonRootHolonContext && $holonId <= 0) {
            continue;
        }
        if ($holonId > 0 && !isset($allowedDirectChildHolonIds[$holonId])) {
            continue;
        }
    }

    $status = DecisionProcess::normalizeStatus($decision->get('status'));
    $decisionType = DecisionProcess::normalizeDecisionType($decision->get('decision_type'));
    $method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
    $visibility = $decision->getVisibilityDisplayData($currentOrganizationId);
    $visibilityAccess = $decision->currentViewerCanAccessVisibility($currentOrganizationId);

    $isOwner = $currentUserId > 0 && (int)$decision->get('IDuser') === $currentUserId;
    $hasUserParticipation = (int)($row['has_user_participation'] ?? 0) > 0;
    $hasEmailParticipation = !$hasUserParticipation && (int)($row['has_email_participation'] ?? 0) > 0;

    $canManage = $isOwner;

    $canView = $canManage
        || $isOwner
        || $hasUserParticipation
        || $hasEmailParticipation
        || ($status !== DecisionProcess::STATUS_DRAFT && $visibilityAccess);
    if (!$canView) {
        continue;
    }

    $consultationStarted = $decision->hasConsultationStarted();
    $consultationOpen = $decision->isConsultationOpen();
    $canParticipate = $decision->isParticipationInterfaceOpen()
        && ($isOwner || $hasUserParticipation || $hasEmailParticipation);

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
    $actionUrl = omoDecisionBuildActionUrl();
    $hasSubmittedResponses = (int)($row['response_count'] ?? 0) > 0
        || omoDecisionsIndexToDateTime($row['responses_submitted_at'] ?? '') instanceof DateTimeInterface;

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
            'mode' => 'view',
            'variant' => 'main',
        ];
    } elseif ($consultationOpen && $canParticipate) {
        $actions[] = [
            'label' => t('decisions.index.action.participate', [], $lang, $sourceLang),
            'url' => $participateUrl,
            'mode' => 'participate',
            'variant' => 'main',
        ];

        if ($canManage) {
            $actions[] = [
                'label' => t('decisions.index.action.manage', [], $lang, $sourceLang),
                'url' => $manageUrl,
                'mode' => 'manage',
                'variant' => 'secondary',
            ];
        }
    } elseif ($consultationStarted) {
        $actions[] = [
            'label' => t('decisions.index.action.view', [], $lang, $sourceLang),
            'url' => $viewUrl,
            'mode' => 'view',
            'variant' => 'secondary',
        ];

        if ($canManage) {
            $actions[] = [
                'label' => t('decisions.index.action.manage', [], $lang, $sourceLang),
                'url' => $manageUrl,
                'mode' => 'manage',
                'variant' => 'secondary',
            ];
        }

        if ($canParticipate) {
            $actions[] = [
                'label' => t('decisions.index.action.participate', [], $lang, $sourceLang),
                'url' => $participateUrl,
                'mode' => 'participate',
                'variant' => 'main',
            ];
        }
    } else {
        if ($canManage) {
            $actions[] = [
                'label' => omoDecisionsIndexResolvePrimaryActionLabel($status, $canManage, $lang, $sourceLang),
                'url' => $manageUrl,
                'mode' => 'manage',
                'variant' => 'main',
            ];
        } else {
            $actions[] = [
                'label' => t('decisions.index.action.view', [], $lang, $sourceLang),
                'url' => $viewUrl,
                'mode' => 'view',
                'variant' => 'secondary',
            ];
        }
    }

    $menuActions = [];
    if ($canManage) {
        $menuActions[] = [
            'label' => t('decisions.index.action.move', [], $lang, $sourceLang),
            'behavior' => 'modal',
            'url' => '/omo/api/decision/move.php?id=' . $decisionId,
            'title' => t('decisions.index.move.modal_title', [], $lang, $sourceLang),
        ];

        $menuActions[] = [
            'label' => t('decisions.index.action.export', [], $lang, $sourceLang),
            'behavior' => 'export',
            'exportUrl' => omoDecisionBuildExportUrl(
                $currentOrganizationId,
                $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
                $decisionId,
                $method
            ),
            'exportFormats' => [
                [
                    'key' => 'csv',
                    'label' => t('decisions.index.export.format.csv', [], $lang, $sourceLang),
                    'description' => t('decisions.index.export.format.csv_description', [], $lang, $sourceLang),
                    'available' => true,
                ],
                [
                    'key' => 'json',
                    'label' => t('decisions.index.export.format.json', [], $lang, $sourceLang),
                    'description' => t('decisions.index.export.format.json_description', [], $lang, $sourceLang),
                    'available' => true,
                ],
                [
                    'key' => 'xml',
                    'label' => t('decisions.index.export.format.xml', [], $lang, $sourceLang),
                    'description' => t('decisions.index.export.format.xml_description', [], $lang, $sourceLang),
                    'available' => true,
                ],
                [
                    'key' => 'pdf',
                    'label' => t('decisions.index.export.format.pdf', [], $lang, $sourceLang),
                    'description' => t('decisions.index.export.format.pdf_description', [], $lang, $sourceLang),
                    'available' => false,
                ],
            ],
        ];

        $canOpenParticipantQrSheet = !in_array($status, [
                DecisionProcess::STATUS_DRAFT,
                DecisionProcess::STATUS_RESULTS,
                DecisionProcess::STATUS_ARCHIVED,
            ], true);

        if ($canOpenParticipantQrSheet) {
            $menuActions[] = [
                'label' => t('decisions.index.action.participant_qr_codes', [], $lang, $sourceLang),
                'behavior' => 'window',
                'url' => omoDecisionBuildParticipantQrSheetUrl(
                    $currentOrganizationId,
                    $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
                    $decisionId
                ),
                'title' => trim((string)$decision->get('title')),
            ];
        }
    }

    if ($canManage && $actionUrl !== '') {
        $canDeleteDecision = !$hasSubmittedResponses
            && $status !== DecisionProcess::STATUS_RESULTS
            && $status !== DecisionProcess::STATUS_ARCHIVED;

        if ($canDeleteDecision) {
            $menuActions[] = [
                'label' => t('decisions.index.action.delete', [], $lang, $sourceLang),
                'behavior' => 'mutation',
                'variant' => 'danger',
                'requestUrl' => $actionUrl,
                'requestPayload' => [
                    'oid' => $currentOrganizationId,
                    'cid' => $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
                    'id' => $decisionId,
                    'method' => $method,
                    'decision_action' => 'delete',
                ],
                'confirmMessage' => t('decisions.index.action.confirm_delete', [], $lang, $sourceLang),
            ];
        } else {
            $menuActions[] = [
                'label' => t('decisions.index.action.archive', [], $lang, $sourceLang),
                'behavior' => 'mutation',
                'requestUrl' => $actionUrl,
                'requestPayload' => [
                    'oid' => $currentOrganizationId,
                    'cid' => $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
                    'id' => $decisionId,
                    'method' => $method,
                    'decision_action' => 'archive',
                ],
                'confirmMessage' => t('decisions.index.action.confirm_archive', [], $lang, $sourceLang),
            ];
        }
    }

    $statusCounts[$status] = isset($statusCounts[$status]) ? $statusCounts[$status] + 1 : 1;
    $isActiveDefault = $status !== DecisionProcess::STATUS_ARCHIVED
        && (
            $isOwner
            || $hasUserParticipation
            || $hasEmailParticipation
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
        'visibilityType' => (string)($visibility['type'] ?? DecisionProcess::getDefaultVisibilityType()),
        'visibilityLabel' => (string)($visibility['badgeText'] ?? ''),
        'visibilityIconUrl' => $resolveDecisionVisibilityIconUrl((string)($visibility['type'] ?? DecisionProcess::getDefaultVisibilityType())),
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
        'menuActions' => $menuActions,
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
    'openDecisionMode' => $initialOpenDecisionMode,
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
        'moreActionLabel' => t('decisions.index.action.more', [], $lang, $sourceLang),
        'moreActionAriaLabel' => t('decisions.index.action.more_aria', [], $lang, $sourceLang),
        'actionErrorUpdate' => t('decisions.index.action.error_update', [], $lang, $sourceLang),
        'moveModalTitle' => t('decisions.index.move.modal_title', [], $lang, $sourceLang),
        'exportActionLabel' => t('decisions.index.action.export', [], $lang, $sourceLang),
        'exportModalTitle' => t('decisions.index.export.modal_title', [], $lang, $sourceLang),
        'exportModalIntro' => t('decisions.index.export.modal_intro', [], $lang, $sourceLang),
        'exportFormatCsvLabel' => t('decisions.index.export.format.csv', [], $lang, $sourceLang),
        'exportFormatJsonLabel' => t('decisions.index.export.format.json', [], $lang, $sourceLang),
        'exportFormatPdfLabel' => t('decisions.index.export.format.pdf', [], $lang, $sourceLang),
        'exportComingSoonLabel' => t('decisions.index.export.format.coming_soon', [], $lang, $sourceLang),
        'exportOpenLabel' => t('decisions.index.export.open', [], $lang, $sourceLang),
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
    $payloadJson = '{"items":[],"openDecisionId":0,"openDecisionMode":"default","groups":[],"statusFilters":[],"statusCounts":{},"typeOptions":[],"methodOptions":[],"holonOptions":[],"text":{},"newUrl":"","refreshUrl":""}';
}
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
<div
    class="omo-decisions omo-panel-view"
    id="omo-decisions-root"
    data-omo-decisions-initialized="0"
    data-omo-decision-scope="<?= $escape($decisionScope) ?>"
    data-omo-decision-oid="<?= (int)$currentOrganizationId ?>"
    data-omo-decision-cid="<?= (int)$normalizedCurrentHolonId ?>"
    data-omo-app-view-preferences="<?= $escape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-view-filter-pending="1"
    aria-busy="true"
>
    <script type="application/json" data-omo-decisions-payload><?= $payloadJson ?></script>
    <div class="omo-panel-view__header omo-panel-view__header--stacked omo-decisions__hero">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-decisions__app-icon" aria-hidden="true">
                    <img src="images/tools/decision.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-decisions__title-row generic-title-row">
                        <h2 class="omo-panel-view__title"><?= $escape(t('decisions.index.title', [], $lang, $sourceLang)) ?></h2>
                        <span class="omo-panel-view__count" data-omo-decisions-count><?= $escape((string)count($decisionEntries)) ?></span>
                    </div>
                </div>
            </div>
            <div class="omo-panel-view__aside omo-decisions__header-actions" data-omo-header-actions>
                <?php if ($canCreateDecision): ?>
                <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" aria-label="<?= $escape(t('decisions.index.new', [], $lang, $sourceLang)) ?>" data-omo-decisions-new>
                    <span class="omo-mobile-corner-action__text"><?= $escape(t('decisions.index.new', [], $lang, $sourceLang)) ?></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="omo-panel-view__header-secondary omo-decisions__header-secondary">
            <div class="omo-decisions__filter-toolbar omo-view-filter" data-omo-decisions-filter-control role="group" aria-label="<?= $escape(t('decisions.index.view_filter.aria', [], $lang, $sourceLang)) ?>">
                <div class="omo-view-filter__input">
                    <div class="omo-view-filter__chips">
                        <button type="button" class="omo-view-filter__chip" data-omo-decisions-filter-toggle data-omo-decisions-scope-chip aria-expanded="false" aria-controls="omo-decisions-filter-panel"><?= $escape(t('decisions.index.scope.' . $decisionScope, [], $lang, $sourceLang)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-decisions-filter-toggle data-omo-decisions-type-chip aria-expanded="false" aria-controls="omo-decisions-filter-panel"><?= $escape(t('decisions.index.filters.type.all', [], $lang, $sourceLang)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-decisions-filter-toggle data-omo-decisions-method-chip aria-expanded="false" aria-controls="omo-decisions-filter-panel"><?= $escape(t('decisions.index.filters.method.all', [], $lang, $sourceLang)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-decisions-filter-toggle data-omo-decisions-sort-chip aria-expanded="false" aria-controls="omo-decisions-filter-panel"><?= $escape(t('decisions.index.controls.sort.time', [], $lang, $sourceLang)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-decisions-filter-toggle data-omo-decisions-density-chip aria-expanded="false" aria-controls="omo-decisions-filter-panel"><?= $escape(t('decisions.index.controls.density.detail', [], $lang, $sourceLang)) ?></button>
                    </div>
                    <label class="omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-omo-decisions-search placeholder="<?= $escape(t('decisions.index.filters.search.placeholder', [], $lang, $sourceLang)) ?>" aria-label="<?= $escape(t('decisions.index.filters.search.label', [], $lang, $sourceLang)) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-decisions-filter-panel" class="omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-omo-decisions-filter-panel hidden>
                    <div class="omo-view-filter__panel-grid">
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.view_filter.scope', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(t('decisions.index.view_filter.scope', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($availableDecisionScopes as $scopeKey): ?>
                                    <?php $scopeLabel = t('decisions.index.scope.' . $scopeKey, [], $lang, $sourceLang); ?>
                                    <button type="button" class="omo-segmented__button<?= $decisionScope === $scopeKey ? ' is-active' : '' ?>" data-omo-decision-scope-toggle="<?= $escape($scopeKey) ?>" aria-pressed="<?= $decisionScope === $scopeKey ? 'true' : 'false' ?>"><?= $escape($scopeLabel) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.filters.type.label', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" data-omo-decisions-type-choices role="group" aria-label="<?= $escape(t('decisions.index.filters.type.label', [], $lang, $sourceLang)) ?>"></div>
                            <select data-omo-decisions-type hidden></select>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.filters.method.label', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" data-omo-decisions-method-choices role="group" aria-label="<?= $escape(t('decisions.index.filters.method.label', [], $lang, $sourceLang)) ?>"></div>
                            <select data-omo-decisions-method hidden></select>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.index.view_filter.presentation', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(t('decisions.index.controls.sort.aria', [], $lang, $sourceLang)) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-decisions-sort="time" aria-pressed="true"><?= $escape(t('decisions.index.controls.sort.time', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decisions-sort="alpha" aria-pressed="false"><?= $escape(t('decisions.index.controls.sort.alpha', [], $lang, $sourceLang)) ?></button>
                            </div>
                            <div class="omo-segmented" role="group" aria-label="<?= $escape(t('decisions.index.controls.density.aria', [], $lang, $sourceLang)) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-decisions-density="detail" aria-pressed="true"><?= $escape(t('decisions.index.controls.density.detail', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-decisions-density="compact" aria-pressed="false"><?= $escape(t('decisions.index.controls.density.compact', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-omo-decisions-filter-apply><?= $escape(t('decisions.index.view_filter.apply', [], $lang, $sourceLang)) ?></button>
                        <?php if (!empty($applicationViewPreferences['canSavePersonal'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decisions-filter-save data-omo-app-view-save-scope="personal"><?= $escape(t('decisions.index.view_filter.save', [], $lang, $sourceLang)) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= $escape($applicationViewPreferences['primarySaveScope']) ?>"><?= $escape(omoApplicationViewPreferencesT('app_view.save_organization_template', array('templateName' => $applicationViewPreferences['templateLabel'] ?? ''))) ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="omo-decisions__filters" aria-label="<?= $escape(t('decisions.index.view_filter.status', [], $lang, $sourceLang)) ?>">
        <div class="omo-decisions__status-scroll" data-omo-decisions-status-scroll>
            <button type="button" class="omo-decisions__status-scroll-button omo-decisions__status-scroll-button--prev" data-omo-decisions-status-scroll-prev aria-label="Defiler les etats vers la gauche" hidden>&lt;</button>
            <div class="omo-decisions__status-tabs" data-omo-decisions-status-tabs></div>
            <button type="button" class="omo-decisions__status-scroll-button omo-decisions__status-scroll-button--next" data-omo-decisions-status-scroll-next aria-label="Defiler les etats vers la droite" hidden>&gt;</button>
        </div>
    </div>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content">
            <div class="omo-decisions__state generic-section" data-omo-decisions-state>
                <?= $escape(t('decisions.index.loading', [], $lang, $sourceLang)) ?>
            </div>

            <div class="omo-decisions__list" data-omo-decisions-list hidden></div>

            <div class="omo-overlay-drawer omo-decisions__editor-drawer" data-omo-decision-editor-drawer hidden>
                <div class="omo-overlay-drawer__backdrop" data-omo-decision-editor-close></div>
                <div class="omo-overlay-drawer__panel">
                    <div class="omo-overlay-drawer__header generic-drawer-header">
                        <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                            <h3 class="omo-overlay-drawer__title" data-omo-subdrawer-title data-omo-decision-editor-title><?= $escape(t('decisions.index.action.open_editor_title', [], $lang, $sourceLang)) ?></h3>
                            <p class="omo-overlay-drawer__description" data-omo-subdrawer-description data-omo-decision-editor-description><?= $escape(t('decisions.index.description', [], $lang, $sourceLang)) ?></p>
                        </div>
                        <div class="generic-drawer-header__actions">
                            <div data-omo-subdrawer-actions></div>
                            <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-omo-decision-editor-close>Fermer</button>
                        </div>
                    </div>
                    <div class="omo-overlay-drawer__body omo-decisions__editor-body" data-omo-decision-editor-body></div>
                </div>
            </div>
        </div>
</div>
</div>

<script src="/common/drawer/subdrawer.js?v=20260816-header-help"></script>
<script src="/omo/assets/js/application-view-preferences.js?v=20260902-view-cleanup"></script>
<link rel="stylesheet" href="/common/choice/decision_cards.css?v=20260813-decision-uniformity">
<script src="/common/choice/decision_cards.js"></script>

<style>
.omo-decisions {
    min-height: 100%;
}

.omo-decisions__app-icon {
    --omo-panel-view-app-icon-accent: var(--color-primary, #2563eb);
}

.omo-decisions__header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}

.omo-decisions__header-secondary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.omo-decisions__scope-slot {
    min-width: 0;
}

.omo-decisions__controls {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12px;
}

.omo-decisions__filters {
    display: grid;
    gap: 12px;
    margin: 0;
    padding: 12px 20px;
    background: color-mix(in srgb, var(--color-bg, #f8fafc) 94%, transparent);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-bottom: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 80%, transparent);
}

.omo-decisions__hero {
    z-index: 30;
    overflow: visible;
}

.omo-decisions__status-bar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    min-width: 0;
}

.omo-decisions__status-scroll {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1 1 auto;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}

.omo-decisions__status-tabs {
    position: relative;
    display: flex;
    flex-wrap: nowrap;
    gap: 0;
    flex: 1 1 auto;
    min-width: 0;
    max-width: 100%;
    padding: 3px 3px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 80%, var(--color-surface, #ffffff));
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    overscroll-behavior-x: contain;
}

.omo-decisions__status-tabs::-webkit-scrollbar {
    display: none;
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

.omo-decisions__status-scroll-button {
    position: absolute;
    top: 50%;
    z-index: 2;
    width: 32px;
    height: 32px;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface, #ffffff) 92%, transparent);
    color: var(--color-text, #1f2937);
    font: inherit;
    font-size: 16px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transform: translateY(-50%);
    box-shadow:
        0 6px 18px rgba(15, 23, 42, 0.12),
        inset 0 0 0 1px color-mix(in srgb, var(--color-border, #d1d5db) 82%, white 18%);
}

.omo-decisions__status-scroll-button:hover {
    background: var(--color-surface, #ffffff);
}

.omo-decisions__status-scroll-button--prev {
    left: 5px;
}

.omo-decisions__status-scroll-button--next {
    right: 5px;
}

.omo-decisions__status-scroll-button[hidden] {
    display: none !important;
}

.omo-decisions__status-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 37px;
    padding: 7px 12px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--color-text-light, #475569);
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
}

.omo-decisions__status-tab:hover {
    color: var(--color-text, #1f2937);
}

.omo-decisions__status-tab.is-active {
    background: var(--color-surface, #ffffff);
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.10);
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
    background: color-mix(in srgb, rgba(148, 163, 184, 0.16) 78%, transparent);
    color: inherit;
    font-size: 0.76rem;
    line-height: 1;
}

.omo-decisions__filters-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.omo-decisions__field--full {
    grid-column: 1 / -1;
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
    margin: var(--generic-container-margin, 20px);
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
}

.omo-decisions__list.generic-file-list .generic-file-list__table {
    margin: 10px;
}

.omo-decisions__group {
    position: relative;
}

.omo-decisions__card-list {
    display: grid;
    gap: 10px;
    margin: 10px;
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

.omo-decisions__compact-title-line {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    max-width: 100%;
}

.omo-decisions__visibility-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
    opacity: 0.82;
}

.omo-decisions__visibility-icon img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.omo-decisions__visibility-icon--compact {
    width: 16px;
    height: 16px;
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

.omo-decisions__compact-menu-toggle {
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    color: var(--color-text, #1f2937);
}

.omo-decisions-card__menu-toggle {
    min-width: 42px;
    padding-left: 12px;
    padding-right: 12px;
}

.omo-decisions__state[hidden] {
    display: none !important;
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

    .omo-decisions__header-secondary,
    .omo-decisions__controls {
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

    .omo-decisions__status-scroll {
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

@media (max-width: 1024px) {
    .omo-decisions__header-actions {
        width: auto;
        position: static;
        z-index: auto;
    }

    .omo-decisions__header-actions .omo-mobile-corner-action {
        width: 42px;
        min-width: 42px;
        max-width: 42px;
        flex: 0 0 42px;
        border-radius: 0 0 0 var(--radius-md) !important;
    }

    .omo-decisions__header-secondary {
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: nowrap;
        gap: 10px;
    }

    .omo-decisions__scope-slot {
        flex: 0 0 auto;
    }

    .omo-decisions__controls {
        width: auto;
        flex: 0 0 auto;
        justify-content: flex-end;
        flex-wrap: nowrap;
        gap: 8px;
    }
}
</style>

<script>
(() => {
const root = document.getElementById('omo-decisions-root');
if (!root) {
    return;
}

if (typeof window.omoDecisionIndexTeardown === 'function') {
    try {
        window.omoDecisionIndexTeardown();
    } catch (error) {
        if (window.console && typeof window.console.warn === 'function') {
            window.console.warn('[OMO][Decisions][panel] previous teardown failed', error);
        }
    }
}

const omoDecisionIndexGlobalCleanupCallbacks = [];
function omoDecisionRegisterGlobalListener(target, eventName, listener, options) {
    if (!target || typeof target.addEventListener !== 'function' || typeof listener !== 'function') {
        return;
    }

    target.addEventListener(eventName, listener, options);
    omoDecisionIndexGlobalCleanupCallbacks.push(function () {
        target.removeEventListener(eventName, listener, options);
    });
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
const omoDecisionsSavedViewsStorageKey = 'omo.decisions.saved-views.v2';
const omoDecisionsLegacySavedViewsStorageKey = 'omo.decisions.saved-views.v1';
const omoDecisionsSessionViewsStorageKey = 'omo.decisions.session-views.v1';
const omoDecisionsSearchStorageKey = 'omo.decisions.quick-search.v1';
const elements = {
    newButton: root.querySelector('[data-omo-decisions-new]'),
    count: root.querySelector('[data-omo-decisions-count]'),
    displayControls: root.querySelector('[data-omo-decisions-display-controls]'),
    statusScroll: root.querySelector('[data-omo-decisions-status-scroll]'),
    statusTabs: root.querySelector('[data-omo-decisions-status-tabs]'),
    statusScrollPrev: root.querySelector('[data-omo-decisions-status-scroll-prev]'),
    statusScrollNext: root.querySelector('[data-omo-decisions-status-scroll-next]'),
    filtersToggle: root.querySelector('[data-omo-decisions-filters-toggle]'),
    filtersPanel: root.querySelector('[data-omo-decisions-filters-panel]'),
    search: root.querySelector('[data-omo-decisions-search]'),
    type: root.querySelector('[data-omo-decisions-type]'),
    method: root.querySelector('[data-omo-decisions-method]'),
    typeChoices: root.querySelector('[data-omo-decisions-type-choices]'),
    methodChoices: root.querySelector('[data-omo-decisions-method-choices]'),
    reset: root.querySelector('[data-omo-decisions-reset]'),
    filterControl: root.querySelector('[data-omo-decisions-filter-control]'),
    viewFilterPanel: root.querySelector('[data-omo-decisions-filter-panel]'),
    state: root.querySelector('[data-omo-decisions-state]'),
    list: root.querySelector('[data-omo-decisions-list]'),
    editorDrawer: root.querySelector('[data-omo-decision-editor-drawer]'),
    editorTitle: root.querySelector('[data-omo-decision-editor-title]'),
    editorDescription: root.querySelector('[data-omo-decision-editor-description]'),
    editorBody: root.querySelector('[data-omo-decision-editor-body]')
};
const decisionDrawerController = elements.editorDrawer && typeof window.omoCreateSubdrawerController === 'function'
    ? window.omoCreateSubdrawerController({ drawer: elements.editorDrawer })
    : null;
if (decisionDrawerController) {
    elements.editorDrawer.__omoSubdrawerController = decisionDrawerController;
    window.omoDecisionDrawer = decisionDrawerController;
}
const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
    ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
    : null;
const savedPreferences = omoDecisionsReadViewPreferences();
const state = {
    status: savedPreferences.status,
    search: omoDecisionsReadSearch(),
    type: savedPreferences.type,
    method: savedPreferences.method,
    filtersExpanded: false,
    sort: savedPreferences.sort,
    density: savedPreferences.density
};
let omoDecisionIndexRefreshToken = 0;
let pendingViewFilters = null;
let decisionFilterPanelOpen = false;

function omoDecisionDebugLog(stage, details) {
    if (!window.console || typeof window.console.log !== 'function') {
        return;
    }

    if (details && typeof details === 'object') {
        console.log('[OMO][Decisions][panel]', stage, details);
        return;
    }

    console.log('[OMO][Decisions][panel]', stage, details);
}

function omoDecisionDebugError(stage, error, details) {
    if (!window.console || typeof window.console.error !== 'function') {
        return;
    }

    if (details && typeof details === 'object') {
        console.error('[OMO][Decisions][panel]', stage, error, details);
        return;
    }

    console.error('[OMO][Decisions][panel]', stage, error);
}

omoDecisionDebugLog('bootstrap:start', {
    oid: Number(root.getAttribute('data-omo-decision-oid') || 0),
    cid: Number(root.getAttribute('data-omo-decision-cid') || 0),
    scope: String(root.getAttribute('data-omo-decision-scope') || 'contextual'),
    payloadItemCount: Array.isArray(payload.items) ? payload.items.length : null,
    refreshUrl: String(payload.refreshUrl || '')
});

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

function omoDecisionsNormalizeScopePreference(value) {
    const normalized = String(value || '').trim().toLowerCase();
    return normalized === 'children' || normalized === 'descendants' ? normalized : 'contextual';
}

function omoDecisionsGetPreferencesContextKey() {
    return String(root.getAttribute('data-omo-decision-oid') || '0')
        + ':' + String(root.getAttribute('data-omo-decision-cid') || '0');
}

function omoDecisionsGetSavedViewsStore() {
    try {
        const rawValue = window.localStorage.getItem(omoDecisionsSavedViewsStorageKey);
        const savedViews = rawValue ? JSON.parse(rawValue) : null;
        if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
            return {
                defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                    ? savedViews.defaultView
                    : null,
                contexts: savedViews.contexts
            };
        }

        const legacyValue = window.localStorage.getItem(omoDecisionsLegacySavedViewsStorageKey);
        const legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
        return {
            defaultView: null,
            contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
        };
    } catch (error) {
        return {defaultView: null, contexts: {}};
    }
}

function omoDecisionsSaveViewsStore(store) {
    try {
        window.localStorage.setItem(omoDecisionsSavedViewsStorageKey, JSON.stringify({
            defaultView: store.defaultView && typeof store.defaultView === 'object'
                ? store.defaultView
                : null,
            contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
        }));
    } catch (error) {
    }
}

function omoDecisionsGetStoredViewPreferences() {
    const preferences = omoDecisionsGetSavedViewsStore().contexts[omoDecisionsGetPreferencesContextKey()];
    return preferences && typeof preferences === 'object' ? preferences : null;
}

function omoDecisionsGetDefaultViewPreferences() {
    return omoDecisionsGetSavedViewsStore().defaultView;
}

function omoDecisionsStoreViewPreferences(preferences) {
    const store = omoDecisionsGetSavedViewsStore();
    store.contexts[omoDecisionsGetPreferencesContextKey()] = omoDecisionsNormalizeViewPreferences(preferences);
    omoDecisionsSaveViewsStore(store);
}

function omoDecisionsStoreDefaultViewPreferences(preferences) {
    const store = omoDecisionsGetSavedViewsStore();
    store.defaultView = omoDecisionsNormalizeViewPreferences(preferences);
    omoDecisionsSaveViewsStore(store);
}

function omoDecisionsClearStoredViewPreferences() {
    const store = omoDecisionsGetSavedViewsStore();
    delete store.contexts[omoDecisionsGetPreferencesContextKey()];
    omoDecisionsSaveViewsStore(store);
}

function omoDecisionsReadStoredValue(storage, storageKey) {
    try {
        const values = JSON.parse(storage.getItem(storageKey) || '{}');
        return values && typeof values === 'object' ? values[omoDecisionsGetPreferencesContextKey()] || null : null;
    } catch (error) {
        return null;
    }
}

function omoDecisionsWriteStoredValue(storage, storageKey, value) {
    try {
        const values = JSON.parse(storage.getItem(storageKey) || '{}');
        const map = values && typeof values === 'object' ? values : {};
        map[omoDecisionsGetPreferencesContextKey()] = value;
        storage.setItem(storageKey, JSON.stringify(map));
    } catch (error) {
    }
}

function omoDecisionsNormalizeViewPreferences(preferences) {
    const values = preferences && typeof preferences === 'object' ? preferences : {};
    return {
        scope: omoDecisionsNormalizeScopePreference(values.scope),
        status: String(values.status || 'active'),
        type: String(values.type || 'all'),
        method: String(values.method || 'all'),
        sort: omoDecisionsNormalizeSortPreference(values.sort),
        density: omoDecisionsNormalizeDensityPreference(values.density)
    };
}

function omoDecisionsReadViewPreferences() {
    const temporary = omoDecisionsReadStoredValue(window.sessionStorage, omoDecisionsSessionViewsStorageKey);
    const saved = omoDecisionsGetStoredViewPreferences();
    const defaultView = omoDecisionsGetDefaultViewPreferences();
    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
        ? window.omoApplicationViewPreferencesGetDefault(root)
        : null;
    const personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
        ? window.omoApplicationViewPreferencesGetPersonal(root)
        : null;
    return omoDecisionsNormalizeViewPreferences(temporary || personalView || serverDefault || saved || defaultView);
}

function omoDecisionsReadSearch() {
    const search = omoDecisionsReadStoredValue(window.sessionStorage, omoDecisionsSearchStorageKey);
    return typeof search === 'string' ? search : '';
}

function omoDecisionsWriteSearch(search) {
    omoDecisionsWriteStoredValue(window.sessionStorage, omoDecisionsSearchStorageKey, String(search || ''));
}

function omoDecisionsClearTemporaryViewPreferences() {
    try {
        const values = JSON.parse(window.sessionStorage.getItem(omoDecisionsSessionViewsStorageKey) || '{}');
        if (!values || typeof values !== 'object') {
            return;
        }
        delete values[omoDecisionsGetPreferencesContextKey()];
        window.sessionStorage.setItem(omoDecisionsSessionViewsStorageKey, JSON.stringify(values));
    } catch (error) {
    }
}

function omoDecisionsClearAllTemporaryViewPreferences() {
    try {
        window.sessionStorage.removeItem(omoDecisionsSessionViewsStorageKey);
    } catch (error) {
    }
}

function omoDecisionsWriteViewPreferences(storage, storageKey, preferences) {
    omoDecisionsWriteStoredValue(
        storage,
        storageKey,
        omoDecisionsNormalizeViewPreferences(preferences)
    );
}

function omoDecisionsGetCurrentViewFilters() {
    return {
        scope: omoDecisionGetCurrentScope(),
        status: state.status,
        type: state.type,
        method: state.method,
        sort: state.sort,
        density: state.density
    };
}

function omoDecisionsWritePreferences(preferences) {
    omoDecisionsWriteViewPreferences(window.sessionStorage, omoDecisionsSessionViewsStorageKey, preferences);

    window.dispatchEvent(new CustomEvent('omo-decisions-preferences-change', {
        detail: omoDecisionsNormalizeViewPreferences(preferences)
    }));
}

function omoDecisionGetCurrentScope() {
    const normalizedScope = String(root.getAttribute('data-omo-decision-scope') || 'contextual').trim().toLowerCase();
    if (normalizedScope === 'global') {
        return 'descendants';
    }
    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
}

function omoDecisionsFindOptionLabel(select, value) {
    if (!select) {
        return '';
    }
    const option = Array.prototype.find.call(select.options || [], function (candidate) {
        return String(candidate.value || '') === String(value || '');
    });
    return option ? String(option.textContent || '').trim() : '';
}

function omoDecisionsFindStatusLabel(value) {
    const filter = (Array.isArray(payload.statusFilters) ? payload.statusFilters : []).find(function (item) {
        return String(item && item.key ? item.key : '') === String(value || '');
    });
    return filter ? String(filter.label || '').trim() : '';
}

function omoDecisionsRenderChoiceButtons(container, options, attribute, selectedValue) {
    if (!container) {
        return;
    }
    container.replaceChildren();
    (Array.isArray(options) ? options : []).forEach(function (option) {
        const value = String(option && option.value !== undefined ? option.value : '');
        const button = document.createElement('button');
        const active = value === String(selectedValue || 'all');
        button.type = 'button';
        button.className = 'omo-segmented__button' + (active ? ' is-active' : '');
        button.setAttribute(attribute, value);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.textContent = String(option && option.label !== undefined ? option.label : value);
        container.appendChild(button);
    });
}

function omoDecisionsSyncFilterChips() {
    const currentScope = omoDecisionGetCurrentScope();
    const entries = [
        {chip: '[data-omo-decisions-scope-chip]', label: function () {
            const button = root.querySelector('[data-omo-decision-scope-toggle="' + currentScope + '"]');
            return button ? String(button.textContent || '').trim() : '';
        }},
        {chip: '[data-omo-decisions-status-chip]', label: function () { return omoDecisionsFindStatusLabel(state.status); }},
        {chip: '[data-omo-decisions-type-chip]', label: function () { return omoDecisionsFindOptionLabel(elements.type, state.type); }},
        {chip: '[data-omo-decisions-method-chip]', label: function () { return omoDecisionsFindOptionLabel(elements.method, state.method); }},
        {chip: '[data-omo-decisions-sort-chip]', label: function () {
            const button = root.querySelector('[data-omo-decisions-sort="' + state.sort + '"]');
            return button ? String(button.textContent || '').trim() : '';
        }},
        {chip: '[data-omo-decisions-density-chip]', label: function () {
            const button = root.querySelector('[data-omo-decisions-density="' + state.density + '"]');
            return button ? String(button.textContent || '').trim() : '';
        }}
    ];
    entries.forEach(function (entry) {
        const chip = root.querySelector(entry.chip);
        const label = entry.label();
        if (chip && label !== '') {
            chip.textContent = label;
        }
    });
}

function omoDecisionsSyncFilterChoices() {
    if (!pendingViewFilters) {
        return;
    }
    const filters = omoDecisionsNormalizeViewPreferences(pendingViewFilters);
    if (!root.querySelector('[data-omo-decision-scope-toggle="' + filters.scope + '"]')) {
        filters.scope = omoDecisionGetCurrentScope();
    }
    filters.status = omoDecisionsFindStatusLabel(filters.status) !== '' ? filters.status : state.status;
    filters.type = omoDecisionsFindOptionLabel(elements.type, filters.type) !== '' ? filters.type : 'all';
    filters.method = omoDecisionsFindOptionLabel(elements.method, filters.method) !== '' ? filters.method : 'all';
    pendingViewFilters = filters;

    root.querySelectorAll('[data-omo-decision-scope-toggle]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decision-scope-toggle') || '') === filters.scope;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-status]').forEach(function (button) {
        const active = String(button.getAttribute('data-status') || '') === filters.status;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-sort') || '') === filters.sort;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-density]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-density') || '') === filters.density;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-type-choice]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-type-choice') || '') === filters.type;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-method-choice]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-method-choice') || '') === filters.method;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    if (elements.type) {
        elements.type.value = filters.type;
    }
    if (elements.method) {
        elements.method.value = filters.method;
    }
}

function omoDecisionsBuildScopeUrl(scope) {
    const query = ['oid=' + encodeURIComponent(String(root.getAttribute('data-omo-decision-oid') || '0'))];
    const holonId = Number(root.getAttribute('data-omo-decision-cid') || 0);
    if (holonId > 0) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }
    const normalizedScope = omoDecisionsNormalizeScopePreference(scope);
    if (normalizedScope !== 'contextual') {
        query.push('decision_scope=' + encodeURIComponent(normalizedScope));
    }
    return '/omo/api/decision/index.php?' + query.join('&');
}

function omoDecisionsRefreshScope(scope) {
    if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
        window.location.href = omoDecisionsBuildScopeUrl(scope);
        return;
    }
    root.classList.add('is-loading');
    window.omoReplaceFetchedPanelRoot({
        rootSelector: '#omo-decisions-root',
        currentRoot: root,
        url: omoDecisionsBuildScopeUrl(scope),
        setLoadingState: function (loading) {
            root.classList.toggle('is-loading', !!loading);
        }
    }).catch(function () {
        root.classList.remove('is-loading');
        root.removeAttribute('data-omo-view-filter-pending');
        root.removeAttribute('aria-busy');
    });
}

function omoDecisionsApplyViewFilters(preferences) {
    const next = omoDecisionsNormalizeViewPreferences(preferences);
    if (next.scope !== omoDecisionGetCurrentScope()) {
        omoDecisionsRefreshScope(next.scope);
        return;
    }
    state.status = next.status;
    state.type = restoreSelectValue(elements.type, next.type);
    state.method = restoreSelectValue(elements.method, next.method);
    state.sort = next.sort;
    state.density = next.density;
    renderList();
}

function omoDecisionsCloseFilterMoreMenu() {
    root.querySelectorAll('[data-omo-decisions-filter-more-menu]').forEach(function (menu) {
        const panel = menu.querySelector('[data-omo-decisions-filter-more-panel]');
        const toggle = menu.querySelector('[data-omo-decisions-filter-more-toggle]');
        if (panel) {
            panel.hidden = true;
        }
        menu.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function omoDecisionsCloseFilterPanel(applyChanges, saveView) {
    if (!decisionFilterPanelOpen) {
        return;
    }
    decisionFilterPanelOpen = false;
    if (elements.viewFilterPanel) {
        elements.viewFilterPanel.hidden = true;
    }
    root.querySelectorAll('[data-omo-decisions-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
    });
    document.removeEventListener('pointerdown', omoDecisionsHandleFilterOutsidePointerDown, true);
    omoDecisionsCloseFilterMoreMenu();
    if (!applyChanges || !pendingViewFilters) {
        pendingViewFilters = null;
        return;
    }
    const next = omoDecisionsNormalizeViewPreferences(pendingViewFilters);
    pendingViewFilters = null;
    if (saveView) {
        omoDecisionsStoreViewPreferences(next);
        omoDecisionsClearTemporaryViewPreferences();
    } else {
        omoDecisionsWritePreferences(next);
    }
    omoDecisionsApplyViewFilters(next);
}

function omoDecisionsApplyFilterMoreAction(action) {
    if (!decisionFilterPanelOpen || !pendingViewFilters) {
        return;
    }

    const next = omoDecisionsNormalizeViewPreferences(pendingViewFilters);
    omoDecisionsCloseFilterPanel(false, false);

    if (action === 'set-default') {
        omoDecisionsClearStoredViewPreferences();
        omoDecisionsClearTemporaryViewPreferences();
        omoDecisionsStoreDefaultViewPreferences(next);
        omoDecisionsApplyViewFilters(next);
        return;
    }

    if (action === 'apply-everywhere') {
        const store = omoDecisionsGetSavedViewsStore();
        store.defaultView = omoDecisionsNormalizeViewPreferences(next);
        store.contexts = {};
        omoDecisionsSaveViewsStore(store);
        omoDecisionsClearAllTemporaryViewPreferences();
        omoDecisionsApplyViewFilters(next);
        return;
    }

    if (action === 'restore-default') {
        omoDecisionsClearStoredViewPreferences();
        omoDecisionsClearTemporaryViewPreferences();
        const store = omoDecisionsGetSavedViewsStore();
        store.defaultView = null;
        omoDecisionsSaveViewsStore(store);
        const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        omoDecisionsApplyViewFilters(serverDefault || {
            scope: 'contextual',
            status: 'active',
            type: 'all',
            method: 'all',
            sort: 'time',
            density: 'detail'
        });
    }
}

function omoDecisionsHandleFilterOutsidePointerDown(event) {
    if (elements.filterControl && elements.filterControl.contains(event.target)) {
        return;
    }
    omoDecisionsCloseFilterPanel(true, false);
}

function omoDecisionsOpenFilterPanel() {
    if (!elements.viewFilterPanel || decisionFilterPanelOpen) {
        return;
    }
    pendingViewFilters = omoDecisionsGetCurrentViewFilters();
    omoDecisionsCloseFilterMoreMenu();
    omoDecisionsSyncFilterChoices();
    elements.viewFilterPanel.hidden = false;
    decisionFilterPanelOpen = true;
    root.querySelectorAll('[data-omo-decisions-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'true');
    });
    document.addEventListener('pointerdown', omoDecisionsHandleFilterOutsidePointerDown, true);
}

function omoDecisionsInitializeViewFilter() {
    const preferredScope = Number(payload.openDecisionId || 0) > 0
        ? omoDecisionGetCurrentScope()
        : savedPreferences.scope;
    if (preferredScope !== omoDecisionGetCurrentScope()) {
        omoDecisionsRefreshScope(preferredScope);
        return true;
    }
    omoDecisionsSyncFilterChips();
    root.removeAttribute('data-omo-view-filter-pending');
    root.removeAttribute('aria-busy');
    return false;
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

    const normalizeDecisionScope = function (scopeValue) {
        const normalizedScope = String(scopeValue || '').trim().toLowerCase();
        if (normalizedScope === 'global') {
            return 'descendants';
        }
        return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
    };
    const currentScope = normalizeDecisionScope(panel.getAttribute('data-omo-decision-scope') || 'contextual');
    const targetScope = normalizeDecisionScope(button.getAttribute('data-omo-decision-scope-toggle') || '');

    if (targetScope === currentScope) {
        return false;
    }

    const scopeSwitch = panel.querySelector('[data-omo-scope-switch]');
    if (scopeSwitch) {
        scopeSwitch.setAttribute('data-omo-scope-switch', targetScope);
        scopeSwitch.style.setProperty(
            '--omo-scope-active-index',
            String(parseInt(button.getAttribute('data-omo-scope-index') || '0', 10) || 0)
        );
    }

    const organizationId = Number(panel.getAttribute('data-omo-decision-oid') || 0);
    let holonId = Number(panel.getAttribute('data-omo-decision-cid') || 0);
    const query = [];

    if (typeof window.omoNormalizeRouteCid === 'function') {
        holonId = Number(window.omoNormalizeRouteCid(holonId) || 0);
    }

    if (organizationId > 0) {
        query.push('oid=' + encodeURIComponent(String(organizationId)));
    }

    if (holonId > 0) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }

    query.push('decision_scope=' + encodeURIComponent(targetScope));
    query.push('_=' + String(Date.now()));

    const targetUrl = '/omo/api/decision/index.php' + (query.length > 0 ? '?' + query.join('&') : '');

    panel.classList.add('is-loading');

    if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
        window.location.href = targetUrl;
        return false;
    }

    window.omoReplaceFetchedPanelRoot({
        rootSelector: '#omo-decisions-root',
        currentRoot: panel,
        url: targetUrl,
        setLoadingState: function (isLoading) {
            panel.classList.toggle('is-loading', !!isLoading);
        }
    }).catch(function () {
        panel.classList.remove('is-loading');
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
    return String(Number(count) || 0);
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

    omoDecisionDebugLog('applyPayload:start', {
        nextPayloadItemCount: Array.isArray(nextPayload.items) ? nextPayload.items.length : null,
        nextRefreshUrl: String(nextPayload.refreshUrl || '')
    });

    const preservedState = {
        status: state.status,
        search: state.search,
        type: state.type,
        method: state.method,
        filtersExpanded: state.filtersExpanded,
        sort: state.sort,
        density: state.density
    };

    replacePayload(nextPayload);

    populateSelect(elements.type, payload.typeOptions);
    populateSelect(elements.method, payload.methodOptions);
    omoDecisionsRenderChoiceButtons(elements.typeChoices, payload.typeOptions, 'data-omo-decisions-type-choice', state.type);
    omoDecisionsRenderChoiceButtons(elements.methodChoices, payload.methodOptions, 'data-omo-decisions-method-choice', state.method);

    state.status = preservedState.status;
    state.search = preservedState.search;
    state.type = restoreSelectValue(elements.type, preservedState.type);
    state.method = restoreSelectValue(elements.method, preservedState.method);
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

    omoDecisionDebugLog('applyPayload:end', {
        currentStatus: state.status,
        currentSort: state.sort,
        currentDensity: state.density,
        currentItemCount: Array.isArray(payload.items) ? payload.items.length : null
    });
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

    omoDecisionDebugLog('refreshIndex:start', {
        requestToken: requestToken,
        resolvedUrl: resolvedUrl,
        silent: settings.silent === true
    });

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

            omoDecisionDebugLog('refreshIndex:success', {
                requestToken: requestToken,
                nextPayloadItemCount: Array.isArray(nextPayload.items) ? nextPayload.items.length : null
            });

            applyDecisionIndexPayload(nextPayload);
            return true;
        })
        .catch(function (error) {
            omoDecisionDebugError('refreshIndex:error', error, {
                requestToken: requestToken,
                resolvedUrl: resolvedUrl,
                silent: settings.silent === true
            });

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

    const settings = options && typeof options === 'object' ? options : {};
    const currentHashState = typeof window.omoParsePopupHashState === 'function'
        ? window.omoParsePopupHashState()
        : null;
    const currentRouteToken = currentHashState && currentHashState.routeToken
        ? String(currentHashState.routeToken)
        : '';

    if (
        settings.force !== true
        && /^decision-(?:(?:d)?\d+|[vgp]\d+)$/i.test(currentRouteToken)
        && typeof window.omoOpenDrawerHashState === 'function'
    ) {
        window.omoOpenDrawerHashState('decision');
        return;
    }

    const wasOpen = !elements.editorDrawer.hidden || elements.editorDrawer.classList.contains('is-open');
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

    if (decisionDrawerController) {
        decisionDrawerController.setHeader({
            title: resolvedTitle,
            description: description || '',
            help: '',
            actions: []
        });
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

            if (decisionDrawerController) {
                decisionDrawerController.applyContentHeader(elements.editorBody);

                const editorHeaderForm = elements.editorBody.querySelector('[data-omo-decision-editor-header-form]');
                if (editorHeaderForm && editorHeaderForm.id) {
                    const submitLabel = String(editorHeaderForm.getAttribute('data-omo-decision-editor-header-submit-label') || '').trim();
                    decisionDrawerController.setHeader({
                        title: String(editorHeaderForm.getAttribute('data-omo-decision-editor-header-title') || resolvedTitle),
                        description: '',
                        actions: submitLabel === '' ? [] : [{
                            label: submitLabel,
                            type: 'button',
                            className: 'generic-action-button generic-action-button--main',
                            attributes: {
                                form: editorHeaderForm.id,
                                'data-omo-decision-editor-submit': ''
                            },
                            onClick: function () {
                                if (!editorHeaderForm.isConnected) {
                                    return;
                                }

                                if (typeof editorHeaderForm.requestSubmit === 'function') {
                                    editorHeaderForm.requestSubmit();
                                    return;
                                }

                                const fallbackSubmit = document.createElement('button');
                                fallbackSubmit.type = 'submit';
                                fallbackSubmit.hidden = true;
                                editorHeaderForm.appendChild(fallbackSubmit);
                                fallbackSubmit.click();
                                fallbackSubmit.remove();
                            }
                        }]
                    });
                }
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

function getCurrentDecisionRouteToken() {
    if (typeof window.omoParsePopupHashState !== 'function') {
        return '';
    }

    const hashState = window.omoParsePopupHashState();
    return hashState && hashState.routeToken
        ? String(hashState.routeToken)
        : '';
}

function buildDecisionRouteToken(decisionId, modeValue) {
    const resolvedDecisionId = Number(decisionId || 0);
    if (!Number.isInteger(resolvedDecisionId) || resolvedDecisionId <= 0) {
        return null;
    }

    if (typeof window.omoBuildDecisionRouteToken === 'function') {
        return window.omoBuildDecisionRouteToken(resolvedDecisionId, normalizeDecisionOpenMode(modeValue));
    }

    const normalizedMode = normalizeDecisionOpenMode(modeValue);
    if (normalizedMode === 'view') {
        return 'decision-v' + String(resolvedDecisionId);
    }

    if (normalizedMode === 'manage') {
        return 'decision-g' + String(resolvedDecisionId);
    }

    if (normalizedMode === 'participate') {
        return 'decision-p' + String(resolvedDecisionId);
    }

    return 'decision-d' + String(resolvedDecisionId);
}

function normalizeDecisionOpenMode(modeValue) {
    const normalizedMode = String(modeValue || '').trim().toLowerCase();
    return normalizedMode === 'view' || normalizedMode === 'manage' || normalizedMode === 'participate'
        ? normalizedMode
        : 'default';
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

function resolveDecisionActionForMode(item, modeValue) {
    const normalizedMode = normalizeDecisionOpenMode(modeValue);
    if (normalizedMode === 'default') {
        return resolveDecisionAutoOpenAction(item);
    }

    const actions = Array.isArray(item && item.actions) ? item.actions : [];
    for (let index = 0; index < actions.length; index += 1) {
        const action = actions[index];
        if (String(action && action.mode ? action.mode : '').trim().toLowerCase() !== normalizedMode) {
            continue;
        }

        const actionUrl = String(action && action.url ? action.url : '').trim();
        if (actionUrl !== '') {
            return action;
        }
    }

    return null;
}

function openDecisionItemById(decisionId, options) {
    const item = findDecisionItemById(decisionId);
    if (!item) {
        return false;
    }

    const settings = options && typeof options === 'object'
        ? options
        : {};
    const action = resolveDecisionActionForMode(item, settings.mode);
    const actionUrl = String(action && action.url ? action.url : '').trim();
    if (actionUrl === '') {
        return false;
    }

    openDecisionEditor(
        actionUrl,
        String(settings.title || item.title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision')),
        String(settings.description || '')
    );

    return true;
}

function openDecisionFromInteraction(decisionId, targetUrl, title, description, modeValue) {
    const resolvedDecisionId = Number(decisionId || 0);
    const resolvedUrl = String(targetUrl || '').trim();
    const normalizedMode = normalizeDecisionOpenMode(modeValue);
    const routeToken = buildDecisionRouteToken(resolvedDecisionId, normalizedMode);
    const currentRouteToken = getCurrentDecisionRouteToken();

    if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
        window.omoOpenDrawerHashState(routeToken);
        return true;
    }

    if (resolvedDecisionId > 0 && openDecisionItemById(resolvedDecisionId, {
        title: title,
        description: description,
        mode: normalizedMode
    })) {
        return true;
    }

    if (resolvedUrl !== '') {
        openDecisionEditor(resolvedUrl, title, description);
        return true;
    }

    return false;
}

function openInitialDecisionFromPayload() {
    const decisionId = Number(payload.openDecisionId || 0);
    if (!Number.isInteger(decisionId) || decisionId <= 0) {
        return false;
    }

    payload.openDecisionId = 0;
    return openDecisionItemById(decisionId, {
        mode: normalizeDecisionOpenMode(payload.openDecisionMode || 'default')
    });
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
    const statusFilters = Array.isArray(payload.statusFilters) ? payload.statusFilters : [];
    const lastFilterIndex = Math.max(0, statusFilters.length - 1);

    statusFilters.forEach(function (filter, index) {
        const key = String(filter && filter.key ? filter.key : 'all');
        const count = key === 'all'
            ? (Array.isArray(payload.items) ? payload.items.length : 0)
            : (key === 'active'
                ? Number(payload.statusCounts && payload.statusCounts.active ? payload.statusCounts.active : 0)
                : Number(payload.statusCounts && payload.statusCounts[key] ? payload.statusCounts[key] : 0));

        const shouldAlwaysShow = index === 0 || index === lastFilterIndex;
        if (!shouldAlwaysShow && count <= 0 && state.status !== key) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'omo-decisions__status-tab' + (state.status === key ? ' is-active' : '');
        button.setAttribute('data-status', key);
        button.setAttribute('aria-pressed', state.status === key ? 'true' : 'false');
        button.innerHTML = '<span>' + escapeHtml(filter && filter.label ? filter.label : key) + '</span>'
            + '<span class="omo-decisions__status-count">' + String(count) + '</span>';
        elements.statusTabs.appendChild(button);
    });

    window.requestAnimationFrame(syncStatusTabsOverflow);
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
    omoDecisionsSyncFilterChips();
}

function resetListPresentation() {
    if (!elements.list) {
        return;
    }

    elements.list.className = 'omo-decisions__list';
    elements.list.removeAttribute('data-generic-file-list');
}

function syncStatusTabsOverflow() {
    if (!elements.statusTabs || !elements.statusScrollPrev || !elements.statusScrollNext) {
        return;
    }

    const maxScrollLeft = Math.max(0, elements.statusTabs.scrollWidth - elements.statusTabs.clientWidth);
    const hasOverflow = maxScrollLeft > 4;
    const scrollLeft = Math.max(0, elements.statusTabs.scrollLeft);

    elements.statusScrollPrev.hidden = !hasOverflow || scrollLeft <= 4;
    elements.statusScrollNext.hidden = !hasOverflow || scrollLeft >= (maxScrollLeft - 4);
}

function scrollStatusTabs(direction) {
    if (!elements.statusTabs) {
        return;
    }

    const offset = Math.max(120, Math.floor(elements.statusTabs.clientWidth * 0.7));
    elements.statusTabs.scrollBy({
        left: direction < 0 ? -offset : offset,
        behavior: 'smooth'
    });
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
    elements.filtersToggle.classList.toggle('is-active', state.search !== '' || state.type !== 'all' || state.method !== 'all');
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
        ? 'generic-hero-panel accent'
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

function buildCompactAvatar(owner) {
    const resolvedOwner = owner && typeof owner === 'object' ? owner : {};
    const ownerName = String(resolvedOwner.displayName || '').trim();
    const ownerInitials = String(resolvedOwner.initials || 'P').trim() || 'P';
    const ownerPhotoUrl = String(resolvedOwner.photoUrl || '').trim();

    if (ownerPhotoUrl !== '') {
        return '<span class="omo-decisions__compact-avatar"><img src="' + escapeHtml(ownerPhotoUrl) + '" alt="' + escapeHtml(ownerName !== '' ? ownerName : ownerInitials) + '" class="omo-decisions__compact-avatar-photo" width="34" height="34" decoding="async"></span>';
    }

    return '<span class="omo-decisions__compact-avatar"><span class="omo-decisions__compact-avatar-placeholder">' + escapeHtml(ownerInitials) + '</span></span>';
}

function buildDecisionVisibilityIconHtml(item, className) {
    const resolvedClassName = String(className || '').trim();
    const iconUrl = String(item && item.visibilityIconUrl ? item.visibilityIconUrl : '').trim();
    const iconLabel = String(item && item.visibilityLabel ? item.visibilityLabel : '').trim();

    if (iconUrl === '') {
        return '';
    }

    return '<span class="' + escapeHtml(resolvedClassName !== '' ? resolvedClassName : 'omo-decisions__visibility-icon') + '" role="img"'
        + (iconLabel !== '' ? ' aria-label="' + escapeHtml(iconLabel) + '" title="' + escapeHtml(iconLabel) + '"' : '')
        + '><img src="' + escapeHtml(iconUrl) + '" alt=""></span>';
}

function buildCompactMetaLine(item) {
    const metaParts = [];
    const ownerName = String(item && item.owner && item.owner.displayName ? item.owner.displayName : '').trim();
    const typeLabel = String(item && item.decisionTypeLabel ? item.decisionTypeLabel : '').trim();
    const methodLabel = String(item && item.evaluationMethodLabel ? item.evaluationMethodLabel : '').trim();
    const badges = Array.isArray(item && item.badges) ? item.badges : [];

    if (ownerName !== '') {
        metaParts.push(ownerName);
    }
    if (typeLabel !== '') {
        metaParts.push(typeLabel);
    }
    if (methodLabel !== '') {
        metaParts.push(methodLabel);
    }

    badges.forEach(function (badge) {
        const normalizedBadge = String(badge || '').trim();
        if (normalizedBadge !== '') {
            metaParts.push(normalizedBadge);
        }
    });

    return metaParts.map(function (part, index) {
        const separator = index > 0 ? '<span class="omo-decisions__compact-meta-separator">|</span>' : '';
        return separator + '<span>' + escapeHtml(part) + '</span>';
    }).join('');
}

function buildCompactListHeader() {
    const header = document.createElement('div');
    header.className = 'generic-file-list__header';
    header.innerHTML = ''
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderName ? payload.text.compactHeaderName : 'Decision') + '</div>'
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderStatus ? payload.text.compactHeaderStatus : 'Statut') + '</div>'
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderScope ? payload.text.compactHeaderScope : 'Structure') + '</div>'
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderActivity ? payload.text.compactHeaderActivity : 'Activite') + '</div>';
    return header;
}

function isDecisionMenuActionUsable(action) {
    if (!action || typeof action !== 'object') {
        return false;
    }

    const behavior = String(action.behavior || '').trim();
    if (behavior === 'mutation') {
        return String(action.requestUrl || '').trim() !== '';
    }

    if (behavior === 'export') {
        return String(action.exportUrl || '').trim() !== '';
    }

    if (behavior === 'window' || behavior === 'modal') {
        return String(action.url || '').trim() !== '';
    }

    return String(action.url || '').trim() !== '';
}

function getDecisionDetailMenuActions(item) {
    return (Array.isArray(item && item.menuActions) ? item.menuActions : []).filter(isDecisionMenuActionUsable);
}

function getDecisionCompactMenuActions(item) {
    const primaryActions = Array.isArray(item && item.actions) ? item.actions : [];
    return primaryActions.concat(getDecisionDetailMenuActions(item)).filter(isDecisionMenuActionUsable);
}

function buildDecisionActionMenuToggle(item, actions, className) {
    const resolvedActions = Array.isArray(actions) ? actions.filter(isDecisionMenuActionUsable) : [];
    if (resolvedActions.length === 0) {
        return null;
    }

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = (String(className || '').trim() || 'omo-decisions__compact-menu-toggle') + ' generic-menu-toggle';
    toggle.setAttribute('data-omo-decision-compact-menu-toggle', '1');
    toggle.setAttribute('data-omo-decision-compact-menu-actions', JSON.stringify(resolvedActions));
    toggle.setAttribute('data-omo-decision-compact-menu-title', String(item && item.title ? item.title : ''));
    toggle.setAttribute('data-omo-decision-compact-menu-description', String(item && item.description ? item.description : ''));
    toggle.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));
    toggle.setAttribute('aria-haspopup', 'menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', String(payload.text && payload.text.moreActionAriaLabel ? payload.text.moreActionAriaLabel : 'Plus d actions pour cette prise de decision'));
    toggle.textContent = String(payload.text && payload.text.moreActionLabel ? payload.text.moreActionLabel : '...');
    return toggle;
}

function buildCompactActionMenu(item) {
    const actions = getDecisionCompactMenuActions(item);

    if (actions.length === 0) {
        return null;
    }

    const menu = document.createElement('div');
    menu.className = 'omo-decisions__compact-menu generic-menu generic-file-list__menu';
    menu.setAttribute('data-omo-decision-compact-menu', '1');

    const toggle = buildDecisionActionMenuToggle(item, actions, 'omo-decisions__compact-menu-toggle generic-file-list__menu-toggle');
    if (!toggle) {
        return null;
    }

    menu.appendChild(toggle);

    return menu;
}

function buildDetailedActionMenuButton(item) {
    const actions = getDecisionDetailMenuActions(item);
    if (actions.length === 0) {
        return null;
    }

    const menu = document.createElement('div');
    menu.className = 'omo-decisions__compact-menu generic-menu';
    menu.setAttribute('data-omo-decision-compact-menu', '1');

    const toggle = buildDecisionActionMenuToggle(
        item,
        actions,
        'omo-decisions-card__action omo-decisions-card__menu-toggle'
    );
    if (!toggle) {
        return null;
    }

    menu.appendChild(toggle);
    return menu;
}

function appendDetailedActionMenuButton(card, item) {
    if (!(card instanceof Element)) {
        return;
    }

    const detailMenuButton = buildDetailedActionMenuButton(item);
    const actionsContainer = card.querySelector('.omo-decisions-card__actions');
    if (detailMenuButton && actionsContainer) {
        actionsContainer.appendChild(detailMenuButton);
    }
}

function renderCompactRow(item) {
    const article = document.createElement('article');
    article.className = 'omo-decisions__item-shell generic-file-list__item-shell';
    article.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));

    const primaryAction = resolveDecisionAutoOpenAction(item);
    const primaryActionUrl = String(primaryAction && primaryAction.url ? primaryAction.url : '').trim();
    const compactMenu = buildCompactActionMenu(item);
    const row = document.createElement(primaryActionUrl !== '' ? 'button' : 'div');
    const activityLabel = String(item && item.lastActivityLabel ? item.lastActivityLabel : (item && item.deadlineLabel ? item.deadlineLabel : '')).trim();
    const scopeLabel = String(item && item.holonLabel ? item.holonLabel : '').trim();

    if (compactMenu) {
        article.classList.add('generic-file-list__item-shell--with-menu');
    }

    row.className = 'omo-decisions__compact-row generic-file-list__row';
    if (primaryActionUrl !== '') {
        row.type = 'button';
        row.setAttribute('data-open-url', primaryActionUrl);
        row.setAttribute('data-open-title', String(item && item.title ? item.title : ''));
        row.setAttribute('data-open-mode', String(primaryAction && primaryAction.mode ? primaryAction.mode : 'default'));
    }

    row.innerHTML = ''
        + '<div class="generic-file-list__cell generic-file-list__cell--name" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderName ? payload.text.compactHeaderName : 'Decision') + '">'
            + '<div class="omo-decisions__compact-name-main generic-file-list__name-main">'
                + buildCompactAvatar(item && item.owner ? item.owner : {})
                + '<div class="omo-decisions__compact-title-block generic-file-list__title-block">'
                    + '<div class="generic-file-list__title-row">'
                        + '<span class="omo-decisions__compact-title-line"><strong class="generic-file-list__title">' + escapeHtml(item && item.title ? item.title : '') + '</strong>' + buildDecisionVisibilityIconHtml(item, 'omo-decisions__visibility-icon omo-decisions__visibility-icon--compact') + '</span>'
                    + '</div>'
                    + '<div class="omo-decisions__compact-meta generic-file-list__meta-line">' + buildCompactMetaLine(item) + '</div>'
                + '</div>'
            + '</div>'
        + '</div>'
        + '<div class="generic-file-list__cell" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderStatus ? payload.text.compactHeaderStatus : 'Statut') + '">'
            + '<span class="omo-decisions__compact-status">' + escapeHtml(item && item.statusLabel ? item.statusLabel : '') + '</span>'
        + '</div>'
        + '<div class="generic-file-list__cell" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderScope ? payload.text.compactHeaderScope : 'Structure') + '">'
            + '<span class="omo-decisions__compact-scope">' + escapeHtml(scopeLabel) + '</span>'
        + '</div>'
        + '<div class="generic-file-list__cell generic-file-list__cell--date" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderActivity ? payload.text.compactHeaderActivity : 'Activite') + '">'
            + '<span class="omo-decisions__compact-activity">' + escapeHtml(activityLabel) + '</span>'
        + '</div>';

    article.appendChild(row);
    if (compactMenu) {
        article.appendChild(compactMenu);
    }
    return article;
}

function renderDetailedList(items, groupMode) {
    const sortedItems = getSortedItems(items, groupMode === 'alpha' ? 'alpha' : 'time');
    const fragment = document.createDocumentFragment();

    if (groupMode === 'alpha') {
        const list = document.createElement('div');
        list.className = 'omo-decisions__card-list';
        sortedItems.forEach(function (item) {
            list.appendChild(renderCard(item));
        });
        fragment.appendChild(list);
        return fragment;
    }

    const itemsByGroupKey = new Map();
    sortedItems.forEach(function (item) {
        const groupKey = String(item && item.activityGroupKey ? item.activityGroupKey : 'too_far');
        if (!itemsByGroupKey.has(groupKey)) {
            itemsByGroupKey.set(groupKey, []);
        }
        itemsByGroupKey.get(groupKey).push(item);
    });

    getOrderedGroups().forEach(function (group) {
        const groupKey = String(group && group.key ? group.key : '');
        const groupItems = itemsByGroupKey.get(groupKey) || [];
        if (groupItems.length === 0) {
            return;
        }

        const section = document.createElement('section');
        section.className = 'omo-decisions__group omo-panel-group';

        const title = document.createElement('h3');
        title.className = 'generic-card-title generic-card-title--small generic-file-list__group-title';
        title.textContent = String(group && group.label ? group.label : '');

        const list = document.createElement('div');
        list.className = 'omo-decisions__card-list';
        groupItems.forEach(function (entry) {
            list.appendChild(renderCard(entry));
        });

        section.appendChild(title);
        section.appendChild(list);
        fragment.appendChild(section);
    });

    return fragment;
}

function renderCompactList(items, groupMode) {
    const sortedItems = getSortedItems(items, groupMode === 'alpha' ? 'alpha' : 'time');
    const fragment = document.createDocumentFragment();

    elements.list.classList.add('generic-file-list', 'generic-file-list--structured');
    elements.list.setAttribute('data-generic-file-list', '1');

    if (groupMode === 'alpha') {
        const section = document.createElement('section');
        section.className = 'omo-decisions__group generic-file-list__group';

        const table = document.createElement('div');
        table.className = 'generic-file-list__table';
        table.appendChild(buildCompactListHeader());
        sortedItems.forEach(function (item) {
            table.appendChild(renderCompactRow(item));
        });

        section.appendChild(table);
        fragment.appendChild(section);
        return fragment;
    }

    elements.list.classList.add('generic-file-list--stacked-sticky');

    const itemsByGroupKey = new Map();
    sortedItems.forEach(function (item) {
        const groupKey = String(item && item.activityGroupKey ? item.activityGroupKey : 'too_far');
        if (!itemsByGroupKey.has(groupKey)) {
            itemsByGroupKey.set(groupKey, []);
        }
        itemsByGroupKey.get(groupKey).push(item);
    });

    getOrderedGroups().forEach(function (group) {
        const groupKey = String(group && group.key ? group.key : '');
        const groupItems = itemsByGroupKey.get(groupKey) || [];
        if (groupItems.length === 0) {
            return;
        }

        const section = document.createElement('section');
        section.className = 'omo-decisions__group generic-file-list__group';

        const title = document.createElement('h3');
        title.className = 'generic-card-title generic-card-title--small generic-file-list__group-title';
        title.textContent = String(group && group.label ? group.label : '');

        const table = document.createElement('div');
        table.className = 'generic-file-list__table';
        table.appendChild(buildCompactListHeader());
        groupItems.forEach(function (entry) {
            table.appendChild(renderCompactRow(entry));
        });

        section.appendChild(title);
        section.appendChild(table);
        fragment.appendChild(section);
    });

    return fragment;
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

        const card = sharedCards.renderCard({
            title: String(item.title || ''),
            description: String(item.description || '').trim(),
            statusLabel: String(item.statusLabel || ''),
            titleIcon: {
                url: String(item.visibilityIconUrl || ''),
                label: String(item.visibilityLabel || '')
            },
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

        card.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));
        card.querySelectorAll('[data-open-url]').forEach(function (button, buttonIndex) {
            const action = Array.isArray(item && item.actions) ? item.actions[buttonIndex] : null;
            button.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));
            button.setAttribute('data-open-mode', String(action && action.mode ? action.mode : 'default'));
        });
        appendDetailedActionMenuButton(card, item);
        return card;
    }

    const article = document.createElement('article');
    article.className = 'omo-decisions-card generic-section generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed';
    article.setAttribute('data-generic-accordion', '1');
    article.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));

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
        ownerAvatarHtml = '<img src="' + escapeHtml(ownerPhotoUrl) + '" alt="' + escapeHtml(ownerName !== '' ? ownerName : ownerInitials) + '" class="omo-decisions-card__owner-photo" width="42" height="42" decoding="async">';
    } else {
        ownerAvatarHtml = '<span class="omo-decisions-card__owner-placeholder">' + escapeHtml(ownerInitials) + '</span>';
    }

    let actionsHtml = '<div class="omo-decisions-card__actions">';
    actions.forEach(function (action) {
        const actionVariant = String(action && action.variant ? action.variant : 'secondary');
        actionsHtml += '<button type="button" class="generic-action-button generic-action-button--' + escapeHtml(actionVariant) + ' omo-decisions-card__action" data-omo-decision-id="' + escapeHtml(String(Number(item && item.id ? item.id : 0) || 0)) + '" data-open-url="' + escapeHtml(action && action.url ? action.url : '') + '" data-open-mode="' + escapeHtml(String(action && action.mode ? action.mode : 'default')) + '">' + escapeHtml(action && action.label ? action.label : '') + '</button>';
    });
    actionsHtml += '</div>';

    article.innerHTML = '<div class="omo-decisions-card__header generic-accordion__header">'
        + '<button type="button" class="omo-decisions-card__summary" data-generic-accordion-toggle aria-expanded="false">'
            + '<span class="omo-decisions-card__owner-avatar">' + ownerAvatarHtml + '</span>'
            + '<span class="omo-decisions-card__summary-copy">'
                + '<span class="omo-decisions-card__summary-top">'
                    + '<span class="omo-decisions-card__title-line"><span class="omo-decisions-card__title generic-title generic-title--item">' + escapeHtml(item.title || '') + '</span>' + buildDecisionVisibilityIconHtml(item, 'omo-decisions-card__title-icon') + '</span>'
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
            + (description !== '' ? '<p class="omo-decisions-card__description generic-description">' + escapeHtml(description) + '</p>' : '')
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

    appendDetailedActionMenuButton(article, item);

    return article;
}

function buildStateCardHtml(title, text, buttonLabel, buttonUrl) {
    let html = '<div class="omo-decisions__state-content">';
    if (title) {
        html += '<h3 class="omo-decisions__empty-title generic-title generic-title--card">' + escapeHtml(title) + '</h3>';
    }
    if (text) {
        html += '<p class="omo-decisions__empty-text generic-description generic-description--relaxed">' + escapeHtml(text) + '</p>';
    }
    if (buttonLabel && buttonUrl) {
        html += '<button type="button" class="generic-action-button generic-action-button--main" data-open-url="' + escapeHtml(buttonUrl) + '">' + escapeHtml(buttonLabel) + '</button>';
    }
    html += '</div>';
    return html;
}

function renderNoResults() {
    return '<h3 class="omo-decisions__no-results-title generic-title generic-title--card">' + escapeHtml(payload.text.noResultsTitle || '') + '</h3>'
        + '<p class="omo-decisions__no-results-text generic-description generic-description--relaxed">' + escapeHtml(payload.text.noResultsText || '') + '</p>';
}

function renderList() {
    omoDecisionDebugLog('renderList:start', {
        payloadItemCount: Array.isArray(payload.items) ? payload.items.length : null,
        status: state.status,
        search: state.search,
        type: state.type,
        method: state.method,
        sort: state.sort,
        density: state.density
    });

    syncAdvancedFiltersVisibility();
    resetStateContainer();
    setVisible(elements.list, false);
    root.setAttribute('data-omo-decisions-initialized', '0');

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
        root.setAttribute('data-omo-decisions-initialized', '1');
        omoDecisionDebugLog('renderList:end-empty', {
            initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
            filteredItemCount: 0
        });
        return;
    }

    if (filteredItems.length === 0) {
        showStateContainer(renderNoResults(), 'default');
        root.setAttribute('data-omo-decisions-initialized', '1');
        omoDecisionDebugLog('renderList:end-no-results', {
            initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
            filteredItemCount: filteredItems.length
        });
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
    root.setAttribute('data-omo-decisions-initialized', '1');
    omoDecisionDebugLog('renderList:end', {
        filteredItemCount: filteredItems.length,
        listChildCount: elements.list ? elements.list.childElementCount : null,
        initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
        listHidden: elements.list ? elements.list.hidden : null,
        stateHidden: elements.state ? elements.state.hidden : null,
        drawerOpen: Boolean(root.closest('.drawer.open')),
        drawerId: root.closest('.drawer') ? String(root.closest('.drawer').id || '') : ''
    });
}

function resetFilters() {
    state.status = 'active';
    state.search = '';
    state.type = 'all';
    state.method = 'all';
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
}

const ownerDocument = root.ownerDocument || document;
let floatingCompactMenu = ownerDocument.querySelector('[data-omo-decision-floating-menu="1"]');
if (!floatingCompactMenu) {
    floatingCompactMenu = ownerDocument.createElement('div');
    floatingCompactMenu.className = 'omo-decisions__menu-panel generic-menu-panel generic-menu-panel--floating omo-decisions__menu-panel--floating';
    floatingCompactMenu.setAttribute('data-omo-decision-floating-menu', '1');
    floatingCompactMenu.setAttribute('role', 'menu');
    floatingCompactMenu.hidden = true;
    ownerDocument.body.appendChild(floatingCompactMenu);
}

let activeCompactMenuToggle = null;

function buildCompactMenuItem(action, title, description) {
    const button = ownerDocument.createElement('button');
    const behavior = String(action && action.behavior ? action.behavior : '').trim();
    const requestPayload = action && action.requestPayload && typeof action.requestPayload === 'object'
        ? action.requestPayload
        : {};
    button.type = 'button';
    button.className = 'omo-decisions__menu-item generic-menu-item';
    if (String(action && action.variant ? action.variant : '').trim() === 'danger') {
        button.classList.add('omo-decisions__menu-item--danger', 'generic-menu-item--danger');
    }
    button.setAttribute('data-omo-decision-compact-menu-action', '1');
    button.setAttribute('role', 'menuitem');
    button.setAttribute(
        'data-omo-decision-menu-behavior',
        behavior === 'mutation' || behavior === 'export' || behavior === 'window' || behavior === 'modal' ? behavior : 'open'
    );
    if (behavior === 'mutation') {
        button.setAttribute('data-omo-decision-menu-request-url', String(action && action.requestUrl ? action.requestUrl : ''));
        button.setAttribute('data-omo-decision-menu-request-payload', JSON.stringify(requestPayload));
        button.setAttribute('data-omo-decision-menu-confirm', String(action && action.confirmMessage ? action.confirmMessage : ''));
    } else if (behavior === 'export') {
        button.setAttribute('data-omo-decision-menu-export-url', String(action && action.exportUrl ? action.exportUrl : ''));
        button.setAttribute('data-omo-decision-menu-export-formats', JSON.stringify(Array.isArray(action && action.exportFormats) ? action.exportFormats : []));
        button.setAttribute('data-open-title', String(action && action.title ? action.title : title || ''));
        button.setAttribute('data-open-description', String(description || ''));
    } else {
        button.setAttribute('data-open-url', String(action && action.url ? action.url : ''));
        button.setAttribute('data-open-title', String(action && action.title ? action.title : title || ''));
        button.setAttribute('data-open-description', String(description || ''));
        button.setAttribute('data-open-mode', String(action && action.mode ? action.mode : 'default'));
    }
    button.textContent = String(action && action.label ? action.label : '');
    return button;
}

function parseCompactMenuActions(toggle) {
    if (!(toggle instanceof Element)) {
        return [];
    }

    try {
        const parsed = JSON.parse(String(toggle.getAttribute('data-omo-decision-compact-menu-actions') || '[]'));
        return Array.isArray(parsed)
            ? parsed.filter(isDecisionMenuActionUsable)
            : [];
    } catch (error) {
        return [];
    }
}

function populateCompactMenu(toggle) {
    const actions = parseCompactMenuActions(toggle);
    const title = String(toggle && toggle.getAttribute('data-omo-decision-compact-menu-title') || '').trim();
    const description = String(toggle && toggle.getAttribute('data-omo-decision-compact-menu-description') || '').trim();
    const fragment = ownerDocument.createDocumentFragment();

    actions.forEach(function (action) {
        fragment.appendChild(buildCompactMenuItem(action, title, description));
    });

    floatingCompactMenu.replaceChildren(fragment);
}

function positionCompactMenu(toggle) {
    if (!(toggle instanceof Element) || !toggle.isConnected) {
        closeCompactMenus();
        return;
    }

    floatingCompactMenu.hidden = false;
    floatingCompactMenu.style.visibility = 'hidden';
    floatingCompactMenu.style.top = '0px';
    floatingCompactMenu.style.left = '0px';

    const toggleRect = toggle.getBoundingClientRect();
    const menuRect = floatingCompactMenu.getBoundingClientRect();
    const viewportPadding = 12;
    const gap = 8;
    let top = toggleRect.bottom + gap;
    let left = toggleRect.right - menuRect.width;

    if (top + menuRect.height > window.innerHeight - viewportPadding) {
        top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
    }

    if (left + menuRect.width > window.innerWidth - viewportPadding) {
        left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
    }

    if (left < viewportPadding) {
        left = viewportPadding;
    }

    floatingCompactMenu.style.top = String(Math.round(top)) + 'px';
    floatingCompactMenu.style.left = String(Math.round(left)) + 'px';
    floatingCompactMenu.style.visibility = '';
}

function closeCompactMenus() {
    root.querySelectorAll('[data-omo-decision-compact-menu="1"]').forEach(function (menu) {
        menu.classList.remove('is-open');
    });

    root.querySelectorAll('[data-omo-decision-compact-menu-toggle="1"]').forEach(function (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
    });

    activeCompactMenuToggle = null;
    floatingCompactMenu.hidden = true;
    floatingCompactMenu.style.visibility = '';
    floatingCompactMenu.replaceChildren();
}

function openCompactMenu(toggle) {
    const parentMenu = toggle ? toggle.closest('[data-omo-decision-compact-menu="1"]') : null;
    const shouldOpen = !!toggle && (!activeCompactMenuToggle || activeCompactMenuToggle !== toggle || floatingCompactMenu.hidden);

    closeCompactMenus();

    if (!toggle || !parentMenu || !shouldOpen) {
        return;
    }

    populateCompactMenu(toggle);
    if (!floatingCompactMenu.childElementCount) {
        return;
    }

    activeCompactMenuToggle = toggle;
    parentMenu.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    positionCompactMenu(toggle);
}

function parseDecisionMenuRequestPayload(button) {
    if (!(button instanceof Element)) {
        return {};
    }

    try {
        const parsed = JSON.parse(String(button.getAttribute('data-omo-decision-menu-request-payload') || '{}'));
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
        return {};
    }
}

function parseDecisionMenuExportFormats(button) {
    if (!(button instanceof Element)) {
        return [];
    }

    try {
        const parsed = JSON.parse(String(button.getAttribute('data-omo-decision-menu-export-formats') || '[]'));
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
}

function buildDecisionExportModalHtml(title, exportUrl, formats) {
    const safeTitle = String(title || '').trim();
    const safeExportUrl = String(exportUrl || '').trim();
    const safeFormats = Array.isArray(formats) ? formats : [];
    let html = '<div class="generic-section generic-section--stack">';

    if (payload.text && payload.text.exportModalIntro) {
        html += '<p>' + escapeHtml(payload.text.exportModalIntro) + '</p>';
    }

    safeFormats.forEach(function (format) {
        const key = String(format && format.key ? format.key : '').trim();
        const label = String(format && format.label ? format.label : key.toUpperCase()).trim();
        const description = String(format && format.description ? format.description : '').trim();
        const available = Boolean(format && format.available);

        html += '<section class="generic-soft-panel generic-soft-panel--stack">';
        html += '<h3 class="generic-card-title generic-card-title--small">' + escapeHtml(label) + '</h3>';
        if (description !== '') {
            html += '<p>' + escapeHtml(description) + '</p>';
        }

        if (available && safeExportUrl !== '' && key !== '') {
            html += '<button type="button" class="generic-action-button generic-action-button--main"'
                + ' data-omo-decision-export-format-button="1"'
                + ' data-omo-decision-export-url="' + escapeHtml(safeExportUrl) + '"'
                + ' data-omo-decision-export-format="' + escapeHtml(key) + '"'
                + ' data-omo-decision-export-title="' + escapeHtml(safeTitle) + '"'
                + '>'
                + escapeHtml(payload.text && payload.text.exportOpenLabel ? payload.text.exportOpenLabel : 'Telecharger')
                + '</button>';
        } else {
            html += '<button type="button" class="generic-action-button generic-action-button--secondary" disabled>'
                + escapeHtml(payload.text && payload.text.exportComingSoonLabel ? payload.text.exportComingSoonLabel : 'Bientot disponible')
                + '</button>';
        }

        html += '</section>';
    });

    html += '</div>';
    return html;
}

function openDecisionExportModal(button) {
    if (!(button instanceof Element) || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    const exportUrl = String(button.getAttribute('data-omo-decision-menu-export-url') || '').trim();
    if (exportUrl === '') {
        return;
    }

    const title = String(button.getAttribute('data-open-title') || '').trim()
        || (payload.text && payload.text.exportModalTitle ? payload.text.exportModalTitle : 'Exporter ce scrutin');
    const formats = parseDecisionMenuExportFormats(button);

    window.commonTopbarOpenModal(
        payload.text && payload.text.exportModalTitle ? payload.text.exportModalTitle : 'Exporter ce scrutin',
        buildDecisionExportModalHtml(title, exportUrl, formats),
        'html'
    );
}

function startDecisionExportDownload(button) {
    if (!(button instanceof Element)) {
        return;
    }

    const exportUrl = String(button.getAttribute('data-omo-decision-export-url') || '').trim();
    const format = String(button.getAttribute('data-omo-decision-export-format') || '').trim();
    if (exportUrl === '' || format === '') {
        return;
    }

    let resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(exportUrl)
        : exportUrl;

    try {
        const url = new URL(resolvedUrl, window.location.origin);
        url.searchParams.set('format', format);
        resolvedUrl = url.toString();
    } catch (error) {
        resolvedUrl += (resolvedUrl.indexOf('?') === -1 ? '?' : '&') + 'format=' + encodeURIComponent(format);
    }

    if (typeof window.commonTopbarCloseModal === 'function') {
        window.commonTopbarCloseModal();
    }

    window.open(resolvedUrl, '_blank', 'noopener');
}

function submitDecisionMenuAction(button) {
    if (!(button instanceof Element)) {
        return Promise.resolve(false);
    }

    const requestUrl = String(button.getAttribute('data-omo-decision-menu-request-url') || '').trim();
    if (requestUrl === '') {
        return Promise.resolve(false);
    }

    const confirmMessage = String(button.getAttribute('data-omo-decision-menu-confirm') || '').trim();
    if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
        return Promise.resolve(false);
    }

    const formData = new FormData();
    const requestPayload = parseDecisionMenuRequestPayload(button);
    Object.keys(requestPayload).forEach(function (key) {
        const value = requestPayload[key];
        if (value === null || typeof value === 'undefined') {
            return;
        }

        formData.append(key, String(value));
    });

    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(requestUrl)
        : requestUrl;

    button.disabled = true;
    closeCompactMenus();

    return fetch(resolvedUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || !result.data.status) {
                const message = result.data && result.data.message
                    ? String(result.data.message)
                    : String(payload.text && payload.text.actionErrorUpdate ? payload.text.actionErrorUpdate : 'Impossible de mettre a jour cette prise de decision pour le moment.');
                throw new Error(message);
            }

            return omoDecisionRefreshIndex({ silent: false });
        })
        .catch(function (error) {
            window.omoNotify(error && error.message ? error.message : (payload.text && payload.text.actionErrorUpdate ? payload.text.actionErrorUpdate : 'Impossible de mettre a jour cette prise de decision pour le moment.'), 'error');
            return false;
        })
        .finally(function () {
            button.disabled = false;
        });
}

try {
    omoDecisionDebugLog('bootstrap:beforePopulate', {
        typeOptionCount: Array.isArray(payload.typeOptions) ? payload.typeOptions.length : null,
        methodOptionCount: Array.isArray(payload.methodOptions) ? payload.methodOptions.length : null,
    });

    populateSelect(elements.type, payload.typeOptions);
    populateSelect(elements.method, payload.methodOptions);
    state.type = restoreSelectValue(elements.type, state.type);
    state.method = restoreSelectValue(elements.method, state.method);
    omoDecisionsRenderChoiceButtons(elements.typeChoices, payload.typeOptions, 'data-omo-decisions-type-choice', state.type);
    omoDecisionsRenderChoiceButtons(elements.methodChoices, payload.methodOptions, 'data-omo-decisions-method-choice', state.method);
    if (elements.search) {
        elements.search.value = state.search;
    }
    renderList();
    omoDecisionDebugLog('bootstrap:afterRender', {
        initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
        listChildCount: elements.list ? elements.list.childElementCount : null
    });
    if (!omoDecisionsInitializeViewFilter()) {
        window.setTimeout(openInitialDecisionFromPayload, 0);
    }
} catch (error) {
    root.setAttribute('data-omo-decisions-initialized', '0');
    omoDecisionDebugError('bootstrap:error', error, {
        initialized: String(root.getAttribute('data-omo-decisions-initialized') || '')
    });
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

if (elements.statusTabs) {
    elements.statusTabs.addEventListener('scroll', syncStatusTabsOverflow, { passive: true });
}

if (elements.statusScrollPrev) {
    elements.statusScrollPrev.addEventListener('click', function () {
        scrollStatusTabs(-1);
    });
}

if (elements.statusScrollNext) {
    elements.statusScrollNext.addEventListener('click', function () {
        scrollStatusTabs(1);
    });
}

omoDecisionRegisterGlobalListener(ownerDocument, 'click', function (event) {
    const toggle = event.target.closest('[data-omo-decision-compact-menu-toggle="1"]');
    if (toggle) {
        return;
    }

    const actionButton = event.target.closest('[data-omo-decision-compact-menu-action="1"]');
    if (actionButton && floatingCompactMenu.contains(actionButton)) {
        event.preventDefault();
        event.stopPropagation();

        const behavior = String(actionButton.getAttribute('data-omo-decision-menu-behavior') || 'open').trim();
        if (behavior === 'mutation') {
            submitDecisionMenuAction(actionButton);
            return;
        }

        if (behavior === 'export') {
            openDecisionExportModal(actionButton);
            closeCompactMenus();
            return;
        }

        if (behavior === 'window') {
            const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
            if (targetUrl !== '') {
                const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
                    ? window.omoResolveAppUrl(targetUrl)
                    : targetUrl;
                window.open(resolvedUrl, '_blank', 'noopener');
            }

            closeCompactMenus();
            return;
        }

        if (behavior === 'modal') {
            const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
            if (targetUrl !== '' && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(
                    String(actionButton.getAttribute('data-open-title') || '').trim()
                        || String(payload.text && payload.text.moveModalTitle ? payload.text.moveModalTitle : 'Déplacer la prise de décision'),
                    targetUrl,
                    'fetch'
                );
            }

            closeCompactMenus();
            return;
        }

        const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
        if (targetUrl !== '') {
            openDecisionFromInteraction(
                activeCompactMenuToggle ? Number(activeCompactMenuToggle.getAttribute('data-omo-decision-id') || 0) : 0,
                targetUrl,
                String(actionButton.getAttribute('data-open-title') || '').trim() || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision'),
                String(actionButton.getAttribute('data-open-description') || '').trim(),
                String(actionButton.getAttribute('data-open-mode') || '').trim()
            );
        }

        closeCompactMenus();
        return;
    }

    const exportButton = event.target.closest('[data-omo-decision-export-format-button="1"]');
    if (exportButton) {
        event.preventDefault();
        startDecisionExportDownload(exportButton);
        return;
    }

    if (!event.target.closest('[data-omo-decision-floating-menu="1"]')) {
        closeCompactMenus();
    }
});

omoDecisionRegisterGlobalListener(ownerDocument, 'keydown', function (event) {
    if (event.key === 'Escape' && !floatingCompactMenu.hidden) {
        closeCompactMenus();
    }
});

omoDecisionRegisterGlobalListener(ownerDocument, 'scroll', function () {
    if (!activeCompactMenuToggle || floatingCompactMenu.hidden) {
        return;
    }

    positionCompactMenu(activeCompactMenuToggle);
}, true);

omoDecisionRegisterGlobalListener(window, 'resize', function () {
    if (!activeCompactMenuToggle || floatingCompactMenu.hidden) {
        return;
    }

    positionCompactMenu(activeCompactMenuToggle);
});

omoDecisionRegisterGlobalListener(window, 'resize', syncStatusTabsOverflow);
omoDecisionRegisterGlobalListener(window, 'omo-decision-moved', function () {
    omoDecisionRefreshIndex({ silent: false });
});

root.querySelectorAll('[data-omo-decisions-filter-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        if (decisionFilterPanelOpen) {
            omoDecisionsCloseFilterPanel(true, false);
        } else {
            omoDecisionsOpenFilterPanel();
        }
    });
});

if (elements.viewFilterPanel) {
    elements.viewFilterPanel.addEventListener('click', function (event) {
        const moreToggle = event.target.closest('[data-omo-decisions-filter-more-toggle]');
        if (moreToggle) {
            event.preventDefault();
            event.stopPropagation();
            const moreMenu = moreToggle.closest('[data-omo-decisions-filter-more-menu]');
            const morePanel = moreMenu ? moreMenu.querySelector('[data-omo-decisions-filter-more-panel]') : null;
            const isMoreMenuOpen = !!morePanel && !morePanel.hidden;
            omoDecisionsCloseFilterMoreMenu();
            if (!isMoreMenuOpen && morePanel) {
                morePanel.hidden = false;
                moreMenu.classList.add('is-open');
                moreToggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }
        const moreAction = event.target.closest('[data-omo-decisions-filter-more-action]');
        if (moreAction) {
            event.preventDefault();
            event.stopPropagation();
            omoDecisionsApplyFilterMoreAction(
                moreAction.getAttribute('data-omo-decisions-filter-more-action') || ''
            );
            return;
        }
        if (event.target.closest('[data-omo-decisions-filter-apply]')) {
            event.preventDefault();
            omoDecisionsCloseFilterPanel(true, false);
            return;
        }
        if (event.target.closest('[data-omo-decisions-filter-save]')) {
            event.preventDefault();
            omoDecisionsCloseFilterPanel(true, true);
            return;
        }
        const scopeButton = event.target.closest('[data-omo-decision-scope-toggle]');
        if (scopeButton && pendingViewFilters) {
            pendingViewFilters.scope = omoDecisionsNormalizeScopePreference(
                scopeButton.getAttribute('data-omo-decision-scope-toggle')
            );
            omoDecisionsSyncFilterChoices();
            return;
        }
        const typeButton = event.target.closest('[data-omo-decisions-type-choice]');
        if (typeButton && pendingViewFilters) {
            pendingViewFilters.type = String(typeButton.getAttribute('data-omo-decisions-type-choice') || 'all');
            omoDecisionsSyncFilterChoices();
            return;
        }
        const methodButton = event.target.closest('[data-omo-decisions-method-choice]');
        if (methodButton && pendingViewFilters) {
            pendingViewFilters.method = String(methodButton.getAttribute('data-omo-decisions-method-choice') || 'all');
            omoDecisionsSyncFilterChoices();
        }
    });
}

root.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && decisionFilterPanelOpen) {
        omoDecisionsCloseFilterPanel(false, false);
    }
});

root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
    button.addEventListener('click', function () {
        const nextSort = omoDecisionsNormalizeSortPreference(
            button.getAttribute('data-omo-decisions-sort')
        );

        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.sort = nextSort;
            omoDecisionsSyncFilterChoices();
            return;
        }

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

        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.density = nextDensity;
            omoDecisionsSyncFilterChoices();
            return;
        }

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

if (!root.__omoDecisionsRouteHandler) {
    root.__omoDecisionsRouteHandler = function (routeEvent) {
        if (!document.body.contains(root)) {
            return;
        }

        const detail = routeEvent && routeEvent.detail
            ? routeEvent.detail
            : {};
        const targetDecisionId = Number(detail.decisionId || 0);
        const targetMode = normalizeDecisionOpenMode(detail.mode || 'default');

        if (targetDecisionId > 0) {
            if (!openDecisionItemById(targetDecisionId, { mode: targetMode })) {
                omoDecisionCloseNestedEditorDrawer({
                    force: true,
                    refreshIndex: false
                });
            }
            return;
        }

        omoDecisionCloseNestedEditorDrawer({
            force: true,
            refreshIndex: false
        });
    };

    window.addEventListener('omo-decisions-route-change', root.__omoDecisionsRouteHandler);
}

if (elements.statusTabs) {
    elements.statusTabs.addEventListener('click', function (event) {
        const button = event.target.closest('[data-status]');
        if (!button) {
            return;
        }

        const nextStatus = String(button.getAttribute('data-status') || 'all');
        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.status = nextStatus;
            omoDecisionsSyncFilterChoices();
            return;
        }
        state.status = nextStatus;
        renderList();
    });
}

if (elements.search) {
    elements.search.addEventListener('input', function () {
        state.search = String(elements.search.value || '');
        omoDecisionsWriteSearch(state.search);
        renderList();
    });
}

if (elements.type) {
    elements.type.addEventListener('change', function () {
        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.type = String(elements.type.value || 'all');
            omoDecisionsSyncFilterChoices();
            return;
        }
        state.type = String(elements.type.value || 'all');
        renderList();
    });
}

if (elements.method) {
    elements.method.addEventListener('change', function () {
        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.method = String(elements.method.value || 'all');
            omoDecisionsSyncFilterChoices();
            return;
        }
        state.method = String(elements.method.value || 'all');
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

    const compactMenuToggle = event.target.closest('[data-omo-decision-compact-menu-toggle="1"]');
    if (compactMenuToggle) {
        event.preventDefault();
        event.stopPropagation();
        openCompactMenu(compactMenuToggle);
        return;
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
    const decisionContainer = button.closest('[data-omo-decision-id]');
    const decisionId = decisionContainer
        ? Number(decisionContainer.getAttribute('data-omo-decision-id') || 0)
        : Number(button.getAttribute('data-omo-decision-id') || 0);
    const targetMode = String(button.getAttribute('data-open-mode') || '').trim();

    openDecisionFromInteraction(
        decisionId,
        targetUrl,
        targetTitle !== '' ? targetTitle : (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision'),
        String(button.getAttribute('data-open-description') || '').trim(),
        targetMode
    );
    event.preventDefault();
    return;

    openDecisionEditor(
        targetUrl,
        targetTitle !== '' ? targetTitle : (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision'),
    );
    event.preventDefault();
});

const omoDecisionIndexTeardown = function () {
    closeCompactMenus();

    if (root.__omoDecisionsRouteHandler) {
        window.removeEventListener('omo-decisions-route-change', root.__omoDecisionsRouteHandler);
        root.__omoDecisionsRouteHandler = null;
    }

    while (omoDecisionIndexGlobalCleanupCallbacks.length > 0) {
        const cleanup = omoDecisionIndexGlobalCleanupCallbacks.pop();
        if (typeof cleanup !== 'function') {
            continue;
        }

        try {
            cleanup();
        } catch (error) {
            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn('[OMO][Decisions][panel] cleanup failed', error);
            }
        }
    }

    if (window.omoDecisionIndexTeardown === omoDecisionIndexTeardown) {
        window.omoDecisionIndexTeardown = null;
    }
};

window.omoDecisionIndexTeardown = omoDecisionIndexTeardown;
})();
</script>
