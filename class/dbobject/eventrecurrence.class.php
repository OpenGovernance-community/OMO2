<?php
namespace dbObject;

/** A series owns its rule and a frozen blueprint, never a live completed PV. */
class EventRecurrence extends DbObject
{
    private array $generatedDocuments = [];
    public static function tableName() { return 'event_recurrence'; }
    public static function rules()
    {
        return [
            [['IDorganization', 'frequency', 'anchor_date', 'timezone'], 'required'],
            [['IDorganization', 'IDreference_event'], 'fk'],
            [['id', 'interval_days', 'month_day', 'weekday', 'ordinal', 'horizon_months', 'anchor_position', 'next_position'], 'integer'],
            [['frequency', 'weekend_shift', 'timezone'], 'string'], [['anchor_date'], 'date'],
            [['parameters'], 'parameters'], [['active'], 'boolean'], [['created_at', 'updated_at'], 'datetime'], [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['IDorganization' => 'Organisation', 'IDreference_event' => 'Reunion de reference', 'frequency' => 'Frequence',
            'interval_days' => 'Intervalle en jours', 'month_day' => 'Jour du mois', 'weekday' => 'Jour de la semaine',
            'ordinal' => 'Rang dans le mois', 'weekend_shift' => 'Report du week-end', 'horizon_months' => 'Horizon en mois',
            'anchor_date' => 'Date de reference', 'timezone' => 'Fuseau horaire', 'active' => 'Active'];
    }
    public static function attributeLength() { return ['frequency' => 24, 'weekend_shift' => 16, 'timezone' => 64]; }

    public static function normalizeSettings(array $values): array
    {
        $frequency = $values['frequency'] ?? 'weekly';
        $shift = $values['weekend_shift'] ?? 'none';
        if (!in_array($frequency, ['weekly', 'days', 'monthly_day', 'monthly_weekday', 'on_close'], true)
            || !in_array($shift, ['none', 'previous', 'next'], true)) {
            throw new \InvalidArgumentException('Recurrence invalide.');
        }
        $documentMode = $values['document_mode'] ?? 'copy';
        if (!in_array($documentMode, ['copy', 'reuse'], true)) { throw new \InvalidArgumentException('Mode de documents invalide.'); }
        $result = ['frequency' => $frequency, 'weekend_shift' => $shift, 'document_mode' => $documentMode];
        foreach (['interval_days' => [1, 366], 'month_day' => [1, 31], 'weekday' => [1, 7], 'ordinal' => [1, 5], 'horizon_months' => [1, 12]] as $key => [$min, $max]) {
            $default = ['interval_days' => 7, 'month_day' => 1, 'weekday' => 1, 'ordinal' => 1, 'horizon_months' => 3][$key];
            $value = filter_var($values[$key] ?? $default, FILTER_VALIDATE_INT);
            if ($value === false || $value < $min || $value > $max) { throw new \InvalidArgumentException('Parametres de recurrence invalides.'); }
            $result[$key] = $value;
        }
        return $result;
    }

    /** Position is immutable even when its event is moved or deleted. 5 means last weekday. */
    public function occurrenceDate(int $position): \DateTimeImmutable
    {
        $offset = $position - (int)$this->get('anchor_position');
        $anchor = new \DateTimeImmutable($this->get('anchor_date')->format('Y-m-d'), new \DateTimeZone($this->get('timezone')));
        if ($offset === 0 || $this->get('frequency') === 'on_close') { return $anchor; }
        $frequency = $this->get('frequency');
        if ($frequency === 'days') {
            $interval = (int)$this->get('interval_days');
            if ($this->get('weekend_shift') === 'none' || $offset < 0) {
                return $this->shiftWeekend($anchor->modify(sprintf('%+d days', $offset * $interval)));
            }
            // Count distinct dates: several weekend slots can land on the same Friday/Monday.
            $nominal = $anchor; $date = $anchor; $count = 0;
            while ($count < $offset) {
                $nominal = $nominal->modify('+' . $interval . ' days');
                $candidate = $this->shiftWeekend($nominal);
                if ($candidate > $date) { $date = $candidate; $count++; }
            }
            return $date;
        }
        if ($frequency === 'weekly') {
            $delta = ((int)$this->get('weekday') - (int)$anchor->format('N') + 7) % 7;
            $days = $delta + ($offset - ($delta > 0 && $offset > 0 ? 1 : 0)) * 7;
            return $anchor->modify(sprintf('%+d days', $days));
        }
        $month = $anchor->modify('first day of this month');
        $candidate = $this->monthlyDate($month);
        $months = $offset > 0 ? $offset - 1 + ($candidate <= $anchor ? 1 : 0)
            : $offset + ($candidate < $anchor ? 1 : 0);
        return $this->monthlyDate($month->modify(sprintf('%+d months', $months)));
    }

    private function monthlyDate(\DateTimeImmutable $month): \DateTimeImmutable
    {
        $frequency = $this->get('frequency');
        // Start at day one to avoid PHP overflowing February into March.
        if ($frequency === 'monthly_weekday') {
            $weekday = (int)$this->get('weekday');
            if ((int)$this->get('ordinal') === 5) {
                $last = $month->modify('last day of this month');
                return $last->modify('-' . (((int)$last->format('N') - $weekday + 7) % 7) . ' days');
            }
            return $month->modify('+' . (($weekday - (int)$month->format('N') + 7) % 7 + ((int)$this->get('ordinal') - 1) * 7) . ' days');
        }
        $day = min((int)$this->get('month_day'), (int)$month->format('t'));
        $date = $month->setDate((int)$month->format('Y'), (int)$month->format('n'), $day);
        return $this->shiftWeekend($date);
    }

    private function shiftWeekend(\DateTimeImmutable $date): \DateTimeImmutable
    {
        if ((int)$date->format('N') >= 6 && $this->get('weekend_shift') !== 'none') {
            $date = $this->get('weekend_shift') === 'previous' ? $date->modify('previous friday') : $date->modify('next monday');
        }
        return $date;
    }

    private static function requireSaved($result): void
    {
        if (!is_array($result) || empty($result['status'])) {
            throw new \RuntimeException((string)($result['text'] ?? 'Impossible d enregistrer la recurrence.'));
        }
    }

    public static function validateDocumentTypes(array $types, string $frequency, string $mode): void
    {
        $hasPv = in_array(Document::TYPE_PV, $types, true);
        if ($mode === 'reuse' && $hasPv) { throw new \InvalidArgumentException('calendar.recurrence.shared_pv'); }
        if ($frequency === 'on_close' && !$hasPv) { throw new \InvalidArgumentException('calendar.recurrence.requires_pv'); }
    }

    public function getDocumentMode(): string { return $this->getParameter('document_mode') === 'reuse' ? 'reuse' : 'copy'; }

    public static function captureBlueprint(Event $event, string $documentMode = 'copy'): array
    {
        $fields = [];
        foreach (['IDuser', 'IDholon', 'IDproject', 'title', 'description', 'status', 'locationmode', 'locationaddress', 'videomeetingurl', 'is_all_day'] as $field) {
            $fields[$field] = $event->get($field);
        }
        $start = $event->get('start_at'); $end = $event->get('end_at');
        $invitations = [];
        foreach ($event->getInvitations(true) as $invitation) {
            if ($invitation->get('status') === ResourceInvitation::STATUS_REVOKED) { continue; }
            $invitations[] = array_combine(['invitation_type', 'IDholon', 'IDuser', 'email', 'display_name'],
                array_map(fn($field) => $invitation->get($field), ['invitation_type', 'IDholon', 'IDuser', 'email', 'display_name']));
        }
        $documents = [];
        foreach ($event->getAssociatedDocuments() as $document) {
            if ($document->isArchived()) { continue; }
            if ($documentMode === 'reuse') {
                self::requireSaved(['status' => EventSharedDocument::attach($event, $document)]);
                // Remove single-event ownership: shared titles and dates must stay stable.
                if ((int)$document->get('IDevent') === (int)$event->getId()) {
                    $document->set('IDevent', null); self::requireSaved($document->save());
                }
                $documents[] = ['source_id' => (int)$document->getId(), 'shared' => true];
            } else { $documents[] = $document->exportEventRecurrenceBlueprint($event); }
        }
        return ['fields' => $fields, 'source_start' => $start->format('c'), 'time' => $start->format('H:i:s'),
            'duration_seconds' => max(0, strtotime($end->format('Y-m-d H:i:s')) - strtotime($start->format('Y-m-d H:i:s'))),
            'invitations' => $invitations, 'documents' => $documents];
    }

    /** The editor has already validated and saved the schedule in this transaction.
     * Persist only membership: a corrected end may now be in the past, so a second
     * full Event::save() would incorrectly treat this same edit as a later edit.
     */
    private static function saveEventLink(Event $event): void
    {
        if ((int)$event->getId() <= 0 || !self::getPdo()->inTransaction()) {
            throw new \RuntimeException('La reunion doit etre enregistree dans la transaction de recurrence.');
        }
        self::requireSaved(['status' => self::execute('UPDATE event SET IDeventrecurrence = :series,
            recurrence_position = :position, recurrence_exception = :exception, updated_at = NOW()
            WHERE id = :event AND IDorganization = :organization',
            ['series' => $event->get('IDeventrecurrence'), 'position' => $event->get('recurrence_position'),
                'exception' => $event->get('recurrence_exception') ? 1 : 0,
                'event' => $event->getId(), 'organization' => $event->get('IDorganization')])]);
    }

    /** Compare editable meeting data, excluding personal buffers, timestamps and participant responses. */
    public static function captureMeetingEditState(Event $event): array
    {
        $state = [];
        foreach (['IDholon', 'IDproject', 'is_all_day'] as $field) { $state[$field] = (int)$event->get($field); }
        foreach (['title', 'description', 'status', 'timezone', 'locationmode', 'locationaddress', 'videomeetingurl'] as $field) {
            $state[$field] = trim((string)$event->get($field));
        }
        foreach (['start_at', 'end_at'] as $field) { $state[$field] = $event->get($field)->format('Y-m-d H:i:s'); }
        $invitations = [];
        foreach ($event->getInvitations(true) as $invitation) {
            if ($invitation->get('status') === ResourceInvitation::STATUS_REVOKED) { continue; }
            $invitations[] = json_encode([(string)$invitation->get('invitation_type'), (int)$invitation->get('IDholon'),
                (int)$invitation->get('IDuser'), mb_strtolower(trim((string)$invitation->get('email')))]);
        }
        sort($invitations, SORT_STRING);
        $state['invitations'] = array_values(array_unique($invitations));
        $state['documents'] = array_map(static fn($document) => (int)$document->getId(), $event->getAssociatedDocuments());
        sort($state['documents'], SORT_NUMERIC);
        return $state;
    }

    /** Called inside the event editor transaction, after invitations/documents have been saved. */
    public static function configure(Event $event, array $settings, bool $applyFollowing, bool $enabled, string $strategy = 'replan', ?array $previousMeetingState = null): void
    {
        if (!in_array($strategy, ['replan', 'after_last', 'stop_keep', 'stop_delete', 'content'], true)) {
            throw new \InvalidArgumentException('Strategie de recurrence invalide.');
        }
        $series = $event->getRecurrence();
        if ($series) {
            $series->lock(); $series->repairLegacyDocumentTitles();
            $event->load((int)$event->getId(), true);
        }
        if ($series && in_array($strategy, ['stop_keep', 'stop_delete'], true)) {
            $series->set('active', 0); self::requireSaved($series->save());
            return;
        }
        if (!$enabled) {
            if ($series) {
                if ($applyFollowing) { $series->lock(); $series->set('active', 0); self::requireSaved($series->save()); }
                else { self::transferReference($event); }
                $event->set('recurrence_exception', 1);
                self::saveEventLink($event);
            }
            return;
        }
        $settings = self::normalizeSettings($settings);
        $preserveDocumentSettings = $series && (!$applyFollowing || $strategy === 'content');
        $documentMode = $preserveDocumentSettings ? $series->getDocumentMode() : $settings['document_mode'];
        self::validateDocumentTypes(array_map(static fn($document) => $document->getDocumentType(),
            array_filter($event->getAssociatedDocuments(), static fn($document) => !$document->isArchived())),
            $preserveDocumentSettings ? $series->get('frequency') : $settings['frequency'], $documentMode);
        unset($settings['document_mode']);
        if ($series && !$applyFollowing) {
            if ($previousMeetingState !== null && $previousMeetingState === self::captureMeetingEditState($event)) {
                return;
            }
            self::transferReference($event);
            $event->set('recurrence_exception', 1);
            self::saveEventLink($event);
            return;
        }
        $newSeries = !$series;
        if ($newSeries && $event->get('end_at') < new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Une reunion passee ne peut pas commencer une nouvelle serie.');
        }
        if (!$series) {
            $series = new self(); $series->set('IDorganization', (int)$event->get('IDorganization'));
            $series->set('next_position', 1); $series->set('anchor_position', 0);
        } else { $series->lock(); }
        $position = $newSeries ? 0 : (int)$event->get('recurrence_position');
        $preserveRule = !$newSeries && $strategy === 'content';
        if (!$preserveRule) { foreach ($settings as $field => $value) { $series->set($field, $value); } }
        $start = $event->get('start_at');
        if (!$preserveRule) {
            $anchorPosition = $position;
            if (!$newSeries && $strategy === 'after_last') {
                $last = self::fetchRow('SELECT start_at FROM event WHERE IDeventrecurrence = :series AND active = 1 ORDER BY start_at DESC LIMIT 1', ['series' => $series->getId()]);
                if ($last) { $start = new \DateTimeImmutable($last['start_at'], new \DateTimeZone($series->get('timezone'))); }
                // Deleted positions stay consumed, even if the last planned meeting was removed.
                $anchorPosition = (int)$series->get('next_position') - 1;
            }
            $series->set('anchor_date', $start->format('Y-m-d'));
            $series->set('anchor_position', $anchorPosition);
        }
        $series->set('timezone', $event->get('timezone') ?: date_default_timezone_get());
        $series->set('IDreference_event', (int)$event->getId());
        $parameters = json_decode((string)$series->get('parameters'), true) ?: [];
        $parameters['blueprint'] = self::captureBlueprint($event, $documentMode); $parameters['document_title_revision'] = 1;
        $parameters['document_mode'] = $documentMode;
        $series->set('parameters', $parameters);
        if (!$preserveRule) { $series->set('active', 1); }
        self::requireSaved($series->save());
        $event->set('IDeventrecurrence', (int)$series->getId()); $event->set('recurrence_position', $position);
        $event->set('recurrence_exception', 0); self::saveEventLink($event);
        if (!$newSeries && $strategy !== 'after_last') {
            foreach (self::fetchAll('SELECT * FROM event WHERE IDeventrecurrence = :series AND recurrence_position > :position AND active = 1 AND recurrence_exception = 0 AND start_at > :now ORDER BY recurrence_position',
                ['series' => $series->getId(), 'position' => $position, 'now' => new \DateTimeImmutable()]) ?: [] as $row) {
                $future = new Event(); $future->hydrateFromDatabaseRow($row, true);
                $chosenStart = $preserveRule || $series->get('frequency') === 'on_close'
                    ? new \DateTimeImmutable($future->get('start_at')->format('Y-m-d') . ' ' . $series->getParameter('blueprint')['time'], new \DateTimeZone($series->get('timezone')))
                    : null;
                $series->applyBlueprint($future, (int)$future->get('recurrence_position'), false, $chosenStart);
            }
        }
    }

    /** Lock order for all writers is series first, then events. */
    public function lock(): void
    {
        $row = self::fetchRow('SELECT * FROM event_recurrence WHERE id = :id FOR UPDATE', ['id' => $this->getId()]);
        if (!$row) { throw new \RuntimeException('Recurrence introuvable.'); }
        $this->hydrateFromDatabaseRow($row, true);
    }

    /** One-time repair for the original French default names frozen before title tracking. */
    private function repairLegacyDocumentTitles(): int
    {
        if ((int)$this->getParameter('document_title_revision') >= 1) { return 0; }
        $blueprint = $this->getParameter('blueprint'); $count = 0;
        if (!is_array($blueprint)) { return 0; }
        $prefix = 'PV ' . (string)($blueprint['fields']['title'] ?? '') . ' du ';
        foreach ($blueprint['documents'] as &$documentBlueprint) {
            $title = (string)($documentBlueprint['values']['title'] ?? '');
            if (isset($documentBlueprint['title_date_pattern']) || ($documentBlueprint['values']['document_type'] ?? '') !== Document::TYPE_PV
                || !str_starts_with($title, $prefix)) { continue; }
            $dateText = substr($title, strlen($prefix));
            $date = \DateTimeImmutable::createFromFormat('!d.m.Y H:i', $dateText);
            if ($date && $date->format('d.m.Y H:i') === $dateText) {
                $documentBlueprint['title_date_pattern'] = ['before' => $prefix, 'after' => ''];
            }
        }
        unset($documentBlueprint);
        foreach (self::fetchAll('SELECT * FROM event WHERE IDeventrecurrence = :series AND active = 1', ['series' => $this->getId()]) ?: [] as $row) {
            $event = new Event(); $event->hydrateFromDatabaseRow($row, true);
            foreach ($event->getAssociatedDocuments() as $document) {
                if ($document->isArchived()) { continue; }
                foreach ($blueprint['documents'] as $documentBlueprint) {
                    if (!isset($documentBlueprint['title_date_pattern']) || $document->get('title') !== $documentBlueprint['values']['title']
                        || $document->getDocumentType() !== $documentBlueprint['values']['document_type']) { continue; }
                    $before = (string)$document->get('title');
                    if (!$event->registerDefaultDocumentTitle($document, $documentBlueprint['title_date_pattern'])) {
                        throw new \RuntimeException('Impossible de conserver le nom automatique du document.');
                    }
                    self::requireSaved($document->syncDefaultEventTitle($event));
                    if ($before !== (string)$document->get('title')) { $count++; }
                    break;
                }
            }
        }
        $parameters = json_decode((string)$this->get('parameters'), true) ?: [];
        $parameters['blueprint'] = $blueprint; $parameters['document_title_revision'] = 1;
        $this->set('parameters', $parameters); self::requireSaved($this->save());
        return $count;
    }

    public static function repairLegacyDocumentTitlesBatch(int $limit = 20, ?int $seriesId = null): int
    {
        if (!self::tableExists(self::tableName())) { return 0; }
        $count = 0;
        foreach (self::fetchAll("SELECT id FROM event_recurrence WHERE JSON_EXTRACT(COALESCE(NULLIF(parameters, ''), '{}'), '$.document_title_revision') IS NULL"
            . ($seriesId !== null ? ' AND id = :series' : '') . ' ORDER BY id LIMIT ' . max(1, $limit),
            $seriesId !== null ? ['series' => $seriesId] : []) ?: [] as $row) {
            $pdo = self::getPdo(); $ownsTransaction = !$pdo->inTransaction();
            try {
                if ($ownsTransaction) { $pdo->beginTransaction(); }
                $series = new self(); $series->setId((int)$row['id']); $series->lock();
                $count += $series->repairLegacyDocumentTitles();
                if ($ownsTransaction) { $pdo->commit(); }
            } catch (\Throwable $exception) {
                if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
                throw $exception;
            }
        }
        return $count;
    }

    private function applyBlueprint(Event $event, int $position, bool $createDocuments, ?\DateTimeImmutable $chosenStart = null): void
    {
        $blueprint = $this->getParameter('blueprint');
        if (!is_array($blueprint)) { throw new \RuntimeException('Reunion de reference indisponible.'); }
        foreach ($blueprint['fields'] as $field => $value) { $event->set($field, $value); }
        $date = $this->occurrenceDate($position);
        [$hour, $minute, $second] = array_map('intval', explode(':', $blueprint['time']));
        $start = $chosenStart ?? $date->setTime($hour, $minute, $second);
        $sourceStart = new \DateTimeImmutable($blueprint['source_start'] ?? ($this->get('anchor_date')->format('Y-m-d') . ' ' . $blueprint['time']));
        $event->set('title', Event::replaceMeetingDateInTitle((string)$event->get('title'), $sourceStart, $start));
        // Wall clock duration keeps ordinary local meetings stable across DST transitions.
        $endWall = (new \DateTimeImmutable($start->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')))->modify('+' . (int)$blueprint['duration_seconds'] . ' seconds');
        $end = new \DateTimeImmutable($endWall->format('Y-m-d H:i:s'), $date->getTimezone());
        if (!$createDocuments && $start <= new \DateTimeImmutable()) { throw new \RuntimeException('Une occurrence ne peut pas etre deplacee dans le passe.'); }
        $event->set('IDorganization', (int)$this->get('IDorganization')); $event->set('timezone', $this->get('timezone'));
        $event->set('start_at', $start); $event->set('end_at', $end); $event->set('active', 1);
        $event->set('IDeventrecurrence', (int)$this->getId()); $event->set('recurrence_position', $position); $event->set('recurrence_exception', 0);
        if ($createDocuments) {
            $parameters = json_decode((string)$event->get('parameters'), true) ?: [];
            $parameters['recurrence_invitation_pending'] = 1;
            $event->set('parameters', $parameters);
        }
        self::requireSaved($event->save());
        $desired = [];
        foreach ($blueprint['invitations'] as $data) {
            $invitation = new EventInvitation(); foreach ($data as $field => $value) { $invitation->set($field, $value); }
            $desired[$invitation->getIdentityKey()] = $invitation;
        }
        foreach ($event->getInvitations(false) as $existing) {
            $key = $existing->getIdentityKey();
            if (isset($desired[$key])) {
                if (!$existing->get('active') || $existing->get('status') === ResourceInvitation::STATUS_REVOKED) {
                    $existing->set('status', 'invited'); $existing->set('active', 1); $existing->set('accepted', null);
                    self::requireSaved($existing->save());
                }
                unset($desired[$key]); continue;
            }
            $existing->set('active', 0); self::requireSaved($existing->save());
        }
        foreach ($desired as $invitation) {
            $invitation->set('IDevent', $event->getId()); $invitation->set('status', 'invited'); $invitation->set('active', 1);
            self::requireSaved($invitation->save());
        }
        if ($createDocuments) {
            foreach ($blueprint['documents'] as $documentBlueprint) {
                if (!empty($documentBlueprint['shared'])) {
                    $document = new Document();
                    if (!$document->load((int)$documentBlueprint['source_id'], true) || !EventSharedDocument::attach($event, $document)) {
                        throw new \RuntimeException('Document partage indisponible.');
                    }
                    continue;
                }
                $documentBlueprint['source_start'] ??= $sourceStart->format('c');
                $document = new Document();
                $this->generatedDocuments[] = $document;
                self::requireSaved($document->createFromEventRecurrenceBlueprint($event, $documentBlueprint));
            }
        }
    }

    /** Remove provisioned files/pads before the SQL transaction is rolled back. */
    private function discardGeneratedDocuments(): void
    {
        foreach (array_reverse($this->generatedDocuments) as $document) {
            if ((int)$document->getId() <= 0) { continue; }
            try { $document->delete(); }
            catch (\Throwable $exception) { error_log('OMO recurrence document cleanup failed: ' . $exception->getMessage()); }
        }
        $this->generatedDocuments = [];
    }

    /** Each occurrence and its cursor are committed atomically; failed work can safely retry. */
    public static function generateDueBatch(int $limit = 100, ?\DateTimeImmutable $now = null, ?int $seriesId = null): int
    {
        if (!self::tableExists(self::tableName())) { return 0; }
        $now ??= new \DateTimeImmutable(); $created = 0; $remaining = max(1, $limit); $failures = [];
        foreach (self::fetchAll("SELECT id FROM event_recurrence WHERE active = 1 AND frequency <> 'on_close'"
            . ($seriesId !== null ? ' AND id = :series' : '') . ' ORDER BY updated_at, id', $seriesId !== null ? ['series' => $seriesId] : []) ?: [] as $row) {
            if ($remaining <= 0) { break; }
            $pdo = self::getPdo(); $ownsTransaction = !$pdo->inTransaction();
            $createdBefore = $created; $remainingBefore = $remaining; $series = null;
            try {
                if ($ownsTransaction) { $pdo->beginTransaction(); }
                $series = new self(); $series->setId((int)$row['id']); $series->lock();
                $series->repairLegacyDocumentTitles();
                if (!$series->get('active') || $series->get('frequency') === 'on_close') {
                    if ($ownsTransaction) { $pdo->commit(); }
                    continue;
                }
                $horizonBase = $now->setTimezone(new \DateTimeZone($series->get('timezone')));
                // A future start must receive a full planning window. Once the
                // series has started, cron advances that window from today.
                $anchor = $series->occurrenceDate((int)$series->get('anchor_position'));
                if ($anchor > $horizonBase) { $horizonBase = $anchor; }
                $horizonMonth = $horizonBase->modify('first day of this month')->modify('+' . (int)$series->get('horizon_months') . ' months');
                $horizon = $horizonMonth->setDate((int)$horizonMonth->format('Y'), (int)$horizonMonth->format('n'), min((int)$horizonBase->format('j'), (int)$horizonMonth->format('t')))->setTime(23, 59, 59);
                while ($series->get('active') && $remaining > 0) {
                    $position = (int)$series->get('next_position');
                    $date = $series->occurrenceDate($position);
                    if ($date > $horizon) { break; }
                    // Long downtime skips obsolete dates instead of flooding past meetings.
                    $occurrenceStart = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $series->getParameter('blueprint')['time'], $date->getTimezone());
                    if ($occurrenceStart > $now) {
                        $existing = new Event();
                        if (!$existing->load([['IDeventrecurrence', $series->getId()], ['recurrence_position', $position]])) {
                            $occurrence = new Event(); $series->applyBlueprint($occurrence, $position, true); $created++;
                            if (!(int)$series->get('IDreference_event')) { $series->set('IDreference_event', (int)$occurrence->getId()); }
                        }
                    }
                    $series->set('next_position', $position + 1); $remaining--;
                }
                self::requireSaved($series->save());
                if ($ownsTransaction) { $pdo->commit(); }
                $series->generatedDocuments = [];
            } catch (\Throwable $exception) {
                if ($series) { $series->discardGeneratedDocuments(); }
                if (!$ownsTransaction) { throw $exception; }
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $created = $createdBefore; $remaining = $remainingBefore;
                error_log('OMO meeting recurrence ' . (int)$row['id'] . ' failed: ' . $exception->getMessage());
                $failures[] = (int)$row['id'];
            }
        }
        if ($failures) { throw new \RuntimeException('Recurrences en echec : ' . implode(', ', $failures)); }
        return $created;
    }

    public static function getPendingInvitationEvents(int $limit = 200, ?int $seriesId = null): array
    {
        $events = [];
        foreach (self::fetchAll("SELECT * FROM event WHERE IDeventrecurrence IS NOT NULL AND active = 1
            AND status <> 'draft' AND JSON_EXTRACT(COALESCE(NULLIF(parameters, ''), '{}'), '$.recurrence_invitation_pending') = 1"
            . ($seriesId !== null ? ' AND IDeventrecurrence = :series' : '') . ' ORDER BY id LIMIT ' . max(1, min(1000, $limit)),
            $seriesId !== null ? ['series' => $seriesId] : []) ?: [] as $row) {
            $event = new Event(); $event->hydrateFromDatabaseRow($row, true); $events[] = $event;
        }
        return $events;
    }

    public static function finishPendingInvitation(Event $event): void
    {
        self::requireSaved(['status' => self::execute("UPDATE event SET parameters = JSON_REMOVE(parameters, '$.recurrence_invitation_pending')
            WHERE id = :id AND IDorganization = :organization", ['id' => $event->getId(), 'organization' => $event->get('IDorganization')])]);
    }

    /** Call under the series lock so generation cannot add meetings during deletion. */
    public function getDeletionEventsFrom(Event $event): array
    {
        if ((int)$event->get('IDeventrecurrence') !== (int)$this->getId()) {
            throw new \InvalidArgumentException('La reunion ne fait pas partie de cette serie.');
        }
        $events = [];
        foreach (self::fetchAll('SELECT * FROM event WHERE IDeventrecurrence = :series
            AND recurrence_position >= :position AND active = 1 AND end_at >= :now ORDER BY recurrence_position FOR UPDATE',
            ['series' => $this->getId(), 'position' => (int)$event->get('recurrence_position'), 'now' => new \DateTimeImmutable()]) ?: [] as $row) {
            $item = new Event(); $item->hydrateFromDatabaseRow($row, true); $events[] = $item;
        }
        return $events;
    }

    /** Neighbours follow actual dates, including moved/personalized occurrences. */
    public function getAdjacentEvents(Event $event, callable $canView): array
    {
        $result = ['previous' => null, 'next' => null];
        if ((int)$event->get('IDeventrecurrence') !== (int)$this->getId()) { return $result; }
        foreach (['previous' => ['<', 'DESC'], 'next' => ['>', 'ASC']] as $direction => [$operator, $order]) {
            $rows = self::fetchAll("SELECT * FROM event WHERE IDeventrecurrence = :series AND IDorganization = :organization
                AND active = 1 AND status <> 'cancelled'
                AND (start_at $operator :start OR (start_at = :same_start AND id $operator :event))
                ORDER BY start_at $order, id $order", ['series' => $this->getId(), 'organization' => $event->get('IDorganization'),
                    'start' => $event->get('start_at'), 'same_start' => $event->get('start_at'), 'event' => $event->getId()]);
            foreach ($rows ?: [] as $row) {
                $candidate = new Event(); $candidate->hydrateFromDatabaseRow($row, true);
                if ($canView($candidate)) { $result[$direction] = $candidate; break; }
            }
        }
        return $result;
    }

    public function hasFollowing(Event $event): bool
    {
        return (bool)self::fetchRow('SELECT id FROM event WHERE IDeventrecurrence = :series
            AND recurrence_position > :position AND active = 1 AND end_at >= :now LIMIT 1',
            ['series' => $this->getId(), 'position' => (int)$event->get('recurrence_position'), 'now' => new \DateTimeImmutable()]);
    }

    public function canManage(int $organizationId, int $userId, string $permission = 'CAN_EDIT_EVENT'): bool
    {
        if ($organizationId !== (int)$this->get('IDorganization') || $userId <= 0) { return false; }
        $holonId = (int)($this->getParameter('blueprint')['fields']['IDholon'] ?? 0);
        if ($holonId > 0) {
            $holon = new Holon();
            return $holon->load($holonId) && $holon->isAllowed($permission, false, $userId);
        }
        $organization = new Organization();
        return $organization->load($organizationId) && $organization->getMembership($userId, true) !== null;
    }

    /** The close action owns the transaction. Repeating it never creates a second successor. */
    public function scheduleNext(Event $closedEvent, \DateTimeImmutable $start): int
    {
        $this->lock(); $this->repairLegacyDocumentTitles(); $closedEvent->load((int)$closedEvent->getId(), true);
        $existingId = (int)$closedEvent->getParameter('next_meeting_id');
        if ($existingId > 0) { return $existingId; }
        self::validateDocumentTypes(array_map(static fn($document) => $document->getDocumentType(),
            array_filter($closedEvent->getAssociatedDocuments(), static fn($document) => !$document->isArchived())),
            'on_close', $this->getDocumentMode());
        if (!$this->get('active') || $this->get('frequency') !== 'on_close'
            || $closedEvent->get('recurrence_exception')
            || (int)$closedEvent->get('IDeventrecurrence') !== (int)$this->getId()
            || $start <= new \DateTimeImmutable() || $start <= $closedEvent->get('start_at')) {
            throw new \InvalidArgumentException('Choisissez une prochaine date dans le futur.');
        }
        // Only the last meeting may extend an on-close chain, even if older links were deleted.
        $later = self::fetchRow('SELECT id FROM event WHERE IDeventrecurrence = :series AND recurrence_position > :position AND active = 1 LIMIT 1',
            ['series' => $this->getId(), 'position' => (int)$closedEvent->get('recurrence_position')]);
        if ($later) { throw new \InvalidArgumentException('Une prochaine reunion existe deja dans cette serie.'); }
        $position = (int)$this->get('next_position'); $next = new Event();
        try {
            $this->applyBlueprint($next, $position, true, $start->setTimezone(new \DateTimeZone($this->get('timezone'))));
            if (!(int)$this->get('IDreference_event')) { $this->set('IDreference_event', (int)$next->getId()); }
            $this->set('next_position', $position + 1); self::requireSaved($this->save());
            if (!self::execute("UPDATE event SET parameters = JSON_SET(COALESCE(NULLIF(parameters, ''), '{}'), '$.next_meeting_id', :next_id) WHERE id = :id",
                ['next_id' => (int)$next->getId(), 'id' => (int)$closedEvent->getId()])) {
                throw new \RuntimeException('Impossible de lier la prochaine reunion.');
            }
            $closedEvent->load((int)$closedEvent->getId(), true);
            $this->generatedDocuments = [];
            return (int)$next->getId();
        } catch (\Throwable $exception) {
            $this->discardGeneratedDocuments();
            throw $exception;
        }
    }

    /** Called before deleting a source; the frozen blueprint remains valid even with no successor. */
    public static function transferReference(Event $event): void
    {
        $series = $event->getRecurrence();
        if (!$series || (int)$series->get('IDreference_event') !== (int)$event->getId()) { return; }
        $series->lock();
        if ((int)$series->get('IDreference_event') !== (int)$event->getId()) { return; }
        $row = self::fetchRow('SELECT id FROM event WHERE IDeventrecurrence = :series AND id <> :excluded AND active = 1 AND recurrence_exception = 0 AND start_at > :now ORDER BY start_at, id LIMIT 1',
            ['series' => $series->getId(), 'excluded' => $event->getId(), 'now' => new \DateTimeImmutable()]);
        $series->set('IDreference_event', $row ? (int)$row['id'] : null);
        if ($row) {
            $next = new Event(); $next->load((int)$row['id'], true);
            $blueprint = $series->getParameter('blueprint');
            $nextDocuments = $next->getAssociatedDocuments();
            foreach ($blueprint['documents'] as $index => &$document) {
                if (!empty($document['shared'])) { continue; }
                if (isset($nextDocuments[$index])) { $document['source_id'] = (int)$nextDocuments[$index]->getId(); }
            }
            unset($document);
            $parameters = json_decode((string)$series->get('parameters'), true) ?: []; $parameters['blueprint'] = $blueprint;
            $series->set('parameters', $parameters);
        }
        self::requireSaved($series->save());
    }
}
