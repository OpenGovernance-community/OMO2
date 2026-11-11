<?php
declare(strict_types=1);

namespace dbObject {
    // In-memory objects: exercise the handler without a database or Telegram calls.
    class User {
        public function getId() { return 7; }
        public function get($key) { return $key === 'active'; }
        public function refreshDbh() {}
    }
    class Document {
        public array $fields = [];
        public function getId() { return empty($GLOBALS['voiceSaveFails']) ? 42 : 0; }
        public function get($key) { return $this->fields[$key] ?? null; }
        public function set($key, $value) { $this->fields[$key] = $value; }
        public function save() { $GLOBALS['voiceDocument'] = $this; return ['status' => empty($GLOBALS['voiceSaveFails'])]; }
    }
    class Media extends Document {
        public function save() { $GLOBALS['voiceMedia'] = $this; return ['status' => true]; }
    }
    class TelegramChatDestination {
        public static function findByTelegramChat(...$args) { return null; }
    }
}

namespace OmoTelegramVoiceTest {
    use RuntimeException;

    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require_once dirname(__DIR__) . '/common/ai_config.php';

    function check(bool $condition, string $message): void {
        if (!$condition) throw new RuntimeException($message);
    }
    function commonAiUserCanUse(...$args) { return $GLOBALS['voiceAllowed'] ?? true; }
    function commonAiGetCurrentUserId() { return 7; }
    function commonOpenAiGetApiKey() { return commonAiGetApiKey(); }
    function translationBundleResolveRequestLocale() { return 'fr'; }
    function loadTranslationBundle($key, $locale, $sourceLang) { return $sourceLang; }
    function t($key, $variables, $bundle, $sourceLang) { return $sourceLang[$key]['text']; }
    function commonAiRequestText($key, $payload, $timeout) {
        $GLOBALS['voiceRequest'] = $payload;
        return $GLOBALS['voiceReply'];
    }
    function commonAiIsTranscriptionConfigured() { return true; }
    function commonAiOrganizationAllows($id) { return true; }
    function patreonIndividualCanUseAi($id) { return true; }
    function patreonUserCanUseAi(...$args) { return true; }
    function commonAiSetUsageContext(...$args) {}
    function commonOpenAiTranscribeUploadedAudio(...$args) {
        return ['status' => true, 'text' => $GLOBALS['voiceTranscript']];
    }
    function getTelegramFile($id) { return ['result' => ['file_path' => 'fixture.ogg']]; }
    function telegramDownloadFile($path) { return ['ok' => true, 'content' => 'fixture audio']; }
    function loadLocalSession($id) { return new \stdClass(); }
    function saveLocalSession($data, $id) { $GLOBALS['voiceSession'] = $data; }
    function sendMessage($chat, $text, $buttons = null, $thread = null) {
        $GLOBALS['voiceMessages'][] = ['text' => $text, 'buttons' => $buttons, 'thread' => $thread];
        return count($GLOBALS['voiceMessages']);
    }
    function deleteMessage(...$args) {}
    function buildMemoActionButtons() { return [['fixture buttons']]; }
    function appBuildAbsoluteUrl($path) { return 'https://omo.example' . $path; }
    function error_log($message) { $GLOBALS['voiceLogs'][] = $message; }

    $source = file_get_contents(dirname(__DIR__) . '/common/ai_client.php');
    $start = strpos($source, 'function commonAiJsonObjectResponseFormat(');
    $end = strpos($source, '/** Text-only adapter', $start);
    eval('namespace ' . __NAMESPACE__ . ';' . substr($source, $start, $end - $start));
    $source = file_get_contents(dirname(__DIR__) . '/common/openai_text.php');
    eval('namespace ' . __NAMESPACE__ . ';' . preg_replace('/^require_once[^\n]*;\s*$/m', '', substr($source, strlen('<?php'))));
    $source = file_get_contents(dirname(__DIR__) . '/telegram/memo_hook.php');
    foreach (['telegramMemoT', 'getMessageThreadId', 'isTelegramPrivateChat', 'formatDocumentLink', 'handleVoiceMessage'] as $name) {
        $start = strpos($source, "\tfunction " . $name . '(');
        $end = strpos($source, "\n\tfunction ", $start + 1);
        check($start !== false && $end !== false, 'Find handler ' . $name);
        $functionSource = preg_replace('/^\s*require_once[^\n]*;\s*$/m', '', substr($source, $start, $end - $start));
        eval('namespace ' . __NAMESPACE__ . ';' . $functionSource);
    }
    $_ENV['AI_PROVIDER'] = 'infomaniak';
    $_ENV['AI_API_KEY'] = 'never-send';
    $_ENV['AI_MODEL'] = 'fixture-model';
    $GLOBALS['voiceTranscript'] = 'Nous avons décidé de préparer le budget vendredi. Léa réunira les devis avant la réunion.';
    $summary = 'Le budget sera préparé vendredi. Léa réunira les devis avant la réunion.';
    $metadata = ['title' => 'Préparation du budget', 'summary' => $summary, 'keywords' => ['#budget', 'devis', 'budget', 'réunion']];
    $GLOBALS['voiceReply'] = ['status' => true, 'content' => json_encode($metadata, JSON_UNESCAPED_UNICODE)];
    $voice = ['from' => ['id' => 77], 'chat' => ['id' => 77], 'voice' => ['file_id' => 'fixture', 'duration' => 30]];
    handleVoiceMessage($voice, new \dbObject\User(), 10);

