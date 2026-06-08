<?php

require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\ArrayUser;
use dbObject\ArrayUserOrganization;
use dbObject\User;
use dbObject\UserOrganization;

if (!function_exists('commonCardDavServicePath')) {
    function commonCardDavServicePath()
    {
        return '/omo/api/carddav';
    }
}

if (!function_exists('commonCardDavNamespaceMap')) {
    function commonCardDavNamespaceMap()
    {
        return array(
            'd' => 'DAV:',
            'card' => 'urn:ietf:params:xml:ns:carddav',
            'cs' => 'http://calendarserver.org/ns/',
        );
    }
}

if (!function_exists('commonCardDavAuthRealm')) {
    function commonCardDavAuthRealm()
    {
        return 'OpenMyOrganization CardDAV';
    }
}

if (!function_exists('commonCardDavBuildHref')) {
    function commonCardDavBuildHref($suffix = '')
    {
        $base = rtrim(commonCardDavServicePath(), '/');
        $suffix = trim((string)$suffix);

        if ($suffix === '' || $suffix === '/') {
            return $base . '/';
        }

        return $base . '/' . ltrim($suffix, '/');
    }
}

if (!function_exists('commonCardDavGetRequestPath')) {
    function commonCardDavGetRequestPath()
    {
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? commonCardDavBuildHref());
        $path = parse_url($requestUri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : commonCardDavBuildHref();
    }
}

if (!function_exists('commonCardDavGetRoutePath')) {
    function commonCardDavGetRoutePath()
    {
        $path = commonCardDavGetRequestPath();
        $base = rtrim(commonCardDavServicePath(), '/');

        if (strpos($path, $base) !== 0) {
            return '/';
        }

        $relative = substr($path, strlen($base));
        if (!is_string($relative) || $relative === '') {
            return '/';
        }

        return '/' . ltrim($relative, '/');
    }
}

if (!function_exists('commonCardDavCanExposeAuthDebug')) {
    function commonCardDavCanExposeAuthDebug()
    {
        return function_exists('appShouldExposeDevDiagnostics') && appShouldExposeDevDiagnostics();
    }
}

if (!function_exists('commonCardDavCanExposeDebug')) {
    function commonCardDavCanExposeDebug()
    {
        return commonCardDavCanExposeAuthDebug();
    }
}

if (!function_exists('commonCardDavSetAuthDebugValue')) {
    function commonCardDavSetAuthDebugValue($key, $value)
    {
        if (!commonCardDavCanExposeAuthDebug()) {
            return;
        }

        $key = trim((string)$key);
        if ($key === '') {
            return;
        }

        if (!isset($GLOBALS['commonCardDavAuthDebug']) || !is_array($GLOBALS['commonCardDavAuthDebug'])) {
            $GLOBALS['commonCardDavAuthDebug'] = array();
        }

        $sanitizedValue = preg_replace('/[^A-Za-z0-9._:-]/', '_', trim((string)$value));
        $GLOBALS['commonCardDavAuthDebug'][$key] = $sanitizedValue === '' ? 'empty' : $sanitizedValue;
    }
}

if (!function_exists('commonCardDavSetDebugValue')) {
    function commonCardDavSetDebugValue($key, $value)
    {
        if (!commonCardDavCanExposeDebug()) {
            return;
        }

        $key = trim((string)$key);
        if ($key === '') {
            return;
        }

        if (!isset($GLOBALS['commonCardDavDebug']) || !is_array($GLOBALS['commonCardDavDebug'])) {
            $GLOBALS['commonCardDavDebug'] = array();
        }

        $sanitizedValue = preg_replace('/[^A-Za-z0-9._:,\-]/', '_', trim((string)$value));
        $GLOBALS['commonCardDavDebug'][$key] = $sanitizedValue === '' ? 'empty' : $sanitizedValue;
    }
}

if (!function_exists('commonCardDavSendAuthDebugHeaders')) {
    function commonCardDavSendAuthDebugHeaders()
    {
        if (!commonCardDavCanExposeAuthDebug()) {
            return;
        }

        $debugValues = isset($GLOBALS['commonCardDavAuthDebug']) && is_array($GLOBALS['commonCardDavAuthDebug'])
            ? $GLOBALS['commonCardDavAuthDebug']
            : array();

        foreach ($debugValues as $key => $value) {
            $headerName = 'X-CardDAV-Auth-' . str_replace(' ', '-', ucwords(str_replace(array('-', '_'), ' ', (string)$key)));
            header($headerName . ': ' . (string)$value);
        }
    }
}

if (!function_exists('commonCardDavSendDebugHeaders')) {
    function commonCardDavSendDebugHeaders()
    {
        if (!commonCardDavCanExposeDebug()) {
            return;
        }

        $debugValues = isset($GLOBALS['commonCardDavDebug']) && is_array($GLOBALS['commonCardDavDebug'])
            ? $GLOBALS['commonCardDavDebug']
            : array();

        foreach ($debugValues as $key => $value) {
            $headerName = 'X-CardDAV-Debug-' . str_replace(' ', '-', ucwords(str_replace(array('-', '_'), ' ', (string)$key)));
            header($headerName . ': ' . (string)$value);
        }
    }
}

if (!function_exists('commonCardDavFindAuthorizationHeader')) {
    function commonCardDavFindAuthorizationHeader()
    {
        $serverCandidates = array(
            'PHP_AUTH_DIGEST' => false,
            'HTTP_AUTHORIZATION' => true,
            'REDIRECT_HTTP_AUTHORIZATION' => true,
            'Authorization' => true,
            'REDIRECT_Authorization' => true,
        );

        foreach ($serverCandidates as $serverKey => $shouldTrim) {
            if (!isset($_SERVER[$serverKey])) {
                continue;
            }

            $value = $shouldTrim
                ? trim((string)$_SERVER[$serverKey])
                : (string)$_SERVER[$serverKey];
            if ($value === '') {
                continue;
            }

            commonCardDavSetAuthDebugValue('source', 'server_' . strtolower($serverKey));
            return $value;
        }

        $envCandidates = array(
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
        );

        foreach ($envCandidates as $envKey) {
            $value = trim((string)(getenv($envKey) ?: ''));
            if ($value === '') {
                continue;
            }

            commonCardDavSetAuthDebugValue('source', 'env_' . strtolower($envKey));
            return $value;
        }

        foreach ($_SERVER as $serverKey => $serverValue) {
            if (stripos((string)$serverKey, 'AUTHORIZATION') === false) {
                continue;
            }

            $value = trim((string)$serverValue);
            if ($value === '') {
                continue;
            }

            commonCardDavSetAuthDebugValue('source', 'server_scan_' . strtolower((string)$serverKey));
            return $value;
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp((string)$name, 'Authorization') !== 0) {
                        continue;
                    }

                    $value = trim((string)$value);
                    if ($value === '') {
                        continue;
                    }

                    commonCardDavSetAuthDebugValue('source', 'getallheaders');
                    return $value;
                }
            }
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp((string)$name, 'Authorization') !== 0) {
                        continue;
                    }

                    $value = trim((string)$value);
                    if ($value === '') {
                        continue;
                    }

                    commonCardDavSetAuthDebugValue('source', 'apache_request_headers');
                    return $value;
                }
            }
        }

        commonCardDavSetAuthDebugValue('source', 'missing');
        return '';
    }
}

