<?php

if (!function_exists('omoCollaboraNormalizeBaseUrl')) {
    function omoCollaboraNormalizeBaseUrl(string $baseUrl): string
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

if (!function_exists('omoCollaboraNormalizeConfig')) {
    function omoCollaboraNormalizeConfig(array $config): array
    {
        return array(
            'baseUrl' => omoCollaboraNormalizeBaseUrl((string)($config['baseUrl'] ?? '')),
            'internalUrl' => omoCollaboraNormalizeBaseUrl((string)($config['internalUrl'] ?? '')),
        );
    }
}

if (!function_exists('omoCollaboraEditableExtensions')) {
    function omoCollaboraEditableExtensions(): array
    {
        return array(
            'odt', 'fodt', 'ott', 'doc', 'docx', 'docm', 'dotx', 'dotm', 'rtf', 'txt',
            'ods', 'fods', 'ots', 'xls', 'xlsx', 'xlsb', 'xlsm', 'xltx', 'xltm', 'csv', 'tsv',
            'odp', 'fodp', 'otp', 'ppt', 'pptx', 'pptm', 'potx', 'potm', 'ppsx',
            'odg', 'fodg', 'otg', 'svg', 'vsd', 'vsdx', 'vss', 'pub', 'dxf', 'emf', 'wmf',
        );
    }
}

if (!function_exists('omoCollaboraSupportsFilename')) {
    function omoCollaboraSupportsFilename(string $filename): bool
    {
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        return $extension !== '' && in_array($extension, omoCollaboraEditableExtensions(), true);
    }
}

if (!function_exists('omoCollaboraGetConfig')) {
    function omoCollaboraGetConfig(?\dbObject\Organization $organization = null): array
    {
        $parameters = array();
        if ($organization instanceof \dbObject\Organization && (int)$organization->getId() > 0) {
            $parameters = $organization->getApplicationParametersByDirectory('documents');
        }

        $storedConfig = isset($parameters['collabora']) && is_array($parameters['collabora'])
            ? $parameters['collabora']
            : array();
        $config = omoCollaboraNormalizeConfig($storedConfig);
        if ($config['internalUrl'] === '') {
            $config['internalUrl'] = $config['baseUrl'];
        }

        return $config;
    }
}

if (!function_exists('omoCollaboraHasConfig')) {
    function omoCollaboraHasConfig(?\dbObject\Organization $organization = null): bool
    {
        $config = omoCollaboraGetConfig($organization);
        return $config['baseUrl'] !== '';
    }
}

if (!function_exists('omoCollaboraNormalizeBrandColor')) {
    function omoCollaboraNormalizeBrandColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color, $matches)) {
            $hex = strtolower($matches[1]);
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            return '#' . $hex;
        }

        return '#2563eb';
    }
}

if (!function_exists('omoCollaboraMixHexColors')) {
    function omoCollaboraMixHexColors(string $firstColor, string $secondColor, float $secondRatio): string
    {
        $firstColor = omoCollaboraNormalizeBrandColor($firstColor);
        $secondColor = omoCollaboraNormalizeBrandColor($secondColor);
        $secondRatio = max(0.0, min(1.0, $secondRatio));
        $firstRatio = 1.0 - $secondRatio;
        $firstHex = substr($firstColor, 1);
        $secondHex = substr($secondColor, 1);
        $result = '#';

        for ($offset = 0; $offset < 6; $offset += 2) {
            $first = hexdec(substr($firstHex, $offset, 2));
            $second = hexdec(substr($secondHex, $offset, 2));
            $result .= str_pad(dechex((int)round($first * $firstRatio + $second * $secondRatio)), 2, '0', STR_PAD_LEFT);
        }

        return $result;
    }
}

