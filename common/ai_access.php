<?php

require_once __DIR__ . '/patreon.php';

function commonOpenAiGetApiKey(): string
{
    if (array_key_exists('OpenAI', $GLOBALS)) {
        return trim((string)$GLOBALS['OpenAI']);
    }

    return function_exists('envValue') ? trim((string)envValue('OPENAI_API_KEY', '')) : '';
}

function commonAiIsConfigured(?string $model = null, ?string $apiKey = null): bool
{
    $model = $model ?? (defined('MODEL') ? (string)MODEL : '');
    return trim($apiKey ?? commonOpenAiGetApiKey()) !== ''
        && trim($model) !== ''
        && function_exists('curl_init');
}

function commonAiUserCanUse(int $userId, ?string $model = null, ?string $apiKey = null): bool
{
    return commonAiIsConfigured($model, $apiKey) && patreonUserCanUseAi($userId);
}

function commonAiGetCurrentUserId(): int
{
    return function_exists('commonGetCurrentUserId')
        ? (int)commonGetCurrentUserId()
        : (int)($_SESSION['currentUser'] ?? 0);
}