if (!function_exists('commonCardDavGetBasicCredentials')) {
    function commonCardDavGetBasicCredentials()
    {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            commonCardDavSetAuthDebugValue('source', 'php_auth_user');
            commonCardDavSetAuthDebugValue('credentials', 'present');
            commonCardDavSetAuthDebugValue('identifier_type', strpos((string)$_SERVER['PHP_AUTH_USER'], '@') !== false ? 'email_like' : 'opaque');
            return array(
                (string)$_SERVER['PHP_AUTH_USER'],
                (string)($_SERVER['PHP_AUTH_PW'] ?? ''),
            );
        }

        $header = commonCardDavFindAuthorizationHeader();
        if ($header === '' || stripos($header, 'Basic ') !== 0) {
            commonCardDavSetAuthDebugValue('credentials', $header === '' ? 'missing' : 'not_basic');
            return null;
        }

        $decoded = base64_decode(substr($header, 6), true);
        if (!is_string($decoded) || strpos($decoded, ':') === false) {
            commonCardDavSetAuthDebugValue('credentials', 'invalid_basic_payload');
            return null;
        }

        list($username, $password) = explode(':', $decoded, 2);
        commonCardDavSetAuthDebugValue('credentials', 'present');
        commonCardDavSetAuthDebugValue('identifier_type', strpos($username, '@') !== false ? 'email_like' : 'opaque');
        return array((string)$username, (string)$password);
    }
}

if (!function_exists('commonCardDavBuildLoginIdentifierCandidates')) {
    function commonCardDavBuildLoginIdentifierCandidates($identifier)
    {
        $identifier = trim((string)$identifier);
        if ($identifier === '') {
            commonCardDavSetAuthDebugValue('lookup_mode', 'empty_identifier');
            return array();
        }

        $candidates = array();
        if (strpos($identifier, '@') === false && function_exists('commonResolveOrganizationContext')) {
            $organizationContext = commonResolveOrganizationContext(1);
            $organizationDomain = trim((string)($organizationContext['domain'] ?? ''));
            if ($organizationDomain !== '') {
                $candidates[] = $identifier . '@' . $organizationDomain;
                commonCardDavSetAuthDebugValue('lookup_mode', 'org_domain_then_raw');
            } else {
                commonCardDavSetAuthDebugValue('lookup_mode', 'raw_only_no_org_domain');
            }
        } else {
            commonCardDavSetAuthDebugValue('lookup_mode', strpos($identifier, '@') !== false ? 'raw_email' : 'raw_only');
        }

        $candidates[] = $identifier;
        $uniqueCandidates = array();

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate === '' || in_array($candidate, $uniqueCandidates, true)) {
                continue;
            }

            $uniqueCandidates[] = $candidate;
        }

        commonCardDavSetAuthDebugValue('identifier_candidates', (string)count($uniqueCandidates));
        return $uniqueCandidates;
    }
}

if (!function_exists('commonCardDavFindUserByIdentifier')) {
    function commonCardDavFindUserByIdentifier($identifier)
    {
        $candidates = commonCardDavBuildLoginIdentifierCandidates($identifier);
        foreach ($candidates as $index => $candidate) {
            if (method_exists('dbObject\\User', 'debugLoginIdentifierMatchSummary')) {
                $summary = User::debugLoginIdentifierMatchSummary($candidate);
                commonCardDavSetAuthDebugValue('lookup_scope', 'user_email_only');
                commonCardDavSetAuthDebugValue('global_email_matches', (string)((int)($summary['globalEmailMatches'] ?? 0)));
                commonCardDavSetAuthDebugValue('organization_email_matches', (string)((int)($summary['organizationEmailMatches'] ?? 0)));
                commonCardDavSetAuthDebugValue('resolved_user_ids', (string)count((array)($summary['resolvedUserIds'] ?? array())));
            }

            $user = User::findByLoginIdentifier($candidate);
            if ($user) {
                commonCardDavSetAuthDebugValue('matched_candidate', $index === 0 ? 'first' : 'fallback');
                return $user;
            }
        }

        commonCardDavSetAuthDebugValue('matched_candidate', 'none');
        return null;
    }
}

if (!function_exists('commonCardDavAuthenticateRequest')) {
    function commonCardDavAuthenticateRequest()
    {
        commonRestoreRememberedUser();

        $credentials = commonCardDavGetBasicCredentials();
        $currentUserId = (int)commonGetCurrentUserId();
        if ($currentUserId > 0 && !is_array($credentials)) {
            $user = new User();
            if ($user->load($currentUserId)) {
                commonCardDavSetAuthDebugValue('result', 'session_user');
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                return $user;
            }
        }

        if (!is_array($credentials) || count($credentials) !== 2) {
            commonCardDavSetAuthDebugValue('result', 'missing_credentials');
            return null;
        }

        $identifier = trim((string)$credentials[0]);
        $password = (string)$credentials[1];
        if ($identifier === '' || $password === '') {
            commonCardDavSetAuthDebugValue('result', 'empty_identifier_or_password');
            return null;
        }

        $user = commonCardDavFindUserByIdentifier($identifier);
        if (!$user) {
            commonCardDavSetAuthDebugValue('result', 'user_not_found');
            return null;
        }

        $passwordHash = (string)$user->get('password');
        commonCardDavSetAuthDebugValue('password_hash', $passwordHash === '' ? 'missing' : 'present');
        if (!commonVerifyUserPassword($password, $passwordHash)) {
            commonCardDavSetAuthDebugValue('result', $passwordHash === '' ? 'user_has_no_password' : 'password_invalid');
            return null;
        }

        $_SESSION['currentUser'] = (int)$user->getId();
        $_SESSION['userRef'] = $user;
        unset($_SESSION['permissionCacheByOrganization']);
        commonCardDavSetAuthDebugValue('result', 'basic_auth_ok');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return $user;
    }
}

if (!function_exists('commonCardDavSendUnauthorized')) {
    function commonCardDavSendUnauthorized()
    {
        http_response_code(401);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('WWW-Authenticate: Basic realm="' . addslashes(commonCardDavAuthRealm()) . '", charset="UTF-8"');
        commonCardDavSendAuthDebugHeaders();
        echo 'Authentication required.';
        exit;
    }
}

if (!function_exists('commonCardDavSendStatusText')) {
    function commonCardDavSendStatusText($statusCode, $message)
    {
        http_response_code((int)$statusCode);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSendDebugHeaders();
        echo (string)$message;
        exit;
    }
}

if (!function_exists('commonCardDavSendOptions')) {
    function commonCardDavSendOptions(array $allowedMethods)
    {
        $allow = implode(', ', array_values(array_unique(array_map('strtoupper', $allowedMethods))));
        header('Allow: ' . $allow);
        header('DAV: 1, 2, access-control, addressbook');
        header('MS-Author-Via: DAV');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSendDebugHeaders();
        http_response_code(204);
        exit;
    }
}

if (!function_exists('commonCardDavReadXmlBody')) {
    function commonCardDavReadXmlBody()
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw)) {
            return array('raw' => '', 'xml' => null);
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return array('raw' => $raw, 'xml' => null);
        }

        $previousUseErrors = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadXML($trimmed);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        return array(
            'raw' => $raw,
            'xml' => $loaded ? $document : null,
        );
    }
}

if (!function_exists('commonCardDavClarkName')) {
    function commonCardDavClarkName(DOMNode $node)
    {
        return '{' . (string)$node->namespaceURI . '}' . (string)$node->localName;
    }
}