if (!function_exists('omoCollaboraBuildOrganizationCssVariables')) {
    function omoCollaboraBuildOrganizationCssVariables(\dbObject\Organization $organization): string
    {
        $brandColor = omoCollaboraNormalizeBrandColor((string)$organization->get('color'));
        $brandLightColor = omoCollaboraMixHexColors($brandColor, '#ffffff', 0.32);
        $brandHex = substr($brandColor, 1);
        $red = hexdec(substr($brandHex, 0, 2));
        $green = hexdec(substr($brandHex, 2, 2));
        $blue = hexdec(substr($brandHex, 4, 2));
        $luminance = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;
        $primaryTextColor = $luminance > 0.58 ? '#111827' : '#ffffff';
        $important = ' !important';

        return implode(';', array(
            '--co-primary-element=' . $brandColor . $important,
            '--co-primary-elment-rgb=' . $red . ',' . $green . ',' . $blue . $important,
            '--co-primary-element-light=' . $brandLightColor . $important,
            '--co-primary-light=' . $brandLightColor . $important,
            '--co-text-accent=' . $brandColor . $important,
            '--co-primary-text=' . $primaryTextColor . $important,
            '--color-primary=' . $brandColor . $important,
            '--color-primary-dark=' . omoCollaboraMixHexColors($brandColor, '#000000', 0.24) . $important,
            '--color-primary-darker=' . omoCollaboraMixHexColors($brandColor, '#000000', 0.46) . $important,
            '--color-primary-lighter=' . omoCollaboraMixHexColors($brandColor, '#ffffff', 0.52) . $important,
            '--color-primary-text=' . $primaryTextColor . $important,
            '--doc-type=' . $red . ',' . $green . ',' . $blue . $important,
        ));
    }
}

if (!function_exists('omoCollaboraBase64UrlEncode')) {
    function omoCollaboraBase64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('omoCollaboraBase64UrlDecode')) {
    function omoCollaboraBase64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}

if (!function_exists('omoCollaboraBuildWopiToken')) {
    function omoCollaboraBuildWopiToken(\dbObject\Document $document, int $userId, int $validUntil = 0): string
    {
        $documentId = (int)$document->getId();
        $userId = (int)$userId;
        $validUntil = $validUntil > 0 ? $validUntil : time() + 3600;
        $payload = array(
            'documentId' => $documentId,
            'userId' => $userId,
            'expiresAt' => $validUntil,
            'nonce' => bin2hex(random_bytes(12)),
        );
        $encodedPayload = omoCollaboraBase64UrlEncode((string)json_encode($payload, JSON_UNESCAPED_SLASHES));
        $secret = trim((string)$document->get('codeedit'));
        if ($secret === '') {
            $secret = 'document-' . $documentId . '-collabora';
        }

        $signature = omoCollaboraBase64UrlEncode(hash_hmac('sha256', $encodedPayload, $secret, true));
        return $encodedPayload . '.' . $signature;
    }
}

if (!function_exists('omoCollaboraVerifyWopiToken')) {
    function omoCollaboraVerifyWopiToken(\dbObject\Document $document, string $token): array|false
    {
        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2) {
            return false;
        }

        $payloadJson = omoCollaboraBase64UrlDecode($parts[0]);
        if (!is_string($payloadJson)) {
            return false;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return false;
        }

        $secret = trim((string)$document->get('codeedit'));
        if ($secret === '') {
            $secret = 'document-' . (int)$document->getId() . '-collabora';
        }

        $expectedSignature = omoCollaboraBase64UrlEncode(hash_hmac('sha256', $parts[0], $secret, true));
        if (!hash_equals($expectedSignature, $parts[1])) {
            return false;
        }

        if ((int)($payload['documentId'] ?? 0) !== (int)$document->getId()) {
            return false;
        }

        if ((int)($payload['userId'] ?? 0) <= 0 || (int)($payload['expiresAt'] ?? 0) < time()) {
            return false;
        }

        return $payload;
    }
}

if (!function_exists('omoCollaboraBuildWopiSource')) {
    function omoCollaboraBuildWopiSource(int $documentId, string $baseUrl = ''): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '') {
            $internalWopiBaseUrl = omoCollaboraNormalizeBaseUrl((string)(getenv('COLLABORA_WOPI_BASE_URL') ?: ''));
            if ($internalWopiBaseUrl !== '') {
                $baseUrl = $internalWopiBaseUrl;
            }

            $scheme = strtolower((string)($_SERVER['HTTPS'] ?? '')) !== 'off' && (string)($_SERVER['HTTPS'] ?? '') !== ''
                ? 'https'
                : 'http';
            if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
                $scheme = 'https';
            }
            $host = trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
            if ($baseUrl === '') {
                $normalizedHost = strtolower(preg_replace('/:\d+$/', '', $host));
                $baseUrl = $normalizedHost === 'localtest.me' || str_ends_with($normalizedHost, '.localtest.me')
                    ? 'http://app'
                    : ($scheme . '://' . $host);
            }
        }

        return $baseUrl . '/omo/api/documents/collabora/wopi.php/' . rawurlencode((string)$documentId);
    }
}

