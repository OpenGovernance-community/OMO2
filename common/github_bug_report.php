<?php

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/patreon.php';
require_once __DIR__ . '/translation_bundles.php';

if (!function_exists('githubBugReportGetSourceLang')) {
    function githubBugReportGetSourceLang(): array
    {
        static $sourceLang = null;
        if ($sourceLang !== null) {
            return $sourceLang;
        }

        $texts = [
            'eyebrow' => 'Signalement rapide',
            'title' => 'Signaler un problème',
            'intro' => 'Ce formulaire crée un ticket GitHub avec votre description et le contexte technique de la page en cours.',
            'repository' => 'Dépôt : {value}',
            'user' => 'Compte : {value}',
            'organization' => 'Organisation : {value}',
            'login_required' => 'Connexion requise',
            'login_required_help' => 'Le signalement automatique vers GitHub est réservé aux personnes connectées.',
            'unavailable' => 'Fonction indisponible',
            'unavailable_help' => 'Le module de signalement n’est pas configuré sur ce serveur.',
            'patreon_required' => 'La configuration Patreon est également requise pour activer ce module.',
            'patreon_access' => 'Accès réservé aux comptes Patreon connectés',
            'patreon_access_help' => 'Le module de signalement est réservé aux personnes ayant connecté un compte Patreon actif, même sans abonnement payant.',
            'patreon_connect_help' => 'Pour continuer, connectez votre compte Patreon puis revenez à ce formulaire.',
            'connect' => 'Se connecter avec Patreon',
            'describe' => 'Décrire le problème',
            'context_help' => 'Le contexte de la page sera ajouté automatiquement : URL, titre, navigateur, langue, fuseau horaire, dimensions de la fenêtre et thème.',
            'title_label' => 'Titre',
            'title_placeholder' => 'Ex. : La fenêtre de partage reste vide',
            'description_label' => 'Description',
            'description_placeholder' => 'Expliquez ce que vous faisiez, ce qui s’est passé et ce que vous attendiez.',
            'attachments_label' => 'Fichiers ou captures d’écran',
            'attachments_help' => 'Les fichiers joints seront stockés sur le serveur et leur lien sera public dans le ticket GitHub. Formats acceptés : PNG, JPG, GIF, WEBP, PDF, TXT, LOG, ZIP.',
            'close' => 'Fermer',
            'send' => 'Envoyer vers GitHub',
            'endpoint_missing' => 'Le point d’envoi du signalement est introuvable.',
            'attachment_warning' => 'Attention : les fichiers joints seront visibles par toute personne consultant le ticket GitHub. Continuer ?',
            'sending' => 'Envoi vers GitHub…',
            'sent' => 'Signalement envoyé.',
            'send_failed' => 'Impossible d’envoyer le signalement pour le moment.',
            'view_issue' => 'Voir le ticket',
            'file' => 'Fichier',
            'browser_unknown' => 'Navigateur inconnu',
            'system_unknown' => 'Système inconnu',
            'request_method' => 'Méthode non autorisée.',
            'login_error' => 'Connexion requise.',
            'module_unavailable' => 'Le module de signalement n’est pas disponible sur ce serveur.',
            'patreon_error' => 'Le module de signalement est réservé aux comptes Patreon connectés.',
            'required_fields' => 'Le titre et la description sont obligatoires.',
            'user_missing' => 'Utilisateur introuvable.',
            'attachment_error' => 'Une pièce jointe n’a pas pu être téléversée correctement.',
            'temporary_file_invalid' => 'Le fichier temporaire de la pièce jointe est invalide.',
            'empty_attachment' => 'Une pièce jointe est vide.',
            'attachment_size' => 'Chaque pièce jointe doit faire moins de 15 Mo.',
            'attachment_type' => 'Type de fichier non autorisé pour cette pièce jointe.',
            'attachment_directory' => 'Impossible de créer le dossier de stockage des pièces jointes.',
            'attachment_store' => 'Impossible de stocker une pièce jointe sur le serveur.',
            'attachment_limit' => 'Vous pouvez joindre au maximum 5 fichiers par signalement.',
            'issue_sent' => 'Signalement envoyé sur GitHub.',
            'issue_create_failed' => 'Impossible de créer le ticket GitHub.',
            'curl_missing' => 'L’extension PHP cURL est manquante.',
            'token_missing' => 'La variable GITHUB_BUGREPORT_TOKEN est absente.',
            'owner_missing' => 'La variable GITHUB_BUGREPORT_REPO_OWNER est absente.',
            'repository_missing' => 'La variable GITHUB_BUGREPORT_REPO_NAME est absente.',
            'upload_root_missing' => 'Le répertoire racine du site est introuvable pour stocker la pièce jointe.',
            'issue_description_heading' => 'Description',
            'issue_context_heading' => 'Contexte technique',
            'issue_attachments_heading' => 'Fichiers joints publics',
            'issue_url' => 'Adresse de la page',
            'issue_page_title' => 'Titre de la page',
            'issue_application' => 'Application',
            'issue_theme' => 'Thème',
            'issue_user' => 'Utilisateur',
            'issue_user_id' => 'Identifiant utilisateur',
            'issue_username' => 'Nom d’utilisateur',
            'issue_organization' => 'Organisation',
            'issue_organization_id' => 'Identifiant de l’organisation',
            'issue_organization_shortname' => 'Nom court de l’organisation',
            'issue_browser' => 'Navigateur',
            'issue_os' => 'Système',
            'issue_user_agent' => 'Agent utilisateur',
            'issue_platform' => 'Plateforme',
            'issue_language' => 'Langue',
            'issue_languages' => 'Langues',
            'issue_timezone' => 'Fuseau horaire',
            'issue_viewport' => 'Dimensions de la fenêtre',
            'issue_screen' => 'Dimensions de l’écran',
            'issue_pixel_ratio' => 'Facteur d’échelle des pixels',
            'issue_referrer' => 'Page d’origine',
            'issue_client_timestamp' => 'Heure du navigateur',
            'issue_server_timestamp' => 'Heure du serveur',
        ];

        $sourceLang = [];
        foreach ($texts as $key => $text) {
            $sourceLang['bug_report.' . $key] = [
                'text' => $text,
                'context' => 'Text displayed by the GitHub bug report feature: ' . $key,
            ];
        }

        return $sourceLang;
    }
}

