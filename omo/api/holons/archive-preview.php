<?php

/** Read-only property rendering, shared by archive confirmation and consultation. */
function omoHolonArchivePropertyHtml(array $entry, \dbObject\Organization $organization, callable $tr, array $visibleHolonIds = []): string
{
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $format = (int)($entry['formatId'] ?? 0);
    $value = (string)($entry['effectiveValue'] ?? $entry['value'] ?? '');
    if (!\dbObject\PropertyFormat::isListFormat($format)) {
        $text = trim(strip_tags($value));
        return $text === '' ? '' : '<p class="generic-description">' . nl2br($escape($text)) . '</p>';
    }

    $type = (string)($entry['listItemType'] ?? 'text');
    $items = [];
    $seen = [];
    // The ordinary structure view combines inherited and local list entries.
    $values = $format === \dbObject\PropertyFormat::FORMAT_LIST
        ? [$entry['ancestor'] ?? '', $entry['value'] ?? $value]
        : [$value];
    foreach ($values as $listValue) {
        foreach ($organization->getHolonEditorListDisplayItems($listValue, $format, $type === 'authority' ? $type : '') as $item) {
            $key = is_array($item) && !empty($item['id']) ? 'id:' . $item['id'] : json_encode($item);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $itemHtml = omoHolonArchiveListItemHtml($item, $type, $organization, $tr, $visibleHolonIds);
            if ($itemHtml !== '') $items[] = '<li>' . $itemHtml . '</li>';
        }
    }
    $html = $items ? '<ul class="generic-stack generic-stack--compact">' . implode('', $items) . '</ul>' : '';
    if ($format === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
        $parts = \dbObject\PropertyFormat::getHtmlListParts($value);
        foreach (['before', 'after'] as $part) {
            $text = trim(strip_tags($parts[$part]));
            $block = $text === '' ? '' : '<p class="generic-description">' . nl2br($escape($text)) . '</p>';
            $html = $part === 'before' ? $block . $html : $html . $block;
        }
    }
    return $html;
}

function omoHolonArchiveListItemHtml($item, string $type, \dbObject\Organization $organization, callable $tr, array $visibleHolonIds): string
{
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $label = is_array($item) ? (string)($item['title'] ?? $item['label'] ?? $item['value'] ?? '') : (string)$item;
    $description = is_array($item) ? (string)($item['description'] ?? $item['text'] ?? '') : '';
    $context = '';
    if ($type === \dbObject\Property::LIST_ITEM_AUTHORITY) {
        $id = is_array($item) ? (int)($item['id'] ?? 0) : (ctype_digit(trim((string)$item)) ? (int)$item : 0);
        if ($id > 0) {
            $authority = new \dbObject\Authority();
            $owner = $authority->load($id) ? $authority->getHolon() : null;
            if (!$owner || $owner->resolveOrganizationId() !== (int)$organization->getId()
                || !(isset($visibleHolonIds[(int)$owner->getId()]) || $owner->canViewDetail() || $owner->canViewArchive())) {
                return '<span class="generic-description">' . $escape($tr('authority_unavailable', ['id' => $id])) . '</span>';
            }
            $label = (string)$authority->get('label');
            $description = str_replace(\dbObject\Authority::IMPORT_NEEDS_PARENT_MARKER, '', (string)$authority->get('description'));
            $parent = $authority->getParent();
            $parentOwner = $parent ? $parent->getHolon() : null;
            if ($parentOwner && $parentOwner->resolveOrganizationId() === (int)$organization->getId()
                && (isset($visibleHolonIds[(int)$parentOwner->getId()]) || $parentOwner->canViewDetail() || $parentOwner->canViewArchive())) {
                $context = $tr('authority_parent', ['name' => $parent->get('label'), 'holon' => $parentOwner->getFullDisplayName()]);
            } else {
                $context = $tr($parent ? 'authority_parent_unavailable' : 'authority_local');
            }
            if ($authority->isShell()) $context .= ' · ' . $tr('authority_shell');
        }
    }
    if (trim($label) === '' && trim($description) === '') return '';
    $html = '<span class="generic-description generic-description--primary"><strong>' . $escape($label) . '</strong></span>';
    if (trim($description) !== '') $html .= '<p class="generic-description generic-description--compact">' . nl2br($escape(strip_tags($description))) . '</p>';
    if ($context !== '') $html .= '<p class="generic-description generic-description--small generic-description--compact">' . $escape($context) . '</p>';
    return $html;
}
