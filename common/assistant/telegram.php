<?php

require_once __DIR__ . '/agent.php';
require_once __DIR__ . '/translations.php';
require_once dirname(__DIR__, 2) . '/shared/telegram_addressing.php';

function commonTelegramAssistantT(string $key, array $variables = []): string
{
    static $bundle;
    $sourceLang = [
        'connect' => ['text' => 'Connectez votre compte en privé avec /connect pour poser des questions sur votre organisation.', 'context' => 'Telegram assistant requires a linked active account.'],
        'choose' => ['text' => 'Choisissez une organisation avec /organization suivi de son numéro :', 'context' => 'Telegram assistant organization selection.'],
        'selected' => ['text' => 'Organisation sélectionnée : {name}. Vous pouvez poser votre question. /new démarre une nouvelle discussion.', 'context' => 'Telegram assistant organization selected.'],
        'none' => ['text' => 'Aucune organisation accessible avec votre compte.', 'context' => 'Telegram assistant has no organization membership.'],
        'private' => ['text' => 'Les réponses de l’assistant sont envoyées en privé. Ouvrez une discussion avec le bot et envoyez /connect, puis /organization si nécessaire.', 'context' => 'Group questions are answered privately to avoid disclosing member-only information.'],
        'stopped' => ['text' => 'Le traitement est désactivé. Envoyez /start pour le réactiver.', 'context' => 'Telegram assistant disabled by stop command.'],
        'reset' => ['text' => 'Nouvelle discussion. Posez votre question.', 'context' => 'Telegram assistant history cleared.'],
        'access' => ['text' => 'Cette organisation n’est plus accessible avec votre compte. Utilisez /organization pour en choisir une autre.', 'context' => 'Telegram assistant organization access denied.'],
        'help' => ['text' => 'Posez vos questions directement en privé. /organization permet de choisir une organisation ; /new efface le contexte de la discussion. Dans un groupe, mentionnez le bot, répondez à l’un de ses messages ou utilisez /ask@nom_du_bot suivi de votre question : la réponse est envoyée en privé. Les autres échanges sont ignorés. Consultation avec vos droits actuels ; questions et extraits utiles transmis à l’IA configurée.', 'context' => 'Telegram assistant help and AI disclosure.'],
    ];
    $bundle ??= loadTranslationBundle('common_assistant_telegram', commonAuthGetTranslationLocale(), $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}

/** No browser/admin/share context or connector identity may be inherited by Telegram. */
function commonTelegramAssistantWithIdentity(\dbObject\User $user, callable $operation): mixed
{
    $previous = $_SESSION ?? [];
    $requestOrganization = $GLOBALS['common_request_organization_id'] ?? null;
    $usage = $GLOBALS['commonAiUsageContext'] ?? null;
    $_SESSION = ['currentUser' => (int)$user->getId(), 'userRef' => $user];
    unset($GLOBALS['common_request_organization_id'], $GLOBALS['commonAiUsageContext']);
    try { return $operation(); }
    finally {
        $_SESSION = $previous;
        if ($requestOrganization === null) unset($GLOBALS['common_request_organization_id']);
        else $GLOBALS['common_request_organization_id'] = $requestOrganization;
        if ($usage === null) unset($GLOBALS['commonAiUsageContext']);
        else $GLOBALS['commonAiUsageContext'] = $usage;
    }
}

/** Private server storage, bounded history, nonblocking turn lock and retry deduplication. */
function commonTelegramAssistantWithState(int $actorId, string $identity, callable $operation): mixed
{
    $secret = (string)envValue('TELEGRAM_WEBHOOK_SECRET', '');
    if ($actorId <= 0 || strlen($secret) < 32) throw new RuntimeException('context');
    $directory = rtrim(sys_get_temp_dir(), '/\\') . '/omo-telegram-' . substr(hash('sha256', dirname(__DIR__, 2)), 0, 20);
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('error');
    $path = $directory . '/' . hash_hmac('sha256', (string)$actorId, $secret) . '.json';
    $file = @fopen($path, 'c+');
    if ($file === false) throw new RuntimeException('error');
    @chmod($path, 0600);
    try {
        if (!flock($file, LOCK_EX | LOCK_NB)) throw new DomainException('busy');
        $state = json_decode(stream_get_contents($file, 3000000), true);
        if (!is_array($state) || ($state['identity'] ?? '') !== $identity) $state = ['identity' => $identity];
        // Persist the throttle/dedup marker before contacting the model, even on interruption.
        $save = static function () use ($file, &$state): void {
            $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, $json) !== strlen($json) || !fflush($file)) throw new RuntimeException('error');
        };
        try { return $operation($state, $save); }
        finally { $save(); }
    } finally { fclose($file); }
}

