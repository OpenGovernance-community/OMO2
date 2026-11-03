<?php
require_once __DIR__ . '/../../translations.php';
require_once dirname(__DIR__, 3) . '/common/lms/ui-translations.php';

function omoLmsUiSourceLang(): array
{
    return commonLmsUiSourceLang();
}

function omoLmsUiT(string $key, array $variables = []): string
{
    return commonLmsUiT($key, $variables);
}