if (!function_exists('commonCardDavParsePropRequest')) {
    function commonCardDavParsePropRequest(?DOMDocument $document = null)
    {
        if (!$document) {
            return array(
                'mode' => 'allprop',
                'properties' => array(),
            );
        }

        $root = $document->documentElement;
        if (!$root) {
            return array(
                'mode' => 'allprop',
                'properties' => array(),
            );
        }

        foreach ($root->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $name = commonCardDavClarkName($child);
            if ($name === '{DAV:}allprop') {
                return array(
                    'mode' => 'allprop',
                    'properties' => array(),
                );
            }

            if ($name === '{DAV:}propname') {
                return array(
                    'mode' => 'propname',
                    'properties' => array(),
                );
            }

            if ($name === '{DAV:}prop') {
                $properties = array();
                foreach ($child->childNodes as $propNode) {
                    if ($propNode instanceof DOMElement) {
                        $properties[] = commonCardDavClarkName($propNode);
                    }
                }
                return array(
                    'mode' => 'prop',
                    'properties' => array_values(array_unique($properties)),
                );
            }
        }

        return array(
            'mode' => 'allprop',
            'properties' => array(),
        );
    }
}

if (!function_exists('commonCardDavCreateDom')) {
    function commonCardDavCreateDom($rootLocalName, $rootNamespace = 'DAV:', $rootPrefix = 'd')
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $root = $document->createElementNS($rootNamespace, $rootPrefix . ':' . $rootLocalName);
        foreach (commonCardDavNamespaceMap() as $prefix => $namespace) {
            if ($prefix === $rootPrefix && $namespace === $rootNamespace) {
                continue;
            }
            $root->setAttribute('xmlns:' . $prefix, $namespace);
        }
        $document->appendChild($root);
        return array($document, $root);
    }
}

if (!function_exists('commonCardDavAppendHref')) {
    function commonCardDavAppendHref(DOMDocument $document, DOMElement $parent, $href)
    {
        $element = $document->createElementNS('DAV:', 'd:href');
        $element->appendChild($document->createTextNode((string)$href));
        $parent->appendChild($element);
        return $element;
    }
}

if (!function_exists('commonCardDavAppendPropertyValue')) {
    function commonCardDavAppendPropertyValue(DOMDocument $document, DOMElement $propertyElement, array $value)
    {
        $type = (string)($value['type'] ?? 'text');

        if ($type === 'text') {
            $propertyElement->appendChild($document->createTextNode((string)($value['value'] ?? '')));
            return;
        }

        if ($type === 'href') {
            commonCardDavAppendHref($document, $propertyElement, (string)($value['value'] ?? ''));
            return;
        }

        if ($type === 'href-list') {
            foreach ((array)($value['value'] ?? array()) as $href) {
                commonCardDavAppendHref($document, $propertyElement, (string)$href);
            }
            return;
        }

        if ($type === 'resourcetype') {
            foreach ((array)($value['value'] ?? array()) as $resourceType) {
                $namespace = (string)($resourceType['namespace'] ?? 'DAV:');
                $prefix = (string)($resourceType['prefix'] ?? 'd');
                $localName = (string)($resourceType['localName'] ?? '');
                if ($localName === '') {
                    continue;
                }
                $propertyElement->appendChild($document->createElementNS($namespace, $prefix . ':' . $localName));
            }
            return;
        }

        if ($type === 'supported-report-set') {
            foreach ((array)($value['value'] ?? array()) as $report) {
                $reportElement = $document->createElementNS('DAV:', 'd:supported-report');
                $nameElement = $document->createElementNS('DAV:', 'd:report');
                $namespace = (string)($report['namespace'] ?? 'DAV:');
                $prefix = (string)($report['prefix'] ?? 'd');
                $localName = (string)($report['localName'] ?? '');
                if ($localName === '') {
                    continue;
                }
                $nameElement->appendChild($document->createElementNS($namespace, $prefix . ':' . $localName));
                $reportElement->appendChild($nameElement);
                $propertyElement->appendChild($reportElement);
            }
            return;
        }

        if ($type === 'supported-address-data') {
            foreach ((array)($value['value'] ?? array()) as $format) {
                $addressData = $document->createElementNS('urn:ietf:params:xml:ns:carddav', 'card:address-data-type');
                $addressData->setAttribute('content-type', (string)($format['contentType'] ?? 'text/vcard'));
                if (!empty($format['version'])) {
                    $addressData->setAttribute('version', (string)$format['version']);
                }
                $propertyElement->appendChild($addressData);
            }
            return;
        }

        if ($type === 'email-address-set') {
            foreach ((array)($value['value'] ?? array()) as $email) {
                $entry = $document->createElementNS('http://calendarserver.org/ns/', 'cs:email-address');
                $entry->appendChild($document->createTextNode((string)$email));
                $propertyElement->appendChild($entry);
            }
            return;
        }

        if ($type === 'raw-xml') {
            $fragment = $document->createDocumentFragment();
            $fragment->appendXML((string)($value['value'] ?? ''));
            $propertyElement->appendChild($fragment);
        }
    }
}

if (!function_exists('commonCardDavRenderPropstat')) {
    function commonCardDavRenderPropstat(DOMDocument $document, DOMElement $responseElement, array $properties, $statusLine, $propnameMode = false)
    {
        if (count($properties) === 0) {
            return;
        }

        $propstatElement = $document->createElementNS('DAV:', 'd:propstat');
        $propElement = $document->createElementNS('DAV:', 'd:prop');

        foreach ($properties as $qname => $value) {
            if (!preg_match('/^\{(.*)\}([^}]+)$/', (string)$qname, $matches)) {
                continue;
            }

            $namespace = (string)$matches[1];
            $localName = (string)$matches[2];
            $prefix = 'x';
            foreach (commonCardDavNamespaceMap() as $mappedPrefix => $mappedNamespace) {
                if ($mappedNamespace === $namespace) {
                    $prefix = $mappedPrefix;
                    break;
                }
            }

            $propertyElement = $document->createElementNS($namespace, $prefix . ':' . $localName);
            if (!$propnameMode && is_array($value) && !empty($value['type'])) {
                commonCardDavAppendPropertyValue($document, $propertyElement, $value);
            }
            $propElement->appendChild($propertyElement);
        }

        $propstatElement->appendChild($propElement);
        $statusElement = $document->createElementNS('DAV:', 'd:status');
        $statusElement->appendChild($document->createTextNode((string)$statusLine));
        $propstatElement->appendChild($statusElement);
        $responseElement->appendChild($propstatElement);
    }
}

if (!function_exists('commonCardDavSendMultistatus')) {
    function commonCardDavSendMultistatus(array $responses, $propnameMode = false, $extraTopLevel = array())
    {
        list($document, $root) = commonCardDavCreateDom('multistatus');

        foreach ($responses as $response) {
            $responseElement = $document->createElementNS('DAV:', 'd:response');
            commonCardDavAppendHref($document, $responseElement, (string)($response['href'] ?? '/'));

            if (!empty($response['statusOnly'])) {
                $statusElement = $document->createElementNS('DAV:', 'd:status');
                $statusElement->appendChild($document->createTextNode((string)$response['statusOnly']));
                $responseElement->appendChild($statusElement);
                $root->appendChild($responseElement);
                continue;
            }

            commonCardDavRenderPropstat(
                $document,
                $responseElement,
                (array)($response['ok'] ?? array()),
                'HTTP/1.1 200 OK',
                $propnameMode
            );
            commonCardDavRenderPropstat(
                $document,
                $responseElement,
                (array)($response['missing'] ?? array()),
                'HTTP/1.1 404 Not Found',
                $propnameMode
            );

            $root->appendChild($responseElement);
        }

        foreach ($extraTopLevel as $elementDefinition) {
            $namespace = (string)($elementDefinition['namespace'] ?? 'DAV:');
            $prefix = (string)($elementDefinition['prefix'] ?? 'd');
            $localName = (string)($elementDefinition['localName'] ?? '');
            if ($localName === '') {
                continue;
            }
            $element = $document->createElementNS($namespace, $prefix . ':' . $localName);
            $element->appendChild($document->createTextNode((string)($elementDefinition['value'] ?? '')));
            $root->appendChild($element);
        }

        http_response_code(207);
        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSendDebugHeaders();
        echo $document->saveXML();
        exit;
    }
}

