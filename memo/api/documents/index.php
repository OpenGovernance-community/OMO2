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
                <span class="omo-panel-view__app-icon memo-documents__app-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M14 3.5v4a1.5 1.5 0 0 0 1.5 1.5h4"></path>
                        <path d="M8 13h8"></path>
                        <path d="M8 17h5"></path>
                        <path d="M13.5 3.5H8A2.5 2.5 0 0 0 5.5 6v12A2.5 2.5 0 0 0 8 20.5h8A2.5 2.5 0 0 0 18.5 18V8.5z"></path>
                    </svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="memo-documents__title-row">
                        <h1 class="omo-panel-view__title">Mes documents</h1>
                        <span class="omo-panel-view__count memo-documents__count">
                            <?= (int)count($documentEntries) ?> document<?= count($documentEntries) > 1 ? 's' : '' ?>
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
            <div class="omo-overlay-drawer__header">
                <div class="omo-overlay-drawer__header-copy">
                    <h3 class="omo-overlay-drawer__title" data-memo-document-drawer-title>Détail du document</h3>
                    <p class="omo-overlay-drawer__description">Lecture du document dans EasyMEMO.</p>
                </div>
                <button type="button" class="omo-overlay-drawer__close" data-memo-document-drawer-close>Fermer</button>
            </div>
            <div class="omo-overlay-drawer__body" data-memo-document-drawer-body></div>
        </div>
    </div>

    <script type="application/json" data-memo-documents-data><?= $documentsPayload ?></script>
</div>

