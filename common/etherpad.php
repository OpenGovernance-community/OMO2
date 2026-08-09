<?php

if (!function_exists('omoEtherpadNormalizeBaseUrl')) {
    function omoEtherpadNormalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '' || preg_match('/[\x00-\x1F\x7F]/', $baseUrl)) {
            return '';
        }

        $parsedUrl = parse_url($baseUrl);
        if (!is_array($parsedUrl) || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
            return '';
        }

        $scheme = strtolower((string)$parsedUrl['scheme']);
        if (!in_array($scheme, array('http', 'https'), true)) {
            return '';
        }

        if (isset($parsedUrl['user']) || isset($parsedUrl['pass']) || isset($parsedUrl['query']) || isset($parsedUrl['fragment'])) {
            return '';
        }

        $host = strtolower(trim((string)$parsedUrl['host']));
        $renderedHost = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $host . ']' : $host;
        $normalizedUrl = $scheme . '://' . $renderedHost;
        $port = (int)($parsedUrl['port'] ?? 0);
        if ($port > 0 && !(($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80))) {
            $normalizedUrl .= ':' . $port;
        }

        $path = rtrim((string)($parsedUrl['path'] ?? ''), '/');
        if ($path !== '' && $path !== '/') {
            $normalizedUrl .= $path[0] === '/' ? $path : '/' . $path;
        }

        return filter_var($normalizedUrl, FILTER_VALIDATE_URL) !== false ? $normalizedUrl : '';
    }
}

if (!function_exists('omoEtherpadResolveConfig')) {
    function omoEtherpadResolveConfig(array $storedConfig = array()): array
    {
        $globalBaseUrl = omoEtherpadNormalizeBaseUrl((string)($GLOBALS['etherpadBaseUrl'] ?? ''));
        $globalApiKey = trim((string)($GLOBALS['etherpadApiKey'] ?? ''));
        $storedBaseUrl = omoEtherpadNormalizeBaseUrl((string)($storedConfig['baseUrl'] ?? ''));
        $storedApiKey = trim((string)($storedConfig['apiKey'] ?? ''));
        $baseUrl = $storedBaseUrl !== '' ? $storedBaseUrl : $globalBaseUrl;

        // Never send the global secret to a different organization-defined host.
        $usesDifferentServer = $storedBaseUrl !== ''
            && ($globalBaseUrl === '' || !hash_equals($globalBaseUrl, $storedBaseUrl));
        $apiKey = $storedApiKey !== ''
            ? $storedApiKey
            : ($usesDifferentServer ? '' : $globalApiKey);

        $apiVersion = trim((string)($storedConfig['apiVersion'] ?? ($GLOBALS['etherpadApiVersion'] ?? '1')));
        if (!preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $apiVersion)) {
            $apiVersion = '1';
        }

        return array(
            'baseUrl' => $baseUrl,
            'apiKey' => $apiKey,
            'apiVersion' => $apiVersion,
            'baseUrlOverride' => $storedBaseUrl,
            'hasBaseUrlOverride' => $storedBaseUrl !== '',
            'hasApiKeyOverride' => $storedApiKey !== '',
        );
    }
}

if (!function_exists('omoEtherpadGetConfig')) {
    function omoEtherpadGetConfig(?\dbObject\Organization $organization = null): array
    {
        $parameters = array();
        if ($organization instanceof \dbObject\Organization && (int)$organization->getId() > 0) {
            $parameters = $organization->getApplicationParametersByDirectory('documents');
        }

        $storedConfig = isset($parameters['etherpad']) && is_array($parameters['etherpad'])
            ? $parameters['etherpad']
            : array();
        return omoEtherpadResolveConfig($storedConfig);
    }
}

if (!function_exists('omoEtherpadHasConfig')) {
    function omoEtherpadHasConfig(?\dbObject\Organization $organization = null): bool
    {
        $config = omoEtherpadGetConfig($organization);
        return $config['baseUrl'] !== '' && $config['apiKey'] !== '';
    }
}

