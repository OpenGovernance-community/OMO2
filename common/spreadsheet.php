<?php

if (!function_exists('omoSpreadsheetSupportedExtensions')) {
    function omoSpreadsheetSupportedExtensions(): array
    {
        return ['xlsx', 'xls', 'xlsm', 'ods', 'csv', 'tsv'];
    }
}

if (!function_exists('omoSpreadsheetSupportsFilename')) {
    function omoSpreadsheetSupportsFilename(string $filename): bool
    {
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        return $extension !== '' && in_array($extension, omoSpreadsheetSupportedExtensions(), true);
    }
}

if (!function_exists('omoSpreadsheetFilenameExtension')) {
    function omoSpreadsheetFilenameExtension(string $filename): string
    {
        return strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
    }
}