<script>
(function () {
    const root = document.getElementById('memo-documents-root');
    if (!root || root.dataset.memoReady === '1') {
        return;
    }

    root.dataset.memoReady = '1';

    const payloadNode = root.querySelector('[data-memo-documents-data]');
    const results = root.querySelector('[data-memo-documents-results]');
    const drawer = root.querySelector('[data-memo-document-drawer]');
    const drawerBody = root.querySelector('[data-memo-document-drawer-body]');
    const drawerTitle = root.querySelector('[data-memo-document-drawer-title]');
    const closeButtons = root.querySelectorAll('[data-memo-document-drawer-close]');
    const preferencesStorageKey = 'memoDocumentsDisplayPreferences';
    const fileIconUrl = '/omo/assets/images/documents/file.png';
    const folderIconUrl = '/omo/assets/images/documents/folder.png';
    const downloadIconUrl = '/omo/assets/images/documents/download.png';
    const linkIconUrl = '/omo/assets/images/documents/link.png';

    if (!payloadNode || !results) {
        return;
    }

    let payload = null;
    try {
        payload = JSON.parse(payloadNode.textContent || '{}');
    } catch (error) {
        return;
    }

    const documents = Array.isArray(payload.documents) ? payload.documents.slice() : [];
    const groups = Array.isArray(payload.groups) ? payload.groups.slice() : [];
    if (documents.length === 0) {
        return;
    }

    const normalizeSortPreference = function (value) {
        return String(value || '').trim().toLowerCase() === 'alpha' ? 'alpha' : 'date';
    };

    const normalizeDensityPreference = function (value) {
        return String(value || '').trim().toLowerCase() === 'compact' ? 'compact' : 'detail';
    };

    const readPreferences = function () {
        let rawValue = '';

        try {
            rawValue = window.localStorage
                ? String(window.localStorage.getItem(preferencesStorageKey) || '')
                : '';
        } catch (error) {
            rawValue = '';
        }

        if (rawValue === '') {
            return {
                sort: 'date',
                density: 'detail'
            };
        }

        try {
            const parsed = JSON.parse(rawValue);
            return {
                sort: normalizeSortPreference(parsed && parsed.sort ? parsed.sort : null),
                density: normalizeDensityPreference(parsed && parsed.density ? parsed.density : null)
            };
        } catch (error) {
            return {
                sort: 'date',
                density: 'detail'
            };
        }
    };

    const writePreferences = function (preferences) {
        const normalizedPreferences = {
            sort: normalizeSortPreference(preferences && preferences.sort ? preferences.sort : null),
            density: normalizeDensityPreference(preferences && preferences.density ? preferences.density : null)
        };

        try {
            if (window.localStorage) {
                window.localStorage.setItem(preferencesStorageKey, JSON.stringify(normalizedPreferences));
            }
        } catch (error) {
        }
    };

    const state = readPreferences();
    const childrenByParentId = new Map();
    let detailRequestToken = 0;

    const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
        ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
        : null;

    const compareText = function (left, right) {
        const normalizedLeft = String(left || '');
        const normalizedRight = String(right || '');

        if (collator) {
            return collator.compare(normalizedLeft, normalizedRight);
        }

        return normalizedLeft.localeCompare(normalizedRight);
    };

    documents.forEach(function (documentItem) {
        const parentDocumentId = Number(documentItem && documentItem.parentDocumentId ? documentItem.parentDocumentId : 0);
        const normalizedParentDocumentId = Number.isInteger(parentDocumentId) && parentDocumentId > 0
            ? parentDocumentId
            : 0;

        if (!childrenByParentId.has(normalizedParentDocumentId)) {
            childrenByParentId.set(normalizedParentDocumentId, []);
        }

        childrenByParentId.get(normalizedParentDocumentId).push(documentItem);
    });

    const sortByDate = function (items) {
        return items.slice().sort(function (left, right) {
            const timestampDiff = Number(right.timestamp || 0) - Number(left.timestamp || 0);
            if (timestampDiff !== 0) {
                return timestampDiff;
            }

            return compareText(left.sortTitle || left.title, right.sortTitle || right.title);
        });
    };

    const sortByAlpha = function (items) {
        return items.slice().sort(function (left, right) {
            const titleDiff = compareText(left.sortTitle || left.title, right.sortTitle || right.title);
            if (titleDiff !== 0) {
                return titleDiff;
            }

            return Number(right.timestamp || 0) - Number(left.timestamp || 0);
        });
    };

    const getSortedTree = function (sortMode, parentDocumentId) {
        const normalizedParentDocumentId = Number(parentDocumentId || 0) > 0
            ? Number(parentDocumentId || 0)
            : 0;
        const sourceItems = childrenByParentId.get(normalizedParentDocumentId) || [];
        const sortedItems = sortMode === 'alpha'
            ? sortByAlpha(sourceItems)
            : sortByDate(sourceItems);

        return sortedItems.map(function (documentItem) {
            const clonedItem = Object.assign({}, documentItem);
            clonedItem.children = getSortedTree(sortMode, Number(documentItem.id || 0));
            return clonedItem;
        });
    };

    const getIconUrl = function (documentItem) {
        if (documentItem && documentItem.isFolder) {
            return folderIconUrl;
        }

        if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'uploaded_file') {
            return downloadIconUrl;
        }

        if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'external_link') {
            return linkIconUrl;
        }

        return fileIconUrl;
    };

    const getIconAlt = function (documentItem) {
        if (documentItem && documentItem.isFolder) {
            return 'Dossier';
        }

        if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'uploaded_file') {
            return 'Fichier';
        }

        if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'external_link') {
            return 'Lien';
        }

        return 'Document';
    };

    const createCompactListHeader = function () {
        const header = document.createElement('div');
        header.className = 'memo-documents__list-header generic-file-list__header';

        [
            { label: 'Nom', className: 'memo-documents__list-header-cell--name' },
            { label: 'Contexte', className: 'memo-documents__list-header-cell--context' },
            { label: 'Type', className: 'memo-documents__list-header-cell--type' },
            { label: 'Date', className: 'memo-documents__list-header-cell--date' }
        ].forEach(function (column) {
            const cell = document.createElement('div');
            cell.className = 'memo-documents__list-header-cell generic-file-list__header-cell ' + column.className;
            cell.textContent = column.label;
            header.appendChild(cell);
        });

        return header;
    };

    const appendDocumentCardContent = function (container, documentItem) {
        container.classList.add(documentItem.isFolder ? 'memo-documents__item--folder' : 'memo-documents__item--file');
        container.setAttribute('data-memo-document-open', String(documentItem.id || '0'));
        container.setAttribute('data-memo-document-title', String(documentItem.title || ''));
        container.setAttribute('data-memo-document-detail-url', String(documentItem.detailUrl || ''));
        container.setAttribute('role', 'button');
        container.setAttribute('tabindex', '0');

        if (state.density === 'compact') {
            container.classList.add('memo-documents__item--compact');

            const frame = document.createElement('div');
            frame.className = 'memo-documents__compact-row generic-file-list__row';

            const nameCell = document.createElement('div');
            nameCell.className = 'memo-documents__compact-cell memo-documents__compact-cell--name generic-file-list__cell generic-file-list__cell--name';

            const iconBox = document.createElement('span');
            iconBox.className = 'memo-documents__compact-icon-box generic-file-list__icon-box';

            const icon = document.createElement('img');
            icon.className = 'memo-documents__compact-icon black-icon';
            icon.src = getIconUrl(documentItem);
            icon.alt = getIconAlt(documentItem);
            icon.loading = 'lazy';
            iconBox.appendChild(icon);

            const titleBlock = document.createElement('div');
            titleBlock.className = 'memo-documents__compact-title-block generic-file-list__title-block';

            const title = document.createElement('strong');
            title.className = 'memo-documents__compact-title generic-file-list__title';
            title.textContent = documentItem.title || '';
            titleBlock.appendChild(title);

            if (documentItem.isFolder) {
                const count = Number(documentItem.childCount || 0);
                const countLabel = document.createElement('span');
                countLabel.className = 'memo-documents__compact-count generic-file-list__count';
                countLabel.textContent = count > 0
                    ? String(count) + ' élément' + (count > 1 ? 's' : '')
                    : 'Dossier';
                titleBlock.appendChild(countLabel);
            }

            nameCell.appendChild(iconBox);
            nameCell.appendChild(titleBlock);

            const contextCell = document.createElement('div');
            contextCell.className = 'memo-documents__compact-cell memo-documents__compact-cell--context generic-file-list__cell';
            contextCell.textContent = documentItem.contextLabel || 'Sans contexte';

            const typeCell = document.createElement('div');
            typeCell.className = 'memo-documents__compact-cell memo-documents__compact-cell--type generic-file-list__cell';
            typeCell.textContent = documentItem.typeLabel || 'Document';

            const dateCell = document.createElement('div');
            dateCell.className = 'memo-documents__compact-cell memo-documents__compact-cell--date generic-file-list__cell';
            dateCell.textContent = state.sort === 'alpha'
                ? (documentItem.fullDateLabel || documentItem.dateLabel || '')
                : (documentItem.dateLabel || '');

            frame.appendChild(nameCell);
            frame.appendChild(contextCell);
            frame.appendChild(typeCell);
            frame.appendChild(dateCell);
            container.appendChild(frame);
            return;
        }

        const frame = document.createElement('div');
        frame.className = 'memo-documents__item-frame';

        const iconBox = document.createElement('div');
        iconBox.className = 'memo-documents__icon-box generic-file-list__icon-box';

        const icon = document.createElement('img');
        icon.className = 'memo-documents__icon black-icon';
        icon.src = getIconUrl(documentItem);
        icon.alt = getIconAlt(documentItem);
        icon.loading = 'lazy';
        iconBox.appendChild(icon);

        const content = document.createElement('div');
        content.className = 'memo-documents__content';

        const head = document.createElement('div');
        head.className = 'memo-documents__item-head';

        const titleRow = document.createElement('div');
        titleRow.className = 'memo-documents__item-title-row';

        const title = document.createElement('strong');
        title.textContent = documentItem.title || '';
        titleRow.appendChild(title);

        const pill = document.createElement('span');
        pill.className = 'omo-pill memo-documents__type-pill';
        pill.textContent = documentItem.typeLabel || 'Document';
        titleRow.appendChild(pill);

        const date = document.createElement('span');
        date.className = 'memo-documents__date';
        date.textContent = state.sort === 'alpha'
            ? (documentItem.fullDateLabel || documentItem.dateLabel || '')
            : (documentItem.dateLabel || '');

        head.appendChild(titleRow);
        head.appendChild(date);
        content.appendChild(head);

        if (documentItem.contextLabel) {
            const context = document.createElement('div');
            context.className = 'memo-documents__context';
            context.textContent = documentItem.contextLabel;
            content.appendChild(context);
        }

        if (documentItem.description) {
            const description = document.createElement('p');
            description.className = 'memo-documents__description';
            description.textContent = documentItem.description;
            content.appendChild(description);
        }

        if (documentItem.keywords) {
            const keywords = document.createElement('div');
            keywords.className = 'memo-documents__keywords';
            keywords.textContent = documentItem.keywords;
            content.appendChild(keywords);
        }

        frame.appendChild(iconBox);
        frame.appendChild(content);
        container.appendChild(frame);
    };

    const createItem = function (documentItem) {
        const shell = document.createElement('article');
        shell.className = 'memo-documents__item-shell generic-file-list__item-shell';

        if (documentItem.isFolder) {
            shell.classList.add('memo-documents__item-shell--folder');
        }

        if (state.density === 'compact') {
            shell.classList.add('memo-documents__item-shell--compact');
        }

        const card = document.createElement('div');
        card.className = 'memo-documents__item omo-card omo-card--interactive';
        appendDocumentCardContent(card, documentItem);
        shell.appendChild(card);

        if (Array.isArray(documentItem.children) && documentItem.children.length > 0) {
            shell.appendChild(renderTree(documentItem.children, 1));
        }

        return shell;
    };

    const renderTree = function (documentItems, depth) {
        const tree = document.createElement('div');
        tree.className = 'memo-documents__tree memo-documents__tree--depth-' + String(Number(depth || 0));

        documentItems.forEach(function (documentItem) {
            tree.appendChild(createItem(documentItem));
        });

        return tree;
    };

    const renderByDate = function () {
        const fragment = document.createDocumentFragment();
        const groupedDocuments = new Map();

        getSortedTree('date', 0).forEach(function (documentItem) {
            const groupKey = documentItem.groupKey || 'too_far';
            if (!groupedDocuments.has(groupKey)) {
                groupedDocuments.set(groupKey, []);
            }

            groupedDocuments.get(groupKey).push(documentItem);
        });

        groups.forEach(function (group) {
            const items = groupedDocuments.get(group.key || '') || [];
            if (items.length === 0) {
                return;
            }

            const section = document.createElement('section');
            section.className = 'memo-documents__group omo-panel-group generic-file-list__group';

            const title = document.createElement('h3');
            title.className = 'memo-documents__group-title omo-panel-group__title generic-file-list__group-title';
            title.textContent = group.label || '';
            section.appendChild(title);

            const list = document.createElement('div');
            list.className = 'memo-documents__list omo-panel-view__body_content';

            if (state.density === 'compact') {
                list.classList.add('memo-documents__list--compact', 'generic-file-list__table');
                list.appendChild(createCompactListHeader());
            }

            items.forEach(function (documentItem) {
                list.appendChild(createItem(documentItem));
            });

            section.appendChild(list);
            fragment.appendChild(section);
        });

        results.replaceChildren(fragment);
    };

    const renderByAlpha = function () {
        const section = document.createElement('section');
        section.className = 'memo-documents__group omo-panel-group generic-file-list__group';

        const title = document.createElement('h3');
        title.className = 'memo-documents__group-title omo-panel-group__title generic-file-list__group-title';
        title.textContent = 'Alphabétique';
        section.appendChild(title);

        const list = document.createElement('div');
        list.className = 'memo-documents__list memo-documents__list--alphabetical omo-panel-view__body_content';

        if (state.density === 'compact') {
            list.classList.add('memo-documents__list--compact', 'generic-file-list__table');
            list.appendChild(createCompactListHeader());
        }

        getSortedTree('alpha', 0).forEach(function (documentItem) {
            list.appendChild(createItem(documentItem));
        });

        section.appendChild(list);
        results.replaceChildren(section);
    };

    const syncButtons = function (selector, activeValue, attributeName) {
        root.querySelectorAll(selector).forEach(function (button) {
            const isActive = button.getAttribute(attributeName) === activeValue;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const render = function () {
        root.classList.toggle('memo-documents--compact', state.density === 'compact');

        if (state.sort === 'alpha') {
            renderByAlpha();
        } else {
            renderByDate();
        }

        syncButtons('[data-memo-documents-sort]', state.sort, 'data-memo-documents-sort');
        syncButtons('[data-memo-documents-density]', state.density, 'data-memo-documents-density');
        if (typeof window.syncGenericFileLists === 'function') {
            window.syncGenericFileLists(results);
        }
    };

    const findDocumentById = function (documentId) {
        const resolvedDocumentId = Number(documentId || 0);
        if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
            return null;
        }

        return documents.find(function (item) {
            return Number(item.id || 0) === resolvedDocumentId;
        }) || null;
    };

    const openDrawer = function () {
        if (!drawer) {
            return;
        }

        drawer.hidden = false;
        requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });
    };

    const closeDrawer = function () {
        if (!drawer) {
            return;
        }

        drawer.classList.remove('is-open');

        window.setTimeout(function () {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
            }
        }, 200);
    };

    const updateHistoryUrl = function (documentId) {
        if (!window.history || !window.history.replaceState || !(Number(documentId) > 0)) {
            return;
        }

        window.history.replaceState({}, document.title, '/memo/' + String(Number(documentId)));
    };

    const renderDetailLoading = function () {
        if (!drawerBody) {
            return;
        }

        drawerBody.innerHTML = '<div class="loading"><div class="omo-empty-state">Chargement...</div></div>';
    };

    const renderDetailError = function () {
        if (!drawerBody) {
            return;
        }

        drawerBody.innerHTML = '<div class="loading"><div class="omo-empty-state">Impossible de charger ce document.</div></div>';
    };

    const openDocumentDetail = function (documentItem, updateHistory) {
        if (!drawer || !drawerBody || !documentItem || !documentItem.id) {
            return;
        }

        if (drawerTitle) {
            drawerTitle.textContent = documentItem.title || 'Détail du document';
        }

        renderDetailLoading();
        openDrawer();

        const requestToken = ++detailRequestToken;

        fetch(String(documentItem.detailUrl || '').trim(), {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('detail_fetch_failed');
                }

                return response.text();
            })
            .then(function (html) {
                if (requestToken !== detailRequestToken) {
                    return;
                }

                drawerBody.innerHTML = html;
                if (updateHistory !== false) {
                    updateHistoryUrl(documentItem.id);
                }
            })
            .catch(function () {
                if (requestToken !== detailRequestToken) {
                    return;
                }

                renderDetailError();
            });
    };

    root.querySelectorAll('[data-memo-documents-sort]').forEach(function (button) {
        button.addEventListener('click', function () {
            const nextSort = normalizeSortPreference(button.getAttribute('data-memo-documents-sort'));
            if (!nextSort || nextSort === state.sort) {
                return;
            }

            state.sort = nextSort;
            writePreferences(state);
            render();
        });
    });

    root.querySelectorAll('[data-memo-documents-density]').forEach(function (button) {
        button.addEventListener('click', function () {
            const nextDensity = normalizeDensityPreference(button.getAttribute('data-memo-documents-density'));
            if (!nextDensity || nextDensity === state.density) {
                return;
            }

            state.density = nextDensity;
            writePreferences(state);
            render();
        });
    });

    root.addEventListener('click', function (event) {
        const closeTrigger = event.target.closest('[data-memo-document-drawer-close]');
        if (closeTrigger) {
            event.preventDefault();
            closeDrawer();
            return;
        }

        const trigger = event.target.closest('[data-memo-document-open]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const documentItem = findDocumentById(Number(trigger.getAttribute('data-memo-document-open') || 0));
        if (!documentItem) {
            return;
        }

        openDocumentDetail(documentItem, true);
    });

    root.addEventListener('keydown', function (event) {
        const trigger = event.target.closest('[data-memo-document-open]');
        if (!trigger) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();

            const documentItem = findDocumentById(Number(trigger.getAttribute('data-memo-document-open') || 0));
            if (!documentItem) {
                return;
            }

            openDocumentDetail(documentItem, true);
        }
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeDrawer();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && drawer && !drawer.hidden) {
            closeDrawer();
        }
    });

    render();

    const initialOpenDocumentId = Number(root.getAttribute('data-memo-open-document-id') || 0);
    if (initialOpenDocumentId > 0) {
        const initialDocument = findDocumentById(initialOpenDocumentId);
        if (initialDocument) {
            openDocumentDetail(initialDocument, false);
        }
    }
})();
</script>

<style>
.memo-documents {
    display: flex;
    flex-direction: column;
    gap: 20px;
    height: 100%;
    min-height: 0;
}

.memo-documents__header {
    gap: 18px;
}

.memo-documents__header-main {
    align-items: flex-start;
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
    flex-wrap: wrap;
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
    min-height: 0;
    overflow-y: auto;
    padding-right: 6px;
}

.memo-documents__group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.memo-documents__group-title {
    margin: 0;
}

.memo-documents__item-shell {
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
    border-radius: 14px;
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
