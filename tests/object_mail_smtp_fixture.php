<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Loopback-only SMTP sink; no forwarding, authentication or external connections.
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$server) throw new RuntimeException('Cannot start test SMTP sink.');
fwrite(STDOUT, stream_socket_get_name($server, false) . "\n"); fflush(STDOUT);
$deadline = time() + 180;
while (time() < $deadline) {
    $client = @stream_socket_accept($server, 1);
    if (!$client) continue;
    stream_set_timeout($client, 10);
    fwrite($client, "220 localhost test SMTP\r\n");
    $recipients = []; $data = '';
    while (($line = fgets($client)) !== false) {
        $command = strtoupper(strtok(trim($line), ' '));
        if ($command === 'EHLO' || $command === 'HELO') fwrite($client, "250 localhost\r\n");
        elseif ($command === 'MAIL') { $recipients = []; fwrite($client, "250 OK\r\n"); }
        elseif ($command === 'RCPT') { $recipients[] = trim($line); fwrite($client, "250 OK\r\n"); }
        elseif ($command === 'DATA') {
            fwrite($client, "354 End with dot\r\n"); $data = '';
            while (($line = fgets($client)) !== false && trim($line) !== '.') $data .= $line;
            file_put_contents($argv[1], json_encode(['recipients' => $recipients, 'data' => $data], JSON_THROW_ON_ERROR) . "\n", FILE_APPEND | LOCK_EX);
            fwrite($client, "250 Accepted\r\n");
        } elseif ($command === 'QUIT') { fwrite($client, "221 Bye\r\n"); break; }
        else fwrite($client, "250 OK\r\n");
    }
    fclose($client);
}
fclose($server);
