<?php

if (!function_exists('omoSpacedeckBase64UrlEncode')) {
    function omoSpacedeckBase64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('omoSpacedeckBase64UrlDecode')) {
    function omoSpacedeckBase64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}

if (!function_exists('omoSpacedeckGetExternalAccessSecret')) {
    function omoSpacedeckGetExternalAccessSecret(): string
    {
        return trim((string)envValue('SPACEDOCK_EXTERNAL_ACCESS_SECRET', ''));
    }
}

if (!function_exists('omoSpacedeckNormalizeBaseUrl')) {
    function omoSpacedeckNormalizeBaseUrl(string $baseUrl): string
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

if (!function_exists('omoSpacedeckGetConfig')) {
    function omoSpacedeckGetConfig(): array
    {
        $publicUrl = omoSpacedeckNormalizeBaseUrl((string)($GLOBALS['spaceDeckBaseUrl'] ?? envValue('SPACEDECK_URL', '')));
        $internalUrl = omoSpacedeckNormalizeBaseUrl((string)($GLOBALS['spaceDeckInternalUrl'] ?? envValue('SPACEDECK_INTERNAL_URL', '')));
        if ($internalUrl === '') {
            $internalUrl = $publicUrl;
        }

        return array(
            'baseUrl' => $publicUrl,
            'internalUrl' => $internalUrl,
            'provisioningToken' => trim((string)($GLOBALS['spaceDeckProvisioningToken'] ?? envValue('SPACEDECK_PROVISIONING_TOKEN', ''))),
        );
    }
}

if (!function_exists('omoSpacedeckHasConfig')) {
    function omoSpacedeckHasConfig(): bool
    {
        $config = omoSpacedeckGetConfig();
        return $config['baseUrl'] !== ''
            && $config['internalUrl'] !== ''
            && $config['provisioningToken'] !== ''
            && omoSpacedeckGetExternalAccessSecret() !== '';
    }
}

if (!function_exists('omoSpacedeckApiRequest')) {
    function omoSpacedeckApiRequest(string $method, string $path, ?array $payload = null): array
    {
        $config = omoSpacedeckGetConfig();
        if ($config['internalUrl'] === '' || $config['provisioningToken'] === '') {
            return array('status' => false, 'text' => 'SpaceDeck n est pas configure.');
        }

        if (!function_exists('curl_init')) {
            return array('status' => false, 'text' => 'cURL est requis pour communiquer avec SpaceDeck.');
        }

        $url = $config['internalUrl'] . '/' . ltrim($path, '/');
        $curl = curl_init($url);
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Spacedeck-Provisioning-Token: ' . $config['provisioningToken'],
        );
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        );
        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }
        curl_setopt_array($curl, $options);
        $responseBody = curl_exec($curl);
        $error = trim((string)curl_error($curl));
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;

        if ($responseBody === false || $httpCode < 200 || $httpCode >= 300) {
            return array(
                'status' => false,
                'text' => $error !== '' ? $error : 'La requete SpaceDeck a echoue.',
                'httpCode' => $httpCode,
            );
        }

        return array(
            'status' => true,
            'data' => is_array($decoded) ? $decoded : array(),
            'httpCode' => $httpCode,
        );
    }
}

if (!function_exists('omoSpacedeckProvisionSpace')) {
    function omoSpacedeckProvisionSpace(string $name): array
    {
        return omoSpacedeckApiRequest('POST', '/api/external/spaces', array(
            'name' => trim($name) !== '' ? trim($name) : 'Tableau blanc collaboratif',
        ));
    }
}

if (!function_exists('omoSpacedeckDeleteSpace')) {
    function omoSpacedeckDeleteSpace(string $spaceId): array
    {
        $spaceId = trim($spaceId);
        if ($spaceId === '') {
            return array('status' => true);
        }

        return omoSpacedeckApiRequest('DELETE', '/api/external/spaces/' . rawurlencode($spaceId));
    }
}

if (!function_exists('omoSpacedeckBuildDocumentOpenUrl')) {
    function omoSpacedeckBuildDocumentOpenUrl(\dbObject\Document $document, int $userId): string
    {
        $spaceId = trim((string)$document->get('spacedeckspaceid'));
        $organizationId = (int)$document->get('IDorganization');
        if ($spaceId === '' || $organizationId <= 0 || $userId <= 0) {
            return '';
        }

        $config = omoSpacedeckGetConfig();
        if ($config['baseUrl'] === '') {
            return '';
        }

        $user = new \dbObject\User();
        $displayName = $user->load($userId)
            ? trim((string)$user->getScopedDisplayName($organizationId))
            : '';
        if ($displayName === '') {
            $displayName = 'Utilisateur ' . $userId;
        }

        $accessLevel = $document->canEditInOrganizationContext($organizationId, $userId, false) ? 2 : 1;
        $token = omoSpacedeckBuildExternalAccessToken(
            $spaceId,
            $userId,
            $displayName,
            $accessLevel,
            0,
            omoSpacedeckGetCurrentLanguage()
        );
        if ($token === '') {
            return '';
        }

        return $config['baseUrl'] . '/spaces/' . rawurlencode($spaceId)
            . '?externalToken=' . rawurlencode($token)
            . '&embedded=1';
    }
}

if (!function_exists('omoSpacedeckNormalizeAccessLevel')) {
    function omoSpacedeckNormalizeAccessLevel(int $accessLevel): int
    {
        return max(0, min(2, $accessLevel));
    }
}

