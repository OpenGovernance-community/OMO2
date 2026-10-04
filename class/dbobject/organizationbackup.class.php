<?php
namespace dbObject;

class OrganizationBackup extends DbObject
{
    public const FREQUENCIES = ['1w', '2w', '1m', '2m', '3m', '5m', '8m', '12m'];

    public static function tableName() { return 'organization_backup'; }

    public static function rules()
    {
        return [
            [['id'], 'integer'],
            [['IDorganization'], 'fk'],
            [['enabled'], 'boolean'],
            [['email', 'frequency'], 'string'],
            [['last_sent_at', 'last_attempt_at'], 'datetime'],
            [['id', 'last_sent_at', 'last_attempt_at'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return ['enabled' => 'Activer la sauvegarde automatique', 'email' => 'Adresse e-mail complémentaire (facultative)', 'frequency' => 'Fréquence'];
    }

    public static function attributeLength() { return ['email' => 254, 'frequency' => 3]; }

    public static function attributeValues()
    {
        return ['frequency' => [
            ['1w', '1 semaine'], ['2w', '2 semaines'], ['1m', '1 mois'], ['2m', '2 mois'],
            ['3m', '3 mois'], ['5m', '5 mois'], ['8m', '8 mois'], ['12m', '12 mois'],
        ]];
    }

    public static function forOrganization(int $organizationId): self
    {
        $backup = new self();
        if (!$backup->load(['IDorganization', $organizationId])) {
            $backup->set('IDorganization', $organizationId);
            $backup->set('enabled', 0);
            $backup->set('email', '');
            $backup->set('frequency', '1m');
        }
        return $backup;
    }

    public function canEdit()
    {
        $id = (int)$this->get('IDorganization');
        return function_exists('commonCurrentUserIsSiteAdminModeEnabled')
            && (\commonCurrentUserIsSiteAdminModeEnabled()
                || (\commonCurrentUserCanUseAdminMode($id) && \commonCurrentUserIsAdminModeEnabled($id)));
    }

    public function canView() { return $this->canEdit(); }
    public function canViewDetail() { return $this->canEdit(); }
    public function canDelete() { return $this->canEdit(); }

    public function save()
    {
        if (!in_array($this->get('frequency'), self::FREQUENCIES, true)
            || ((string)$this->get('email') !== '' && !filter_var($this->get('email'), FILTER_VALIDATE_EMAIL))) {
            return ['status' => false];
        }
        if ((int)$this->getId() <= 0) { return parent::save(); }
        // Configuration changes must not overwrite a cron's delivery timestamps.
        return ['status' => self::execute(
            'UPDATE organization_backup SET enabled = :enabled, email = :email, frequency = :frequency WHERE id = :id',
            ['enabled' => (int)$this->get('enabled'), 'email' => $this->get('email'), 'frequency' => $this->get('frequency'), 'id' => (int)$this->getId()]
        )];
    }

    public function nextDueAt(): ?\DateTimeImmutable
    {
        $last = $this->get('last_sent_at');
        if (!$last) { return null; }
        $last = $last instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($last) : new \DateTimeImmutable((string)$last);
        $frequency = (string)$this->get('frequency');
        if (!in_array($frequency, self::FREQUENCIES, true)) { throw new \RuntimeException('Invalid backup frequency.'); }
        $count = (int)$frequency;
        if (str_ends_with($frequency, 'w')) { return $last->modify('+' . $count . ' weeks'); }
        // Clamp the day to the destination month's end (January 31 -> February 28).
        $month = $last->modify('first day of this month')->modify('+' . $count . ' months');
        return $month->setDate((int)$month->format('Y'), (int)$month->format('m'), min((int)$last->format('d'), (int)$month->format('t')));
    }

    public function isDue(\DateTimeImmutable $now): bool
    {
        if (!(bool)$this->get('enabled')) { return false; }
        $due = $this->nextDueAt();
        $attempt = $this->get('last_attempt_at');
        if ($attempt) {
            $attempt = $attempt instanceof \DateTimeInterface ? $attempt : new \DateTimeImmutable((string)$attempt);
            if ($now->getTimestamp() - $attempt->getTimestamp() < 3600) { return false; }
        }
        return $due === null || $due <= $now;
    }

    public function recordAttempt(\DateTimeImmutable $now, bool $success = false): void
    {
        $sql = $success
            ? 'UPDATE organization_backup SET last_sent_at = :sent WHERE id = :id'
            : 'UPDATE organization_backup SET last_attempt_at = :sent WHERE id = :id';
        if (!self::execute($sql, ['sent' => $now->format('Y-m-d H:i:s'), 'id' => (int)$this->getId()])) {
            throw new \RuntimeException('Unable to record backup delivery state.');
        }
        $this->set($success ? 'last_sent_at' : 'last_attempt_at', $now);
    }

    public function getRecipients(): array
    {
        $memberships = new ArrayUserOrganization();
        $memberships->load(['where' => [
            ['field' => 'IDorganization', 'value' => (int)$this->get('IDorganization')],
            ['field' => 'active', 'value' => 1],
        ]]);
        $emails = [];
        foreach ($memberships as $membership) {
            if ($membership->isOrganizationAdmin()) { $emails[] = $membership->getScopedEmail(); }
        }
        $emails[] = $this->get('email');
        $valid = [];
        foreach ($emails as $email) {
            $email = trim((string)$email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) { $valid[strtolower($email)] = $email; }
        }
        return array_values($valid);
    }

    /** Browser fallback is scoped to the current user's organizations; server cron covers all. */
    public static function loadEnabledForCron(int $userId, bool $serverCron): array
    {
        $sql = 'SELECT b.* FROM organization_backup b WHERE b.enabled = 1';
        $params = [];
        if (!$serverCron) {
            if ($userId <= 0) { return []; }
            $sql .= ' AND EXISTS (SELECT 1 FROM user_organization uo WHERE uo.IDorganization = b.IDorganization AND uo.IDuser = :user AND uo.active = 1)';
            $params['user'] = $userId;
        }
        $rows = self::fetchAll($sql . ' ORDER BY b.id', $params);
        if ($rows === false) { throw new \RuntimeException('Unable to load organization backups.'); }
        $backups = [];
        foreach ($rows as $row) {
            $backup = new self();
            $backup->hydrateFromDatabaseRow($row, true);
            $backups[] = $backup;
        }
        return $backups;
    }
}
