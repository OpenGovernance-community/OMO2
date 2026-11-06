<?php

require_once __DIR__ . '/ai_config.php';

/** Configuration discovery only: no prompts, metering or provider-controlled URLs. */
function commonAiInfomaniakGet(string $path, string $token): array
{
    $token = commonAiNormalizeInfomaniakToken($token);
    if ($token === '') throw new RuntimeException('token_missing');
    if (preg_match('/[\x00-\x20\x7f]/', $token)) throw new RuntimeException('token_format');
    if (strlen($token) > 8192) throw new RuntimeException('token_length');
    if (!function_exists('curl_init')) throw new RuntimeException('connection');
    $curl = curl_init('https://api.infomaniak.com' . $path);
    if ($curl === false) throw new RuntimeException('connection');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
    ]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    // Never relay provider response bodies or cURL diagnostics that may contain secrets.
    if ($status === 401) throw new RuntimeException('token_invalid');
    if ($status === 403) throw new RuntimeException('token_denied');
    if ($status === 429) throw new RuntimeException('rate_limit');
    if ($status === 404 && str_starts_with($path, '/2/ai/')) throw new RuntimeException('product');
    if ($body === false && curl_errno($curl) === CURLE_OPERATION_TIMEDOUT) throw new RuntimeException('timeout');
    if ($body === false || $status < 200 || $status >= 300) throw new RuntimeException('connection');
    $data = json_decode($body, true);
    if (!is_array($data) || !is_array($data['data'] ?? null) || ($data['result'] ?? '') === 'error') {
        throw new RuntimeException('response');
    }
    return $data['data'];
}

/** An explicit product goes straight to its API; product discovery remains optional. */
function commonAiInfomaniakDiscover(string $token, string $productId = '', ?callable $get = null): array
{
    $get ??= 'commonAiInfomaniakGet';
    $products = [];
    if ($productId !== '') {
        try { commonAiGetInfomaniakBaseUrl($productId); }
        catch (InvalidArgumentException $error) { throw new RuntimeException('product_format'); }
        $products[$productId] = ['id' => $productId, 'label' => 'AI Services #' . $productId];
    } else {
        foreach ($get('/1/ai', $token) as $row) {
            $id = (string)($row['product_id'] ?? '');
            if (!preg_match('/\A[1-9][0-9]{0,18}\z/D', $id)) continue;
            $products[$id] = ['id' => $id, 'label' => (string)($row['product_name'] ?? $id)
                . ' - ' . (string)($row['account_name'] ?? '') . ' (#' . $id . ')'];
        }
    }
    if (!$products) throw new RuntimeException('products');
    if ($productId === '' && count($products) === 1) $productId = (string)array_key_first($products);
    $models = [];
    if ($productId !== '') {
        commonAiGetInfomaniakBaseUrl($productId);
        $rows = $get('/2/ai/' . $productId . '/openai/v1/models', $token);
        // Some versions document a single model object instead of an array.
        if (isset($rows['id'])) $rows = [$rows];
        $catalog = [];
        try {
            foreach ($get('/1/ai/models', $token) as $row) {
                $catalog[(string)($row['name'] ?? '')] = $row;
            }
        } catch (RuntimeException $error) {
            // Optional descriptions must not block the authoritative product model list.
        }
        foreach ($rows as $row) {
            $id = trim((string)($row['id'] ?? ''));
            if ($id === '' || strlen($id) > 255 || preg_match('/[\x00-\x1f\x7f]/', $id)) continue;
            $type = strtolower((string)($catalog[$id]['type'] ?? ''));
            if (preg_match('/embed|rerank|audio|transcri|speech|image|diffusion/', $type)) continue;
            $models[$id] = ['id' => $id, 'label' => $id];
        }
        if (!$models) throw new RuntimeException('models');
        ksort($models, SORT_NATURAL | SORT_FLAG_CASE);
    }
    return ['products' => array_values($products), 'productId' => $productId, 'models' => array_values($models)];
}
