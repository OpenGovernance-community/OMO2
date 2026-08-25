<?php
require_once __DIR__ . '/ethercalc.php';

use dbObject\Document;
use dbObject\StatIndicator;
use dbObject\StatIndicatorValue;

if (!function_exists('omoStatsEthercalcParseDecimal')) {
    function omoStatsEthercalcParseDecimal($value): ?float
    {
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

if (!function_exists('omoStatsEthercalcParseDate')) {
    function omoStatsEthercalcParseDate($value): ?\DateTimeImmutable
    {
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
                return (new \DateTimeImmutable('1899-12-30 00:00:00'))->modify('+' . (int)floor($serial) . ' days');
            }
        }

        try {
            $date = new \DateTimeImmutable($value);
            return $date->setTime(0, 0, 0);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

if (!function_exists('omoStatsEthercalcParseCsv')) {
    function omoStatsEthercalcParseCsv(string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [];
        }
        fwrite($stream, $csv);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            if ($row === [null]) {
                continue;
            }
            $rows[] = array_map(static fn ($cell) => is_string($cell) ? trim($cell) : '', $row);
        }
        fclose($stream);
        return $rows;
    }
}

if (!function_exists('omoStatsEthercalcFetchRows')) {
    function omoStatsEthercalcFetchRows(Document $document): array
    {
        if (!$document->isEthercalcDocument() || (int)$document->get('active') !== 1) {
            return ['status' => false, 'text' => 'Le document EtherCalc est indisponible.'];
        }

        $roomId = $document->getEthercalcRoomId();
        if ($roomId === '' || !omoEthercalcIsValidRoomId($roomId)) {
            return ['status' => false, 'text' => 'Identifiant EtherCalc invalide.'];
        }

        $result = omoEthercalcRequest('GET', '/' . rawurlencode($roomId) . '.csv');
        if (empty($result['status'])) {
            return $result;
        }

        return ['status' => true, 'rows' => omoStatsEthercalcParseCsv((string)($result['body'] ?? ''))];
    }
}

if (!function_exists('omoStatsEthercalcReadCell')) {
    function omoStatsEthercalcReadCell(Document $document, string $cell): array
    {
        $cell = StatIndicator::normalizeEthercalcCell($cell);
        if ($cell === '') {
            return ['status' => false, 'text' => 'Cellule EtherCalc invalide.'];
        }

        $result = omoStatsEthercalcFetchRows($document);
        if (empty($result['status'])) {
            return $result;
        }

        preg_match('/^([A-Z]+)([0-9]+)$/', $cell, $matches);
        $columnIndex = StatIndicator::ethercalcColumnToIndex($matches[1] ?? '');
        $rowIndex = ((int)($matches[2] ?? 0)) - 1;
        $rawValue = $result['rows'][$rowIndex][$columnIndex] ?? null;
        $value = omoStatsEthercalcParseDecimal($rawValue);
        return $value === null
            ? ['status' => false, 'text' => 'La cellule EtherCalc ne contient pas une valeur numerique.']
            : ['status' => true, 'value' => $value];
    }
}

if (!function_exists('omoStatsEthercalcReadTable')) {
    function omoStatsEthercalcReadTable(Document $document, string $range, string $dateColumn, string $valueColumn): array
    {
        $range = StatIndicator::normalizeEthercalcRange($range);
        $dateColumn = StatIndicator::normalizeEthercalcColumn($dateColumn);
        $valueColumn = StatIndicator::normalizeEthercalcColumn($valueColumn);
        if ($range === '' || $dateColumn === '' || $valueColumn === '') {
            return ['status' => false, 'text' => 'Configuration du tableau EtherCalc invalide.'];
        }

        preg_match('/^([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)$/', $range, $matches);
        $startColumn = StatIndicator::ethercalcColumnToIndex($matches[1] ?? '');
        $startRow = ((int)($matches[2] ?? 0)) - 1;
        $endColumn = StatIndicator::ethercalcColumnToIndex($matches[3] ?? '');
        $endRow = ((int)($matches[4] ?? 0)) - 1;
        $dateColumnIndex = StatIndicator::ethercalcColumnToIndex($dateColumn);
        $valueColumnIndex = StatIndicator::ethercalcColumnToIndex($valueColumn);
        if ($dateColumnIndex < $startColumn || $dateColumnIndex > $endColumn || $valueColumnIndex < $startColumn || $valueColumnIndex > $endColumn) {
            return ['status' => false, 'text' => 'Les colonnes du tableau doivent etre incluses dans la plage.'];
        }

        $result = omoStatsEthercalcFetchRows($document);
        if (empty($result['status'])) {
            return $result;
        }

        $measurements = [];
        $dataStartRow = $startRow;
        $headerLabel = '';
        $firstRow = $result['rows'][$startRow] ?? [];
        $firstDate = omoStatsEthercalcParseDate($firstRow[$dateColumnIndex] ?? null);
        $firstValue = omoStatsEthercalcParseDecimal($firstRow[$valueColumnIndex] ?? null);
        if (!($firstDate instanceof \DateTimeInterface) || $firstValue === null) {
            $headerLabel = trim((string)($firstRow[$valueColumnIndex] ?? ''));
            $dataStartRow += 1;
        }
        for ($rowIndex = $dataStartRow; $rowIndex <= $endRow; $rowIndex += 1) {
            $row = $result['rows'][$rowIndex] ?? [];
            $measuredAt = omoStatsEthercalcParseDate($row[$dateColumnIndex] ?? null);
            $value = omoStatsEthercalcParseDecimal($row[$valueColumnIndex] ?? null);
            if ($measuredAt instanceof \DateTimeInterface && $value !== null) {
                $measurements[] = ['measured_at' => $measuredAt, 'value' => $value];
            }
        }

        usort($measurements, static fn (array $left, array $right) => $left['measured_at']->getTimestamp() <=> $right['measured_at']->getTimestamp());
        return ['status' => true, 'measurements' => $measurements, 'header_label' => $headerLabel];
    }
}

if (!function_exists('omoStatsSynchronizeEthercalcIndicator')) {
    function omoStatsSynchronizeEthercalcIndicator(StatIndicator $indicator, ?\DateTimeInterface $referenceDate = null): array
    {
        $referenceDate = $referenceDate instanceof \DateTimeInterface ? $referenceDate : new \DateTimeImmutable();
        $document = $indicator->getEthercalcDocument();
        if (!($document instanceof Document) || (int)$document->get('IDorganization') !== (int)$indicator->get('IDorganization')) {
            return ['status' => false, 'text' => 'Le document EtherCalc source est introuvable.'];
        }

        if ($indicator->isEthercalcCellSource()) {
            $result = omoStatsEthercalcReadCell($document, (string)$indicator->get('ethercalc_cell'));
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
                return ['status' => false, 'text' => 'Impossible d enregistrer la valeur EtherCalc.'];
            }
        } elseif ($indicator->isEthercalcTableSource()) {
            $result = omoStatsEthercalcReadTable(
                $document,
                (string)$indicator->get('ethercalc_range'),
                (string)$indicator->get('ethercalc_date_column'),
                (string)$indicator->get('ethercalc_value_column')
            );
            if (empty($result['status'])) {
                return $result;
            }
            if (!$indicator->replaceMeasurementsFromEthercalc($result['measurements'] ?? [])) {
                return ['status' => false, 'text' => 'Impossible de remplacer les valeurs EtherCalc.'];
            }
        } else {
            return ['status' => false, 'text' => 'Source EtherCalc invalide.'];
        }

        $saveResult = $indicator->markEthercalcSynced($referenceDate);
        return is_array($saveResult) && !empty($saveResult['status'])
            ? ['status' => true, 'header_label' => trim((string)($result['header_label'] ?? ''))]
            : ['status' => false, 'text' => 'Impossible de dater la synchronisation EtherCalc.'];
    }
}

if (!function_exists('omoStatsMaybeSynchronizeEthercalcIndicators')) {
    function omoStatsMaybeSynchronizeEthercalcIndicators(int $limit = 20): int
    {
        if (!omoEthercalcHasConfig()) {
            return 0;
        }

        $tmpDirectory = dirname(__DIR__) . '/tmp';
        if (!is_dir($tmpDirectory)) {
            @mkdir($tmpDirectory, 0777, true);
        }
        if (!is_dir($tmpDirectory)) {
            return 0;
        }

        $statePath = $tmpDirectory . '/stats-ethercalc-sync-last-run.txt';
        $lockPath = $tmpDirectory . '/stats-ethercalc-sync.lock';
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
            foreach (StatIndicator::loadDueEthercalcSources($limit, $now) as $indicator) {
                if (!($indicator instanceof StatIndicator)) {
                    continue;
                }
                $result = omoStatsSynchronizeEthercalcIndicator($indicator, $now);
                if (!empty($result['status'])) {
                    $synced += 1;
                } else {
                    error_log('EtherCalc indicator #' . (int)$indicator->getId() . ' was not synchronized: ' . (string)($result['text'] ?? 'unknown error'));
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
