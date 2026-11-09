<?php

/** Only explicit references to this bot activate text handling in a group. */
function telegramTextRoute(array $message): ?array
{
    $text = trim((string)($message['text'] ?? ''));
    if ($text === '' || !empty($message['from']['is_bot']) || !empty($message['sender_chat'])) return null;
    $private = ($message['chat']['type'] ?? '') === 'private'
        || (isset($message['chat']['id'], $message['from']['id']) && (string)$message['chat']['id'] === (string)$message['from']['id']);
    preg_match('/^\/([a-z0-9_]+)(?:@([a-z0-9_]+))?(?=\s|$)/i', $text, $command);
    $recipient = $command[2] ?? '';
    if (!$private || $recipient !== '') {
        // Receiving a message (even with privacy mode disabled) is not evidence of intent.
        $entities = $message['entities'] ?? [];
        $hasMention = count(array_filter($entities, static fn ($entity) => in_array($entity['type'] ?? '', ['mention', 'text_mention'], true))) > 0;
        if ($recipient === '' && !$hasMention && empty($message['reply_to_message']['from']['is_bot'])) return null;
        if (!$private && !empty($message['forward_origin'])) return null;
        static $bot;
        if ($bot === null) {
            $response = telegramApiRequest('getMe');
            $bot = !empty($response['ok']) ? ($response['result'] ?? []) : [];
        }
        $botId = (int)($bot['id'] ?? 0);
        $username = (string)($bot['username'] ?? '');
        if ($botId <= 0 || $username === '') return null; // Fail closed if identity cannot be verified.
        if ($recipient !== '' && strcasecmp($recipient, $username) !== 0) return null;
        $addressed = $recipient !== '' || (!empty($message['reply_to_message']['from']['is_bot'])
            && (int)($message['reply_to_message']['from']['id'] ?? 0) === $botId);
        // Telegram entity offsets/lengths count UTF-16 code units, including emoji pairs.
        $encoded = mb_convert_encoding((string)$message['text'], 'UTF-16LE', 'UTF-8');
        $mentions = [];
        foreach ($entities as $entity) {
            $offset = (int)($entity['offset'] ?? -1); $length = (int)($entity['length'] ?? 0);
            if ($offset < 0 || $length <= 0 || ($offset + $length) * 2 > strlen($encoded)) continue;
            $value = mb_convert_encoding(substr($encoded, $offset * 2, $length * 2), 'UTF-8', 'UTF-16LE');
            if ((($entity['type'] ?? '') === 'mention' && strcasecmp($value, '@' . $username) === 0)
                || (($entity['type'] ?? '') === 'text_mention' && (int)($entity['user']['id'] ?? 0) === $botId)) {
                $addressed = true;
                $mentions[] = [$offset * 2, $length * 2];
            }
        }
        if (!$private && !$addressed) return null;
        if (!$private) {
            usort($mentions, static fn ($a, $b) => $b[0] <=> $a[0]);
            foreach ($mentions as [$offset, $length]) $encoded = substr_replace($encoded, '', $offset, $length);
            $text = trim(mb_convert_encoding($encoded, 'UTF-8', 'UTF-16LE'));
        }
    }
    // Normalize addressed commands before the existing command handlers run.
    $text = preg_replace('/^(\/[a-z0-9_]+)@[a-z0-9_]+(?=\s|$)/i', '$1', $text);
    $text = ltrim($text, " \t\r\n,:");
    if ($text === '') return null;
    preg_match('/^(\/[a-z0-9_]+)(?=\s|$)/i', $text, $command);
    return ['text' => $text, 'command' => strtolower($command[1] ?? '')];
}
