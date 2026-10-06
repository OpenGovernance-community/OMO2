<?php

// The same normalized event cache serves CalDAV reports and subscribed ICS feeds.
if (!class_exists(\RRule\RRule::class)) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

function commonExternalCalendarIcsComponents(string $feed): array
{
    $feed = preg_replace('/^\xEF\xBB\xBF/', '', $feed);
    if (strlen($feed) > 10 * 1024 * 1024) {
        throw new \RuntimeException('Le flux ICS est trop volumineux.');
    }
    $lines = commonExternalCalendarUnfoldLines($feed);
    if (strtoupper(trim((string)($lines[0] ?? ''))) !== 'BEGIN:VCALENDAR') {
        throw new \RuntimeException('Le flux ICS est invalide.');
    }
    $events = [];
    $current = null;
    $nested = 0;
    $closed = false;
    $calendarZone = '';
    foreach ($lines as $line) {
        $property = commonExternalCalendarParseProperty($line);
        if ($property === null) { continue; }
        $name = $property['name'];
        $value = strtoupper(trim((string)$property['value']));
        if ($name === 'BEGIN' && $value === 'VEVENT') {
            if ($current !== null || count($events) >= 10000) { throw new \RuntimeException('Le flux ICS contient trop d evenements.'); }
            $current = [];
            $nested = 0;
            continue;
        }
        if ($name === 'END' && $value === 'VEVENT') {
            if ($current === null || $nested !== 0) { throw new \RuntimeException('Le flux ICS est incomplet.'); }
            $events[] = $current;
            $current = null;
            continue;
        }
        if ($current !== null) {
            if ($name === 'BEGIN') { $nested++; }
            elseif ($name === 'END') { $nested--; }
            elseif ($nested === 0) { $current[$name][] = $property; }
        } elseif ($name === 'END' && $value === 'VCALENDAR') {
            $closed = true;
        } elseif ($name === 'X-WR-TIMEZONE') {
            $calendarZone = trim((string)$property['value']);
        }
    }
    if (!$closed || $current !== null) { throw new \RuntimeException('Le flux ICS est incomplet.'); }
    return ['events' => $events, 'timezone' => $calendarZone];
}

function commonExternalCalendarIcsProperty(array $event, string $name): ?array
{
    return $event[$name][0] ?? null;
}

function commonExternalCalendarIcsDate(array $property, \DateTimeZone $defaultZone): array
{
    if (!empty($property['parameters']['TZID'])) {
        try { new \DateTimeZone((string)$property['parameters']['TZID']); }
        catch (\Throwable $exception) { throw new \RuntimeException('Fuseau horaire ICS non pris en charge.'); }
    }
    $date = commonExternalCalendarParseDateTime($property['value'], (array)$property['parameters'], $defaultZone);
    if ($date === null) { throw new \RuntimeException('Une date du flux ICS est invalide.'); }
    return $date;
}

function commonExternalCalendarIcsKey(\DateTimeInterface $date, bool $allDay): string
{
    return $allDay ? $date->format('Ymd') : $date->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
}

function commonExternalCalendarIcsDates(array $event, \DateTimeZone $defaultZone): array
{
    $startProperty = commonExternalCalendarIcsProperty($event, 'DTSTART');
    if ($startProperty === null) { throw new \RuntimeException('Un evenement ICS n a pas de debut.'); }
    $start = commonExternalCalendarIcsDate($startProperty, $defaultZone);
    $endProperty = commonExternalCalendarIcsProperty($event, 'DTEND');
    if ($endProperty !== null) {
        $end = commonExternalCalendarIcsDate($endProperty, $start['timezone']);
        if ($end['is_all_day'] !== $start['is_all_day']) { throw new \RuntimeException('Une duree ICS est invalide.'); }
        $endAt = $end['value'];
    } elseif (($duration = commonExternalCalendarIcsProperty($event, 'DURATION')) !== null) {
        $endAt = $start['value']->add(new \DateInterval((string)$duration['value']));
    } else {
        $endAt = $start['is_all_day'] ? $start['value']->modify('+1 day') : $start['value']->modify('+1 hour');
    }
    if ($endAt <= $start['value']) { throw new \RuntimeException('Une duree ICS est invalide.'); }
    return [$start['value'], $endAt, (bool)$start['is_all_day']];
}

