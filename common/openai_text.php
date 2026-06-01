<?php

if (!function_exists('commonOpenAiGetApiKey')) {
    function commonOpenAiGetApiKey()
    {
        $globalKey = trim((string)($GLOBALS['OpenAI'] ?? ''));
        if ($globalKey !== '') {
            return $globalKey;
        }

        return function_exists('envValue') ? trim((string)envValue('OPENAI_API_KEY', '')) : '';
    }
}

function commonOpenAiGetRewriteModel()
{
    $globalModel = trim((string)($GLOBALS['openAiRewriteModel'] ?? ''));
    if ($globalModel !== '') {
        return $globalModel;
    }

    if (function_exists('envValue')) {
        $configuredModel = trim((string)envValue(
            'OPENAI_REWRITE_MODEL',
            envValue('OPENAI_MODEL', 'gpt-4.1-mini')
        ));
        if ($configuredModel !== '') {
            return $configuredModel;
        }
    }

    return 'gpt-4.1-mini';
}

function commonOpenAiBuildRewriteModelFallbacks($preferredModel)
{
    $models = array();
    $normalizedPreferredModel = trim((string)$preferredModel);

    if ($normalizedPreferredModel !== '') {
        $models[] = $normalizedPreferredModel;
    }

    foreach (array('gpt-4.1-mini', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4o') as $candidateModel) {
        if (!in_array($candidateModel, $models, true)) {
            $models[] = $candidateModel;
        }
    }

    return $models;
}

function commonOpenAiRequestChatCompletion($apiKey, array $payload)
{
    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    if ($curl === false) {
        return array(
            'status' => false,
            'message' => 'Impossible de preparer la requete OpenAI.',
        );
    }

    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encodedPayload) || $encodedPayload === '') {
        return array(
            'status' => false,
            'message' => 'Impossible d encoder la requete OpenAI.',
        );
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $encodedPayload,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ),
    ));

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        return array(
            'status' => false,
            'message' => $curlError !== '' ? $curlError : 'La requete OpenAI a echoue.',
            'http_code' => $httpCode,
        );
    }

    $decodedResponse = json_decode((string)$response, true);
    if (is_array($decodedResponse) && isset($decodedResponse['error']['message'])) {
        return array(
            'status' => false,
            'message' => trim((string)$decodedResponse['error']['message']),
            'http_code' => $httpCode,
            'raw' => $decodedResponse,
        );
    }

    $content = is_array($decodedResponse)
        ? trim((string)($decodedResponse['choices'][0]['message']['content'] ?? ''))
        : '';

    if ($content === '') {
        return array(
            'status' => false,
            'message' => 'OpenAI returned an empty response.',
            'http_code' => $httpCode,
        );
    }

    return array(
        'status' => true,
        'content' => $content,
        'http_code' => $httpCode,
    );
}

function commonOpenAiDecodeRewriteResponse($content)
{
    $payload = trim((string)$content);
    if ($payload === '') {
        return '';
    }

    if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $payload, $matches)) {
        $payload = trim((string)$matches[1]);
    }

    $decoded = json_decode($payload, true);
    if (is_array($decoded)) {
        return trim((string)($decoded['rewritten_text'] ?? ''));
    }

    return trim((string)$content);
}

