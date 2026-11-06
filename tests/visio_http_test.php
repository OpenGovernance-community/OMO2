<?php
declare(strict_types=1);
// Local CLI only: disposable web server, sessions and database fixtures.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'org1.localtest.me';
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\User;
use dbObject\VideoRoom;

function videoHttpExpect(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/visio-http-' . bin2hex(random_bytes(6));
mkdir($directory, 0700);
$router = $directory . '/router.php';
$settings = ['JITSI_ENABLED' => 'true', 'JITSI_URL' => 'https://meet.example.test', 'JITSI_APP_ID' => 'omo2',
    'JITSI_APP_SECRET' => bin2hex(random_bytes(32)), 'JITSI_XMPP_DOMAIN' => 'meet.jitsi'];
file_put_contents($router, '<?php require ' . var_export($root . '/shared_functions.php', true)
    . '; foreach (' . var_export($settings, true) . ' as $k=>$v) $_ENV[$k]=$v; '
    . '$_GET["room"]=preg_match("~^/visio/([a-f0-9]{32})~",$_SERVER["REQUEST_URI"],$m)?$m[1]:""; '
    . 'require ' . var_export($root . '/visio/index.php', true) . ';');
$process = null;
$users = [];
$sessionIds = [];
$room = null;
try {
    session_write_close();
    foreach (['Owner', 'Member'] as $name) {
        $user = new User();
        foreach (['email' => 'video-http-' . bin2hex(random_bytes(6)) . '@example.invalid',
            'firstname' => $name, 'lastname' => 'HTTP Fixture', 'active' => 1] as $key => $value) $user->set($key, $value);
        videoHttpExpect(!empty($user->save()['status']), 'Fixture user saved');
        $users[] = $user;
        session_id(bin2hex(random_bytes(16)));
        session_start();
        $_SESSION = ['currentUser' => $user->getId(), 'auth_security_version' => (int)$user->get('security_version')];
        $sessionIds[] = session_id();
        session_write_close();
    }
    $portSocket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($portSocket, false);
    fclose($portSocket);
    $process = proc_open([PHP_BINARY, '-S', $address, $router], [0 => ['pipe', 'r'], 1 => ['file', $directory . '/server.log', 'a'], 2 => ['file', $directory . '/server.log', 'a']], $pipes, $root);
    videoHttpExpect(is_resource($process), 'Local test server started');
    fclose($pipes[0]);
    $request = static function (string $path, ?string $session = null, ?array $post = null) use ($address): array {
        $curl = curl_init('http://' . $address . $path);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Host: org1.localtest.me', 'Origin: http://org1.localtest.me'],
            CURLOPT_COOKIE => $session ? session_name() . '=' . $session : '']);
        if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
        $response = curl_exec($curl);
        if ($response === false) throw new RuntimeException('HTTP test request failed: ' . curl_error($curl));
        $length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        return [curl_getinfo($curl, CURLINFO_RESPONSE_CODE), substr($response, 0, $length), substr($response, $length)];
    };
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $socket = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    [$status, , $body] = $request('/visio/', $sessionIds[0]);
    videoHttpExpect($status === 200 && preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $body, $match) === 1, 'Owner creation form and CSRF');
    $csrf = $match[1];
    [$status] = $request('/visio/', $sessionIds[0], ['action' => 'create', 'title' => 'Forbidden', '_csrf' => 'bad']);
    videoHttpExpect($status === 403, 'CSRF rejection');
    [$status, $headers] = $request('/visio/', $sessionIds[0], ['action' => 'create', 'title' => 'HTTP réunion', '_csrf' => $csrf, 'IDuser' => $users[1]->getId(), 'moderator' => 'true']);
    videoHttpExpect($status === 303 && preg_match('~Location: (/visio/([a-f0-9]{32}))~i', $headers, $match) === 1, 'Create redirects to clean invitation');
    $path = $match[1];
    $room = VideoRoom::findByInvitation($match[2]);
    videoHttpExpect($room && $room->isOwner((int)$users[0]->getId()), 'Posted owner ignored');
    foreach ([0 => true, 1 => false] as $index => $moderator) {
        [$status, $headers, $body] = $request($path, $sessionIds[$index]);
        videoHttpExpect($status === 200 && stripos($headers, 'no-store') !== false, 'Private page response');
        videoHttpExpect(preg_match('~<script id="visio-config" type="application/json">(.*?)</script>~s', $body, $match) === 1, 'Iframe configuration present');
        $client = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        videoHttpExpect($client['access']['moderator'] === $moderator, 'HTTP role derives from session and room');
    }
    [$status, $headers, $body] = $request($path);
    videoHttpExpect($status === 200 && !str_contains($body, 'id="visio-config"') && str_contains($body, 'name="name"'), 'Guest sees name form without a token');
    preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $body, $csrfMatch);
    preg_match('/Set-Cookie: ' . preg_quote(session_name(), '/') . '=([^;]+)/i', $headers, $cookie);
    videoHttpExpect(isset($cookie[1]), 'Guest session cookie');
    $sessionIds[] = $cookie[1];
    [$status, , $body] = $request($path, $cookie[1], ['action' => 'join', '_csrf' => $csrfMatch[1], 'name' => '']);
    videoHttpExpect($status === 400 && str_contains($body, 'name="name"'), 'Invalid guest name retains form');
    [$status] = $request($path, $cookie[1], ['action' => 'join', '_csrf' => $csrfMatch[1], 'name' => 'Guest', 'moderator' => 'true']);
    videoHttpExpect($status === 303, 'Guest join redirects');
    [$status, , $body] = $request($path . '?join=1', $cookie[1]);
    preg_match('~<script id="visio-config" type="application/json">(.*?)</script>~s', $body, $match);
    $client = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
    videoHttpExpect($status === 200 && $client['access']['moderator'] === false, 'Guest cannot request moderator role');
    echo "OK: HTTP creation, CSRF, session identities, guest validation and clean invitations.\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach ($users as $user) $user->delete(); // FK cascade also removes the fixture room.
    foreach ($sessionIds as $id) {
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        session_id($id); session_start(); $_SESSION = []; session_destroy();
    }
    foreach ([$router, $directory . '/server.log'] as $file) if (is_file($file)) unlink($file);
    rmdir($directory);
}