/** Convert even an unwanted model table to labeled records before delivery. */
function commonTelegramAssistantText(array $result, int $organizationId, string $baseUrl): string
{
    $links = [];
    foreach ($result['links'] ?? [] as $link) {
        if (commonAssistantPageUrl($link['url'], $organizationId) !== null) $links[$link['url']] = $link;
    }
    $text = commonAssistantFormatAnswer($result['answer'], $links);
    $text = preg_replace_callback('/\[([^\]\n]+)\]\(([^\s)]+)\)/', static fn ($m) => $m[1] . ' (' . rtrim($baseUrl, '/') . $m[2] . ')', $text);
    $lines = explode("\n", str_replace("\r", '', $text));
    $output = [];
    for ($i = 0; $i < count($lines); $i++) {
        if (isset($lines[$i + 1]) && str_contains($lines[$i], '|')
            && preg_match('/^\s*\|?\s*:?-{3,}:?\s*(?:\|\s*:?-{3,}:?\s*)+\|?\s*$/', $lines[$i + 1])) {
            $cells = static fn ($line) => array_map('trim', preg_split('/(?<!\\\\)\|/', trim(trim($line), '|')));
            $headers = $cells($lines[$i]);
            $i += 2;
            while (isset($lines[$i]) && str_contains($lines[$i], '|') && trim($lines[$i]) !== '') {
                $fields = [];
                foreach ($cells($lines[$i]) as $key => $value) $fields[] = ($headers[$key] ?? '') . ' : ' . str_replace('\\|', '|', $value);
                $output[] = '• ' . implode(' ; ', $fields);
                $i++;
            }
            $i--;
        } else {
            $output[] = preg_replace('/^\s{0,3}#{1,6}\s+/', '', $lines[$i]);
        }
    }
    $text = implode("\n", $output);
    // Broad list tools can expose hundreds of links; keep the extra source footer bounded.
    foreach (array_slice($links, 0, 8, true) as $url => $link) {
        $absolute = rtrim($baseUrl, '/') . $url;
        if (!str_contains($text, $absolute)) $text .= "\n" . $link['label'] . ' : ' . $absolute;
    }
    return $text;
}

/** Split on Unicode code points, with room for Telegram's UTF-16 length accounting. */
function commonTelegramAssistantSend(int $chatId, string $text): bool
{
    $ok = true;
    while ($text !== '') {
        $part = mb_substr($text, 0, 1800, 'UTF-8');
        if (mb_strlen($text, 'UTF-8') > 1800) {
            $break = mb_strrpos($part, "\n", 0, 'UTF-8');
            if ($break === false || $break < 900) $break = mb_strrpos($part, ' ', 0, 'UTF-8');
            if ($break !== false && $break >= 900) $part = mb_substr($part, 0, $break + 1, 'UTF-8');
        }
        $ok = sendMessage($chatId, $part) !== null && $ok;
        $text = mb_substr($text, mb_strlen($part, 'UTF-8'), null, 'UTF-8');
    }
    return $ok;
}

