<?php
declare(strict_types=1);

namespace OmoInfomaniakJsonTest;

use RuntimeException;

// Exercise the real document/FAQ helpers and adapter, replacing only access and HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/openai_text.php';
require_once dirname(__DIR__) . '/common/faq_ai.php';

function commonAiUserCanUse(...$arguments): bool { return true; }
function commonAiGetCurrentUserId(): int { return 1; }
function commonAiRecordUsage(...$arguments): void {}
function curl_init($url) { return (object)['url' => $url, 'options' => []]; }
function curl_setopt_array($curl, array $options) { $curl->options = $options; return true; }
function curl_getinfo($curl, $option) { return $GLOBALS['jsonHttpCode']; }
function curl_error($curl) { return 'Simulated HTTP failure'; }
function curl_exec($curl)
{
    $GLOBALS['jsonLastRequest'] = json_decode($curl->options[CURLOPT_POSTFIELDS], true, 512, JSON_THROW_ON_ERROR);
    if (($GLOBALS['jsonLastRequest']['response_format']['type'] ?? '') !== 'json_schema') {
        $GLOBALS['jsonHttpCode'] = 400;
        return json_encode(['error' => ['message' => 'json_object is no longer supported; use json_schema.']]);
    }
    $GLOBALS['jsonHttpCode'] = 200;
    return json_encode(['choices' => [['message' => ['content' => json_encode($GLOBALS['jsonReply'])], 'finish_reason' => 'stop']]]);
}

foreach (['ai_client.php', 'openai_text.php', 'faq_ai.php'] as $file) {
    $source = (string)file_get_contents(dirname(__DIR__) . '/common/' . $file);
    $source = preg_replace('/^require_once[^\n]*;\s*$/m', '', substr($source, strlen('<?php')));
    eval('namespace ' . __NAMESPACE__ . '; use \Throwable; use \InvalidArgumentException;' . $source);
}
$_ENV['AI_PROVIDER'] = 'infomaniak';
$_ENV['AI_API_KEY'] = 'fixture-key';
$_ENV['AI_INFOMANIAK_PRODUCT_ID'] = '12345';
$_ENV['AI_MODEL'] = 'fixture-model';

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function checkSchema(array $properties): void
{
    $payload = $GLOBALS['jsonLastRequest'];
    $format = $payload['response_format'];
    check($format['type'] === 'json_schema' && !empty($format['json_schema']['name']), 'Infomaniak requires a named JSON schema.');
    $schema = $format['json_schema']['schema'];
    check($schema['type'] === 'object' && $schema['properties'] === $properties, 'The schema must describe the actual expected response fields and types.');
    check($schema['required'] === array_keys($properties) && $schema['additionalProperties'] === false, 'All expected fields are required and unknown fields are excluded.');
    check($payload['model'] === 'fixture-model' && $payload['stream'] === false, 'Keep the configured model and non-streaming transport.');
    check(!isset($payload['max_tokens']) && $payload['max_completion_tokens'] > 0, 'Keep Infomaniak token parameter adaptation.');
}

$GLOBALS['jsonReply'] = ['rewritten_text' => 'Rewritten passage'];
$result = commonOpenAiRewriteSelectedDocumentText('Passage', 'Full document with Passage', ['user_id' => 1]);
check(!empty($result['status']) && $result['text'] === 'Rewritten passage', 'Memo/PV rewriting must work when the provider rejects json_object: ' . ($result['message'] ?? ''));
checkSchema(['rewritten_text' => ['type' => 'string']]);

foreach ([[], ['pv_summary' => true]] as $options) {
    $GLOBALS['jsonReply'] = ['summarized_text' => 'Short summary'];
    $result = commonOpenAiSummarizeSelectedDocumentText('Passage', 'Full document', $options + ['user_id' => 1]);
    check(!empty($result['status']) && $result['text'] === 'Short summary', 'Selection and complete PV summaries must use the supported format.');
    checkSchema(['summarized_text' => ['type' => 'string']]);
}
$result = commonOpenAiSummarizeGovernanceChanges([['action' => 'create', 'name' => 'Role']], 'fr');
check(!empty($result['status']) && $result['text'] === 'Short summary', 'Governance summaries must use the supported format.');
checkSchema(['summarized_text' => ['type' => 'string']]);

foreach ([[], [['id' => 7, 'question' => 'Question', 'answer' => 'Answer']]] as $sources) {
    $GLOBALS['jsonReply'] = ['supported' => true, 'answer' => 'Supported answer'];
    $properties = ['supported' => ['type' => 'boolean'], 'answer' => ['type' => 'string']];
    if ($sources) {
        $GLOBALS['jsonReply']['faq_ids'] = [7];
        $properties['faq_ids'] = ['type' => 'array', 'items' => ['type' => 'integer']];
    }
    $result = faqAiFindAnswer('Question', '', $sources, __NAMESPACE__ . '\\commonOpenAiRequestChatCompletion', 1);
    check($result['answer'] === 'Supported answer', 'FAQ/changelog answers must retain their structured fields.');
    checkSchema($properties);
}
foreach (['openai', 'mistral', 'openai_compatible', 'anthropic'] as $provider) {
    $_ENV['AI_PROVIDER'] = $provider;
    $_ENV['AI_BASE_URL'] = 'https://compatible.example.invalid/v1';
    $format = commonAiJsonObjectResponseFormat('document_rewrite', ['rewritten_text' => ['type' => 'string']]);
    check($format === ['type' => 'json_object'], 'Other providers keep their existing JSON mode: ' . $provider);
    $request = commonAiBuildTextRequest('fixture-key', [
        'model' => 'fixture-model', 'response_format' => $format,
        'messages' => [['role' => 'system', 'content' => 'Return JSON.'], ['role' => 'user', 'content' => 'Rewrite.']],
    ]);
    check($provider === 'anthropic' ? !isset($request['payload']['response_format'])
        : $request['payload']['response_format'] === $format, 'Keep provider-specific request adaptation: ' . $provider);
}
echo "ai_infomaniak_json_format_test: OK\n";
