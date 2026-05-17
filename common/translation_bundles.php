<?php

// This helper expects the shared dbObject autoload to already be initialized.

function translationBundleNormalizeLocale($locale)
{
    $locale = strtolower(trim((string)$locale));
    return str_replace('_', '-', $locale);
}

function translationBundleGetBuiltInLanguageCatalog()
{
    return [
        [
            'locale' => 'fr',
            'name' => 'Francais',
            'native_name' => 'Francais',
            'sort_order' => 10,
            'active' => 1,
            'is_source' => 1,
        ],
        [
            'locale' => 'en',
            'name' => 'Anglais',
            'native_name' => 'English',
            'sort_order' => 20,
            'active' => 1,
            'is_source' => 0,
        ],
        [
            'locale' => 'de',
            'name' => 'Allemand',
            'native_name' => 'Deutsch',
            'sort_order' => 30,
            'active' => 1,
            'is_source' => 0,
        ],
        [
            'locale' => 'es',
            'name' => 'Espagnol',
            'native_name' => 'Espanol',
            'sort_order' => 40,
            'active' => 1,
            'is_source' => 0,
        ],
        [
            'locale' => 'it',
            'name' => 'Italien',
            'native_name' => 'Italiano',
            'sort_order' => 50,
            'active' => 1,
            'is_source' => 0,
        ],
        [
            'locale' => 'pt',
            'name' => 'Portugais',
            'native_name' => 'Portugues',
            'sort_order' => 60,
            'active' => 1,
            'is_source' => 0,
        ],
        [
            'locale' => 'nl',
            'name' => 'Neerlandais',
            'native_name' => 'Nederlands',
            'sort_order' => 70,
            'active' => 1,
            'is_source' => 0,
        ],
        [
            'locale' => 'pl',
            'name' => 'Polonais',
            'native_name' => 'Polski',
            'sort_order' => 80,
            'active' => 1,
            'is_source' => 0,
        ],
    ];
}

function translationBundleNormalizeLanguageCatalogRows(array $rows)
{
    $normalizedRows = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $locale = translationBundleNormalizeLocale($row['locale'] ?? '');
        if ($locale === '' || !translationBundleIsValidLocale($locale)) {
            continue;
        }

        $normalizedRows[$locale] = [
            'locale' => $locale,
            'name' => trim((string)($row['name'] ?? '')),
            'native_name' => trim((string)($row['native_name'] ?? '')),
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'active' => !empty($row['active']) ? 1 : 0,
            'is_source' => !empty($row['is_source']) ? 1 : 0,
        ];
    }

    $builtInRows = translationBundleGetBuiltInLanguageCatalog();
    foreach ($builtInRows as $row) {
        $locale = translationBundleNormalizeLocale($row['locale'] ?? '');
        if ($locale === '' || isset($normalizedRows[$locale])) {
            continue;
        }

        if ($locale === 'fr') {
            $normalizedRows[$locale] = $row;
        }
    }

    uasort($normalizedRows, function (array $left, array $right) {
        if ((int)$left['is_source'] !== (int)$right['is_source']) {
            return ((int)$left['is_source'] > (int)$right['is_source']) ? -1 : 1;
        }

        if ((int)$left['sort_order'] !== (int)$right['sort_order']) {
            return ((int)$left['sort_order'] < (int)$right['sort_order']) ? -1 : 1;
        }

        return strcmp((string)$left['locale'], (string)$right['locale']);
    });

    return array_values($normalizedRows);
}

function translationBundleGetAvailableLanguages($refresh = false)
{
    static $cachedRows = null;

    if (!$refresh && is_array($cachedRows)) {
        return $cachedRows;
    }

    $rows = [];

    if (class_exists('\dbObject\TranslationLanguage') && \dbObject\TranslationLanguage::isStorageAvailable()) {
        $rows = \dbObject\TranslationLanguage::loadActiveCatalogRows();
    }

    if (!is_array($rows) || count($rows) === 0) {
        $rows = translationBundleGetBuiltInLanguageCatalog();
    }

    $cachedRows = translationBundleNormalizeLanguageCatalogRows($rows);
    return $cachedRows;
}

