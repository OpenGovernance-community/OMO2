<?php

require_once dirname(__DIR__) . '/includes/env.php';

function commonAiEnv(string $key, string $default = ''): string
{
    return trim((string)envValue($key, $default));
}

function commonAiGetProvider(): string
{
    return commonAiEnv('AI_PROVIDER') ?: 'openai';
}

function commonAiGetCompatibleBaseUrl(?string $configuredUrl = null): string
{
    $url = rtrim(trim($configuredUrl ?? commonAiEnv('AI_BASE_URL')), '/');
    if ($url === '') $url = 'https://openrouter.ai/api/v1';
    $parts = parse_url($url);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
        || filter_var($url, FILTER_VALIDATE_URL) === false || preg_match('/[\s\x00-\x1f\x7f]|%0[ad]/i', $url)
        || str_ends_with($url, '/chat/completions')) {
        throw new InvalidArgumentException('Adresse de base IA invalide.');
    }
    return $url;
}

function commonAiGetLegacyApiKey(): string
{
    return trim((string)($GLOBALS['OpenAI'] ?? commonAiEnv('OPENAI_API_KEY')));
}

function commonAiGetApiKey(): string
{
    $key = commonAiEnv('AI_API_KEY');
    return $key !== '' ? $key : (commonAiGetProvider() === 'openai' ? commonAiGetLegacyApiKey() : '');
}

function commonAiGetModel(bool $translation = false): string
{
    $model = commonAiEnv($translation ? 'AI_TRANSLATION_MODEL' : 'AI_MODEL');
    if ($model !== '') return $model;
    if ($translation && commonAiEnv('AI_MODEL') !== '') return commonAiGetModel();
    if (commonAiGetProvider() === 'openai') {
        $legacyModel = $translation
            ? trim((string)($GLOBALS['openAiTranslationModel'] ?? commonAiEnv('OPENAI_TRANSLATION_MODEL')))
            : commonAiEnv('OPENAI_MODEL');
        if ($legacyModel !== '') return $legacyModel;
    }
    return $translation ? commonAiGetModel() : (commonAiGetProvider() === 'openai' ? 'gpt-4o' : '');
}

function commonAiGetTranscriptionProvider(): string
{
    return commonAiEnv('TRANSCRIPTION_PROVIDER') ?: 'openai';
}

function commonAiGetTranscriptionApiKey(): string
{
    $key = commonAiEnv('TRANSCRIPTION_API_KEY');
    if ($key !== '') return $key;
    $provider = commonAiGetTranscriptionProvider();
    if (in_array($provider, ['openai', 'mistral'], true) && $provider === commonAiGetProvider()) return commonAiGetApiKey();
    return $provider === 'openai' ? commonAiGetLegacyApiKey() : '';
}

function commonAiGetTranscriptionDefaultModel(string $provider): string
{
    return match ($provider) {
        'openai' => 'gpt-4o-mini-transcribe',
        'groq' => 'whisper-large-v3-turbo',
        'mistral' => 'voxtral-mini-latest',
        default => '',
    };
}

function commonAiGetTranscriptionModel(): string
{
    $model = commonAiEnv('TRANSCRIPTION_MODEL');
    if ($model !== '') return $model;
    $provider = commonAiGetTranscriptionProvider();
    if ($provider === 'openai') {
        $legacy = trim((string)($GLOBALS['openAiTranscriptionModel'] ?? ''))
            ?: commonAiEnv('OPENAI_TRANSCRIPTION_MODEL') ?: commonAiEnv('OPENAI_AUDIO_TRANSCRIPTION_MODEL');
        if ($legacy !== '') return $legacy;
    }
    return commonAiGetTranscriptionDefaultModel($provider);
}
