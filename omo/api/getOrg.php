<?php
require_once __DIR__ . '/bootstrap.php';
use dbObject\ArrayOrganization;
use dbObject\Holon;

function omoEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function omoNormalizeLabel($value)
{
    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', (string)$value);
    return trim((string)$value);
}

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

function omoRenderTextBlock($text, $className = 'section-text')
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }

    return '<div class="' . omoEscape($className) . '">' . nl2br(omoEscape($text)) . '</div>';
}

function omoRenderSectionBody(array $entry)
{
    $value = trim((string)($entry['value'] ?? ''));
    $ancestor = trim((string)($entry['ancestor'] ?? ''));
    $effective = trim((string)($entry['effectiveValue'] ?? ''));

    if ($effective === '') {
        return '';
    }

    $currentItems = omoSplitTextItems($value);
    $ancestorItems = omoSplitTextItems($ancestor);
    $effectiveItems = $value !== '' ? $currentItems : $ancestorItems;

    $html = '';

    if ($ancestor !== '' && $value !== '') {
        $html .= '<div class="section-inherited">';
        $html .= '<div class="section-inherited__label">Hérité</div>';
        $html .= omoRenderTextBlock($ancestor, 'section-inherited__text');
        $html .= '</div>';
    }

    if (count($effectiveItems) > 1) {
        $html .= '<ul class="section-list">';
        foreach ($effectiveItems as $item) {
            $html .= '<li>' . omoEscape($item) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    return $html . omoRenderTextBlock($effective);
}

function omoBuildSections(Holon $holon)
{
    $entries = $holon->getPropertyEntries();
    $usedIndexes = array();
    $sections = array();

    $definitions = array(
        array(
            'title' => "Raison d'être",
            'propertyIds' => array(5),
            'keywords' => array('raison', 'etre'),
        ),
        array(
            'title' => 'Attendus',
            'propertyIds' => array(6),
            'keywords' => array('attendu'),
        ),
        array(
            'title' => "Domaines d'autorité",
            'propertyIds' => array(7),
            'keywords' => array('autorite', 'domaine'),
        ),
        array(
            'title' => 'Stratégie',
            'propertyIds' => array(8),
            'keywords' => array('strategie'),
        ),
    );

    foreach ($definitions as $definition) {
        foreach ($entries as $index => $entry) {
            if (isset($usedIndexes[$index])) {
                continue;
            }

            $effective = trim((string)($entry['effectiveValue'] ?? ''));
            if ($effective === '') {
                continue;
            }

            $match = in_array((int)$entry['id'], $definition['propertyIds'], true);
            if (!$match) {
                $searchable = omoNormalizeLabel(($entry['shortname'] ?? '') . ' ' . ($entry['name'] ?? ''));
                $keywordMatches = 0;
                foreach ($definition['keywords'] as $keyword) {
                    if (strpos($searchable, $keyword) !== false) {
                        $keywordMatches += 1;
                    }
                }
                $match = $keywordMatches > 0;
            }

            if (!$match) {
                continue;
            }

            $sections[] = array(
                'title' => $definition['title'],
                'entry' => $entry,
            );
            $usedIndexes[$index] = true;
            break;
        }
    }

    foreach ($entries as $index => $entry) {
        if (isset($usedIndexes[$index])) {
            continue;
        }

        $effective = trim((string)($entry['effectiveValue'] ?? ''));
        if ($effective === '') {
            continue;
        }

        $title = trim((string)($entry['name'] ?: $entry['shortname'] ?: ('Propriété ' . $entry['id'])));
        $sections[] = array(
            'title' => $title,
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

function omoGetHolonTypeLabel(Holon $holon)
{
    switch ((int)$holon->get('IDtypeholon')) {
        case 4:
            return 'Organisation';
        case 2:
            return 'Cercle';
        case 1:
            return 'Role';
        case 3:
            return 'Groupe';
        default:
            return 'Holon';
    }
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

$root = $organization->getStructuralRootHolon();
if ($root === null) {
    http_response_code(404);
    ?>
    <div class="circle-panel"><div class="error">Aucune structure racine n'a été trouvée pour cette organisation.</div></div>
    <?php
    exit;
}

$currentHolon = $root;

if ($cid > 0) {
    $candidate = new Holon();
    if (!$candidate->load($cid) || !$candidate->isDescendantOf($root->getId())) {
        http_response_code(404);
        ?>
        <div class="circle-panel"><div class="error">Holon introuvable pour cette organisation.</div></div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$breadcrumb = $currentHolon->getPathHolons();
$sections = omoBuildSections($currentHolon);
$childNavigation = omoBuildChildNavigation($currentHolon);
$holonTypeLabel = omoGetHolonTypeLabel($currentHolon);
$selectedNodeClass = 'node_' . (int)$currentHolon->getId();
?>

<style>
.<?= omoEscape($selectedNodeClass) ?> > ul {
    border-left: 1px solid var(--color-primary) !important;
    border-width: 0 0 0 2px !important;
}

.<?= omoEscape($selectedNodeClass) ?> > .role-item {
    box-shadow: inset 0 0 0 2px var(--color-primary);
}
</style>

<div class="circle-panel">
    <div class="circle-top">
    <div class="breadcrumb">
        <?php foreach ($breadcrumb as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="separator">›</span>
            <?php endif; ?>

            <?php $isActive = ((int)$crumb->getId() === (int)$currentHolon->getId()); ?>
            <span class="crumb<?= $isActive ? ' active' : '' ?>"
                  data-cid="<?= (int)$crumb->getId() ?>"
                  data-is-root="<?= $index === 0 ? '1' : '0' ?>">
                <?= omoEscape($crumb->get('name')) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="circle-header">
        <div>
            <div class="circle-kicker"><?= omoEscape($holonTypeLabel) ?></div>
            <h2 class="circle-title"><?= omoEscape($currentHolon->get('name')) ?></h2>
        </div>
        <div class="circle-meta">
            <button type="button" class="circle-badge circle-badge--link" data-copy-direct-link="1" data-cid="<?= (int)$currentHolon->getId() ?>">#<?= (int)$currentHolon->getId() ?></button>
        </div>
    </div>
    </div>

    <?php if (count($sections) === 0): ?>
        <div class="circle-section">
            <div class="section-title">Informations</div>
            <p class="section-text">Aucun contenu n'est encore renseigné pour ce holon.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($sections as $section): ?>
        <div class="circle-section">
            <div class="section-header">
                <span class="section-title"><?= omoEscape($section['title']) ?></span>
                <span class="section-toggle">▾</span>
            </div>
            <div class="section-content">
                <?= omoRenderSectionBody($section['entry']) ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (count($childNavigation['containers']) > 0 || count($childNavigation['roles']) > 0): ?>
        <div class="circle-section">
            <div class="section-header">
                <span class="section-title">Dependances</span>
                <span class="section-toggle">&#9662;</span>
            </div>
            <div class="section-content">
                <?php if (count($childNavigation['containers']) > 0): ?>
                    <div class="child-nav-group">
                        <div class="child-nav-subtitle">Cercles</div>
                        <div class="child-nav-list">
                            <?php foreach ($childNavigation['containers'] as $child): ?>
                                <button type="button" class="child-nav-item" data-cid="<?= (int)$child['id'] ?>">
                                    <span class="child-nav-dot child-nav-dot--container"></span>
                                    <span class="child-nav-label"><?= omoEscape($child['name']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (count($childNavigation['roles']) > 0): ?>
                    <div class="child-nav-group">
                        <div class="child-nav-subtitle">Roles</div>
                        <div class="child-nav-list">
                            <?php foreach ($childNavigation['roles'] as $child): ?>
                                <button type="button" class="child-nav-item child-nav-item--role" data-cid="<?= (int)$child['id'] ?>">
                                    <span class="child-nav-dot child-nav-dot--role"></span>
                                    <span class="child-nav-label"><?= omoEscape($child['name']) ?></span>
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
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-light);
    margin-bottom: 6px;
}

.circle-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
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

.circle-badge--link.copied {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.circle-section {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 14px;
    box-shadow: var(--shadow-sm);
}

.section-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-text-light);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.section-text,
.section-inherited__text {
    font-size: 14px;
    line-height: 1.5;
    white-space: pre-line;
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

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.section-toggle {
    font-size: 12px;
    transition: transform 0.2s ease;
}

.section-content {
    margin-top: 10px;
}

.section-inherited {
    padding: 10px 12px;
    margin-bottom: 12px;
    border-radius: var(--radius-sm, 8px);
    background: var(--color-surface-alt, #f0f2f5);
    border: 1px dashed var(--color-border);
}

.section-inherited__label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-light);
    margin-bottom: 6px;
}

.circle-section.collapsed .section-content {
    display: none;
}

.circle-section.collapsed .section-toggle {
    transform: rotate(-90deg);
}

.child-nav-group + .child-nav-group {
    margin-top: 16px;
}

.child-nav-subtitle {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-text-light);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
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
$(document)
  .off('click.omoOrgSection', '#panel-left .section-header')
  .on('click.omoOrgSection', '#panel-left .section-header', function () {
    const section = $(this).closest('.circle-section');
    const key = omoNormalizeSectionKey(section.find('.section-title').first().text());

    section.toggleClass('collapsed');
    localStorage.setItem('section_' + key, section.hasClass('collapsed'));
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

function omoNormalizeSectionKey(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, '_');
}

function omoBuildDirectHolonUrl(cid) {
    const rootId = <?= (int)$root->getId() ?>;
    return Number(cid) === Number(rootId)
        ? `${window.location.origin}/omo/`
        : `${window.location.origin}/omo/c/${cid}`;
}

(function restoreSections() {
    $('#panel-left .circle-section').each(function () {
        const key = omoNormalizeSectionKey($(this).find('.section-title').first().text());
        const saved = localStorage.getItem('section_' + key);

        if (saved === 'true') {
            $(this).addClass('collapsed');
            return;
        }

        if (saved === null && key === 'dependances') {
            $(this).addClass('collapsed');
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
