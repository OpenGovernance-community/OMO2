<?php

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/patreon.php';

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
            $issues[] = 'extension PHP cURL manquante';
        }

        if (githubBugReportGetToken() === '') {
            $issues[] = 'GITHUB_BUGREPORT_TOKEN manquant';
        }

        if (githubBugReportGetRepositoryOwner() === '') {
            $issues[] = 'GITHUB_BUGREPORT_REPO_OWNER manquant';
        }

        if (githubBugReportGetRepositoryName() === '') {
            $issues[] = 'GITHUB_BUGREPORT_REPO_NAME manquant';
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
                githubBugReportBuildErrorMessage($response, $decoded, 'Impossible de creer l issue GitHub.')
            );
        }

        return $decoded;
    }
}