if (!function_exists('commonCardDavCollectUniqueStrings')) {
    function commonCardDavCollectUniqueStrings(array $values)
    {
        $unique = array();
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '' || isset($unique[$value])) {
                continue;
            }
            $unique[$value] = $value;
        }
        return array_values($unique);
    }
}

if (!function_exists('commonCardDavBuildUid')) {
    function commonCardDavBuildUid(User $user)
    {
        $host = preg_replace('/:\d+$/', '', commonGetRequestHost());
        if ($host === '') {
            $host = 'localhost';
        }
        return 'omo-user-' . (int)$user->getId() . '@' . $host;
    }
}

if (!function_exists('commonCardDavBuildStableUuid')) {
    function commonCardDavBuildStableUuid($seed)
    {
        $hash = md5((string)$seed);
        return substr($hash, 0, 8)
            . '-' . substr($hash, 8, 4)
            . '-' . substr($hash, 12, 4)
            . '-' . substr($hash, 16, 4)
            . '-' . substr($hash, 20, 12);
    }
}

if (!function_exists('commonCardDavEscapeVCardText')) {
    function commonCardDavEscapeVCardText($value)
    {
        $value = str_replace("\r\n", "\n", (string)$value);
        $value = str_replace("\r", "\n", $value);
        $value = str_replace("\\", "\\\\", $value);
        $value = str_replace(';', '\;', $value);
        $value = str_replace(',', '\,', $value);
        return str_replace("\n", '\n', $value);
    }
}

if (!function_exists('commonCardDavFoldVCardLine')) {
    function commonCardDavFoldVCardLine($line)
    {
        $line = (string)$line;
        if ($line === '') {
            return '';
        }

        $chunks = array();
        $remaining = $line;

        while (strlen($remaining) > 75) {
            if (function_exists('mb_strcut')) {
                $chunk = mb_strcut($remaining, 0, 75, 'UTF-8');
            } else {
                $chunk = substr($remaining, 0, 75);
            }
            if ($chunk === '') {
                break;
            }
            $chunks[] = $chunk;
            $remaining = (string)substr($remaining, strlen($chunk));
        }

        $chunks[] = $remaining;
        return implode("\r\n ", $chunks);
    }
}

if (!function_exists('commonCardDavFormatTimestamp')) {
    function commonCardDavFormatTimestamp(?DateTimeInterface $dateTime = null)
    {
        if (!$dateTime) {
            return '';
        }

        $utc = new DateTimeZone('UTC');
        $timestamp = DateTimeImmutable::createFromInterface($dateTime)->setTimezone($utc);
        return $timestamp->format('Ymd\THis\Z');
    }
}

if (!function_exists('commonCardDavFormatHttpDate')) {
    function commonCardDavFormatHttpDate(?DateTimeInterface $dateTime = null)
    {
        if (!$dateTime) {
            return '';
        }

        $gmt = new DateTimeZone('GMT');
        $timestamp = DateTimeImmutable::createFromInterface($dateTime)->setTimezone($gmt);
        return $timestamp->format('D, d M Y H:i:s') . ' GMT';
    }
}

if (!function_exists('commonCardDavExtractGeo')) {
    function commonCardDavExtractGeo($value)
    {
        if (!is_object($value)) {
            return null;
        }

        $latitude = $value->lat ?? null;
        $longitude = $value->long ?? null;
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        return array(
            'lat' => (float)$latitude,
            'long' => (float)$longitude,
        );
    }
}

if (!function_exists('commonCardDavResolveLastModified')) {
    function commonCardDavResolveLastModified(User $user, array $memberships)
    {
        $dates = array();

        foreach (array('datecreation', 'dateconnexion') as $field) {
            $value = $user->get($field);
            if ($value instanceof DateTimeInterface) {
                $dates[] = DateTimeImmutable::createFromInterface($value);
            }
        }

        foreach ($memberships as $membership) {
            if (!$membership instanceof UserOrganization) {
                continue;
            }
            foreach (array('datecreation', 'dateconnexion') as $field) {
                $value = $membership->get($field);
                if ($value instanceof DateTimeInterface) {
                    $dates[] = DateTimeImmutable::createFromInterface($value);
                }
            }
        }

        if (count($dates) === 0) {
            return null;
        }

        usort($dates, static function (DateTimeImmutable $left, DateTimeImmutable $right) {
            return $right <=> $left;
        });

        return $dates[0];
    }
}

if (!function_exists('commonCardDavBuildContactSearchValues')) {
    function commonCardDavBuildContactSearchValues(array $payload)
    {
        return array(
            'FN' => array((string)($payload['displayName'] ?? '')),
            'EMAIL' => (array)($payload['emails'] ?? array()),
            'NICKNAME' => (array)($payload['usernames'] ?? array()),
            'UID' => array((string)($payload['uid'] ?? '')),
            'ORG' => (array)($payload['organizationNames'] ?? array()),
            'NOTE' => array((string)($payload['note'] ?? '')),
            'CATEGORIES' => (array)($payload['organizationNames'] ?? array()),
        );
    }
}

if (!function_exists('commonCardDavRenderVCard')) {
    function commonCardDavRenderVCard(array $payload)
    {
        $lines = array(
            'BEGIN:VCARD',
            'VERSION:3.0',
            'PRODID:-//OpenMyOrganization//CardDAV//EN',
            'UID:' . commonCardDavEscapeVCardText((string)($payload['uid'] ?? '')),
            'FN:' . commonCardDavEscapeVCardText((string)($payload['displayName'] ?? '')),
            'N:' . implode(';', array(
                commonCardDavEscapeVCardText((string)($payload['lastname'] ?? '')),
                commonCardDavEscapeVCardText((string)($payload['firstname'] ?? '')),
                '',
                '',
                '',
            )),
        );

        $usernames = commonCardDavCollectUniqueStrings((array)($payload['usernames'] ?? array()));
        if (count($usernames) > 0) {
            $lines[] = 'NICKNAME:' . commonCardDavEscapeVCardText($usernames[0]);
        }

        foreach (commonCardDavCollectUniqueStrings((array)($payload['emails'] ?? array())) as $email) {
            $lines[] = 'EMAIL;TYPE=INTERNET:' . commonCardDavEscapeVCardText($email);
        }

        if (!empty($payload['organizationNames'])) {
            $lines[] = 'ORG:' . commonCardDavEscapeVCardText((string)$payload['organizationNames'][0]);
            $lines[] = 'CATEGORIES:' . commonCardDavEscapeVCardText(implode(', ', (array)$payload['organizationNames']));
        }

        if (!empty($payload['photoUrl'])) {
            $lines[] = 'PHOTO;VALUE=URI:' . commonCardDavEscapeVCardText((string)$payload['photoUrl']);
        }

        if (!empty($payload['birthdate'])) {
            $lines[] = 'BDAY:' . commonCardDavEscapeVCardText((string)$payload['birthdate']);
        }

        if (!empty($payload['geo']) && is_array($payload['geo'])) {
            $lines[] = 'GEO:' . (string)$payload['geo']['lat'] . ';' . (string)$payload['geo']['long'];
        }

        if (!empty($payload['note'])) {
            $lines[] = 'NOTE:' . commonCardDavEscapeVCardText((string)$payload['note']);
        }

        if (!empty($payload['rev'])) {
            $lines[] = 'REV:' . commonCardDavEscapeVCardText((string)$payload['rev']);
        }

        $lines[] = 'END:VCARD';

        $folded = array();
        foreach ($lines as $line) {
            $folded[] = commonCardDavFoldVCardLine($line);
        }

        return implode("\r\n", $folded) . "\r\n";
    }
}

