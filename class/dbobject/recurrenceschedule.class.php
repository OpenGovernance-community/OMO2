<?php
namespace dbObject;

final class RecurrenceSchedule
{
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_SEMIANNUAL = 'semiannual';
    public const FREQUENCY_YEARLY = 'yearly';

    public static function getFrequencyCatalog()
    {
        return [
            self::FREQUENCY_DAILY => self::FREQUENCY_DAILY,
            self::FREQUENCY_WEEKLY => self::FREQUENCY_WEEKLY,
            self::FREQUENCY_MONTHLY => self::FREQUENCY_MONTHLY,
            self::FREQUENCY_QUARTERLY => self::FREQUENCY_QUARTERLY,
            self::FREQUENCY_SEMIANNUAL => self::FREQUENCY_SEMIANNUAL,
            self::FREQUENCY_YEARLY => self::FREQUENCY_YEARLY,
        ];
    }

    public static function normalizeFrequency($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getFrequencyCatalog()) ? $value : null;
    }

    public static function normalizeSchedule($frequency, $value)
    {
        $frequency = self::normalizeFrequency($frequency);
        $value = trim((string)$value);
        if ($frequency === null || $value === '') {
            return null;
        }

        if ($frequency === self::FREQUENCY_DAILY) {
            return preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $value) ? $value : null;
        }

        $limits = [
            self::FREQUENCY_WEEKLY => [1, 7],
            self::FREQUENCY_MONTHLY => [1, 31],
            self::FREQUENCY_QUARTERLY => [1, 3],
            self::FREQUENCY_SEMIANNUAL => [1, 6],
            self::FREQUENCY_YEARLY => [1, 12],
        ];
        if (!isset($limits[$frequency]) || !ctype_digit($value)) {
            return null;
        }

        $numericValue = (int)$value;
        return $numericValue >= $limits[$frequency][0] && $numericValue <= $limits[$frequency][1]
            ? (string)$numericValue
            : null;
    }

    public static function getPreviousOccurrence($frequency, $schedule, \DateTimeImmutable $reference)
    {
        $frequency = self::normalizeFrequency($frequency);
        $schedule = self::normalizeSchedule($frequency, $schedule);
        if ($frequency === null || $schedule === null) {
            return null;
        }

        if ($frequency === self::FREQUENCY_DAILY) {
            [$hour, $minute] = array_map('intval', explode(':', $schedule));
            $occurrence = $reference->setTime($hour, $minute, 0);
            return $occurrence > $reference ? $occurrence->modify('-1 day') : $occurrence;
        }

        $currentDay = $reference->setTime(0, 0, 0);
        if ($frequency === self::FREQUENCY_WEEKLY) {
            $daysBack = ((int)$currentDay->format('N') - (int)$schedule + 7) % 7;
            return $currentDay->modify('-' . $daysBack . ' days');
        }

        $currentMonth = (int)$reference->format('n');
        $currentYear = (int)$reference->format('Y');
        if ($frequency === self::FREQUENCY_MONTHLY) {
            $targetDay = min((int)$schedule, (int)$reference->format('t'));
            $occurrence = $reference->setDate($currentYear, $currentMonth, $targetDay)->setTime(0, 0, 0);
            if ($occurrence > $reference) {
                $occurrence = $occurrence->modify('-1 month');
                $targetDay = min((int)$schedule, (int)$occurrence->format('t'));
                $occurrence = $occurrence->setDate(
                    (int)$occurrence->format('Y'),
                    (int)$occurrence->format('n'),
                    $targetDay
                )->setTime(0, 0, 0);
            }
            return $occurrence;
        }

        if ($frequency === self::FREQUENCY_QUARTERLY || $frequency === self::FREQUENCY_SEMIANNUAL) {
            $cycleLength = $frequency === self::FREQUENCY_QUARTERLY ? 3 : 6;
            $anchorMonth = (int)$schedule;
            $offset = ($currentMonth - $anchorMonth + 12) % $cycleLength;
            $targetMonth = $currentMonth - $offset;
            $targetYear = $currentYear;
            if ($targetMonth < 1) {
                $targetMonth += 12;
                $targetYear--;
            }
            $occurrence = $reference->setDate($targetYear, $targetMonth, 1)->setTime(0, 0, 0);
            return $occurrence > $reference ? $occurrence->modify('-' . $cycleLength . ' months') : $occurrence;
        }

        $occurrence = $reference->setDate($currentYear, (int)$schedule, 1)->setTime(0, 0, 0);
        return $occurrence > $reference ? $occurrence->modify('-1 year') : $occurrence;
    }

    public static function getNextOccurrence($frequency, $schedule, \DateTimeImmutable $reference)
    {
        $frequency = self::normalizeFrequency($frequency);
        $schedule = self::normalizeSchedule($frequency, $schedule);
        if ($frequency === null || $schedule === null) {
            return null;
        }

        if ($frequency === self::FREQUENCY_DAILY) {
            [$hour, $minute] = array_map('intval', explode(':', $schedule));
            $occurrence = $reference->setTime($hour, $minute, 0);
            return $occurrence > $reference ? $occurrence : $occurrence->modify('+1 day');
        }
        if ($frequency === self::FREQUENCY_WEEKLY) {
            $currentDay = $reference->setTime(0, 0, 0);
            $daysForward = ((int)$schedule - (int)$currentDay->format('N') + 7) % 7;
            $occurrence = $currentDay->modify('+' . $daysForward . ' days');
            return $occurrence > $reference ? $occurrence : $occurrence->modify('+1 week');
        }
        if ($frequency === self::FREQUENCY_MONTHLY) {
            $targetDay = min((int)$schedule, (int)$reference->format('t'));
            $occurrence = $reference->setDate(
                (int)$reference->format('Y'),
                (int)$reference->format('n'),
                $targetDay
            )->setTime(0, 0, 0);
            if ($occurrence > $reference) {
                return $occurrence;
            }
            $monthStart = $reference->modify('first day of next month')->setTime(0, 0, 0);
            $targetDay = min((int)$schedule, (int)$monthStart->format('t'));
            return $monthStart->setDate(
                (int)$monthStart->format('Y'),
                (int)$monthStart->format('n'),
                $targetDay
            )->setTime(0, 0, 0);
        }
        $previous = self::getPreviousOccurrence($frequency, $schedule, $reference);
        if (!($previous instanceof \DateTimeImmutable)) {
            return null;
        }
        if ($frequency === self::FREQUENCY_QUARTERLY) {
            return $previous->modify('+3 months');
        }
        if ($frequency === self::FREQUENCY_SEMIANNUAL) {
            return $previous->modify('+6 months');
        }
        return $previous->modify('+1 year');
    }
}
?>
