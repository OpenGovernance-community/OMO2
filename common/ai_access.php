<?php

require_once __DIR__ . '/patreon.php';
require_once __DIR__ . '/ai_config.php';

function commonOpenAiGetApiKey(): string
{
    return commonAiGetApiKey();
}

function commonAiIsConfigured(?string $model = null, ?string $apiKey = null): bool
{
    $model = $model ?? commonAiGetModel();
    if (commonAiGetProvider() === 'openai_compatible') {
        try { commonAiGetCompatibleBaseUrl(); }
        catch (InvalidArgumentException $error) { return false; }
    }
    return in_array(commonAiGetProvider(), ['openai', 'anthropic', 'mistral', 'openai_compatible'], true)
        && trim($apiKey ?? commonAiGetApiKey()) !== ''
        && trim($model) !== ''
        && function_exists('curl_init');
}

function commonAiIsTranscriptionConfigured(?string $model = null, ?string $apiKey = null): bool
{
    return in_array(commonAiGetTranscriptionProvider(), ['openai', 'groq', 'mistral'], true)
        && trim($apiKey ?? commonAiGetTranscriptionApiKey()) !== ''
        && trim($model ?? commonAiGetTranscriptionModel()) !== ''
        && function_exists('curl_init');
}

function commonAiUserCanTranscribe(int $userId, ?string $model = null): bool
{
    return commonAiIsTranscriptionConfigured($model) && patreonUserCanUseAi($userId);
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
