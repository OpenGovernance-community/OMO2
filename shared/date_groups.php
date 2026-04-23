<?php

if (!defined('CAL_GREGORIAN')) {
    define('CAL_GREGORIAN', 0);
}

if (!defined('SHARED_NATIVE_CAL_DAYS_IN_MONTH_AVAILABLE')) {
    define('SHARED_NATIVE_CAL_DAYS_IN_MONTH_AVAILABLE', function_exists('cal_days_in_month'));
}

if (!function_exists('sharedCalDaysInMonth')) {
    function sharedCalDaysInMonth(int $calendar, int $month, int $year): int
    {
        if (SHARED_NATIVE_CAL_DAYS_IN_MONTH_AVAILABLE) {
            return cal_days_in_month($calendar, $month, $year);
        }

        if ($calendar !== CAL_GREGORIAN) {
            throw new InvalidArgumentException('Only the Gregorian calendar is supported without ext-calendar.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-n-j', $year . '-' . $month . '-1');

        if (!$date instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('Invalid month or year provided.');
        }

        return (int)$date->format('t');
    }
}

if (!function_exists('cal_days_in_month')) {
    function cal_days_in_month($calendar, $month, $year): int
    {
        return sharedCalDaysInMonth((int)$calendar, (int)$month, (int)$year);
    }
}

if (!function_exists('sharedGetRelativeDateGroups')) {
    function sharedGetRelativeDateGroups(?DateTimeInterface $referenceDate = null, ?array $labels = null): array
    {
        $today = $referenceDate instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($referenceDate)->setTime(0, 0)
            : new DateTimeImmutable('today');

        $lastMonth = $today->modify('-1 month');
        $dayOfWeek = (int)$today->format('N');
        $dayOfMonth = (int)$today->format('d');

        $resolvedLabels = array_merge([
            'today' => "Aujourd'hui",
            'yesterday' => 'Hier',
            'this_week' => 'Cette semaine',
            'last_week' => 'La semaine passée',
            'this_month' => 'Ce mois',
            'last_month' => 'Le mois passé',
            'this_year' => 'Cette année',
            'last_year' => "L'année passée",
            'earlier' => 'Précédemment',
            'too_far' => 'Trop loin',
        ], is_array($labels) ? $labels : []);

        return [
            ['key' => 'today', 'duration' => 0, 'label' => $resolvedLabels['today']],
            ['key' => 'yesterday', 'duration' => 1, 'label' => $resolvedLabels['yesterday']],
            ['key' => 'this_week', 'duration' => min($dayOfWeek, 2), 'label' => $resolvedLabels['this_week']],
            ['key' => 'last_week', 'duration' => max(2, $dayOfWeek), 'label' => $resolvedLabels['last_week']],
            ['key' => 'this_month', 'duration' => max($dayOfWeek + 1, $dayOfWeek + 7), 'label' => $resolvedLabels['this_month']],
            ['key' => 'last_month', 'duration' => max($dayOfMonth, $dayOfWeek + 8), 'label' => $resolvedLabels['last_month']],
            [
                'key' => 'this_year',
                'duration' => $dayOfMonth + sharedCalDaysInMonth(
                    CAL_GREGORIAN,
                    (int)$lastMonth->format('m'),
                    (int)$lastMonth->format('Y')
                ),
                'label' => $resolvedLabels['this_year'],
            ],
            ['key' => 'last_year', 'duration' => 730, 'label' => $resolvedLabels['last_year']],
            ['key' => 'earlier', 'duration' => 1100, 'label' => $resolvedLabels['earlier']],
            ['key' => 'too_far', 'duration' => 9999, 'label' => $resolvedLabels['too_far']],
        ];
    }
}

if (!function_exists('sharedGetRelativeDateGroupIndex')) {
    function sharedGetRelativeDateGroupIndex(int $interval, array $groups): int
    {
        $selectedIndex = 0;

        foreach ($groups as $index => $group) {
            if ($interval >= (int)$group['duration']) {
                $selectedIndex = $index;
                continue;
            }

            break;
        }

        return $selectedIndex;
    }
}

if (!function_exists('sharedGetRelativeDateGroupKey')) {
    function sharedGetRelativeDateGroupKey($date, ?DateTimeInterface $referenceDate = null): string
    {
        if (!$date instanceof DateTimeInterface) {
            return 'too_far';
        }

        $reference = $referenceDate instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($referenceDate)->setTime(0, 0)
            : new DateTimeImmutable('today');
        $normalizedDate = DateTimeImmutable::createFromInterface($date)->setTime(0, 0);

        if ($normalizedDate > $reference) {
            return 'today';
        }

        $ageInDays = (int)$normalizedDate->diff($reference)->format('%a');
        if ($ageInDays === 0) {
            return 'today';
        }

        if ($ageInDays === 1) {
            return 'yesterday';
        }

        $startOfThisWeek = $reference->modify('monday this week');
        $startOfLastWeek = $startOfThisWeek->modify('-7 days');
        $startOfThisMonth = $reference->modify('first day of this month');
        $startOfLastMonth = $startOfThisMonth->modify('-1 month');
        $startOfThisYear = $reference->setDate((int)$reference->format('Y'), 1, 1);
        $startOfLastYear = $startOfThisYear->modify('-1 year');

        if ($normalizedDate >= $startOfThisWeek) {
            return 'this_week';
        }

        if ($normalizedDate >= $startOfLastWeek) {
            return 'last_week';
        }

        if ($normalizedDate >= $startOfThisMonth) {
            return 'this_month';
        }

        if ($normalizedDate >= $startOfLastMonth) {
            return 'last_month';
        }

        if ($normalizedDate >= $startOfThisYear) {
            return 'this_year';
        }

        if ($normalizedDate >= $startOfLastYear) {
            return 'last_year';
        }

        return 'earlier';
    }
}

if (!function_exists('sharedGetRelativeDateGroupIndexForDate')) {
    function sharedGetRelativeDateGroupIndexForDate($date, array $groups, ?DateTimeInterface $referenceDate = null): int
    {
        $groupKey = sharedGetRelativeDateGroupKey($date, $referenceDate);
        $fallbackIndex = count($groups) > 0 ? count($groups) - 1 : 0;

        foreach ($groups as $index => $group) {
            if (($group['key'] ?? '') === $groupKey) {
                return $index;
            }
        }

        return $fallbackIndex;
    }
}

if (!function_exists('sharedGetDateAgeInDays')) {
    function sharedGetDateAgeInDays($date, ?DateTimeInterface $referenceDate = null, int $fallback = 9999): int
    {
        if (!$date instanceof DateTimeInterface) {
            return $fallback;
        }

        $normalizedReferenceDate = $referenceDate instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($referenceDate)->setTime(0, 0)
            : new DateTimeImmutable('today');

        $normalizedDate = DateTimeImmutable::createFromInterface($date)->setTime(0, 0);

        return (int)$normalizedDate->diff($normalizedReferenceDate)->format('%a');
    }
}
