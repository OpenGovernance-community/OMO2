<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\Tip;

function tipsHttp(string $path, array $query, ?array $post = null, string $tipCookie = '', bool $csrf = true, string $imagePath = ''): array
{
    if ($post !== null && $csrf) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id() . ($tipCookie !== '' ? '; ' . $tipCookie : '');
    session_write_close();
    $curl = curl_init('https://localhost' . $path . '?' . http_build_query($query));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) {
        if ($imagePath !== '') $post['image_file'] = new CURLFile($imagePath, 'image/png', 'tip.png');
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $imagePath !== '' ? $post : http_build_query($post)]);
    }
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    return ['status' => $status, 'body' => $body, 'json' => json_decode((string)$body, true)];
}

$before = $_SESSION;
$items = mcpFixtures();
$imagePath = '';
$savedImage = '';
try {
    $uid = (int)$items['user']->getId();
    $oid = (int)$items['org']->getId();
    $items['tip'] = mcpFixture(Tip::class, ['text' => 'HTTP test tip', 'link' => '#|faq', 'active' => 1]);
    $items['inactive_tip'] = mcpFixture(Tip::class, ['text' => 'Hidden tip', 'link' => '/omo/', 'active' => 0]);
    foreach (['javascript:alert(1)', '//evil.example', '/\\evil.example', "https://example.org/\nfoo"] as $url) {
        mcpCheck(!Tip::isSafeLink($url), 'Unsafe tip URL is rejected.');
    }
    foreach (['#|faq-1', '/omo/#parameters', 'https://example.org/help?a=1'] as $url) {
        mcpCheck(Tip::isSafeLink($url), 'Internal and external help URLs are allowed.');
    }
    $invalid = new Tip();
    $invalid->loadFromArray(['text' => 'Unsafe', 'link' => 'javascript:alert(1)', 'active' => 1]);
    mcpCheck(empty($invalid->save()['status']), 'The model protects every save entry point.');
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version')];
    $query = ['oid' => $oid];
    $response = tipsHttp('/omo/api/tips.php', $query);
    mcpCheck($response['status'] === 200 && !empty($response['json']['tip']), 'An authenticated member receives a tip: ' . $response['status'] . ' ' . $response['body']);
    $cookieName = $response['json']['cookieName'];
    mcpCheck($cookieName === 'omo_tip_' . $uid, 'Daily cookie is account-specific.');
    $cookie = $cookieName . '=' . rawurlencode(json_encode(['day' => $response['json']['day'], 'id' => $items['tip']->getId(), 'seen' => true]));
    $response = tipsHttp('/omo/api/tips.php', $query, null, $cookie);
    mcpCheck($response['json']['tip']['id'] === (int)$items['tip']->getId(), 'Reload restores the active tip ID from the daily cookie.');
    mcpCheck(strtotime($response['json']['expires']) > time(), 'Cookie expiration is a future UTC timestamp.');
    $response = tipsHttp('/omo/api/tips.php', $query + ['next' => '1'], null, $cookie);
    mcpCheck($response['json']['tip']['id'] !== (int)$items['tip']->getId(), 'Next avoids immediate repetition.');
    $hiddenCookie = $cookieName . '=' . rawurlencode(json_encode(['day' => $response['json']['day'], 'id' => $items['inactive_tip']->getId(), 'seen' => true]));
    $response = tipsHttp('/omo/api/tips.php', $query, null, $hiddenCookie);
    mcpCheck($response['json']['tip']['id'] !== (int)$items['inactive_tip']->getId(), 'Unpublished cookie entries cannot be restored.');
    $save = '/omo/api/parameters/tips/save.php';
    $post = ['text' => 'Updated HTTP tip', 'link' => 'https://example.org/faq', 'active' => '1', 'id' => $items['tip']->getId()];
    mcpCheck(tipsHttp('/omo/api/parameters/tips/index.php', $query)['status'] === 403, 'Ordinary members cannot manage the catalog.');
    mcpCheck(tipsHttp($save, $query, $post)['status'] === 403, 'Ordinary members cannot save global tips.');
    mcpCheck(tipsHttp('/omo/api/tips.php', ['oid' => $items['other_org']->getId()])['status'] === 403, 'Tips endpoint checks organization access.');
    $items['user']->set('siteadmin', 1);
    mcpCheck(!empty($items['user']->save()['status']), 'Fixture becomes a site administrator.');
    $_SESSION['isSiteAdminModeEnabled'] = true;
    $response = tipsHttp('/omo/api/parameters/tips/index.php', $query + ['id' => $items['tip']->getId()]);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], "name='text'"), 'Super administrator gets the adminEdit form.');
    mcpCheck(tipsHttp($save, $query, $post, '', false)['status'] === 403, 'Catalog saves require CSRF protection.');
    mcpCheck(tipsHttp($save, $query, array_replace($post, ['link' => 'javascript:alert(1)']))['status'] === 422, 'Catalog rejects unsafe links over HTTP.');
    mcpCheck(tipsHttp($save, $query, array_replace($post, ['text' => str_repeat('x', 501)]))['status'] === 422, 'Catalog limits short text length.');
    $response = tipsHttp($save, $query, $post);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Super administrator can save tips.');
    $tip = new Tip();
    $tip->load($items['tip']->getId(), true);
    mcpCheck($tip->get('text') === 'Updated HTTP tip', 'Saved text is persisted.');
    $imagePath = tempnam(sys_get_temp_dir(), 'tip-image-');
    $image = imagecreatetruecolor(80, 45);
    imagepng($image, $imagePath);
    unset($image);
    $response = tipsHttp($save, $query, array_replace($post, ['image' => 'newimage']), '', true, $imagePath);
    mcpCheck($response['status'] === 200, 'The adminEdit image upload is accepted.');
    $tip->load($items['tip']->getId(), true);
    $savedImage = (string)$tip->get('image');
    mcpCheck(str_starts_with($savedImage, '/img/upload/tip/'), 'The optional image is stored by dbObject.');
    mcpCheck(tipsHttp('/omo/api/tips.php', $query, null, $cookie)['json']['tip']['image'] === $savedImage, 'The illustration is returned with its tip.');
    $response = tipsHttp($save, $query, array_replace($post, ['image' => '', 'active' => '0']));
    mcpCheck($response['status'] === 200, 'A published tip can be hidden and its image removed.');
    $tip->load($items['tip']->getId(), true);
    mcpCheck(!$tip->get('active') && !$tip->get('image'), 'Publication and image removal are persisted.');
    $response = tipsHttp($save, $query, ['text' => 'Created over HTTP', 'link' => '/omo/', 'active' => '1']);
    mcpCheck($response['status'] === 200 && !empty($response['json']['id']), 'Super administrator creates a new catalog entry.');
    $items['created_tip'] = new Tip();
    $items['created_tip']->load($response['json']['id']);
    echo "tips_http_test: OK\n";
} finally {
    $_SESSION = $before;
    mcpCleanup($items);
    if ($imagePath !== '' && is_file($imagePath)) unlink($imagePath);
    if (str_starts_with($savedImage, '/img/upload/tip/') && is_file(dirname(__DIR__) . $savedImage)) unlink(dirname(__DIR__) . $savedImage);
}
