<?php
require_once __DIR__ . '/spreadsheet.php';

if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    }
}

use dbObject\Document;
use dbObject\StatIndicator;
use dbObject\StatIndicatorValue;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!function_exists('omoStatsSpreadsheetParseDecimal')) {
    function omoStatsSpreadsheetParseDecimal($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(['.', ','], ['', '.'], $value)
                : str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) ? (float)$value : null;
    }
}

if (!function_exists('omoStatsSpreadsheetParseDate')) {
    function omoStatsSpreadsheetParseDate($value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        foreach (['!Y-m-d', '!Y-m-d H:i', '!Y-m-d H:i:s', '!d.m.Y', '!d/m/Y', '!d-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date instanceof \DateTimeImmutable && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0))) {
                return $date;
            }
        }

        if (is_numeric($value)) {
            $serial = (float)$value;
            if ($serial >= 1 && $serial <= 100000) {
                $days = (int)floor($serial);
                $seconds = (int)round(($serial - $days) * 86400);
                return (new \DateTimeImmutable('1899-12-30 00:00:00'))->modify('+' . $days . ' days')->modify('+' . $seconds . ' seconds');
            }
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

if (!function_exists('omoStatsSpreadsheetLoadWorksheet')) {
    function omoStatsSpreadsheetLoadWorksheet(StatIndicator $indicator): array
    {
        if (!class_exists(IOFactory::class)) {
            return ['status' => false, 'text' => 'La lecture des fichiers tableurs n est pas disponible sur ce serveur.'];
        }

        $document = $indicator->getSpreadsheetDocument();
        if (
            !($document instanceof Document)
            || (int)$document->get('IDorganization') !== (int)$indicator->get('IDorganization')
            || !$document->hasStoredFile()
            || !$document->isUploadedFile()
            || !omoSpreadsheetSupportsFilename($document->getStoredFileDownloadName())
        ) {
            return ['status' => false, 'text' => 'Le document tableur source est introuvable ou invalide.'];
        }

        $organization = $indicator->getOrganization();
        if (!($organization instanceof \dbObject\Organization)) {
            return ['status' => false, 'text' => 'L organisation du document tableur est introuvable.'];
        }
        $download = $organization->downloadDocumentFileFromStorage((string)$document->get('storedfilepath'));
        if (!is_array($download) || empty($download['status']) || !isset($download['body'])) {
            return ['status' => false, 'text' => (string)($download['text'] ?? 'Impossible de telecharger le document tableur.')];
        }

        $extension = omoSpreadsheetFilenameExtension($document->getStoredFileDownloadName());
        $temporaryPath = tempnam(sys_get_temp_dir(), 'omo-spreadsheet-');
        if ($temporaryPath === false) {
            return ['status' => false, 'text' => 'Impossible de preparer la lecture du document tableur.'];
        }
        $pathWithExtension = $temporaryPath . '.' . $extension;
        @unlink($temporaryPath);
        if (@file_put_contents($pathWithExtension, (string)$download['body']) === false) {
            @unlink($pathWithExtension);
            return ['status' => false, 'text' => 'Impossible de preparer la lecture du document tableur.'];
        }

        try {
            $reader = in_array($extension, ['csv', 'tsv'], true)
                ? new \PhpOffice\PhpSpreadsheet\Reader\Csv()
                : IOFactory::createReaderForFile($pathWithExtension);
            $reader->setReadDataOnly(true);
            if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
                $reader->setDelimiter($extension === 'tsv' ? "\t" : ',');
            }
            $workbook = $reader->load($pathWithExtension);
            $sheetName = StatIndicator::normalizeSpreadsheetSheet($indicator->get('spreadsheet_sheet'));
            $worksheet = $sheetName !== '' ? $workbook->getSheetByName($sheetName) : $workbook->getActiveSheet();
            if ($worksheet === null) {
                return ['status' => false, 'text' => 'La feuille du document tableur est introuvable.'];
            }
            return ['status' => true, 'workbook' => $workbook, 'worksheet' => $worksheet];
        } catch (\Throwable $exception) {
            return ['status' => false, 'text' => 'Impossible de lire le document tableur: ' . $exception->getMessage()];
        } finally {
            @unlink($pathWithExtension);
        }
    }
}

if (!function_exists('omoStatsSpreadsheetReadCell')) {
    function omoStatsSpreadsheetReadCell(StatIndicator $indicator, string $cell): array
    {
        $cell = StatIndicator::normalizeEthercalcCell($cell);
        if ($cell === '') {
            return ['status' => false, 'text' => 'La cellule du tableur doit etre ecrite sous la forme A1.'];
        }
        $result = omoStatsSpreadsheetLoadWorksheet($indicator);
        if (empty($result['status'])) {
            return $result;
        }
        try {
            $value = $result['worksheet']->getCell($cell)->getCalculatedValue();
            $value = omoStatsSpreadsheetParseDecimal($value);
            return $value === null
                ? ['status' => false, 'text' => 'La cellule du tableur ne contient pas une valeur numerique.']
                : ['status' => true, 'value' => $value];
        } catch (\Throwable $exception) {
            return ['status' => false, 'text' => 'Impossible de lire la cellule du tableur.'];
        }
    }
}

if (!function_exists('omoStatsSpreadsheetReadTable')) {
    function omoStatsSpreadsheetReadTable(StatIndicator $indicator, string $range, string $dateColumn, string $valueColumn): array
    {
        $range = StatIndicator::normalizeEthercalcRange($range);
        $dateColumn = StatIndicator::normalizeEthercalcColumn($dateColumn);
        $valueColumn = StatIndicator::normalizeEthercalcColumn($valueColumn);
        if ($range === '' || $dateColumn === '' || $valueColumn === '') {
            return ['status' => false, 'text' => 'La configuration du tableau est invalide.'];
        }

        preg_match('/^([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)$/', $range, $matches);
        $startColumn = Coordinate::columnIndexFromString($matches[1] ?? 'A');
        $startRow = (int)($matches[2] ?? 1);
        $endColumn = Coordinate::columnIndexFromString($matches[3] ?? 'A');
        $endRow = (int)($matches[4] ?? 1);
        $dateColumnIndex = Coordinate::columnIndexFromString($dateColumn);
        $valueColumnIndex = Coordinate::columnIndexFromString($valueColumn);
        if ($dateColumnIndex < $startColumn || $dateColumnIndex > $endColumn || $valueColumnIndex < $startColumn || $valueColumnIndex > $endColumn) {
            return ['status' => false, 'text' => 'Les colonnes de date et de valeur doivent etre incluses dans la plage.'];
        }

        $result = omoStatsSpreadsheetLoadWorksheet($indicator);
        if (empty($result['status'])) {
            return $result;
        }

        $worksheet = $result['worksheet'];
        $measurements = [];
        $dataStartRow = $startRow;
        $headerLabel = '';
        $firstDate = omoStatsSpreadsheetParseDate($worksheet->getCell($dateColumn . $startRow)->getCalculatedValue());
        $firstValue = omoStatsSpreadsheetParseDecimal($worksheet->getCell($valueColumn . $startRow)->getCalculatedValue());
        if (!($firstDate instanceof \DateTimeInterface) || $firstValue === null) {
            $headerLabel = trim((string)$worksheet->getCell($valueColumn . $startRow)->getCalculatedValue());
            $dataStartRow += 1;
        }
        for ($row = $dataStartRow; $row <= $endRow; $row += 1) {
            $dateCell = $dateColumn . $row;
            $valueCell = $valueColumn . $row;
            $measuredAt = omoStatsSpreadsheetParseDate($worksheet->getCell($dateCell)->getCalculatedValue());
            $value = omoStatsSpreadsheetParseDecimal($worksheet->getCell($valueCell)->getCalculatedValue());
            if ($measuredAt instanceof \DateTimeInterface && $value !== null) {
                $measurements[] = ['measured_at' => $measuredAt, 'value' => $value];
            }
        }

        usort($measurements, static fn (array $left, array $right) => $left['measured_at']->getTimestamp() <=> $right['measured_at']->getTimestamp());
        return ['status' => true, 'measurements' => $measurements, 'header_label' => $headerLabel];
    }
}

if (!function_exists('omoStatsSynchronizeSpreadsheetIndicator')) {
    function omoStatsSynchronizeSpreadsheetIndicator(StatIndicator $indicator, ?\DateTimeInterface $referenceDate = null): array
    {
        $referenceDate = $referenceDate instanceof \DateTimeInterface ? $referenceDate : new \DateTimeImmutable();
        if ($indicator->isSpreadsheetCellSource()) {
            $result = omoStatsSpreadsheetReadCell($indicator, (string)$indicator->get('spreadsheet_cell'));
            if (empty($result['status'])) {
                return $result;
            }
            $value = new StatIndicatorValue();
            $value->set('IDstatindicator', (int)$indicator->getId());
            $value->set('IDuser', null);
            $value->set('value', (float)$result['value']);
            $value->set('measured_at', \DateTime::createFromInterface($referenceDate));
            $saveResult = $value->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return ['status' => false, 'text' => 'Impossible d enregistrer la valeur du tableur.'];
            }
        } elseif ($indicator->isSpreadsheetTableSource()) {
            $result = omoStatsSpreadsheetReadTable(
                $indicator,
                (string)$indicator->get('spreadsheet_range'),
                (string)$indicator->get('spreadsheet_date_column'),
                (string)$indicator->get('spreadsheet_value_column')
            );
            if (empty($result['status'])) {
                return $result;
            }
            if (!$indicator->replaceMeasurementsFromSpreadsheet($result['measurements'] ?? [])) {
                return ['status' => false, 'text' => 'Impossible de remplacer les valeurs du tableur.'];
            }
        } else {
            return ['status' => false, 'text' => 'Source tableur invalide.'];
        }

        $saveResult = $indicator->markSpreadsheetSynced($referenceDate);
        return is_array($saveResult) && !empty($saveResult['status'])
            ? ['status' => true, 'header_label' => trim((string)($result['header_label'] ?? ''))]
            : ['status' => false, 'text' => 'Impossible de dater la synchronisation du tableur.'];
    }
}

if (!function_exists('omoStatsMaybeSynchronizeSpreadsheetIndicators')) {
    function omoStatsMaybeSynchronizeSpreadsheetIndicators(int $limit = 20): int
    {
        $tmpDirectory = dirname(__DIR__) . '/tmp';
        if (!is_dir($tmpDirectory)) {
            @mkdir($tmpDirectory, 0777, true);
        }
        if (!is_dir($tmpDirectory)) {
            return 0;
        }

        $statePath = $tmpDirectory . '/stats-spreadsheet-sync-last-run.txt';
        $lockPath = $tmpDirectory . '/stats-spreadsheet-sync.lock';
        $intervalSeconds = 300;
        $lastRun = is_file($statePath) ? (int)trim((string)@file_get_contents($statePath)) : 0;
        if ($lastRun > 0 && (time() - $lastRun) < $intervalSeconds) {
            return 0;
        }
        $lockHandle = @fopen($lockPath, 'c');
        if ($lockHandle === false || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            if (is_resource($lockHandle)) {
                fclose($lockHandle);
            }
            return 0;
        }

        $synced = 0;
        try {
            $lastRun = is_file($statePath) ? (int)trim((string)@file_get_contents($statePath)) : 0;
            if ($lastRun > 0 && (time() - $lastRun) < $intervalSeconds) {
                return 0;
            }
            $now = new \DateTimeImmutable();
            foreach (StatIndicator::loadDueSpreadsheetSources($limit, $now) as $indicator) {
                if (!($indicator instanceof StatIndicator)) {
                    continue;
                }
                $result = omoStatsSynchronizeSpreadsheetIndicator($indicator, $now);
                if (!empty($result['status'])) {
                    $synced += 1;
                } else {
                    error_log('Spreadsheet indicator #' . (int)$indicator->getId() . ' was not synchronized: ' . (string)($result['text'] ?? 'unknown error'));
                }
            }
            @file_put_contents($statePath, (string)time(), LOCK_EX);
        } finally {
            @flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
        return $synced;
    }
}
