<?php

function commonDocumentIconUrl(\dbObject\Document $document): string
{
    if ($document->isFolder()) {
        return '/omo/assets/images/documents/folder.png';
    }

    $icon = match ($document->getDocumentType()) {
        \dbObject\Document::TYPE_EXTERNAL_LINK => 'link',
        \dbObject\Document::TYPE_UPLOADED_FILE => match ($document->getStoredFileKind()) {
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            'text' => 'text',
            'spreadsheet' => 'spreadsheet-kind',
            'presentation' => 'presentation',
            'drawing' => 'drawing',
            default => 'download',
        },
        \dbObject\Document::TYPE_PV => 'pv',
        \dbObject\Document::TYPE_ETHERPAD => 'collaborative',
        \dbObject\Document::TYPE_ETHERCALC => 'spreadsheet',
        \dbObject\Document::TYPE_WHITEBOARD => 'whiteboard',
        default => 'file',
    };

    return '/omo/assets/images/documents/' . $icon . '.png';
}
