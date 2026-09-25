<?php
return static function (\dbObject\Document $document): array {
    return [
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
};
