<?php
require_once __DIR__ . '/bootstrap.php';
use dbObject\ArrayOrganization;
use dbObject\Holon;
use dbObject\PropertyFormat;

function omoSplitTextItems($text)
{
    $text = trim((string)$text);
    if ($text === '') {
        return array();
    }

    if (strpos($text, '|') !== false) {
        $parts = explode('|', $text);
    } else {
        $parts = preg_split('/\r\n|\r|\n/', $text);
    }

    $items = array();
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '') {
            $items[] = $part;
        }
    }

    return $items;
}

function omoParseListItems($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return array();
    }

    $decoded = json_decode($rawValue, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_values($decoded);
    }

    return omoSplitTextItems($rawValue);
}

function omoFormatListItemValue($item, array $entry)
{
    $listItemType = (string)($entry['listItemType'] ?? '');

    if ($listItemType === 'date') {
        $value = trim((string)$item);
        if ($value === '') {
            return '';
        }

        try {
            return (new DateTime($value))->format('d.m.Y');
        } catch (Exception $exception) {
            return $value;
        }
    }

    if ($listItemType === 'holon') {
        static $holonLabelCache = array();

        $holonId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
        if ($holonId <= 0) {
            return is_scalar($item) ? trim((string)$item) : '';
        }

        if (!isset($holonLabelCache[$holonId])) {
            $holon = new Holon();
            $holonLabelCache[$holonId] = ($holon->load($holonId) && $holon->canView())
                ? $holon->getDisplayName()
                : '';
        }

        return $holonLabelCache[$holonId];
    }

    if (is_array($item)) {
        return trim((string)($item['label'] ?? $item['value'] ?? ''));
    }

    return trim((string)$item);
}

function omoNormalizeDetailedListItem($item)
{
    if (is_array($item)) {
        return array(
            'title' => trim((string)($item['title'] ?? $item['label'] ?? $item['value'] ?? '')),
            'description' => trim((string)($item['description'] ?? $item['text'] ?? '')),
        );
    }

    return array(
        'title' => trim((string)$item),
        'description' => '',
    );
}

function omoRenderTextBlock($text, $className = 'section-text')
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }

    return '<div class="' . omoApiEscape($className) . '">' . nl2br(omoApiEscape($text)) . '</div>';
}

function omoRenderHtmlBlock($html, $className = 'section-html')
{
    $safeHtml = PropertyFormat::sanitizeHtml($html);
    if (PropertyFormat::isEmptyValue(PropertyFormat::FORMAT_HTML, $safeHtml)) {
        return '';
    }

    return '<div class="' . omoApiEscape($className) . '">' . $safeHtml . '</div>';
}

