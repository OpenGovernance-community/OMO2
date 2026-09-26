<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$currentUserId = (int)commonGetCurrentUserId();
$initialOpenDocumentId = isset($_GET['open_document_id']) ? (int)$_GET['open_document_id'] : 0;

$documents = new \dbObject\ArrayDocument();
$documents->loadOwnedByUser($currentUserId);

$today = new DateTimeImmutable('today');
$groups = sharedGetRelativeDateGroups($today);

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

$documentEntries = array();

foreach ($documents as $document) {
    if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
        continue;
    }

    $organizationId = (int)$document->get('IDorganization');
    $holonId = (int)$document->get('IDholon');
    $documentActivityVisited = array();
    $createdAt = $document->get('datecreation');
    $updatedAt = $document->getActivityDate($documentActivityVisited);
    $activityDate = $document->isFolder() && $updatedAt instanceof DateTimeInterface
        ? $updatedAt
        : $createdAt;
    $groupIndex = sharedGetRelativeDateGroupIndexForDate($activityDate, $groups, $today);
    $group = $groups[$groupIndex] ?? array('key' => 'too_far', 'label' => 'Trop loin');
    $groupKey = (string)($group['key'] ?? 'too_far');
    $contextLabel = trim((string)$document->getOrganizationContextLabel());
    $documentType = $document->getDocumentType();
    $typeLabel = 'Document';

    if ($document->isFolder()) {
        $typeLabel = 'Dossier';
    } elseif ($documentType === \dbObject\Document::TYPE_EXTERNAL_LINK) {
        $typeLabel = 'Lien';
    } elseif ($documentType === \dbObject\Document::TYPE_UPLOADED_FILE) {
        $typeLabel = 'Fichier';
    } elseif ($documentType === \dbObject\Document::TYPE_ETHERPAD) {
        $typeLabel = 'Document collaboratif';
    } elseif ($documentType === \dbObject\Document::TYPE_ETHERCALC) {
        $typeLabel = 'Tableur collaboratif';
    }

    $documentEntries[] = array(
        'id' => (int)$document->getId(),
        'parentDocumentId' => max(0, (int)$document->get('IDdocument_parent')),
        'title' => trim((string)$document->get('title')) !== ''
            ? trim((string)$document->get('title'))
            : ('Document #' . (int)$document->getId()),
        'description' => trim((string)$document->get('description')),
        'keywords' => trim((string)$document->get('keywords')),
        'isFolder' => $document->isFolder(),
        'documentType' => $documentType,
        'typeLabel' => $typeLabel,
        'contextLabel' => $contextLabel,
        'childCount' => 0,
        'dateLabel' => $formatDate($activityDate, in_array($groupKey, array('earlier', 'too_far'), true)),
        'fullDateLabel' => $formatDate($activityDate, true),
        'timestamp' => $activityDate instanceof DateTimeInterface ? (int)$activityDate->getTimestamp() : 0,
        'groupKey' => $groupKey,
        'groupLabel' => (string)($group['label'] ?? 'Trop loin'),
        'sortTitle' => memoApiSortKey($document->get('title')),
        'detailUrl' => '/memo/api/documents/detail.php?id=' . (int)$document->getId(),
		'canEdit' => $document->canManageInOrganizationContext($organizationId)
			|| (!$document->isEtherpadDocument() && !$document->isEthercalcDocument() && $document->canEditInOrganizationContext($organizationId)),
        'editUrl' => '/omo/api/documents/create.php'
            . '?id=' . (int)$document->getId()
            . ($organizationId > 0 ? '&oid=' . $organizationId : '')
            . ($holonId > 0 ? '&cid=' . $holonId : ''),
    );
}

$documentEntriesById = array();
foreach ($documentEntries as $entryIndex => $entry) {
    $documentEntriesById[(int)($entry['id'] ?? 0)] = $entryIndex;
}

foreach ($documentEntries as $entry) {
    $parentDocumentId = (int)($entry['parentDocumentId'] ?? 0);
    if ($parentDocumentId <= 0 || !isset($documentEntriesById[$parentDocumentId])) {
        continue;
    }

    $parentEntryIndex = (int)$documentEntriesById[$parentDocumentId];
    $documentEntries[$parentEntryIndex]['childCount'] = (int)($documentEntries[$parentEntryIndex]['childCount'] ?? 0) + 1;
}

