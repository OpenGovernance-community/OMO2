<?php

require_once __DIR__ . '/environment_subdomains.php';

function commonSearchEngineIndexingAllowed(): bool
{
    return strtolower(trim((string)commonReadRuntimeEnvValue('SEARCH_ENGINE_INDEXING', 'allow'))) !== 'block';
}

function commonSendSearchEngineIndexingHeaders(): void
{
    static $sent = false;
    if (!$sent && PHP_SAPI !== 'cli' && !headers_sent() && !commonSearchEngineIndexingAllowed()) {
        // Do not relax more restrictive directives already set by individual endpoints.
        header('X-Robots-Tag: noindex, nofollow', false);
        $sent = true;
    }
}