if (!function_exists('omoEtherpadApiRequest')) {
    function omoEtherpadApiRequest(?\dbObject\Organization $organization, string $function, array $parameters = array()): array
    {
        $config = omoEtherpadGetConfig($organization);
        if ($config['baseUrl'] === '' || $config['apiKey'] === '') {
            return array('status' => false, 'text' => 'Etherpad n est pas configure pour cette organisation.');
        }

        if (!function_exists('curl_init')) {
            return array('status' => false, 'text' => 'cURL est requis pour communiquer avec Etherpad.');
        }

        $function = trim($function);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $function)) {
            return array('status' => false, 'text' => 'Fonction Etherpad invalide.');
        }

        $url = $config['baseUrl'] . '/api/' . rawurlencode($config['apiVersion']) . '/' . $function;
        $parameters['apikey'] = $config['apiKey'];
        // Etherpad's documented HTTP API uses query parameters. Some supported
        // releases reject POST requests entirely, so keep this interoperable.
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $url .= '?' . $query;
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        $apiHost = strtolower(trim((string)parse_url($config['baseUrl'], PHP_URL_HOST)));
        $localDevelopmentCertificate = '/etc/apache2/ssl/dev-localhost.crt';
        if (
            $apiHost !== ''
            && (hash_equals($apiHost, 'localtest.me') || str_ends_with($apiHost, '.localtest.me'))
            && is_file($localDevelopmentCertificate)
        ) {
            curl_setopt($curl, CURLOPT_CAINFO, $localDevelopmentCertificate);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
        ));

        $response = curl_exec($curl);
        $curlError = trim((string)curl_error($curl));
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($response === false) {
            return array('status' => false, 'text' => $curlError !== '' ? $curlError : 'La requete Etherpad a echoue.');
        }

        $payload = json_decode((string)$response, true);
        if (!is_array($payload)) {
            return array('status' => false, 'text' => 'Etherpad a renvoye une reponse invalide.', 'httpCode' => $httpCode);
        }

        $code = (int)($payload['code'] ?? -1);
        if ($httpCode < 200 || $httpCode >= 300 || $code !== 0) {
            $message = trim((string)($payload['message'] ?? 'La requete Etherpad a echoue.'));
            return array(
                'status' => false,
                'code' => $code,
                'httpCode' => $httpCode,
                'message' => $message,
                'text' => 'Etherpad: ' . $message,
                'data' => $payload['data'] ?? null,
            );
        }

        return array(
            'status' => true,
            'code' => $code,
            'httpCode' => $httpCode,
            'data' => $payload['data'] ?? null,
            'message' => trim((string)($payload['message'] ?? 'ok')),
        );
    }
}

if (!function_exists('omoEtherpadCreateDocumentPad')) {
    function omoEtherpadCreateDocumentPad(\dbObject\Organization $organization, int $organizationId, int $userId, string $displayName, string $initialText = ''): array
    {
        try {
            $padName = 'omo-' . (int)$organizationId . '-' . bin2hex(random_bytes(10));
        } catch (\Throwable $exception) {
            return array('status' => false, 'text' => 'Impossible de generer le nom du pad Etherpad.');
        }

        $authorResult = omoEtherpadApiRequest($organization, 'createAuthorIfNotExistsFor', array(
            'authorMapper' => 'omo-organization-' . (int)$organizationId . '-user-' . (int)$userId,
            'name' => trim($displayName) !== '' ? trim($displayName) : ('Utilisateur ' . (int)$userId),
        ));
        if (!($authorResult['status'] ?? false)) {
            return $authorResult;
        }

        $authorId = trim((string)($authorResult['data']['authorID'] ?? ''));
        if ($authorId === '') {
            return array('status' => false, 'text' => 'Etherpad n a pas renvoye d identifiant auteur.');
        }

        $groupResult = omoEtherpadApiRequest($organization, 'createGroupIfNotExistsFor', array(
            'groupMapper' => 'omo-document-' . (int)$organizationId . '-' . $padName,
        ));
        if (!($groupResult['status'] ?? false)) {
            return $groupResult;
        }

        $groupId = trim((string)($groupResult['data']['groupID'] ?? ''));
        if ($groupId === '') {
            return array('status' => false, 'text' => 'Etherpad n a pas renvoye d identifiant de groupe.');
        }

        $padResult = omoEtherpadApiRequest($organization, 'createGroupPad', array(
            'groupID' => $groupId,
            'padName' => $padName,
            'text' => $initialText,
            'authorId' => $authorId,
        ));
        if (!($padResult['status'] ?? false)) {
            omoEtherpadApiRequest($organization, 'deleteGroup', array('groupID' => $groupId));
            return $padResult;
        }

        $padId = trim((string)($padResult['data']['padID'] ?? ($groupId . '$' . $padName)));
        return $padId !== ''
            ? array('status' => true, 'padId' => $padId, 'authorId' => $authorId, 'groupId' => $groupId)
            : array('status' => false, 'text' => 'Etherpad n a pas renvoye d identifiant de pad.');
    }
}