if (!function_exists('commonCardDavBuildContactResource')) {
    function commonCardDavBuildContactResource($viewerUserId, User $user, array $memberships)
    {
        $viewerUserId = (int)$viewerUserId;
        $organizationNames = array();
        $emails = array();
        $usernames = array();
        $presentationValues = array();
        $photoUrl = '';

        foreach ($memberships as $membership) {
            if (!$membership instanceof UserOrganization) {
                continue;
            }

            $organization = $membership->get('organization');
            if ($organization && trim((string)$organization->get('name')) !== '') {
                $organizationNames[] = trim((string)$organization->get('name'));
            }

            $scopedEmail = trim((string)$membership->getScopedEmail());
            if ($scopedEmail !== '') {
                $emails[] = $scopedEmail;
            }

            $scopedUsername = trim((string)$membership->getScopedUsername());
            if ($scopedUsername !== '') {
                $usernames[] = $scopedUsername;
            }

            $scopedPresentation = trim((string)$membership->getScopedPresentation());
            if ($scopedPresentation !== '') {
                $presentationValues[] = $scopedPresentation;
            }

            if ($photoUrl === '') {
                $photoUrl = trim((string)$membership->getProfilePhotoUrl());
            }
        }

        if ($photoUrl === '') {
            $photoUrl = trim((string)$user->getProfilePhotoUrl());
        }
        $photoUrl = commonBuildAbsoluteAssetUrl($photoUrl);

        $organizationNames = commonCardDavCollectUniqueStrings($organizationNames);
        $emails = commonCardDavCollectUniqueStrings(array_merge(
            $emails,
            array(trim((string)$user->get('email')))
        ));
        $usernames = commonCardDavCollectUniqueStrings(array_merge(
            $usernames,
            array(trim((string)$user->get('username')))
        ));
        $presentationValues = commonCardDavCollectUniqueStrings($presentationValues);

        $displayName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
        if ($displayName === '' && count($usernames) > 0) {
            $displayName = $usernames[0];
        }
        if ($displayName === '' && count($emails) > 0) {
            $displayName = $emails[0];
        }
        if ($displayName === '') {
            $displayName = 'Member ' . (int)$user->getId();
        }

        $noteParts = array();
        if (count($presentationValues) > 0) {
            $noteParts[] = $presentationValues[0];
        } else {
            $globalPresentation = trim((string)$user->getScopedPresentation());
            if ($globalPresentation !== '') {
                $noteParts[] = $globalPresentation;
            }
        }
        if (count($organizationNames) > 0) {
            $noteParts[] = 'Organizations: ' . implode(', ', $organizationNames);
        }

        $lastModified = commonCardDavResolveLastModified($user, $memberships);
        $payload = array(
            'userId' => (int)$user->getId(),
            'viewerUserId' => $viewerUserId,
            'uid' => commonCardDavBuildUid($user),
            'displayName' => $displayName,
            'firstname' => trim((string)$user->get('firstname')),
            'lastname' => trim((string)$user->get('lastname')),
            'emails' => $emails,
            'usernames' => $usernames,
            'organizationNames' => $organizationNames,
            'note' => implode("\n\n", $noteParts),
            'photoUrl' => $photoUrl,
            'birthdate' => $user->get('birthdate') instanceof DateTimeInterface
                ? $user->get('birthdate')->format('Y-m-d')
                : '',
            'geo' => commonCardDavExtractGeo($user->get('latlong')),
            'rev' => commonCardDavFormatTimestamp($lastModified),
        );

        $vcard = commonCardDavRenderVCard($payload);
        $cardName = 'user-' . (int)$user->getId() . '.vcf';
        $href = commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/members/' . $cardName);
        $etag = '"' . sha1($vcard) . '"';

        return array(
            'type' => 'card',
            'href' => $href,
            'cardName' => $cardName,
            'etag' => $etag,
            'contentType' => 'text/vcard; charset=utf-8',
            'contentLength' => strlen($vcard),
            'lastModified' => $lastModified,
            'vcard' => $vcard,
            'payload' => $payload,
            'searchValues' => commonCardDavBuildContactSearchValues($payload),
            'resourceId' => 'urn:uuid:' . commonCardDavBuildStableUuid('card:' . (int)$user->getId()),
        );
    }
}

if (!function_exists('commonCardDavLoadContactsForViewer')) {
    function commonCardDavLoadContactsForViewer($viewerUserId)
    {
        static $cache = array();

        $viewerUserId = (int)$viewerUserId;
        if ($viewerUserId <= 0) {
            commonCardDavSetDebugValue('viewer_user_id', '0');
            commonCardDavSetDebugValue('contacts_returned', '0');
            return array();
        }

        if (array_key_exists($viewerUserId, $cache)) {
            commonCardDavSetDebugValue('viewer_user_id', (string)$viewerUserId);
            commonCardDavSetDebugValue('contacts_cache', 'hit');
            commonCardDavSetDebugValue('contacts_returned', (string)count($cache[$viewerUserId]));
            return $cache[$viewerUserId];
        }

        commonCardDavSetDebugValue('viewer_user_id', (string)$viewerUserId);
        commonCardDavSetDebugValue('contacts_cache', 'miss');

        $memberships = new ArrayUserOrganization();
        $memberships->loadCardDavVisibleForViewer($viewerUserId);
        commonCardDavSetDebugValue('shared_membership_rows', (string)count($memberships));

        $groupedMemberships = array();
        $userIds = array();

        foreach ($memberships as $membership) {
            if (!$membership instanceof UserOrganization) {
                continue;
            }

            $userId = (int)$membership->get('IDuser');
            if ($userId <= 0) {
                continue;
            }

            if (!isset($groupedMemberships[$userId])) {
                $groupedMemberships[$userId] = array();
                $userIds[$userId] = $userId;
            }

            $groupedMemberships[$userId][] = $membership;
        }

        commonCardDavSetDebugValue('distinct_candidate_users', (string)count($userIds));

        if (count($userIds) === 0) {
            $cache[$viewerUserId] = array();
            commonCardDavSetDebugValue('viewable_users', '0');
            commonCardDavSetDebugValue('contacts_returned', '0');
            return $cache[$viewerUserId];
        }

        $users = new ArrayUser();
        $users->load(array(
            'where' => array(
                array(
                    'field' => 'id',
                    'op' => 'in',
                    'value' => array_values($userIds),
                ),
            ),
            'orderBy' => array(
                array('field' => 'lastname', 'dir' => 'ASC'),
                array('field' => 'firstname', 'dir' => 'ASC'),
                array('field' => 'username', 'dir' => 'ASC'),
                array('field' => 'email', 'dir' => 'ASC'),
            ),
        ));

        $contactMap = array();
        $viewableUserCount = 0;
        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $userId = (int)$user->getId();
            if ($userId <= 0 || !isset($groupedMemberships[$userId])) {
                continue;
            }

            if (!$user->canView()) {
                continue;
            }

            $viewableUserCount++;
            $contact = commonCardDavBuildContactResource($viewerUserId, $user, $groupedMemberships[$userId]);
            $contactMap[$contact['cardName']] = $contact;
        }

        commonCardDavSetDebugValue('viewable_users', (string)$viewableUserCount);

        uasort($contactMap, static function (array $left, array $right) {
            $leftLabel = (string)($left['payload']['displayName'] ?? '');
            $rightLabel = (string)($right['payload']['displayName'] ?? '');

            if (function_exists('mb_strtolower')) {
                $leftLabel = mb_strtolower($leftLabel, 'UTF-8');
                $rightLabel = mb_strtolower($rightLabel, 'UTF-8');
            } else {
                $leftLabel = strtolower($leftLabel);
                $rightLabel = strtolower($rightLabel);
            }

            return strcmp($leftLabel, $rightLabel);
        });

        $cache[$viewerUserId] = $contactMap;
        commonCardDavSetDebugValue('contacts_returned', (string)count($contactMap));
        return $cache[$viewerUserId];
    }
}

