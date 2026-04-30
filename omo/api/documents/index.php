<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$currentOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;

$documents = new \dbObject\ArrayDocument();
if ($currentOrganizationId > 0) {
    $loadParams = [
        'where' => [
            ['field' => 'IDorganization', 'value' => $currentOrganizationId],
        ],
        'orderBy' => [
            ['field' => 'datecreation', 'dir' => 'DESC'],
        ],
    ];

    if ($currentHolonId > 0) {
        $loadParams['where'][] = ['field' => 'IDholon', 'value' => $currentHolonId];
    } else {
        $loadParams['where'][] = ['field' => 'IDholon', 'op' => 'is null'];
    }

    $documents->load($loadParams);
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
    $groupIndex = sharedGetRelativeDateGroupIndexForDate($createdAt, $groups, $today);
    $group = $groups[$groupIndex] ?? ['key' => 'too_far', 'label' => 'Trop loin'];
    $groupKey = (string)($group['key'] ?? 'too_far');

    $documentEntries[] = [
        'id' => (int)$document->getId(),
        'href' => '/memo/' . (int)$document->getId(),
        'title' => (string)$document->get('title'),
        'description' => trim((string)$document->get('description')),
        'keywords' => trim((string)$document->get('keywords')),
        'dateLabel' => $formatDate($createdAt, in_array($groupKey, ['earlier', 'too_far'], true)),
        'fullDateLabel' => $formatDate($createdAt, true),
        'timestamp' => $createdAt instanceof DateTimeInterface ? (int)$createdAt->getTimestamp() : 0,
        'groupKey' => $groupKey,
        'groupLabel' => (string)($group['label'] ?? 'Trop loin'),
        'sortTitle' => $normalizeSortValue($document->get('title')),
        'contextUrl' => '/omo/api/documents/detail.php?id=' . (int)$document->getId()
            . '&oid=' . $currentOrganizationId
            . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : ''),
    ];
}

$documentsPayload = json_encode(
    [
        'documents' => $documentEntries,
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
    $documentsPayload = '{"documents":[],"groups":[]}';
}
?>
<div class="omo-documents omo-panel-view">
    <div class="omo-documents__header omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title">Documents</h2>
        </div>
        <div class="omo-panel-view__aside">
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

            <div class="omo-documents__count omo-panel-view__count">
                <?= $escape(count($documentEntries)) ?> document<?= count($documentEntries) > 1 ? 's' : '' ?>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <?php if (count($documentEntries) === 0): ?>
            <div class="omo-documents__empty omo-empty-state">
                Aucun document disponible pour ce compte.
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
                            <a
                                class="omo-documents__item omo-card omo-card--interactive"
                                href="<?= $escape($entry['href']) ?>"
                                target="_blank"
                                rel="noopener"
                                data-omo-document-id="<?= $escape($entry['id']) ?>"
                            >
                                <div class="omo-documents__item-head">
                                    <span class="omo-documents__date"><?= $escape($entry['dateLabel']) ?></span>
                                    <strong><?= $escape($entry['title']) ?></strong>
                                </div>

                                <?php if ($entry['description'] !== ''): ?>
                                    <p><?= $escape($entry['description']) ?></p>
                                <?php endif; ?>

                                <?php if ($entry['keywords'] !== ''): ?>
                                    <div class="omo-documents__keywords"><?= $escape($entry['keywords']) ?></div>
                                <?php endif; ?>
                            </a>
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
                if (typeof window.omoInitDocumentsPanels !== 'function') {
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

                            const state = {
                                sort: 'date',
                                density: 'detail',
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
                                const link = document.createElement('a');
                                link.className = 'omo-documents__item omo-card omo-card--interactive';
                                link.href = documentItem.href || '#';
                                link.target = '_blank';
                                link.rel = 'noopener';
                                link.setAttribute('data-omo-document-id', documentItem.id || '');

                                if (state.density === 'compact') {
                                    link.classList.add('omo-documents__item--compact');
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

                                return link;
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

                            panel.querySelectorAll('[data-omo-documents-sort]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    const nextSort = button.getAttribute('data-omo-documents-sort');

                                    if (!nextSort || nextSort === state.sort) {
                                        return;
                                    }

                                    state.sort = nextSort;
                                    render();
                                });
                            });

                            panel.querySelectorAll('[data-omo-documents-density]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    const nextDensity = button.getAttribute('data-omo-documents-density');

                                    if (!nextDensity || nextDensity === state.density) {
                                        return;
                                    }

                                    state.density = nextDensity;
                                    render();
                                });
                            });

                            panel.addEventListener('click', function (event) {
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
                                if (event.key === 'Escape' && detailDrawer && detailDrawer.classList.contains('is-open')) {
                                    closeDetailDrawer();
                                }
                            });

                            render();
                        });
                    };
                }

                window.omoInitDocumentsPanels();
            })();
            </script>
        <?php endif; ?>
    </div>
</div>

<style>
.omo-documents__controls {
    justify-content: flex-end;
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

.omo-documents__item {
    text-decoration: none;
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
}

.omo-documents__item--compact .omo-documents__item-head {
    margin-bottom: 0;
    gap: 0;
}

.omo-documents__item--compact .omo-documents__date,
.omo-documents__item--compact p,
.omo-documents__item--compact .omo-documents__keywords {
    display: none;
}

@media (max-width: 768px) {
    .omo-documents__controls {
        justify-content: flex-start;
    }
}
</style>
