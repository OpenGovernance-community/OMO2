<?php

if (!function_exists('commonUserProfileMonthName')) {
    function commonUserProfileMonthName($month)
    {
        static $months = [
            1 => 'janvier',
            2 => 'fevrier',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'aout',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'decembre',
        ];

        $month = (int)$month;
        return $months[$month] ?? '';
    }
}

if (!function_exists('commonUserProfileBuildBirthdayDate')) {
    function commonUserProfileBuildBirthdayDate($year, $month, $day, DateTimeZone $timezone)
    {
        $year = (int)$year;
        $month = max(1, min(12, (int)$month));
        $day = max(1, (int)$day);
        $maxDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $day = min($day, $maxDay);

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), $timezone);
    }
}

if (!function_exists('commonUserProfileFormatBirthDate')) {
    function commonUserProfileFormatBirthDate($birthDate, $includeYear = true)
    {
        if (!$birthDate instanceof DateTimeInterface) {
            return '';
        }

        $day = (int)$birthDate->format('j');
        $monthName = commonUserProfileMonthName((int)$birthDate->format('n'));
        if ($monthName === '') {
            return '';
        }

        if (!$includeYear) {
            return $day . ' ' . $monthName;
        }

        return $day . ' ' . $monthName . ' ' . $birthDate->format('Y');
    }
}

if (!function_exists('commonUserProfileBuildRecurringDateSummary')) {
    function commonUserProfileBuildRecurringDateSummary($dateValue, array $labels = [], ?DateTimeInterface $referenceDate = null, ?DateTimeZone $timezone = null, ?int $windowDays = null)
    {
        if (!$dateValue instanceof DateTimeInterface) {
            return null;
        }

        if ($timezone === null) {
            $timezone = new DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        if ($referenceDate instanceof DateTimeInterface) {
            $today = DateTimeImmutable::createFromInterface($referenceDate)->setTimezone($timezone)->setTime(0, 0, 0);
        } else {
            $today = new DateTimeImmutable('today', $timezone);
        }

        $month = (int)$dateValue->format('n');
        $day = (int)$dateValue->format('j');
        $monthName = commonUserProfileMonthName($month);
        $shortDateLabel = commonUserProfileFormatBirthDate($dateValue, false);
        $nextOccurrence = commonUserProfileBuildBirthdayDate((int)$today->format('Y'), $month, $day, $timezone);

        if ($nextOccurrence < $today) {
            $nextOccurrence = commonUserProfileBuildBirthdayDate(((int)$today->format('Y')) + 1, $month, $day, $timezone);
        }

        $daysUntil = (int)$today->diff($nextOccurrence)->format('%a');
        if ($windowDays !== null && $daysUntil > max(0, (int)$windowDays)) {
            return null;
        }

        $todayLabel = (string)($labels['today'] ?? "Evenement aujourd'hui");
        $soonPrefix = (string)($labels['soonPrefix'] ?? 'Evenement dans');
        $monthPrefix = (string)($labels['monthPrefix'] ?? 'Evenement en');
        $detailPrefix = array_key_exists('detailPrefix', $labels)
            ? (string)$labels['detailPrefix']
            : 'Le';

        if ($daysUntil === 0) {
            $headline = $todayLabel;
        } elseif ($daysUntil <= 14) {
            $headline = $soonPrefix . ' ' . $daysUntil . ' jour' . ($daysUntil > 1 ? 's' : '');
        } else {
            $headline = $monthPrefix . ' ' . $monthName;
        }

        return [
            'headline' => $headline,
            'detail' => $shortDateLabel !== '' ? trim($detailPrefix . ' ' . $shortDateLabel) : '',
            'daysUntil' => $daysUntil,
            'nextBirthday' => $nextOccurrence,
            'monthName' => $monthName,
        ];
    }
}

if (!function_exists('commonUserProfileBuildRecentDateSummary')) {
    function commonUserProfileBuildRecentDateSummary($dateValue, array $labels = [], ?DateTimeInterface $referenceDate = null, ?DateTimeZone $timezone = null, int $windowDays = 7)
    {
        if (!$dateValue instanceof DateTimeInterface) {
            return null;
        }

        if ($timezone === null) {
            $timezone = new DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        if ($referenceDate instanceof DateTimeInterface) {
            $today = DateTimeImmutable::createFromInterface($referenceDate)->setTimezone($timezone)->setTime(0, 0, 0);
        } else {
            $today = new DateTimeImmutable('today', $timezone);
        }

        $eventDate = DateTimeImmutable::createFromInterface($dateValue)->setTimezone($timezone)->setTime(0, 0, 0);
        if ($eventDate > $today) {
            return null;
        }

        $daysSince = (int)$eventDate->diff($today)->format('%a');
        if ($daysSince > max(0, $windowDays)) {
            return null;
        }

        $label = (string)($labels['label'] ?? 'Nouveau');
        $detailPrefix = array_key_exists('detailPrefix', $labels)
            ? (string)$labels['detailPrefix']
            : 'Arrive le';
        $shortDateLabel = commonUserProfileFormatBirthDate($eventDate, false);

        return [
            'headline' => $label,
            'detail' => $shortDateLabel !== '' ? trim($detailPrefix . ' ' . $shortDateLabel) : '',
            'daysSince' => $daysSince,
            'eventDate' => $eventDate,
        ];
    }
}

if (!function_exists('commonUserProfileBuildBirthdaySummary')) {
    function commonUserProfileBuildBirthdaySummary($birthDate, ?DateTimeInterface $referenceDate = null, ?DateTimeZone $timezone = null)
    {
        return commonUserProfileBuildRecurringDateSummary($birthDate, [
            'today' => "Anniversaire aujourd'hui",
            'soonPrefix' => 'Anniversaire dans',
            'monthPrefix' => 'Anniversaire en',
            'detailPrefix' => 'Le',
        ], $referenceDate, $timezone);
    }
}
