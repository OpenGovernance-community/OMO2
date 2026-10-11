<?php

require_once dirname(__DIR__) . '/common/ai_access.php';
require_once dirname(__DIR__) . '/common/ai_client.php';

function say($demand, $systemInstruction = null, ?int $userId = null)
{
    if (!commonAiUserCanUse($userId ?? commonAiGetCurrentUserId())) return '';
    $instruction = is_string($systemInstruction) && trim($systemInstruction) !== ''
        ? $systemInstruction
        : 'Tu es un assistant specialise dans les syntheses efficaces et pertinentes. Tu ne rajoutes pas de titre, de fioritures ou de contexte aux resumes et listes produits.';
    $result = commonAiRequestText(commonAiGetApiKey(), [
        'model' => commonAiGetModel(),
        'messages' => [
            ['role' => 'system', 'content' => $instruction],
            ['role' => 'user', 'content' => (string)$demand],
        ],
        'temperature' => 0.7,
    ], 240);
    return !empty($result['status']) ? (string)$result['content'] : '';
}
