<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared/telegram_addressing.php';
function telegramApiRequest($method, $params = []) {
    if ($method !== 'getMe') throw new RuntimeException('Unexpected API call');
    return ['ok' => true, 'result' => ['id' => 123456, 'is_bot' => true, 'username' => 'OMOTestBot']];
}
function checkRoute(array $message, ?array $expected, string $label): void {
    $actual = telegramTextRoute($message);
    if ($actual !== $expected) throw new RuntimeException($label . ': ' . json_encode($actual));
}
$base = ['from' => ['id' => 42], 'chat' => ['id' => -123, 'type' => 'supergroup']];
foreach ([
    ['text' => 'Quels rôles existent ?'],
    ['text' => '/ask Quels rôles existent ?'],
    ['text' => '/connect'],
    ['text' => '/ask@OtherBot Quels rôles existent ?'],
    ['text' => '/help@OtherBot'],
    ['text' => '@OtherBot question', 'entities' => [['type' => 'mention', 'offset' => 0, 'length' => 9]]],
    ['text' => 'Suite ?', 'reply_to_message' => ['from' => ['id' => 999999, 'is_bot' => true]]],
    ['text' => 'Suite ?', 'reply_to_message' => ['from' => ['id' => 123456, 'is_bot' => false]]],
    ['text' => '@OMOTestBot question', 'entities' => [['type' => 'code', 'offset' => 0, 'length' => 11]]],
    ['text' => '@OMOTestBot question', 'entities' => [['type' => 'mention', 'offset' => 0, 'length' => 11]], 'forward_origin' => ['type' => 'user']],
    ['text' => '/ask@OtherBot question', 'reply_to_message' => ['from' => ['id' => 123456, 'is_bot' => true]]],
] as $ignored) checkRoute(array_replace($base, $ignored), null, 'Unaddressed exchanges ignored');
checkRoute($base + ['text' => '/ask@omotestbot Quels rôles ?'], ['text' => '/ask Quels rôles ?', 'command' => '/ask'], 'Addressed command');
checkRoute($base + ['text' => '/connect@OMOTestBot'], ['text' => '/connect', 'command' => '/connect'], 'Existing command');
checkRoute($base + ['text' => '@OMOTestBot, Quels rôles ?', 'entities' => [['type' => 'mention', 'offset' => 0, 'length' => 11]]],
    ['text' => 'Quels rôles ?', 'command' => ''], 'Mention');
checkRoute($base + ['text' => '😀 @OMOTestBot Quels rôles ?', 'entities' => [['type' => 'mention', 'offset' => 3, 'length' => 11]]],
    ['text' => '😀  Quels rôles ?', 'command' => ''], 'UTF-16 emoji offsets');
checkRoute($base + ['text' => 'Bot, question ?', 'entities' => [['type' => 'text_mention', 'offset' => 0, 'length' => 3, 'user' => ['id' => 123456]]]],
    ['text' => 'question ?', 'command' => ''], 'User-ID mention');
checkRoute($base + ['text' => 'Suite ?', 'reply_to_message' => ['from' => ['id' => 123456, 'is_bot' => true]]],
    ['text' => 'Suite ?', 'command' => ''], 'Reply to this bot');
checkRoute(array_replace($base, ['chat' => ['id' => 42, 'type' => 'private'], 'text' => 'Question privée']),
    ['text' => 'Question privée', 'command' => ''], 'Private text');
checkRoute(array_replace($base, ['from' => ['id' => 42, 'is_bot' => true], 'text' => '/ask@OMOTestBot question']), null, 'Bot loops ignored');
echo "PASS explicit bot mentions, addressed commands, replies, other bots, ordinary group exchanges, Unicode entities and private text; no external calls\n";