if (!function_exists('omoEtherpadDeleteDocumentPad')) {
    function omoEtherpadDeleteDocumentPad(\dbObject\Organization $organization, string $padId): array
    {
        $padId = trim($padId);
        if ($padId === '') {
            return array('status' => true);
        }

        $groupId = trim((string)strtok($padId, '$'));
        $result = omoEtherpadApiRequest($organization, 'deletePad', array('padID' => $padId));
        $message = strtolower(trim((string)($result['message'] ?? '')));
        $alreadyDeleted = !($result['status'] ?? false)
            && (str_contains($message, 'does not exist') || str_contains($message, "doesn't exist"));
        if (!($result['status'] ?? false) && !$alreadyDeleted) {
            return $result;
        }

        // Each OMO document owns its Etherpad group. Group cleanup is best effort:
        // the pad deletion is the operation that must govern local deletion.
        if ($groupId !== '') {
            omoEtherpadApiRequest($organization, 'deleteGroup', array('groupID' => $groupId));
        }

        return array('status' => true, 'alreadyDeleted' => $alreadyDeleted);
    }
}

if (!function_exists('omoEtherpadCreateSession')) {
    function omoEtherpadCreateSession(\dbObject\Organization $organization, string $groupId, string $authorId, int $validUntil = 0): array
    {
        $groupId = trim($groupId);
        $authorId = trim($authorId);
        $validUntil = $validUntil > 0 ? $validUntil : (time() + 3600);
        if ($groupId === '' || $authorId === '') {
            return array('status' => false, 'text' => 'Identifiants Etherpad incomplets pour la session.');
        }

        $result = omoEtherpadApiRequest($organization, 'createSession', array(
            'groupID' => $groupId,
            'authorID' => $authorId,
            'validUntil' => $validUntil,
        ));
        if (!($result['status'] ?? false)) {
            return $result;
        }

        $sessionId = trim((string)($result['data']['sessionID'] ?? ''));
        return $sessionId !== ''
            ? array('status' => true, 'sessionId' => $sessionId, 'validUntil' => $validUntil)
            : array('status' => false, 'text' => 'Etherpad n a pas renvoye d identifiant de session.');
    }
}

if (!function_exists('omoEtherpadGetOrCreateSession')) {
    function omoEtherpadGetOrCreateSession(\dbObject\Organization $organization, string $groupId, string $authorId, int $validUntil = 0): array
    {
        $groupId = trim($groupId);
        $authorId = trim($authorId);
        $validUntil = $validUntil > 0 ? $validUntil : time() + 3600;
        if ($groupId === '' || $authorId === '') {
            return array('status' => false, 'text' => 'Identifiants Etherpad incomplets pour la session.');
        }

        $sessionsResult = omoEtherpadApiRequest($organization, 'listSessionsOfGroup', array('groupID' => $groupId));
        if (($sessionsResult['status'] ?? false) && is_array($sessionsResult['data'] ?? null)) {
            foreach ($sessionsResult['data'] as $sessionId => $sessionData) {
                if (
                    is_array($sessionData)
                    && trim((string)($sessionData['authorID'] ?? '')) === $authorId
                    && (int)($sessionData['validUntil'] ?? 0) > time() + 60
                ) {
                    return array(
                        'status' => true,
                        'sessionId' => trim((string)$sessionId),
                        'validUntil' => (int)$sessionData['validUntil'],
                        'reused' => true,
                    );
                }
            }
        }

        return omoEtherpadCreateSession($organization, $groupId, $authorId, $validUntil);
    }
}

