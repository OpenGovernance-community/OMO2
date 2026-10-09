<?php
declare(strict_types=1);

namespace OmoAiProviderTest;
use \RuntimeException;

// Evaluate the real client in an isolated namespace to replace only its HTTP transport.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
foreach (['CURLOPT_RETURNTRANSFER', 'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLOPT_HTTPHEADER', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT', 'CURLOPT_FOLLOWLOCATION', 'CURLINFO_HTTP_CODE'] as $index => $name) {
    if (!defined($name)) define($name, $index + 1);
}
function curl_init($url) { return (object)['url' => $url, 'options' => []]; }
function curl_setopt_array($curl, array $options) { $curl->options = $options; return true; }
function curl_exec($curl) { $GLOBALS['aiProviderLastRequest'] = $curl; return $GLOBALS['aiProviderResponse']; }
function curl_getinfo($curl, $option) { return $GLOBALS['aiProviderHttpCode'] ?? 200; }
function curl_error($curl) { return 'simulated transport failure'; }

require_once dirname(__DIR__) . '/common/ai_client.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/common/openai_audio.php';
require_once dirname(__DIR__) . '/includes/server_env_admin.php';
$clientSource = file_get_contents(dirname(__DIR__) . '/common/ai_client.php');
eval('namespace OmoAiProviderTest; use \Throwable; use \InvalidArgumentException;' . str_replace("require_once __DIR__ . '/ai_config.php';", '', substr($clientSource, strlen('<?php'))));
$audioSource = file_get_contents(dirname(__DIR__) . '/common/openai_audio.php');
eval('namespace OmoAiProviderTest;' . str_replace("require_once __DIR__ . '/ai_access.php';", '', substr($audioSource, strlen('<?php'))));
$translationSource = file_get_contents(dirname(__DIR__) . '/common/translation_bundles.php');
$translationStart = strpos($translationSource, 'function translationBundleTranslateWithAi(');
$translationEnd = strpos($translationSource, 'function translationBundleProcessRefreshJob(', $translationStart);
eval('namespace OmoAiProviderTest;' . substr($translationSource, $translationStart, $translationEnd - $translationStart));

function aiProviderExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function aiProviderEnv(array $values): void
{
    foreach (['AI_PROVIDER', 'AI_BASE_URL', 'AI_API_KEY', 'AI_MODEL', 'AI_TRANSLATION_MODEL', 'TRANSCRIPTION_PROVIDER', 'TRANSCRIPTION_API_KEY', 'TRANSCRIPTION_MODEL', 'OPENAI_API_KEY', 'OPENAI_MODEL', 'OPENAI_TRANSLATION_MODEL', 'OPENAI_TRANSCRIPTION_MODEL', 'OPENAI_AUDIO_TRANSCRIPTION_MODEL'] as $key) {
        $_ENV[$key] = $values[$key] ?? '';
    }
    unset($GLOBALS['OpenAI'], $GLOBALS['openAiTranslationModel'], $GLOBALS['openAiTranscriptionModel']);
}

aiProviderEnv(['OPENAI_API_KEY' => 'legacy-text-key', 'OPENAI_MODEL' => 'legacy-main', 'OPENAI_TRANSLATION_MODEL' => 'legacy-small', 'OPENAI_TRANSCRIPTION_MODEL' => 'legacy-audio']);
aiProviderExpect(commonAiGetApiKey() === 'legacy-text-key' && commonAiGetModel() === 'legacy-main' && commonAiGetModel(true) === 'legacy-small', 'Legacy text and translation settings must remain effective.');
aiProviderExpect(commonAiGetTranscriptionApiKey() === 'legacy-text-key' && commonAiGetTranscriptionModel() === 'legacy-audio', 'Legacy transcription must remain effective.');
$effective = serverEnvAdminBuildCurrentValues();
aiProviderExpect($effective['AI_API_KEY'] === 'legacy-text-key' && $effective['AI_TRANSLATION_MODEL'] === 'legacy-small' && $effective['TRANSCRIPTION_API_KEY'] === 'legacy-text-key', 'The form must migrate effective legacy settings without exposing secret values.');
aiProviderExpect(serverEnvAdminBuildDisplayValues($effective)['AI_API_KEY'] === '' && serverEnvAdminBuildDisplayValues($effective)['TRANSCRIPTION_API_KEY'] === '', 'The form must hide both API keys.');

aiProviderEnv(['AI_PROVIDER' => 'anthropic', 'AI_API_KEY' => 'claude-text-key', 'AI_MODEL' => 'claude-main', 'AI_TRANSLATION_MODEL' => 'claude-small', 'TRANSCRIPTION_API_KEY' => 'audio-key', 'TRANSCRIPTION_MODEL' => 'audio-model', 'OPENAI_API_KEY' => 'legacy-key', 'OPENAI_MODEL' => 'legacy-main']);
aiProviderExpect(commonAiGetApiKey() === 'claude-text-key' && commonAiGetModel() === 'claude-main' && commonAiGetModel(true) === 'claude-small', 'Text and translation must use their separate selected models.');
aiProviderExpect(commonAiGetTranscriptionApiKey() === 'audio-key' && commonAiGetTranscriptionModel() === 'audio-model', 'Audio must use its independent credentials and model.');
$_ENV['TRANSCRIPTION_API_KEY'] = '';
aiProviderExpect(commonAiGetTranscriptionApiKey() === 'legacy-key', 'Claude text key must never be sent to audio; legacy audio fallback remains valid.');
$_ENV['OPENAI_API_KEY'] = '';
aiProviderExpect(commonAiGetTranscriptionApiKey() === '', 'Claude alone must not activate audio.');
$_ENV['TRANSCRIPTION_API_KEY'] = 'audio-key';
$GLOBALS['aiProviderResponse'] = '{"text":"Audio converted to text"}';
$audio = commonOpenAiRequestAudioTranscription(commonAiGetTranscriptionApiKey(), __FILE__, 'audio/ogg', 'recording.ogg', ['model' => commonAiGetTranscriptionModel()]);
$request = $GLOBALS['aiProviderLastRequest'];
aiProviderExpect($audio['status'] && $audio['text'] === 'Audio converted to text' && $request->url === 'https://api.openai.com/v1/audio/transcriptions', 'Audio must use its own transcription endpoint even when text uses Claude.');
aiProviderExpect(in_array('Authorization: Bearer audio-key', $request->options[CURLOPT_HTTPHEADER], true) && $request->options[CURLOPT_POSTFIELDS]['model'] === 'audio-model', 'Audio requests must use the dedicated key and model.');
$_ENV['TRANSCRIPTION_PROVIDER'] = 'disabled';
aiProviderExpect(!commonAiIsTranscriptionConfigured() && !commonOpenAiRequestAudioTranscription('audio-key', __FILE__, 'audio/ogg', 'recording.ogg', ['model' => 'audio-model'])['status'], 'Disabled audio must stop before contacting the provider.');
$_ENV['TRANSCRIPTION_PROVIDER'] = 'openai';
$_ENV['AI_API_KEY'] = '';
aiProviderExpect(!commonAiIsConfigured() && commonAiIsTranscriptionConfigured(), 'Configured audio must work independently of text configuration.');
$_ENV['AI_API_KEY'] = 'claude-text-key';
$_ENV['AI_TRANSLATION_MODEL'] = '';
aiProviderExpect(commonAiGetModel(true) === 'claude-main', 'An empty translation model must use the selected main model.');
$_ENV['AI_TRANSLATION_MODEL'] = 'claude-small';

$payload = ['model' => commonAiGetModel(), 'messages' => [['role' => 'system', 'content' => 'Return JSON.'], ['role' => 'user', 'content' => 'Summarize.']], 'response_format' => ['type' => 'json_object'], 'temperature' => 0.2, 'max_tokens' => 500];
$GLOBALS['aiProviderResponse'] = json_encode(['content' => [['type' => 'thinking', 'thinking' => 'private'], ['type' => 'text', 'text' => '{"summary":"ok"}']], 'stop_reason' => 'end_turn']);
$result = commonAiRequestText(commonAiGetApiKey(), $payload);
$request = $GLOBALS['aiProviderLastRequest'];
$body = json_decode($request->options[CURLOPT_POSTFIELDS], true);
aiProviderExpect($result['status'] && $result['content'] === '{"summary":"ok"}', 'Claude text blocks must be decoded without including thinking.');
aiProviderExpect($request->url === 'https://api.anthropic.com/v1/messages' && in_array('x-api-key: claude-text-key', $request->options[CURLOPT_HTTPHEADER], true), 'Claude must use the native endpoint and authentication.');
aiProviderExpect($body['system'] === 'Return JSON.' && count($body['messages']) === 1 && $body['model'] === 'claude-main' && $body['max_tokens'] === 500 && !isset($body['response_format']) && !isset($body['temperature']), 'Claude must receive the native message format.');
aiProviderExpect($request->options[CURLOPT_FOLLOWLOCATION] === false, 'Credentials must not follow HTTP redirects.');

$GLOBALS['aiProviderResponse'] = json_encode(['content' => [['type' => 'text', 'text' => '{"greeting":{"text":"Hello {name}"}}']], 'stop_reason' => 'end_turn']);
$translated = translationBundleTranslateWithAi('provider-test', 'en', ['greeting' => ['text' => 'Bonjour {name}', 'context' => 'Greeting']]);
$body = json_decode($GLOBALS['aiProviderLastRequest']->options[CURLOPT_POSTFIELDS], true);
aiProviderExpect($body['model'] === 'claude-small' && $translated['greeting']['text'] === 'Hello {name}', 'Real translation requests must use the translation model and retain placeholders.');
$GLOBALS['aiProviderResponse'] = '{"content":[{"type":"text","text":"partial"}],"stop_reason":"max_tokens"}';
aiProviderExpect(!commonAiRequestText('claude-text-key', $payload)['status'], 'Truncated output must not be accepted as a complete answer.');
$GLOBALS['aiProviderHttpCode'] = 401;
$GLOBALS['aiProviderResponse'] = '{"error":{"message":"invalid key"}}';
aiProviderExpect(!commonAiRequestText('claude-text-key', $payload)['status'], 'Provider errors must fail cleanly.');
$GLOBALS['aiProviderHttpCode'] = 200;
$GLOBALS['aiProviderResponse'] = 'not JSON';
aiProviderExpect(!commonAiRequestText('claude-text-key', $payload)['status'], 'Malformed provider output must fail cleanly.');

aiProviderEnv(['AI_PROVIDER' => 'openai', 'AI_API_KEY' => 'new-text-key', 'AI_MODEL' => 'selected-main', 'AI_TRANSLATION_MODEL' => 'selected-small']);
$payload['model'] = commonAiGetModel();
$GLOBALS['aiProviderResponse'] = '{"choices":[{"message":{"content":"ok"},"finish_reason":"stop"}]}';
aiProviderExpect(commonAiRequestText(commonAiGetApiKey(), $payload)['status'], 'OpenAI text transport remains supported.');
$request = $GLOBALS['aiProviderLastRequest'];
$body = json_decode($request->options[CURLOPT_POSTFIELDS], true);
aiProviderExpect($request->url === 'https://api.openai.com/v1/chat/completions' && in_array('Authorization: Bearer new-text-key', $request->options[CURLOPT_HTTPHEADER], true) && $body['response_format']['type'] === 'json_object', 'OpenAI must retain its own endpoint, authentication and JSON mode.');
aiProviderExpect(commonAiGetTranscriptionApiKey() === 'new-text-key', 'OpenAI text credentials may supply the default audio key.');
aiProviderExpect(serverEnvAdminValidateValues(['AI_PROVIDER' => 'anthropic', 'AI_API_KEY' => 'same-key', 'AI_MODEL' => 'claude-main'], ['AI_PROVIDER' => 'openai', 'AI_API_KEY' => 'same-key']) !== [], 'Changing provider must require replacing the old text secret.');
aiProviderExpect(serverEnvAdminValidateValues(['AI_PROVIDER' => 'anthropic', 'AI_API_KEY' => 'new-key', 'AI_MODEL' => 'claude-main'], ['AI_PROVIDER' => 'openai', 'AI_API_KEY' => 'old-key']) === [], 'An explicit new provider key and model must be accepted.');
aiProviderExpect(serverEnvAdminValidateValues(['AI_PROVIDER' => 'anthropic', 'AI_API_KEY' => 'new-key', 'AI_MODEL' => '']) !== [], 'Claude must require an explicit model once configured.');
aiProviderExpect(serverEnvAdminValidateValues(['AI_PROVIDER' => 'unknown']) !== [], 'Unknown providers must be rejected.');

foreach (['' => 'https://openrouter.ai/api/v1', 'https://api.mistral.ai/v1/' => 'https://api.mistral.ai/v1', 'https://api.groq.com/openai/v1' => 'https://api.groq.com/openai/v1', 'https://api.deepseek.com' => 'https://api.deepseek.com'] as $configured => $expectedBaseUrl) {
    aiProviderEnv(['AI_PROVIDER' => 'openai_compatible', 'AI_BASE_URL' => $configured, 'AI_API_KEY' => 'compatible-key', 'AI_MODEL' => 'vendor/main', 'AI_TRANSLATION_MODEL' => 'vendor/small']);
    $payload['model'] = commonAiGetModel();
    aiProviderExpect(commonAiIsConfigured(), 'A configured compatible service must enable text tools.');
    aiProviderExpect(commonAiRequestText(commonAiGetApiKey(), $payload)['status'], 'Compatible providers must share the Chat Completions request and response formats.');
    $request = $GLOBALS['aiProviderLastRequest'];
    $body = json_decode($request->options[CURLOPT_POSTFIELDS], true);
    aiProviderExpect($request->url === $expectedBaseUrl . '/chat/completions' && in_array('Authorization: Bearer compatible-key', $request->options[CURLOPT_HTTPHEADER], true), 'The request must use the chosen base URL and its dedicated key.');
    aiProviderExpect($body['model'] === 'vendor/main' && $body['response_format']['type'] === 'json_object', 'Compatible requests must retain the selected model and JSON mode.');
    aiProviderExpect(commonAiGetTranscriptionApiKey() === '', 'A compatible text key must not leak into the audio service.');
}
$GLOBALS['aiProviderResponse'] = '{"choices":[{"message":{"content":"{\"greeting\":{\"text\":\"Hello {name}\"}}"},"finish_reason":"stop"}]}';
$translated = translationBundleTranslateWithAi('compatible-test', 'en', ['greeting' => ['text' => 'Bonjour {name}']]);
$body = json_decode($GLOBALS['aiProviderLastRequest']->options[CURLOPT_POSTFIELDS], true);
aiProviderExpect($body['model'] === 'vendor/small' && $translated['greeting']['text'] === 'Hello {name}', 'Compatible services must use the dedicated translation model.');

foreach (['http://api.example.org/v1', 'https://user:password@api.example.org/v1', 'https://api.example.org/v1?key=secret', 'https://api.example.org/v1#section', 'https://api.example.org/v1/chat/completions', 'https://bad host.example.org/v1', "https://api.example.org/v1\r\n", 'https://api.example.org/v1%0aheader'] as $url) {
    $values = ['AI_PROVIDER' => 'openai_compatible', 'AI_BASE_URL' => $url, 'AI_API_KEY' => 'compatible-key', 'AI_MODEL' => 'vendor/main'];
    // Submitted values trim surrounding whitespace; embedded controls are never accepted.
    if (str_ends_with($url, "\r\n")) $values['AI_BASE_URL'] = "https://api.example.org/v1\r\nextra";
    aiProviderExpect(serverEnvAdminValidateValues($values) !== [], 'An invalid custom API address must be rejected.');
    aiProviderEnv($values);
    aiProviderExpect(!commonAiIsConfigured() && !commonAiRequestText('compatible-key', $payload)['status'], 'Malformed manually configured addresses must not trigger HTTP calls.');
}
$currentCompatible = ['AI_PROVIDER' => 'openai_compatible', 'AI_BASE_URL' => 'https://openrouter.ai/api/v1', 'AI_API_KEY' => 'old-key', 'AI_MODEL' => 'vendor/main'];
aiProviderExpect(serverEnvAdminValidateValues(array_merge($currentCompatible, ['AI_BASE_URL' => 'https://api.mistral.ai/v1']), $currentCompatible) !== [], 'Changing destination must not silently send an existing key to another API.');
aiProviderExpect(serverEnvAdminValidateValues(array_merge($currentCompatible, ['AI_BASE_URL' => 'https://api.mistral.ai/v1', 'AI_API_KEY' => 'new-key']), $currentCompatible) === [], 'A custom API address with an explicitly replaced key must be accepted.');
aiProviderExpect(serverEnvAdminValidateValues(array_merge($currentCompatible, ['AI_BASE_URL' => 'https://openrouter.ai/api/v1/']), $currentCompatible) === [], 'A trailing slash must not count as changing destination.');

aiProviderEnv(['AI_PROVIDER' => 'mistral', 'AI_API_KEY' => 'mistral-key', 'AI_MODEL' => 'mistral-small-latest', 'AI_TRANSLATION_MODEL' => 'mistral-translation', 'TRANSCRIPTION_PROVIDER' => 'mistral', 'OPENAI_API_KEY' => 'old-openai-key', 'OPENAI_TRANSCRIPTION_MODEL' => 'old-openai-model']);
$GLOBALS['aiProviderResponse'] = '{"choices":[{"message":{"content":"ok"},"finish_reason":"stop"}]}';
$payload['model'] = commonAiGetModel();
aiProviderExpect(commonAiIsConfigured() && commonAiRequestText(commonAiGetApiKey(), $payload)['status'], 'Mistral must enable the shared text tools.');
$request = $GLOBALS['aiProviderLastRequest'];
$body = json_decode($request->options[CURLOPT_POSTFIELDS], true);
aiProviderExpect($request->url === 'https://api.mistral.ai/v1/chat/completions' && $body['model'] === 'mistral-small-latest' && $body['response_format']['type'] === 'json_object' && in_array('Authorization: Bearer mistral-key', $request->options[CURLOPT_HTTPHEADER], true), 'Mistral text must use its native endpoint, selected model, key and JSON mode.');
$GLOBALS['aiProviderResponse'] = '{"choices":[{"message":{"content":"{\"greeting\":{\"text\":\"Hello {name}\"}}"},"finish_reason":"stop"}]}';
$translated = translationBundleTranslateWithAi('test.mistral', 'en', ['greeting' => ['text' => 'Bonjour {name}']]);
$body = json_decode($GLOBALS['aiProviderLastRequest']->options[CURLOPT_POSTFIELDS], true);
aiProviderExpect($body['model'] === 'mistral-translation' && $translated['greeting']['text'] === 'Hello {name}', 'Mistral translations must use their independent model.');
aiProviderExpect(commonAiGetTranscriptionApiKey() === 'mistral-key' && commonAiGetTranscriptionModel() === 'voxtral-mini-latest', 'Matching Mistral providers may share a key but must ignore legacy OpenAI audio models.');

foreach (['groq' => ['https://api.groq.com/openai/v1/audio/transcriptions', 'whisper-large-v3-turbo'], 'mistral' => ['https://api.mistral.ai/v1/audio/transcriptions', 'voxtral-mini-latest']] as $provider => [$url, $model]) {
    aiProviderEnv(['AI_PROVIDER' => 'anthropic', 'AI_API_KEY' => 'private-claude-key', 'TRANSCRIPTION_PROVIDER' => $provider, 'OPENAI_API_KEY' => 'private-openai-key', 'OPENAI_TRANSCRIPTION_MODEL' => 'old-openai-model']);
    aiProviderExpect(commonAiGetTranscriptionApiKey() === '' && !commonAiIsTranscriptionConfigured() && commonAiGetTranscriptionModel() === $model, 'New audio providers must never inherit another provider key or legacy audio model.');
    $_ENV['TRANSCRIPTION_API_KEY'] = $provider . '-audio-key';
    $GLOBALS['aiProviderResponse'] = '{"text":"Recognized speech"}';
    $audio = commonOpenAiRequestAudioTranscription(commonAiGetTranscriptionApiKey(), __FILE__, 'audio/ogg', 'recording.ogg', ['model' => commonAiGetTranscriptionModel(), 'response_format' => 'json', 'prompt' => 'French dictation.']);
    $request = $GLOBALS['aiProviderLastRequest'];
    $multipart = $request->options[CURLOPT_POSTFIELDS];
    aiProviderExpect($audio['status'] && $audio['text'] === 'Recognized speech' && $request->url === $url && $multipart['model'] === $model && $multipart['file'] instanceof \CURLFile && in_array('Authorization: Bearer ' . $provider . '-audio-key', $request->options[CURLOPT_HTTPHEADER], true), 'Each audio provider must receive its own credentials, model and file.');
    aiProviderExpect($provider === 'mistral' ? (!isset($multipart['prompt']) && !isset($multipart['response_format'])) : ($multipart['response_format'] === 'json' && $multipart['prompt'] === 'French dictation.'), 'Audio payloads must match the selected service supported fields.');
    $_ENV['TRANSCRIPTION_MODEL'] = 'custom-audio-model';
    aiProviderExpect(commonAiGetTranscriptionModel() === 'custom-audio-model', 'Explicit custom audio models must remain authoritative.');
}

$current = ['AI_PROVIDER' => 'mistral', 'AI_API_KEY' => 'saved-key', 'AI_MODEL' => 'mistral-small-latest', 'TRANSCRIPTION_PROVIDER' => 'groq', 'TRANSCRIPTION_API_KEY' => 'saved-audio-key'];
$disabled = array_merge($current, ['AI_PROVIDER' => 'disabled']);
aiProviderExpect(serverEnvAdminValidateValues($disabled, $current) === [], 'Disabling text must not demand a new key.');
$mergedDisabled = serverEnvAdminMergeSubmittedValues(['AI_PROVIDER' => 'disabled'], $current);
aiProviderExpect(array_intersect_key($mergedDisabled, $disabled) === $disabled, 'Disabling text must preserve saved keys and models.');
aiProviderEnv(array_merge($disabled, ['TRANSCRIPTION_MODEL' => 'whisper-large-v3-turbo']));
$GLOBALS['aiProviderLastRequest'] = null;
aiProviderExpect(!commonAiIsConfigured('explicit-model', 'explicit-key') && !commonAiUserCanUse(123, 'explicit-model', 'explicit-key') && !commonAiRequestText('explicit-key', ['model' => 'explicit-model', 'messages' => []])['status'], 'Disabled text must block user tools and even direct calls with explicit credentials.');
aiProviderExpect(!\translationBundleQueueRefresh('disabled.test', 'en', ['greeting' => ['text' => 'Bonjour']]), 'Disabled text must stop translation queueing before touching the database or launching a worker.');
try {
    translationBundleTranslateWithAi('disabled.test', 'en', ['greeting' => ['text' => 'Bonjour']]);
    throw new RuntimeException('Disabled translation unexpectedly succeeded.');
} catch (RuntimeException $error) {
    aiProviderExpect($error->getMessage() === 'Text AI is disabled.', 'Disabled translations must stop before constructing a provider request.');
}
aiProviderExpect($GLOBALS['aiProviderLastRequest'] === null && commonAiIsTranscriptionConfigured(), 'Disabling text must make zero text HTTP calls while preserving independent audio.');
aiProviderExpect(serverEnvAdminValidateValues($current, $disabled) !== [], 'Re-enabling with an inherited key must require explicit confirmation of the destination key.');
aiProviderExpect(serverEnvAdminValidateValues($current, $disabled, ['AI_API_KEY' => 'saved-key']) === [], 'Explicitly re-entering the same key must allow re-enabling the same provider.');
$audioChange = array_merge($current, ['TRANSCRIPTION_PROVIDER' => 'mistral']);
aiProviderExpect(serverEnvAdminValidateValues($audioChange, $current) !== [], 'Changing audio provider must not forward the old key automatically.');
aiProviderExpect(serverEnvAdminValidateValues($audioChange, $current, ['TRANSCRIPTION_API_KEY' => 'saved-audio-key']) === [], 'An explicitly entered audio key must acknowledge the new destination.');
aiProviderExpect(serverEnvAdminValidateValues(array_merge($current, ['TRANSCRIPTION_PROVIDER' => 'disabled']), $current) === [], 'Disabling audio must preserve its key without demanding replacement.');

echo "PASS text providers, independent translation models, OpenAI/Groq/Mistral audio, disabled text without HTTP or queueing, legacy settings and destination-key guards\n";