if (!function_exists('omoSpacedeckNormalizeLanguage')) {
    function omoSpacedeckNormalizeLanguage(string $language): string
    {
        $language = strtolower(trim($language));
        $language = str_replace('_', '-', $language);
        $language = explode('-', $language, 2)[0];
        $supportedLanguages = array('en', 'de', 'fr', 'oc', 'es', 'hu', 'cs');

        return in_array($language, $supportedLanguages, true) ? $language : 'en';
    }
}

if (!function_exists('omoSpacedeckGetCurrentLanguage')) {
    function omoSpacedeckGetCurrentLanguage(): string
    {
        if (function_exists('omoGetTranslationLocale')) {
            return omoSpacedeckNormalizeLanguage((string)omoGetTranslationLocale());
        }

        if (function_exists('commonAuthGetTranslationLocale')) {
            return omoSpacedeckNormalizeLanguage((string)commonAuthGetTranslationLocale());
        }

        return 'en';
    }
}

if (!function_exists('omoSpacedeckBuildExternalAccessToken')) {
    function omoSpacedeckBuildExternalAccessToken(
        string $spaceId,
        int $userId,
        string $displayName,
        int $accessLevel,
        int $validUntil = 0,
        string $language = ''
    ): string {
        $secret = omoSpacedeckGetExternalAccessSecret();
        $spaceId = trim($spaceId);
        $displayName = trim($displayName);
        $validUntil = $validUntil > 0 ? $validUntil : time() + 900;

        if ($secret === '' || $spaceId === '' || $userId <= 0 || $displayName === '' || $validUntil <= time()) {
            return '';
        }

        $payload = array(
            'spaceId' => $spaceId,
            'userId' => $userId,
            'name' => mb_substr($displayName, 0, 120, 'UTF-8'),
            'language' => omoSpacedeckNormalizeLanguage($language),
            'accessLevel' => omoSpacedeckNormalizeAccessLevel($accessLevel),
            'expiresAt' => $validUntil,
            'nonce' => bin2hex(random_bytes(12)),
        );
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($payloadJson)) {
            return '';
        }

        $encodedPayload = omoSpacedeckBase64UrlEncode($payloadJson);
        $signature = omoSpacedeckBase64UrlEncode(hash_hmac('sha256', $encodedPayload, $secret, true));
        return $encodedPayload . '.' . $signature;
    }
}

if (!function_exists('omoSpacedeckVerifyExternalAccessToken')) {
    function omoSpacedeckVerifyExternalAccessToken(string $token): array|false
    {
        $parts = explode('.', trim($token), 2);
        $secret = omoSpacedeckGetExternalAccessSecret();
        if (count($parts) !== 2 || $secret === '') {
            return false;
        }

        $expectedSignature = omoSpacedeckBase64UrlEncode(hash_hmac('sha256', $parts[0], $secret, true));
        if (!hash_equals($expectedSignature, $parts[1])) {
            return false;
        }

        $payloadJson = omoSpacedeckBase64UrlDecode($parts[0]);
        if (!is_string($payloadJson)) {
            return false;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return false;
        }

        $spaceId = trim((string)($payload['spaceId'] ?? ''));
        $userId = (int)($payload['userId'] ?? 0);
        $displayName = trim((string)($payload['name'] ?? ''));
        $expiresAt = (int)($payload['expiresAt'] ?? 0);
        if ($spaceId === '' || $userId <= 0 || $displayName === '' || $expiresAt < time()) {
            return false;
        }

        $payload['spaceId'] = $spaceId;
        $payload['userId'] = $userId;
        $payload['name'] = mb_substr($displayName, 0, 120, 'UTF-8');
        $payload['language'] = omoSpacedeckNormalizeLanguage((string)($payload['language'] ?? ''));
        $payload['accessLevel'] = omoSpacedeckNormalizeAccessLevel((int)($payload['accessLevel'] ?? 0));
        return $payload;
    }
}

if (!function_exists('omoSpacedeckBuildExternalAccessResponse')) {
    function omoSpacedeckBuildExternalAccessResponse(array $server): array
    {
        $pathInfo = trim((string)($server['PATH_INFO'] ?? ''), '/');
        if ($pathInfo === '') {
            $requestPath = (string)(parse_url((string)($server['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
            $scriptName = (string)($server['SCRIPT_NAME'] ?? '');
            if ($scriptName !== '' && str_starts_with($requestPath, $scriptName . '/')) {
                $pathInfo = trim(substr($requestPath, strlen($scriptName)), '/');
            }
        }

        $parts = explode('/', $pathInfo, 2);
        $token = trim((string)($parts[0] ?? ''));
        $requestedMethod = strtoupper(trim((string)($parts[1] ?? '')));
        $spaceId = trim((string)($server['HTTP_X_SPACEDECK_SPACE_ID'] ?? ''));
        $headerMethod = strtoupper(trim((string)($server['HTTP_X_SPACEDECK_REQUEST_METHOD'] ?? '')));
        $payload = omoSpacedeckVerifyExternalAccessToken($token);

        $accessLevel = 0;
        if (
            is_array($payload)
            && $spaceId !== ''
            && hash_equals((string)$payload['spaceId'], $spaceId)
            && in_array($requestedMethod, array('GET', 'POST', 'PUT', 'DELETE'), true)
            && hash_equals($requestedMethod, $headerMethod)
        ) {
            $accessLevel = (int)$payload['accessLevel'];
        }

        $response = array('access_level' => $accessLevel);
        if ($accessLevel > 0 && is_array($payload)) {
            $response['user'] = array(
                'id' => 'omo-user-' . (int)$payload['userId'],
                'name' => (string)$payload['name'],
                'language' => (string)$payload['language'],
            );
        }

        return $response;
    }
}
