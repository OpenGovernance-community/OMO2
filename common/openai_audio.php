<?php

function commonOpenAiGetApiKey()
{
    $globalKey = trim((string)($GLOBALS['OpenAI'] ?? ''));
    if ($globalKey !== '') {
        return $globalKey;
    }

    return function_exists('envValue') ? trim((string)envValue('OPENAI_API_KEY', '')) : '';
}

function commonOpenAiGetTranscriptionModel()
{
    $globalModel = trim((string)($GLOBALS['openAiTranscriptionModel'] ?? ''));
    if ($globalModel !== '') {
        return $globalModel;
    }

    if (function_exists('envValue')) {
        $configuredModel = trim((string)envValue(
            'OPENAI_TRANSCRIPTION_MODEL',
            envValue('OPENAI_AUDIO_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe')
        ));
        if ($configuredModel !== '') {
            return $configuredModel;
        }
    }

    return 'gpt-4o-mini-transcribe';
}

function commonOpenAiGetDefaultTranscriptionPrompt()
{
    if (function_exists('envValue')) {
        $configuredPrompt = trim((string)envValue('OPENAI_TRANSCRIPTION_PROMPT', ''));
        if ($configuredPrompt !== '') {
            return $configuredPrompt;
        }
    }

    return 'Transcription en francais d une dictee pour un document. Rends la ponctuation naturelle et garde les retours a la ligne evidents.';
}

function commonOpenAiDetectUploadedAudioMimeType(array $uploadedFile)
{
    $tmpName = trim((string)($uploadedFile['tmp_name'] ?? ''));
    if ($tmpName !== '' && is_file($tmpName) && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = trim((string)finfo_file($finfo, $tmpName));
            finfo_close($finfo);
            if ($mimeType !== '') {
                return strtolower($mimeType);
            }
        }
    }

    $fallbackType = strtolower(trim((string)($uploadedFile['type'] ?? '')));
    if ($fallbackType !== '') {
        $parts = explode(';', $fallbackType, 2);
        return trim((string)($parts[0] ?? ''));
    }

    return 'application/octet-stream';
}

function commonOpenAiResolveAudioUploadExtension(array $uploadedFile, $mimeType = '')
{
    $allowedExtensions = array(
        'mp3' => true,
        'mp4' => true,
        'mpeg' => true,
        'mpga' => true,
        'm4a' => true,
        'wav' => true,
        'webm' => true,
        'ogg' => true,
    );

    $originalName = trim((string)($uploadedFile['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension !== '' && !empty($allowedExtensions[$extension])) {
        return $extension;
    }

    $normalizedMimeType = strtolower(trim((string)$mimeType));
    if (strpos($normalizedMimeType, 'mp4') !== false || strpos($normalizedMimeType, 'm4a') !== false) {
        return 'm4a';
    }
    if (strpos($normalizedMimeType, 'mpeg') !== false || strpos($normalizedMimeType, 'mp3') !== false) {
        return 'mp3';
    }
    if (strpos($normalizedMimeType, 'wav') !== false) {
        return 'wav';
    }
    if (strpos($normalizedMimeType, 'ogg') !== false) {
        return 'ogg';
    }

    return 'webm';
}

function commonOpenAiNormalizeAudioUploadMimeType($mimeType, $extension = '')
{
    $normalizedMimeType = strtolower(trim((string)$mimeType));
    $normalizedExtension = strtolower(trim((string)$extension));

    if ($normalizedMimeType === 'video/webm' || $normalizedMimeType === 'application/webm') {
        return 'audio/webm';
    }
    if ($normalizedMimeType === 'application/ogg' || $normalizedMimeType === 'video/ogg') {
        return 'audio/ogg';
    }
    if ($normalizedMimeType === 'audio/x-wav') {
        return 'audio/wav';
    }
    if ($normalizedMimeType === 'application/octet-stream') {
        if ($normalizedExtension === 'webm') {
            return 'audio/webm';
        }
        if ($normalizedExtension === 'ogg') {
            return 'audio/ogg';
        }
        if ($normalizedExtension === 'wav') {
            return 'audio/wav';
        }
        if ($normalizedExtension === 'm4a' || $normalizedExtension === 'mp4') {
            return 'audio/mp4';
        }
        if ($normalizedExtension === 'mp3' || $normalizedExtension === 'mpeg' || $normalizedExtension === 'mpga') {
            return 'audio/mpeg';
        }
    }

    return $normalizedMimeType !== '' ? $normalizedMimeType : 'audio/webm';
}

function commonOpenAiBuildTranscriptionModelFallbacks($preferredModel)
{
    $normalizedPreferredModel = trim((string)$preferredModel);
    $models = array();

    if ($normalizedPreferredModel !== '') {
        $models[] = $normalizedPreferredModel;
    }

    foreach (array('gpt-4o-mini-transcribe', 'gpt-4o-transcribe', 'whisper-1') as $candidateModel) {
        if (!in_array($candidateModel, $models, true)) {
            $models[] = $candidateModel;
        }
    }

    return $models;
}

function commonOpenAiRequestAudioTranscription($apiKey, $tmpName, $mimeType, $filename, array $payload)
{
    $curl = curl_init('https://api.openai.com/v1/audio/transcriptions');
    if ($curl === false) {
        return array(
            'status' => false,
            'message' => 'Impossible de preparer la requete OpenAI.',
        );
    }

    $multipartPayload = $payload;
    $multipartPayload['file'] = new \CURLFile($tmpName, $mimeType, $filename);

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $multipartPayload,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apiKey,
        ),
    ));

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        return array(
            'status' => false,
            'message' => $curlError !== '' ? $curlError : 'La requete de transcription a echoue.',
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

    $text = is_array($decodedResponse)
        ? trim((string)($decodedResponse['text'] ?? ''))
        : trim((string)$response);

    if ($text === '') {
        return array(
            'status' => false,
            'message' => 'OpenAI returned an empty transcription.',
            'http_code' => $httpCode,
        );
    }

    return array(
        'status' => true,
        'text' => $text,
        'http_code' => $httpCode,
    );
}

