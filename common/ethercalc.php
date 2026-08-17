<?php

if (!function_exists('omoEthercalcNormalizeBaseUrl')) {
    function omoEthercalcNormalizeBaseUrl(string $baseUrl): string
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

if (!function_exists('omoEthercalcGetConfig')) {
    function omoEthercalcGetConfig(): array
    {
        return array(
            'baseUrl' => omoEthercalcNormalizeBaseUrl((string)($GLOBALS['ethercalcBaseUrl'] ?? '')),
            'internalBaseUrl' => omoEthercalcNormalizeBaseUrl((string)($GLOBALS['ethercalcInternalBaseUrl'] ?? '')),
            'key' => trim((string)($GLOBALS['ethercalcKey'] ?? '')),
        );
    }
}

if (!function_exists('omoEthercalcHasConfig')) {
    function omoEthercalcHasConfig(): bool
    {
        $config = omoEthercalcGetConfig();
        return $config['baseUrl'] !== '' && $config['key'] !== '';
    }
}

if (!function_exists('omoEthercalcIsValidRoomId')) {
    function omoEthercalcIsValidRoomId(string $roomId): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}$/', $roomId) === 1;
    }
}

if (!function_exists('omoEthercalcBuildEditToken')) {
    function omoEthercalcBuildEditToken(string $roomId): string
    {
        $config = omoEthercalcGetConfig();
        if (!omoEthercalcIsValidRoomId($roomId) || $config['key'] === '') {
            return '';
        }

        return hash_hmac('sha256', $roomId, $config['key']);
    }
}

if (!function_exists('omoEthercalcBuildSheetUrl')) {
    function omoEthercalcBuildSheetUrl(string $roomId, bool $canEdit, string $displayName = ''): string
    {
        $config = omoEthercalcGetConfig();
        if ($config['baseUrl'] === '' || !omoEthercalcIsValidRoomId($roomId)) {
            return '';
        }

        $parameters = array(
            'auth' => $canEdit ? omoEthercalcBuildEditToken($roomId) : '0',
        );
        if ($canEdit && $parameters['auth'] === '') {
            return '';
        }
        if (!$canEdit) {
            $parameters['view'] = '1';
        }

        $displayName = trim($displayName);
        if ($displayName !== '') {
            $parameters['user'] = mb_substr($displayName, 0, 100, 'UTF-8');
        }

        return $config['baseUrl'] . '/' . rawurlencode($roomId)
            . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('omoEthercalcRequest')) {
    function omoEthercalcRequest(string $method, string $path, array $parameters = array(), ?string $body = null): array
    {
        $config = omoEthercalcGetConfig();
        $baseUrl = $config['internalBaseUrl'] !== '' ? $config['internalBaseUrl'] : $config['baseUrl'];
        if ($baseUrl === '' || $config['key'] === '') {
            return array('status' => false, 'text' => 'EtherCalc n est pas configure.');
        }

        if (!function_exists('curl_init')) {
            return array('status' => false, 'text' => 'cURL est requis pour communiquer avec EtherCalc.');
        }

        $method = strtoupper(trim($method));
        if (!in_array($method, array('GET', 'POST', 'PUT', 'DELETE'), true) || !preg_match('#^/[A-Za-z0-9_./-]*$#', $path)) {
            return array('status' => false, 'text' => 'Requete EtherCalc invalide.');
        }

        $url = $baseUrl . $path;
        if (count($parameters) > 0) {
            $url .= '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json; charset=utf-8',
            ));
        }

        $response = curl_exec($curl);
        $curlError = trim((string)curl_error($curl));
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($response === false) {
            return array('status' => false, 'text' => $curlError !== '' ? $curlError : 'La requete EtherCalc a echoue.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return array(
                'status' => false,
                'httpCode' => $httpCode,
                'text' => 'EtherCalc a renvoye une erreur HTTP ' . $httpCode . '.',
            );
        }

        return array(
            'status' => true,
            'httpCode' => $httpCode,
            'body' => (string)$response,
        );
    }
}

if (!function_exists('omoEthercalcCreateDocumentSheet')) {
    function omoEthercalcCreateDocumentSheet(int $organizationId): array
    {
        if (!omoEthercalcHasConfig()) {
            return array('status' => false, 'text' => 'EtherCalc n est pas configure.');
        }

        try {
            $roomId = 'omo-' . (int)$organizationId . '-' . bin2hex(random_bytes(12));
        } catch (\Throwable $exception) {
            return array('status' => false, 'text' => 'Impossible de generer l identifiant EtherCalc.');
        }

        $result = omoEthercalcRequest('POST', '/_', array(), json_encode(array(
            'room' => $roomId,
            'snapshot' => '',
        ), JSON_UNESCAPED_SLASHES));
        if (!($result['status'] ?? false)) {
            return $result;
        }

        return array('status' => true, 'roomId' => $roomId);
    }
}

if (!function_exists('omoEthercalcDeleteDocumentSheet')) {
    function omoEthercalcDeleteDocumentSheet(string $roomId): array
    {
        if (!omoEthercalcIsValidRoomId($roomId)) {
            return array('status' => false, 'text' => 'Identifiant EtherCalc invalide.');
        }

        $token = omoEthercalcBuildEditToken($roomId);
        if ($token === '') {
            return array('status' => false, 'text' => 'EtherCalc n est pas configure.');
        }

        $result = omoEthercalcRequest('DELETE', '/_/' . rawurlencode($roomId), array('auth' => $token));
        if (($result['status'] ?? false) === true || (int)($result['httpCode'] ?? 0) === 404) {
            return array('status' => true);
        }

        return $result;
    }
}
