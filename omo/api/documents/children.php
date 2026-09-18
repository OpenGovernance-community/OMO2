<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/list_entries.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;
use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$sourceLang = omoDocumentsGetListGroupSourceLang();
$lang = omoLoadTranslationBundle('omo_documents_index', $sourceLang);
$translate = static function (string $key) use (&$lang, &$sourceLang): string {
    return t($key, array(), $lang, $sourceLang);
};

$folderId = (int)($_GET['id'] ?? 0);
$organizationId = (int)($_GET['oid'] ?? $_SESSION['currentOrganization'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? 0);
$requestedDocumentScope = $_GET['document_scope'] ?? 'contextual';
$isPvApplicationTab = (int)($_GET['is_pv_application_tab'] ?? 0) === 1;
$currentUserId = (int)commonGetCurrentUserId();

$organization = new Organization();
if (
    $folderId <= 0
    || $organizationId <= 0
    || $currentUserId <= 0
    || !commonUserHasOrganizationAccess($currentUserId, $organizationId)
    || !$organization->load($organizationId)
) {
    http_response_code(403);
    echo json_encode(array('status' => false, 'message' => 'Acces refuse.'));
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$currentContextHolon = null;
if ($contextHolonId > 0) {
    $candidateHolon = new Holon();
    if (
        $candidateHolon->load($contextHolonId)
        && $organization->containsHolon($candidateHolon)
        && $candidateHolon->canViewDetail()
    ) {
        $currentContextHolon = $candidateHolon;
    }
}

if (!($currentContextHolon instanceof Holon) && $rootHolon instanceof Holon) {
    $currentContextHolon = $rootHolon;
}

$effectiveCurrentHolonId = $currentContextHolon instanceof Holon ? (int)$currentContextHolon->getId() : 0;
$availableDocumentScopes = omoApiGetAvailableContextScopes(
    $rootHolon instanceof Holon,
    $currentContextHolon,
    $rootHolon
);
$documentScope = omoApiNormalizeContextScope($requestedDocumentScope, $availableDocumentScopes);
$scopeHolonIds = $documentScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentContextHolon)
    : omoApiGetDescendantHolonIds($currentContextHolon);

$documents = new \dbObject\ArrayDocument();
$documentVisibilityRuleMap = $documents->loadVisibleForOrganizationContext(
    $organizationId,
    $effectiveCurrentHolonId,
    $documentScope,
    $scopeHolonIds
);
$folder = null;
$childDocuments = array();
foreach ($documents as $document) {
    if (!($document instanceof Document)) {
        continue;
    }

    if ((int)$document->getId() === $folderId && $document->isFolder()) {
        $folder = $document;
    }
    if ((int)$document->get('IDdocument_parent') === $folderId) {
        $childDocuments[] = $document;
    }
}

if (!($folder instanceof Document)) {
    http_response_code(404);
    echo json_encode(array('status' => false, 'message' => 'Dossier introuvable.'));
    exit;
}

$childDocumentIds = array_values(array_map(static function (Document $document): int {
    return (int)$document->getId();
}, $childDocuments));
$documentEditVisibilityRuleMap = count($childDocumentIds) > 0
    ? ObjectVisibility::loadActiveRuleRows(Document::getEditVisibilityObjectType(), $childDocumentIds, $organizationId)
    : array();
$documentListMetadata = \dbObject\ArrayDocument::loadListMetadataForOrganization($organizationId);
$pvEventsById = array();
$pvEventIds = array();
foreach ($childDocuments as $document) {
    if ($document->isPvDocument() && (int)$document->get('IDevent') > 0) {
        $pvEventIds[(int)$document->get('IDevent')] = (int)$document->get('IDevent');
    }
}
if (count($pvEventIds) > 0) {
    $pvEvents = new \dbObject\ArrayEvent();
    $pvEvents->load(array(
        'where' => array(array('field' => 'id', 'op' => 'in', 'value' => array_values($pvEventIds))),
        'hydrate' => true,
    ));
    foreach ($pvEvents as $pvEvent) {
        if ($pvEvent instanceof \dbObject\Event && (int)$pvEvent->getId() > 0) {
            $pvEventsById[(int)$pvEvent->getId()] = $pvEvent;
        }
    }
}

$today = new DateTimeImmutable('today');
$groups = sharedGetRelativeDateGroups($today, array(
    'today' => $translate('documents.group.today'),
    'yesterday' => $translate('documents.group.yesterday'),
    'this_week' => $translate('documents.group.this_week'),
    'last_week' => $translate('documents.group.last_week'),
    'this_month' => $translate('documents.group.this_month'),
    'last_month' => $translate('documents.group.last_month'),
    'this_year' => $translate('documents.group.this_year'),
    'earlier' => $translate('documents.group.earlier'),
    'too_far' => $translate('documents.group.too_far'),
));
$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;
if ($formatter instanceof IntlDateFormatter) {
    $formatter->setPattern('d MMM');
}
$formatterWithYear = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;
if ($formatterWithYear instanceof IntlDateFormatter) {
    $formatterWithYear->setPattern('d MMM y');
}
$formatDate = static function ($value, bool $includeYear = false) use ($formatter, $formatterWithYear): string {
    if (!$value instanceof DateTimeInterface) {
        return '';
    }
    $selectedFormatter = $includeYear ? $formatterWithYear : $formatter;
    if ($selectedFormatter instanceof IntlDateFormatter) {
        $formatted = $selectedFormatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }
    return $value->format($includeYear ? 'd.m.Y' : 'd.m');
};
$documentVisibilityIconMap = array(
    ObjectVisibility::TYPE_EVERYONE => '/omo/assets/images/documents/visibility/everyone.png',
    ObjectVisibility::TYPE_ORGANIZATION => '/omo/assets/images/documents/visibility/organization.png',
    ObjectVisibility::TYPE_CIRCLE => '/omo/assets/images/documents/visibility/circle.png',
    ObjectVisibility::TYPE_ROLE => '/omo/assets/images/documents/visibility/role.png',
    ObjectVisibility::TYPE_SELF => '/omo/assets/images/documents/visibility/me.png',
);
$resolveDocumentVisibilityIconUrl = static function (string $visibilityType) use ($documentVisibilityIconMap): string {
    $normalizedVisibilityType = ObjectVisibility::normalizeVisibilityType($visibilityType);
    return (string)($documentVisibilityIconMap[$normalizedVisibilityType] ?? $documentVisibilityIconMap[ObjectVisibility::TYPE_ORGANIZATION]);
};

$entries = omoDocumentsBuildListEntries($childDocuments, array(
    'organizationId' => $organizationId,
    'currentUserId' => $currentUserId,
    'organization' => $organization,
    'documentScope' => $documentScope,
    'visibilityRuleMap' => $documentVisibilityRuleMap,
    'editVisibilityRuleMap' => $documentEditVisibilityRuleMap,
    'documentListMetadata' => $documentListMetadata,
    'pvEventsById' => $pvEventsById,
    'documentViewerContext' => ObjectVisibility::buildCurrentViewerContext($organizationId, $currentUserId),
    'isPvApplicationTab' => $isPvApplicationTab,
    'groups' => $groups,
    'today' => $today,
    'formatDate' => $formatDate,
    'resolveDocumentVisibilityIconUrl' => $resolveDocumentVisibilityIconUrl,
    'normalizeSortValue' => 'omoApiSortKey',
));

echo json_encode(array('status' => true, 'entries' => $entries), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
