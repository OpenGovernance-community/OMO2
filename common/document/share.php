<?php
// Browser access only. Service callbacks use signed, document-scoped grants instead.
function commonDocumentSharePasswordVerified(\dbObject\DocumentShareLink $link): bool
{
    return !$link->requiresPassword() || hash_equals($link->getAccessFingerprint(),
        (string)($_SESSION['omo_document_share_approvals'][(int)$link->getId()] ?? ''));
}

function commonDocumentShareRememberPassword(\dbObject\DocumentShareLink $link): void
{
    $_SESSION['omo_document_share_approvals'][(int)$link->getId()] = $link->getAccessFingerprint();
    // Preserve existing HTML live-follow and PV participation entry points.
    commonRememberSharePasswordVerified((string)$link->get('token'));
}

function commonResolveDocumentShare(string $token, int $documentId = 0, bool $requirePassword = true): ?\dbObject\DocumentShareLink
{
    $link = \dbObject\DocumentShareLink::findValidByToken($token);
    $document = $link ? $link->getDocument() : null;
    return $document && hash_equals((string)$link->get('token'), $token) && $link->allowsDocumentAccess($document)
        && ($documentId <= 0 || (int)$document->getId() === $documentId)
        && (!$requirePassword || commonDocumentSharePasswordVerified($link)) ? $link : null;
}

/** Exact-document grant for collaborative launchers; never enters the general OMO API bootstrap. */
function commonResolveDocumentLinkAccess(): ?array
{
    $id = (int)($_GET['id'] ?? 0);
    if (isset($_GET['share_token'])) {
        $link = commonResolveDocumentShare((string)$_GET['share_token'], $id);
        $document = $link ? $link->getDocument() : null;
        return $document ? ['document' => $document, 'shareLink' => $link,
            'canEdit' => $link->allowsDocumentEditing($document)] : null;
    }
    $document = \dbObject\Document::findByReaderCode((string)($_GET['code'] ?? ''), $id);
    return $document && $document->canReadFromLink((int)commonGetCurrentUserId())
        ? ['document' => $document, 'shareLink' => null, 'canEdit' => false] : null;
}

function commonDocumentShareValidUntil(\dbObject\DocumentShareLink $link, int $lifetime = 900): int
{
    $expiration = $link->get('dateexpiration');
    return min(time() + $lifetime, $expiration instanceof DateTimeInterface ? $expiration->getTimestamp() : PHP_INT_MAX);
}

/** Etherpad rechecks its native session on every message, including existing sockets. */
function commonDocumentShareRevokeEditingAccess(\dbObject\DocumentShareLink $link, \dbObject\Document $document): bool
{
    if (!$document->isEtherpadDocument() || !$link->allowsDocumentEditing($document)) return true;
    require_once dirname(__DIR__) . '/etherpad.php';
    $organization = new \dbObject\Organization();
    if (!$organization->load((int)$document->get('IDorganization'))) return false;
    $author = omoEtherpadApiRequest($organization, 'createAuthorIfNotExistsFor', [
        'authorMapper' => 'omo-share-' . $link->getId() . '-' . $link->getAccessFingerprint(),
    ]);
    $authorId = (string)($author['data']['authorID'] ?? '');
    if (empty($author['status']) || $authorId === '') return false;
    $sessions = omoEtherpadApiRequest($organization, 'listSessionsOfAuthor', ['authorID' => $authorId]);
    if (empty($sessions['status'])) return false;
    foreach (array_keys($sessions['data'] ?? []) as $id) {
        if (empty(omoEtherpadApiRequest($organization, 'deleteSession', ['sessionID' => $id])['status'])) return false;
    }
    return true;
}