    $request = $GLOBALS['voiceRequest'];
    check($request['response_format']['type'] === 'json_schema', 'Use the existing Infomaniak JSON schema adapter.');
    check($request['response_format']['json_schema']['schema']['required'] === ['title', 'summary', 'keywords'], 'Require all metadata fields.');
    check($request['model'] === 'fixture-model', 'Respect the configured model.');
    check(json_decode($request['messages'][1]['content'], true)['transcript'] === $GLOBALS['voiceTranscript'], 'Preserve the transcript as source data.');
    check(str_contains($request['messages'][0]['content'], 'one or at most two'), 'Request at most two summary sentences.');
    check($GLOBALS['voiceDocument']->fields['description'] === $summary, 'Save the generated summary on the memo.');
    check($GLOBALS['voiceDocument']->fields['title'] === $metadata['title'], 'Save the generated title on the memo.');
    check($GLOBALS['voiceDocument']->fields['content'] === $GLOBALS['voiceTranscript'], 'Keep the complete transcription.');
    check($GLOBALS['voiceMedia']->fields['description'] === $summary, 'Save the same summary on the audio.');
    check($GLOBALS['voiceDocument']->fields['keywords'] === 'budget, devis, réunion', 'Clean hashtags and duplicate keywords.');
    $reply = end($GLOBALS['voiceMessages']);
    check($reply['text'] === "⬆ " . $summary . "\nhttps://omo.example/memo/42", 'Show the summary above the saved memo link.');
    check($reply['buttons'] !== null && $GLOBALS['voiceSession']->lastDoc === 42, 'Keep memo actions and session tracking.');

    // Models that wrap otherwise valid JSON in markdown must still work.
    $GLOBALS['voiceReply']['content'] = "```json\n" . json_encode($metadata) . "\n```";
    check(commonOpenAiGenerateDocumentMetadata('fixture', ['user_id' => 7])['summary'] === $summary, 'Accept fenced JSON.');
    $metadata['title'] = str_repeat('é', 120);
    $GLOBALS['voiceReply']['content'] = json_encode($metadata);
    check(mb_strlen(commonOpenAiGenerateDocumentMetadata('fixture', ['user_id' => 7])['title'], 'UTF-8') === 100, 'Respect the title field length without breaking UTF-8.');

    foreach (['openai', 'anthropic', 'mistral', 'openai_compatible'] as $provider) {
        $_ENV['AI_PROVIDER'] = $provider;
        check(commonOpenAiGenerateDocumentMetadata('fixture', ['user_id' => 7])['status'], 'Metadata works with provider ' . $provider);
        check($GLOBALS['voiceRequest']['response_format']['type'] === 'json_object', 'Retain the shared JSON format for ' . $provider);
    }
    $_ENV['AI_PROVIDER'] = 'infomaniak';
    foreach (['', '**TITLE:** Budget\n**SUMMARY:** Devis vendredi', '{"title":"Budget","summary":[],"keywords":[]}', '{"title":"Budget","summary":"","keywords":[]}', '{"title":"Budget","summary":"Résumé","keywords":[{}]}'] as $invalid) {
        $GLOBALS['voiceReply'] = ['status' => true, 'content' => $invalid];
        check(!commonOpenAiGenerateDocumentMetadata('fixture', ['user_id' => 7])['status'], 'Reject malformed metadata without treating raw JSON as a summary.');
    }
    $GLOBALS['voiceReply'] = ['status' => false, 'http_code' => 400, 'message' => 'Private provider error'];
    handleVoiceMessage($voice, new \dbObject\User(), 10);
    $reply = end($GLOBALS['voiceMessages'])['text'];
    check(str_contains($reply, 'enregistré, mais le résumé') && str_ends_with($reply, "\nhttps://omo.example/memo/42"), 'Save the transcript and expose a summary failure with the link below.');
    $log = end($GLOBALS['voiceLogs']);
    check(str_contains($log, 'metadata_request_failed') && str_contains($log, '400'), 'Log actionable metadata failure diagnostics.');
    check(!str_contains($log, 'Private') && !str_contains($log, 'never-send') && !str_contains($log, 'Léa'), 'Keep provider errors, credentials and memo content out of diagnostics.');

    $GLOBALS['voiceAllowed'] = false;
    check(commonOpenAiGenerateDocumentMetadata('fixture', ['user_id' => 7])['code'] === 'metadata_access_denied', 'Keep AI access checks.');
    $GLOBALS['voiceAllowed'] = true;
    $GLOBALS['voiceSaveFails'] = true;
    handleVoiceMessage($voice, new \dbObject\User(), 10);
    check(!str_contains(end($GLOBALS['voiceMessages'])['text'], 'enregistré'), 'Never claim a failed memo was saved.');
    unset($GLOBALS['voiceSaveFails']);

    // Unbound group: only return a summary, without creating or linking a memo.
    unset($GLOBALS['voiceDocument'], $GLOBALS['voiceMedia']);
    $GLOBALS['voiceReply'] = ['status' => true, 'content' => json_encode(['title' => 'Budget', 'summary' => $summary, 'keywords' => []])];
    $voice['chat']['id'] = -123;
    $voice['message_thread_id'] = 9;
    handleVoiceMessage($voice, new \dbObject\User(), 10);
    $reply = end($GLOBALS['voiceMessages']);
    check(!isset($GLOBALS['voiceDocument']) && !str_contains($reply['text'], 'https://'), 'Unbound group summary creates no memo or link.');
    check(str_contains($reply['text'], $summary) && $reply['thread'] === 9, 'Return the group summary in the original thread.');
    echo "telegram_voice_metadata_test: OK\n";
}
