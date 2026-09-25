<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/avatar.php';
require_once __DIR__ . '/projects/shared.php';
commonReleaseReadOnlySession();
use dbObject\ArrayOrganization;
use dbObject\ArrayProject;
use dbObject\Authority;
use dbObject\Holon;
use dbObject\HolonPermission;
use dbObject\Organization;
use dbObject\Permission;
use dbObject\PropertyFormat;
use dbObject\Project;

function omoGetOrgPanelSourceLang(): array
{
    return [
        'leftbar.actions.add' => [
            'text' => 'Ajouter',
            'context' => 'Action menu label to create a child holon in the left panel.',
        ],
        'leftbar.actions.delete' => [
            'text' => 'Supprimer',
            'context' => 'Action menu label to delete the current holon in the left panel.',
        ],
        'leftbar.actions.edit' => [
            'text' => 'Modifier',
            'context' => 'Action menu label to edit the current holon in the left panel.',
        ],
        'leftbar.actions.history' => [
            'text' => 'Historique',
            'context' => 'Action menu label to open the current holon history in the left panel.',
        ],
        'leftbar.actions.move' => [
            'text' => 'Deplacer',
            'context' => 'Action menu label to move the current holon in the left panel.',
        ],
        'leftbar.actions.share_as_model' => [
            'text' => 'Partager comme modèle',
            'context' => 'Action menu label to publish the current organization as a public model.',
        ],
        'leftbar.actions.stop_sharing_as_model' => [
            'text' => 'Ne plus partager comme modèle',
            'context' => 'Action menu label to unpublish the current organization model.',
        ],
        'leftbar.children.section_title' => [
            'text' => 'Dependances',
            'context' => 'Accordion title for child navigation in the left panel.',
        ],
        'leftbar.copy_link.error' => [
            'text' => 'Impossible de copier le lien direct.',
            'context' => 'Console error message when the direct holon link cannot be copied from the left panel.',
        ],
        'leftbar.copy_link.success' => [
            'text' => 'Lien copie',
            'context' => 'Temporary button label shown after copying a direct holon link from the left panel.',
        ],
        'leftbar.detail.item_fallback' => [
            'text' => 'Element',
            'context' => 'Fallback title for a detail card item in the left panel when no title is available.',
        ],
        'leftbar.detail.show' => [
            'text' => 'Voir détail',
            'context' => 'Label shown beside the control that expands the HTML detail of a text property.',
        ],
        'leftbar.detail.property_fallback' => [
            'text' => 'Propriete {propertyId}',
            'context' => 'Fallback section title for a holon property in the left panel when no label is available.',
        ],
        'leftbar.detail.updated_at' => [
            'text' => 'Mis a jour le {date}',
            'context' => 'Update metadata shown below a left panel section when the updater is unknown.',
        ],
        'leftbar.detail.updated_by' => [
            'text' => 'Mis a jour le {date} par {userName}',
            'context' => 'Update metadata shown below a left panel section when the updater is known.',
        ],
        'leftbar.authority.delegated_count' => [
            'one' => '{count} autorite deleguee',
            'other' => '{count} autorites deleguees',
            'context' => 'Count shown beside an authority that has direct delegated child authorities.',
        ],
        'leftbar.authority.delegated' => [
            'text' => 'deleguee',
            'context' => 'Status shown for an inactive authority shell after complete delegation.',
        ],
        'leftbar.authority.description' => [
            'text' => 'Description',
            'context' => 'Section title for the description of an authority.',
        ],
        'leftbar.authority.delegated_to' => [
            'text' => 'Deleguee a',
            'context' => 'Section title for direct child authority delegations.',
        ],
        'leftbar.authority.internal_children' => [
            'text' => 'Sous-autorites',
            'context' => 'Section title for authority descendants held by the same holon.',
        ],
        'leftbar.authority.inherited_from' => [
            'text' => 'Heritee de',
            'context' => 'Section title for the parent authority of an authority.',
        ],
        'leftbar.authority.root' => [
            'text' => 'Autorite racine',
            'context' => 'Fallback shown when an authority has no parent authority.',
        ],
        'leftbar.empty.message' => [
            'text' => 'Aucun contenu n’est encore renseigné pour cet élément.',
            'context' => 'Message shown in the left panel when the current holon has no visible content.',
        ],
        'leftbar.empty.section_title' => [
            'text' => 'Informations',
            'context' => 'Section title shown in the left panel when the current holon has no visible content.',
        ],
        'leftbar.error.holon_access_denied' => [
            'text' => 'Accès refusé à cet élément.',
            'context' => 'Error message shown in the left panel when the current holon cannot be viewed.',
        ],
        'leftbar.error.holon_not_found' => [
            'text' => 'Élément introuvable pour cette organisation.',
            'context' => 'Error message shown in the left panel when the requested holon cannot be found.',
        ],
        'leftbar.error.organization_access_denied' => [
            'text' => 'Accès refusé à cette organisation.',
            'context' => 'Error message shown in the left panel when the current organization cannot be viewed.',
        ],
        'leftbar.error.organization_invalid' => [
            'text' => 'Organisation invalide.',
            'context' => 'Error message shown in the left panel when no valid organization identifier is available.',
        ],
        'leftbar.error.organization_not_found' => [
            'text' => 'Organisation introuvable.',
            'context' => 'Error message shown in the left panel when the requested organization cannot be found.',
        ],
        'leftbar.error.root_not_found' => [
            'text' => 'Aucune structure racine n’a été trouvée pour cette organisation.',
            'context' => 'Error message shown in the left panel when the organization has no structural root holon.',
        ],
        'leftbar.members.add' => [
            'text' => 'Ajouter un membre',
            'context' => 'Button label and modal title used to add a member from the left panel.',
        ],
        'leftbar.members.pending_tooltip' => [
            'text' => '{memberName} - invitation en attente',
            'context' => 'Tooltip shown for a pending invited member avatar in the left panel.',
        ],
		'leftbar.members.role_focus_line' => [
			'text' => 'Focus : {focus}',
			'context' => 'Additional tooltip line showing a role assignment focus for a member avatar.',
		],
		'leftbar.members.admin_tooltip' => [
			'text' => '{memberName} - {adminLabel}',
			'context' => 'Tooltip shown for an administrator member avatar in the left panel.',
		],
        'leftbar.members.section_title' => [
            'text' => 'Membres',
            'context' => 'Section title shown above the member avatars in the left panel.',
        ],
        'leftbar.members.view_all' => [
            'text' => 'Voir tout',
            'context' => 'Button label to open the complete team drawer from the left panel.',
        ],
        'leftbar.project.children.empty' => [
            'text' => 'Aucun sous-projet direct.',
            'context' => 'Message shown when expanding a project reference without direct subprojects.',
        ],
        'leftbar.project.children.error' => [
            'text' => 'Impossible de charger les sous-projets.',
            'context' => 'Message shown when loading direct subprojects of a project reference fails.',
        ],
        'leftbar.project.children.loading' => [
            'text' => 'Chargement des sous-projets...',
            'context' => 'Temporary message while direct subprojects of a project reference are loading.',
        ],
    ];
}

