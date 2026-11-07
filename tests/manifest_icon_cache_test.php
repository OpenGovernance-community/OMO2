<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/omo/manifest_icons.php';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

function manifestIconHttp(string $path, array $headers = [], bool $head = false): array
{
    $responseHeaders = [];
    $curl = curl_init('https://localtest.me' . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_PROXY => '', CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_NOBODY => $head,
        CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
            }
            return strlen($line);
        },
    ]);
    $body = curl_exec($curl);
    mcpCheck(is_string($body), 'Local icon request failed: ' . curl_error($curl));
    return ['status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), 'headers' => $responseHeaders, 'body' => $body];
}

function manifestIconFixtureImage(string $path, int $red, int $blue): void
{
    $image = imagecreatetruecolor(80, 40);
    imagefill($image, 0, 0, imagecolorallocate($image, $red, 40, $blue));
    imagepng($image, $path, 0);
}

$items = [];
$cacheFiles = [];
$logo = '/img/upload/organization/manifest-icon-test-' . bin2hex(random_bytes(8)) . '.png';
$logoPath = dirname(__DIR__) . $logo;
$cacheDirectory = sys_get_temp_dir() . '/omo-manifest-icons-'
    . substr(hash('sha256', dirname(__DIR__) . '/omo'), 0, 16);
try {
    if (!is_dir(dirname($logoPath))) mkdir(dirname($logoPath), 0755, true);
    manifestIconFixtureImage($logoPath, 160, 20);
    foreach (['first' => '#ff0000', 'second' => '#0000ff'] as $key => $color) {
        $items[$key] = mcpFixture(McpTestOrganization::class,
            ['name' => 'Icon cache fixture ' . $key, 'logo' => $logo, 'color' => $color]);
    }
    $context = ['id' => (int)$items['first']->getId(), 'logo' => $logo, 'color' => '#ff0000'];
    $other = array_replace($context, ['id' => (int)$items['second']->getId(), 'color' => '#0000ff']);
    foreach ([$context, $other] as $variant) {
        foreach ([[192, 'any'], [512, 'maskable']] as [$size, $purpose]) {
            $cacheFiles[] = $cacheDirectory . '/' . hash('sha256', $variant['id'] . ':' . $size . ':' . $purpose) . '.cache';
        }
    }
    $url = omoManifestBuildIconUrl($context, 192);
    $cold = manifestIconHttp($url);
    mcpCheck($cold['status'] === 200 && getimagesizefromstring($cold['body'])[0] === 192, 'The icon is a 192px PNG.');
    mcpCheck($cold['headers']['cache-control'] === 'private, max-age=31536000, immutable', 'Pinned versions allow browser caching.');
    mcpCheck(!isset($cold['headers']['pragma']) && !isset($cold['headers']['expires']), 'Legacy cache prevention is removed.');
    $manifest = manifestIconHttp('/omo/manifest.php?oid=' . $context['id']);
    mcpCheck($manifest['status'] === 200 && json_decode($manifest['body'], true)['icons'][0]['src'] === $url,
        'The live manifest uses the same content version as the page head and icon endpoint.');
    mcpCheck(is_file($cacheFiles[0]), 'The generated PNG is cached on disk.');
    touch($cacheFiles[0], time() - 100);
    clearstatcache();
    $cacheTime = filemtime($cacheFiles[0]);
    $warm = manifestIconHttp($url);
    clearstatcache();
    mcpCheck($warm['body'] === $cold['body'] && filemtime($cacheFiles[0]) === $cacheTime, 'A cache hit reuses the PNG without rewriting it.');
    $conditional = manifestIconHttp($url, ['If-None-Match: "other", W/' . $cold['headers']['etag']]);
    mcpCheck($conditional['status'] === 304 && $conditional['body'] === '', 'ETag lists and weak validators avoid retransmission.');
    mcpCheck(manifestIconHttp($url, [], true)['body'] === '', 'HEAD returns no image body.');

    $maskable = manifestIconHttp(omoManifestBuildIconUrl($context, 512, 'maskable'));
    $otherIcon = manifestIconHttp(omoManifestBuildIconUrl($other, 512, 'maskable'), ['If-None-Match: ' . $maskable['headers']['etag']]);
    mcpCheck($otherIcon['status'] === 200 && $otherIcon['body'] !== $maskable['body'], 'Organization variants must not share the wrong image.');
    $pixels = imagecreatefromstring($otherIcon['body']);
    mcpCheck(imagesx($pixels) === 512 && (imagecolorat($pixels, 0, 0) & 0xffffff) === 0x0000ff, 'Maskable icons retain the organization color and size.');
    $unpinned = manifestIconHttp('/omo/manifest_icon.php?size=192&v=' . omoManifestBuildIconVersionToken($context));
    mcpCheck($unpinned['headers']['cache-control'] === 'private, no-cache' && $unpinned['headers']['vary'] === 'Cookie', 'Context-dependent URLs must always revalidate.');

    $oldVersion = omoManifestBuildIconVersionToken($context);
    $originalTime = filemtime($logoPath);
    $originalSize = filesize($logoPath);
    manifestIconFixtureImage($logoPath, 20, 160);
    touch($logoPath, $originalTime);
    clearstatcache();
    mcpCheck(filesize($logoPath) === $originalSize && omoManifestBuildIconVersionToken($context) !== $oldVersion, 'Replacing a logo at the same path, size and timestamp changes its version.');
    $changed = manifestIconHttp(omoManifestBuildIconUrl($context, 192), ['If-None-Match: ' . $cold['headers']['etag']]);
    mcpCheck($changed['status'] === 200 && $changed['body'] !== $cold['body'], 'A changed logo invalidates the server and browser variants.');
    mcpCheck(manifestIconHttp($url)['headers']['cache-control'] === 'private, no-cache', 'Stale version URLs cannot receive immutable caching.');
    $items['first']->set('color', '#00ff00');
    mcpCheck(!empty($items['first']->save()['status']), 'Change the organization color.');
    $context['color'] = '#00ff00';
    $green = manifestIconHttp(omoManifestBuildIconUrl($context, 512, 'maskable'));
    $pixels = imagecreatefromstring($green['body']);
    mcpCheck((imagecolorat($pixels, 0, 0) & 0xffffff) === 0x00ff00, 'Color changes invalidate the maskable variant.');
    $debug = manifestIconHttp($url . '&debug=1');
    mcpCheck($debug['headers']['cache-control'] === 'no-store' && json_decode($debug['body'], true)['result'] === 'generated', 'Debug bypasses caching and keeps diagnostics.');
    unlink($logoPath);
    $fallback = manifestIconHttp(omoManifestBuildIconUrl($context, 192));
    mcpCheck($fallback['status'] === 200 && $fallback['headers']['cache-control'] === 'private, no-cache'
        && $fallback['body'] === file_get_contents(dirname(__DIR__) . '/omo/icons/icon-192.png'), 'A missing logo serves a revalidated fallback, never the old cached logo.');
    mcpCheck(manifestIconHttp(omoManifestBuildIconUrl($context, 192), ['If-None-Match: ' . $fallback['headers']['etag']])['status'] === 304, 'Fallback images also support revalidation.');
    echo "manifest_icon_cache_test: OK (disk/browser cache, variants, logo/color invalidation, 304, HEAD, debug, fallback)\n";
} finally {
    if (is_file($logoPath)) unlink($logoPath);
    foreach ($cacheFiles as $path) { if (is_file($path)) unlink($path); }
    mcpCleanup($items);
}
