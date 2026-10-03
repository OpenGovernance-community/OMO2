<?php
// Download an agent-supplied file without forwarding OMO credentials or accessing private networks.
const OMO_MCP_FILE_MAX_BYTES = 20 * 1024 * 1024;

function omoMcpFileTarget(string $url): array
{
    $parts = parse_url($url);
    if (strlen($url) > 8192 || !filter_var($url, FILTER_VALIDATE_URL) || !$parts
        || ($parts['scheme'] ?? '') !== 'https' || (int)($parts['port'] ?? 443) !== 443
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
        || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) throw new DomainException('File URL must be public HTTPS without credentials.');
    $host = strtolower($parts['host']);
    // Use checked IPv4 addresses only, pinned for the actual connection (including redirects).
    $addresses = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? [$host] : (gethostbynamel($host) ?: []);
    if (!$addresses) throw new DomainException('File host unavailable.');
    foreach ($addresses as $address) {
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE)
            || (int)explode('.', $address)[0] >= 224) {
            throw new DomainException('Private or reserved file hosts are unavailable.');
        }
    }
    return ['host' => $host, 'address' => $addresses[0]];
}

function omoMcpDownloadFile(array $file, string $fallbackName): array
{
    $path = tempnam(sys_get_temp_dir(), 'omo-mcp-');
    if ($path === false) throw new DomainException('Temporary file storage unavailable.');
    $stream = fopen($path, 'w+b');
    if ($stream === false) { unlink($path); throw new DomainException('Temporary file storage unavailable.'); }
    try {
        $url = $file['download_url']; $deadline = microtime(true) + 30;
        for ($redirects = 0; $redirects <= 4; $redirects++) {
            $target = omoMcpFileTarget($url);
            $remaining = (int)(($deadline - microtime(true)) * 1000);
            if ($remaining <= 0) throw new DomainException('File download timed out. Request a fresh file URL and retry.');
            ftruncate($stream, 0); rewind($stream); $size = 0; $location = ''; $oversized = false;
            $curl = curl_init($url);
            curl_setopt_array($curl, [CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_PROXY => '', CURLOPT_RESOLVE => [$target['host'] . ':443:' . $target['address']],
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_CONNECTTIMEOUT_MS => min(5000, $remaining),
                CURLOPT_TIMEOUT_MS => $remaining, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/octet-stream', 'User-Agent: OMO MCP document import'],
                CURLOPT_WRITEFUNCTION => static function ($handle, $chunk) use ($stream, &$size, &$oversized) {
                    $size += strlen($chunk);
                    if ($size > OMO_MCP_FILE_MAX_BYTES) { $oversized = true; return 0; }
                    return fwrite($stream, $chunk);
                },
                CURLOPT_HEADERFUNCTION => static function ($handle, $line) use (&$location) {
                    if (stripos($line, 'Location:') === 0) $location = trim(substr($line, 9));
                    return strlen($line);
                }]);
            $ok = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            unset($curl);
            if ($oversized) throw new DomainException('File exceeds the 20 MiB import limit.');
            if ($ok === false) throw new DomainException('File download failed. Request a fresh file URL and retry.');
            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                if ($redirects === 4 || $location === '') throw new DomainException('File redirect unavailable.');
                $origin = 'https://' . $target['host'];
                if (str_starts_with($location, '//')) $location = 'https:' . $location;
                elseif (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $location)) {
                    $location = $origin . (str_starts_with($location, '/') ? $location
                        : substr(parse_url($url, PHP_URL_PATH) ?: '/', 0, strrpos(parse_url($url, PHP_URL_PATH) ?: '/', '/') + 1) . $location);
                }
                $url = $location; continue;
            }
            if ($status !== 200 || $size === 0) throw new DomainException('File download unavailable (empty file or expired URL).');
            fclose($stream); $stream = null;
            $name = $file['file_name'] ?? $fallbackName;
            if ($name === '') $name = $fallbackName;
            return ['error' => UPLOAD_ERR_OK, 'tmp_name' => $path, 'name' => $name,
                'type' => mime_content_type($path) ?: 'application/octet-stream', 'size' => $size];
        }
        throw new DomainException('File redirect unavailable.');
    } catch (Throwable $error) {
        if (is_resource($stream)) fclose($stream);
        unlink($path);
        throw $error;
    }
}