$sourceLang = omoGetOrgPanelSourceLang();
$lang = translationBundleInit('omo_get_org_panel', omoGetTranslationLocale(), $sourceLang);

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
                ? $holon->getFullDisplayName()
                : '';
        }

        return $holonLabelCache[$holonId];
    }

    if ($listItemType === 'project') {
        static $projectTitleCache = array();

        $projectId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
        if ($projectId <= 0) {
            return is_scalar($item) ? trim((string)$item) : '';
        }

        if (!isset($projectTitleCache[$projectId])) {
            $project = new Project();
            $projectTitleCache[$projectId] = ($project->load($projectId)
                && (int)$project->get('IDorganization') === (int)($_SESSION['currentOrganization'] ?? 0)
                && (
                    omoProjectsCanRevealProjectTitle($project)
                    || omoProjectsCanViewProject($project, omoProjectsResolveContext((int)$_SESSION['currentOrganization']))
                )
            )
                ? trim((string)$project->get('title'))
                : '';
        }

        return $projectTitleCache[$projectId];
    }

    if ($listItemType === 'authority') {
        static $authorityLabelCache = array();

        $authorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
        if ($authorityId <= 0) {
            return is_scalar($item) ? trim((string)$item) : '';
        }

        if (!isset($authorityLabelCache[$authorityId])) {
            $authority = new Authority();
            $authorityLabelCache[$authorityId] = '';
            if (
                $authority->load($authorityId)
                && (int)$authority->getOrganizationId() === (int)($_SESSION['currentOrganization'] ?? 0)
            ) {
                $authorityLabelCache[$authorityId] = trim((string)$authority->get('label'));
            }
        }

        return $authorityLabelCache[$authorityId];
    }

    if (is_array($item)) {
        return trim((string)($item['label'] ?? $item['value'] ?? ''));
    }

    return trim((string)$item);
}

function omoGetProjectReferenceData($projectId)
{
    static $projectsById = null;
    static $childrenByParent = null;
    static $statusSummaryMemo = array();
    static $projectContext = null;

    $projectId = (int)$projectId;
    if ($projectId <= 0) {
        return null;
    }

    if (omoProjectsIsStructuralShareRequest()) {
        $project = new Project();
        if (
            !$project->load($projectId)
            || (int)$project->get('IDorganization') !== (int)($_SESSION['currentOrganization'] ?? 0)
            || !omoProjectsCanRevealProjectTitle($project)
        ) {
            return null;
        }

        return array(
            'project' => $project,
            'titleOnly' => true,
        );
    }

    if ($projectsById === null) {
        $projectsById = array();
        $childrenByParent = array();
        $projectContext = omoProjectsResolveContext((int)($_SESSION['currentOrganization'] ?? 0));
        $projects = new ArrayProject();
        $projects->loadForOrganization((int)($_SESSION['currentOrganization'] ?? 0));

        foreach ($projects as $project) {
            $id = (int)$project->getId();
            if ($id <= 0 || empty($projectContext['status']) || !omoProjectsCanViewProject($project, $projectContext)) {
                continue;
            }

            $projectsById[$id] = $project;
            $parentId = (int)$project->get('IDproject_parent');
            if ($parentId > 0) {
                $childrenByParent[$parentId][] = $project;
            }
        }
    }

    if (!isset($projectsById[$projectId])) {
        return null;
    }

	$project = $projectsById[$projectId];
	return array(
		'project' => $project,
		'statusSummary' => omoProjectsBuildStatusBar($project, $childrenByParent, $statusSummaryMemo),
		'hasDirectChildren' => !empty($childrenByParent[$projectId]),
	);
}

