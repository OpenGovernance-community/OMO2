<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$url = trim((string)envValue('VISIO_TRAFFIC_URL', ''));
if (!preg_match('~\Ahttps?://~', $url)) { fwrite(STDERR, "Set VISIO_TRAFFIC_URL to the private JVB debug URL.\n"); exit(1); }
$watch = in_array('--watch', $argv, true);
do {
    try {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false]);
        $body = curl_exec($curl);
        if (!is_string($body) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) throw new RuntimeException('JVB request failed');
        $payload = json_decode($body, true, 128, JSON_THROW_ON_ERROR);
        unset($curl);
        $result = \dbObject\VideoTraffic::ingest(\dbObject\VideoTraffic::observations($payload, $url));
        fwrite(STDOUT, gmdate('c') . ' ' . json_encode($result) . PHP_EOL);
    } catch (Throwable $error) {
        try { \dbObject\VideoTrafficCollector::failure(); } catch (Throwable $ignored) {}
        fwrite(STDERR, gmdate('c') . ' ' . $error->getMessage() . PHP_EOL);
        if (!$watch) exit(1);
    }
    if ($watch) sleep(30);
} while ($watch);