function omoRenderFormattedList(array $items, array $entry, $className = 'section-list')
{
    $html = '<ul class="' . omoApiEscape($className) . '">';
    foreach ($items as $item) {
        $formattedItem = omoFormatListItemValue($item, $entry);
        if ($formattedItem === '') {
            continue;
        }
        $html .= '<li>' . omoApiEscape($formattedItem) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function omoBuildListItemDescriptors(array $ancestorItems, array $currentItems)
{
    $descriptors = array();

    foreach ($ancestorItems as $item) {
        $key = is_array($item)
            ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : trim((string)$item);
        if ($key === '') {
            continue;
        }

        $descriptors[$key] = array(
            'item' => $item,
            'source' => 'inherited',
        );
    }

    foreach ($currentItems as $item) {
        $key = is_array($item)
            ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : trim((string)$item);
        if ($key === '') {
            continue;
        }

        $descriptors[$key] = array(
            'item' => $item,
            'source' => 'local',
        );
    }

    return array_values($descriptors);
}

function omoRenderMixedList(array $ancestorItems, array $currentItems, array $entry, $className = 'section-list')
{
    $descriptors = omoBuildListItemDescriptors($ancestorItems, $currentItems);
    if (count($descriptors) === 0) {
        return '';
    }

    $html = '<ul class="' . omoApiEscape($className) . '">';
    foreach ($descriptors as $descriptor) {
        $formattedItem = omoFormatListItemValue($descriptor['item'], $entry);
        if ($formattedItem === '') {
            continue;
        }

        $html .= '<li class="is-' . omoApiEscape($descriptor['source']) . '">' . omoApiEscape($formattedItem) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function omoRenderDetailedList(array $ancestorItems, array $currentItems, array $entry, $className = 'section-detail-list')
{
    $descriptors = omoBuildListItemDescriptors($ancestorItems, $currentItems);
    if (count($descriptors) === 0) {
        return '';
    }

    $html = '<div class="' . omoApiEscape($className) . '">';
    foreach ($descriptors as $descriptor) {
        $detailItem = omoNormalizeDetailedListItem($descriptor['item']);
        if ($detailItem['title'] === '' && $detailItem['description'] === '') {
            continue;
        }

        $html .= '<details class="section-detail-card is-' . omoApiEscape($descriptor['source']) . '">';
        $html .= '<summary>' . omoApiEscape($detailItem['title'] !== '' ? $detailItem['title'] : 'Element') . '</summary>';
        if ($detailItem['description'] !== '') {
            $html .= '<div class="section-detail-card__body">' . nl2br(omoApiEscape($detailItem['description'])) . '</div>';
        }
        $html .= '</details>';
    }
    $html .= '</div>';

    return $html;
}

function omoSplitInheritedTextBlocks($text)
{
    $text = trim((string)$text);
    if ($text === '') {
        return array();
    }

    return array_values(array_filter(array_map('trim', explode('|', $text)), function ($item) {
        return $item !== '';
    }));
}

function omoRenderSectionBody(array $entry)
{
    $value = trim((string)($entry['value'] ?? ''));
    $ancestor = trim((string)($entry['ancestor'] ?? ''));
    $effective = trim((string)($entry['effectiveValue'] ?? ''));
    $formatId = (int)($entry['formatId'] ?? 0);

    if ($effective === '') {
        return '';
    }

    if ($formatId === 2) {
        $currentItems = omoParseListItems($value);
        $ancestorItems = omoParseListItems($ancestor);
        if ((string)($entry['listItemType'] ?? '') === 'detail') {
            return omoRenderDetailedList($ancestorItems, $currentItems, $entry);
        }
        return omoRenderMixedList($ancestorItems, $currentItems, $entry);
    }

    if ($formatId === PropertyFormat::FORMAT_HTML) {
        $html = '';
        if ($ancestor !== '') {
            $html .= omoRenderHtmlBlock($ancestor, 'section-html section-text--inherited');
        }
        if ($value !== '') {
            $html .= omoRenderHtmlBlock($value, 'section-html section-text--local');
        }

        return $html;
    }

    $html = '';
    foreach (omoSplitInheritedTextBlocks($ancestor) as $ancestorBlock) {
        $html .= omoRenderTextBlock($ancestorBlock, 'section-text section-text--inherited');
    }

    if ($value !== '') {
        $html .= omoRenderTextBlock($value, 'section-text section-text--local');
    }

    return $html;
}

function omoEntryHasLocalDisplayValue(array $entry)
{
    $formatId = (int)($entry['formatId'] ?? 0);
    $value = $entry['value'] ?? '';

    if ($formatId === PropertyFormat::FORMAT_LIST) {
        return count(omoParseListItems($value)) > 0;
    }

    if ($formatId === PropertyFormat::FORMAT_HTML) {
        return !PropertyFormat::isEmptyValue(PropertyFormat::FORMAT_HTML, $value);
    }

    return trim((string)$value) !== '';
}

function omoNormalizeEntryDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    try {
        return new DateTime($value);
    } catch (Exception $exception) {
        return null;
    }
}

function omoResolveUserDisplayName($userId, $organizationId = 0)
{
    static $cache = array();

    $userId = (int)$userId;
    $organizationId = (int)$organizationId;
    if ($userId <= 0) {
        return '';
    }

    $cacheKey = $userId . ':' . $organizationId;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $user = new \dbObject\User();
    if (!$user->load($userId)) {
        $cache[$cacheKey] = '';
        return '';
    }

    if (!$user->canView()) {
        $cache[$cacheKey] = '';
        return '';
    }

    $cache[$cacheKey] = trim((string)$user->getScopedDisplayName($organizationId));
    return $cache[$cacheKey];
}

function omoRenderSectionUpdateMeta(array $entry, $organizationId = 0)
{
    if (!omoEntryHasLocalDisplayValue($entry)) {
        return '';
    }

    $updatedAt = omoNormalizeEntryDateTime($entry['updatedAt'] ?? null);
    if (!$updatedAt) {
        return '';
    }

    $metaText = 'Mis a jour le ' . $updatedAt->format('d.m.Y H:i');
    $updatedByName = omoResolveUserDisplayName((int)($entry['updatedByUserId'] ?? 0), (int)$organizationId);
    if ($updatedByName !== '') {
        $metaText .= ' par ' . $updatedByName;
    }

    return '<div class="section-update-meta">' . omoApiEscape($metaText) . '</div>';
}

function omoBuildSections(Holon $holon)
{
    $entries = $holon->getPropertyEntries();
    $sections = array();

    foreach ($entries as $entry) {
        $effective = trim((string)($entry['effectiveValue'] ?? ''));
        if ($effective === '') {
            continue;
        }

        $sections[] = array(
            'title' => trim((string)($entry['name'] ?: $entry['shortname'] ?: ('Propriete ' . $entry['id']))),
            'entry' => $entry,
        );
    }

    return $sections;
}

function omoBuildChildNavigation(Holon $holon)
{
    $items = array(
        'containers' => array(),
        'roles' => array(),
    );

    $children = $holon->getChildren();
    if (!$children) {
        return $items;
    }

    foreach ($children as $child) {
        $entry = array(
            'id' => (int)$child->getId(),
            'name' => trim((string)$child->get('name')),
            'type' => (int)$child->get('IDtypeholon'),
        );

        if ($entry['type'] === 1) {
            $items['roles'][] = $entry;
        } else {
            $items['containers'][] = $entry;
        }
    }

    return $items;
}

function omoGetHolonHeaderLabel(Holon $holon)
{
    return $holon->getTemplateLabel(true);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$cid = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="circle-panel"><div class="error">Organisation invalide.</div></div>
    <?php
    exit;
}

$organizations = new ArrayOrganization();
$organizations->load(array(
    'where' => array(
        array('field' => 'id', 'value' => $organizationId),
    ),
    'limit' => 1,
));

$organization = $organizations->get($organizationId);
if ($organization === null) {
    http_response_code(404);
    ?>
    <div class="circle-panel"><div class="error">Organisation introuvable.</div></div>
    <?php
    exit;
}

$canViewOrganization = $organization->canViewDetail();
if (!$canViewOrganization) {
    http_response_code(403);
    ?>
    <div class="circle-panel"><div class="error">Acces refuse a cette organisation.</div></div>
    <?php
    exit;
}

$root = $organization->getStructuralRootHolon();
if ($root === null) {
    require_once __DIR__ . '/organization_setup_panel.php';
    omoRenderOrganizationInfoPanel($organization);
    exit;
}

$root = $organization->getStructuralRootHolon();
if ($root === null) {
    http_response_code(404);
    ?>
    <div class="circle-panel"><div class="error">Aucune structure racine n'a &eacute;t&eacute; trouv&eacute;e pour cette organisation.</div></div>
    <?php
    exit;
}

$navigationRoot = $root;
$shareLink = function_exists('commonGetCurrentShareLink') ? commonGetCurrentShareLink() : null;
if ($shareLink && $shareLink->canViewOrganization($organizationId)) {
    $shareScopeHolon = $shareLink->getScopeHolon();
    if ($shareScopeHolon instanceof Holon) {
        $navigationRoot = $shareScopeHolon;
    }
}

$currentHolon = $navigationRoot;

if ($cid > 0) {
    $candidate = new Holon();
    if (!$candidate->load($cid) || !$candidate->isDescendantOf($navigationRoot->getId())) {
        http_response_code(404);
        ?>
        <div class="circle-panel"><div class="error">Holon introuvable pour cette organisation.</div></div>
        <?php
        exit;
    }

    if (!$candidate->canViewDetail()) {
        http_response_code(403);
        ?>
        <div class="circle-panel"><div class="error">Acces refuse a ce holon.</div></div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$breadcrumb = array_values(array_filter($currentHolon->getPathHolons(), function ($holon) use ($navigationRoot) {
    return $holon instanceof Holon && $holon->isDescendantOf($navigationRoot->getId(), true);
}));
$sections = omoBuildSections($currentHolon);
$childNavigation = omoBuildChildNavigation($currentHolon);
$holonTypeLabel = omoGetHolonHeaderLabel($currentHolon);
$selectedNodeClass = 'node_' . (int)$currentHolon->getId();
$memberCards = $currentHolon->getAssociatedMemberCards(array(
    'organizationId' => $organizationId,
));
if (function_exists('commonGetCurrentShareToken') && commonGetCurrentShareToken() !== '' && !commonCurrentShareAllowsPeople()) {
    $memberCards = array();
}
$memberPreviewLimit = 8;
$visibleMemberCards = array_slice($memberCards, 0, $memberPreviewLimit);
$hasHiddenMembers = count($memberCards) > count($visibleMemberCards);
$isOrganizationDefinitionHolon = (int)$currentHolon->get('IDtypeholon') === 4;
$isCurrentTemplateHolon = !$isOrganizationDefinitionHolon && $root ? $currentHolon->isTemplateNode((int)$root->getId()) : false;
$editTemplateContextId = $isCurrentTemplateHolon && $currentHolon->getParentHolon()
    ? (int)$currentHolon->getParentHolon()->getId()
    : ($isOrganizationDefinitionHolon ? (int)$currentHolon->getId() : 0);
$canManageMembers = $currentHolon->canEdit();
$canCreateChildHolon = $currentHolon->canEdit() && in_array((int)$currentHolon->get('IDtypeholon'), array(2, 3, 4), true);
$canEditHolon = $currentHolon->canEdit() && in_array((int)$currentHolon->get('IDtypeholon'), array(1, 2, 3, 4), true);
$canMoveHolon = !$isCurrentTemplateHolon && $currentHolon->canEdit() && in_array((int)$currentHolon->get('IDtypeholon'), array(1, 2, 3), true);
$canDeleteHolon = $currentHolon->canDelete() && in_array((int)$currentHolon->get('IDtypeholon'), array(1, 2, 3), true);
$canViewHolonHistory = $currentHolon->canViewDetail();
$deleteDescendantCount = $canDeleteHolon ? (int)$currentHolon->countVisibleDescendants() : 0;
$parentHolonForDelete = $canDeleteHolon ? $currentHolon->getParentHolon() : null;
$deleteParentId = $parentHolonForDelete ? (int)$parentHolonForDelete->getId() : 0;
$deleteParentIsRoot = $parentHolonForDelete ? ((int)$parentHolonForDelete->get('IDtypeholon') === 4) : false;
$hasHolonActions = $canCreateChildHolon || $canEditHolon || $canMoveHolon || $canDeleteHolon || $canViewHolonHistory;
?>

<style>
.<?= omoApiEscape($selectedNodeClass) ?> > ul {
    border-left: 1px solid var(--color-primary) !important;
    border-width: 0 0 0 2px !important;
}

.<?= omoApiEscape($selectedNodeClass) ?> > .role-item {
    box-shadow: inset 0 0 0 2px var(--color-primary);
}
</style>

<div class="circle-panel">
    <div class="circle-top">
    <div class="breadcrumb">
        <?php foreach ($breadcrumb as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="separator">&rsaquo;</span>
            <?php endif; ?>

            <?php $isActive = ((int)$crumb->getId() === (int)$currentHolon->getId()); ?>
            <span class="crumb<?= $isActive ? ' active' : '' ?>"
                  data-cid="<?= (int)$crumb->getId() ?>"
                  data-is-root="<?= $index === 0 ? '1' : '0' ?>">
                <?= omoApiEscape($crumb->get('name')) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="circle-header">
        <div>
            <div class="circle-kicker generic-card-title generic-card-title--eyebrow"><?= omoApiEscape($holonTypeLabel) ?></div>
            <h2 class="circle-title generic-card-title generic-card-title--section"><?= omoApiEscape($currentHolon->get('name')) ?></h2>
        </div>
        <div class="circle-meta">
            <?php if ($hasHolonActions): ?>
                <div class="circle-menu" data-holon-menu="1">
                    <button
                        type="button"
                        class="circle-badge circle-badge--menu noMobile"
                        data-holon-menu-toggle="1"
                        aria-haspopup="menu"
                        aria-expanded="false"
                    >...</button>
                    <div class="circle-menu__panel" data-holon-menu-panel="1" hidden>
                        <?php if ($canCreateChildHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-create-holon="1"
                                data-cid="<?= (int)$currentHolon->getId() ?>"
                            >Add</button>
                        <?php endif; ?>
                        <?php if ($canEditHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-edit-holon="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                                data-template-edit="<?= $isCurrentTemplateHolon ? '1' : '0' ?>"
                                data-definition-edit="<?= $isOrganizationDefinitionHolon ? '1' : '0' ?>"
                                data-template-context-id="<?= (int)$editTemplateContextId ?>"
                            >Edit</button>
                        <?php endif; ?>
                        <?php if ($canMoveHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-move-holon="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                            >Move</button>
                        <?php endif; ?>
                        <?php if ($canViewHolonHistory): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-holon-history="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                            >Historique</button>
                        <?php endif; ?>
                        <?php if ($canDeleteHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item circle-menu__item--danger"
                                data-delete-holon="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                                data-name="<?= omoApiEscape($currentHolon->get('name')) ?>"
                                data-type-label="<?= omoApiEscape($holonTypeLabel) ?>"
                                data-descendant-count="<?= (int)$deleteDescendantCount ?>"
                                data-parent-id="<?= (int)$deleteParentId ?>"
                                data-parent-is-root="<?= $deleteParentIsRoot ? '1' : '0' ?>"
                            >Del</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <button type="button" class="circle-badge circle-badge--link" data-copy-direct-link="1" data-cid="<?= (int)$currentHolon->getId() ?>">#<?= (int)$currentHolon->getId() ?></button>
        </div>
    </div>
    <?php if (count($visibleMemberCards) > 0 || $canManageMembers): ?>
        <div class="circle-members">
            <div class="circle-members__label generic-card-title generic-card-title--eyebrow">Membres</div>
            <div class="circle-members__row">
                <div class="circle-members__list">
                    <?php foreach ($visibleMemberCards as $member): ?>
                        <?php $memberTooltip = $member['displayName'] . (!empty($member['isPending']) ? ' - invitation en attente' : ''); ?>
                        <span
                            class="circle-member<?= !empty($member['isPending']) ? ' circle-member--pending' : '' ?>"
                            data-tooltip="<?= omoApiEscape($memberTooltip) ?>"
                            data-member-user-id="<?= (int)($member['userId'] ?? 0) ?>"
                            <?php if ((int)($member['userId'] ?? 0) > 0 && !empty($member['canViewDetail'])): ?>
                                data-open-user-context="1"
                                role="button"
                                tabindex="0"
                            <?php endif; ?>
                            aria-label="<?= omoApiEscape($memberTooltip) ?>"
                        >
                            <?php if (trim((string)$member['photoUrl']) !== ''): ?>
                                <img
                                    src="<?= omoApiEscape($member['photoUrl']) ?>"
                                    alt="<?= omoApiEscape($member['displayName']) ?>"
                                    class="circle-member__photo"
                                >
                            <?php else: ?>
                                <span class="circle-member__initials"><?= omoApiEscape($member['initials']) ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if ($canManageMembers): ?>
                        <button
                            type="button"
                            class="circle-member circle-member--add"
                            data-open-member-popup="1"
                            data-hid="<?= (int)$currentHolon->getId() ?>"
                            aria-label="Ajouter un membre"
                            title="Ajouter un membre"
                        >+</button>
                    <?php endif; ?>
                </div>
                <?php if ($hasHiddenMembers): ?>
                    <button type="button" class="circle-badge circle-badge--action" data-open-team-drawer="1" data-cid="<?= (int)$currentHolon->getId() ?>">Voir tout</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <?php if (count($sections) === 0): ?>
        <div class="circle-section generic-accordion generic-accordion--card">
            <div class="circle-section__title generic-card-title generic-card-title--small">Informations</div>
            <p class="section-text">Aucun contenu n'est encore renseign&eacute; pour ce holon.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($sections as $section): ?>
        <div class="circle-section generic-accordion generic-accordion--card generic-accordion--collapsible">
            <div class="generic-accordion__header">
                <span class="generic-accordion__title generic-card-title generic-card-title--small"><?= omoApiEscape($section['title']) ?></span>
                <span class="generic-accordion__toggle">&#9662;</span>
            </div>
            <div class="generic-accordion__content">
                <?= omoRenderSectionBody($section['entry']) ?>
                <?= omoRenderSectionUpdateMeta($section['entry'], $organizationId) ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (count($childNavigation['containers']) > 0 || count($childNavigation['roles']) > 0): ?>
        <div class="circle-section circle-section--navigation generic-accordion generic-accordion--card generic-accordion--collapsible">
            <div class="generic-accordion__header">
                <span class="generic-accordion__title generic-card-title generic-card-title--small">Dependances</span>
                <span class="generic-accordion__toggle">&#9662;</span>
            </div>
            <div class="generic-accordion__content">
                <?php if (count($childNavigation['containers']) > 0): ?>
                    <div class="child-nav-group">
                        <div class="child-nav-subtitle generic-card-title generic-card-title--small">Cercles</div>
                        <div class="child-nav-list">
                            <?php foreach ($childNavigation['containers'] as $child): ?>
                                <button type="button" class="child-nav-item" data-cid="<?= (int)$child['id'] ?>">
                                    <span class="child-nav-dot child-nav-dot--container"></span>
                                    <span class="child-nav-label"><?= omoApiEscape($child['name']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (count($childNavigation['roles']) > 0): ?>
                    <div class="child-nav-group">
                        <div class="child-nav-subtitle generic-card-title generic-card-title--small">Roles</div>
                        <div class="child-nav-list">
                            <?php foreach ($childNavigation['roles'] as $child): ?>
                                <button type="button" class="child-nav-item child-nav-item--role" data-cid="<?= (int)$child['id'] ?>">
                                    <span class="child-nav-dot child-nav-dot--role"></span>
                                    <span class="child-nav-label"><?= omoApiEscape($child['name']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.breadcrumb {
    font-size: 12px;
    color: var(--color-text-light);
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.crumb {
    cursor: pointer;
}

.crumb:hover {
    text-decoration: underline;
}

.crumb.active {
    color: var(--color-text);
    font-weight: 600;
}

.separator {
    opacity: 0.5;
}

.circle-panel {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.circle-top {
    position: sticky;
    top: 0;
    z-index: 5;
    margin: -20px -20px 0;
    padding: 20px 20px 16px;
    background: var(--color-surface, #fff);
    border-bottom: 1px solid var(--color-border);
}

.circle-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
}

.circle-kicker {
    margin-bottom: 6px;
}

.circle-title {
    margin: 0;
}

.circle-members {
    display: grid;
    gap: 8px;
    margin-top: 14px;
}

.circle-members__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.circle-members__list {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.circle-member {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    overflow: hidden;
    border: 1px solid var(--color-border);
    background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface-alt, #f0f2f5));
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(15, 23, 42, 0.08));
}

.circle-member[data-open-user-context="1"] {
    cursor: pointer;
}

.circle-member--pending {
    opacity: 0.55;
    border-style: dashed;
}

.circle-member--add {
    cursor: pointer;
    font-size: 18px;
    font-weight: 500;
    color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 8%, var(--color-surface));
}

.circle-member--add:hover {
    border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
    background: color-mix(in srgb, var(--color-primary) 14%, var(--color-surface));
}

.circle-member__photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.circle-member--pending .circle-member__photo {
    filter: grayscale(1);
}

.circle-member__initials {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 12px;
    font-weight: 700;
    color: var(--color-text);
}

.circle-member--pending .circle-member__initials {
    color: var(--color-text-light);
}

.circle-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.circle-badge {
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    background: var(--color-surface-alt, #f0f2f5);
    color: var(--color-text-light);
    border: 1px solid var(--color-border);
}

.circle-badge--link {
    cursor: pointer;
}

.circle-badge--action {
    cursor: pointer;
    color: var(--color-primary);
    border-color: color-mix(in srgb, var(--color-primary) 24%, var(--color-border));
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.circle-badge--action:hover {
    border-color: var(--color-primary);
}

.circle-badge--menu {
    cursor: pointer;
    min-width: 34px;
    padding: 6px 8px;
    color: var(--color-text);
    font-weight: 700;
    letter-spacing: 0.08em;
}

.circle-badge--danger {
    cursor: pointer;
    color: #b91c1c;
    border-color: rgba(220, 38, 38, 0.26);
    background: rgba(220, 38, 38, 0.08);
}

.circle-badge--danger:hover {
    border-color: rgba(220, 38, 38, 0.4);
    background: rgba(220, 38, 38, 0.14);
}

.circle-badge--link.copied {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.circle-menu {
    position: relative;
}

.circle-menu__panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 140px;
    padding: 6px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface, #fff);
    box-shadow: var(--shadow-md, 0 12px 24px rgba(15, 23, 42, 0.14));
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 15;
}

.circle-menu__panel[hidden] {
    display: none;
}

.circle-menu__item {
    width: 100%;
    padding: 9px 11px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
    font-size: 13px;
}

.circle-menu__item:hover {
    background: var(--color-surface-alt, #f0f2f5);
}

.circle-menu__item--danger {
    color: #b91c1c;
}

.circle-menu__item--danger:hover {
    background: rgba(220, 38, 38, 0.08);
}

.circle-section__title {
    margin-bottom: 8px;
}

.section-text,
.section-inherited__text {
    font-size: 14px;
    line-height: 1.5;
    white-space: pre-line;
}

.section-html {
    font-size: 14px;
    line-height: 1.5;
    word-break: break-word;
}

.section-html > :first-child {
    margin-top: 0;
}

.section-html > :last-child {
    margin-bottom: 0;
}

.section-html p {
    margin: 0 0 0.85em;
}

.section-update-meta {
    margin-top: 10px;
    font-size: 11px;
    line-height: 1.35;
    color: var(--color-text-light);
}

.section-html ul,
.section-html ol {
    margin: 0.25em 0;
    padding-left: 18px;
}

.section-html a {
    color: var(--color-primary);
    text-decoration: underline;
}

.section-text--inherited {
    font-style: italic;
    color: var(--color-text-light);
}

.section-list {
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0;
}

.section-list li {
    font-size: 14px;
    line-height: 1.4;
}

.section-list li.is-inherited {
    font-style: italic;
    color: var(--color-text-light);
}

.section-detail-list {
    display: grid;
    gap: 8px;
}

.section-detail-card {
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-surface-alt) 65%, var(--color-surface));
    overflow: hidden;
}

.section-detail-card.is-inherited {
    color: var(--color-text-light);
}

.section-detail-card summary {
    cursor: pointer;
    padding: 10px 12px;
    font-weight: 600;
}

.section-detail-card__body {
    padding: 0 12px 12px;
    font-size: 14px;
    line-height: 1.5;
    white-space: pre-line;
}

.section-inherited {
    padding: 10px 12px;
    margin-bottom: 12px;
    border-radius: var(--radius-sm, 8px);
    background: var(--color-surface-alt, #f0f2f5);
    border: 1px dashed var(--color-border);
}

.section-inherited__label {
    margin-bottom: 6px;
}

.child-nav-group + .child-nav-group {
    margin-top: 16px;
}

.child-nav-subtitle {
    margin-bottom: 8px;
}

.child-nav-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.child-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    background: var(--color-surface-alt, #f0f2f5);
    color: var(--color-text);
    cursor: pointer;
    text-align: left;
}

.child-nav-item:hover {
    border-color: var(--color-primary);
}

.child-nav-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    flex: 0 0 10px;
}

.child-nav-dot--container {
    background: var(--color-primary);
}

.child-nav-dot--role {
    background: var(--chart-role-fill, #fbbf24);
}

.child-nav-label {
    line-height: 1.35;
}
</style>

<script>
function omoCloseHolonMenus() {
    $('#panel-left [data-holon-menu="1"]').each(function () {
        $(this).removeClass('is-open');
        $(this).find('[data-holon-menu-panel="1"]').prop('hidden', true);
        $(this).find('[data-holon-menu-toggle="1"]').attr('aria-expanded', 'false');
    });
}

window.dispatchEvent(new CustomEvent('omo-structure-member-highlight', {
    detail: {
        userId: null
    }
}));

$(document)
  .off('click.omoOrgSection', '#panel-left .generic-accordion__header')
  .on('click.omoOrgSection', '#panel-left .generic-accordion__header', function () {
    const section = $(this).closest('.generic-accordion--collapsible');
    const key = omoNormalizeSectionKey(section.find('.generic-accordion__title').first().text());

    section.toggleClass('is-collapsed');
    localStorage.setItem('section_' + key, section.hasClass('is-collapsed'));
  });

$(document)
  .off('click.omoOrgCrumb', '#panel-left .crumb[data-cid]')
  .on('click.omoOrgCrumb', '#panel-left .crumb[data-cid]', function () {
    const cid = Number($(this).data('cid'));
    const isRoot = String($(this).data('is-root')) === '1';

    if (!cid || typeof navigate !== 'function' || typeof parseUrl !== 'function') {
        return;
    }

    const route = parseUrl();
    navigate(route.oid, isRoot ? null : cid, route.hash || null);
  });

$(document)
  .off('click.omoOrgChildNav', '#panel-left .child-nav-item[data-cid]')
  .on('click.omoOrgChildNav', '#panel-left .child-nav-item[data-cid]', function () {
    const cid = Number($(this).data('cid'));

    if (!cid || typeof navigate !== 'function' || typeof parseUrl !== 'function') {
        return;
    }

    const route = parseUrl();
    navigate(route.oid, cid, route.hash || null);
  });

$(document)
  .off('mouseenter.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]')
  .on('mouseenter.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]', function () {
    const userId = Number($(this).data('member-user-id'));

    window.dispatchEvent(new CustomEvent('omo-structure-member-highlight', {
        detail: {
            userId: userId > 0 ? userId : null
        }
    }));
  });

$(document)
  .off('mouseleave.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]')
  .on('mouseleave.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]', function () {
    window.dispatchEvent(new CustomEvent('omo-structure-member-highlight', {
        detail: {
            userId: null
        }
    }));
  });

$(document)
  .off('click.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]')
  .on('click.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]', function () {
    const userId = Number($(this).data('member-user-id'));

    if (typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
  });

$(document)
  .off('keydown.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]')
  .on('keydown.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    $(this).trigger('click');
  });

$(document)
  .off('click.omoOrgHolonMenu', '#panel-left [data-holon-menu-toggle="1"]')
  .on('click.omoOrgHolonMenu', '#panel-left [data-holon-menu-toggle="1"]', function (event) {
    event.stopPropagation();

    const menu = $(this).closest('[data-holon-menu="1"]');
    const willOpen = !menu.hasClass('is-open');
    omoCloseHolonMenus();

    if (!willOpen) {
        return;
    }

    menu.addClass('is-open');
    menu.find('[data-holon-menu-panel="1"]').prop('hidden', false);
    menu.find('[data-holon-menu-toggle="1"]').attr('aria-expanded', 'true');
  });

$(document)
  .off('click.omoOrgHolonMenuItem', '#panel-left [data-holon-menu-panel="1"] button')
  .on('click.omoOrgHolonMenuItem', '#panel-left [data-holon-menu-panel="1"] button', function () {
    omoCloseHolonMenus();
  });

$(document)
  .off('click.omoOrgHolonMenuOutside')
  .on('click.omoOrgHolonMenuOutside', function (event) {
    if ($(event.target).closest('#panel-left [data-holon-menu="1"]').length) {
        return;
    }

    omoCloseHolonMenus();
  });

$(document)
  .off('click.omoOrgCreateHolon', '#panel-left [data-open-create-holon="1"]')
  .on('click.omoOrgCreateHolon', '#panel-left [data-open-create-holon="1"]', function () {
    const cid = Number($(this).data('cid'));

    if (!cid || typeof window.omoOpenDrawerHashState !== 'function') {
        return;
    }

    window.omoOpenDrawerHashState('holon-create-' + cid);
  });

$(document)
  .off('click.omoOrgEditHolon', '#panel-left [data-open-edit-holon="1"]')
  .on('click.omoOrgEditHolon', '#panel-left [data-open-edit-holon="1"]', function () {
    const button = $(this);
    const hid = Number(button.data('hid'));
    const isTemplateEdit = String(button.data('template-edit')) === '1';
    const isDefinitionEdit = String(button.data('definition-edit')) === '1';
    const templateContextId = Number(button.data('template-context-id') || 0);

    if (!hid || typeof window.omoOpenDrawerHashState !== 'function') {
        return;
    }

    if ((isTemplateEdit || isDefinitionEdit) && templateContextId > 0) {
        window.omoOpenDrawerHashState('holon-template-edit-' + templateContextId + '-' + hid);
        return;
    }

    window.omoOpenDrawerHashState('holon-edit-' + hid);
  });

$(document)
  .off('click.omoOrgMoveHolon', '#panel-left [data-open-move-holon="1"]')
  .on('click.omoOrgMoveHolon', '#panel-left [data-open-move-holon="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.omoOpenPopupHashState !== 'function') {
        return;
    }

    window.omoOpenPopupHashState('holon-move', hid);
  });

$(document)
  .off('click.omoOrgOpenTeamDrawer', '#panel-left [data-open-team-drawer="1"]')
  .on('click.omoOrgOpenTeamDrawer', '#panel-left [data-open-team-drawer="1"]', function () {
    if (typeof openDrawer !== 'function') {
        return;
    }

    const route = typeof parseUrl === 'function' ? parseUrl() : { oid: null, cid: null };
    const targetCid = Number($(this).data('cid') || route.cid || 0);
    let drawerUrl = '/omo/api/team/index.php';

    if (route && route.oid) {
        drawerUrl += '?oid=' + encodeURIComponent(route.oid);
        if (targetCid > 0) {
            drawerUrl += '&cid=' + encodeURIComponent(targetCid);
        }
    } else if (targetCid > 0) {
        drawerUrl += '?cid=' + encodeURIComponent(targetCid);
    }

    openDrawer('drawer_team', drawerUrl);
  });

$(document)
  .off('click.omoOrgOpenMemberPopup', '#panel-left [data-open-member-popup="1"]')
  .on('click.omoOrgOpenMemberPopup', '#panel-left [data-open-member-popup="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        'Ajouter un membre',
        'api/holons/member_popup.php?hid=' + hid,
        'fetch'
    );
  });

$(document)
  .off('click.omoOrgOpenHolonHistory', '#panel-left [data-open-holon-history="1"]')
  .on('click.omoOrgOpenHolonHistory', '#panel-left [data-open-holon-history="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        'Historique',
        'api/holons/history_popup.php?hid=' + hid,
        'fetch'
    );
  });

$(document)
  .off('click.omoOrgDeleteHolon', '#panel-left [data-delete-holon="1"]')
  .on('click.omoOrgDeleteHolon', '#panel-left [data-delete-holon="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.omoOpenPopupHashState !== 'function') {
        return;
    }

    window.omoOpenPopupHashState('holon-delete', hid);
  });

function omoNormalizeSectionKey(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, '_');
}

function omoBuildDirectHolonUrl(cid) {
    const rootId = <?= (int)$navigationRoot->getId() ?>;
    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: <?= (int)$organizationId ?> };
    const targetCid = Number(cid) === Number(rootId) ? null : cid;

    if (typeof buildOmoUrl === 'function') {
        return buildOmoUrl(route.oid, targetCid, null, { absolute: true });
    }

    if (targetCid) {
        return `${window.location.origin}/omo/c/${targetCid}`;
    }

    return `${window.location.origin}/omo/`;
}

(function restoreSections() {
    $('#panel-left .generic-accordion--collapsible').each(function () {
        const key = omoNormalizeSectionKey($(this).find('.generic-accordion__title').first().text());
        const saved = localStorage.getItem('section_' + key);

        if (saved === 'true') {
            $(this).addClass('is-collapsed');
            return;
        }

        if (saved === null && key === 'dependances') {
            $(this).addClass('is-collapsed');
        }
    });
})();

$(document)
  .off('click.omoOrgCopyDirectLink', '#panel-left [data-copy-direct-link="1"]')
  .on('click.omoOrgCopyDirectLink', '#panel-left [data-copy-direct-link="1"]', async function () {
    const button = this;
    const cid = Number($(button).data('cid'));
    const url = omoBuildDirectHolonUrl(cid);

    if (!url) {
        return;
    }

    try {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(url);
        } else {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }

        const originalText = button.textContent;
        $(button).addClass('copied');
        button.textContent = 'Lien copie';

        window.setTimeout(function () {
            button.textContent = originalText;
            $(button).removeClass('copied');
        }, 1200);
    } catch (error) {
        console.error('Impossible de copier le lien direct.', error);
    }
  });
</script>