if (!function_exists('omoEtherpadBuildSessionCookieValue')) {
    function omoEtherpadBuildSessionCookieValue(string $sessionId, string $existingValue = ''): string
    {
        $sessionIds = array();
        foreach (array_merge(explode(',', $existingValue), array($sessionId)) as $candidate) {
            $candidate = trim((string)$candidate);
            if (!preg_match('/^s\.[A-Za-z0-9_-]{1,128}$/', $candidate)) {
                continue;
            }

            $sessionIds[$candidate] = $candidate;
        }

        return implode(',', array_slice(array_values($sessionIds), -20));
    }
}

if (!function_exists('omoEtherpadResolveCookieDomain')) {
    function omoEtherpadResolveCookieDomain(?\dbObject\Organization $organization = null): ?string
    {
        $config = omoEtherpadGetConfig($organization);
        $remoteHost = strtolower(trim((string)parse_url($config['baseUrl'], PHP_URL_HOST)));
        $currentHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
        $currentHost = trim((string)parse_url('http://' . $currentHost, PHP_URL_HOST));
        if ($remoteHost === '' || $currentHost === '' || filter_var($remoteHost, FILTER_VALIDATE_IP) || filter_var($currentHost, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (hash_equals($currentHost, $remoteHost)) {
            return '';
        }

        $configuredDomain = strtolower(trim((string)($GLOBALS['etherpadCookieDomain'] ?? '')));
        if ($configuredDomain === '') {
            return null;
        }

        $configuredDomain = ltrim($configuredDomain, '.');
        $hostMatchesDomain = static function (string $host) use ($configuredDomain): bool {
            return hash_equals($host, $configuredDomain) || str_ends_with($host, '.' . $configuredDomain);
        };

        return $hostMatchesDomain($currentHost) && $hostMatchesDomain($remoteHost)
            ? '.' . $configuredDomain
            : null;
    }
}

if (!function_exists('omoEtherpadCanUseEditingSessions')) {
    function omoEtherpadCanUseEditingSessions(?\dbObject\Organization $organization = null): bool
    {
        return omoEtherpadHasConfig($organization) && omoEtherpadResolveCookieDomain($organization) !== null;
    }
}

if (!function_exists('omoEtherpadBuildPadUrl')) {
    function omoEtherpadBuildPadUrl(?\dbObject\Organization $organization, string $padId): string
    {
        $config = omoEtherpadGetConfig($organization);
        $padId = trim($padId);
        if ($config['baseUrl'] === '' || $padId === '') {
            return '';
        }

        // Etherpad pad identifiers use a '$' separator between group and pad.
        $encodedPadId = str_replace('%24', '$', rawurlencode($padId));
        return $config['baseUrl'] . '/p/' . $encodedPadId;
    }
}

if (!function_exists('omoEtherpadGetOrigin')) {
    function omoEtherpadGetOrigin(?\dbObject\Organization $organization = null): string
    {
        $baseUrl = omoEtherpadGetConfig($organization)['baseUrl'] ?? '';
        $parsedUrl = parse_url((string)$baseUrl);
        if (!is_array($parsedUrl) || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
            return '';
        }

        $scheme = strtolower((string)$parsedUrl['scheme']);
        $host = strtolower((string)$parsedUrl['host']);
        if (!in_array($scheme, array('http', 'https'), true) || $host === '') {
            return '';
        }

        $origin = $scheme . '://' . (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $host . ']' : $host);
        $port = (int)($parsedUrl['port'] ?? 0);
        if ($port > 0 && !(($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80))) {
            $origin .= ':' . $port;
        }

        return $origin;
    }
}
