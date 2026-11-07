<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/ai_disclosure.php';
require_once dirname(__DIR__) . '/common/legal_page_helper.php';

function privacyCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function privacyEnv(array $values): void
{
    foreach (['AI_PROVIDER', 'AI_API_KEY', 'AI_MODEL', 'AI_TRANSLATION_MODEL', 'AI_BASE_URL', 'AI_INFOMANIAK_PRODUCT_ID',
        'TRANSCRIPTION_PROVIDER', 'TRANSCRIPTION_API_KEY', 'TRANSCRIPTION_MODEL', 'TRANSCRIPTION_INFOMANIAK_PRODUCT_ID',
        'OPENAI_API_KEY', 'OPENAI_MODEL', 'OPENAI_TRANSLATION_MODEL', 'OPENAI_TRANSCRIPTION_MODEL', 'OPENAI_AUDIO_TRANSCRIPTION_MODEL'] as $key) {
        $_ENV[$key] = $values[$key] ?? '';
    }
    unset($GLOBALS['OpenAI'], $GLOBALS['openAiTranslationModel'], $GLOBALS['openAiTranscriptionModel']);
}
// Render the real public page without its bootstrap or translation queue touching the database.
eval('namespace OmoPrivacyRenderTest;
function commonLegalResolveLocale() { return "fr"; }
function commonLegalLoadBundle($key, $source, $locale) { $GLOBALS["privacySource"] = $source; return $source; }
function commonLegalT($key, $vars = [], $bundle = null, $source = null) {
    $text = $bundle[$key]["text"] ?? $source[$key]["text"] ?? $key;
    foreach ($vars as $name => $value) $text = str_replace("{" . $name . "}", (string)$value, $text);
    return $text;
}');
function privacyRender(): string
{
    $source = file_get_contents(dirname(__DIR__) . '/common/politique-confidentialite.php');
    foreach (["require_once __DIR__ . '/../shared_functions.php';", "require_once __DIR__ . '/legal_page_helper.php';", "require_once __DIR__ . '/ai_disclosure.php';"] as $include) $source = str_replace($include, '', $source);
    ob_start();
    try { eval('namespace OmoPrivacyRenderTest; use \DateTimeImmutable;' . substr($source, strlen('<?php'))); return ob_get_clean(); }
    catch (Throwable $error) { ob_end_clean(); throw $error; }
}
$before = $_ENV;
$globalsBefore = [];
foreach (['OpenAI', 'openAiTranslationModel', 'openAiTranscriptionModel'] as $name) if (isset($GLOBALS[$name])) $globalsBefore[$name] = $GLOBALS[$name];
try {
    privacyEnv(['AI_PROVIDER' => 'infomaniak', 'AI_API_KEY' => 'PRIVATE-TEXT-TOKEN', 'AI_INFOMANIAK_PRODUCT_ID' => '987654321',
        'AI_MODEL' => 'mistralai/main-test-model', 'AI_TRANSLATION_MODEL' => 'mistralai/translation-test-model',
        'TRANSCRIPTION_PROVIDER' => 'groq', 'TRANSCRIPTION_API_KEY' => 'PRIVATE-AUDIO-TOKEN', 'TRANSCRIPTION_MODEL' => 'whisper-large-v3-turbo']);
    $html = privacyRender();
    foreach (['Infomaniak', 'mistralai/main-test-model', 'mistralai/translation-test-model', 'Groq', 'whisper-large-v3-turbo', 'enregistrement audio', 'du contenu texte est envoye'] as $expected) privacyCheck(str_contains($html, $expected), 'Public page is missing: ' . $expected);
    foreach (['PRIVATE-TEXT-TOKEN', 'PRIVATE-AUDIO-TOKEN', '987654321'] as $private) privacyCheck(!str_contains($html, $private), 'Private configuration leaked');
    privacyCheck(!str_contains(json_encode($GLOBALS['privacySource']), 'mistralai/main-test-model'), 'Dynamic settings must never enter shared translation source/cache');
    $_ENV['AI_PROVIDER'] = 'anthropic'; $_ENV['AI_MODEL'] = 'claude-test'; $_ENV['AI_TRANSLATION_MODEL'] = '';
    $_ENV['TRANSCRIPTION_PROVIDER'] = 'infomaniak'; $_ENV['TRANSCRIPTION_INFOMANIAK_PRODUCT_ID'] = '123'; $_ENV['TRANSCRIPTION_MODEL'] = 'whisper';
    $changed = commonAiGetPublicServices();
    privacyCheck($changed['functions']['provider'] === 'Anthropic (Claude)' && $changed['translation']['model'] === 'claude-test', 'Translation inherits the effective main model');
    privacyCheck($changed['transcription']['provider'] === 'Infomaniak' && $changed['transcription']['status'] === 'configured', 'Audio remains independent of text');
    $html = privacyRender();
    privacyCheck(str_contains($html, 'claude-test') && !str_contains($html, 'mistralai/main-test-model'), 'Public page follows runtime changes without a translation-cache refresh');
    $_ENV['AI_PROVIDER'] = 'disabled';
    $disabled = commonAiGetPublicServices();
    privacyCheck($disabled['functions']['status'] === 'disabled' && $disabled['functions']['model'] === '' && $disabled['transcription']['status'] === 'configured', 'Disabled text does not disclose old models or hide active independent audio');
    $_ENV['AI_PROVIDER'] = 'openai_compatible'; $_ENV['AI_MODEL'] = 'compatible-model';
    $_ENV['AI_BASE_URL'] = 'https://gateway.example.org/private-endpoint-marker/v1';
    $html = privacyRender();
    privacyCheck(str_contains($html, 'gateway.example.org') && !str_contains($html, 'private-endpoint-marker'), 'Custom compatible service discloses only its hostname');
    $_ENV['AI_BASE_URL'] = '';
    privacyCheck(commonAiGetPublicServices()['functions']['provider'] === 'OpenRouter', 'Default compatible API identifies the actual configured gateway');
    $_ENV['AI_MODEL'] = '<script>privateScript()</script>';
    $html = privacyRender();
    privacyCheck(str_contains($html, '&lt;script&gt;privateScript()&lt;/script&gt;') && !str_contains($html, '<script>privateScript()'), 'Model settings are escaped before legal HTML rendering');
    $_ENV['AI_API_KEY'] = '';
    privacyCheck(commonAiGetPublicServices()['functions']['status'] === 'unconfigured', 'Missing credentials do not claim an active service');
    privacyEnv(['AI_PROVIDER' => 'openai', 'OPENAI_API_KEY' => 'PRIVATE-LEGACY-TOKEN', 'OPENAI_MODEL' => 'legacy-main', 'OPENAI_TRANSLATION_MODEL' => 'legacy-translation']);
    $legacy = commonAiGetPublicServices();
    privacyCheck($legacy['functions']['model'] === 'legacy-main' && $legacy['translation']['model'] === 'legacy-translation', 'Legacy installations retain their effective disclosed models');
    echo "PASS public AI privacy disclosure, independent providers, model fallbacks, runtime refresh, safe HTML and no secrets\n";
} finally {
    $_ENV = $before;
    foreach (['OpenAI', 'openAiTranslationModel', 'openAiTranscriptionModel'] as $name) { unset($GLOBALS[$name]); if (array_key_exists($name, $globalsBefore)) $GLOBALS[$name] = $globalsBefore[$name]; }
    unset($GLOBALS['privacySource']);
}