if (!function_exists('commonCardDavBuildSyncToken')) {
    function commonCardDavBuildSyncToken($viewerUserId, array $contactMap)
    {
        $seed = 'viewer:' . (int)$viewerUserId;
        foreach ($contactMap as $contact) {
            $seed .= '|' . (string)($contact['cardName'] ?? '') . ':' . (string)($contact['etag'] ?? '');
        }
        return commonCardDavBuildHref('sync/' . sha1($seed));
    }
}

if (!function_exists('commonCardDavBuildContactsDebugSummary')) {
    function commonCardDavBuildContactsDebugSummary(User $viewer, $sampleSize = 10)
    {
        $sampleSize = max(1, (int)$sampleSize);
        $contactMap = commonCardDavLoadContactsForViewer((int)$viewer->getId());
        $debugValues = isset($GLOBALS['commonCardDavDebug']) && is_array($GLOBALS['commonCardDavDebug'])
            ? $GLOBALS['commonCardDavDebug']
            : array();

        $contacts = array();
        $count = 0;
        foreach ($contactMap as $cardName => $contact) {
            $contacts[] = array(
                'cardName' => (string)$cardName,
                'href' => (string)($contact['href'] ?? ''),
                'displayName' => (string)($contact['payload']['displayName'] ?? ''),
                'emails' => array_values((array)($contact['payload']['emails'] ?? array())),
                'organizations' => array_values((array)($contact['payload']['organizationNames'] ?? array())),
            );

            $count++;
            if ($count >= $sampleSize) {
                break;
            }
        }

        return array(
            'viewerUserId' => (int)$viewer->getId(),
            'viewerEmail' => trim((string)$viewer->get('email')),
            'routePath' => commonCardDavGetRoutePath(),
            'debug' => $debugValues,
            'contactCount' => count($contactMap),
            'sampleContacts' => $contacts,
        );
    }
}

if (!function_exists('commonCardDavSupportedReports')) {
    function commonCardDavSupportedReports()
    {
        return array(
            array(
                'namespace' => 'urn:ietf:params:xml:ns:carddav',
                'prefix' => 'card',
                'localName' => 'addressbook-query',
            ),
            array(
                'namespace' => 'urn:ietf:params:xml:ns:carddav',
                'prefix' => 'card',
                'localName' => 'addressbook-multiget',
            ),
            array(
                'namespace' => 'DAV:',
                'prefix' => 'd',
                'localName' => 'sync-collection',
            ),
        );
    }
}

if (!function_exists('commonCardDavSupportedAddressData')) {
    function commonCardDavSupportedAddressData()
    {
        return array(
            array(
                'contentType' => 'text/vcard',
                'version' => '3.0',
            ),
        );
    }
}

if (!function_exists('commonCardDavBuildPropertyMap')) {
    function commonCardDavBuildPropertyMap(array $resource, User $viewer, array $context = array())
    {
        $viewerUserId = (int)$viewer->getId();
        $principalHref = commonCardDavBuildHref('principals/' . $viewerUserId . '/');
        $principalCollectionHref = commonCardDavBuildHref('principals/');
        $addressbookRootHref = commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/');
        $addressbookHref = commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/members/');
        $contactMap = isset($context['contactMap']) && is_array($context['contactMap'])
            ? $context['contactMap']
            : array();
        $syncToken = commonCardDavBuildSyncToken($viewerUserId, $contactMap);
        $viewerEmail = trim((string)$viewer->get('email'));

        switch ((string)($resource['type'] ?? '')) {
            case 'root':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => commonCardDavAuthRealm()),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}principal-collection-set' => array('type' => 'href-list', 'value' => array($principalCollectionHref)),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCardDavSupportedReports()),
                );

            case 'principals':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Principals'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                );

            case 'principal':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)$viewer->getScopedDisplayName()),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'principal'),
                        ),
                    ),
                    '{DAV:}principal-URL' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}principal-collection-set' => array('type' => 'href-list', 'value' => array($principalCollectionHref)),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCardDavSupportedReports()),
                    '{urn:ietf:params:xml:ns:carddav}addressbook-home-set' => array('type' => 'href', 'value' => $addressbookRootHref),
                    '{http://calendarserver.org/ns/}email-address-set' => array('type' => 'email-address-set', 'value' => $viewerEmail !== '' ? array($viewerEmail) : array()),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => 'urn:uuid:' . commonCardDavBuildStableUuid('principal:' . $viewerUserId)),
                );

            case 'addressbooks':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Addressbooks'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                );

            case 'addressbook-home':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Addressbook home'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                    '{urn:ietf:params:xml:ns:carddav}addressbook-home-set' => array('type' => 'href', 'value' => $addressbookRootHref),
                );

            case 'addressbook':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'OMO Members'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                            array('namespace' => 'urn:ietf:params:xml:ns:carddav', 'prefix' => 'card', 'localName' => 'addressbook'),
                        ),
                    ),
                    '{DAV:}owner' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCardDavSupportedReports()),
                    '{urn:ietf:params:xml:ns:carddav}supported-address-data' => array('type' => 'supported-address-data', 'value' => commonCardDavSupportedAddressData()),
                    '{urn:ietf:params:xml:ns:carddav}addressbook-description' => array('type' => 'text', 'value' => 'Merged members from your organizations'),
                    '{DAV:}sync-token' => array('type' => 'text', 'value' => $syncToken),
                    '{http://calendarserver.org/ns/}getctag' => array('type' => 'text', 'value' => sha1($syncToken)),
                );

            case 'card':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)($resource['payload']['displayName'] ?? $resource['cardName'] ?? 'Contact')),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(),
                    ),
                    '{DAV:}getetag' => array('type' => 'text', 'value' => (string)($resource['etag'] ?? '')),
                    '{DAV:}getcontenttype' => array('type' => 'text', 'value' => (string)($resource['contentType'] ?? 'text/vcard; charset=utf-8')),
                    '{DAV:}getcontentlength' => array('type' => 'text', 'value' => (string)($resource['contentLength'] ?? 0)),
                    '{DAV:}getlastmodified' => array('type' => 'text', 'value' => commonCardDavFormatHttpDate($resource['lastModified'] ?? null)),
                    '{DAV:}creationdate' => array('type' => 'text', 'value' => !empty($resource['lastModified']) ? DateTimeImmutable::createFromInterface($resource['lastModified'])->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z') : ''),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => (string)($resource['resourceId'] ?? '')),
                    '{urn:ietf:params:xml:ns:carddav}address-data' => array('type' => 'text', 'value' => (string)($resource['vcard'] ?? '')),
                );
        }

        return array();
    }
}

