<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$currentOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$initialOpenDocumentId = isset($_GET['open_document_id']) ? (int)$_GET['open_document_id'] : 0;
$documentScope = strtolower(trim((string)($_GET['document_scope'] ?? 'contextual')));
if ($documentScope !== 'global') {
    $documentScope = 'contextual';
}

$organization = new Organization();
if ($currentOrganizationId > 0) {
    $organization->load($currentOrganizationId);
}

$canToggleDocumentScope = $organization->getId() > 0 && $organization->getEnabledStructuralRootHolon() !== null;
$canCreateDocument = $organization->getId() > 0
    && (int)commonGetCurrentUserId() > 0
    && commonCurrentUserHasOrganizationAccess($currentOrganizationId);
$newDocumentUrl = '/omo/api/documents/create.php?oid=' . $currentOrganizationId . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '');

$documents = new \dbObject\ArrayDocument();
$documentVisibilityRuleMap = array();
if ($currentOrganizationId > 0) {
    $documentVisibilityRuleMap = $documents->loadVisibleForOrganizationContext(
        $currentOrganizationId,
        $currentHolonId,
        $documentScope
    );
}

$today = new DateTimeImmutable('today');
$groups = sharedGetRelativeDateGroups($today);

$escape = 'omoApiEscape';
$normalizeSortValue = 'omoApiSortKey';

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

$documentEntries = [];

foreach ($documents as $document) {
    $createdAt = $document->get('datecreation');
    $documentId = (int)$document->getId();
    $documentHolonId = (int)$document->get('IDholon');
    $visibility = $document->getVisibilityDisplayData(
        $currentOrganizationId,
        $documentVisibilityRuleMap[$documentId] ?? null
    );
    $groupIndex = sharedGetRelativeDateGroupIndexForDate($createdAt, $groups, $today);
    $group = $groups[$groupIndex] ?? ['key' => 'too_far', 'label' => 'Trop loin'];
    $groupKey = (string)($group['key'] ?? 'too_far');

    $documentEntries[] = [
        'id' => $documentId,
        'href' => '/memo/' . $documentId,
        'title' => (string)$document->get('title'),
        'contextLabel' => $documentScope === 'global'
            ? trim((string)$document->getOrganizationContextLabel())
            : '',
        'contextBreadcrumb' => $documentScope === 'global'
            ? array_values(array_map(
                static function (array $item): array {
                    $organizationId = (int)($item['organizationId'] ?? 0);
                    $holonId = (int)($item['holonId'] ?? 0);

                    return array(
                        'label' => trim((string)($item['label'] ?? '')),
                        'organizationId' => $organizationId,
                        'holonId' => $holonId,
                    );
                },
                $document->getOrganizationContextBreadcrumbItems()
            ))
            : array(),
        'description' => trim((string)$document->get('description')),
        'keywords' => trim((string)$document->get('keywords')),
        'canEdit' => $document->canEditInOrganizationContext($currentOrganizationId),
        'editUrl' => '/omo/api/documents/create.php?oid=' . $currentOrganizationId
            . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '')
            . '&id=' . $documentId,
        'visibilityBadge' => (string)($visibility['badgeText'] ?? ''),
        'visibilityType' => (string)($visibility['type'] ?? ''),
        'dateLabel' => $formatDate($createdAt, in_array($groupKey, ['earlier', 'too_far'], true)),
        'fullDateLabel' => $formatDate($createdAt, true),
        'timestamp' => $createdAt instanceof DateTimeInterface ? (int)$createdAt->getTimestamp() : 0,
        'groupKey' => $groupKey,
        'groupLabel' => (string)($group['label'] ?? 'Trop loin'),
        'sortTitle' => $normalizeSortValue($document->get('title')),
        'contextUrl' => '/omo/api/documents/detail.php?id=' . $documentId
            . '&oid=' . $currentOrganizationId
            . ($documentHolonId > 0 ? '&cid=' . $documentHolonId : ''),
    ];
}

