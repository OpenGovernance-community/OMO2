<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function omoMcpUiBundle(): array
{
    static $bundle;
    if ($bundle !== null) return $bundle;
    $sourceLang = [
        'title' => ['text' => 'Connexion OMO pour un assistant', 'context' => 'MCP OAuth consent page title.'],
        'intro' => ['text' => 'Connectez-vous avec votre compte OMO pour choisir une organisation.', 'context' => 'Login introduction for MCP authorization.'],
        'request' => ['text' => '{client} demande un acces en lecture a vos informations OMO.', 'context' => 'Consent request; client is an untrusted application name.'],
        'scope' => ['text' => 'Cette connexion permet de rechercher et lire les informations auxquelles votre compte a acces dans l organisation choisie : structure et proprietes, membres, documents et PV, projets, calendrier et autres modules disponibles. Vos droits OMO sont appliques a chaque lecture.', 'context' => 'Data scope of MCP reading.'],
        'scope_read_only' => ['text' => 'Cette autorisation est limitee a la lecture.', 'context' => 'Read-only scope on consent and connections pages.'],
        'identity' => ['text' => 'Compte : {name}', 'context' => 'Signed-in identity on consent page.'],
        'destination' => ['text' => 'Adresse de retour : {origin}', 'context' => 'Show the full callback URI to identify the requesting application.'],
        'organization' => ['text' => 'Organisation autorisee', 'context' => 'Organization selector label on OAuth consent.'],
        'allow' => ['text' => 'Autoriser la lecture', 'context' => 'Approve read-only OAuth access.'],
        'request_create' => ['text' => '{client} demande la lecture de vos informations OMO et la creation de documents.', 'context' => 'Consent request for document creation.'],
        'scope_create' => ['text' => 'L assistant pourra aussi enregistrer des textes et fichiers dans les espaces ou votre compte a le droit de creer des documents. Les droits OMO sont verifies a chaque creation. Cette autorisation ne permet pas de modifier ou supprimer des documents existants.', 'context' => 'Additional document creation scope on OAuth consent.'],
        'allow_create' => ['text' => 'Autoriser la lecture et la creation de documents', 'context' => 'Approve read and document creation OAuth access.'],
        'deny' => ['text' => 'Refuser', 'context' => 'Deny OAuth consent.'],
        'empty' => ['text' => 'Aucune organisation accessible pour ce compte.', 'context' => 'No eligible organization for MCP authorization.'],
        'invalid' => ['text' => 'Demande invalide ou expiree. Relancez la connexion depuis votre assistant.', 'context' => 'Persistent OAuth request validation error.'],
        'connections' => ['text' => 'Mes connexions aux assistants', 'context' => 'MCP grant management page title and link.'],
        'connections_intro' => ['text' => 'Chaque connexion autorise les operations acceptees lors du consentement, dans une organisation. Vous pouvez retirer cet acces ici.', 'context' => 'MCP grant management page introduction.'],
        'no_connections' => ['text' => 'Aucune connexion active.', 'context' => 'Empty MCP grant management state.'],
        'revoke' => ['text' => 'Revoquer', 'context' => 'Revoke a personal MCP grant.'],
        'revoked' => ['text' => 'Acces revoque.', 'context' => 'Topbar notification after personal grant revocation.'],
        'revoke_error' => ['text' => 'Impossible de revoquer cet acces. Rechargez la page.', 'context' => 'Topbar notification for invalid revocation form.'],
        'expires' => ['text' => 'Autorisation valable jusqu au {date}', 'context' => 'Display the refresh grant expiration.'],
    ];
    $lang = loadTranslationBundle('common_mcp_ui', commonAuthGetTranslationLocale(), $sourceLang);
    return $bundle = [$lang, $sourceLang];
}
function omoMcpUiT(string $key, array $variables = []): string
{
    [$lang, $sourceLang] = omoMcpUiBundle();
    return t($key, $variables, $lang, $sourceLang);
}
function omoMcpEscape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function omoMcpPageStart(string $title, ?string $redirectUri = null): void
{
    $formAction = "'self'";
    if ($redirectUri !== null) {
        // Only pass the callback from the validated, session-stored OAuth request.
        // Chromium also checks form-action on the POST's redirect to the client.
        if (!omoMcpValidRedirect($redirectUri, commonReadRuntimeEnvBool('MCP_ALLOW_LOCAL_HTTP', false))) {
            throw new InvalidArgumentException('Invalid consent callback.');
        }
        $parts = parse_url($redirectUri);
        $formAction .= ' ' . $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
    header("Content-Security-Policy: frame-ancestors 'none'; form-action " . $formAction . "; base-uri 'none'");
    header('X-Frame-Options: DENY');
    ?>
    <!doctype html><html lang="<?= omoMcpEscape(commonAuthGetTranslationLocale()) ?>"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= omoMcpEscape($title) ?></title>
    <?= commonStylesheetTags('/common/assets/theme.css') ?>
    <?= commonStylesheetTags('/common/assets/components.css') ?>
    <?= commonStylesheetTags('/common/notifications/notifications.css') ?>
    <script src="<?= commonAssetUrl('/common/notifications/notifications.js') ?>" defer></script>
    </head><body><main class="generic-section generic-stack">
    <h1 class="generic-card-title"><?= omoMcpEscape($title) ?></h1>
    <?php
}
function omoMcpPageEnd(): void { echo '</main></body></html>'; }
function omoMcpLoginIfNeeded(string $returnTo): void
{
    if (commonGetCurrentUserId() > 0) return;
    commonRenderMagicLoginPage(['title' => omoMcpUiT('title'), 'intro' => omoMcpUiT('intro'),
        'appName' => 'OMO', 'returnTo' => $returnTo]);
    exit;
}
function omoMcpEligibleOrganizations(int $userId): array
{
    $user = new \dbObject\User();
    if (!$user->load($userId)) return [];
    $items = [];
    foreach ($user->getAccessibleOrganizations() as $organization) {
        if (\dbObject\UserOrganization::hasActiveMembership($userId, (int)$organization->getId())
            && $organization->canViewDetail()) $items[] = $organization;
    }
    return $items;
}
