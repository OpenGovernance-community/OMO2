<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/topbar.php';

use dbObject\{DbObject, Organization};

function topbarHomeCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function topbarHomeLinks(array $context, array $options = []): array
{
    ob_start();
    commonRenderTopbar(array_replace([
        'organization' => $context, 'userName' => 'Test',
        'profile' => ['enabled' => false, 'data' => ['displayName' => 'Test', 'email' => 'test@example.invalid', 'initials' => 'T']],
    ], $options));
    $html = (string)ob_get_clean();
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    $links = [];
    foreach (['logo', 'name'] as $part) {
        $links[$part] = $xpath->query('//a[contains(@class, "common-topbar__brand-link--' . $part . '")]')->item(0)?->getAttribute('href');
    }
    return $links;
}

$_SESSION = [];
$_GET = [];
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/omo/?cid=123';
$_ENV['ORGANIZATION_SUBDOMAIN_ROUTING'] = 'true';
foreach ([
    ['omo2.org', 'instantz.omo2.org', 'omo2.org', 'instantz'],
    ['omo2.org', 'omo2.org', 'omo2.org', ''],
    ['beta.opengov.tools', 'beta.opengov.tools', 'beta.opengov.tools', ''],
    ['dev.opengov.tools', 'dev.opengov.tools', 'dev.opengov.tools', ''],
    ['beta.opengov.tools', 'instantz.beta.opengov.tools', 'beta.opengov.tools', 'instantz'],
    ['dev.opengov.tools', 'instantz.dev.opengov.tools', 'dev.opengov.tools', 'instantz'],
    ['preview.team.example.org', 'preview.team.example.org', 'preview.team.example.org', ''],
    ['preview.team.example.org', 'instantz.preview.team.example.org:8443', 'preview.team.example.org:8443', 'instantz'],
    ['omo2.org', 'beta.opengov.tools', 'beta.opengov.tools', ''],
    ['omo2.org', 'dev.opengov.tools', 'dev.opengov.tools', ''],
    ['omo2.org', 'deploy.opengov.tools', 'deploy.opengov.tools', ''],
    ['opengov.tools', 'beta.opengov.tools', 'beta.opengov.tools', ''],
    ['opengov.tools', 'dev.opengov.tools', 'dev.opengov.tools', ''],
    ['opengov.tools', 'instantz.beta.opengov.tools', 'beta.opengov.tools', 'instantz'],
    ['omo2.org', 'notomo2.org', 'notomo2.org', ''],
    ['', 'test.localhost:8080', 'localhost:8080', 'test'],
] as [$configuredRoot, $host, $expectedRoot, $expectedSubdomain]) {
    $_ENV['COOKIE_ROOT_HOST'] = $configuredRoot;
    $_SERVER['HTTP_HOST'] = $host;
    topbarHomeCheck(commonGetRootHost() === $expectedRoot, 'Wrong root for ' . $host);
    topbarHomeCheck(commonGetRequestSubdomain($host) === $expectedSubdomain, 'Wrong organization subdomain for ' . $host);
    $context = ['isValid' => true, 'id' => 42, 'name' => 'Test organization', 'host' => commonGetRequestHost(), 'routeMode' => 'host'];
    $links = topbarHomeLinks($context);
    topbarHomeCheck($links === ['logo' => 'https://' . $host . '/omo/', 'name' => 'https://' . $host . '/omo/'], 'Brand links must stay on the current organization or environment root: ' . $host);
}

$_ENV['COOKIE_ROOT_HOST'] = 'omo2.org';
$_SERVER['HTTP_HOST'] = 'instantz.omo2.org';
$context = ['isValid' => true, 'id' => 42, 'name' => 'Test', 'host' => commonGetRequestHost(), 'routeMode' => 'host'];
topbarHomeCheck(topbarHomeLinks($context, ['brandLogoHref' => '/omo/share.php?token=test'])['logo'] === '/omo/share.php?token=test', 'Explicit public-share links must be preserved');
$context['isValid'] = false;
topbarHomeCheck(topbarHomeLinks($context)['logo'] === 'https://omo2.org/omo/', 'Invalid organization must keep the catalog link');
$_SERVER['HTTP_HOST'] = 'beta.opengov.tools';
$context = ['isValid' => true, 'id' => 42, 'name' => 'Test', 'host' => commonGetRequestHost(), 'routeMode' => 'path'];
topbarHomeCheck(topbarHomeLinks($context)['logo'] === 'https://beta.opengov.tools/omo/', 'Path routing on beta keeps the beta catalog');

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organization = new Organization();
    $organization->set('name', 'Topbar routing fixture');
    $organization->set('shortname', 'topbar-' . bin2hex(random_bytes(6)));
    topbarHomeCheck(!empty($organization->save()['status']), 'Organization fixture failed');
    foreach (['beta.opengov.tools', 'dev.opengov.tools', 'preview.team.example.org'] as $root) {
        $_ENV['COOKIE_ROOT_HOST'] = $root;
        $_SERVER['HTTP_HOST'] = $root;
        $context = commonResolveOrganizationContext((int)$organization->getId());
        topbarHomeCheck($context['isValid'] && commonGetRequestSubdomain() === '', 'Configured root must resolve as the catalog, not an organization subdomain');
        $_SERVER['HTTP_HOST'] = $organization->get('shortname') . '.' . $root;
        $context = commonResolveOrganizationContext(0);
        topbarHomeCheck($context['isValid'] && $context['id'] === (int)$organization->getId(), 'Organization lookup must reuse the configured root');
        topbarHomeCheck(topbarHomeLinks($context)['logo'] === 'https://' . $_SERVER['HTTP_HOST'] . '/omo/', 'Resolved organization keeps its own home link');
    }
    echo "PASS topbar organization home, configured roots, beta/dev/deploy, ports, path routing and explicit link overrides\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
