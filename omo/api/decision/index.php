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
            'text' => 'Espace introuvable pour cette organisation.',
        'context' => 'Error message when the requested holon context cannot be loaded.',
    ],
    'decisions.index.context.holon_denied' => [
            'text' => 'Accès refusé à cet espace.',
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
    'decisions.index.action.duplicate' => [
        'text' => 'Dupliquer',
        'context' => 'Menu action opening a prefilled, unsaved copy of one decision.',
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

    $canManage = $decision->canUseManagementPermission('CAN_EDIT_DECISION', $currentUserId);
    $canDelete = $decision->canUseManagementPermission('CAN_DELETE_DECISION', $currentUserId);

    $canView = $canManage
        || $canDelete
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
        if ($canCreateDecision) {
            $menuActions[] = [
                'label' => t('decisions.index.action.duplicate', [], $lang, $sourceLang),
                'behavior' => 'direct',
                'url' => '/omo/api/decision/edit.php?' . http_build_query([
                    'oid' => $currentOrganizationId,
                    'cid' => $normalizedCurrentHolonId > 0 ? $normalizedCurrentHolonId : $holonId,
                    'duplicate_id' => $decisionId,
                ]),
                'title' => t('decisions.index.action.duplicate', [], $lang, $sourceLang),
            ];
        }

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

    if (($canManage || $canDelete) && $actionUrl !== '') {
        $canDeleteDecision = !$hasSubmittedResponses
            && $status !== DecisionProcess::STATUS_RESULTS
            && $status !== DecisionProcess::STATUS_ARCHIVED;

        if ($canDeleteDecision && $canDelete) {
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
        } elseif (!$canDeleteDecision && $canManage) {
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
                        <?php if (!empty($applicationViewPreferences['canSavePersonal']) || !empty($applicationViewPreferences['canSaveTemporary'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary"<?= !empty($applicationViewPreferences['canSavePersonal']) ? ' data-omo-decisions-filter-save' : '' ?> data-omo-app-view-save-scope="<?= $escape($applicationViewPreferences['primarySaveScope']) ?>"><?= $escape(t('decisions.index.view_filter.save', [], $lang, $sourceLang)) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= $escape($applicationViewPreferences['primarySaveScope']) ?>"><?= $escape($applicationViewPreferences['primarySaveLabel'] ?? '') ?></button>
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

<script src="/common/drawer/subdrawer.js?v=20260906-slide-right"></script>
<script src="/omo/assets/js/application-view-preferences.js?v=20260917-filter-hierarchy"></script>
<link rel="stylesheet" href="/common/choice/decision_cards.css?v=20260923-compact-editor">
<script src="/common/choice/decision_cards.js"></script>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/decision/index.css') ?>">

<script src="<?= commonAssetUrl('/omo/api/decision/index.js') ?>"></script>
