<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__, 2) . '/common/auth.php';
require_once dirname(__DIR__, 2) . '/shared/date_groups.php';

if (!function_exists('memoApiEscape')) {
    function memoApiEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('memoApiNormalizeLabel')) {
    function memoApiNormalizeLabel($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        return trim((string)preg_replace('/[^a-z0-9]+/', ' ', $value));
    }
}

if (!function_exists('memoApiSortKey')) {
    function memoApiSortKey($value)
    {
        return memoApiNormalizeLabel($value);
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

commonRestoreRememberedUser();