if (!function_exists('commonCardDavGetDefaultPropertyNames')) {
    function commonCardDavGetDefaultPropertyNames(array $resource, User $viewer, array $context = array())
    {
        return array_keys(commonCardDavBuildPropertyMap($resource, $viewer, $context));
    }
}

if (!function_exists('commonCardDavBuildResponseForResource')) {
    function commonCardDavBuildResponseForResource(array $resource, User $viewer, array $requestedProperties, $propnameMode = false, array $context = array())
    {
        $propertyMap = commonCardDavBuildPropertyMap($resource, $viewer, $context);
        $properties = count($requestedProperties) > 0
            ? array_values(array_unique($requestedProperties))
            : array_keys($propertyMap);

        $ok = array();
        $missing = array();

        foreach ($properties as $propertyName) {
            if (isset($propertyMap[$propertyName])) {
                $ok[$propertyName] = $propertyMap[$propertyName];
            } else {
                $missing[$propertyName] = array('type' => 'text', 'value' => '');
            }
        }

        return array(
            'href' => (string)($resource['href'] ?? commonCardDavBuildHref()),
            'ok' => $ok,
            'missing' => $missing,
            'propnameMode' => (bool)$propnameMode,
        );
    }
}

if (!function_exists('commonCardDavResolveRouteResource')) {
    function commonCardDavResolveRouteResource(User $viewer, $routePath)
    {
        $viewerUserId = (int)$viewer->getId();
        $routePath = '/' . trim((string)$routePath, '/');
        if ($routePath === '/') {
            return array(
                'type' => 'root',
                'href' => commonCardDavBuildHref(),
            );
        }

        if ($routePath === '/principals') {
            return array(
                'type' => 'principals',
                'href' => commonCardDavBuildHref('principals/'),
            );
        }

        if ($routePath === '/principals/' . $viewerUserId) {
            return array(
                'type' => 'principal',
                'href' => commonCardDavBuildHref('principals/' . $viewerUserId . '/'),
            );
        }

        if ($routePath === '/addressbooks') {
            return array(
                'type' => 'addressbooks',
                'href' => commonCardDavBuildHref('addressbooks/'),
            );
        }

        if ($routePath === '/addressbooks/' . $viewerUserId) {
            return array(
                'type' => 'addressbook-home',
                'href' => commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/'),
            );
        }

        if ($routePath === '/addressbooks/' . $viewerUserId . '/members') {
            return array(
                'type' => 'addressbook',
                'href' => commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/members/'),
            );
        }

        if (preg_match('#^/addressbooks/' . preg_quote((string)$viewerUserId, '#') . '/members/(user-\d+\.vcf)$#', $routePath, $matches)) {
            $cardName = (string)$matches[1];
            $contactMap = commonCardDavLoadContactsForViewer($viewerUserId);
            if (!isset($contactMap[$cardName])) {
                return null;
            }
            return $contactMap[$cardName];
        }

        return null;
    }
}

if (!function_exists('commonCardDavListChildResources')) {
    function commonCardDavListChildResources(array $resource, User $viewer)
    {
        $viewerUserId = (int)$viewer->getId();

        switch ((string)($resource['type'] ?? '')) {
            case 'root':
                return array(
                    array(
                        'type' => 'principals',
                        'href' => commonCardDavBuildHref('principals/'),
                    ),
                    array(
                        'type' => 'addressbooks',
                        'href' => commonCardDavBuildHref('addressbooks/'),
                    ),
                );

            case 'principals':
                return array(
                    array(
                        'type' => 'principal',
                        'href' => commonCardDavBuildHref('principals/' . $viewerUserId . '/'),
                    ),
                );

            case 'addressbooks':
                return array(
                    array(
                        'type' => 'addressbook-home',
                        'href' => commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/'),
                    ),
                );

            case 'addressbook-home':
                return array(
                    array(
                        'type' => 'addressbook',
                        'href' => commonCardDavBuildHref('addressbooks/' . $viewerUserId . '/members/'),
                    ),
                );

            case 'addressbook':
                return array_values(commonCardDavLoadContactsForViewer($viewerUserId));
        }

        return array();
    }
}

if (!function_exists('commonCardDavNormalizeDepth')) {
    function commonCardDavNormalizeDepth()
    {
        $depthHeader = strtolower(trim((string)($_SERVER['HTTP_DEPTH'] ?? '0')));
        if ($depthHeader === '1') {
            return 1;
        }
        return 0;
    }
}

if (!function_exists('commonCardDavHandlePropfind')) {
    function commonCardDavHandlePropfind(User $viewer, array $resource)
    {
        $xmlPayload = commonCardDavReadXmlBody();
        $request = commonCardDavParsePropRequest($xmlPayload['xml']);
        $propnameMode = $request['mode'] === 'propname';
        $requestedProperties = $request['mode'] === 'prop'
            ? (array)$request['properties']
            : array();
        $contactMap = commonCardDavLoadContactsForViewer((int)$viewer->getId());
        commonCardDavSetDebugValue('request_method', 'PROPFIND');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('request_depth', (string)commonCardDavNormalizeDepth());
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));
        $context = array('contactMap' => $contactMap);

        $responses = array(
            commonCardDavBuildResponseForResource($resource, $viewer, $requestedProperties, $propnameMode, $context),
        );

        if (commonCardDavNormalizeDepth() === 1) {
            foreach (commonCardDavListChildResources($resource, $viewer) as $childResource) {
                $responses[] = commonCardDavBuildResponseForResource($childResource, $viewer, $requestedProperties, $propnameMode, $context);
            }
        }

        commonCardDavSendMultistatus($responses, $propnameMode);
    }
}

