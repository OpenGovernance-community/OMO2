<?php
declare(strict_types=1);
// Disposable local accounts and sessions for test-local-browser.cjs; never an HTTP endpoint.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'org1.localtest.me';
require_once dirname(__DIR__) . '/shared_functions.php';
if (!envIsLocalRuntimeHost() || !str_ends_with((string)envValue('JITSI_URL', ''), 'meet.localtest.me')) {
    throw new RuntimeException('This fixture is reserved for local Jitsi.');
}
$file = sys_get_temp_dir() . '/omo-visio-browser-fixture.json';
$action = $argv[1] ?? '';
if ($action === 'delete') {
    $fixture = is_file($file) ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) : [];
    foreach ($fixture['users'] ?? [] as $identity) {
        $user = new \dbObject\User();
        if ($user->load((int)$identity['id']) && preg_match('/\Avisio-browser-[a-f0-9]+@example\.invalid\z/D', (string)$user->get('email'))) $user->delete();
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        session_id($identity['session']); session_start(); $_SESSION = []; session_destroy();
    }
    if (!empty($fixture['organization'])) {
        $organization = new \dbObject\Organization();
        if ($organization->load((int)$fixture['organization']) && str_starts_with((string)$organization->get('name'), 'Visio browser fixture ')) $organization->delete();
    }
    if (is_file($file)) unlink($file);
    echo "Local browser fixtures removed.\n";
    exit;
}
if ($action !== 'create' || is_file($file)) throw new RuntimeException('Use create with no existing fixture, or delete first.');
$fixture = ['origin' => 'https://org1.localtest.me', 'cookie' => session_name(), 'users' => []];
session_write_close();
try {
    foreach (['Owner', 'Member', 'Outsider'] as $name) {
        $user = new \dbObject\User();
        foreach (['email' => 'visio-browser-' . bin2hex(random_bytes(8)) . '@example.invalid', 'firstname' => $name, 'lastname' => 'Browser Fixture', 'active' => 1] as $key => $value) $user->set($key, $value);
        if (empty($user->save()['status'])) throw new RuntimeException('Unable to save fixture account.');
        session_id(bin2hex(random_bytes(16))); session_start();
        $_SESSION = ['currentUser' => $user->getId(), 'auth_security_version' => (int)$user->get('security_version')];
        $fixture['users'][] = ['id' => $user->getId(), 'session' => session_id()];
        session_write_close();
    }
    $organization = new \dbObject\Organization();
    $organization->set('name', 'Visio browser fixture ' . bin2hex(random_bytes(6)));
    if (empty($organization->save()['status'])) throw new RuntimeException('Unable to save fixture organization.');
    $fixture['organization'] = $organization->getId();
    foreach (array_slice($fixture['users'], 0, 2) as $identity) {
        $membership = new \dbObject\UserOrganization();
        foreach (['IDuser' => $identity['id'], 'IDorganization' => $organization->getId(), 'active' => 1] as $key => $value) $membership->set($key, $value);
        if (empty($membership->save()['status'])) throw new RuntimeException('Unable to save fixture membership.');
    }
} finally {
    file_put_contents($file, json_encode($fixture, JSON_THROW_ON_ERROR));
    chmod($file, 0600);
}
echo "Local browser fixtures created; private session data written outside the web root.\n";