$documentsPayload = json_encode(
    [
        'documents' => $documentEntries,
        'openDocumentId' => $initialOpenDocumentId > 0 ? $initialOpenDocumentId : 0,
        'groups' => array_map(
            static function (array $group): array {
                return [
                    'key' => (string)($group['key'] ?? ''),
                    'label' => (string)($group['label'] ?? ''),
                ];
            },
            $groups
        ),
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (!is_string($documentsPayload)) {
    $documentsPayload = '{"documents":[],"openDocumentId":0,"groups":[]}';
}
?>
<div
    class="omo-documents omo-panel-view"
    id="omo-documents-root"
    data-omo-document-scope="<?= $escape($documentScope) ?>"
    data-omo-document-oid="<?= (int)$currentOrganizationId ?>"
    data-omo-document-cid="<?= (int)$currentHolonId ?>"
>
    <div class="omo-documents__header omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <div class="omo-documents__title-row">
                <h2 class="omo-panel-view__title">Documents</h2>
                <span class="omo-documents__count omo-panel-view__count"><?= $escape(count($documentEntries)) ?></span>
            </div>
        </div>
        <div class="omo-panel-view__aside omo-documents__aside">
            <div class="omo-documents__controls-row">
            <?php if ($canToggleDocumentScope): ?>
                <div
                    class="omo-scope-toggle"
                    role="tablist"
                    aria-label="Portee des documents"
                    data-omo-scope-switch="<?= $escape($documentScope) ?>"
                >
                    <button
                        type="button"
                        class="omo-scope-toggle__button<?= $documentScope === 'contextual' ? ' is-active' : '' ?>"
                        data-omo-document-scope-toggle="contextual"
                        aria-pressed="<?= $documentScope === 'contextual' ? 'true' : 'false' ?>"
                        onclick="return window.omoToggleDocumentsScope ? window.omoToggleDocumentsScope(this, event) : false;"
                    >Contextuel</button>
                    <button
                        type="button"
                        class="omo-scope-toggle__button<?= $documentScope === 'global' ? ' is-active' : '' ?>"
                        data-omo-document-scope-toggle="global"
                        aria-pressed="<?= $documentScope === 'global' ? 'true' : 'false' ?>"
                        onclick="return window.omoToggleDocumentsScope ? window.omoToggleDocumentsScope(this, event) : false;"
                    >Global</button>
                </div>
            <?php endif; ?>
            <?php if ($canCreateDocument): ?>
                <button
                    type="button"
                    class="generic-action-button generic-action-button--main omo-documents__new-button"
                    data-omo-documents-new
                    data-omo-documents-new-url="<?= $escape($newDocumentUrl) ?>"
                >Nouveau</button>
            <?php endif; ?>
            <?php if (count($documentEntries) > 0): ?>
                <div class="omo-documents__controls omo-panel-controls">
                    <div class="omo-segmented" role="group" aria-label="Tri des documents">
                        <button type="button" class="omo-segmented__button is-active" data-omo-documents-sort="date" aria-pressed="true">Date</button>
                        <button type="button" class="omo-segmented__button" data-omo-documents-sort="alpha" aria-pressed="false">Alphabétique</button>
                    </div>
                    <div class="omo-segmented" role="group" aria-label="Densité d'affichage des documents">
                        <button type="button" class="omo-segmented__button is-active" data-omo-documents-density="detail" aria-pressed="true">Détail</button>
                        <button type="button" class="omo-segmented__button" data-omo-documents-density="compact" aria-pressed="false">Compact</button>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <?php if (count($documentEntries) === 0): ?>
            <div class="omo-documents__empty omo-empty-state">
                <?php if ($documentScope === 'global'): ?>
                    Aucun document disponible dans cette organisation.
                <?php else: ?>
                    Aucun document disponible pour ce contexte.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="omo-documents__results" data-omo-documents-results>
                <?php
                $currentGroupKey = null;

                foreach ($documentEntries as $entry):
                    if ($entry['groupKey'] !== $currentGroupKey):
                        if ($currentGroupKey !== null):
                ?>
                        </div>
                    </section>
                <?php
                        endif;

                        $currentGroupKey = $entry['groupKey'];
                ?>
                    <section class="omo-documents__group omo-panel-group">
                        <h3 class="omo-panel-group__title"><?= $escape($entry['groupLabel']) ?></h3>
                        <div class="omo-documents__list omo-panel-view__body_content">
                <?php endif; ?>
                            <article class="omo-documents__item-shell">
                                <div
                                    class="omo-documents__item omo-card omo-card--interactive"
                                    role="button"
                                    tabindex="0"
                                    data-omo-document-id="<?= $escape($entry['id']) ?>"
                                    data-omo-document-href="<?= $escape($entry['href']) ?>"
                                    data-omo-document-context-url="<?= $escape($entry['contextUrl']) ?>"
                                    data-omo-document-title="<?= $escape($entry['title']) ?>"
                                    data-omo-document-full-date="<?= $escape($entry['fullDateLabel']) ?>"
                                >
                                    <div class="omo-documents__item-head">
                                        <span class="omo-documents__date"><?= $escape($entry['dateLabel']) ?></span>
                                        <strong><?= $escape($entry['title']) ?></strong>
                                    </div>

                                    <?php if (!empty($entry['contextBreadcrumb'])): ?>
                                        <div class="omo-documents__context" aria-label="Contexte du document">
                                            <?php foreach ($entry['contextBreadcrumb'] as $breadcrumbIndex => $breadcrumbItem): ?>
                                                <?php if ($breadcrumbIndex > 0): ?>
                                                    <span class="omo-documents__context-separator">›</span>
                                                <?php endif; ?>
                                                <button
                                                    type="button"
                                                    class="omo-documents__context-link"
                                                    data-omo-document-context-jump="1"
                                                    data-omo-document-context-jump-oid="<?= (int)($breadcrumbItem['organizationId'] ?? 0) ?>"
                                                    data-omo-document-context-jump-cid="<?= (int)($breadcrumbItem['holonId'] ?? 0) ?>"
                                                ><?= $escape($breadcrumbItem['label'] ?? '') ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($entry['contextLabel'] !== ''): ?>
                                        <div class="omo-documents__context"><?= $escape($entry['contextLabel']) ?></div>
                                    <?php endif; ?>

                                    <?php if ($entry['visibilityBadge'] !== ''): ?>
                                        <div class="omo-documents__meta-row">
                                            <span class="omo-pill"><?= $escape($entry['visibilityBadge']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($entry['description'] !== ''): ?>
                                        <p><?= $escape($entry['description']) ?></p>
                                    <?php endif; ?>

                                    <?php if ($entry['keywords'] !== ''): ?>
                                        <div class="omo-documents__keywords"><?= $escape($entry['keywords']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($entry['canEdit'])): ?>
                                    <div class="omo-documents__menu" data-omo-document-menu="1">
                                        <button
                                            type="button"
                                            class="omo-documents__menu-toggle"
                                            data-omo-document-menu-toggle="1"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Actions pour <?= $escape($entry['title']) ?>"
                                        >...</button>
                                        <div class="omo-documents__menu-panel" data-omo-document-menu-panel="1" hidden>
                                            <button
                                                type="button"
                                                class="omo-documents__menu-item"
                                                data-omo-document-edit="1"
                                                data-omo-document-edit-url="<?= $escape($entry['editUrl']) ?>"
                                                data-omo-document-edit-title="<?= $escape($entry['title']) ?>"
                                            >Editer</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </article>
                <?php endforeach; ?>
                        </div>
                    </section>
            </div>

            <div class="omo-overlay-drawer omo-documents__detail-drawer" data-omo-document-detail-drawer hidden>
                <div class="omo-overlay-drawer__backdrop" data-omo-document-detail-close></div>
                <div class="omo-overlay-drawer__panel">
                    <div class="omo-overlay-drawer__header">
                        <div class="omo-overlay-drawer__header-copy">
                            <h3 class="omo-overlay-drawer__title" data-omo-document-detail-title>Détail du document</h3>
                            <p class="omo-overlay-drawer__description" data-omo-document-detail-description>Lecture du document dans OMO.</p>
                        </div>
                        <button type="button" class="omo-overlay-drawer__close" data-omo-document-detail-close>Fermer</button>
                    </div>
                    <div class="omo-overlay-drawer__body" data-omo-document-detail-body></div>
                </div>
            </div>

            <script type="application/json" data-omo-documents-data><?= $documentsPayload ?></script>
            <script>
            (function () {
                const omoDocumentsPreferencesStorageKey = 'omoDocumentsDisplayPreferences';

                const omoDocumentsNormalizeSortPreference = function (value) {
                    return String(value || '').trim().toLowerCase() === 'alpha'
                        ? 'alpha'
                        : 'date';
                };

                const omoDocumentsNormalizeDensityPreference = function (value) {
                    return String(value || '').trim().toLowerCase() === 'compact'
                        ? 'compact'
                        : 'detail';
                };

                const omoDocumentsReadPreferences = function () {
                    let rawValue = '';

                    try {
                        rawValue = window.localStorage
                            ? String(window.localStorage.getItem(omoDocumentsPreferencesStorageKey) || '')
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
                            sort: omoDocumentsNormalizeSortPreference(parsed && parsed.sort ? parsed.sort : null),
                            density: omoDocumentsNormalizeDensityPreference(parsed && parsed.density ? parsed.density : null)
                        };
                    } catch (error) {
                        return {
                            sort: 'date',
                            density: 'detail'
                        };
                    }
                };

                const omoDocumentsWritePreferences = function (preferences) {
                    const normalizedPreferences = {
                        sort: omoDocumentsNormalizeSortPreference(preferences && preferences.sort ? preferences.sort : null),
                        density: omoDocumentsNormalizeDensityPreference(preferences && preferences.density ? preferences.density : null)
                    };

                    try {
                        if (window.localStorage) {
                            window.localStorage.setItem(
                                omoDocumentsPreferencesStorageKey,
                                JSON.stringify(normalizedPreferences)
                            );
                        }
                    } catch (error) {
                    }

                    window.dispatchEvent(new CustomEvent('omo-documents-preferences-change', {
                        detail: normalizedPreferences
                    }));
                };

                window.omoInitDocumentsPanels = function (root) {
                        const scope = root instanceof Element ? root : document;

                        scope.querySelectorAll('.omo-documents').forEach(function (panel) {
                            if (panel.dataset.omoDocumentsReady === '1') {
                                return;
                            }

                            const results = panel.querySelector('[data-omo-documents-results]');
                            const dataNode = panel.querySelector('[data-omo-documents-data]');
                            const detailDrawer = panel.querySelector('[data-omo-document-detail-drawer]');
                            const detailBody = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-body]') : null;
                            const detailTitle = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-title]') : null;
                            const detailDescription = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-description]') : null;

                            if (!results || !dataNode) {
                                return;
                            }

                            let payload = null;

                            try {
                                payload = JSON.parse(dataNode.textContent || '{}');
                            } catch (error) {
                                return;
                            }

                            const documents = Array.isArray(payload.documents) ? payload.documents.slice() : [];
                            const groups = Array.isArray(payload.groups) ? payload.groups : [];

                            if (documents.length === 0) {
                                panel.dataset.omoDocumentsReady = '1';
                                return;
                            }

                            panel.dataset.omoDocumentsReady = '1';
                            const savedPreferences = omoDocumentsReadPreferences();

                            const state = {
                                sort: savedPreferences.sort,
                                density: savedPreferences.density,
                                activeDocumentId: detailDrawer && detailDrawer.dataset.omoDocumentActiveId
                                    ? Number(detailDrawer.dataset.omoDocumentActiveId)
                                    : null
                            };
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

                            const createItem = function (documentItem) {
                                const shell = document.createElement('article');
                                shell.className = 'omo-documents__item-shell';

                                const link = document.createElement('div');
                                link.className = 'omo-documents__item omo-card omo-card--interactive';
                                link.setAttribute('role', 'button');
                                link.setAttribute('tabindex', '0');
                                link.setAttribute('data-omo-document-id', documentItem.id || '');
                                link.setAttribute('data-omo-document-href', documentItem.href || '');
                                link.setAttribute('data-omo-document-context-url', documentItem.contextUrl || '');
                                link.setAttribute('data-omo-document-title', documentItem.title || '');
                                link.setAttribute('data-omo-document-full-date', documentItem.fullDateLabel || '');

                                if (state.density === 'compact') {
                                    link.classList.add('omo-documents__item--compact');
                                    shell.classList.add('omo-documents__item-shell--compact');
                                }

                                const head = document.createElement('div');
                                head.className = 'omo-documents__item-head';

                                const date = document.createElement('span');
                                date.className = 'omo-documents__date';
                                date.textContent = state.sort === 'alpha'
                                    ? (documentItem.fullDateLabel || documentItem.dateLabel || '')
                                    : (documentItem.dateLabel || '');

                                const title = document.createElement('strong');
                                title.textContent = documentItem.title || '';

                                head.appendChild(date);
                                head.appendChild(title);
                                link.appendChild(head);

                                if (Array.isArray(documentItem.contextBreadcrumb) && documentItem.contextBreadcrumb.length > 0) {
                                    const context = document.createElement('div');
                                    context.className = 'omo-documents__context';
                                    context.setAttribute('aria-label', 'Contexte du document');

                                    documentItem.contextBreadcrumb.forEach(function (breadcrumbItem, breadcrumbIndex) {
                                        if (breadcrumbIndex > 0) {
                                            const separator = document.createElement('span');
                                            separator.className = 'omo-documents__context-separator';
                                            separator.textContent = '›';
                                            context.appendChild(separator);
                                        }

                                        const contextButton = document.createElement('button');
                                        contextButton.type = 'button';
                                        contextButton.className = 'omo-documents__context-link';
                                        contextButton.setAttribute('data-omo-document-context-jump', '1');
                                        contextButton.setAttribute('data-omo-document-context-jump-oid', String(breadcrumbItem && breadcrumbItem.organizationId ? breadcrumbItem.organizationId : ''));
                                        contextButton.setAttribute('data-omo-document-context-jump-cid', String(breadcrumbItem && breadcrumbItem.holonId ? breadcrumbItem.holonId : '0'));
                                        contextButton.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                        context.appendChild(contextButton);
                                    });

                                    link.appendChild(context);
                                } else if (documentItem.contextLabel) {
                                    const context = document.createElement('div');
                                    context.className = 'omo-documents__context';
                                    context.textContent = documentItem.contextLabel;
                                    link.appendChild(context);
                                }

                                if (documentItem.visibilityBadge) {
                                    const metaRow = document.createElement('div');
                                    metaRow.className = 'omo-documents__meta-row';

                                    const visibilityBadge = document.createElement('span');
                                    visibilityBadge.className = 'omo-pill';
                                    visibilityBadge.textContent = documentItem.visibilityBadge;

                                    metaRow.appendChild(visibilityBadge);
                                    link.appendChild(metaRow);
                                }

                                if (state.density !== 'compact' && documentItem.description) {
                                    const description = document.createElement('p');
                                    description.textContent = documentItem.description;
                                    link.appendChild(description);
                                }

                                if (state.density !== 'compact' && documentItem.keywords) {
                                    const keywords = document.createElement('div');
                                    keywords.className = 'omo-documents__keywords';
                                    keywords.textContent = documentItem.keywords;
                                    link.appendChild(keywords);
                                }

                                shell.appendChild(link);

                                if (documentItem.canEdit && documentItem.editUrl) {
                                    const menu = document.createElement('div');
                                    menu.className = 'omo-documents__menu';
                                    menu.setAttribute('data-omo-document-menu', '1');

                                    const toggle = document.createElement('button');
                                    toggle.type = 'button';
                                    toggle.className = 'omo-documents__menu-toggle';
                                    toggle.setAttribute('data-omo-document-menu-toggle', '1');
                                    toggle.setAttribute('aria-haspopup', 'menu');
                                    toggle.setAttribute('aria-expanded', 'false');
                                    toggle.setAttribute('aria-label', 'Actions pour ' + String(documentItem.title || 'ce document'));
                                    toggle.textContent = '...';

                                    const panel = document.createElement('div');
                                    panel.className = 'omo-documents__menu-panel';
                                    panel.setAttribute('data-omo-document-menu-panel', '1');
                                    panel.hidden = true;

                                    const editButton = document.createElement('button');
                                    editButton.type = 'button';
                                    editButton.className = 'omo-documents__menu-item';
                                    editButton.setAttribute('data-omo-document-edit', '1');
                                    editButton.setAttribute('data-omo-document-edit-url', String(documentItem.editUrl || ''));
                                    editButton.setAttribute('data-omo-document-edit-title', String(documentItem.title || ''));
                                    editButton.textContent = 'Editer';

                                    panel.appendChild(editButton);
                                    menu.appendChild(toggle);
                                    menu.appendChild(panel);
                                    shell.appendChild(menu);
                                }

                                return shell;
                            };

                            const setDetailHeader = function (documentItem) {
                                if (!detailTitle || !detailDescription) {
                                    return;
                                }

                                detailTitle.textContent = documentItem && documentItem.title
                                    ? documentItem.title
                                    : 'Détail du document';
                                detailDescription.textContent = documentItem && documentItem.fullDateLabel
                                    ? 'Document créé le ' + documentItem.fullDateLabel + '.'
                                    : 'Lecture du document dans OMO.';
                            };

                            const openDetailDrawer = function () {
                                if (!detailDrawer) {
                                    return;
                                }

                                detailDrawer.hidden = false;

                                requestAnimationFrame(function () {
                                    detailDrawer.classList.add('is-open');
                                });
                            };

                            const closeDetailDrawer = function () {
                                if (!detailDrawer) {
                                    return;
                                }

                                detailDrawer.classList.remove('is-open');
                                detailDrawer.dataset.omoDocumentActiveId = '';
                                state.activeDocumentId = null;

                                window.setTimeout(function () {
                                    if (!detailDrawer.classList.contains('is-open')) {
                                        detailDrawer.hidden = true;
                                    }
                                }, 200);
                            };

                            const renderDetailLoading = function () {
                                if (!detailBody) {
                                    return;
                                }

                                detailBody.innerHTML = window.getSkeleton
                                    ? getSkeleton('panel')
                                    : '<div class="loading">Chargement…</div>';
                            };

                            const renderDetailError = function () {
                                if (!detailBody) {
                                    return;
                                }

                                detailBody.innerHTML = '<div class="loading"><div class="omo-empty-state">Impossible de charger ce document.</div></div>';
                            };

                            const openDocumentDetail = function (documentItem) {
                                if (!detailDrawer || !detailBody || !documentItem || !documentItem.id) {
                                    return;
                                }

                                state.activeDocumentId = Number(documentItem.id);
                                detailDrawer.dataset.omoDocumentActiveId = String(documentItem.id);
                                setDetailHeader(documentItem);
                                renderDetailLoading();
                                openDetailDrawer();

                                const requestToken = ++detailRequestToken;
                                const detailUrl = documentItem.contextUrl
                                    ? String(documentItem.contextUrl)
                                    : '/omo/api/documents/detail.php?id=' + encodeURIComponent(documentItem.id);

                                $.ajax({
                                    url: detailUrl,
                                    method: 'GET',
                                    cache: false,
                                    success: function (data) {
                                        if (requestToken !== detailRequestToken || state.activeDocumentId !== Number(documentItem.id)) {
                                            return;
                                        }

                                        detailBody.innerHTML = data;
                                    },
                                    error: function () {
                                        if (requestToken !== detailRequestToken) {
                                            return;
                                        }

                                        renderDetailError();
                                    }
                                });
                            };

                            const renderByDate = function () {
                                const fragment = document.createDocumentFragment();
                                const groupedDocuments = new Map();

                                sortByDate(documents).forEach(function (documentItem) {
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
                                    section.className = 'omo-documents__group omo-panel-group';

                                    const title = document.createElement('h3');
                                    title.className = 'omo-panel-group__title';
                                    title.textContent = group.label || '';

                                    const list = document.createElement('div');
                                    list.className = 'omo-documents__list omo-panel-view__body_content';

                                    items.forEach(function (documentItem) {
                                        list.appendChild(createItem(documentItem));
                                    });

                                    section.appendChild(title);
                                    section.appendChild(list);
                                    fragment.appendChild(section);
                                });

                                results.replaceChildren(fragment);
                            };

                            const renderByAlpha = function () {
                                const list = document.createElement('div');
                                list.className = 'omo-documents__list omo-documents__list--alphabetical omo-panel-view__body_content';

                                sortByAlpha(documents).forEach(function (documentItem) {
                                    list.appendChild(createItem(documentItem));
                                });

                                results.replaceChildren(list);
                            };

                            const syncButtons = function (selector, activeValue, attributeName) {
                                panel.querySelectorAll(selector).forEach(function (button) {
                                    const isActive = button.getAttribute(attributeName) === activeValue;
                                    button.classList.toggle('is-active', isActive);
                                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                                });
                            };

                            const render = function () {
                                if (state.sort === 'alpha') {
                                    renderByAlpha();
                                } else {
                                    renderByDate();
                                }

                                syncButtons('[data-omo-documents-sort]', state.sort, 'data-omo-documents-sort');
                                syncButtons('[data-omo-documents-density]', state.density, 'data-omo-documents-density');
                            };

                            const findDocumentItemById = function (documentId) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return null;
                                }

                                return documents.find(function (item) {
                                    return Number(item.id || 0) === resolvedDocumentId;
                                }) || null;
                            };

                            const openInitialDocumentFromPayload = function () {
                                const documentId = Number(payload.openDocumentId || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return false;
                                }

                                payload.openDocumentId = 0;

                                const documentItem = findDocumentItemById(documentId);
                                if (!documentItem || typeof window.omoOpenDocumentDetailByPayload !== 'function') {
                                    return false;
                                }

                                window.omoOpenDocumentDetailByPayload(documentItem, panel);
                                return true;
                            };

                            panel.querySelectorAll('[data-omo-documents-sort]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    const nextSort = omoDocumentsNormalizeSortPreference(
                                        button.getAttribute('data-omo-documents-sort')
                                    );

                                    if (!nextSort || nextSort === state.sort) {
                                        return;
                                    }

                                    state.sort = nextSort;
                                    omoDocumentsWritePreferences({
                                        sort: state.sort,
                                        density: state.density
                                    });
                                    render();
                                });
                            });

                            panel.querySelectorAll('[data-omo-documents-density]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    const nextDensity = omoDocumentsNormalizeDensityPreference(
                                        button.getAttribute('data-omo-documents-density')
                                    );

                                    if (!nextDensity || nextDensity === state.density) {
                                        return;
                                    }

                                    state.density = nextDensity;
                                    omoDocumentsWritePreferences({
                                        sort: state.sort,
                                        density: state.density
                                    });
                                    render();
                                });
                            });

                            panel.addEventListener('click', function (event) {
                                const contextJump = event.target.closest('[data-omo-document-context-jump]');
                                if (contextJump) {
                                    event.preventDefault();
                                    event.stopPropagation();

                                    const targetOrganizationId = Number(contextJump.getAttribute('data-omo-document-context-jump-oid') || panel.getAttribute('data-omo-document-oid') || 0);
                                    const targetHolonId = Number(contextJump.getAttribute('data-omo-document-context-jump-cid') || 0);
                                    if (!Number.isInteger(targetOrganizationId) || targetOrganizationId <= 0) {
                                        return;
                                    }

                                    const hashState = typeof window.omoParsePopupHashState === 'function'
                                        ? window.omoParsePopupHashState()
                                        : { popupToken: null };
                                    const nextHash = typeof window.omoBuildHashFromState === 'function'
                                        ? window.omoBuildHashFromState('documents', hashState && hashState.popupToken ? String(hashState.popupToken) : null)
                                        : 'documents';

                                    if (typeof window.omoNavigate === 'function') {
                                        window.omoNavigate(
                                            targetOrganizationId,
                                            Number.isInteger(targetHolonId) && targetHolonId > 0 ? targetHolonId : null,
                                            nextHash
                                        );
                                        return;
                                    }

                                    window.location.href = '/omo/' + (Number.isInteger(targetHolonId) && targetHolonId > 0 ? ('c/' + encodeURIComponent(String(targetHolonId))) : '') + (nextHash ? ('#' + nextHash) : '');
                                    return;
                                }

                                const closeTrigger = event.target.closest('[data-omo-document-detail-close]');

                                if (closeTrigger) {
                                    event.preventDefault();
                                    closeDetailDrawer();
                                    return;
                                }

                                const trigger = event.target.closest('[data-omo-document-id]');

                                if (!trigger || !panel.contains(trigger)) {
                                    return;
                                }

                                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                                    return;
                                }

                                event.preventDefault();

                                const documentId = Number(trigger.getAttribute('data-omo-document-id'));

                                if (!documentId || Number.isNaN(documentId)) {
                                    return;
                                }

                                const documentItem = documents.find(function (item) {
                                    return Number(item.id) === documentId;
                                });

                                if (!documentItem) {
                                    return;
                                }

                                openDocumentDetail(documentItem);
                            });

                            panel.addEventListener('keydown', function (event) {
                                const card = event.target.closest('[data-omo-document-id]');
                                if (
                                    card
                                    && panel.contains(card)
                                    && (event.key === 'Enter' || event.key === ' ')
                                    && !event.target.closest('[data-omo-document-context-jump]')
                                ) {
                                    event.preventDefault();
                                    const documentItem = findDocumentItemById(Number(card.getAttribute('data-omo-document-id') || 0));
                                    if (documentItem) {
                                        openDocumentDetail(documentItem);
                                    }
                                    return;
                                }

                                if (event.key === 'Escape' && detailDrawer && detailDrawer.classList.contains('is-open')) {
                                    closeDetailDrawer();
                                }
                            });

                            render();
                            window.setTimeout(openInitialDocumentFromPayload, 0);

                            if (!panel.__omoDocumentsRouteHandler) {
                                panel.__omoDocumentsRouteHandler = function (routeEvent) {
                                    const detail = routeEvent && routeEvent.detail ? routeEvent.detail : {};
                                    const targetDocumentId = Number(detail.documentId || 0);

                                    if (targetDocumentId > 0) {
                                        const documentItem = findDocumentItemById(targetDocumentId);
                                        if (documentItem && typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                            window.omoOpenDocumentDetailByPayload(documentItem, panel);
                                        }
                                        return;
                                    }

                                    closeDetailDrawer();
                                };

                                window.addEventListener('omo-documents-route-change', panel.__omoDocumentsRouteHandler);
                            }

                            if (!panel.__omoDocumentsPreferencesHandler) {
                                panel.__omoDocumentsPreferencesHandler = function (preferenceEvent) {
                                    const detail = preferenceEvent && preferenceEvent.detail ? preferenceEvent.detail : {};
                                    const nextSort = omoDocumentsNormalizeSortPreference(detail.sort);
                                    const nextDensity = omoDocumentsNormalizeDensityPreference(detail.density);

                                    if (nextSort === state.sort && nextDensity === state.density) {
                                        return;
                                    }

                                    state.sort = nextSort;
                                    state.density = nextDensity;
                                    render();
                                };

                                window.addEventListener('omo-documents-preferences-change', panel.__omoDocumentsPreferencesHandler);
                            }
                        });
                    };

                window.omoInitDocumentsPanels();
            })();
            </script>
        <?php endif; ?>

        <div class="omo-overlay-drawer omo-documents__editor-drawer" data-omo-document-editor-drawer hidden>
            <div class="omo-overlay-drawer__backdrop" data-omo-document-editor-close></div>
            <div class="omo-overlay-drawer__panel">
                <div class="omo-overlay-drawer__header">
                    <div class="omo-overlay-drawer__header-copy">
                        <h3 class="omo-overlay-drawer__title" data-omo-document-editor-title>Nouveau document</h3>
                        <p class="omo-overlay-drawer__description" data-omo-document-editor-description>Creation d un document dans le contexte courant.</p>
                    </div>
                    <button type="button" class="omo-overlay-drawer__close" data-omo-document-editor-close>Fermer</button>
                </div>
                <div class="omo-overlay-drawer__body" data-omo-document-editor-body></div>
            </div>
        </div>

        <script>
        (function () {
            window.omoInitDocumentsScopePanels = function (root) {
                    const scope = root instanceof Element ? root : document;

                    scope.querySelectorAll('.omo-documents').forEach(function (panel) {
                        if (panel.dataset.omoDocumentsScopeReady === '1') {
                            return;
                        }

                        let documentScopeRefreshToken = 0;

                        const getCurrentScope = function () {
                            return String(panel.getAttribute('data-omo-document-scope') || 'contextual').trim().toLowerCase() === 'global'
                                ? 'global'
                                : 'contextual';
                        };

                        const buildScopeUrl = function (scopeValue) {
                            const resolvedScope = String(scopeValue || '').trim().toLowerCase() === 'global'
                                ? 'global'
                                : 'contextual';
                            const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
                            const holonId = Number(panel.getAttribute('data-omo-document-cid') || 0);
                            const query = [];

                            if (organizationId > 0) {
                                query.push('oid=' + encodeURIComponent(String(organizationId)));
                            }

                            if (holonId > 0) {
                                query.push('cid=' + encodeURIComponent(String(holonId)));
                            }

                            if (resolvedScope !== 'contextual') {
                                query.push('document_scope=' + encodeURIComponent(resolvedScope));
                            }

                            return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
                        };

                        const setScopeLoadingState = function (isLoading, targetScope) {
                            panel.classList.toggle('is-loading', Boolean(isLoading));

                            panel.querySelectorAll('[data-omo-document-scope-toggle]').forEach(function (button) {
                                const buttonScope = String(button.getAttribute('data-omo-document-scope-toggle') || '').trim().toLowerCase();
                                const isActive = buttonScope === targetScope;

                                button.disabled = Boolean(isLoading);
                                button.classList.toggle('is-active', isActive);
                                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            });

                            const toggle = panel.querySelector('[data-omo-scope-switch]');
                            if (toggle) {
                                toggle.setAttribute('data-omo-scope-switch', targetScope);
                            }
                        };

                        panel.addEventListener('click', function (event) {
                            const button = event.target.closest('[data-omo-document-scope-toggle]');
                            if (!button || !panel.contains(button)) {
                                return;
                            }

                            const targetScope = String(button.getAttribute('data-omo-document-scope-toggle') || '').trim().toLowerCase() === 'global'
                                ? 'global'
                                : 'contextual';

                            if (targetScope === getCurrentScope()) {
                                return;
                            }

                            if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                                window.location.href = buildScopeUrl(targetScope);
                                return;
                            }

                            const requestId = ++documentScopeRefreshToken;

                            window.omoReplaceFetchedPanelRoot({
                                rootSelector: '#omo-documents-root',
                                currentRoot: panel,
                                url: buildScopeUrl(targetScope),
                                setLoadingState: function (isLoading) {
                                    if (requestId !== documentScopeRefreshToken && !isLoading) {
                                        return;
                                    }

                                    setScopeLoadingState(isLoading, targetScope);
                                }
                            });
                        });

                        panel.dataset.omoDocumentsScopeReady = '1';
                    });
                };

            window.omoInitDocumentsScopePanels();
        })();
        </script>
        <script>
        window.omoToggleDocumentsScope = function (button, event) {
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

            const panel = button.closest('#omo-documents-root');
            if (!panel) {
                return false;
            }

            const currentScope = String(panel.getAttribute('data-omo-document-scope') || 'contextual').trim().toLowerCase() === 'global'
                ? 'global'
                : 'contextual';
            const targetScope = String(button.getAttribute('data-omo-document-scope-toggle') || '').trim().toLowerCase() === 'global'
                ? 'global'
                : 'contextual';

            if (targetScope === currentScope) {
                return false;
            }

            const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
            const holonId = Number(panel.getAttribute('data-omo-document-cid') || 0);
            const query = [];

            if (organizationId > 0) {
                query.push('oid=' + encodeURIComponent(String(organizationId)));
            }

            if (holonId > 0) {
                query.push('cid=' + encodeURIComponent(String(holonId)));
            }

            query.push('document_scope=' + encodeURIComponent(targetScope));
            query.push('_=' + String(Date.now()));

            const targetUrl = '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');

            panel.classList.add('is-loading');

            if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                window.location.href = targetUrl;
                return false;
            }

            window.omoReplaceFetchedPanelRoot({
                rootSelector: '#omo-documents-root',
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
        </script>
        <script>
        (function () {
            function getDocumentsRoot() {
                return document.getElementById('omo-documents-root');
            }

            function buildDocumentsPanelUrl(root) {
                if (!root) {
                    return '/omo/api/documents/index.php';
                }

                const organizationId = Number(root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root.getAttribute('data-omo-document-cid') || 0);
                const scope = String(root.getAttribute('data-omo-document-scope') || 'contextual').trim().toLowerCase() === 'global'
                    ? 'global'
                    : 'contextual';
                const query = [];

                if (organizationId > 0) {
                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                }

                if (holonId > 0) {
                    query.push('cid=' + encodeURIComponent(String(holonId)));
                }

                if (scope !== 'contextual') {
                    query.push('document_scope=' + encodeURIComponent(scope));
                }

                query.push('_=' + String(Date.now()));

                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
            }

            function executeFetchedScripts(container) {
                if (!container) {
                    return;
                }

                Array.from(container.querySelectorAll('script')).forEach(function (script) {
                    const executableScript = document.createElement('script');
                    Array.from(script.attributes).forEach(function (attribute) {
                        executableScript.setAttribute(attribute.name, attribute.value);
                    });
                    executableScript.text = script.textContent || '';
                    document.body.appendChild(executableScript);
                    document.body.removeChild(executableScript);
                });
            }

            function openEditorDrawer(url, title, description) {
                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-editor-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-editor-body]') : null;
                const titleNode = drawer ? drawer.querySelector('[data-omo-document-editor-title]') : null;
                const descriptionNode = drawer ? drawer.querySelector('[data-omo-document-editor-description]') : null;
                const targetUrl = String(url || '').trim();

                if (!drawer || !body || targetUrl === '') {
                    return;
                }

                if (titleNode) {
                    titleNode.textContent = String(title || 'Nouveau document').trim() || 'Nouveau document';
                }

                if (descriptionNode) {
                    descriptionNode.textContent = String(description || 'Creation d un document dans le contexte courant.').trim()
                        || 'Creation d un document dans le contexte courant.';
                }

                body.innerHTML = window.getSkeleton
                    ? getSkeleton('panel')
                    : '<div class="loading">Chargement...</div>';

                drawer.hidden = false;
                requestAnimationFrame(function () {
                    drawer.classList.add('is-open');
                });

                fetch(targetUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('document_editor_load_failed');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        body.innerHTML = html;
                        executeFetchedScripts(temp);
                    })
                    .catch(function () {
                        body.innerHTML = '<div class="omo-empty-state">Impossible de charger l editeur du document.</div>';
                    });
            }

            window.omoCloseDocumentEditorDrawer = function () {
                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-editor-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-editor-body]') : null;

                if (!drawer) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('omo-document-editor-drawer-close'));
                drawer.classList.remove('is-open');

                window.setTimeout(function () {
                    if (!drawer.classList.contains('is-open')) {
                        drawer.hidden = true;
                        if (body) {
                            body.innerHTML = '';
                        }
                    }
                }, 200);
            };

            window.omoRefreshDocumentsPanel = function () {
                const root = getDocumentsRoot();
                if (!root || typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                    return Promise.resolve(null);
                }

                return window.omoReplaceFetchedPanelRoot({
                    rootSelector: '#omo-documents-root',
                    currentRoot: root,
                    url: buildDocumentsPanelUrl(root),
                    setLoadingState: function (isLoading) {
                        root.classList.toggle('is-loading', !!isLoading);
                    }
                });
            };

            window.omoOpenDocumentEditorDrawer = function (url, title, description) {
                openEditorDrawer(url, title, description);
            };

            window.omoCloseDocumentDetailDrawer = function () {
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
                if (/^documents-\d+$/i.test(routeToken) && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('documents');
                    return;
                }

                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;

                if (!drawer) {
                    return;
                }

                drawer.classList.remove('is-open');
                window.setTimeout(function () {
                    if (!drawer.classList.contains('is-open')) {
                        drawer.hidden = true;
                        if (body) {
                            body.innerHTML = '';
                        }
                    }
                }, 200);
            };

            window.omoOpenDocumentDetailByPayload = function (documentItem, rootOverride) {
                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;
                const titleNode = drawer ? drawer.querySelector('[data-omo-document-detail-title]') : null;
                const descriptionNode = drawer ? drawer.querySelector('[data-omo-document-detail-description]') : null;
                const detailUrl = String(documentItem && documentItem.contextUrl ? documentItem.contextUrl : '').trim();
                const title = String(documentItem && documentItem.title ? documentItem.title : '').trim();
                const fullDate = String(documentItem && documentItem.fullDateLabel ? documentItem.fullDateLabel : '').trim();

                if (!drawer || !body || detailUrl === '') {
                    return false;
                }

                if (titleNode) {
                    titleNode.textContent = title !== '' ? title : 'Detail du document';
                }

                if (descriptionNode) {
                    descriptionNode.textContent = fullDate !== ''
                        ? 'Document cree le ' + fullDate + '.'
                        : 'Lecture du document dans OMO.';
                }

                body.innerHTML = window.getSkeleton
                    ? getSkeleton('panel')
                    : '<div class="loading">Chargement...</div>';

                drawer.hidden = false;
                requestAnimationFrame(function () {
                    drawer.classList.add('is-open');
                });

                fetch(detailUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('document_detail_load_failed');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        body.innerHTML = html;
                        executeFetchedScripts(temp);
                    })
                    .catch(function () {
                        body.innerHTML = '<div class="omo-empty-state">Impossible de charger ce document.</div>';
                    });

                return true;
            };

            window.omoOpenDocumentDetailFromTrigger = function (trigger, event) {
                if (!(trigger instanceof Element)) {
                    return true;
                }

                const documentId = Number(trigger.getAttribute('data-omo-document-id') || 0);
                const routeToken = typeof window.omoBuildDocumentRouteToken === 'function'
                    ? window.omoBuildDocumentRouteToken(documentId)
                    : (Number.isInteger(documentId) && documentId > 0 ? ('documents-' + documentId) : null);
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const currentRouteToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';

                if (event) {
                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return true;
                    }
                    event.preventDefault();
                }

                if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                    window.omoOpenDrawerHashState(routeToken);
                    return false;
                }

                const root = trigger.closest('#omo-documents-root') || getDocumentsRoot();
                const opened = window.omoOpenDocumentDetailByPayload({
                    contextUrl: String(trigger.getAttribute('data-omo-document-context-url') || '').trim(),
                    title: String(trigger.getAttribute('data-omo-document-title') || '').trim(),
                    fullDateLabel: String(trigger.getAttribute('data-omo-document-full-date') || '').trim()
                }, root);

                if (!opened) {
                    return true;
                }

                return false;
            };

            const root = getDocumentsRoot();
            if (!root || root.dataset.omoDocumentsDrawerReady === '1') {
                return;
            }

            root.dataset.omoDocumentsDrawerReady = '1';

            function closeDocumentMenus() {
                root.querySelectorAll('[data-omo-document-menu="1"]').forEach(function (menu) {
                    menu.classList.remove('is-open');
                    const panel = menu.querySelector('[data-omo-document-menu-panel="1"]');
                    const toggle = menu.querySelector('[data-omo-document-menu-toggle="1"]');
                    if (panel) {
                        panel.hidden = true;
                    }
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            root.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-omo-document-menu-toggle="1"]');
                if (toggle) {
                    event.preventDefault();
                    event.stopPropagation();

                    const menu = toggle.closest('[data-omo-document-menu="1"]');
                    const willOpen = !!menu && !menu.classList.contains('is-open');
                    closeDocumentMenus();

                    if (!menu || !willOpen) {
                        return;
                    }

                    menu.classList.add('is-open');
                    const panel = menu.querySelector('[data-omo-document-menu-panel="1"]');
                    if (panel) {
                        panel.hidden = false;
                    }
                    toggle.setAttribute('aria-expanded', 'true');
                    return;
                }

                const editButton = event.target.closest('[data-omo-document-edit="1"]');
                if (editButton) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    openEditorDrawer(
                        String(editButton.getAttribute('data-omo-document-edit-url') || '').trim(),
                        'Editer le document',
                        'Modification du document dans le contexte courant.'
                    );
                    return;
                }

                if (!event.target.closest('[data-omo-document-menu="1"]')) {
                    closeDocumentMenus();
                }
            });

            root.querySelectorAll('[data-omo-document-editor-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.omoCloseDocumentEditorDrawer();
                });
            });

            root.querySelectorAll('[data-omo-document-detail-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.omoCloseDocumentDetailDrawer();
                });
            });

            const newButton = root.querySelector('[data-omo-documents-new]');
            if (newButton) {
                newButton.addEventListener('click', function () {
                    const targetUrl = String(newButton.getAttribute('data-omo-documents-new-url') || '').trim();
                    openEditorDrawer(targetUrl, 'Nouveau document', 'Creation d un document dans le contexte courant.');
                });
            }
        })();
        </script>
    </div>
