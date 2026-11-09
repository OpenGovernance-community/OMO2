<?php
require_once __DIR__ . '/ai_translations.php';


require_once __DIR__ . '/ai_access.php';

function commonOpenAiGetTranscriptionModel()
{
    return commonAiGetTranscriptionModel();
}

function commonOpenAiGetDefaultTranscriptionPrompt()
{
    if (function_exists('envValue')) {
        $configuredPrompt = trim((string)envValue('OPENAI_TRANSCRIPTION_PROMPT', ''));
        if ($configuredPrompt !== '') {
            return $configuredPrompt;
        }
    }

    return commonAiUiT('copy.transcription_en_francais_d_une_dictee_pour_un_document_rends_la_ponct');
}

function commonOpenAiDetectUploadedAudioMimeType(array $uploadedFile)
{
    $tmpName = trim((string)($uploadedFile['tmp_name'] ?? ''));
    if ($tmpName !== '' && is_file($tmpName) && class_exists('finfo')) {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = trim((string)$finfo->file($tmpName));
        if ($mimeType !== '') {
            return strtolower($mimeType);
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
    $model = trim((string)$preferredModel);
    return $model === '' ? [] : [$model];
}

function commonOpenAiRequestAudioTranscription($apiKey, $tmpName, $mimeType, $filename, array $payload)
{
    if (!commonAiOrganizationAllows(commonAiRequestOrganization())) {
        return ['status' => false, 'http_code' => 403, 'code' => 'organization_ai_blocked', 'message' => commonAiOrganizationBlockedMessage()];
    }
    if (!commonAiIsTranscriptionConfigured((string)($payload['model'] ?? ''), (string)$apiKey)) {
        return ['status' => false, 'message' => 'Configuration IA indisponible.'];
    }
    $provider = commonAiGetTranscriptionProvider();
    $deadline = microtime(true) + 110;
    $url = match ($provider) {
        'groq' => 'https://api.groq.com/openai/v1/audio/transcriptions',
        'mistral' => 'https://api.mistral.ai/v1/audio/transcriptions',
        'infomaniak' => 'https://api.infomaniak.com/1/ai/' . commonAiGetTranscriptionInfomaniakProductId() . '/openai/audio/transcriptions',
        default => 'https://api.openai.com/v1/audio/transcriptions',
    };
    $curl = curl_init($url);
    if ($curl === false) {
        return array(
            'status' => false,
            'message' => commonAiUiT('copy.impossible_de_preparer_la_requete_de_transcription'),
        );
    }

    $multipartPayload = $payload;
    if ($provider === 'mistral') {
        // Voxtral returns JSON natively and does not accept OpenAI prompt/response_format fields.
        unset($multipartPayload['prompt'], $multipartPayload['response_format']);
    }
    $multipartPayload['file'] = new \CURLFile($tmpName, $mimeType, $filename);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $multipartPayload,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $provider === 'infomaniak' ? 110 : 120,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apiKey,
        ),
    ));

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $usageResponse = is_string($response) ? json_decode($response, true) : null;
    if ($provider === 'infomaniak' && $httpCode >= 200 && $httpCode < 300 && is_array($usageResponse) && isset($usageResponse['batch_id'])) {
        try {
            $usageResponse = commonInfomaniakWaitForTranscription($usageResponse['batch_id'], $apiKey, commonAiGetTranscriptionInfomaniakProductId(), $deadline);
        } catch (RuntimeException $error) {
            $usageResponse = ['error' => ['message' => $error->getMessage()]];
        }
        $response = json_encode($usageResponse, JSON_THROW_ON_ERROR);
    }
    commonAiRecordUsage('model', $provider, (string)($payload['model'] ?? ''), (string)($payload['prompt'] ?? ''),
        is_array($usageResponse) ? (string)($usageResponse['text'] ?? '') : '', is_array($usageResponse) ? $usageResponse : [], $httpCode, (int)filesize($tmpName));
    if ($response === false) {
        return array(
            'status' => false,
            'message' => $curlError !== '' ? $curlError : commonAiUiT('copy.la_requete_de_transcription_a_echoue'),
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

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['status' => false, 'http_code' => $httpCode, 'message' => commonAiUiT('copy.la_requete_de_transcription_a_echoue')];
    }

    $text = is_array($decodedResponse)
        ? trim((string)($decodedResponse['text'] ?? ''))
        : trim((string)$response);

    if ($text === '') {
        return array(
            'status' => false,
            'message' => commonAiUiT('copy.le_service_a_renvoye_une_transcription_vide'),
            'http_code' => $httpCode,
        );
    }

    return array(
        'status' => true,
        'text' => $text,
        'http_code' => $httpCode,
    );
}

