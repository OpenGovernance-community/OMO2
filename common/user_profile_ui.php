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

if (!function_exists('commonUserProfileBuildBirthdaySummary')) {
    function commonUserProfileBuildBirthdaySummary($birthDate, ?DateTimeInterface $referenceDate = null, ?DateTimeZone $timezone = null)
    {
        if (!$birthDate instanceof DateTimeInterface) {
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

        $birthMonth = (int)$birthDate->format('n');
        $birthDay = (int)$birthDate->format('j');
        $nextBirthday = commonUserProfileBuildBirthdayDate((int)$today->format('Y'), $birthMonth, $birthDay, $timezone);
        if ($nextBirthday < $today) {
            $nextBirthday = commonUserProfileBuildBirthdayDate(((int)$today->format('Y')) + 1, $birthMonth, $birthDay, $timezone);
        }

        $daysUntil = (int)$today->diff($nextBirthday)->format('%a');
        $monthName = commonUserProfileMonthName($birthMonth);
        $shortDateLabel = commonUserProfileFormatBirthDate($birthDate, false);

        if ($daysUntil === 0) {
            $headline = "Anniversaire aujourd'hui";
        } elseif ($daysUntil <= 14) {
            $headline = 'Anniversaire dans ' . $daysUntil . ' jour' . ($daysUntil > 1 ? 's' : '');
        } else {
            $headline = 'Anniversaire en ' . $monthName;
        }

        return [
            'headline' => $headline,
            'detail' => $shortDateLabel !== '' ? 'Le ' . $shortDateLabel : '',
            'daysUntil' => $daysUntil,
            'nextBirthday' => $nextBirthday,
            'monthName' => $monthName,
        ];
    }
}