$documentsPayload = json_encode(
    array(
        'documents' => array_values($documentEntries),
        'openDocumentId' => $initialOpenDocumentId > 0 ? $initialOpenDocumentId : 0,
        'groups' => array_values(array_map(static function (array $group): array {
            return array(
                'key' => (string)($group['key'] ?? ''),
                'label' => (string)($group['label'] ?? ''),
            );
        }, $groups)),
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (!is_string($documentsPayload)) {
    $documentsPayload = '{"documents":[],"openDocumentId":0,"groups":[]}';
}
?>
<div class="memo-documents omo-panel-view" id="memo-documents-root" data-memo-open-document-id="<?= (int)$initialOpenDocumentId ?>">
    <div class="omo-panel-view__header omo-panel-view__header--stacked memo-documents__header">
        <div class="omo-panel-view__header-main memo-documents__header-main">
            <div class="omo-panel-view__title-cluster">
                <div class="omo-panel-view__header-copy">
                    <div class="memo-documents__title-row">
                        <span class="omo-panel-view__app-icon memo-documents__app-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M14 3.5v4a1.5 1.5 0 0 0 1.5 1.5h4"></path>
                                <path d="M8 13h8"></path>
                                <path d="M8 17h5"></path>
                                <path d="M13.5 3.5H8A2.5 2.5 0 0 0 5.5 6v12A2.5 2.5 0 0 0 8 20.5h8A2.5 2.5 0 0 0 18.5 18V8.5z"></path>
                            </svg>
                        </span>
                        <h1 class="omo-panel-view__title">Mes documents</h1>
                        <span class="omo-panel-view__count memo-documents__count">
                            <?= (int)count($documentEntries) ?>
                        </span>
                    </div>
                    <p class="omo-panel-view__subtitle memo-documents__subtitle">
                        Tous vos documents, tous holons confondus, avec ouverture directe dans un drawer interne.
                    </p>
                </div>
            </div>
        </div>
        <?php if (count($documentEntries) > 0): ?>
            <div class="omo-panel-view__header-secondary memo-documents__header-actions">
                <div class="omo-panel-controls memo-documents__controls">
                    <div class="omo-segmented" role="group" aria-label="Tri des documents memo">
                        <button type="button" class="omo-segmented__button is-active" data-memo-documents-sort="date" data-omo-segmented-option="temporal" aria-pressed="true"><span class="omo-segmented__text">Date</span></button>
                        <button type="button" class="omo-segmented__button" data-memo-documents-sort="alpha" data-omo-segmented-option="alphabetical" aria-pressed="false"><span class="omo-segmented__text">Alphabétique</span></button>
                    </div>
                    <div class="omo-segmented" role="group" aria-label="Densité d'affichage des documents memo">
                        <button type="button" class="omo-segmented__button is-active" data-memo-documents-density="detail" data-omo-documents-density="detail" aria-pressed="true">Détail</button>
                        <button type="button" class="omo-segmented__button" data-memo-documents-density="compact" data-omo-documents-density="compact" aria-pressed="false">Compact</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="omo-panel-view__body">
        <?php if (count($documentEntries) === 0): ?>
            <div class="memo-documents__empty omo-empty-state">
                Aucun document ne vous appartient encore.
            </div>
        <?php else: ?>
            <div class="memo-documents__results generic-file-list" data-memo-documents-results></div>
        <?php endif; ?>
    </div>

    <div class="omo-overlay-drawer memo-documents__detail-drawer" data-memo-document-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-memo-document-drawer-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-memo-document-drawer-title>Détail du document</h3>
                </div>
                <div class="generic-drawer-header__actions">
                    <button type="button" class="omo-overlay-drawer__close" data-memo-document-drawer-close>Fermer</button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-memo-document-drawer-body></div>
        </div>
    </div>

    <div class="omo-overlay-drawer memo-documents__editor-drawer" data-memo-document-editor-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-memo-document-editor-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-memo-document-editor-title>Editer le document</h3>
                    <p class="omo-overlay-drawer__description" data-memo-document-editor-description>Modification du document dans EasyMEMO.</p>
                </div>
                <div class="generic-drawer-header__actions">
                    <button type="button" class="omo-overlay-drawer__close" data-memo-document-editor-close>Fermer</button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-memo-document-editor-body></div>
        </div>
    </div>

    <script type="application/json" data-memo-documents-data><?= $documentsPayload ?></script>
</div>

<script src="<?= commonAssetUrl('/memo/api/documents/index.js') ?>"></script>

<style>
.memo-documents {
    display: flex;
    flex-direction: column;
    gap: 20px;
    height: auto;
    min-height: 0;
    overflow: visible;
}

.memo-documents__header {
    gap: 18px;
    position: sticky;
    top: var(--memo-documents-header-sticky-top, 0px);
}

.memo-documents__header-main {
    align-items: flex-start;
}

.memo-documents .omo-panel-view__header-copy {
    flex: 1 1 auto;
}

.memo-documents__app-icon svg {
    width: 24px;
    height: 24px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.memo-documents__title-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: nowrap;
    position: sticky;
    top: 0;
    z-index: 1;
    width: 100%;
    background: var(--color-surface);
    padding-top:10px;
}

.memo-documents__title-row .omo-panel-view__title {
    white-space: nowrap;
}

.memo-documents__subtitle {
    max-width: 62ch;
}

.memo-documents__controls {
    justify-content: flex-start;
}

.memo-documents__results,
.memo-documents__list,
.memo-documents__tree {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.memo-documents .omo-panel-view__body {
    flex: 0 0 auto;
    min-height: auto;
    overflow: visible;
    padding-right: 0;
}

.memo-documents__group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.memo-documents__group-title {
    margin: 0;
    top: var(--memo-documents-sticky-header-height, 0px);
}

.memo-documents__item-shell {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.memo-documents__item-shell--compact {
    gap: 8px;
}

.memo-documents__tree--depth-1,
.memo-documents__tree--depth-2,
.memo-documents__tree--depth-3,
.memo-documents__tree--depth-4 {
    margin-left: 26px;
}

.memo-documents__item {
    padding: 18px 20px;
}

.memo-documents__editor-drawer .omo-overlay-drawer__body {
    padding: 0;
}

.memo-documents__detail-drawer,
.memo-documents__editor-drawer {
    z-index: 6000;
}

.memo-documents__detail-drawer {
    position: fixed;
    inset: 0;
}

.memo-documents__item-frame {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 14px;
    align-items: start;
}

.memo-documents__icon-box,
.memo-documents__compact-icon-box {
    width: 42px;
    height: 42px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface-alt) 86%, #dbeafe 14%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 42px;
}

.memo-documents__icon,
.memo-documents__compact-icon {
    width: 22px;
    height: 22px;
}

.memo-documents__content {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}

.memo-documents__item-head,
.memo-documents__item-title-row {
    display: flex;
    gap: 12px;
    justify-content: space-between;
    align-items: flex-start;
}

.memo-documents__item-title-row strong {
    font-size: 1rem;
}

.memo-documents__type-pill {
    white-space: nowrap;
}

.memo-documents__date,
.memo-documents__context,
.memo-documents__keywords,
.memo-documents__compact-cell {
    color: var(--color-text-light);
    font-size: 0.92rem;
}

.memo-documents__description {
    margin: 0;
    line-height: 1.6;
}

.memo-documents__item[role="button"] {
    cursor: pointer;
}

.memo-documents__item[role="button"]:hover,
.memo-documents__item[role="button"]:focus-visible {
    border-color: color-mix(in srgb, var(--color-border) 58%, var(--color-primary) 42%);
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
}

.omo-documents__menu {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 2;
}

.omo-documents__menu-toggle {
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
}

.memo-documents__list--compact {
    gap: 0;
}

.memo-documents__list-header {
    display: grid;
    grid-template-columns: minmax(280px, 2.2fr) minmax(160px, 1.4fr) minmax(120px, 0.8fr) minmax(120px, 0.8fr);
    gap: 12px;
    padding: 0 14px 10px;
}

.memo-documents__list-header-cell {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.memo-documents__compact-row {
    display: grid;
    grid-template-columns: minmax(280px, 2.2fr) minmax(160px, 1.4fr) minmax(120px, 0.8fr) minmax(120px, 0.8fr);
    gap: 12px;
    align-items: center;
}

.memo-documents__compact-cell--name {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.memo-documents__compact-title-block {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.memo-documents__compact-title {
    color: var(--color-text);
    font-weight: 700;
}

.memo-documents__compact-count {
    font-size: 0.84rem;
    color: var(--color-text-light);
}

.memo-documents--compact .memo-documents__item {
    padding: 14px 18px;
}

.memo-documents__item-shell--compact .omo-documents__menu {
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
}

.memo-documents__empty {
    padding: 42px 20px;
}

@media (max-width: 1100px) {
    .memo-documents__list-header,
    .memo-documents__compact-row {
        grid-template-columns: minmax(220px, 2fr) minmax(140px, 1fr) minmax(100px, 0.8fr) minmax(100px, 0.8fr);
    }
}

@media (max-width: 860px) {
    .memo-documents__item-head,
    .memo-documents__item-title-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .memo-documents__list-header {
        display: none;
    }

    .memo-documents__compact-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .memo-documents__compact-cell {
        font-size: 0.88rem;
    }

    .memo-documents__tree--depth-1,
    .memo-documents__tree--depth-2,
    .memo-documents__tree--depth-3,
    .memo-documents__tree--depth-4 {
        margin-left: 14px;
    }
}
</style>
