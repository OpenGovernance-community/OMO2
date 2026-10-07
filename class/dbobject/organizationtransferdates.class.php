<?php
namespace dbObject;

/** Shift typed dates together; export metadata and free text keep their contents. */
class OrganizationTransferDates
{
    private const FIELDS = [
        'createdAt', 'updatedAt', 'archivedAt', 'closedAt', 'openedAt', 'statusAt',
        'lastConnectionAt', 'plannedStartAt', 'plannedEndAt', 'blockedUntil',
        'proposedAt', 'proposalDecidedAt', 'activationAt', 'activatedAt', 'completedAt',
        'scheduledFor', 'checkedAt', 'measuredAt', 'startAt', 'endAt', 'scheduledAt',
        'assignedAt', 'assignmentReviewDate', 'pointAt', 'deletedAt', 'reviewDate', 'expirationDate',
    ];

    public static function shift(array $payload, ?\DateTimeImmutable $today = null): array
    {
        $exportedAt = $payload['exportedAt'] ?? null;
        if (!is_string($exportedAt) || !preg_match('/^\d{4}-\d{2}-\d{2}(?:T.*)?$/D', $exportedAt)) {
            throw new \RuntimeException('Le mode jeu de role exige une date d export valide dans le fichier.');
        }
        try { $exportDate = new \DateTimeImmutable($exportedAt); }
        catch (\Throwable $e) { throw new \RuntimeException('Date d export invalide.'); }
        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) { throw new \RuntimeException('Date d export invalide.'); }
        $today ??= new \DateTimeImmutable('today');
        $zone = new \DateTimeZone('UTC');
        $from = new \DateTimeImmutable($exportDate->format('Y-m-d'), $zone);
        $to = new \DateTimeImmutable($today->format('Y-m-d'), $zone);
        $days = (int)$from->diff($to)->format('%r%a');
        $dateProperties = [];
        foreach ($payload['propertyDefinitions'] ?? [] as $definition) {
            if ((int)($definition['formatId'] ?? 0) === PropertyFormat::FORMAT_DATE) { $dateProperties[(int)$definition['id']] = true; }
        }
        self::walk($payload, $days, $dateProperties);
        return $payload;
    }

    private static function walk(array &$node, int $days, array $dateProperties): void
    {
        foreach ($node as $key => &$value) {
            if (in_array($key, ['parameters', 'propertyValues', 'propertyDefinitions', 'source', 'media'], true)) { continue; }
            if (is_array($value)) { self::walk($value, $days, $dateProperties); }
            elseif (is_string($value) && $value !== '' && (in_array($key, self::FIELDS, true)
                || ($key === 'value' && isset($dateProperties[(int)($node['propertyId'] ?? 0)])))) {
                if (!preg_match('/^(\d{4}-\d{2}-\d{2})((?:T| ).*)?$/D', $value, $match)) {
                    throw new \RuntimeException('Date invalide pour '.$key.'.');
                }
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $match[1], new \DateTimeZone('UTC'));
                if (!$date || $date->format('Y-m-d') !== $match[1]) { throw new \RuntimeException('Date invalide pour '.$key.'.'); }
                // Calendar days, rather than seconds, preserve dates across DST transitions.
                $value = $date->modify(($days >= 0 ? '+' : '').$days.' days')->format('Y-m-d').($match[2] ?? '');
            }
        }
        unset($value);
    }
}
