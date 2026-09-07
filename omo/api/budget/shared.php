<?php

use dbObject\Holon;
use dbObject\Organization;

if (!function_exists('omoBudgetSourceLang')) {
    function omoBudgetSourceLang(): array
    {
        return [
            'budget.title' => ['text' => 'Budget', 'context' => 'Budget application title.'],
            'budget.measured_time' => ['text' => 'Temps mesuré', 'context' => 'Heading for measured work time.'],
            'budget.cumulative_time' => ['text' => 'Temps cumulé', 'context' => 'Label for cumulative measured work time.'],
            'budget.reference' => ['text' => 'Budget des affectations', 'context' => 'Legend label for the time budget curve aggregated from assignments.'],
            'budget.reference.holon' => ['text' => 'Budget du holon', 'context' => 'Legend label for the time budget stored directly on the current holon.'],
            'budget.holon_budget.title' => ['text' => 'Budget du holon', 'context' => 'Heading for the budget stored directly on the current holon.'],
            'budget.holon_budget.description' => ['text' => 'Enveloppes directement attribuées à ce holon.', 'context' => 'Description of the direct holon budget editor.'],
            'budget.holon_budget.time' => ['text' => 'Budget temps', 'context' => 'Label for the time budget stored on a holon.'],
            'budget.holon_budget.time_help' => ['text' => 'En heures.', 'context' => 'Help text for the holon time budget amount.'],
            'budget.holon_budget.money' => ['text' => 'Budget argent', 'context' => 'Label for the money budget stored on a holon.'],
            'budget.holon_budget.recurrence' => ['text' => 'Récurrence', 'context' => 'Label for a holon budget recurrence selector.'],
            'budget.holon_budget.recurrence.choose' => ['text' => 'Choisir…', 'context' => 'Empty choice for a holon budget recurrence selector.'],
            'budget.holon_budget.recurrence.day' => ['text' => 'Par jour', 'context' => 'Daily holon budget recurrence option.'],
            'budget.holon_budget.recurrence.week' => ['text' => 'Par semaine', 'context' => 'Weekly holon budget recurrence option.'],
            'budget.holon_budget.recurrence.month' => ['text' => 'Par mois', 'context' => 'Monthly holon budget recurrence option.'],
            'budget.holon_budget.recurrence.year' => ['text' => 'Par année', 'context' => 'Yearly holon budget recurrence option.'],
            'budget.holon_budget.save' => ['text' => 'Enregistrer', 'context' => 'Button saving the direct holon budgets.'],
            'budget.holon_budget.saved' => ['text' => 'Budget du holon enregistré.', 'context' => 'Success message after saving direct holon budgets.'],
            'budget.holon_budget.error.context' => ['text' => 'Le holon demandé est introuvable.', 'context' => 'Error returned when the holon budget context cannot be resolved.'],
            'budget.holon_budget.error.forbidden' => ['text' => "Vous n’avez pas le droit de modifier le budget de ce holon.", 'context' => 'Error returned when a user cannot edit direct holon budgets.'],
            'budget.holon_budget.error.method' => ['text' => 'Action non autorisée.', 'context' => 'Error returned for an unsupported holon budget request method.'],
            'budget.holon_budget.error.invalid_budget' => ['text' => 'Le budget doit être un nombre positif ou nul.', 'context' => 'Error returned when a direct holon budget amount is invalid.'],
            'budget.holon_budget.error.invalid_recurrence' => ['text' => 'Choisissez une récurrence pour chaque budget renseigné.', 'context' => 'Error returned when a direct holon budget recurrence is invalid.'],
            'budget.holon_budget.error.save' => ['text' => 'Impossible d’enregistrer le budget du holon.', 'context' => 'Fallback error returned when direct holon budgets cannot be saved.'],
            'budget.period' => ['text' => '30 derniers jours', 'context' => 'Label for the initial budget reporting period.'],
            'budget.daily_time' => ['text' => 'Temps par jour', 'context' => 'Heading for the daily measured time chart.'],
            'budget.date' => ['text' => 'Date', 'context' => 'Date label in the measured time chart tooltip.'],
            'budget.scope.direct' => ['text' => 'Temps directement associé à ce rôle', 'context' => 'Scope explanation for a role holon.'],
            'budget.scope.descendants' => ['text' => 'Temps du holon et de tous ses descendants', 'context' => 'Scope explanation for a container holon.'],
            'budget.empty' => ['text' => 'Aucun temps mesuré sur cette période.', 'context' => 'Empty state for the measured time chart.'],
            'budget.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Error shown when the organization or holon cannot be viewed.'],
            'budget.duration.seconds' => ['text' => '{seconds} s', 'context' => 'Measured duration shorter than one minute.'],
            'budget.duration.minutes' => ['text' => '{minutes} min', 'context' => 'Duration shorter than one hour.'],
            'budget.duration.hours' => ['text' => '{hours} h', 'context' => 'Duration made only of full hours.'],
            'budget.duration.hours_minutes' => ['text' => '{hours} h {minutes} min', 'context' => 'Duration made of hours and minutes.'],
        ];
    }
}

