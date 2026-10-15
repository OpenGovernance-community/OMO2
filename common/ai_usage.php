<?php

/** Context comes from authorized server routes, never from the model or request payload. */
function commonAiSetUsageContext(int $userId, int $organizationId, string $feature, string $origin = 'site'): void
{
    $GLOBALS['commonAiUsageContext'] = ['IDuser' => $userId > 0 ? $userId : null,
        'IDorganization' => $organizationId > 0 ? $organizationId : null,
        'feature' => $feature, 'origin' => $origin];
}

function commonAiWithUsageContext(array $context, callable $callback)
{
    $previous = $GLOBALS['commonAiUsageContext'] ?? null;
    $GLOBALS['commonAiUsageContext'] = $context;
    try { return $callback(); }
    finally { $GLOBALS['commonAiUsageContext'] = $previous; }
}

function commonAiUsageChars($value): int
{
    $text = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    return mb_strlen((string)$text, 'UTF-8');
}

function commonAiRecordUsage(string $kind, string $provider, string $model, $input, $output, array $response, int $httpStatus, int $audioBytes = 0): void
{
    $context = $GLOBALS['commonAiUsageContext'] ?? [
        'IDuser' => (int)($_SESSION['currentUser'] ?? 0) ?: null, 'IDorganization' => null,
        'feature' => 'text', 'origin' => PHP_SAPI === 'cli' ? 'background' : 'site'];
    $usage = $kind === 'model' ? ($response['usage'] ?? []) : [];
    $count = static fn ($value) => is_numeric($value) && $value >= 0 ? (int)$value : null;
    $in = $count($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? null);
    $out = $count($usage['completion_tokens'] ?? $usage['output_tokens'] ?? null);
    // Anthropic reports cached input separately from uncached input.
    if ($provider === 'anthropic' && $in !== null) $in += (int)($usage['cache_creation_input_tokens'] ?? 0) + (int)($usage['cache_read_input_tokens'] ?? 0);
    $total = $count($usage['total_tokens'] ?? ($in !== null && $out !== null ? $in + $out : null));
    try {
        \dbObject\AiUsage::record($context + ['kind' => $kind, 'provider' => $provider, 'model' => mb_substr($model, 0, 120),
            'input_tokens' => $in, 'output_tokens' => $out, 'total_tokens' => $total,
            'input_chars' => commonAiUsageChars($input), 'output_chars' => commonAiUsageChars($output),
            'audio_bytes' => $audioBytes, 'success' => $httpStatus >= 200 && $httpStatus < 300 && empty($response['error']), 'http_status' => $httpStatus]);
    } catch (Throwable $error) {
        // Do not lose a paid response if metrics storage is temporarily unavailable.
        error_log('OMO AI usage could not be recorded: ' . get_class($error));
    }
}
