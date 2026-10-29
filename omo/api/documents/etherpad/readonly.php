<?php
// Rendered only after open.php has checked document access.
if (!isset($document, $content)) { http_response_code(404); exit; }
$sourceLang = [
    'readonly' => ['text' => 'Lecture seule', 'context' => 'Status of a private PAD shown without editing rights.'],
    'refresh' => ['text' => 'Le contenu est actualise automatiquement.', 'context' => 'Automatic refresh notice for the read-only PAD.'],
    'error' => ['text' => 'Impossible d’actualiser le PAD. Nouvelle tentative dans quelques instants.', 'context' => 'Persistent read-only PAD refresh error.'],
    'denied' => ['text' => 'Ce PAD n’est plus accessible.', 'context' => 'Read-only PAD access revoked while the page was open.'],
];
$lang = omoLoadTranslationBundle('omo_documents_etherpad_readonly', $sourceLang);
$t = static fn(string $key): string => t($key, [], $lang, $sourceLang);
$loaded = !empty($content['status']);
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; style-src 'self' 'unsafe-inline'; img-src https: data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
?>
<!doctype html>
<html lang="<?= omoApiEscape(translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= omoApiEscape($document->get('title')) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/omo/api/documents/etherpad/readonly.css">
    <script src="/omo/api/documents/etherpad/readonly.js" defer></script>
</head>
<body class="omo-pad-reader">
    <main class="generic-page-shell generic-stack" data-pad-reader>
        <p class="generic-help-text"><?= omoApiEscape($t('readonly')) ?> &middot; <?= omoApiEscape($t('refresh')) ?></p>
        <p class="generic-help-text" role="status" data-pad-reader-status
            data-error="<?= omoApiEscape($t('error')) ?>" data-denied="<?= omoApiEscape($t('denied')) ?>"<?= $loaded ? ' hidden' : '' ?>><?= $loaded ? '' : omoApiEscape($t('error')) ?></p>
        <article class="generic-section" data-pad-reader-content><?= $loaded ? $content['html'] : '' ?></article>
    </main>
</body>
</html>