if (!function_exists('omoBudgetResolveNiceDurationStep')) {
    function omoBudgetResolveNiceDurationStep(float $minimumStep): int
    {
        $minimumStep = max(1.0, $minimumStep);
        $steps = [
            1, 2, 5, 10, 15, 30,
            60, 120, 300, 600, 900, 1200, 1800, 2700,
            3600, 5400, 7200, 10800, 14400, 21600, 28800, 43200,
            86400, 172800, 259200, 604800, 1209600, 2592000,
        ];

        foreach ($steps as $step) {
            if ($step >= $minimumStep) {
                return $step;
            }
        }

        $minimumDays = $minimumStep / 86400;
        $power = pow(10, floor(log10($minimumDays)));
        $normalizedDays = $minimumDays / $power;
        if ($normalizedDays <= 1) {
            $niceDays = 1;
        } elseif ($normalizedDays <= 2) {
            $niceDays = 2;
        } elseif ($normalizedDays <= 5) {
            $niceDays = 5;
        } else {
            $niceDays = 10;
        }

        return max(86400, (int)round($niceDays * $power * 86400));
    }
}

if (!function_exists('omoBudgetResolveChartScale')) {
    function omoBudgetResolveChartScale(int $maximumValue, int $targetIntervals = 4): array
    {
        $maximumValue = max(1, $maximumValue);
        $targetIntervals = max(1, $targetIntervals);
        $step = omoBudgetResolveNiceDurationStep($maximumValue / $targetIntervals);
        $scaleMaximum = $step * $targetIntervals;

        return [
            'max' => $scaleMaximum,
            'step' => $step,
            'intervals' => $targetIntervals,
        ];
    }
}