if (!function_exists('githubBugReportT')) {
    function githubBugReportT(string $key, array $variables = []): string
    {
        static $bundle = null;
        static $sourceLang = null;
        if ($sourceLang === null) {
            $sourceLang = githubBugReportGetSourceLang();
            $locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
            $bundle = loadTranslationBundle('bug_report', $locale, $sourceLang);
        }

        return t('bug_report.' . $key, $variables, $bundle, $sourceLang);
    }
}

if (!function_exists('githubBugReportGetToken')) {
    function githubBugReportGetToken()
    {
        return trim((string)($GLOBALS['githubBugReportToken'] ?? envValue('GITHUB_BUGREPORT_TOKEN', '')));
    }
}

if (!function_exists('githubBugReportGetRepositoryOwner')) {
    function githubBugReportGetRepositoryOwner()
    {
        $value = trim((string)($GLOBALS['githubBugReportRepoOwner'] ?? envValue('GITHUB_BUGREPORT_REPO_OWNER', 'OpenGovernance-community')));
        return $value !== '' ? $value : 'OpenGovernance-community';
    }
}

if (!function_exists('githubBugReportGetRepositoryName')) {
    function githubBugReportGetRepositoryName()
    {
        $value = trim((string)($GLOBALS['githubBugReportRepoName'] ?? envValue('GITHUB_BUGREPORT_REPO_NAME', 'OMO2')));
        return $value !== '' ? $value : 'OMO2';
    }
}

if (!function_exists('githubBugReportGetUserAgent')) {
    function githubBugReportGetUserAgent()
    {
        $value = trim((string)($GLOBALS['githubBugReportUserAgent'] ?? envValue('GITHUB_BUGREPORT_USER_AGENT', 'OMO Bug Reporter')));
        return $value !== '' ? $value : 'OMO Bug Reporter';
    }
}

if (!function_exists('githubBugReportGetDefaultLabels')) {
    function githubBugReportGetDefaultLabels()
    {
        $raw = trim((string)($GLOBALS['githubBugReportLabels'] ?? envValue('GITHUB_BUGREPORT_LABELS', '')));
        if ($raw === '') {
            return [];
        }

        $labels = [];
        foreach (preg_split('/[\r\n,;]+/', $raw) as $label) {
            $label = trim((string)$label);
            if ($label === '') {
                continue;
            }

            $labels[$label] = $label;
        }

        return array_values($labels);
    }
}

if (!function_exists('githubBugReportGetConfigurationIssues')) {
    function githubBugReportGetConfigurationIssues()
    {
        $issues = [];

        if (!function_exists('curl_init')) {
            $issues[] = githubBugReportT('curl_missing');
        }

        if (githubBugReportGetToken() === '') {
            $issues[] = githubBugReportT('token_missing');
        }

        if (githubBugReportGetRepositoryOwner() === '') {
            $issues[] = githubBugReportT('owner_missing');
        }

        if (githubBugReportGetRepositoryName() === '') {
            $issues[] = githubBugReportT('repository_missing');
        }

        return array_values(array_unique($issues));
    }
}

if (!function_exists('githubBugReportIsConfigured')) {
    function githubBugReportIsConfigured()
    {
        return githubBugReportGetConfigurationIssues() === [];
    }
}