function translationBundleGetLanguageOptions($refresh = false)
{
    $options = [];

    foreach (translationBundleGetAvailableLanguages($refresh) as $row) {
        $label = trim((string)($row['native_name'] ?? ''));
        if ($label === '') {
            $label = trim((string)($row['name'] ?? ''));
        }
        if ($label === '') {
            $label = strtoupper((string)$row['locale']);
        }

        $options[] = [
            'locale' => (string)$row['locale'],
            'label' => $label,
        ];
    }

    return $options;
}

function translationBundleGetSupportedLocales()
{
    return array_column(translationBundleGetAvailableLanguages(), 'locale');
}

function translationBundleResolveSupportedLocale($locale, array $supportedLocales = [])
{
    $supportedLocales = $supportedLocales ?: translationBundleGetSupportedLocales();
    $normalizedLocale = translationBundleNormalizeLocale($locale);
    $candidates = translationBundleBuildLocaleCandidates($normalizedLocale);

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $supportedLocales, true)) {
            return $candidate;
        }
    }

    return '';
}

function translationBundleResolveBrowserLocale(array $supportedLocales = [], $fallback = 'fr')
{
    $supportedLocales = $supportedLocales ?: translationBundleGetSupportedLocales();
    $acceptLanguage = (string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $fallbackLocale = translationBundleResolveSupportedLocale($fallback, $supportedLocales);

    foreach (explode(',', $acceptLanguage) as $part) {
        $rawLocale = trim((string)preg_replace('/;.*$/', '', $part));
        $resolvedLocale = translationBundleResolveSupportedLocale($rawLocale, $supportedLocales);
        if ($resolvedLocale !== '') {
            return $resolvedLocale;
        }
    }

    return $fallbackLocale !== '' ? $fallbackLocale : (string)reset($supportedLocales);
}

function translationBundleResolveRequestLocale($cookieName = 'lang', array $supportedLocales = [], $fallback = 'fr')
{
    $supportedLocales = $supportedLocales ?: translationBundleGetSupportedLocales();
    $cookieLocale = translationBundleResolveSupportedLocale($_COOKIE[$cookieName] ?? '', $supportedLocales);

    if ($cookieLocale !== '') {
        return $cookieLocale;
    }

    return translationBundleResolveBrowserLocale($supportedLocales, $fallback);
}

function translationBundleGetRequestLocalePreference($cookieName = 'lang', array $supportedLocales = [])
{
    $supportedLocales = $supportedLocales ?: translationBundleGetSupportedLocales();
    $cookieLocale = translationBundleResolveSupportedLocale($_COOKIE[$cookieName] ?? '', $supportedLocales);

    return $cookieLocale !== '' ? $cookieLocale : 'system';
}

function translationBundleIsValidLocale($locale)
{
    return preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/', translationBundleNormalizeLocale($locale)) === 1;
}

function translationBundleCanonicalizeValue($value)
{
    if (!is_array($value)) {
        return $value;
    }

    $isList = array_keys($value) === range(0, count($value) - 1);
    if ($isList) {
        foreach ($value as $index => $childValue) {
            $value[$index] = translationBundleCanonicalizeValue($childValue);
        }

        return $value;
    }

    ksort($value);
    foreach ($value as $key => $childValue) {
        $value[$key] = translationBundleCanonicalizeValue($childValue);
    }

    return $value;
}

function translationBundleEncodePayload(array $sourceLang)
{
    $payload = json_encode(
        translationBundleCanonicalizeValue($sourceLang),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return is_string($payload) ? $payload : '';
}

function translationBundleSourceHash(array $sourceLang)
{
    $payload = translationBundleEncodePayload($sourceLang);
    if ($payload === '') {
        $payload = serialize(translationBundleCanonicalizeValue($sourceLang));
    }

    return hash('sha256', $payload);
}

function translationBundleSanitizeEntry($entry)
{
    if (!is_array($entry)) {
        return null;
    }

    $sanitized = [];

    if (array_key_exists('text', $entry) && (is_scalar($entry['text']) || $entry['text'] === null)) {
        $sanitized['text'] = (string)$entry['text'];
    }

    if (array_key_exists('one', $entry) && (is_scalar($entry['one']) || $entry['one'] === null)) {
        $sanitized['one'] = (string)$entry['one'];
    }

    if (array_key_exists('other', $entry) && (is_scalar($entry['other']) || $entry['other'] === null)) {
        $sanitized['other'] = (string)$entry['other'];
    }

    if (array_key_exists('context', $entry) && (is_scalar($entry['context']) || $entry['context'] === null)) {
        $sanitized['context'] = (string)$entry['context'];
    }

    if (!array_key_exists('text', $sanitized) && !array_key_exists('one', $sanitized) && !array_key_exists('other', $sanitized)) {
        return null;
    }

    return $sanitized;
}

function translationBundleMergeWithSource(array $sourceLang, array $translatedLang)
{
    $merged = [];

    foreach ($sourceLang as $key => $sourceEntry) {
        $sanitizedSourceEntry = translationBundleSanitizeEntry($sourceEntry);
        if ($sanitizedSourceEntry === null) {
            continue;
        }

        $sanitizedTranslatedEntry = translationBundleSanitizeEntry($translatedLang[$key] ?? null);
        if ($sanitizedTranslatedEntry === null) {
            $merged[$key] = $sanitizedSourceEntry;
            continue;
        }

        $merged[$key] = array_replace($sanitizedSourceEntry, $sanitizedTranslatedEntry);
    }

    return $merged;
}

function translationBundleSetRuntime($bundleKey, $locale, array $sourceLang, array $activeBundle)
{
    $GLOBALS['translationBundleRuntime'] = [
        'bundleKey' => (string)$bundleKey,
        'locale' => translationBundleNormalizeLocale($locale),
        'sourceLang' => $sourceLang,
        'activeBundle' => $activeBundle,
    ];

    return $activeBundle;
}

function translationBundleGetRuntime()
{
    return (isset($GLOBALS['translationBundleRuntime']) && is_array($GLOBALS['translationBundleRuntime']))
        ? $GLOBALS['translationBundleRuntime']
        : [];
}

function translationBundleBuildLocaleCandidates($locale)
{
    $normalizedLocale = translationBundleNormalizeLocale($locale);
    if ($normalizedLocale === '') {
        return [];
    }

    $candidates = [$normalizedLocale];
    $separatorPosition = strpos($normalizedLocale, '-');

    if ($separatorPosition !== false) {
        $baseLocale = substr($normalizedLocale, 0, $separatorPosition);
        if ($baseLocale !== '' && !in_array($baseLocale, $candidates, true)) {
            $candidates[] = $baseLocale;
        }
    }

    return $candidates;
}

function translationBundleResolveStorageLocale($locale, $matchedLocale = '')
{
    $matchedLocale = translationBundleNormalizeLocale($matchedLocale);
    if ($matchedLocale !== '') {
        return $matchedLocale;
    }

    $candidates = translationBundleBuildLocaleCandidates($locale);
    if (count($candidates) > 1) {
        return (string)$candidates[count($candidates) - 1];
    }

    return translationBundleNormalizeLocale($locale);
}

function translationBundleIsExecAvailable()
{
    if (!function_exists('exec')) {
        return false;
    }

    $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array('exec', $disabledFunctions, true);
}

function translationBundleGetPhpBinary()
{
    $isCliCandidate = function ($path) {
        $path = trim((string)$path);
        if ($path === '') {
            return false;
        }

        $basename = strtolower((string)pathinfo($path, PATHINFO_BASENAME));
        if ($basename === '') {
            return false;
        }

        if (strpos($basename, 'php-fpm') !== false || strpos($basename, 'php-cgi') !== false) {
            return false;
        }

        return strpos($basename, 'php') !== false;
    };

    if (function_exists('envValue')) {
        $configuredBinary = trim((string)envValue('TRANSLATION_WORKER_PHP_BINARY', ''));
        if ($isCliCandidate($configuredBinary)) {
            return $configuredBinary;
        }
    }

    if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '' && $isCliCandidate(PHP_BINARY)) {
        return PHP_BINARY;
    }

    if (defined('PHP_BINDIR') && is_string(PHP_BINDIR) && PHP_BINDIR !== '') {
        $candidates = [
            rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php',
            rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php.exe',
            rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php-cli',
            rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php-cli.exe',
            dirname(rtrim(PHP_BINDIR, '/\\')) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php',
            dirname(rtrim(PHP_BINDIR, '/\\')) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php-cli',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && $isCliCandidate($candidate)) {
                return $candidate;
            }
        }
    }

    $fallbackCandidates = [
        '/usr/bin/php',
        '/usr/local/bin/php',
        '/bin/php',
    ];

    foreach ($fallbackCandidates as $candidate) {
        if (is_file($candidate) && $isCliCandidate($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function translationBundleGetWorkerScriptPath()
{
    return dirname(__DIR__) . '/scripts/process-translation-bundle-job.php';
}

function translationBundleGetWorkerLogPath()
{
    if (function_exists('envValue')) {
        $configuredPath = trim((string)envValue('TRANSLATION_WORKER_LOG', ''));
        if ($configuredPath !== '') {
            return $configuredPath;
        }
    }

    return '';
}

function translationBundleWriteWorkerDebug($message, $jobId = 0, $persistToJob = true)
{
    $message = '[' . date('Y-m-d H:i:s') . '] ' . trim((string)$message);
    $jobId = (int)$jobId;

    $logPath = translationBundleGetWorkerLogPath();
    if ($logPath !== '') {
        @file_put_contents($logPath, $message . PHP_EOL, FILE_APPEND);
    } else {
        error_log($message);
    }

    if (
        $persistToJob
        && $jobId > 0
        && class_exists('\dbObject\TranslationBundleRefreshJob')
        && method_exists('\dbObject\TranslationBundleRefreshJob', 'setLastError')
    ) {
        \dbObject\TranslationBundleRefreshJob::setLastError($jobId, $message);
    }
}

function translationBundleInterpolate(string $text, array $variables = [])
{
    foreach ($variables as $name => $value) {
        if (is_scalar($value) || $value === null) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }
    }

    return $text;
}

function translationBundleTriggerAsyncRefreshJob($jobId)
{
    $jobId = (int)$jobId;
    if ($jobId <= 0) {
        translationBundleWriteWorkerDebug('Refused async trigger because job id is invalid.', $jobId);
        return false;
    }

    if (!translationBundleIsExecAvailable()) {
        translationBundleWriteWorkerDebug(
            'Unable to trigger translation worker asynchronously because exec() is unavailable.',
            $jobId
        );
        return false;
    }

    $phpBinary = translationBundleGetPhpBinary();
    $scriptPath = translationBundleGetWorkerScriptPath();
    if (!is_file($scriptPath)) {
        translationBundleWriteWorkerDebug(
            'Unable to trigger translation worker because the worker script is missing: ' . $scriptPath,
            $jobId
        );
        return false;
    }

    $commandParts = [
        escapeshellarg((string)$phpBinary),
        escapeshellarg((string)$scriptPath),
        '--job=' . $jobId,
    ];
    $command = implode(' ', $commandParts);

    if (DIRECTORY_SEPARATOR === '\\') {
        $dispatchCommand = 'start /B "" ' . $command . ' >NUL 2>&1';
        @exec($dispatchCommand);
        translationBundleWriteWorkerDebug(
            'Dispatched translation worker command on Windows for job=' . $jobId . ' command=' . $dispatchCommand,
            $jobId
        );
        return true;
    }

    $dispatchCommand = $command . ' >/dev/null 2>&1 & echo $!';
    $output = [];
    $exitCode = 0;
    @exec($dispatchCommand, $output, $exitCode);
    $pid = trim((string)(count($output) > 0 ? $output[count($output) - 1] : ''));
    $success = $exitCode === 0;

    translationBundleWriteWorkerDebug(
        'Async dispatch ' . ($success ? 'requested' : 'failed')
        . ' for job=' . $jobId
        . ' php=' . $phpBinary
        . ' script=' . $scriptPath
        . ' pid=' . ($pid !== '' ? $pid : 'n/a')
        . ' exit_code=' . $exitCode
        . ' sapi=' . PHP_SAPI
        . ' command=' . $dispatchCommand,
        $jobId
    );

    return $success;
}

function translationBundleQueueRefresh(string $bundleKey, string $locale, array $sourceLang, string $storageLocale = ''): bool
{
    $bundleKey = trim($bundleKey);
    $storageLocale = translationBundleResolveStorageLocale($storageLocale !== '' ? $storageLocale : $locale);

    if ($bundleKey === '' || !translationBundleIsValidLocale($storageLocale)) {
        return false;
    }

    $sourceHash = translationBundleSourceHash($sourceLang);
    $sourceJson = translationBundleEncodePayload($sourceLang);
    if ($sourceJson === '') {
        return false;
    }

    \dbObject\TranslationBundle::markOutdatedPreservingTranslations($bundleKey, $storageLocale, $sourceHash);
    $jobState = \dbObject\TranslationBundleRefreshJob::enqueuePending($bundleKey, $storageLocale, $sourceHash, $sourceJson);

    if (!empty($jobState['shouldTrigger']) && !empty($jobState['job']) && (int)$jobState['job']->getId() > 0) {
        translationBundleTriggerAsyncRefreshJob((int)$jobState['job']->getId());
    }

    return true;
}

function translationBundleGetOpenAiApiKey()
{
    $globalKey = trim((string)($GLOBALS['OpenAI'] ?? ''));
    if ($globalKey !== '') {
        return $globalKey;
    }

    return function_exists('envValue') ? trim((string)envValue('OPENAI_API_KEY', '')) : '';
}

function translationBundleGetOpenAiTranslationModel()
{
    $globalModel = trim((string)($GLOBALS['openAiTranslationModel'] ?? ''));
    if ($globalModel !== '') {
        return $globalModel;
    }

    if (function_exists('envValue')) {
        $configuredModel = trim((string)envValue('OPENAI_TRANSLATION_MODEL', envValue('OPENAI_MODEL', 'gpt-4o')));
        if ($configuredModel !== '') {
            return $configuredModel;
        }
    }

    return 'gpt-4o';
}

function translationBundleDecodeJsonString($payload)
{
    $payload = trim((string)$payload);
    if ($payload === '') {
        return null;
    }

    if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $payload, $matches)) {
        $payload = trim((string)$matches[1]);
    }

    try {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

function translationBundleFilterTranslatedPayload(array $sourceLang, array $translatedPayload)
{
    $filteredPayload = [];

    foreach ($sourceLang as $key => $sourceEntry) {
        $sanitizedSourceEntry = translationBundleSanitizeEntry($sourceEntry);
        $sanitizedTranslatedEntry = translationBundleSanitizeEntry($translatedPayload[$key] ?? null);

        if ($sanitizedSourceEntry === null || $sanitizedTranslatedEntry === null) {
            continue;
        }

        $filteredEntry = [];
        if (array_key_exists('text', $sanitizedSourceEntry) && array_key_exists('text', $sanitizedTranslatedEntry)) {
            $filteredEntry['text'] = (string)$sanitizedTranslatedEntry['text'];
        }
        if (array_key_exists('one', $sanitizedSourceEntry) && array_key_exists('one', $sanitizedTranslatedEntry)) {
            $filteredEntry['one'] = (string)$sanitizedTranslatedEntry['one'];
        }
        if (array_key_exists('other', $sanitizedSourceEntry) && array_key_exists('other', $sanitizedTranslatedEntry)) {
            $filteredEntry['other'] = (string)$sanitizedTranslatedEntry['other'];
        }

        if ($filteredEntry !== []) {
            $filteredPayload[$key] = $filteredEntry;
        }
    }

    return $filteredPayload;
}

function translationBundleTranslateWithAi(string $bundleKey, string $locale, array $sourceLang): array
{
    $apiKey = translationBundleGetOpenAiApiKey();
    if ($apiKey === '') {
        throw new \RuntimeException('OPENAI_API_KEY is not configured.');
    }

    if (!class_exists('\Orhanerday\OpenAi\OpenAi')) {
        throw new \RuntimeException('OpenAI client library is not available.');
    }

    $translationModel = translationBundleGetOpenAiTranslationModel();
    $openAi = new \Orhanerday\OpenAi\OpenAi($apiKey);

    $systemPrompt = 'You are a professional translator for software user interfaces. '
        . 'Translate the provided bundle from French into the requested locale. '
        . 'Return only a JSON object. Keep the same top-level keys. '
        . 'For each entry, return only translated text fields named text, one, and other. '
        . 'Do not return context fields. Preserve placeholders like {username}, HTML, punctuation, and line breaks. '
        . 'Do not invent keys. Do not add explanations.';

    $userPrompt = [
        'bundle_key' => $bundleKey,
        'target_locale' => $locale,
        'source_bundle' => $sourceLang,
    ];

    $result = $openAi->chat([
        'model' => $translationModel,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => json_encode($userPrompt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        ],
        'temperature' => 0.1,
        'max_tokens' => 8000,
    ]);

    $decodedResult = json_decode((string)$result, true);
    if (is_array($decodedResult) && isset($decodedResult['error']['message'])) {
        throw new \RuntimeException((string)$decodedResult['error']['message']);
    }

    $content = is_array($decodedResult)
        ? (string)($decodedResult['choices'][0]['message']['content'] ?? '')
        : '';
    if ($content === '') {
        throw new \RuntimeException('OpenAI returned an empty translation response.');
    }

    $translatedPayload = translationBundleDecodeJsonString($content);
    if (!is_array($translatedPayload)) {
        throw new \RuntimeException('OpenAI returned invalid JSON for the translation bundle.');
    }

    return translationBundleFilterTranslatedPayload($sourceLang, $translatedPayload);
}

function translationBundleProcessRefreshJob(array $job)
{
    $sourceLang = translationBundleDecodeJsonString($job['source_json'] ?? '');
    if (!is_array($sourceLang)) {
        throw new \RuntimeException('The queued translation source JSON is invalid.');
    }

    $translatedPayload = translationBundleTranslateWithAi(
        (string)($job['bundle_key'] ?? ''),
        (string)($job['locale'] ?? ''),
        $sourceLang
    );

    $translatedJson = json_encode($translatedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($translatedJson) || $translatedJson === '') {
        throw new \RuntimeException('Unable to encode the translated bundle JSON.');
    }

    if (!\dbObject\TranslationBundle::saveTranslatedBundle(
        (string)($job['bundle_key'] ?? ''),
        (string)($job['locale'] ?? ''),
        (string)($job['source_hash'] ?? ''),
        $translatedJson,
        'machine_translated'
    )) {
        throw new \RuntimeException('Unable to save the translated bundle.');
    }

    return true;
}

function loadTranslationBundle(string $bundleKey, string $locale, array $sourceLang): array
{
    $bundleKey = trim($bundleKey);
    $locale = translationBundleNormalizeLocale($locale);

    if ($bundleKey === '' || !translationBundleIsValidLocale($locale)) {
        return $sourceLang;
    }

    $sourceHash = translationBundleSourceHash($sourceLang);
    $localeCandidates = translationBundleBuildLocaleCandidates($locale);

    foreach ($localeCandidates as $candidateLocale) {
        $bundle = \dbObject\TranslationBundle::findByBundleAndLocale($bundleKey, $candidateLocale);

        if (!$bundle instanceof \dbObject\TranslationBundle) {
            continue;
        }

        $translatedJson = (string)$bundle->get('translated_json');
        $translatedLang = [];
        $hasInvalidTranslatedJson = false;

        if ($translatedJson !== '') {
            try {
                $translatedLang = json_decode($translatedJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $translatedLang = [];
                $hasInvalidTranslatedJson = true;
            }
        }

        if (!is_array($translatedLang)) {
            $translatedLang = [];
            $hasInvalidTranslatedJson = true;
        }

        $mergedBundle = translationBundleMergeWithSource($sourceLang, $translatedLang);
        $storedHash = (string)$bundle->get('source_hash');
        $storedStatus = trim((string)$bundle->get('status'));
        $needsRefresh = $storedHash !== $sourceHash || $storedStatus === 'outdated' || $hasInvalidTranslatedJson;

        if ($needsRefresh) {
            translationBundleQueueRefresh($bundleKey, $locale, $sourceLang, $candidateLocale);
        }

        return $mergedBundle;
    }

    translationBundleQueueRefresh($bundleKey, $locale, $sourceLang);
    return $sourceLang;
}

function translationBundleInit(string $bundleKey, string $locale, array $sourceLang): array
{
    $activeBundle = loadTranslationBundle($bundleKey, $locale, $sourceLang);
    return translationBundleSetRuntime($bundleKey, $locale, $sourceLang, $activeBundle);
}

function translationBundleMarkForRefresh(string $bundleKey, string $locale, array $sourceLang, string $status = 'outdated'): bool
{
    return translationBundleQueueRefresh($bundleKey, $locale, $sourceLang);
}

function translationBundleResolveEntry(string $key, ?array $bundle = null, ?array $sourceLang = null)
{
    if ($bundle === null || $sourceLang === null) {
        $runtime = translationBundleGetRuntime();
        if ($bundle === null) {
            $bundle = $runtime['activeBundle'] ?? [];
        }
        if ($sourceLang === null) {
            $sourceLang = $runtime['sourceLang'] ?? [];
        }
    }

    $bundleEntry = translationBundleSanitizeEntry($bundle[$key] ?? null);
    if ($bundleEntry !== null) {
        return $bundleEntry;
    }

    return translationBundleSanitizeEntry($sourceLang[$key] ?? null);
}

function translationBundleResolveText(array $entry, array $variables = [])
{
    if (array_key_exists('text', $entry)) {
        return (string)$entry['text'];
    }

    $count = isset($variables['count']) ? (int)$variables['count'] : 0;
    if ($count === 1 && array_key_exists('one', $entry)) {
        return (string)$entry['one'];
    }

    if (array_key_exists('other', $entry)) {
        return (string)$entry['other'];
    }

    return array_key_exists('one', $entry) ? (string)$entry['one'] : '';
}

function translationBundleTranslate(string $key, array $variables = [], ?array $bundle = null, ?array $sourceLang = null): string
{
    $entry = translationBundleResolveEntry($key, $bundle, $sourceLang);
    if ($entry === null) {
        return $key;
    }

    return translationBundleInterpolate(
        translationBundleResolveText($entry, $variables),
        $variables
    );
}

if (!function_exists('t')) {
    function t($key, array $variables = [], ?array $bundle = null, ?array $sourceLang = null)
    {
        return translationBundleTranslate((string)$key, $variables, $bundle, $sourceLang);
    }
}

?>