function commonOpenAiTranscribeUploadedAudio(array $uploadedFile, array $options = array())
{
    $apiKey = commonOpenAiGetApiKey();
    if ($apiKey === '') {
        return array(
            'status' => false,
            'message' => 'OPENAI_API_KEY is not configured.',
        );
    }

    $uploadError = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        return array(
            'status' => false,
            'message' => 'Impossible de recevoir le fichier audio.',
        );
    }

    $tmpName = trim((string)($uploadedFile['tmp_name'] ?? ''));
    if ($tmpName === '' || !is_file($tmpName)) {
        return array(
            'status' => false,
            'message' => 'Le fichier audio recu est invalide.',
        );
    }

    $size = (int)($uploadedFile['size'] ?? 0);
    if ($size <= 0) {
        return array(
            'status' => false,
            'message' => 'Le fichier audio est vide.',
        );
    }

    if ($size > 25 * 1024 * 1024) {
        return array(
            'status' => false,
            'message' => 'Le fichier audio depasse la limite de 25 Mo.',
        );
    }

    $detectedMimeType = commonOpenAiDetectUploadedAudioMimeType($uploadedFile);
    $extension = commonOpenAiResolveAudioUploadExtension($uploadedFile, $detectedMimeType);
    $mimeType = commonOpenAiNormalizeAudioUploadMimeType(
        $detectedMimeType,
        $extension
    );
    $transcriptionModel = trim((string)($options['model'] ?? commonOpenAiGetTranscriptionModel()));
    $transcriptionPrompt = trim((string)($options['prompt'] ?? commonOpenAiGetDefaultTranscriptionPrompt()));
    $candidateModels = commonOpenAiBuildTranscriptionModelFallbacks($transcriptionModel);
    $filename = 'document-dictation.' . $extension;
    $lastFailure = null;

    try {
        foreach ($candidateModels as $candidateModel) {
            $requestPayload = array(
                'model' => $candidateModel,
                'response_format' => 'json',
            );

            if ($transcriptionPrompt !== '') {
                $requestPayload['prompt'] = $transcriptionPrompt;
            }

            $result = commonOpenAiRequestAudioTranscription(
                $apiKey,
                $tmpName,
                $mimeType,
                $filename,
                $requestPayload
            );

            if (!empty($result['status'])) {
                return array(
                    'status' => true,
                    'text' => trim((string)($result['text'] ?? '')),
                    'model' => $candidateModel,
                );
            }

            $lastFailure = $result;
        }

        return array(
            'status' => false,
            'message' => trim((string)($lastFailure['message'] ?? 'Impossible de transcrire cet enregistrement.')),
        );
    } catch (\Throwable $exception) {
        return array(
            'status' => false,
            'message' => trim((string)$exception->getMessage()) !== ''
                ? trim((string)$exception->getMessage())
                : 'Impossible de transcrire cet enregistrement.',
        );
    }
}
