<?php
return static function (\dbObject\Document $document, \dbObject\Organization $organization, int $currentHolonId, string $query = ''): array {
    $preview = [
        'title' => $document->get('title'),
        'fields' => [
            'tags' => $document->get('keywords'),
            'format' => $document->getDocumentTypeLabel(),
            'context' => $document->getOrganizationContextLabel(),
            'created' => $document->get('datecreation'),
            'updated' => $document->get('datemodification'),
            'author' => $document->getCreatedByDisplayName(),
            'status' => $document->isPvDocument() ? $document->getPvStageLabel() : '',
        ],
        'sections' => [omoSearchPreviewSection('summary', $document->get('description'))],
    ];
    if ($document->isPvDocument()) {
        $event = $document->getAssociatedEvent();
        $preview['fields']['context'] = $document->getPvPermissionHolon()?->getFullDisplayName() ?: $preview['fields']['context'];
        $preview['fields']['meeting'] = $event?->get('title');
        $preview['fields']['start'] = $event?->get('start_at');
        $preview['fields']['end'] = $event?->get('end_at');
        $points = $document->getVisiblePvPointsForUser((int)commonGetCurrentUserId());
        $groups = [];
        foreach ($points as $point) {
            if ($point->isGroup()) { $groups[(int)$point->getId()] = $point->get('title'); }
        }
        $items = [];
        foreach ($points as $point) {
            if ($point->isGroup()) { continue; }
            $items[] = [
                'title' => $point->get('title'),
                'text' => $point->get('content'),
                'meta' => $groups[(int)$point->get('IDparent')] ?? '',
            ];
        }
        $preview['collections'] = [['title' => omoSearchPreviewT('points'), 'items' => $items, 'matchingOnly' => true]];
    } elseif ($document->supportsHtmlContent() || omoSearchPreviewMatchScore(omoSearchPreviewText($document->get('content')), $query) > 0) {
        $preview['sections'][] = omoSearchPreviewSection('content', $document->get('content'));
    }
    return $preview;
};