if (!function_exists('omoBudgetResolveRecurrenceBounds')) {
    function omoBudgetResolveRecurrenceBounds(DateTimeImmutable $at, string $recurrence): ?array
    {
        $at = $at->setTime(0, 0, 0);
        if ($recurrence === 'day') {
            $start = $at;
            $end = $start->modify('+1 day');
        } elseif ($recurrence === 'week') {
            $start = $at->modify('-' . ((int)$at->format('N') - 1) . ' days');
            $end = $start->modify('+1 week');
        } elseif ($recurrence === 'month') {
            $start = $at->modify('first day of this month');
            $end = $start->modify('+1 month');
        } elseif ($recurrence === 'year') {
            $start = $at->setDate((int)$at->format('Y'), 1, 1);
            $end = $start->modify('+1 year');
        } else {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }
}

if (!function_exists('omoBudgetAggregateTimeBudgetsByRecurrence')) {
    function omoBudgetAggregateTimeBudgetsByRecurrence(array $budgets): array
    {
        $totals = array();
        foreach ($budgets as $budget) {
            $recurrence = trim((string)($budget['recurrence'] ?? ''));
            $hours = is_numeric($budget['hours'] ?? null) ? max(0.0, (float)$budget['hours']) : 0.0;
            if (!in_array($recurrence, ['day', 'week', 'month', 'year'], true) || $hours <= 0) {
                continue;
            }
            $totals[$recurrence] = ($totals[$recurrence] ?? 0.0) + $hours;
        }

        $aggregated = array();
        foreach (['day', 'week', 'month', 'year'] as $recurrence) {
            if (($totals[$recurrence] ?? 0.0) <= 0) {
                continue;
            }
            $aggregated[] = array(
                'hours' => $totals[$recurrence],
                'recurrence' => $recurrence,
            );
        }

        return $aggregated;
    }
}

if (!function_exists('omoBudgetExpectedReferenceSeconds')) {
    function omoBudgetExpectedReferenceSeconds(array $budgets, DateTimeImmutable $at): int
    {
        $expectedSeconds = 0.0;
        foreach ($budgets as $budget) {
            $hours = is_numeric($budget['hours'] ?? null) ? max(0.0, (float)$budget['hours']) : 0.0;
            $bounds = omoBudgetResolveRecurrenceBounds($at, (string)($budget['recurrence'] ?? ''));
            if ($hours <= 0 || !is_array($bounds)) {
                continue;
            }

            $periodSeconds = max(1, $bounds['end']->getTimestamp() - $bounds['start']->getTimestamp());
            $elapsedSeconds = max(0, min($periodSeconds, $at->getTimestamp() - $bounds['start']->getTimestamp()));
            $expectedSeconds += ($hours * 3600) * ($elapsedSeconds / $periodSeconds);
        }

        return max(0, (int)round($expectedSeconds));
    }
}

if (!function_exists('omoBudgetBuildTimeBudgetReferenceSeries')) {
    function omoBudgetBuildTimeBudgetReferenceSeries(array $budgets, array $dateKeys, DateTimeZone $timezone): array
    {
        $budgets = omoBudgetAggregateTimeBudgetsByRecurrence($budgets);
        $dateKeys = array_values(array_filter(array_map('strval', $dateKeys), static function ($dateKey) {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateKey) === 1;
        }));
        if (count($budgets) === 0 || count($dateKeys) === 0) {
            return array();
        }

        sort($dateKeys, SORT_STRING);
        $rangeStart = new DateTimeImmutable($dateKeys[0] . ' 00:00:00', $timezone);
        $rangeEnd = (new DateTimeImmutable($dateKeys[count($dateKeys) - 1] . ' 00:00:00', $timezone))->modify('+1 day');
        $series = array(array(
            'timestamp' => $rangeStart->getTimestamp(),
            'value' => omoBudgetExpectedReferenceSeconds($budgets, $rangeStart),
        ));

        $boundary = $rangeStart->modify('+1 day');
        while ($boundary < $rangeEnd) {
            $hasReset = false;
            foreach ($budgets as $budget) {
                $bounds = omoBudgetResolveRecurrenceBounds($boundary, (string)($budget['recurrence'] ?? ''));
                if (is_array($bounds) && $bounds['start']->getTimestamp() === $boundary->getTimestamp()) {
                    $hasReset = true;
                    break;
                }
            }

            if ($hasReset) {
                $beforeBoundary = $boundary->modify('-1 second');
                $series[] = array(
                    'timestamp' => $boundary->getTimestamp(),
                    'value' => omoBudgetExpectedReferenceSeconds($budgets, $beforeBoundary),
                );
            }
            $series[] = array(
                'timestamp' => $boundary->getTimestamp(),
                'value' => omoBudgetExpectedReferenceSeconds($budgets, $boundary),
            );
            $boundary = $boundary->modify('+1 day');
        }

        $rangeLastSecond = $rangeEnd->modify('-1 second');
        $series[] = array(
            'timestamp' => $rangeEnd->getTimestamp(),
            'value' => omoBudgetExpectedReferenceSeconds($budgets, $rangeLastSecond),
        );

        return $series;
    }
}