</div>

<style>
.omo-documents__title-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
}

.omo-documents__count {
    min-width: 0;
}

.omo-documents__aside {
    display: flex;
    width: 100%;
    justify-content: flex-end;
}

.omo-documents__controls-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    grid-template-areas:
        ". . new"
        "scope . controls";
    align-items: center;
    gap: 12px;
    width: 100%;
}

.omo-documents__new-button {
    grid-area: new;
    justify-self: end;
}

.omo-documents__controls {
    grid-area: controls;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12px;
}

.omo-documents__controls-row .omo-scope-toggle {
    grid-area: scope;
}

.omo-documents__editor-drawer .omo-overlay-drawer__body {
    padding: 0;
}

.omo-documents__results {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.omo-documents__list {
    display: grid;
    gap: 12px;
}

.omo-documents__list--alphabetical {
    align-content: start;
}

.omo-documents__item-shell {
    position: relative;
}

.omo-documents__item {
    display: block;
    text-decoration: none;
    padding-right: 52px;
}

.omo-documents__item-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.omo-documents__date {
    color: var(--color-text-light);
    font-size: 0.92rem;
}

.omo-documents__item strong {
    line-height: 1.3;
}

.omo-documents__context {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 6px;
    margin-bottom: 8px;
    color: var(--color-text-light);
    font-size: 0.84rem;
    line-height: 1.4;
}

.omo-documents__context-separator {
    opacity: 0.65;
}

.omo-documents__context-link {
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    line-height: inherit;
    text-decoration: underline;
    text-underline-offset: 0.14em;
    cursor: pointer;
}

.omo-documents__context-link:hover {
    color: var(--color-primary);
}

.omo-documents__menu {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
}

.omo-documents__menu-toggle {
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    background: color-mix(in srgb, var(--color-surface) 92%, white);
    color: var(--color-text);
    cursor: pointer;
}

.omo-documents__menu-panel {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 140px;
    padding: 6px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.16);
}