if (!function_exists('githubBugReportUiIsEnabled')) {
    function githubBugReportUiIsEnabled()
    {
        return githubBugReportIsConfigured() && patreonSupportUiIsEnabled();
    }
}

if (!function_exists('githubBugReportGetDestinationSummary')) {
    function githubBugReportGetDestinationSummary()
    {
        $repoOwner = githubBugReportGetRepositoryOwner();
        $repoName = githubBugReportGetRepositoryName();
 
        return [
            'repo' => $repoOwner !== '' && $repoName !== '' ? $repoOwner . '/' . $repoName : '',
            'repoUrl' => $repoOwner !== '' && $repoName !== '' ? 'https://github.com/' . $repoOwner . '/' . $repoName : '',
        ];
    }
}

if (!function_exists('githubBugReportBuildApiHeaders')) {
    function githubBugReportBuildApiHeaders($includeJsonContentType = true)
    {
        $headers = [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . githubBugReportGetToken(),
            'User-Agent: ' . githubBugReportGetUserAgent(),
            'X-GitHub-Api-Version: 2022-11-28',
        ];

        if ($includeJsonContentType) {
            $headers[] = 'Content-Type: application/json';
        }

        return $headers;
    }
}

if (!function_exists('githubBugReportRequest')) {
    function githubBugReportRequest($method, $url, $payload = null)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La configuration GitHub est incomplete : extension PHP cURL manquante.');
        }

        $curl = curl_init((string)$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper((string)$method));
        curl_setopt($curl, CURLOPT_HTTPHEADER, githubBugReportBuildApiHeaders(true));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        if ($payload !== null) {
            $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encodedPayload === false) {
                throw new RuntimeException('Impossible de preparer la charge GitHub.');
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedPayload);
        }

        $body = curl_exec($curl);
        if ($body === false) {
            $error = curl_error($curl);
            throw new RuntimeException('Erreur reseau GitHub : ' . $error);
        }

        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        return [
            'status' => $status,
            'body' => (string)$body,
        ];
    }
}

if (!function_exists('githubBugReportDecodeResponse')) {
    function githubBugReportDecodeResponse(array $response)
    {
        $body = trim((string)($response['body'] ?? ''));
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('githubBugReportBuildErrorMessage')) {
    function githubBugReportBuildErrorMessage(array $response, array $payload, $fallback)
    {
        $message = trim((string)($payload['message'] ?? ''));
        $details = [];

        if (!empty($payload['errors']) && is_array($payload['errors'])) {
            foreach ($payload['errors'] as $error) {
                if (is_string($error) && trim($error) !== '') {
                    $details[] = trim($error);
                    continue;
                }

                if (!is_array($error)) {
                    continue;
                }

                if (!empty($error['message']) && is_string($error['message'])) {
                    $details[] = trim($error['message']);
                    continue;
                }

                if (!empty($error['code']) && is_string($error['code'])) {
                    $details[] = trim($error['code']);
                }
            }
        }

        $details = array_values(array_unique(array_filter($details)));

        if ($message !== '' && $details !== []) {
            return $fallback . ' ' . $message . ' (' . implode('; ', $details) . ').';
        }

        if ($message !== '') {
            return $fallback . ' ' . $message . '.';
        }

        if ($details !== []) {
            return $fallback . ' ' . implode('; ', $details) . '.';
        }

        return $fallback . ' HTTP ' . (int)($response['status'] ?? 0) . '.';
    }
}

if (!function_exists('githubBugReportCreateIssue')) {
    function githubBugReportCreateIssue($title, $body, array $options = [])
    {
        if (!githubBugReportIsConfigured()) {
            throw new RuntimeException('Configuration GitHub incomplete.');
        }

        $payload = [
            'title' => (string)$title,
            'body' => (string)$body,
        ];

        $labels = isset($options['labels']) && is_array($options['labels'])
            ? array_values(array_filter(array_map('strval', $options['labels'])))
            : githubBugReportGetDefaultLabels();
        if ($labels !== []) {
            $payload['labels'] = $labels;
        }

        $type = isset($options['type']) ? trim((string)$options['type']) : '';
        if ($type !== '') {
            $payload['type'] = $type;
        }

        $url = 'https://api.github.com/repos/'
            . rawurlencode(githubBugReportGetRepositoryOwner())
            . '/'
            . rawurlencode(githubBugReportGetRepositoryName())
            . '/issues';

        $response = githubBugReportRequest('POST', $url, $payload);
        $decoded = githubBugReportDecodeResponse($response);

        if ((int)$response['status'] !== 201) {
            throw new RuntimeException(
                githubBugReportBuildErrorMessage($response, $decoded, githubBugReportT('issue_create_failed'))
            );
        }

        return $decoded;
    }
}
