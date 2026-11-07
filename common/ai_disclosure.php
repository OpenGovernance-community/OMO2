<?php

require_once __DIR__ . '/ai_access.php';

/** Explicit public allowlist: never return tokens, product IDs or full endpoint URLs. */
function commonAiGetPublicServices(): array
{
    $textProvider = commonAiGetProvider();
    $audioProvider = commonAiGetTranscriptionProvider();
    $describe = static function (string $provider, string $model, bool $configured): array {
        $name = match ($provider) {
            'openai' => 'OpenAI', 'anthropic' => 'Anthropic (Claude)',
            'mistral' => 'Mistral AI', 'infomaniak' => 'Infomaniak', 'groq' => 'Groq',
            default => '',
        };
        if ($provider === 'openai_compatible') {
            try {
                $host = strtolower((string)parse_url(commonAiGetCompatibleBaseUrl(), PHP_URL_HOST));
                $name = match ($host) {
                    'openrouter.ai' => 'OpenRouter', 'api.openai.com' => 'OpenAI',
                    'api.mistral.ai' => 'Mistral AI', 'api.groq.com' => 'Groq',
                    'api.infomaniak.com' => 'Infomaniak', default => $host,
                };
            } catch (InvalidArgumentException $error) {
                $name = '';
            }
        }
        return ['provider' => $name, 'model' => $provider === 'disabled' ? '' : $model,
            'status' => $provider === 'disabled' ? 'disabled' : ($configured ? 'configured' : 'unconfigured')];
    };
    return [
        'functions' => $describe($textProvider, commonAiGetModel(), commonAiIsConfigured()),
        'translation' => $describe($textProvider, commonAiGetModel(true), commonAiIsConfigured(commonAiGetModel(true))),
        'transcription' => $describe($audioProvider, commonAiGetTranscriptionModel(), commonAiIsTranscriptionConfigured()),
    ];
}
