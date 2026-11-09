<?php
declare(strict_types=1);

/**
 * Additive STL for regularly spaced daily observations (weekly seasonality).
 * Algorithm: Cleveland et al. (1990), https://netlib.org/a/stl
 * Local linear LOESS, seasonal window 7, trend window 15, low-pass window 9.
 * Two inner iterations and ten bisquare robustness iterations limit outliers.
 * No interpolation of missing observations and no forecasting are performed.
 */
final class StatsStl
{
    public const MIN_DAYS = 21;

    /** @return list<float> Empty when the input cannot support a weekly fit. */
    public static function trend(array $values): array
    {
        $values = array_values($values);
        $count = count($values);
        if ($count < self::MIN_DAYS) {
            return [];
        }
        foreach ($values as &$value) {
            if (!is_numeric($value) || !is_finite((float)$value)) {
                return [];
            }
            $value = (float)$value;
        }
        unset($value);

        $trend = array_fill(0, $count, 0.0);
        $season = $trend;
        $weights = array_fill(0, $count, 1.0);
        $tolerance = max(1.0, max(array_map('abs', $values))) * 1e-12;
        for ($outer = 0; $outer <= 10; $outer++) {
            for ($inner = 0; $inner < 2; $inner++) {
                $extended = array_fill(0, $count + 14, 0.0);
                for ($day = 0; $day < 7; $day++) {
                    $subseries = $subweights = [];
                    for ($index = $day; $index < $count; $index += 7) {
                        $subseries[] = $values[$index] - $trend[$index];
                        $subweights[] = $weights[$index];
                    }
                    // Extend each weekday subseries by one week at both ends.
                    for ($week = -1; $week <= count($subseries); $week++) {
                        $extended[$day + ($week + 1) * 7] = self::localFit($subseries, $week, 7, $subweights);
                    }
                }
                $low = self::movingAverage(self::movingAverage(self::movingAverage($extended, 7), 7), 3);
                $low = self::smooth($low, 9);
                $adjusted = [];
                for ($index = 0; $index < $count; $index++) {
                    $season[$index] = $extended[$index + 7] - $low[$index];
                    $adjusted[] = $values[$index] - $season[$index];
                }
                $trend = self::smooth($adjusted, 15, $weights);
            }
            if ($outer === 10) {
                break;
            }
            $residuals = [];
            foreach ($values as $index => $value) {
                $residuals[] = abs($value - $season[$index] - $trend[$index]);
            }
            $sorted = $residuals;
            sort($sorted, SORT_NUMERIC);
            $middle = intdiv($count, 2);
            $scale = 3 * ($sorted[intdiv($count - 1, 2)] + $sorted[$middle]);
            foreach ($residuals as $index => $residual) {
                // An exact seasonal/linear fit can have a zero median residual.
                $weights[$index] = $scale <= $tolerance
                    ? ($residual <= $tolerance ? 1.0 : 0.0)
                    : ($residual <= 0.001 * $scale ? 1.0
                        : ($residual > 0.999 * $scale ? 0.0 : (1 - ($residual / $scale) ** 2) ** 2));
            }
        }
        return $trend;
    }

    private static function smooth(array $values, int $window, array $weights = []): array
    {
        $result = [];
        foreach ($values as $index => $value) {
            $result[] = self::localFit($values, $index, $window, $weights);
        }
        return $result;
    }

    /** Weighted local linear regression with tricube distance weights. */
    private static function localFit(array $values, int $position, int $window, array $weights): float
    {
        $count = count($values);
        $size = min($count, $window);
        $left = max(0, min($count - $size, $position - intdiv($size, 2)));
        $right = $left + $size - 1;
        $radius = max($position - $left, $right - $position) + intdiv(max(0, $window - $count), 2);
        $sum = $sumX = $sumY = $sumXX = $sumXY = 0.0;
        for ($index = $left; $index <= $right; $index++) {
            $distance = abs($index - $position);
            $weight = $radius > 0 ? max(0.0, 1 - ($distance / $radius) ** 3) ** 3 : 1.0;
            $weight *= $weights[$index] ?? 1.0;
            $offset = $index - $position;
            $sum += $weight;
            $sumX += $weight * $offset;
            $sumY += $weight * $values[$index];
            $sumXX += $weight * $offset * $offset;
            $sumXY += $weight * $offset * $values[$index];
        }
        if ($sum <= 0) {
            return $values[max(0, min($count - 1, $position))];
        }
        $variance = $sumXX / $sum - ($sumX / $sum) ** 2;
        if ($variance <= (0.001 * max(0, $count - 1)) ** 2) {
            return $sumY / $sum;
        }
        $slope = ($sumXY / $sum - $sumX * $sumY / ($sum * $sum)) / $variance;
        return ($sumY - $slope * $sumX) / $sum;
    }

    private static function movingAverage(array $values, int $window): array
    {
        $sum = array_sum(array_slice($values, 0, $window));
        $result = [$sum / $window];
        for ($index = $window, $count = count($values); $index < $count; $index++) {
            $sum += $values[$index] - $values[$index - $window];
            $result[] = $sum / $window;
        }
        return $result;
    }
}

/**
 * Preserve chart indices, reject gaps and omit the current (incomplete) UTC day.
 * Missing observations must remain missing, rather than being treated as zero.
 * @return array<int, float>
 */
function siteStatsDailyTrend(array $series, ?DateTimeImmutable $today = null): array
{
    $today ??= new DateTimeImmutable('today', new DateTimeZone('UTC'));
    $values = [];
    $previous = null;
    foreach ($series as $point) {
        if (!(($point['date'] ?? null) instanceof DateTimeInterface)) {
            return [];
        }
        $day = $point['date']->format('Y-m-d');
        if ($previous !== null && $day !== $previous->modify('+1 day')->format('Y-m-d')) {
            return [];
        }
        $previous = new DateTimeImmutable($day, new DateTimeZone('UTC'));
        if ($day >= $today->format('Y-m-d')) {
            break;
        }
        $values[] = $point['value'] ?? null;
    }
    return StatsStl::trend($values);
}
