<?php

if (!function_exists('omoCalendarGetUpcomingSectionMetadata')) {
    function omoCalendarGetUpcomingSectionMetadata(\DateTimeImmutable $anchorDate, \DateTimeImmutable $todayStart): array
    {
        $tomorrowStart = $todayStart->modify('+1 day');
        $dayAfterTomorrow = $todayStart->modify('+2 days');
        $weekEnd = $todayStart->modify('sunday this week')->setTime(23, 59, 59);
        $nextWeekStart = $todayStart->modify('monday next week')->setTime(0, 0, 0);
        $nextWeekEnd = $nextWeekStart->modify('sunday this week')->setTime(23, 59, 59);
        $thisMonthEnd = $todayStart->modify('last day of this month')->setTime(23, 59, 59);
        $nextMonthStart = $todayStart->modify('first day of next month')->setTime(0, 0, 0);
        $nextMonthEnd = $nextMonthStart->modify('last day of this month')->setTime(23, 59, 59);

        if ($anchorDate < $tomorrowStart) {
            return ['key' => 'today', 'sort' => 10];
        }
        if ($anchorDate < $dayAfterTomorrow) {
            return ['key' => 'tomorrow', 'sort' => 20];
        }
        if ($anchorDate <= $weekEnd) {
            return ['key' => 'this_week', 'sort' => 30];
        }
        if ($anchorDate >= $nextWeekStart && $anchorDate <= $nextWeekEnd) {
            return ['key' => 'next_week', 'sort' => 40];
        }
        if ($anchorDate <= $thisMonthEnd) {
            return ['key' => 'this_month', 'sort' => 50];
        }
        if ($anchorDate >= $nextMonthStart && $anchorDate <= $nextMonthEnd) {
            return ['key' => 'next_month', 'sort' => 60];
        }

        $monthStart = $anchorDate->modify('first day of this month')->setTime(0, 0, 0);
        return [
            'key' => 'month_' . $monthStart->format('Y_m'),
            'sort' => 100000 + (int)$monthStart->format('U'),
            'month' => $monthStart,
        ];
    }
}
