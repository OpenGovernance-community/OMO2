<?php

require_once __DIR__ . '/patreon.php';
require_once __DIR__ . '/ai_config.php';
require_once __DIR__ . '/ai_usage.php';

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
    if (commonAiGetProvider() === 'infomaniak') {
        try { commonAiGetInfomaniakBaseUrl(); }
        catch (InvalidArgumentException $error) { return false; }
    }
    return in_array(commonAiGetProvider(), ['openai', 'anthropic', 'mistral', 'infomaniak', 'openai_compatible'], true)
        && trim($apiKey ?? commonAiGetApiKey()) !== ''
        && trim($model) !== ''
        && function_exists('curl_init');
}

function commonAiIsTranscriptionConfigured(?string $model = null, ?string $apiKey = null): bool
{
    if (commonAiGetTranscriptionProvider() === 'infomaniak') {
        try { commonAiGetTranscriptionInfomaniakProductId(); }
        catch (InvalidArgumentException $error) { return false; }
    }
    return in_array(commonAiGetTranscriptionProvider(), ['openai', 'groq', 'mistral', 'infomaniak'], true)
        && trim($apiKey ?? commonAiGetTranscriptionApiKey()) !== ''
        && trim($model ?? commonAiGetTranscriptionModel()) !== ''
        && function_exists('curl_init');
}

function commonAiUserCanTranscribe(int $userId, ?string $model = null, ?int $organizationId = null): bool
{
    $organizationId ??= commonAiContextOrganization($userId);
    return commonAiOrganizationAllows($organizationId) && commonAiIsTranscriptionConfigured($model) && patreonUserCanUseAi($userId, $organizationId);
}

function commonAiUserCanUse(int $userId, ?string $model = null, ?string $apiKey = null, ?int $organizationId = null): bool
{
    $organizationId ??= commonAiContextOrganization($userId);
    return commonAiOrganizationAllows($organizationId) && commonAiIsConfigured($model, $apiKey) && patreonUserCanUseAi($userId, $organizationId);
}

/** Organization policy is independent of individual subscriptions and admin overrides. */
function commonAiOrganizationAllows(int $organizationId): bool
{
    return $organizationId <= 0 || \dbObject\Organization::allowsInternalAi($organizationId);
}

function commonAiRequestOrganization(): int
{
    $context = $GLOBALS['commonAiUsageContext'] ?? [];
    return array_key_exists('IDorganization', $context)
        ? (int)($context['IDorganization'] ?? 0) : (int)($_SESSION['currentOrganization'] ?? 0);
}

function commonAiOrganizationBlockedMessage(): string
{
    require_once __DIR__ . '/translation_bundles.php';
    $sourceLang = ['organization_blocked' => ['text' => 'Les fonctions IA sont bloquées par cette organisation.',
        'context' => 'Organization security policy blocks all internal AI tools regardless of personal subscriptions.']];
    static $lang = null;
    $locale = function_exists('omoGetTranslationLocale') ? omoGetTranslationLocale() : (string)($_COOKIE['lang'] ?? 'fr');
    $lang ??= loadTranslationBundle('common_ai_access', $locale, $sourceLang);
    return t('organization_blocked', [], $lang, $sourceLang);
}

function commonAiGetCurrentUserId(): int
{
    return function_exists('commonGetCurrentUserId')
        ? (int)commonGetCurrentUserId()
        : (int)($_SESSION['currentUser'] ?? 0);
}

function commonAiContextOrganization(int $userId): int
{
    $context = $GLOBALS['commonAiUsageContext'] ?? [];
    if (array_key_exists('IDorganization', $context) && (int)($context['IDuser'] ?? 0) === $userId) {
        return (int)($context['IDorganization'] ?? 0);
    }
    return $userId === commonAiGetCurrentUserId() ? (int)($_SESSION['currentOrganization'] ?? 0) : 0;
}