function commonTelegramAssistantHandle(array $message, \dbObject\User $user, string $command, ?callable $request = null): void
{
    $actorId = (int)($message['from']['id'] ?? 0);
    $private = isTelegramPrivateChat($message);
    $route = telegramTextRoute($message);
    if ($route === null) return;
    $command = $route['command'];
    if (!$private && $command !== '' && $command !== '/ask') return;
    if ($actorId <= 0 || !empty($message['from']['is_bot']) || !empty($message['sender_chat'])) return;
    if (!$private) sendMessage($message['chat']['id'], commonTelegramAssistantT('private'), null, getMessageThreadId($message));
    if ((int)$user->getId() <= 0 || !$user->get('active') || (string)$user->get('telegramID') !== (string)$actorId) {
        commonTelegramAssistantSend($actorId, commonTelegramAssistantT('connect'));
        return;
    }
    $preferences = loadLocalSession($actorId);
    if (isset($preferences->active) && !$preferences->active) {
        commonTelegramAssistantSend($actorId, commonTelegramAssistantT('stopped'));
        return;
    }
    // Do not hold the unrelated browser session during the model call.
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    try {
        commonTelegramAssistantWithIdentity($user, static function () use ($actorId, $user, $command, $message, $private, $request, $route): void {
            $identity = hash('sha256', json_encode([(int)$user->getId(), $user->get('security_version'), $user->get('telegramID')]));
            commonTelegramAssistantWithState($actorId, $identity, static function (array &$state, callable $save) use ($actorId, $user, $command, $message, $private, $request, $route): void {
                $messageKey = (string)$message['chat']['id'] . ':' . (int)($message['message_id'] ?? 0);
                if (in_array($messageKey, $state['processed'] ?? [], true)) return;
                $text = $route['text'];
                $argument = trim((string)preg_replace('/^\S+\s*/u', '', $text));
                $oid = (int)($state['organization'] ?? 0);
                if (!$private) {
                    $destination = \dbObject\TelegramChatDestination::findByTelegramChat($message['chat']['id'], getMessageThreadId($message));
                    if ($destination) $oid = (int)$destination->get('IDorganization');
                }
                if ($command === '/organization' && $argument !== '') {
                    if (!preg_match('/^[1-9][0-9]*$/D', $argument)) throw new DomainException('access');
                    $oid = (int)$argument;
                }
                if ($oid <= 0 || ($command === '/organization' && $argument === '')) {
                    $organizations = new \dbObject\ArrayOrganization();
                    $organizations->loadAccessibleForUser((int)$user->getId());
                    $choices = [];
                    foreach ($organizations as $organization) {
                        $id = (int)$organization->getId();
                        try { commonAssistantWithContext($id, static fn () => commonAssistantGrant($id)); }
                        catch (DomainException $error) { continue; }
                        $choices[$id] = '/organization ' . $id . ' — ' . $organization->get('name');
                    }
                    if (!$choices) { commonTelegramAssistantSend($actorId, commonTelegramAssistantT('none')); return; }
                    if (count($choices) !== 1 || $command === '/organization') {
                        commonTelegramAssistantSend($actorId, commonTelegramAssistantT('choose') . "\n" . implode("\n", $choices));
                        return;
                    }
                    $oid = (int)array_key_first($choices);
                }
                commonAssistantWithContext($oid, static function () use ($oid, &$state, $save, $actorId, $user, $command, $text, $argument, $messageKey, $private, $request): void {
                    try { $grant = commonAssistantGrant($oid); }
                    catch (DomainException $error) { unset($state['chats'][$oid], $state['organization']); throw new DomainException('access'); }
                    if ($private) $state['organization'] = $oid;
                    if ($command === '/organization') {
                        $organization = \dbObject\McpStructure::organization($grant);
                        commonTelegramAssistantSend($actorId, commonTelegramAssistantT('selected', ['name' => $organization->get('name')]));
                        return;
                    }
                    if ($command === '/new') {
                        unset($state['chats'][$oid]);
                        commonTelegramAssistantSend($actorId, commonTelegramAssistantT('reset'));
                        return;
                    }
                    if (!commonAiOrganizationAllows($oid)) { commonTelegramAssistantSend($actorId, commonAiOrganizationBlockedMessage()); return; }
                    if (!commonAiUserCanUse((int)$user->getId(), null, null, $oid)) throw new DomainException('unavailable');
                    $question = $command === '/ask' ? $argument : $text;
                    if ($question === '' || !mb_check_encoding($question, 'UTF-8') || mb_strlen($question, 'UTF-8') > 4000) throw new DomainException('invalid');
                    $state['rate'] ??= [];
                    commonAssistantConsumeRate($state['rate']);
                    $state['processed'] = array_slice(array_merge($state['processed'] ?? [], [$messageKey]), -50);
                    $save();
                    $baseUrl = commonSecurityBaseUrl();
                    $wait = sendMessage($actorId, commonAssistantT('working'));
                    ignore_user_abort(true);
                    set_time_limit(120);
                    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                    try {
                        commonAiSetUsageContext((int)$user->getId(), $oid, 'assistant', 'telegram');
                        $chat = $state['chats'][$oid] ?? [];
                        $history = ($chat['updated'] ?? 0) > time() - 86400 ? ($chat['history'] ?? []) : [];
                        $result = commonAssistantRun($grant, $history, $question, commonAuthGetTranslationLocale(), null, $request, 'telegram');
                        // Recheck linkage, membership and organization AI policy before sending private data.
                        $freshUser = loadTelegramUserByActorId($actorId);
                        if ((int)$freshUser->getId() !== (int)$user->getId() || !$freshUser->get('active')
                            || $freshUser->get('security_version') !== $user->get('security_version')) throw new DomainException('access');
                        commonAssistantGrant($oid);
                        if (!commonAiOrganizationAllows($oid) || !commonAiUserCanUse((int)$user->getId(), null, null, $oid)) throw new DomainException('unavailable');
                        $answer = commonTelegramAssistantText($result, $oid, $baseUrl);
                        if (!commonTelegramAssistantSend($actorId, $answer)) throw new RuntimeException('provider');
                        $history[] = ['role' => 'user', 'content' => $question];
                        $history[] = ['role' => 'assistant', 'content' => mb_substr($result['answer'], 0, 10000, 'UTF-8')];
                        unset($state['chats'][$oid]);
                        $state['chats'][$oid] = ['updated' => time(), 'history' => array_slice($history, -12)];
                        $state['chats'] = array_slice($state['chats'], -4, null, true);
                    } finally { if ($wait !== null) deleteMessage($actorId, $wait); }
                });
            });
        });
    } catch (Throwable $error) {
        $key = in_array($error->getMessage(), ['access', 'unavailable', 'invalid', 'busy', 'rate', 'limit', 'provider'], true) ? $error->getMessage() : 'error';
        error_log('OMO Telegram assistant failure: ' . get_class($error) . ' category=' . $key);
        commonTelegramAssistantSend($actorId, $key === 'access' ? commonTelegramAssistantT('access') : commonAssistantT($key));
    }
}