if (!function_exists('commonCardDavMatchesQueryFilter')) {
    function commonCardDavMatchesQueryFilter(array $contact, ?DOMDocument $document = null)
    {
        if (!$document || !$document->documentElement) {
            return true;
        }

        $root = $document->documentElement;
        $filters = array();

        foreach ($root->childNodes as $child) {
            if (!$child instanceof DOMElement || commonCardDavClarkName($child) !== '{urn:ietf:params:xml:ns:carddav}filter') {
                continue;
            }

            foreach ($child->childNodes as $filterNode) {
                if (!$filterNode instanceof DOMElement || commonCardDavClarkName($filterNode) !== '{urn:ietf:params:xml:ns:carddav}prop-filter') {
                    continue;
                }

                $propertyName = strtoupper(trim((string)$filterNode->getAttribute('name')));
                if ($propertyName === '') {
                    continue;
                }

                foreach ($filterNode->childNodes as $textMatchNode) {
                    if (!$textMatchNode instanceof DOMElement || commonCardDavClarkName($textMatchNode) !== '{urn:ietf:params:xml:ns:carddav}text-match') {
                        continue;
                    }

                    $filters[] = array(
                        'property' => $propertyName,
                        'value' => trim((string)$textMatchNode->textContent),
                    );
                }
            }
        }

        if (count($filters) === 0) {
            return true;
        }

        $searchValues = isset($contact['searchValues']) && is_array($contact['searchValues'])
            ? $contact['searchValues']
            : array();

        foreach ($filters as $filter) {
            $propertyValues = array_map('strval', (array)($searchValues[$filter['property']] ?? array()));
            $needle = function_exists('mb_strtolower')
                ? mb_strtolower((string)$filter['value'], 'UTF-8')
                : strtolower((string)$filter['value']);

            if ($needle === '') {
                continue;
            }

            $matched = false;
            foreach ($propertyValues as $propertyValue) {
                $haystack = function_exists('mb_strtolower')
                    ? mb_strtolower((string)$propertyValue, 'UTF-8')
                    : strtolower((string)$propertyValue);
                if (strpos($haystack, $needle) !== false) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('commonCardDavBuildReportPropRequest')) {
    function commonCardDavBuildReportPropRequest(?DOMDocument $document = null)
    {
        if (!$document || !$document->documentElement) {
            return array(
                'mode' => 'prop',
                'properties' => array(
                    '{DAV:}getetag',
                    '{urn:ietf:params:xml:ns:carddav}address-data',
                ),
            );
        }

        return commonCardDavParsePropRequest($document);
    }
}

if (!function_exists('commonCardDavHandleAddressbookQueryReport')) {
    function commonCardDavHandleAddressbookQueryReport(User $viewer, array $resource, ?DOMDocument $document = null)
    {
        $contactMap = commonCardDavLoadContactsForViewer((int)$viewer->getId());
        $reportRequest = commonCardDavBuildReportPropRequest($document);
        $requestedProperties = (array)$reportRequest['properties'];
        $context = array('contactMap' => $contactMap);
        $responses = array();
        commonCardDavSetDebugValue('request_method', 'REPORT-query');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));

        foreach ($contactMap as $contact) {
            if (!commonCardDavMatchesQueryFilter($contact, $document)) {
                continue;
            }

            $responses[] = commonCardDavBuildResponseForResource($contact, $viewer, $requestedProperties, false, $context);
        }

        commonCardDavSetDebugValue('report_matches', (string)count($responses));
        commonCardDavSendMultistatus($responses, false);
    }
}

if (!function_exists('commonCardDavExtractRequestedHrefs')) {
    function commonCardDavExtractRequestedHrefs(?DOMDocument $document = null)
    {
        if (!$document || !$document->documentElement) {
            return array();
        }

        $hrefs = array();
        foreach ($document->documentElement->childNodes as $child) {
            if ($child instanceof DOMElement && commonCardDavClarkName($child) === '{DAV:}href') {
                $hrefs[] = trim((string)$child->textContent);
            }
        }

        return commonCardDavCollectUniqueStrings($hrefs);
    }
}

if (!function_exists('commonCardDavHandleAddressbookMultigetReport')) {
    function commonCardDavHandleAddressbookMultigetReport(User $viewer, array $resource, ?DOMDocument $document = null)
    {
        $contactMap = commonCardDavLoadContactsForViewer((int)$viewer->getId());
        $hrefIndex = array();
        foreach ($contactMap as $contact) {
            $hrefIndex[(string)$contact['href']] = $contact;
        }

        $requestedProperties = (array)commonCardDavBuildReportPropRequest($document)['properties'];
        $context = array('contactMap' => $contactMap);
        $responses = array();
        commonCardDavSetDebugValue('request_method', 'REPORT-multiget');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));

        foreach (commonCardDavExtractRequestedHrefs($document) as $href) {
            if (!isset($hrefIndex[$href])) {
                $responses[] = array(
                    'href' => $href,
                    'statusOnly' => 'HTTP/1.1 404 Not Found',
                );
                continue;
            }

            $responses[] = commonCardDavBuildResponseForResource($hrefIndex[$href], $viewer, $requestedProperties, false, $context);
        }

        commonCardDavSetDebugValue('report_matches', (string)count($responses));
        commonCardDavSendMultistatus($responses, false);
    }
}

if (!function_exists('commonCardDavHandleSyncCollectionReport')) {
    function commonCardDavHandleSyncCollectionReport(User $viewer, array $resource, ?DOMDocument $document = null)
    {
        $contactMap = commonCardDavLoadContactsForViewer((int)$viewer->getId());
        $requestedProperties = (array)commonCardDavBuildReportPropRequest($document)['properties'];
        if (count($requestedProperties) === 0) {
            $requestedProperties = array('{DAV:}getetag');
        }

        $context = array('contactMap' => $contactMap);
        $responses = array();
        commonCardDavSetDebugValue('request_method', 'REPORT-sync');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));
        foreach ($contactMap as $contact) {
            $responses[] = commonCardDavBuildResponseForResource($contact, $viewer, $requestedProperties, false, $context);
        }

        commonCardDavSetDebugValue('report_matches', (string)count($responses));
        commonCardDavSendMultistatus(
            $responses,
            false,
            array(
                array(
                    'namespace' => 'DAV:',
                    'prefix' => 'd',
                    'localName' => 'sync-token',
                    'value' => commonCardDavBuildSyncToken((int)$viewer->getId(), $contactMap),
                ),
            )
        );
    }
}

if (!function_exists('commonCardDavHandleReport')) {
    function commonCardDavHandleReport(User $viewer, array $resource)
    {
        if ((string)($resource['type'] ?? '') !== 'addressbook') {
            commonCardDavSendStatusText(405, 'REPORT is only supported on the addressbook collection.');
        }

        $xmlPayload = commonCardDavReadXmlBody();
        $document = $xmlPayload['xml'];
        if (!$document || !$document->documentElement) {
            commonCardDavSendStatusText(400, 'Invalid REPORT body.');
        }

        $rootName = commonCardDavClarkName($document->documentElement);
        if ($rootName === '{urn:ietf:params:xml:ns:carddav}addressbook-query') {
            commonCardDavHandleAddressbookQueryReport($viewer, $resource, $document);
        }

        if ($rootName === '{urn:ietf:params:xml:ns:carddav}addressbook-multiget') {
            commonCardDavHandleAddressbookMultigetReport($viewer, $resource, $document);
        }

        if ($rootName === '{DAV:}sync-collection') {
            commonCardDavHandleSyncCollectionReport($viewer, $resource, $document);
        }

        commonCardDavSendStatusText(403, 'Unsupported REPORT.');
    }
}

if (!function_exists('commonCardDavSendCard')) {
    function commonCardDavSendCard(array $resource, $sendBody = true)
    {
        header('Content-Type: ' . (string)($resource['contentType'] ?? 'text/vcard; charset=utf-8'));
        header('ETag: ' . (string)($resource['etag'] ?? ''));
        header('Content-Length: ' . (string)($resource['contentLength'] ?? 0));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (!empty($resource['lastModified']) && $resource['lastModified'] instanceof DateTimeInterface) {
            header('Last-Modified: ' . commonCardDavFormatHttpDate($resource['lastModified']));
        }

        commonCardDavSetDebugValue('request_method', $sendBody ? 'GET' : 'HEAD');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'card'));
        http_response_code(200);
        commonCardDavSendDebugHeaders();
        if ($sendBody) {
            echo (string)($resource['vcard'] ?? '');
        }
        exit;
    }
}
