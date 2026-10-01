<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';

use dbObject\DocumentShareLink;
use dbObject\EventPublicRegistration;
use dbObject\Organization;

$sourceLang = [
    'unavailable' => ['text' => 'Ce document est indisponible pour cette inscription.', 'context' => 'A public registrant cannot open an event document.'],
    'error' => ['text' => 'Impossible d’ouvrir le document pour le moment. Réessayez dans quelques instants.', 'context' => 'Temporary failure opening a public event document.'],
];
$lang = loadTranslationBundle('event_public_document', translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr'), $sourceLang);
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');

$registration = EventPublicRegistration::forToken(trim((string)($_GET['receipt'] ?? '')));
$document = $registration ? $registration->getAccessibleDocument((int)($_GET['id'] ?? 0)) : null;
if (!$document) {
    http_response_code(404);
    echo htmlspecialchars(t('unavailable', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8');
    exit;
}

try {
    $url = '';
    if ($document->isPvDocument() && !$document->isPvValidated()) {
        $event = $registration->getAccessibleEvent();
        $creatorId = (int)$event->get('IDuser') ?: (int)$document->get('IDusercreation');
        $share = DocumentShareLink::getOrCreatePvParticipantLink($document, $creatorId, (string)$registration->get('email'));
        if ($share instanceof DocumentShareLink) { $url = $share->buildPvParticipationUrl(); }
    } elseif ($document->isEtherpadDocument()) {
        require_once dirname(__DIR__) . '/common/etherpad.php';
        $organization = new Organization();
        if ($organization->load((int)$document->get('IDorganization')) && omoEtherpadHasConfig($organization) && $document->getEtherpadPadId() !== '') {
            $access = omoEtherpadPrepareEditingAccess(
                $organization,
                $document->getEtherpadPadId(),
                'omo-public-registration-' . (int)$registration->getId(),
                $registration->getParticipantDisplayName()
            );
            if (!empty($access['status'])) { $url = $access['url']; }
        }
    } elseif ($document->isFramapadExternalLink()) {
        $url = $document->getExternalUrl();
        $fragment = '';
        if (str_contains($url, '#')) { [$url, $fragment] = explode('#', $url, 2); $fragment = '#' . $fragment; }
        [$padBase, $padQuery] = array_pad(explode('?', $url, 2), 2, '');
        parse_str($padQuery, $padOptions);
        $padOptions['userName'] = $registration->getParticipantDisplayName();
        $url = $padBase . '?' . http_build_query($padOptions) . $fragment;
    }
    if ($url !== '') {
        header('Location: ' . $url, true, 302);
        exit;
    }
} catch (Throwable $exception) {
    error_log('Public event document open failed for document ' . (int)$document->getId() . ': ' . get_class($exception));
}
http_response_code(503);
echo htmlspecialchars(t('error', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8');