.omo-documents__menu-item {
    display: block;
    width: 100%;
    padding: 9px 10px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
}

.omo-documents__menu-item:hover {
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.omo-documents__meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.omo-documents__item p {
    margin: 0 0 10px;
    color: var(--color-text-light);
}

.omo-documents__keywords {
    font-size: 0.92rem;
    color: var(--color-primary);
}

.omo-documents__item--compact {
    padding: 12px 14px;
    padding-right: 52px;
}

.omo-documents__item--compact .omo-documents__item-head {
    margin-bottom: 0;
    gap: 0;
}

.omo-documents__item--compact .omo-documents__context,
.omo-documents__item--compact .omo-documents__date,
.omo-documents__item--compact p,
.omo-documents__item--compact .omo-documents__keywords {
    display: none;
}

@media (max-width: 768px) {
    .omo-documents__title-row {
        flex-wrap: wrap;
        gap: 6px 10px;
    }

    .omo-documents__aside {
        align-items: stretch;
        justify-content: flex-start;
    }

    .omo-documents__controls-row,
    .omo-documents__controls {
        justify-content: flex-start;
    }

    .omo-documents__controls-row {
        grid-template-columns: 1fr;
        grid-template-areas:
            "new"
            "scope"
            "controls";
    }

    .omo-documents__new-button {
        justify-self: stretch;
    }
}
</style>