function omoRenderProjectReferenceItem($item, $source = '')
{
    $projectId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
    $referenceData = omoGetProjectReferenceData($projectId);
    if ($referenceData === null) {
        return '';
    }

    $project = $referenceData['project'];
    $title = trim((string)$project->get('title'));
    if ($title === '') {
        return '';
    }

    if (!empty($referenceData['titleOnly'])) {
        $className = $source !== '' ? ' class="is-' . omoApiEscape($source) . '"' : '';
        return '<li' . $className . '>' . omoApiEscape($title) . '</li>';
    }

	$priority = Project::normalizeLevel($project->get('priority'));
	$status = Project::normalizeStatus($project->get('status'));
	$statusLabel = omoProjectsStatusLabel($status, (int)$project->get('IDorganization'));
	$hasDirectChildren = !empty($referenceData['hasDirectChildren']);
    $className = 'section-project-reference';
    if ($source !== '') {
        $className .= ' is-' . $source;
    }

	$html = '<li class="' . omoApiEscape($className) . '" data-omo-project-reference data-project-id="' . $projectId . '">';
	$html .= '<div class="section-project-reference__head">';
	$html .= '<span class="section-project-reference__status-dot section-project-reference__status-dot--' . omoApiEscape($status) . '"'
		. ' role="img" aria-label="' . omoApiEscape($statusLabel) . '" title="' . omoApiEscape($statusLabel) . '"></span>';
    $html .= '<a class="section-project-reference__title" data-omo-project-reference-title href="#projects-d' . $projectId . '">' . omoApiEscape($title) . '</a>';
    if ($priority !== null) {
        $html .= '<span class="generic-project-priority generic-project-priority--p' . (int)$priority . '">P' . (int)$priority . '</span>';
    }
	$html .= '</div>';
	if ($hasDirectChildren) {
		$html .= '<button type="button" class="section-project-reference__status-toggle" data-omo-project-reference-toggle aria-expanded="false" aria-label="Afficher les sous-projets de ' . omoApiEscape($title) . '">';
		$html .= omoProjectsRenderStatusBar($referenceData['statusSummary'], 'section-project-reference__status-bar', 'div', true);
		$html .= '</button>';
		$html .= '<div class="section-project-reference__children" data-omo-project-reference-children hidden></div>';
	}
    $html .= '</li>';

    return $html;
}

function omoGetAuthorityReferenceData($item)
{
    static $authorityCache = array();

    $authorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
    if ($authorityId <= 0) {
        return null;
    }

    if (array_key_exists($authorityId, $authorityCache)) {
        return $authorityCache[$authorityId];
    }

    $authorityCache[$authorityId] = null;
    $authority = new Authority();
    if (
        !$authority->load($authorityId)
        || (int)$authority->getOrganizationId() !== (int)($_SESSION['currentOrganization'] ?? 0)
    ) {
        return null;
    }

    $children = array();
    foreach ($authority->getChildren() as $child) {
        $label = trim((string)$child->get('label'));
        if ($label !== '') {
            $owner = $child->getHolon();
            $children[] = array(
                'id' => (int)$child->getId(),
                'label' => $label,
                'holonId' => $owner instanceof Holon ? (int)$owner->getId() : 0,
                'holonLabel' => $owner instanceof Holon ? $owner->getFullDisplayName() : '',
            );
        }
    }

    $parentData = null;
    $parent = $authority->getParent();
    if ($parent instanceof Authority) {
        $parentOwner = $parent->getHolon();
        $parentData = array(
            'label' => trim((string)$parent->get('label')),
            'holonLabel' => $parentOwner instanceof Holon ? $parentOwner->getFullDisplayName() : '',
        );
    }

    $authorityCache[$authorityId] = array(
        'id' => $authorityId,
        'ownerHolonId' => (int)$authority->get('IDholon'),
        'parentId' => (int)$authority->get('IDauthority_parent'),
        'label' => trim((string)$authority->get('label')),
        'isShell' => $authority->isShell(),
        'description' => trim((string)$authority->get('description')),
        'parent' => $parentData,
        'children' => $children,
    );

    return $authorityCache[$authorityId];
}

function omoRenderAuthorityInternalTree(array $referenceData, $ownerHolonId, array $visited = array())
{
    $authorityId = (int)($referenceData['id'] ?? 0);
    if ($authorityId <= 0 || isset($visited[$authorityId])) {
        return '';
    }
    $visited[$authorityId] = true;
    $items = '';
    foreach ($referenceData['children'] as $child) {
        if ((int)($child['holonId'] ?? 0) !== (int)$ownerHolonId) {
            continue;
        }
        $childData = omoGetAuthorityReferenceData((int)($child['id'] ?? 0));
        if (!is_array($childData)) {
            continue;
        }
        $label = omoApiEscape((string)$childData['label']);
        if (!empty($childData['isShell'])) {
            $label = '<em>' . $label . '</em>';
        }
        $nested = omoRenderAuthorityInternalTree($childData, $ownerHolonId, $visited);
        $items .= '<li>' . $label . $nested . '</li>';
    }

    return $items !== '' ? '<ul class="section-authority-reference__children">' . $items . '</ul>' : '';
}

