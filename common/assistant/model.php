<?php

require_once dirname(__DIR__) . '/ai_client.php';

/** Keep native provider messages intact during a turn (including reasoning/tool IDs). */
function commonAssistantModelRequest(string $system, array $messages, array $tools): array
{
    $provider = commonAiGetProvider();
    $model = commonAiGetModel();
    $key = commonAiGetApiKey();
    $functions = array_map(static fn (array $tool): array => [
        'name' => $tool['name'], 'description' => $tool['description'],
        'parameters' => $tool['inputSchema'],
    ], $tools);
    if ($provider === 'openai') {
        foreach ($functions as &$function) {
            // Responses forbids top-level anyOf in function parameters; the shared validator still enforces it.
            unset($function['parameters']['anyOf']);
            if (($function['parameters']['properties'] ?? null) === []) $function['parameters']['properties'] = new stdClass();
        }
        unset($function);
    }
    if ($provider === 'openai') {
        return ['url' => 'https://api.openai.com/v1/responses',
            'headers' => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
            'payload' => ['model' => $model, 'instructions' => $system, 'input' => $messages,
                'tools' => array_map(static fn ($tool) => ['type' => 'function', 'strict' => false] + $tool, $functions),
                'store' => false, 'include' => ['reasoning.encrypted_content'], 'max_output_tokens' => 3000]];
    }
    if ($provider === 'anthropic') {
        return ['url' => 'https://api.anthropic.com/v1/messages',
            'headers' => ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'],
            'payload' => ['model' => $model, 'system' => $system, 'messages' => $messages, 'max_tokens' => 3000,
                'tools' => array_map(static fn ($tool) => ['name' => $tool['name'],
                    'description' => $tool['description'], 'input_schema' => $tool['parameters']], $functions)]];
    }
    return commonAiBuildTextRequest($key, ['model' => $model,
        'messages' => array_merge([['role' => 'system', 'content' => $system]], $messages),
        'max_tokens' => 3000,
        'tools' => array_map(static fn ($tool) => ['type' => 'function', 'function' => $tool], $functions)]);
}

function commonAssistantDecodeModel(array $response, int $httpCode): array
{
    if ($httpCode < 200 || $httpCode >= 300 || isset($response['error'])) {
        $code = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string)($response['error']['code'] ?? $response['error']['type'] ?? 'unknown'));
        $param = preg_replace('/[^a-zA-Z0-9_.\[\]-]/', '', (string)($response['error']['param'] ?? ''));
        error_log('OMO assistant provider=' . commonAiGetProvider() . ' http=' . $httpCode . ' code=' . substr($code, 0, 100) . ' param=' . substr($param, 0, 100));
        throw new RuntimeException('provider');
    }
    $text = []; $calls = []; $continuation = [];
    $provider = commonAiGetProvider();
    if ($provider === 'openai') {
        if (($response['status'] ?? '') !== 'completed') throw new RuntimeException('provider');
        $continuation = $response['output'] ?? [];
        foreach ($continuation as $item) {
            if (($item['type'] ?? '') === 'function_call') {
                $calls[] = ['id' => $item['call_id'], 'name' => $item['name'], 'arguments' => $item['arguments']];
            } elseif (($item['type'] ?? '') === 'message') {
                foreach ($item['content'] ?? [] as $part) {
                    if (($part['type'] ?? '') === 'output_text') $text[] = $part['text'];
                    if (($part['type'] ?? '') === 'refusal') $text[] = $part['refusal'];
                }
            }
        }
    } elseif ($provider === 'anthropic') {
        if (!in_array($response['stop_reason'] ?? '', ['end_turn', 'tool_use', 'stop_sequence'], true)) throw new RuntimeException('provider');
        $continuation = [['role' => 'assistant', 'content' => $response['content'] ?? []]];
        foreach ($response['content'] ?? [] as $part) {
            if (($part['type'] ?? '') === 'text') $text[] = $part['text'];
            if (($part['type'] ?? '') === 'tool_use') $calls[] = ['id' => $part['id'],
                'name' => $part['name'], 'arguments' => json_encode($part['input'], JSON_THROW_ON_ERROR)];
        }
    } else {
        $choice = $response['choices'][0] ?? [];
        if (!in_array($choice['finish_reason'] ?? '', ['stop', 'tool_calls'], true)) throw new RuntimeException('provider');
        $message = $choice['message'] ?? [];
        $continuation = [$message];
        if (is_string($message['content'] ?? null)) $text[] = $message['content'];
        foreach ($message['tool_calls'] ?? [] as $call) $calls[] = ['id' => $call['id'],
            'name' => $call['function']['name'], 'arguments' => $call['function']['arguments']];
    }
    if (!$calls && trim(implode("\n", $text)) === '') throw new RuntimeException('provider');
    return ['text' => trim(implode("\n", $text)), 'calls' => $calls, 'continuation' => $continuation];
}

function commonAssistantToolMessages(array $results): array
{
    return match (commonAiGetProvider()) {
        'openai' => array_map(static fn ($r) => ['type' => 'function_call_output', 'call_id' => $r['id'], 'output' => $r['content']], $results),
        'anthropic' => [['role' => 'user', 'content' => array_map(static fn ($r) => [
            'type' => 'tool_result', 'tool_use_id' => $r['id'], 'content' => $r['content'], 'is_error' => $r['error']], $results)]],
        default => array_map(static fn ($r) => ['role' => 'tool', 'tool_call_id' => $r['id'], 'name' => $r['name'], 'content' => $r['content']], $results),
    };
}