if (!function_exists('omoCollaboraBuildRequestOrigin')) {
    function omoCollaboraBuildRequestOrigin(): string
    {
        $scheme = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            ? 'https'
            : (strtolower((string)($_SERVER['HTTPS'] ?? '')) !== 'off' && (string)($_SERVER['HTTPS'] ?? '') !== '' ? 'https' : 'http');
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        return $host !== '' ? $scheme . '://' . $host : '';
    }
}

if (!function_exists('omoCollaboraFetchDiscovery')) {
    function omoCollaboraFetchDiscovery(array $config): array
    {
        $baseUrl = rtrim((string)($config['internalUrl'] ?? $config['baseUrl'] ?? ''), '/');
        if ($baseUrl === '' || !function_exists('curl_init')) {
            return array('status' => false, 'text' => 'Collabora n est pas disponible pour cette organisation.');
        }

        $url = $baseUrl . '/hosting/discovery';
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ));

        $host = strtolower(trim((string)parse_url($baseUrl, PHP_URL_HOST)));
        $localCertificate = '/etc/apache2/ssl/dev-localhost.crt';
        if ($host !== '' && ($host === 'localtest.me' || str_ends_with($host, '.localtest.me')) && is_file($localCertificate)) {
            curl_setopt($curl, CURLOPT_CAINFO, $localCertificate);
        }

        $response = curl_exec($curl);
        $error = trim((string)curl_error($curl));
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return array(
                'status' => false,
                'text' => $error !== '' ? $error : 'Impossible de charger la decouverte Collabora.',
                'httpCode' => $httpCode,
            );
        }

        return array('status' => true, 'xml' => (string)$response, 'httpCode' => $httpCode);
    }
}

if (!function_exists('omoCollaboraBuildBlankDocumentFile')) {
    function omoCollaboraBuildBlankDocumentFile(string $title): array
    {
        $safeTitle = trim($title) !== '' ? trim($title) : 'Document';
        $tmpPath = tempnam(sys_get_temp_dir(), 'omo-collabora-');
        if (!is_string($tmpPath) || $tmpPath === '') {
            return array('status' => false, 'text' => 'Impossible de preparer le document Collabora.');
        }

        $entries = array(
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/_rels/document.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>' . htmlspecialchars($safeTitle, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</w:t></w:r></w:p><w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr></w:body></w:document>',
            'word/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style></w:styles>',
        );

        if (class_exists('ZipArchive', false)) {
            $zip = new ZipArchive();
            if ($zip->open($tmpPath, ZipArchive::OVERWRITE) !== true) {
                @unlink($tmpPath);
                return array('status' => false, 'text' => 'Impossible de preparer le document Collabora.');
            }
            foreach ($entries as $entryName => $entryContents) {
                $zip->addFromString($entryName, $entryContents);
            }
            $zip->close();
        } else {
            $localDirectory = '';
            $centralDirectory = '';
            $offset = 0;
            foreach ($entries as $entryName => $entryContents) {
                $entryName = (string)$entryName;
                $entryContents = (string)$entryContents;
                $nameLength = strlen($entryName);
                $contentLength = strlen($entryContents);
                $checksum = crc32($entryContents);
                $localDirectory .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $checksum, $contentLength, $contentLength, $nameLength, 0);
                $localDirectory .= $entryName . $entryContents;
                $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $checksum, $contentLength, $contentLength, $nameLength, 0, 0, 0, 0, 0, $offset);
                $centralDirectory .= $entryName;
                $offset += 30 + $nameLength + $contentLength;
            }
            $centralOffset = strlen($localDirectory);
            $centralLength = strlen($centralDirectory);
            $endOfDirectory = pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), $centralLength, $centralOffset, 0);
            if (file_put_contents($tmpPath, $localDirectory . $centralDirectory . $endOfDirectory) === false) {
                @unlink($tmpPath);
                return array('status' => false, 'text' => 'Impossible de preparer le document Collabora.');
            }
        }

        return array(
            'status' => true,
            'tmpName' => $tmpPath,
            'name' => $safeTitle . '.docx',
            'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => (int)filesize($tmpPath),
        );
    }
}