if (!function_exists('omoBudgetBuildCumulativeTimeSeries')) {
    function omoBudgetBuildCumulativeTimeSeries(array $dailySeconds, DateTimeZone $timezone, string $resetRecurrence = '', int $initialSeconds = 0): array
    {
        $dailySeconds = array_map(static function ($seconds) {
            return max(0, (int)$seconds);
        }, $dailySeconds);
        if (count($dailySeconds) === 0) {
            return array();
        }

        $dateKeys = array_keys($dailySeconds);
        $rangeStart = new DateTimeImmutable((string)$dateKeys[0] . ' 00:00:00', $timezone);
        $resetRecurrence = in_array($resetRecurrence, ['day', 'week', 'month', 'year'], true)
            ? $resetRecurrence
            : '';
        $cumulativeSeconds = max(0, $initialSeconds);
        $initialBounds = $resetRecurrence !== '' ? omoBudgetResolveRecurrenceBounds($rangeStart, $resetRecurrence) : null;
        if (is_array($initialBounds) && $initialBounds['start']->getTimestamp() === $rangeStart->getTimestamp()) {
            $cumulativeSeconds = 0;
        }

        $series = array(array(
            'timestamp' => $rangeStart->getTimestamp(),
            'date' => (string)$dateKeys[0],
            'value' => $cumulativeSeconds,
            'isDataPoint' => false,
        ));

        foreach ($dailySeconds as $dateKey => $seconds) {
            $dayStart = new DateTimeImmutable((string)$dateKey . ' 00:00:00', $timezone);
            $dayEnd = $dayStart->modify('+1 day');
            if ($dayStart > $rangeStart && $resetRecurrence !== '') {
                $bounds = omoBudgetResolveRecurrenceBounds($dayStart, $resetRecurrence);
                if (is_array($bounds) && $bounds['start']->getTimestamp() === $dayStart->getTimestamp()) {
                    $series[] = array(
                        'timestamp' => $dayStart->getTimestamp(),
                        'date' => (string)$dateKey,
                        'value' => $cumulativeSeconds,
                        'isDataPoint' => false,
                    );
                    $cumulativeSeconds = 0;
                    $series[] = array(
                        'timestamp' => $dayStart->getTimestamp(),
                        'date' => (string)$dateKey,
                        'value' => 0,
                        'isDataPoint' => false,
                    );
                }
            }

            $cumulativeSeconds += max(0, (int)$seconds);
            $series[] = array(
                'timestamp' => $dayEnd->getTimestamp() - 1,
                'date' => (string)$dateKey,
                'value' => $cumulativeSeconds,
                'isDataPoint' => true,
            );
        }

        return $series;
    }
}