function commonOpenAiRewriteSelectedDocumentText($selectedText, $fullText, array $options = array())
{
    $apiKey = commonOpenAiGetApiKey();
    if ($apiKey === '') {
        return array(
            'status' => false,
            'message' => 'OPENAI_API_KEY is not configured.',
        );
    }

    $selectedText = trim((string)$selectedText);
    $fullText = trim((string)$fullText);

    if ($selectedText === '' || $fullText === '') {
        return array(
            'status' => false,
            'message' => 'Le texte a reecrire est vide.',
        );
    }

    $title = trim((string)($options['title'] ?? ''));
    $preferredModel = trim((string)($options['model'] ?? commonOpenAiGetRewriteModel()));
    $candidateModels = commonOpenAiBuildRewriteModelFallbacks($preferredModel);
    $rewritingWholeDocument = !empty($options['rewrite_full_document']);

    $systemPrompt = 'You rewrite French document text for readability and coherence. '
        . 'Return only a JSON object with one key named rewritten_text. '
        . 'Preserve the original meaning, facts, tone, and language. '
        . 'Do not add commentary, explanations, markdown, or titles. '
        . 'Output plain text with paragraph breaks only.';

    $instruction = $rewritingWholeDocument
        ? 'The selected text is the whole document. Improve readability, clarity, and flow while keeping the meaning and tone.'
        : 'Rewrite only the selected passage. Keep it aligned with the style, terminology, tone, and rhythm of the rest of the document.';

    $userPayload = array(
        'task' => 'rewrite_document_selection',
        'title' => $title,
        'rewrite_full_document' => $rewritingWholeDocument,
        'instruction' => $instruction,
        'selected_text' => $selectedText,
        'full_document_text' => $fullText,
    );

    $lastFailure = null;

    foreach ($candidateModels as $candidateModel) {
        $result = commonOpenAiRequestChatCompletion($apiKey, array(
            'model' => $candidateModel,
            'response_format' => array('type' => 'json_object'),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $systemPrompt,
                ),
                array(
                    'role' => 'user',
                    'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ),
            ),
            'temperature' => 0.3,
            'max_tokens' => 1200,
        ));

        if (empty($result['status'])) {
            $lastFailure = $result;
            continue;
        }

        $rewrittenText = commonOpenAiDecodeRewriteResponse($result['content'] ?? '');
        if ($rewrittenText === '') {
            $lastFailure = array(
                'status' => false,
                'message' => 'OpenAI returned an empty rewritten text.',
            );
            continue;
        }

        return array(
            'status' => true,
            'text' => $rewrittenText,
            'model' => $candidateModel,
        );
    }

    return array(
        'status' => false,
        'message' => trim((string)($lastFailure['message'] ?? 'Impossible de reecrire ce texte.')),
    );
}

function commonOpenAiDecodeSummarizeResponse($content)
{
    $payload = trim((string)$content);
    if ($payload === '') {
        return '';
    }

    if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $payload, $matches)) {
        $payload = trim((string)$matches[1]);
    }

    $decoded = json_decode($payload, true);
    if (is_array($decoded)) {
        return trim((string)($decoded['summarized_text'] ?? ''));
    }

    return trim((string)$content);
}

function commonOpenAiSummarizeSelectedDocumentText($selectedText, $fullText, array $options = array())
{
    $apiKey = commonOpenAiGetApiKey();
    if ($apiKey === '') {
        return array(
            'status' => false,
            'message' => 'OPENAI_API_KEY is not configured.',
        );
    }

    $selectedText = trim((string)$selectedText);
    $fullText = trim((string)$fullText);

    if ($selectedText === '' || $fullText === '') {
        return array(
            'status' => false,
            'message' => 'Le texte a resumer est vide.',
        );
    }

    $title = trim((string)($options['title'] ?? ''));
    $preferredModel = trim((string)($options['model'] ?? commonOpenAiGetRewriteModel()));
    $candidateModels = commonOpenAiBuildRewriteModelFallbacks($preferredModel);

    $systemPrompt = 'You shorten selected French document text while preserving meaning, accuracy, and coherence. '
        . 'Return only a JSON object with one key named summarized_text. '
        . 'The output must stay in French, keep the original tone, and remain easy to read. '
        . 'Target roughly half the length of the selected text. '
        . 'Do not add commentary, markdown, bullet points, or titles unless they already exist inside the selected text. '
        . 'Output plain text with paragraph breaks only.';

    $userPayload = array(
        'task' => 'summarize_document_selection',
        'title' => $title,
        'instruction' => 'Shorten only the selected passage to about half its length while keeping it aligned with the style and terminology of the rest of the document.',
        'selected_text' => $selectedText,
        'full_document_text' => $fullText,
    );

    $lastFailure = null;

    foreach ($candidateModels as $candidateModel) {
        $result = commonOpenAiRequestChatCompletion($apiKey, array(
            'model' => $candidateModel,
            'response_format' => array('type' => 'json_object'),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $systemPrompt,
                ),
                array(
                    'role' => 'user',
                    'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ),
            ),
            'temperature' => 0.2,
            'max_tokens' => 900,
        ));

        if (empty($result['status'])) {
            $lastFailure = $result;
            continue;
        }

        $summarizedText = commonOpenAiDecodeSummarizeResponse($result['content'] ?? '');
        if ($summarizedText === '') {
            $lastFailure = array(
                'status' => false,
                'message' => 'OpenAI returned an empty summarized text.',
            );
            continue;
        }

        return array(
            'status' => true,
            'text' => $summarizedText,
            'model' => $candidateModel,
        );
    }

    return array(
        'status' => false,
        'message' => trim((string)($lastFailure['message'] ?? 'Impossible de resumer ce texte.')),
    );
}
