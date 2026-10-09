<?php

/** Escape an iCalendar TEXT value before the caller folds its content lines. */
function commonCalendarEscapeIcsText($value): string
{
    return str_replace(["\\", "\r\n", "\r", "\n", ';', ','],
        ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'], (string)$value);
}

/** Preparation, then five minutes before; short or absent preparation gets one reminder. */
function commonCalendarIcsReminderLines(int $preparationMinutes, string $title): array
{
    $lines = [];
    foreach (array_unique([max(5, $preparationMinutes), 5]) as $minutes) {
        array_push($lines, 'BEGIN:VALARM', 'ACTION:DISPLAY', 'TRIGGER;RELATED=START:-PT' . $minutes . 'M',
            'DESCRIPTION:' . commonCalendarEscapeIcsText($title), 'END:VALARM');
    }
    return $lines;
}