if (!function_exists('omoBudgetRenderMeasuredTimeChart')) {
    function omoBudgetRenderMeasuredTimeChart(
        array $dailySeconds,
        DateTimeZone $timezone,
        array $referenceSeries = array(),
        array $holonReferenceSeries = array(),
        string $cumulativeResetRecurrence = '',
        int $initialCumulativeSeconds = 0
    ): string
    {
        $dailySeconds = array_map(static function ($seconds) {
            return max(0, (int)$seconds);
        }, $dailySeconds);
        $totalSeconds = array_sum($dailySeconds);
        if (
            count($dailySeconds) === 0
            || ($totalSeconds <= 0 && count($referenceSeries) === 0 && count($holonReferenceSeries) === 0 && $initialCumulativeSeconds <= 0)
        ) {
            return '';
        }

        $width = 900;
        $height = 300;
        $paddingLeft = 72;
        $paddingRight = 72;
        $paddingTop = 24;
        $paddingBottom = 42;
        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $dateKeys = array_keys($dailySeconds);
        $rangeStart = new DateTimeImmutable((string)$dateKeys[0] . ' 00:00:00', $timezone);
        $rangeEnd = (new DateTimeImmutable((string)$dateKeys[count($dateKeys) - 1] . ' 00:00:00', $timezone))->modify('+1 day');
        $rangeSeconds = max(1, $rangeEnd->getTimestamp() - $rangeStart->getTimestamp());
        $dailyScale = omoBudgetResolveChartScale(max($dailySeconds));
        $referenceValues = array_values(array_filter(array_map(static function ($point) {
            return is_numeric($point['value'] ?? null) ? max(0, (int)$point['value']) : null;
        }, array_merge($referenceSeries, $holonReferenceSeries)), static function ($value) {
            return $value !== null;
        }));
        $cumulativeSeries = omoBudgetBuildCumulativeTimeSeries(
            $dailySeconds,
            $timezone,
            $cumulativeResetRecurrence,
            $initialCumulativeSeconds
        );
        $cumulativeValues = array_map(static function (array $point): int {
            return max(0, (int)($point['value'] ?? 0));
        }, $cumulativeSeries);
        $cumulativeScale = omoBudgetResolveChartScale(max(array_merge([0], $cumulativeValues, $referenceValues)));
        $dayCount = count($dailySeconds);
        $slotWidth = $plotWidth / max(1, $dayCount);
        $barWidth = max(5.0, min(22.0, $slotWidth * 0.62));

        $formatNumber = static function (float $value): string {
            return number_format($value, 2, '.', '');
        };
        $mapY = static function (int $seconds, int $scaleMaximum) use ($paddingTop, $plotHeight): float {
            return $paddingTop + (1 - ($seconds / max(1, $scaleMaximum))) * $plotHeight;
        };
        $mapX = static function (int $timestamp) use ($rangeStart, $rangeSeconds, $paddingLeft, $plotWidth): float {
            $ratio = max(0.0, min(1.0, ($timestamp - $rangeStart->getTimestamp()) / $rangeSeconds));
            return $paddingLeft + ($ratio * $plotWidth);
        };
        $formatDate = static function (string $dateKey) use ($timezone): string {
            return (new DateTimeImmutable($dateKey . ' 12:00:00', $timezone))->format('d.m.Y');
        };

        $svg = '<div class="omo-stats-interactive-chart omo-budget__chart-plot generic-soft-panel" data-omo-stats-interactive-chart>';
        $svg .= '<svg class="omo-stats-chart omo-stats-chart--large omo-stats-chart--cumulative" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . omoApiEscape(omoBudgetT('budget.daily_time')) . '">';

        for ($gridIndex = 0; $gridIndex <= $dailyScale['intervals']; $gridIndex++) {
            $ratio = $gridIndex / $dailyScale['intervals'];
            $gridY = $paddingTop + ($plotHeight * $ratio);
            $dailyGridValue = max(0, $dailyScale['max'] - ($dailyScale['step'] * $gridIndex));
            $cumulativeGridValue = (int)round($cumulativeScale['max'] * (1 - $ratio));
            $svg .= '<line class="omo-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . $formatNumber($gridY) . '" x2="' . ($width - $paddingRight) . '" y2="' . $formatNumber($gridY) . '"/>';
            $svg .= '<text class="omo-stats-chart__axis-label" x="' . ($paddingLeft - 10) . '" y="' . $formatNumber($gridY + 4) . '" text-anchor="end">' . omoApiEscape(omoBudgetFormatDuration((int)$dailyGridValue)) . '</text>';
            $svg .= '<text class="omo-stats-chart__axis-label omo-stats-chart__axis-label--cumulative" x="' . ($width - $paddingRight + 10) . '" y="' . $formatNumber($gridY + 4) . '">' . omoApiEscape(omoBudgetFormatDuration($cumulativeGridValue)) . '</text>';
        }

        foreach ($dailySeconds as $dateKey => $seconds) {
            $dayStart = new DateTimeImmutable((string)$dateKey . ' 00:00:00', $timezone);
            $dayEnd = $dayStart->modify('+1 day');
            $x = $mapX((int)round(($dayStart->getTimestamp() + $dayEnd->getTimestamp()) / 2));
            $barY = $mapY($seconds, $dailyScale['max']);
            $barHeight = max(1.0, ($paddingTop + $plotHeight) - $barY);
            $dailyTooltip = omoBudgetT('budget.measured_time') . ' : ' . omoBudgetFormatDuration($seconds)
                . "\n" . omoBudgetT('budget.date') . ' : ' . $formatDate($dateKey);

            $svg .= '<rect class="omo-stats-chart__bar" x="' . $formatNumber($x - ($barWidth / 2)) . '" y="' . $formatNumber($barY) . '" width="' . $formatNumber($barWidth) . '" height="' . $formatNumber($barHeight) . '" rx="3" data-omo-stats-chart-tooltip="' . omoApiEscape($dailyTooltip) . '" tabindex="0" aria-label="' . omoApiEscape($dailyTooltip) . '"/>';
        }

        $cumulativeCoordinates = array_map(static function (array $point) use ($mapX, $mapY, $cumulativeScale): array {
            return array(
                $mapX((int)$point['timestamp']),
                $mapY(max(0, (int)$point['value']), $cumulativeScale['max']),
                (string)$point['date'],
                max(0, (int)$point['value']),
                !empty($point['isDataPoint']),
            );
        }, $cumulativeSeries);

        $coordinateString = implode(' ', array_map(static function (array $point) use ($formatNumber) {
            return $formatNumber($point[0]) . ',' . $formatNumber($point[1]);
        }, $cumulativeCoordinates));
        if (count($cumulativeCoordinates) > 1) {
            $svg .= '<polyline class="omo-stats-chart__line omo-stats-chart__line--cumulative" points="' . $coordinateString . '"/>';
        }
        foreach ($cumulativeCoordinates as $point) {
            if (empty($point[4])) {
                continue;
            }
            $cumulativeTooltip = omoBudgetT('budget.cumulative_time') . ' : ' . omoBudgetFormatDuration((int)$point[3])
                . "\n" . omoBudgetT('budget.date') . ' : ' . $formatDate((string)$point[2]);
            $svg .= '<circle class="omo-stats-chart__point omo-stats-chart__point--cumulative" cx="' . $formatNumber($point[0]) . '" cy="' . $formatNumber($point[1]) . '" r="3.5" data-omo-stats-chart-tooltip="' . omoApiEscape($cumulativeTooltip) . '" tabindex="0" aria-label="' . omoApiEscape($cumulativeTooltip) . '"/>';
        }

        $referenceCoordinates = array_values(array_filter(array_map(static function ($point) use ($mapX, $mapY, $cumulativeScale) {
            if (!is_numeric($point['timestamp'] ?? null) || !is_numeric($point['value'] ?? null)) {
                return null;
            }
            return [
                $mapX((int)$point['timestamp']),
                $mapY(max(0, (int)$point['value']), $cumulativeScale['max']),
            ];
        }, $referenceSeries)));
        if (count($referenceCoordinates) > 1) {
            $referenceCoordinateString = implode(' ', array_map(static function (array $point) use ($formatNumber) {
                return $formatNumber($point[0]) . ',' . $formatNumber($point[1]);
            }, $referenceCoordinates));
            $svg .= '<polyline class="omo-stats-chart__reference" points="' . $referenceCoordinateString . '"/>';
        }

        $holonReferenceCoordinates = array_values(array_filter(array_map(static function ($point) use ($mapX, $mapY, $cumulativeScale) {
            if (!is_numeric($point['timestamp'] ?? null) || !is_numeric($point['value'] ?? null)) {
                return null;
            }
            return [
                $mapX((int)$point['timestamp']),
                $mapY(max(0, (int)$point['value']), $cumulativeScale['max']),
            ];
        }, $holonReferenceSeries)));
        if (count($holonReferenceCoordinates) > 1) {
            $holonReferenceCoordinateString = implode(' ', array_map(static function (array $point) use ($formatNumber) {
                return $formatNumber($point[0]) . ',' . $formatNumber($point[1]);
            }, $holonReferenceCoordinates));
            $svg .= '<polyline class="omo-stats-chart__reference omo-stats-chart__reference--ceiling omo-budget__holon-reference" points="' . $holonReferenceCoordinateString . '"/>';
        }

        $firstDate = (string)$dateKeys[0];
        $lastDate = (string)$dateKeys[count($dateKeys) - 1];
        $svg .= '<text class="omo-stats-chart__axis-label" x="' . $paddingLeft . '" y="' . ($height - 12) . '">' . omoApiEscape($formatDate($firstDate)) . '</text>';
        $svg .= '<text class="omo-stats-chart__axis-label" x="' . ($width - $paddingRight) . '" y="' . ($height - 12) . '" text-anchor="end">' . omoApiEscape($formatDate($lastDate)) . '</text>';
        $svg .= '</svg>';
        $svg .= '<div class="omo-stats-detail__legend omo-stats-detail__legend--cumulative" aria-hidden="true">';
        $svg .= '<span class="omo-stats-detail__legend-item omo-stats-detail__legend-item--measure">' . omoApiEscape(omoBudgetT('budget.measured_time')) . '</span>';
        $svg .= '<span class="omo-stats-detail__legend-item omo-stats-detail__legend-item--cumulative">' . omoApiEscape(omoBudgetT('budget.cumulative_time')) . '</span>';
        if (count($referenceCoordinates) > 1) {
            $svg .= '<span class="omo-stats-detail__legend-item omo-stats-detail__legend-item--reference">' . omoApiEscape(omoBudgetT('budget.reference')) . '</span>';
        }
        if (count($holonReferenceCoordinates) > 1) {
            $svg .= '<span class="omo-stats-detail__legend-item omo-budget__legend-item--holon-reference">' . omoApiEscape(omoBudgetT('budget.reference.holon')) . '</span>';
        }
        $svg .= '</div></div>';

        return $svg;
    }
}