function commonExternalCalendarIcsValues(array $event, string $uid, string $key, \DateTimeImmutable $start,
    \DateTimeImmutable $end, bool $allDay): array
{
    $value = static function (string $name) use ($event): string {
        return trim((string)commonExternalCalendarUnescapeText(commonExternalCalendarIcsProperty($event, $name)['value'] ?? ''));
    };
    $sourceKey = $uid . '|' . $key;
    if (strlen($sourceKey) > 512) { $sourceKey = hash('sha256', $sourceKey); }
    $storageZone = new \DateTimeZone(date_default_timezone_get());
    return [
        'source_key' => $sourceKey,
        'source_etag' => '',
        'title' => $value('SUMMARY') ?: 'Evenement externe',
        'description' => $value('DESCRIPTION'),
        'location' => $value('LOCATION'),
        'timezone' => $start->getTimezone()->getName(),
        'start_at' => $start->setTimezone($storageZone),
        'end_at' => ($allDay ? $end->modify('-1 second') : $end)->setTimezone($storageZone),
        'is_all_day' => $allDay ? 1 : 0,
        'preparation_minutes' => min(1440, max(0, (int)($value('X-OMO-PREPARATION-MINUTES')))),
        'closing_minutes' => min(1440, max(0, (int)($value('X-OMO-CLOSING-MINUTES')))),
        'is_busy' => strtoupper($value('TRANSP') ?: 'OPAQUE') === 'TRANSPARENT' ? 0 : 1,
    ];
}

function commonExternalCalendarIcsRecurrence(string $rule, \DateTimeImmutable $start, bool $allDay): \RRule\RRule
{
    // Passing a DateTime beside an RFC string loses VALUE=DATE in the library.
    // Resolve UNTIL explicitly so date-only limits keep the calendar timezone.
    $parts = ['DTSTART' => \DateTime::createFromImmutable($start)];
    foreach (explode(';', $rule) as $part) {
        $pair = explode('=', $part);
        $name = strtoupper(trim($pair[0]));
        if (count($pair) !== 2 || $name === '' || trim($pair[1]) === '' || array_key_exists($name, $parts)) {
            throw new \RuntimeException('Regle de recurrence ICS invalide.');
        }
        $value = trim($pair[1]);
        if ($name === 'UNTIL') {
            $until = commonExternalCalendarIcsDate(['value' => $value, 'parameters' => []], $start->getTimezone());
            // Some exporters also use an explicit UTC cutoff for all-day series.
            if (!$allDay && $until['is_all_day']) { throw new \RuntimeException('Date de fin de recurrence ICS incompatible.'); }
            $value = \DateTime::createFromImmutable($until['value']);
        }
        $parts[$name] = $value;
    }
    return new \RRule\RRule($parts);
}

