<?php

function commonPvParticipationCanUsePath(string $path): bool
{
    return in_array(rtrim($path, '/'), [
        '/omo/pv_participation.php',
        '/omo/api/documents/pv/editor.php',
        '/omo/api/documents/pv/action.php',
        '/omo/api/documents/pv/discussion.php',
    ], true);
}

function commonResolvePublicPvParticipationLink()
{
    static $resolved = false;
    static $link = null;
    if ($resolved) {
        return $link;
    }
    $resolved = true;

    $path = trim((string)commonGetRequestPath());
    if (!commonPvParticipationCanUsePath($path)) {
        return null;
    }

    $documentId = (int)($_REQUEST['document_id'] ?? $_REQUEST['id'] ?? 0);
    $token = trim((string)($_REQUEST['pv_token'] ?? ''));
    if ($token === '' && $documentId > 0) {
        $token = trim((string)($_SESSION['omo_pv_participation_tokens'][$documentId] ?? ''));
    }
    if ($token === '') {
        return null;
    }

    $candidate = \dbObject\DocumentShareLink::findValidByToken($token);
    if (!($candidate instanceof \dbObject\DocumentShareLink) || !$candidate->allowsPvContribution()) {
        return null;
    }

    $document = $candidate->getDocument();
    if (
        !($document instanceof \dbObject\Document)
        || !$document->isPvDocument()
        || $document->isPvValidated()
        || ($documentId > 0 && (int)$document->getId() !== $documentId)
    ) {
        return null;
    }

    if (!isset($_SESSION['omo_pv_participation_tokens']) || !is_array($_SESSION['omo_pv_participation_tokens'])) {
        $_SESSION['omo_pv_participation_tokens'] = [];
    }
    $_SESSION['omo_pv_participation_tokens'][(int)$document->getId()] = (string)$candidate->get('token');
    $link = $candidate;
    return $link;
}

function commonGetPublicPvParticipationLink()
{
    $link = $GLOBALS['omoPublicPvParticipationLink'] ?? null;
    $userId = (int)commonGetCurrentUserId();
    if ($link instanceof \dbObject\DocumentShareLink && $userId > 0) {
        $organizationId = (int)$link->get('IDorganization');
        $document = $link->getDocument();
        // A restored account session uses the same editor and permissions as OMO.
        // A participation token alone never upgrades the recipient's rights.
        if (\dbObject\UserOrganization::hasActiveMembership($userId, $organizationId)
            && $document instanceof \dbObject\Document
            && (int)$document->get('IDorganization') === $organizationId
            && ($document->isPvEditor($userId) || $document->canUserManagePvStructure($organizationId, $userId))
            && $document->canUserOpenPvEditor($userId, $organizationId)) {
            return null;
        }
    }
    return $link;
}

// This grant is used only by the standalone reader and the file download route.
function commonResolvePvEmbeddedDocument(): ?\dbObject\Document
{
    $token = trim((string)($_GET['pv_token'] ?? ''));
    $sourceId = (int)($_GET['pv_document_id'] ?? 0);
    $link = \dbObject\DocumentShareLink::findValidByToken($token);
    if (!$link || $sourceId <= 0 || (int)$link->get('IDdocument') !== $sourceId
        || ($link->requiresPassword() && !commonIsSharePasswordVerified($token))) return null;
    return $link->getReadablePvEmbeddedDocument((int)($_GET['id'] ?? 0));
}

function commonPvParticipationRecipientIsOrganizationMember($shareLink, int $organizationId): bool
{
    if (!($shareLink instanceof \dbObject\DocumentShareLink) || $organizationId <= 0) {
        return false;
    }

    return \dbObject\UserOrganization::hasActiveMembership(
        $shareLink->getRecipientUserId(),
        $organizationId
    );
}

function commonPvParticipationRecipientOrganizationUserId($shareLink, int $organizationId): int
{
    return commonPvParticipationRecipientIsOrganizationMember($shareLink, $organizationId)
        ? $shareLink->getRecipientUserId()
        : 0;
}

function commonPvParticipationRecipientCanUseStructure($shareLink, int $organizationId): bool
{
    if (!commonPvParticipationRecipientIsOrganizationMember($shareLink, $organizationId)) {
        return false;
    }

    $organization = new \dbObject\Organization();
    return $organization->load($organizationId)
        && $organization->isStructureApplicationEnabled($shareLink->getRecipientUserId());
}

function commonPvParticipationCanEditPoint(\dbObject\Document $document, \dbObject\DocumentPvPoint $point, $shareLink): bool
{
    if ($point->isMoved()) return false;
    if (
        !($shareLink instanceof \dbObject\DocumentShareLink)
        || !$shareLink->allowsPvContribution()
        || (int)$point->get('IDdocument') !== (int)$document->getId()
        || $point->isGroup()
        || $point->isHandled()
        || $document->isPvValidated()
        || !in_array($document->getPvStage(), [\dbObject\Document::PV_STAGE_PREPARATION, \dbObject\Document::PV_STAGE_MEETING], true)
    ) {
        return false;
    }

    $recipientUserId = $shareLink->getRecipientUserId();
    if ($recipientUserId > 0 && $recipientUserId === (int)$point->get('IDuser_author')) {
        return true;
    }

    return hash_equals($shareLink->getRecipientEmail(), trim(mb_strtolower((string)$point->get('author_email'), 'UTF-8')));
}