if (!function_exists('omoBudgetT')) {
    function omoBudgetT(string $key, array $replace = []): string
    {
        static $sourceLang = null;
        static $lang = null;

        $sourceLang = $sourceLang ?? omoBudgetSourceLang();
        $lang = $lang ?? omoLoadTranslationBundle('omo_budget', $sourceLang);

        return t($key, $replace, $lang, $sourceLang);
    }
}

if (!function_exists('omoBudgetResolveContext')) {
    function omoBudgetResolveContext(int $organizationId, int $currentHolonId = 0): array
    {
        $organization = new Organization();
        if ($organizationId <= 0 || !$organization->load($organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoBudgetT('budget.error.context')];
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!($rootHolon instanceof Holon)) {
            return ['status' => false, 'message' => omoBudgetT('budget.error.context')];
        }

        $currentHolon = $rootHolon;
        if ($currentHolonId > 0 && $currentHolonId !== (int)$rootHolon->getId()) {
            $candidate = new Holon();
            if (
                !$candidate->load($currentHolonId)
                || !$candidate->isDescendantOf((int)$rootHolon->getId(), true)
                || !$candidate->canViewDetail()
            ) {
                return ['status' => false, 'message' => omoBudgetT('budget.error.context')];
            }
            $currentHolon = $candidate;
        }

        return [
            'status' => true,
            'organization' => $organization,
            'rootHolon' => $rootHolon,
            'currentHolon' => $currentHolon,
        ];
    }
}

if (!function_exists('omoBudgetFormatDuration')) {
    function omoBudgetFormatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds > 0 && $seconds < 60) {
            return omoBudgetT('budget.duration.seconds', ['seconds' => $seconds]);
        }

        $minutes = max(0, (int)round($seconds / 60));
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return omoBudgetT('budget.duration.minutes', ['minutes' => $minutes]);
        }
        if ($remainingMinutes <= 0) {
            return omoBudgetT('budget.duration.hours', ['hours' => $hours]);
        }

        return omoBudgetT('budget.duration.hours_minutes', [
            'hours' => $hours,
            'minutes' => $remainingMinutes,
        ]);
    }
}
