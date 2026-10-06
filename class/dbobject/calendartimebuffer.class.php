<?php
namespace dbObject;

/** Attached preparation/closing time, shared by OMO events and booking calendars. */
trait CalendarTimeBuffer
{
    public const MAX_BUFFER_MINUTES = 1440;

    public static function validateBufferMinutes($value): int
    {
        if ($value === null || $value === '') { return 0; }
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]{1,4}$/D', (string)$value)
            || (int)$value > self::MAX_BUFFER_MINUTES) {
            throw new \RuntimeException('buffer_invalid');
        }
        return (int)$value;
    }

    public function withTimeBuffers(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return [\DateTimeImmutable::createFromInterface($start)->modify('-' . max(0, (int)$this->get('preparation_minutes')) . ' minutes'),
            \DateTimeImmutable::createFromInterface($end)->modify('+' . max(0, (int)$this->get('closing_minutes')) . ' minutes')];
    }
}