function omoRenderAuthorityReferenceItem($item, $source = '')
{
    $referenceData = omoGetAuthorityReferenceData($item);
    if (!is_array($referenceData) || $referenceData['label'] === '') {
        return '';
    }

    $className = 'section-authority-reference';
    if ($source !== '') {
        $className .= ' is-' . $source;
    }

    $children = $referenceData['children'];
    $delegatedChildren = array_filter($children, static function ($child) use ($referenceData) {
        $childHolonId = (int)($child['holonId'] ?? 0);
        return $childHolonId > 0 && $childHolonId !== (int)($referenceData['ownerHolonId'] ?? 0);
    });
    $countLabel = count($delegatedChildren) > 0
        ? '-' . t('leftbar.authority.delegated_count', array('count' => count($delegatedChildren)))
        : '';
    $statusLabel = !empty($referenceData['isShell']) ? t('leftbar.authority.delegated') : $countLabel;
    $parent = is_array($referenceData['parent'] ?? null) ? $referenceData['parent'] : null;
    $parentLabel = $parent ? trim((string)($parent['label'] ?? '')) : '';
    $parentHolonLabel = $parent ? trim((string)($parent['holonLabel'] ?? '')) : '';
    $html = '<li class="' . omoApiEscape($className) . '">';
    $html .= '<details class="section-authority-reference__details">';
    $authorityLabel = omoApiEscape($referenceData['label']);
    if (!empty($referenceData['isShell'])) {
        $authorityLabel = '<em>' . $authorityLabel . '</em>';
    }
    $html .= '<summary><span class="section-authority-reference__label generic-title generic-title--compact">' . $authorityLabel . '</span>';
    if ($statusLabel !== '') {
        $html .= ' <span class="section-authority-reference__count">(' . omoApiEscape($statusLabel) . ')</span>';
    }
    $html .= '</summary>';
    $html .= '<div class="section-authority-reference__body">';
    $html .= '<section><h4>' . omoApiEscape(t('leftbar.authority.inherited_from')) . '</h4><div>'
        . ($parentLabel !== ''
            ? omoApiEscape($parentLabel) . ($parentHolonLabel !== '' ? ' <span class="section-authority-reference__holon">(' . omoApiEscape($parentHolonLabel) . ')</span>' : '')
            : omoApiEscape(t('leftbar.authority.root')))
        . '</div></section>';
    if (trim((string)$referenceData['description']) !== '') {
        $html .= '<section><h4>' . omoApiEscape(t('leftbar.authority.description')) . '</h4><div>'
            . nl2br(omoApiEscape($referenceData['description'])) . '</div></section>';
    }
    $internalChildren = omoRenderAuthorityInternalTree($referenceData, (int)$referenceData['ownerHolonId']);
    if ($internalChildren !== '') {
        $html .= '<section><h4>' . omoApiEscape(t('leftbar.authority.internal_children')) . '</h4>' . $internalChildren . '</section>';
    }
    if (count($delegatedChildren) > 0) {
        $html .= '<section><h4>' . omoApiEscape(t('leftbar.authority.delegated_to')) . '</h4><ul class="section-authority-reference__children">';
        foreach ($delegatedChildren as $child) {
            $childLabel = trim((string)($child['label'] ?? ''));
            $holonLabel = trim((string)($child['holonLabel'] ?? ''));
            if ($childLabel === '') {
                continue;
            }
            $html .= '<li>' . omoApiEscape($childLabel)
                . ($holonLabel !== '' ? ' <span class="section-authority-reference__holon">(' . omoApiEscape($holonLabel) . ')</span>' : '')
                . '</li>';
        }
        $html .= '</ul></section>';
    }
    $html .= '</div></details></li>';

    return $html;
}