function commonExternalCalendarParseIcsFeed(string $feed, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array
{
    try {
        $groups = [];
        $document = commonExternalCalendarIcsComponents($feed);
        $defaultZone = new \DateTimeZone($document['timezone'] ?: date_default_timezone_get());
        foreach ($document['events'] as $event) {
            $uid = trim((string)(commonExternalCalendarIcsProperty($event, 'UID')['value'] ?? ''));
            if ($uid === '') { throw new \RuntimeException('Un evenement ICS n a pas d identifiant.'); }
            $groups[$uid][] = $event;
        }
        $parsed = [];
        foreach ($groups as $uid => $events) {
            $master = null;
            $overrides = [];
            foreach ($events as $event) {
                $rid = commonExternalCalendarIcsProperty($event, 'RECURRENCE-ID');
                if ($rid === null) { $master = $event; }
                else { $overrides[] = [$rid, $event]; }
            }
            if ($master === null || strtoupper((string)(commonExternalCalendarIcsProperty($master, 'STATUS')['value'] ?? '')) === 'CANCELLED') { continue; }
            [$masterStart, $masterEnd, $allDay] = commonExternalCalendarIcsDates($master, $defaultZone);
            $zone = $masterStart->getTimezone();
            $duration = $allDay ? (int)$masterStart->diff($masterEnd)->format('%a') : $masterEnd->getTimestamp() - $masterStart->getTimestamp();
            $starts = [commonExternalCalendarIcsKey($masterStart, $allDay) => $masterStart];
            foreach ($master['RRULE'] ?? [] as $rule) {
                $recurrence = commonExternalCalendarIcsRecurrence((string)$rule['value'], $masterStart, $allDay);
                $lookback = $allDay ? min($duration, 366) . ' days' : min($duration, 366 * 86400) . ' seconds';
                $begin = \DateTimeImmutable::createFromInterface($rangeStart)->modify('-' . $lookback);
                foreach ($recurrence->getOccurrencesBetween($begin, $rangeEnd, 10001) as $occurrence) {
                    if (count($starts) >= 10000) { throw new \RuntimeException('Trop d occurrences ICS.'); }
                    $date = \DateTimeImmutable::createFromInterface($occurrence)->setTimezone($zone);
                    $starts[commonExternalCalendarIcsKey($date, $allDay)] = $date;
                }
            }
            foreach ($master['RDATE'] ?? [] as $property) {
                if (str_contains((string)$property['value'], '/')) { throw new \RuntimeException('RDATE en periode non pris en charge.'); }
                foreach (explode(',', (string)$property['value']) as $rawDate) {
                    $date = commonExternalCalendarIcsDate(['value' => $rawDate, 'parameters' => $property['parameters']], $zone);
                    if ($date['is_all_day'] !== $allDay) { throw new \RuntimeException('RDATE incompatible.'); }
                    $starts[commonExternalCalendarIcsKey($date['value'], $allDay)] = $date['value'];
                }
            }
            foreach ($master['EXDATE'] ?? [] as $property) {
                foreach (explode(',', (string)$property['value']) as $rawDate) {
                    $date = commonExternalCalendarIcsDate(['value' => $rawDate, 'parameters' => $property['parameters']], $zone);
                    unset($starts[commonExternalCalendarIcsKey($date['value'], $allDay)]);
                }
            }
            foreach ($starts as $key => $start) {
                $end = $allDay ? $start->modify('+' . $duration . ' days') : $start->setTimestamp($start->getTimestamp() + $duration);
                if ($start > $rangeEnd || $end < $rangeStart) { continue; }
                $values = commonExternalCalendarIcsValues($master, (string)$uid, (string)$key, $start, $end, $allDay);
                $parsed[$values['source_key']] = $values;
            }
            foreach ($overrides as [$rid, $override]) {
                if (!empty($rid['parameters']['RANGE'])) { throw new \RuntimeException('Exception ICS RANGE non prise en charge.'); }
                $original = commonExternalCalendarIcsDate($rid, $zone);
                $key = commonExternalCalendarIcsKey($original['value'], $allDay);
                $sourceKey = (string)$uid . '|' . $key;
                if (strlen($sourceKey) > 512) { $sourceKey = hash('sha256', $sourceKey); }
                unset($parsed[$sourceKey]);
                if (strtoupper((string)(commonExternalCalendarIcsProperty($override, 'STATUS')['value'] ?? '')) === 'CANCELLED') { continue; }
                $effective = array_replace($master, $override);
                if (!isset($override['DTSTART'])) { $effective['DTSTART'] = [$rid]; }
                if (!isset($override['DTEND']) && !isset($override['DURATION'])) {
                    unset($effective['DTEND'], $effective['DURATION']);
                }
                [$start, $end, $overrideAllDay] = commonExternalCalendarIcsDates($effective, $zone);
                if (!isset($override['DTEND']) && !isset($override['DURATION'])) {
                    $end = $allDay ? $start->modify('+' . $duration . ' days') : $start->setTimestamp($start->getTimestamp() + $duration);
                }
                if ($start > $rangeEnd || $end < $rangeStart) { continue; }
                $values = commonExternalCalendarIcsValues($effective, (string)$uid, $key, $start, $end, $overrideAllDay);
                $parsed[$values['source_key']] = $values;
            }
        }
        if (count($parsed) > 10000) { throw new \RuntimeException('Trop d occurrences ICS.'); }
        return ['status' => true, 'events' => array_values($parsed)];
    } catch (\Throwable $exception) {
        $detail = trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $exception->getMessage()) ?? '');
        if ($detail === '') {
            $detail = 'Cause non precisee';
        }
        $detail = function_exists('mb_substr') ? mb_substr($detail, 0, 400, 'UTF-8') : substr($detail, 0, 400);
        $message = 'Le flux ICS ne peut pas etre lu completement. Detail technique ('
            . get_class($exception) . ') : ' . $detail;
        error_log('OMO ICS parsing failed: ' . $message);
        return ['status' => false, 'message' => $message];
    }
}
