<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/ai_infomaniak.php';
require_once dirname(__DIR__) . '/includes/server_env_admin.php';

function infomaniakCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function infomaniakReject(callable $operation, string $reason): void
{
    try { $operation(); }
    catch (RuntimeException | InvalidArgumentException $error) {
        infomaniakCheck($error->getMessage() === $reason, 'Unexpected discovery error');
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $reason);
}
$before = $_SESSION ?? [];
try {
    infomaniakCheck(commonAiNormalizeInfomaniakToken("  Bearer fixture-key  ") === 'fixture-key', 'Accept a pasted Bearer header value');
    $submitted = serverEnvAdminReadSubmittedValues(['AI_PROVIDER' => 'infomaniak', 'AI_API_KEY' => 'Bearer fixture-key']);
    infomaniakCheck($submitted['AI_API_KEY'] === 'fixture-key', 'Save the same normalized token that discovery verifies');
    infomaniakCheck(serverEnvAdminGetSavedInfomaniakToken(['AI_PROVIDER' => 'infomaniak', 'AI_API_KEY' => 'Bearer stored-fixture-key']) === 'stored-fixture-key', 'A blank token field can reuse the persisted Infomaniak key');
    infomaniakCheck(serverEnvAdminGetSavedInfomaniakToken(['AI_PROVIDER' => 'openai', 'AI_API_KEY' => 'other-provider-key']) === '', 'Never reuse another provider key for Infomaniak discovery');
    infomaniakCheck(serverEnvAdminGetSavedInfomaniakToken(['AI_PROVIDER' => 'infomaniak', 'AI_API_KEY' => '']) === '', 'Missing saved credentials remain missing');
    infomaniakReject(fn () => commonAiInfomaniakGet('/1/ai', 'invalid token'), 'token_format');
    infomaniakReject(fn () => commonAiInfomaniakGet('/1/ai', ''), 'token_missing');
    infomaniakReject(fn () => commonAiInfomaniakGet('/1/ai', str_repeat('x', 8193)), 'token_length');
    $products = [['product_id' => 123, 'product_name' => 'AI Services', 'account_name' => 'Organization A']];
    $requests = [];
    $get = static function (string $path, string $token) use (&$products, &$requests): array {
        infomaniakCheck($token === 'fixture-key', 'Discovery must use only its explicit token');
        $requests[] = $path;
        return match ($path) {
            '/1/ai' => $products,
            '/2/ai/123/openai/v1/models' => [['id' => 'mistral-small'], ['id' => 'embedding'], ['id' => 'vision-model']],
            '/2/ai/456/openai/v1/models' => [['id' => 'other-model']],
            '/2/ai/999/openai/v1/models' => throw new RuntimeException('product'),
            '/1/ai/models' => [['name' => 'mistral-small', 'type' => 'llm'], ['name' => 'embedding', 'type' => 'embedding']],
            default => throw new RuntimeException('Unexpected path'),
        };
    };
    $single = commonAiInfomaniakDiscover('fixture-key', '', $get);
    infomaniakCheck($single['productId'] === '123', 'A single product must be selected automatically');
    infomaniakCheck(array_column($single['models'], 'id') === ['mistral-small', 'vision-model'], 'Exclude known non-text types while retaining unknown chat and vision models');
    infomaniakCheck(str_contains($single['products'][0]['label'], 'Organization A'), 'Product labels identify organizations');
    $products[] = ['product_id' => 456, 'product_name' => 'Other', 'account_name' => 'Organization B'];
    $requests = [];
    $multiple = commonAiInfomaniakDiscover('fixture-key', '', $get);
    infomaniakCheck($multiple['productId'] === '' && $multiple['models'] === [] && $requests === ['/1/ai'], 'Several products require explicit selection before model discovery');
    $requests = [];
    $chosen = commonAiInfomaniakDiscover('fixture-key', '456', $get);
    infomaniakCheck($chosen['models'][0]['id'] === 'other-model', 'Models belong to the chosen product');
    infomaniakCheck(!in_array('/1/ai', $requests, true), 'An explicit Product ID does not require product-list permissions');
    $requests = [];
    infomaniakReject(fn () => commonAiInfomaniakDiscover('fixture-key', '999', $get), 'product');
    infomaniakCheck($requests === ['/2/ai/999/openai/v1/models'], 'The product-specific API enforces access to an explicit identifier');
    $requests = [];
    infomaniakReject(fn () => commonAiInfomaniakDiscover('fixture-key', '../456', $get), 'product_format');
    infomaniakCheck($requests === [], 'Reject malformed Product IDs before any HTTP request');
    infomaniakReject(fn () => commonAiInfomaniakDiscover('fixture-key', '', static fn () => []), 'products');
    $fallback = commonAiInfomaniakDiscover('fixture-key', '123', static function ($path, $token) use ($get) {
        if ($path === '/1/ai/models') throw new RuntimeException('connection');
        return $get($path, $token);
    });
    infomaniakCheck(count($fallback['models']) === 3, 'Optional catalog outage must preserve product model discovery');
    $objectList = commonAiInfomaniakDiscover('fixture-key', '123', static function ($path, $token) use ($get) {
        if ($path === '/2/ai/123/openai/v1/models') return ['id' => 'mistral-small'];
        return $get($path, $token);
    });
    infomaniakCheck(count($objectList['models']) === 1, 'Support documented single-object response');
    foreach (['', '0', '../456', '123?foo', '1/2', ' 1 2 '] as $id) {
        infomaniakReject(fn () => commonAiGetInfomaniakBaseUrl($id), 'Produit Infomaniak invalide.');
    }
    serverEnvAdminRememberInfomaniakDiscovery('fixture-key', $single);
    $values = ['AI_PROVIDER' => 'infomaniak', 'AI_API_KEY' => 'fixture-key', 'AI_INFOMANIAK_PRODUCT_ID' => '123', 'AI_MODEL' => 'mistral-small', 'AI_TRANSLATION_MODEL' => ''];
    infomaniakCheck(serverEnvAdminInfomaniakSelectionIsVerified($values, []), 'Discovered choices may be saved');
    infomaniakCheck(!str_contains(json_encode($_SESSION['server_env_infomaniak']), 'fixture-key'), 'Receipts never store the token');
    foreach (['AI_API_KEY' => 'different-key', 'AI_INFOMANIAK_PRODUCT_ID' => '456', 'AI_MODEL' => 'invented', 'AI_TRANSLATION_MODEL' => 'invented'] as $key => $value) {
        infomaniakCheck(!serverEnvAdminInfomaniakSelectionIsVerified(array_merge($values, [$key => $value]), []), 'Changed credential or invalid choice requires discovery: ' . $key);
    }
    unset($_SESSION['server_env_infomaniak']);
    infomaniakCheck(serverEnvAdminInfomaniakSelectionIsVerified($values, $values), 'Unchanged settings survive discovery outage');
    infomaniakCheck(!serverEnvAdminInfomaniakSelectionIsVerified($values, []), 'New settings cannot bypass discovery');
    infomaniakCheck(serverEnvAdminValidateValues($values) === [], 'Valid dedicated provider configuration accepted');
    infomaniakCheck(serverEnvAdminValidateValues(array_merge($values, ['AI_INFOMANIAK_PRODUCT_ID' => ''])) !== [], 'Missing product rejected');
    echo "PASS Infomaniak product discovery, model selection, outages and token-bound configuration\n";
} finally { $_SESSION = $before; }