/** Poll and download only from the fixed product URL; never follow a URL from an API reply. */
function commonInfomaniakTranscriptionGet(string $path, string $apiKey, int $timeout): array|string
{
    if (!commonAiOrganizationAllows(commonAiRequestOrganization())) throw new RuntimeException(commonAiOrganizationBlockedMessage());
    $curl = curl_init('https://api.infomaniak.com' . $path);
    if ($curl === false) throw new RuntimeException(commonAiUiT('copy.impossible_de_preparer_le_suivi_de_transcription_infomaniak'));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => min(5, $timeout), CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey]]);
    $body = curl_exec($curl);
    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($body === false || $code < 200 || $code >= 300) {
        throw new RuntimeException(commonAiUiT('copy.impossible_de_recuperer_la_transcription_infomaniak_http_code', ['code' => $code]));
    }
    $decoded = json_decode($body, true);
    if (is_array($decoded)) return $decoded;
    if (str_ends_with($path, '/download')) return $body;
    throw new RuntimeException(commonAiUiT('copy.reponse_de_suivi_infomaniak_invalide'));
}

function commonInfomaniakWaitForTranscription(string $batchId, string $apiKey, string $productId, float $deadline, ?callable $get = null): array
{
    if (!preg_match('/\A[a-zA-Z0-9-]{1,128}\z/D', $batchId)) throw new RuntimeException('Identifiant de transcription Infomaniak invalide.');
    commonAiGetInfomaniakBaseUrl($productId);
    $get ??= 'commonInfomaniakTranscriptionGet';
    $path = '/1/ai/' . $productId . '/results/' . rawurlencode($batchId);
    while (($remaining = (int)ceil($deadline - microtime(true))) > 0) {
        $result = $get($path, $apiKey, min(10, $remaining));
        if (!is_array($result)) throw new RuntimeException(commonAiUiT('copy.reponse_de_suivi_infomaniak_invalide'));
        $status = $result['status'] ?? '';
        if ($status === 'success') {
            $data = $result['data'] ?? null;
            if ($data === null || $data === '') {
                $remaining = (int)ceil($deadline - microtime(true));
                if ($remaining < 1) break;
                $data = $get($path . '/download', $apiKey, min(10, $remaining));
            }
            if (is_string($data)) $data = json_decode($data, true) ?? $data;
            $text = is_array($data) ? trim((string)($data['text'] ?? '')) : (is_string($data) ? trim($data) : '');
            if ($text === '') throw new RuntimeException(commonAiUiT('copy.infomaniak_a_renvoye_une_transcription_vide'));
            return ['text' => $text];
        }
        if (in_array($status, ['failed', 'cancelled'], true)) throw new RuntimeException(commonAiUiT('copy.la_transcription_infomaniak_a_echoue_ou_a_ete_annulee'));
        if (!in_array($status, ['pending', 'processing'], true)) throw new RuntimeException(commonAiUiT('copy.etat_de_transcription_infomaniak_invalide'));
        usleep(500000);
    }
    throw new RuntimeException(commonAiUiT('copy.la_transcription_infomaniak_n_est_pas_terminee_dans_le_delai_imparti_e'));
}

function commonOpenAiTranscribeUploadedAudio(array $uploadedFile, array $options = array())
{
    if (!commonAiUserCanTranscribe((int)($options['user_id'] ?? commonAiGetCurrentUserId()), (string)($options['model'] ?? commonOpenAiGetTranscriptionModel()))) {
        return ['status' => false, 'message' => 'Fonctions IA indisponibles pour ce compte.'];
    }
    $apiKey = commonAiGetTranscriptionApiKey();

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
            'message' => commonAiUiT('copy.le_fichier_audio_recu_est_invalide'),
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
            'message' => commonAiUiT('copy.le_fichier_audio_depasse_la_limite_de_25_mo'),
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
