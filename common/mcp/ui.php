<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function omoMcpUiBundle(): array
{
    static $bundle;
    if ($bundle !== null) return $bundle;
    $sourceLang = [
        'title' => ['text' => 'Connexion OMO pour un assistant', 'context' => 'MCP OAuth consent page title.'],
        'intro' => ['text' => 'Connectez-vous avec votre compte OMO pour choisir une organisation.', 'context' => 'Login introduction for MCP authorization.'],
        'brand' => ['text' => 'Connexion aux assistants', 'context' => 'Public MCP page brand subtitle.'],
        'permissions' => ['text' => 'Accès demandés', 'context' => 'OAuth permissions section heading.'],
        'read_heading' => ['text' => 'Consulter vos informations', 'context' => 'Read permission heading.'],
        'create_heading' => ['text' => 'Créer des documents', 'context' => 'Document creation permission heading.'],
        'mail_heading' => ['text' => 'Envoyer des e-mails', 'context' => 'Object mail permission heading.'],
        'event_heading' => ['text' => 'Créer des événements', 'context' => 'Event creation permission heading.'],
        'project_heading' => ['text' => 'Créer et modifier des projets', 'context' => 'Project write permission heading.'],
        'scope_project' => ['text' => 'L’assistant pourra créer et modifier les projets que vous demandez, notamment leur statut, dans la limite de vos droits OMO. Un blocage exige une raison et une date de réexamen. Cette autorisation ne permet pas de supprimer ou archiver des projets.', 'context' => 'Additional projects:write OAuth consent.'],
        'decision_heading' => ['text' => 'Créer des scrutins', 'context' => 'Decision creation permission heading.'],
        'scope_decision' => ['text' => 'L’assistant pourra créer les scrutins que vous demandez dans les espaces autorisés, avec des propositions et des invitations. Les propositions datées reservent des événements provisoires dans les agendas des participants. Les droits sont vérifiés à chaque création. L’envoi d’e-mails exige une autorisation distincte.', 'context' => 'Additional decisions:create OAuth consent, including native calendar reservations.'],
        'scope_event' => ['text' => 'L’assistant pourra créer les événements que vous demandez dans les espaces où votre compte en a le droit, avec les invités habituels ou ceux que vous choisissez. Les droits sont vérifiés à chaque création. Cette autorisation ne permet pas de modifier ou supprimer des événements existants.', 'context' => 'Additional events:create OAuth consent.'],
        'scope_mail' => ['text' => 'L’assistant pourra envoyer les messages que vous lui demandez aux membres ou invités références dans un holon, une réunion, un projet ou une décision. Il doit disposer de vos droits de participation ou de gestion. Les décisions exigent un droit de gestion. Aucune adresse libre ne peut être fournie. Les destinataires et droits sont vérifiés avant chaque envoi, avec des limites de fréquence.', 'context' => 'Additional mail:send OAuth consent.'],
        'request_write' => ['text' => '{client} demande les accès présentés ci-dessous à votre compte OMO.', 'context' => 'Consent with optional writing scopes.'],
        'allow_scopes' => ['text' => 'Autoriser ces accès', 'context' => 'Approve the displayed OAuth scopes.'],
        'choose_heading' => ['text' => 'Choisir une organisation', 'context' => 'OAuth organization selection heading.'],
        'choose_hint' => ['text' => 'L’accès de cet assistant sera limité à cette organisation et aux droits de votre compte.', 'context' => 'Organization scope explanation before consent.'],
        'revoke_hint' => ['text' => 'Vous pourrez retirer cet accès à tout moment depuis vos connexions aux assistants.', 'context' => 'Revocation reassurance before consent.'],
        'request' => ['text' => '{client} demande un accès en lecture à vos informations OMO.', 'context' => 'Consent request; client is an untrusted application name.'],
        'scope' => ['text' => 'Cette connexion permet de rechercher et lire les informations auxquelles votre compte a accès dans l’organisation choisie : structure et propriétés, membres, documents et PV, projets, calendrier et autres modules disponibles. Elle permet aussi de consulter les disponibilités des membres, avec leurs calendriers importés, sans exposer les titres privés. Vos droits OMO sont appliqués à chaque lecture.', 'context' => 'Data scope of MCP reading.'],
        'scope_read_only' => ['text' => 'Cette autorisation est limitée à la lecture.', 'context' => 'Read-only scope on consent and connections pages.'],
        'identity' => ['text' => 'Compte : {name}', 'context' => 'Signed-in identity on consent page.'],
        'destination' => ['text' => 'Adresse de retour : {origin}', 'context' => 'Show the full callback URI to identify the requesting application.'],
        'organization' => ['text' => 'Organisation autorisée', 'context' => 'Organization selector label on OAuth consent.'],
        'allow' => ['text' => 'Autoriser la lecture', 'context' => 'Approve read-only OAuth access.'],
        'request_create' => ['text' => '{client} demande la lecture de vos informations OMO et la création de documents.', 'context' => 'Consent request for document creation.'],
        'scope_create' => ['text' => 'L’assistant pourra aussi enregistrer des textes et fichiers dans les espaces où votre compte a le droit de créer des documents. Les droits OMO sont vérifiés à chaque création. Cette autorisation ne permet pas de modifier ou supprimer des documents existants.', 'context' => 'Additional document creation scope on OAuth consent.'],
        'allow_create' => ['text' => 'Autoriser la lecture et la création de documents', 'context' => 'Approve read and document creation OAuth access.'],
        'deny' => ['text' => 'Refuser', 'context' => 'Deny OAuth consent.'],
        'empty' => ['text' => 'Aucune organisation éligible. Un abonnement Patreon individuel ou de l’organisation est requis, ainsi qu’une adhésion active.', 'context' => 'No eligible organization for MCP authorization.'],
        'invalid' => ['text' => 'Demande invalide ou expiree. Relancez la connexion depuis votre assistant.', 'context' => 'Persistent OAuth request validation error.'],
        'connections' => ['text' => 'Mes connexions aux assistants', 'context' => 'MCP grant management page title and link.'],
        'connections_intro' => ['text' => 'Chaque connexion autorise les opérations acceptées lors du consentement, dans une organisation. Vous pouvez retirer cet accès ici.', 'context' => 'MCP grant management page introduction.'],
        'no_connections' => ['text' => 'Aucune connexion active.', 'context' => 'Empty MCP grant management state.'],
        'revoke' => ['text' => 'Révoquer', 'context' => 'Revoke a personal MCP grant.'],
        'revoked' => ['text' => 'Accès révoqué.', 'context' => 'Topbar notification after personal grant revocation.'],
        'revoke_error' => ['text' => 'Impossible de révoquer cet accès. Rechargez la page.', 'context' => 'Topbar notification for invalid revocation form.'],
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
    <?= commonStylesheetTags('/common/mcp/ui.css') ?>
    <?= commonStylesheetTags('/common/notifications/notifications.css') ?>
    <script src="<?= commonAssetUrl('/common/notifications/notifications.js') ?>" defer></script>
    </head><body class="generic-public-page"><main class="generic-page-shell mcp-shell">
    <header class="generic-public-brand">
        <img class="generic-public-brand__logo" src="<?= omoMcpEscape(commonAssetUrl('/img/omo2/logo-omo-dark.png')) ?>" alt="OMO" width="1076" height="332">
        <span class="generic-public-brand__label"><?= omoMcpEscape(omoMcpUiT('brand')) ?></span>
    </header>
    <h1 class="generic-card-title generic-card-title--display"><?= omoMcpEscape($title) ?></h1>
    <?php
}
function omoMcpPageEnd(): void { echo '</main></body></html>'; }
function omoMcpLoginIfNeeded(string $returnTo): void
{
    if (commonGetCurrentUserId() > 0) return;
    commonRenderMagicLoginPage(['title' => omoMcpUiT('title'), 'intro' => omoMcpUiT('intro'),
        'appName' => omoMcpUiT('brand'), 'returnTo' => $returnTo, 'layout' => 'public']);
    exit;
}
function omoMcpEligibleOrganizations(int $userId): array
{
    require_once dirname(__DIR__) . '/patreon.php';
    $user = new \dbObject\User();
    if (!$user->load($userId)) return [];
    $items = [];
    foreach ($user->getAccessibleOrganizations() as $organization) {
        if (\dbObject\UserOrganization::hasActiveMembership($userId, (int)$organization->getId())
            && patreonUserCanUseAi($userId, (int)$organization->getId()) && $organization->canViewDetail()) $items[] = $organization;
    }
    return $items;
}
