<?php

require_once __DIR__ . '/ai_config.php';

/** Text-only adapter shared by document tools, Telegram and UI translations. */
function commonAiBuildTextRequest(string $apiKey, array $payload): array
{
    $provider = commonAiGetProvider();
    if (!in_array($provider, ['openai', 'anthropic', 'mistral', 'openai_compatible'], true)) {
        throw new InvalidArgumentException('Fournisseur IA inconnu.');
    }
    $headers = ['Content-Type: application/json'];
    if ($provider === 'anthropic') {
        $system = [];
        $messages = [];
        foreach ($payload['messages'] ?? [] as $message) {
            if (!is_string($message['content'] ?? null)) throw new InvalidArgumentException('Seuls les messages texte sont pris en charge.');
            if (in_array($message['role'] ?? '', ['system', 'developer'], true)) {
                $system[] = $message['content'];
            } else {
                $messages[] = $message;
            }
        }
        $payload = [
            'model' => (string)($payload['model'] ?? ''),
            'system' => implode("\n\n", $system),
            'messages' => $messages,
            'max_tokens' => (int)($payload['max_tokens'] ?? 4096),
        ];
        // Claude JSON responses are requested in the system prompt and validated by callers.
        // Omit sampling settings so models that do not support them remain usable.
        $headers[] = 'x-api-key: ' . $apiKey;
        $headers[] = 'anthropic-version: 2023-06-01';
        $url = 'https://api.anthropic.com/v1/messages';
    } else {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
        $url = match ($provider) {
            'openai_compatible' => commonAiGetCompatibleBaseUrl() . '/chat/completions',
            'mistral' => 'https://api.mistral.ai/v1/chat/completions',
            default => 'https://api.openai.com/v1/chat/completions',
        };
    }
    return ['url' => $url, 'headers' => $headers, 'payload' => $payload];
}

function commonAiDecodeTextResponse(array $response, int $httpCode): array
{
    if ($httpCode < 200 || $httpCode >= 300 || isset($response['error'])) {
        return ['status' => false, 'http_code' => $httpCode, 'message' => (string)($response['error']['message'] ?? 'La requete IA a echoue.')];
    }
    if (commonAiGetProvider() === 'anthropic') {
        $parts = [];
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') $parts[] = (string)($block['text'] ?? '');
        }
        $content = trim(implode("\n", $parts));
        $truncated = ($response['stop_reason'] ?? '') === 'max_tokens';
    } else {
        $content = trim((string)($response['choices'][0]['message']['content'] ?? ''));
        $truncated = ($response['choices'][0]['finish_reason'] ?? '') === 'length';
    }
    if ($content === '' || $truncated) {
        return ['status' => false, 'http_code' => $httpCode, 'message' => $truncated ? 'La reponse IA est incomplete.' : 'La reponse IA est vide.'];
    }
    return ['status' => true, 'content' => $content, 'http_code' => $httpCode];
}

function commonAiRequestText(string $apiKey, array $payload, int $timeout = 120): array
{
    if ($apiKey === '' || trim((string)($payload['model'] ?? '')) === '' || !function_exists('curl_init')) {
        return ['status' => false, 'message' => 'Configuration IA indisponible.'];
    }
    try {
        $request = commonAiBuildTextRequest($apiKey, $payload);
        $encoded = json_encode($request['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        return ['status' => false, 'message' => 'Configuration ou requete IA invalide.'];
    }
    $curl = curl_init($request['url']);
    if ($curl === false) return ['status' => false, 'message' => 'Impossible de preparer la requete IA.'];
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $encoded,
        CURLOPT_HTTPHEADER => $request['headers'],
        CURLOPT_CONNECTTIMEOUT => min(10, max(1, $timeout)),
        CURLOPT_TIMEOUT => max(1, $timeout),
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $response = curl_exec($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($response === false) return ['status' => false, 'http_code' => $httpCode, 'message' => curl_error($curl) ?: 'La requete IA a echoue.'];
    $decoded = json_decode((string)$response, true);
    if (!is_array($decoded)) return ['status' => false, 'http_code' => $httpCode, 'message' => 'La reponse IA est invalide.'];
    return commonAiDecodeTextResponse($decoded, $httpCode);
}
