<?php
require_once dirname(__DIR__) . '/assets.php';

/** Render inside the reader root so fetched drawers initialize their own controller. */
function commonPvReferencesAssets(\dbObject\Document $document, int $viewerUserId, string $rootSelector): void
{
    if (!$document->isPvDocument() || $viewerUserId <= 0) return;
    echo '<link rel="stylesheet" href="' . commonAssetUrl('/common/document/pv-references.css') . '">';
    echo commonPageScriptTags('/common/document/pv-references.js', [
        'rootSelector' => $rootSelector,
        'stage' => $document->getPvStage(),
        'viewerKey' => 'user:' . $viewerUserId,
        'identities' => $document->getPvReferenceIdentities($viewerUserId),
    ]);
}