function omoFilterTopLevelAuthorityItems(array $items)
{
    $itemsById = array();
    foreach ($items as $item) {
        $referenceData = omoGetAuthorityReferenceData($item);
        if (is_array($referenceData)) {
            $itemsById[(int)$referenceData['id']] = $referenceData;
        }
    }

    return array_values(array_filter($items, static function ($item) use ($itemsById) {
        $referenceData = omoGetAuthorityReferenceData($item);
        if (!is_array($referenceData)) {
            return true;
        }
        $parentId = (int)($referenceData['parentId'] ?? 0);
        return !isset($itemsById[$parentId])
            || (int)($itemsById[$parentId]['ownerHolonId'] ?? 0) !== (int)($referenceData['ownerHolonId'] ?? 0);
    }));
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

    return '<div class="' . omoApiEscape($className) . ' generic-description generic-description--small generic-description--primary">' . nl2br(omoApiEscape($text)) . '</div>';
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
    if ((string)($entry['listItemType'] ?? '') === 'authority') {
        $items = omoFilterTopLevelAuthorityItems($items);
    }
    $html = '<ul class="' . omoApiEscape($className) . '">';
    foreach ($items as $item) {
        if ((string)($entry['listItemType'] ?? '') === 'project') {
            $html .= omoRenderProjectReferenceItem($item);
            continue;
        }
        if ((string)($entry['listItemType'] ?? '') === 'authority') {
            $html .= omoRenderAuthorityReferenceItem($item);
            continue;
        }
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
    if ((string)($entry['listItemType'] ?? '') === 'authority') {
        $topLevelItems = omoFilterTopLevelAuthorityItems(array_column($descriptors, 'item'));
        $topLevelIds = array_fill_keys(array_map(static function ($item) {
            $referenceData = omoGetAuthorityReferenceData($item);
            return is_array($referenceData) ? (int)$referenceData['id'] : 0;
        }, $topLevelItems), true);
        $descriptors = array_values(array_filter($descriptors, static function ($descriptor) use ($topLevelIds) {
            $referenceData = omoGetAuthorityReferenceData($descriptor['item']);
            return !is_array($referenceData) || isset($topLevelIds[(int)$referenceData['id']]);
        }));
    }
    if (count($descriptors) === 0) {
        return '';
    }

    $html = '<ul class="' . omoApiEscape($className) . '">';
    foreach ($descriptors as $descriptor) {
        if ((string)($entry['listItemType'] ?? '') === 'project') {
            $html .= omoRenderProjectReferenceItem($descriptor['item'], $descriptor['source']);
            continue;
        }
        if ((string)($entry['listItemType'] ?? '') === 'authority') {
            $html .= omoRenderAuthorityReferenceItem($descriptor['item'], $descriptor['source']);
            continue;
        }
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

        $html .= '<details class="generic-accordion generic-accordion--inset section-detail-card is-' . omoApiEscape($descriptor['source']) . '">';
        $html .= '<summary>' . omoApiEscape($detailItem['title'] !== '' ? $detailItem['title'] : t('leftbar.detail.item_fallback')) . '</summary>';
        if ($detailItem['description'] !== '') {
            $html .= '<div class="generic-accordion generic-accordion--inset section-detail-card__body">' . nl2br(omoApiEscape($detailItem['description'])) . '</div>';
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

    if (PropertyFormat::isEmptyValue($formatId, $effective)) {
        return '';
    }

    if ($formatId === PropertyFormat::FORMAT_TEXT_HTML) {
        $parts = PropertyFormat::getTextHtmlParts($effective);
        $summary = $parts['text'] !== '' ? $parts['text'] : t('leftbar.detail.item_fallback');
        if (PropertyFormat::isEmptyValue(PropertyFormat::FORMAT_HTML, $parts['detail'])) {
            return '<div class="section-text-html__plain">' . omoApiEscape($summary) . '</div>';
        }

        $html = '<details class="section-text-html"><summary class="section-text-html__summary">' . omoApiEscape($summary) . '<span class="section-text-html__toggle"><span>' . omoApiEscape(t('leftbar.detail.show')) . '</span><span class="section-text-html__arrow" aria-hidden="true">&#9656;</span></span></summary>';
        $html .= omoRenderHtmlBlock($parts['detail'], 'section-text-html__body section-html');
        return $html . '</details>';
    }

    if ($formatId === PropertyFormat::FORMAT_HTML_LIST) {
        $parts = PropertyFormat::getHtmlListParts($effective);
        if (PropertyFormat::isEmptyValue(PropertyFormat::FORMAT_HTML_LIST, $parts)) {
            return '';
        }
        $html = '<div class="section-html-list">';
        $html .= omoRenderHtmlBlock($parts['before'], 'section-html section-html--before-list');
        $listEntry = $entry;
        $listEntry['value'] = json_encode($parts['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $listEntry['ancestor'] = '';
        $listEntry['effectiveValue'] = $listEntry['value'];
        $html .= omoRenderMixedList(array(), $parts['items'], $listEntry);
        $html .= omoRenderHtmlBlock($parts['after'], 'section-html section-html--after-list');
        return $html . '</div>';
    }

    if ($formatId === PropertyFormat::FORMAT_LIST) {
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

    if (in_array($formatId, array(PropertyFormat::FORMAT_TEXT_HTML, PropertyFormat::FORMAT_HTML_LIST), true)) {
        return !PropertyFormat::isEmptyValue($formatId, $value);
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

    $formattedDate = $updatedAt->format('d.m.Y H:i');
    $metaText = t('leftbar.detail.updated_at', ['date' => $formattedDate]);
    $updatedByName = omoResolveUserDisplayName((int)($entry['updatedByUserId'] ?? 0), (int)$organizationId);
    if ($updatedByName !== '') {
        $metaText = t('leftbar.detail.updated_by', [
            'date' => $formattedDate,
            'userName' => $updatedByName,
        ]);
    }

    return '<div class="section-update-meta">' . omoApiEscape($metaText) . '</div>';
}

function omoBuildSections(Holon $holon)
{
    $entries = $holon->getPropertyEntries();
    $sections = array();

    foreach ($entries as $entry) {
        $effective = $entry['effectiveValue'] ?? '';
        $formatId = (int)($entry['formatId'] ?? 0);
        if (PropertyFormat::isEmptyValue($formatId, $effective)) {
            continue;
        }

        $sections[] = array(
            'title' => trim((string)($entry['name'] ?: $entry['shortname'] ?: t('leftbar.detail.property_fallback', [
                'propertyId' => (int)$entry['id'],
            ]))),
            'entry' => $entry,
        );
    }

    return $sections;
}

function omoBuildChildNavigation(Holon $holon)
{
    $items = array(
        'circles' => array(),
        'groups' => array(),
        'roles' => array(),
    );

    $children = $holon->getChildren();
    if (!$children) {
        return $items;
    }

    foreach ($children as $child) {
        $entry = array(
            'id' => (int)$child->getId(),
            'name' => trim((string)$child->getFullDisplayName()),
            'type' => (int)$child->get('IDtypeholon'),
        );

        if ($entry['type'] === 1) {
            $items['roles'][] = $entry;
        } elseif ($entry['type'] === 3) {
            $items['groups'][] = $entry;
        } else {
            $items['circles'][] = $entry;
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
    <div class="circle-panel"><div class="error"><?= omoApiEscape(t('leftbar.error.organization_invalid')) ?></div></div>
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
    <div class="circle-panel"><div class="error"><?= omoApiEscape(t('leftbar.error.organization_not_found')) ?></div></div>
    <?php
    exit;
}

$canViewOrganization = $organization->canViewDetail();
if (!$canViewOrganization) {
    http_response_code(403);
    ?>
    <div class="circle-panel"><div class="error"><?= omoApiEscape(t('leftbar.error.organization_access_denied')) ?></div></div>
    <?php
    exit;
}

$organizationLexicon = $organization->getLexicon();
$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
$childCircleLabel = Organization::getLexiconLabel($organizationLexicon, 'circle', true);
$childGroupLabel = Organization::getLexiconLabel($organizationLexicon, 'group', true);
$childRoleLabel = Organization::getLexiconLabel($organizationLexicon, 'role', true);

$root = $organization->getEnabledStructuralRootHolon();
if ($root === null) {
    require_once __DIR__ . '/organization_setup_panel.php';
    omoRenderOrganizationInfoPanel($organization);
    exit;
}

$root = $organization->getEnabledStructuralRootHolon();
if ($root === null) {
    http_response_code(404);
    ?>
    <div class="circle-panel"><div class="error"><?= omoApiEscape(t('leftbar.error.root_not_found')) ?></div></div>
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
        <div class="circle-panel"><div class="error"><?= omoApiEscape(t('leftbar.error.holon_not_found')) ?></div></div>
        <?php
        exit;
    }

    if (!$candidate->canViewDetail()) {
        http_response_code(403);
        ?>
        <div class="circle-panel"><div class="error"><?= omoApiEscape(t('leftbar.error.holon_access_denied')) ?></div></div>
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
$holonIconUrl = trim((string)$currentHolon->getEffectiveIcon());
if ($holonIconUrl === 'newimage') {
    $holonIconUrl = '';
}
$selectedNodeClass = 'node_' . (int)$currentHolon->getId();
$memberCards = $currentHolon->getAssociatedMemberCards(array(
    'organizationId' => $organizationId,
));
$isRoleHolon = (int)$currentHolon->get('IDtypeholon') === 1;
if (function_exists('commonGetCurrentShareToken') && commonGetCurrentShareToken() !== '' && !commonCurrentShareAllowsPeople()) {
    $memberCards = array();
} else {
    $directContextAdminUserIds = array_fill_keys(
        $currentHolon->getDirectContextAdminUserIds($organizationId),
        true
    );

    foreach ($memberCards as &$memberCard) {
        $memberCard['isAdmin'] = isset($directContextAdminUserIds[(int)($memberCard['userId'] ?? 0)]);
    }
    unset($memberCard);

    usort($memberCards, static function (array $left, array $right) {
        if ((bool)($left['isAdmin'] ?? false) !== (bool)($right['isAdmin'] ?? false)) {
            return !empty($left['isAdmin']) ? -1 : 1;
        }

        return strcmp(
            omoApiSortKey((string)($left['displayName'] ?? '')),
            omoApiSortKey((string)($right['displayName'] ?? ''))
        );
    });
}
$regularMemberCount = count(array_filter($memberCards, static function (array $member): bool {
    return empty($member['isAdmin']);
}));
$isOrganizationDefinitionHolon = (int)$currentHolon->get('IDtypeholon') === 4;
$isCurrentTemplateHolon = !$isOrganizationDefinitionHolon && $root ? $currentHolon->isTemplateNode((int)$root->getId()) : false;
$editTemplateContextId = $isCurrentTemplateHolon && $currentHolon->getParentHolon()
    ? (int)$currentHolon->getParentHolon()->getId()
    : ($isOrganizationDefinitionHolon ? (int)$currentHolon->getId() : 0);
$canAddMembers = $currentHolon->isAllowed('CAN_ADD_MEMBER');
$canCreateChildHolon = $currentHolon->isAllowed('CAN_ADD_HOLON') && in_array((int)$currentHolon->get('IDtypeholon'), array(2, 3, 4), true);
$canEditHolon = $currentHolon->isAllowed('CAN_EDIT_HOLON') && in_array((int)$currentHolon->get('IDtypeholon'), array(1, 2, 3, 4), true);
$canMoveHolon = !$isCurrentTemplateHolon && $currentHolon->isAllowed('CAN_MOVE_HOLON') && in_array((int)$currentHolon->get('IDtypeholon'), array(1, 2, 3), true);
$canDeleteHolon = $currentHolon->isAllowed('CAN_DELETE_HOLON') && $currentHolon->canDelete() && in_array((int)$currentHolon->get('IDtypeholon'), array(1, 2, 3), true);
$canViewHolonHistory = $currentHolon->canViewDetail();
$activeOrganizationMembership = $organization->getMembership((int)commonGetCurrentUserId(), true);
$canManageOrganizationModel = $isOrganizationDefinitionHolon
    && $activeOrganizationMembership
    && $activeOrganizationMembership->isOrganizationAdmin()
    && commonCurrentUserIsAdminModeEnabled($organizationId);
$deleteDescendantCount = $canDeleteHolon ? (int)$currentHolon->countVisibleDescendants() : 0;
$parentHolonForDelete = $canDeleteHolon ? $currentHolon->getParentHolon() : null;
$deleteParentId = $parentHolonForDelete ? (int)$parentHolonForDelete->getId() : 0;
$deleteParentIsRoot = $parentHolonForDelete ? ((int)$parentHolonForDelete->get('IDtypeholon') === 4) : false;
$hasHolonActions = $canCreateChildHolon || $canEditHolon || $canMoveHolon || $canDeleteHolon || $canViewHolonHistory || $canManageOrganizationModel;
$debugPermissionCatalog = Permission::getEditorCatalog();
$debugPermissionEntries = array();
foreach ($debugPermissionCatalog as $permissionEntry) {
    $permissionKey = trim((string)($permissionEntry['key'] ?? ''));
    if ($permissionKey === '') {
        continue;
    }

    $debugPermissionEntries[] = array(
        'key' => $permissionKey,
        'isAllowed' => $currentHolon->isAllowed($permissionKey),
    );
}
$debugPermissionSessionCache = $_SESSION['permissionCacheByOrganization'][(int)$organizationId] ?? null;
$debugPermissionRebuild = HolonPermission::buildPermissionDebugForOrganization(
    (int)commonGetCurrentUserId(),
    (int)$organizationId
);
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
    

    <div class="circle-header">
        <div>
            <div class="breadcrumb">
                <?php foreach ($breadcrumb as $index => $crumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="separator">&rsaquo;</span>
                    <?php endif; ?>

                    <?php $isActive = ((int)$crumb->getId() === (int)$currentHolon->getId()); ?>
                    <?php if (!$isActive): ?>
                    <span class="crumb<?= $isActive ? ' active' : '' ?>"
                          data-cid="<?= (int)$crumb->getId() ?>"
                          data-is-root="<?= $index === 0 ? '1' : '0' ?>">
                        <?= omoApiEscape($crumb->getFullDisplayName()) ?>
                    </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="circle-title-row">
                <?php if ($holonIconUrl !== ''): ?>
                    <div class="circle-title__icon">
                        <img src="<?= omoApiEscape($holonIconUrl) ?>" alt="">
                    </div>
                <?php endif; ?>
                <div class="circle-title-copy">
                    <div class="circle-kicker generic-card-title generic-card-title--eyebrow"><?= omoApiEscape($holonTypeLabel) ?></div>
                    <h2 class="circle-title generic-card-title generic-card-title--section">
                        <span><?= omoApiEscape($currentHolon->getFullDisplayName()) ?></span>
                    </h2>
                </div>
            </div>
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
                        <?php if ($canManageOrganizationModel): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-toggle-organization-model="1"
                                data-oid="<?= (int)$organizationId ?>"
                            ><?= omoApiEscape($organization->isSharedAsTemplate() ? t('leftbar.actions.stop_sharing_as_model') : t('leftbar.actions.share_as_model')) ?></button>
                        <?php endif; ?>
                        <?php if ($canCreateChildHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-create-holon="1"
                                data-cid="<?= (int)$currentHolon->getId() ?>"
                            ><?= omoApiEscape(t('leftbar.actions.add')) ?></button>
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
                            ><?= omoApiEscape(t('leftbar.actions.edit')) ?></button>
                        <?php endif; ?>
                        <?php if ($canMoveHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-move-holon="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                            ><?= omoApiEscape(t('leftbar.actions.move')) ?></button>
                        <?php endif; ?>
                        <?php if ($canViewHolonHistory): ?>
                            <button
                                type="button"
                                class="circle-menu__item"
                                data-open-holon-history="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                            ><?= omoApiEscape(t('leftbar.actions.history')) ?></button>
                        <?php endif; ?>
                        <?php if ($canDeleteHolon): ?>
                            <button
                                type="button"
                                class="circle-menu__item circle-menu__item--danger"
                                data-delete-holon="1"
                                data-hid="<?= (int)$currentHolon->getId() ?>"
                                data-name="<?= omoApiEscape($currentHolon->getFullDisplayName()) ?>"
                                data-type-label="<?= omoApiEscape($holonTypeLabel) ?>"
                                data-descendant-count="<?= (int)$deleteDescendantCount ?>"
                                data-parent-id="<?= (int)$deleteParentId ?>"
                                data-parent-is-root="<?= $deleteParentIsRoot ? '1' : '0' ?>"
                            ><?= omoApiEscape(t('leftbar.actions.delete')) ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <button type="button" class="circle-badge circle-badge--link" data-copy-direct-link="1" data-cid="<?= (int)$currentHolon->getId() ?>">#<?= (int)$currentHolon->getId() ?></button>
        </div>
    </div>
    <?php if (count($memberCards) > 0 || $canAddMembers): ?>
        <div class="circle-members">
            <div class="circle-members__label generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(t('leftbar.members.section_title')) ?></div>
            <div class="circle-members__row">
                <div class="circle-members__list">
                    <?php foreach ($memberCards as $member): ?>
						<?php
						$hasPendingInvitation = !empty($member['hasPendingInvitation']);
						$memberTooltip = $hasPendingInvitation
							? t('leftbar.members.pending_tooltip', ['memberName' => $member['displayName']])
							: (!empty($member['isAdmin'])
								? t('leftbar.members.admin_tooltip', ['memberName' => $member['displayName'], 'adminLabel' => $adminLabel])
								: (string)$member['displayName']); ?>
						<?php
						$memberFocus = '';
						if ($isRoleHolon && is_array($member['assignmentLinks'] ?? null)) {
							foreach ($member['assignmentLinks'] as $assignmentLink) {
								if ((int)($assignmentLink['holonId'] ?? 0) !== (int)$currentHolon->getId()) {
									continue;
								}
								$memberFocus = trim((string)($assignmentLink['focus'] ?? ''));
								break;
							}
						}
						if ($memberFocus !== '') {
							$memberTooltip .= "\n" . t('leftbar.members.role_focus_line', ['focus' => $memberFocus]);
						}
						?>
                        <?php
                        $memberPhotoUrl = trim((string)($member['photoUrl'] ?? ''));
                        $memberInitials = trim((string)($member['initials'] ?? ''));
                        if ($memberInitials === '') {
                            $memberInitials = \dbObject\User::buildInitials((string)($member['displayName'] ?? ''));
                        }
                        $memberAvatarPalette = commonBuildAvatarPalette(
                            $memberInitials,
                            (int)($member['userId'] ?? 0),
                            trim((string)($member['avatarSeed'] ?? '')) !== ''
                                ? trim((string)$member['avatarSeed'])
                                : \commonBuildAvatarSeedLabel(
                                    (string)($member['displayName'] ?? ''),
                                    ''
                                )
                        );
                        $memberAvatarStyle = '--circle-member-avatar-bg: ' . $memberAvatarPalette['background'] . '; --circle-member-avatar-text: ' . $memberAvatarPalette['foreground'] . ';';
                        ?>
                        <span
                            class="circle-member<?= !empty($member['isAdmin']) ? ' circle-member--admin' : ' circle-member--regular' ?><?= $hasPendingInvitation ? ' circle-member--pending' : '' ?>"
                            data-circle-member-item="1"
                            data-tooltip="<?= omoApiEscape($memberTooltip) ?>"
                            data-member-user-id="<?= (int)($member['userId'] ?? 0) ?>"
                            <?= $memberPhotoUrl === '' ? 'style="' . omoApiEscape($memberAvatarStyle) . '"' : '' ?>
                            <?php if ((int)($member['userId'] ?? 0) > 0 && !empty($member['canViewDetail'])): ?>
                                data-open-user-context="1"
                                role="button"
                                tabindex="0"
                            <?php endif; ?>
                            aria-label="<?= omoApiEscape($memberTooltip) ?>"
                        >
                            <?php if ($memberPhotoUrl !== ''): ?>
                                <img
                                    src="<?= omoApiEscape($memberPhotoUrl) ?>"
                                    alt="<?= omoApiEscape($member['displayName']) ?>"
                                    class="circle-member__photo"
                                >
                            <?php else: ?>
                                <span class="circle-member__initials"><?= omoApiEscape($memberInitials) ?></span>
                            <?php endif; ?>
                            <?php if ($hasPendingInvitation): ?>
                                <span class="circle-member__invitation-icon" aria-hidden="true">&#9993;</span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                    <button
                        type="button"
                        class="circle-member circle-member--more"
                        data-circle-member-action="more"
                        data-open-team-drawer="1"
                        data-cid="<?= (int)$currentHolon->getId() ?>"
                        aria-label="<?= omoApiEscape(t('leftbar.members.view_all')) ?>"
                        title="<?= omoApiEscape(t('leftbar.members.view_all')) ?>"
                        <?= $regularMemberCount <= 8 ? 'hidden' : '' ?>
                    >...</button>
                    <?php if ($canAddMembers): ?>
                        <button
                            type="button"
                            class="circle-member circle-member--add"
                            data-circle-member-action="add"
                            data-open-member-popup="1"
                            data-hid="<?= (int)$currentHolon->getId() ?>"
                            aria-label="<?= omoApiEscape(t('leftbar.members.add')) ?>"
                            title="<?= omoApiEscape(t('leftbar.members.add')) ?>"
                        >+</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <?php if (count($sections) === 0): ?>
        <div class="circle-section generic-section generic-accordion generic-accordion--card">
            <div class="circle-section__title generic-card-title generic-card-title--small"><?= omoApiEscape(t('leftbar.empty.section_title')) ?></div>
            <p class="section-text generic-description generic-description--small generic-description--primary"><?= omoApiEscape(t('leftbar.empty.message')) ?></p>
        </div>
    <?php endif; ?>

    <?php foreach ($sections as $section): ?>
        <div class="circle-section generic-section generic-accordion generic-accordion--card generic-accordion--collapsible">
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
    <?php if (count($childNavigation['circles']) > 0 || count($childNavigation['groups']) > 0 || count($childNavigation['roles']) > 0): ?>
        <div class="circle-section circle-section--navigation generic-section generic-accordion generic-accordion--card generic-accordion--collapsible" data-section-key="dependencies">
            <div class="generic-accordion__header">
                <span class="generic-accordion__title generic-card-title generic-card-title--small"><?= omoApiEscape(t('leftbar.children.section_title')) ?></span>
                <span class="generic-accordion__toggle">&#9662;</span>
            </div>
            <div class="generic-accordion__content">
                <?php if (count($childNavigation['circles']) > 0): ?>
                    <div class="child-nav-group">
                        <div class="child-nav-subtitle generic-card-title generic-card-title--small"><?= omoApiEscape($childCircleLabel) ?></div>
                        <div class="child-nav-list">
                            <?php foreach ($childNavigation['circles'] as $child): ?>
                                <button type="button" class="child-nav-item" data-cid="<?= (int)$child['id'] ?>">
                                    <span class="child-nav-dot child-nav-dot--container"></span>
                                    <span class="child-nav-label"><?= omoApiEscape($child['name']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (count($childNavigation['groups']) > 0): ?>
                    <div class="child-nav-group">
                        <div class="child-nav-subtitle generic-card-title generic-card-title--small"><?= omoApiEscape($childGroupLabel) ?></div>
                        <div class="child-nav-list">
                            <?php foreach ($childNavigation['groups'] as $child): ?>
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
                        <div class="child-nav-subtitle generic-card-title generic-card-title--small"><?= omoApiEscape($childRoleLabel) ?></div>
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

    <?php if (count($debugPermissionEntries) > 0 && 1==0): ?>
        <div class="circle-section generic-section generic-accordion generic-accordion--card">
            <div class="circle-section__title generic-card-title generic-card-title--small">Permissions</div>
            <p class="section-text">Codes disponibles sur ce holon. Ceux que vous avez sont en gras.</p>
            <div class="section-text">
                <?php foreach ($debugPermissionEntries as $index => $permissionEntry): ?>
                    <?php if ($index > 0): ?>, <?php endif; ?>
                    <?php if (!empty($permissionEntry['isAllowed'])): ?>
                        <strong><?= omoApiEscape($permissionEntry['key']) ?></strong>
                    <?php else: ?>
                        <span><?= omoApiEscape($permissionEntry['key']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <pre class="section-text" style="white-space: pre-wrap; font-size: 12px; margin-top: 12px;"><?= omoApiEscape(json_encode($debugPermissionSessionCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
            <pre class="section-text" style="white-space: pre-wrap; font-size: 12px; margin-top: 12px;"><?= omoApiEscape(json_encode($debugPermissionRebuild, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
        </div>
    <?php endif; ?>

</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/getOrg.css') ?>">

<?= commonPageScriptTags('/omo/api/getOrg.js', [
    'leftbarMembersAdd' => t('leftbar.members.add'),
    'leftbarActionsHistory' => t('leftbar.actions.history'),
    'rootId' => (int)$navigationRoot->getId(),
    'oid' => (int)$organizationId,
    'leftbarCopyLinkSuccess' => t('leftbar.copy_link.success'),
    'leftbarCopyLinkError' => t('leftbar.copy_link.error'),
    'leftbarProjectChildrenLoading' => omoApiEscape(t('leftbar.project.children.loading')),
    'leftbarProjectChildrenEmpty' => omoApiEscape(t('leftbar.project.children.empty')),
    'leftbarProjectChildrenError' => omoApiEscape(t('leftbar.project.children.error')),
], 'omoOrganizationPageConfig') ?>
