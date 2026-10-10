<?php
namespace dbObject;

class Event extends DbObject
{
    use CalendarTimeBuffer;
    const STATUS_DRAFT = 'draft';
    const STATUS_OPTION = 'option';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const LOCATION_MODE_IN_PERSON = 'in_person';
    const LOCATION_MODE_VIRTUAL = 'virtual';
    const LOCATION_MODE_HYBRID = 'hybrid';

    public static function tableName()
    {
        return 'event';
    }

    public static function rules()
    {
        return [
            [['IDuser', 'title', 'status', 'start_at', 'end_at'], 'required'],
            [['id', 'recurrence_position'], 'integer'],
            [['IDorganization', 'IDholon', 'IDproject', 'IDuser', 'IDdecision_proposal', 'IDeventrecurrence'], 'fk'],
            [['title', 'status', 'timezone', 'locationmode', 'locationaddress', 'videomeetingurl'], 'string'],
            [['description'], 'text'],
            [['parameters'], 'parameters'],
            [['is_all_day', 'active', 'recurrence_exception'], 'boolean'],
            [['start_at', 'end_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Espace associé',
            'IDproject' => 'Projet',
            'IDdecision_proposal' => 'Proposition de date',
            'IDeventrecurrence' => 'Serie de reunions',
            'recurrence_position' => 'Position dans la serie',
            'recurrence_exception' => 'Occurrence personnalisee',
            'IDuser' => 'Créateur',
            'title' => 'Titre',
            'description' => 'Description',
            'status' => 'Statut',
            'timezone' => 'Fuseau horaire',
            'locationmode' => 'Format du lieu',
            'locationaddress' => 'Adresse',
            'videomeetingurl' => 'Lien de visio',
            'start_at' => 'Début',
            'end_at' => 'Fin',
            'is_all_day' => 'Journée entière',
            'parameters' => 'Paramètres',
            'active' => 'Actif',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => "Espace optionnel pour rattacher l'événement.",
            'IDproject' => "Projet optionnel auquel l'événement est associé.",
            'IDuser' => "Utilisateur qui a créé l'événement.",
            'status' => "Cycle de vie simple avant l'ajout des invitations et réponses.",
            'timezone' => "Fuseau horaire de référence pour l'export agenda.",
            'locationmode' => 'Permet de préciser si la rencontre est en présentiel, en visio ou mixte.',
            'locationaddress' => 'Adresse libre pour les rencontres en présentiel ou mixtes.',
            'videomeetingurl' => 'URL http ou https de la salle de réunion virtuelle.',
            'is_all_day' => "Indique si l'événement doit être interprété comme une journée complète.",
            'parameters' => 'Réservé pour les invitations, métadonnées et options futures.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 190,
            'status' => 20,
            'timezone' => 64,
            'locationmode' => 20,
            'locationaddress' => 1000,
            'videomeetingurl' => 2000,
        ];
    }

    public static function getOrder()
    {
        return 'start_at ASC, id ASC';
    }

    public function getRecurrence(): ?EventRecurrence
    {
        $series = new EventRecurrence();
        return (int)$this->get('IDeventrecurrence') > 0 && $series->load((int)$this->get('IDeventrecurrence')) ? $series : null;
    }

    public function isPastRecurringMeeting(): bool
    {
        return (int)$this->get('IDeventrecurrence') > 0 && $this->get('end_at') instanceof \DateTimeInterface
            && $this->get('end_at') < new \DateTimeImmutable();
    }

    /** Snapshot identities separately from presence, which remains editable during the meeting. */
    public function freezeInvitationParticipants(): bool
    {
        if ($this->getParameter('meeting_participants') !== null) { return true; }
        $snapshot = ['targets' => $this->getEffectiveInvitationTargets((int)$this->get('IDorganization'), fresh: true, activeOnly: true),
            'entries' => $this->getAttendanceEntries((int)$this->get('IDorganization'))];
        // Preserve concurrent metadata; only install the snapshot once.
        $saved = self::execute("UPDATE event SET parameters = JSON_SET(COALESCE(NULLIF(parameters, ''), '{}'), '$.meeting_participants', JSON_EXTRACT(:snapshot, '$'))
            WHERE id = :id AND JSON_EXTRACT(COALESCE(NULLIF(parameters, ''), '{}'), '$.meeting_participants') IS NULL",
            ['id' => $this->getId(), 'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
        if ($saved) { $this->load((int)$this->getId(), true); }
        return (bool)$saved;
    }

    public static function freezePastMeetingParticipantsBatch(int $limit = 200): int
    {
        $count = 0;
        foreach (self::fetchAll("SELECT * FROM event WHERE IDeventrecurrence IS NOT NULL AND active = 1 AND end_at <= :now
            AND JSON_EXTRACT(COALESCE(NULLIF(parameters, ''), '{}'), '$.meeting_participants') IS NULL ORDER BY start_at DESC LIMIT " . max(1, $limit),
            ['now' => new \DateTimeImmutable()]) ?: [] as $row) {
            $event = new self(); $event->hydrateFromDatabaseRow($row, true);
            if ($event->freezeInvitationParticipants()) { $count++; }
        }
        return $count;
    }

    public static function findByDecisionProposal(int $proposalId): ?self
    {
        $row = self::fetchRow('SELECT * FROM event WHERE IDdecision_proposal = :id', ['id' => $proposalId]);
        if (!$row) return null;
        $event = new self();
        $event->hydrateFromDatabaseRow($row, true);
        return $event;
    }

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        $organizationId = (int)$organizationId;
        $result = self::execute("UPDATE event SET IDuser = CASE WHEN end_at < NOW() THEN :ghost_user_id ELSE NULL END WHERE IDorganization = :organization_id AND IDuser = :user_id", array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => $organizationId, 'user_id' => (int)$userId));
        if ($result) {
            CalDavCache::invalidateOrganization($organizationId);
        }

        return $result;
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_DRAFT => [
                'label' => 'Brouillon',
                'description' => "L'événement est encore en préparation.",
            ],
            self::STATUS_OPTION => [
                'label' => 'Option',
                'description' => 'Date envisageable mais pas encore confirmee.',
            ],
            self::STATUS_CONFIRMED => [
                'label' => 'Confirmé',
                'description' => "L'événement est planifié et prêt à être diffusé.",
            ],
            self::STATUS_CANCELLED => [
                'label' => 'Annulé',
                'description' => "L'événement est conservé mais ne doit plus être actif.",
            ],
        ];
    }

    public static function getLocationModeCatalog()
    {
        return [
            self::LOCATION_MODE_IN_PERSON => [
                'label' => 'Présentiel',
                'description' => 'La rencontre se passe sur place avec une adresse.',
            ],
            self::LOCATION_MODE_VIRTUAL => [
                'label' => 'Virtuel',
                'description' => 'La rencontre se passe uniquement en visio.',
            ],
            self::LOCATION_MODE_HYBRID => [
                'label' => 'Mixte',
                'description' => 'La rencontre combine un lieu physique et une visio.',
            ],
        ];
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_DRAFT;
    }

    public static function normalizeLocationMode($locationMode)
    {
        $locationMode = trim(mb_strtolower((string)$locationMode, 'UTF-8'));

        return array_key_exists($locationMode, self::getLocationModeCatalog())
            ? $locationMode
            : '';
    }

    public static function sanitizeVideoMeetingUrl($value): string
    {
        $value = trim((string)$value);
        if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = @parse_url($value);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $value;
    }

    protected static function inferLocationModeFromFields(string $locationAddress, string $videoMeetingUrl): string
    {
        if ($locationAddress !== '' && $videoMeetingUrl !== '') {
            return self::LOCATION_MODE_HYBRID;
        }

        if ($locationAddress !== '') {
            return self::LOCATION_MODE_IN_PERSON;
        }

        if ($videoMeetingUrl !== '') {
            return self::LOCATION_MODE_VIRTUAL;
        }

        return '';
    }

    public static function resolveLinkedDocumentVisibilityTypeForHolonId($holonId): string
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return \dbObject\ObjectVisibility::TYPE_ORGANIZATION;
        }

        $holon = new \dbObject\Holon();
        if (!$holon->load($holonId)) {
            return \dbObject\ObjectVisibility::TYPE_ORGANIZATION;
        }

        if ((int)$holon->get('IDtypeholon') === 1) {
            return \dbObject\ObjectVisibility::TYPE_ROLE;
        }

        if ((int)$holon->get('IDtypeholon') === 2) {
            return \dbObject\ObjectVisibility::TYPE_CIRCLE;
        }

        return \dbObject\ObjectVisibility::TYPE_ORGANIZATION;
    }

    public function getLocationModeLabel(): string
    {
        $catalog = self::getLocationModeCatalog();
        $locationMode = self::normalizeLocationMode($this->get('locationmode'));

        return trim((string)($catalog[$locationMode]['label'] ?? ''));
    }

    public function getResolvedLocationAddress(): string
    {
        return trim((string)$this->get('locationaddress'));
    }

    public function getResolvedVideoMeetingUrl(): string
    {
        return self::sanitizeVideoMeetingUrl($this->get('videomeetingurl'));
    }

    public function getLocationDisplayData(): array
    {
        return [
            'mode' => self::normalizeLocationMode($this->get('locationmode')),
            'modeLabel' => $this->getLocationModeLabel(),
            'address' => $this->getResolvedLocationAddress(),
            'videoUrl' => $this->getResolvedVideoMeetingUrl(),
        ];
    }

    public function getAssociatedDocuments(): array
    {
        $documents = new \dbObject\ArrayDocument();
        if ((int)$this->getId() <= 0) {
            return array();
        }

        $documents->load(array(
            'where' => array(
                array('field' => 'IDevent', 'value' => (int)$this->getId()),
            ),
            'orderBy' => array(
                array('field' => 'id', 'dir' => 'ASC'),
            ),
        ));

        $owned = array_values(array_filter($documents->getArrayCopy(), static function ($document) {
            return $document instanceof \dbObject\Document && (int)$document->getId() > 0;
        }));
        $shared = EventSharedDocument::documentsByEvent([(int)$this->getId()], (int)$this->get('IDorganization'));
        return array_merge($owned, $shared[(int)$this->getId()] ?? []);
    }

    public function getProject()
    {
        $projectId = (int)$this->get('IDproject');
        if ($projectId <= 0) {
            return null;
        }

        $project = new \dbObject\Project();
        return $project->load($projectId) ? $project : null;
    }

    public function getInvitations($activeOnly = false)
    {
        $items = new \dbObject\ArrayEventInvitation();
        $params = [
            'where' => [
                ['field' => 'resource_type', 'value' => \dbObject\EventInvitation::resourceType()],
                ['field' => 'resource_id', 'value' => (int)$this->getId()],
            ],
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if ($activeOnly) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
        }

        $items->load($params);
        return $items;
    }

    public function hasExplicitInvitations(): bool
    {
        foreach ($this->getInvitations(true) as $invitation) {
            if (
                $invitation instanceof \dbObject\EventInvitation
                && \dbObject\EventInvitation::normalizeStatus($invitation->get('status')) !== \dbObject\EventInvitation::STATUS_REVOKED
            ) {
                return true;
            }
        }

        return false;
    }

    protected static function normalizeInvitationEmail($email): string
    {
        $email = trim(mb_strtolower((string)$email, 'UTF-8'));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    protected function getInvitationMembershipUserIds($holonId, $organizationId, bool $fresh = false, bool $activeOnly = false): array
    {
        static $membershipCache = [];

        $holonId = (int)$holonId;
        $organizationId = (int)$organizationId;
        if ($holonId <= 0 || $organizationId <= 0) {
            return [];
        }

        $cacheKey = $organizationId . ':' . $holonId . ':' . (int)$activeOnly;
        if (!$fresh && isset($membershipCache[$cacheKey])) {
            return $membershipCache[$cacheKey];
        }

        $holon = new \dbObject\Holon();
        $organization = new \dbObject\Organization();
        if (
            !$holon->load($holonId, $fresh)
            || !$organization->load($organizationId)
            || !$organization->containsHolon($holon)
            || !(bool)$holon->get('active')
            || !(bool)$holon->get('visible')
        ) {
            $membershipCache[$cacheKey] = [];
            return $membershipCache[$cacheKey];
        }

        if ($activeOnly && $holon->isOrganizationHolon()) {
            $memberships = new ArrayUserOrganization(); $memberships->loadActiveForOrganization($organizationId);
            $userIds = array_map(static fn($membership) => (int)$membership->get('IDuser'), $memberships->getArrayCopy());
        } else {
            $userIds = $holon->getAssociatedMemberUserIds([
                'organizationId' => $organizationId,
                'skipPermissionFilter' => true,
                'activeOnly' => $activeOnly,
            ]);
        }

        $membershipCache[$cacheKey] = array_values(array_unique(array_map('intval', is_array($userIds) ? $userIds : [])));
        return $membershipCache[$cacheKey];
    }

    protected function organizationHasStructureApplication($organizationId): bool
    {
        static $cache = [];

        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return false;
        }

        if (array_key_exists($organizationId, $cache)) {
            return $cache[$organizationId];
        }

        $organization = new \dbObject\Organization();
        $applicationUserId = (int)$this->get('IDuser');
        $cache[$organizationId] = $organization->load($organizationId)
            && $organization->isStructureApplicationEnabled($applicationUserId > 0 ? $applicationUserId : null);
        return $cache[$organizationId];
    }

    protected function getOrganizationMemberUserIds($organizationId): array
    {
        static $cache = [];

        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return [];
        }

        if (isset($cache[$organizationId])) {
            return $cache[$organizationId];
        }

        $memberships = new \dbObject\ArrayUserOrganization();
        $memberships->loadActiveForOrganization($organizationId);
        $userIds = [];
        foreach ($memberships as $membership) {
            $userId = (int)$membership->get('IDuser');
            if ($userId > 0) {
                $userIds[$userId] = $userId;
            }
        }

        $cache[$organizationId] = array_values($userIds);
        return $cache[$organizationId];
    }

    protected function getViewerScopedEmail($userId, $organizationId): string
    {
        static $emailCache = [];

        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        if ($userId <= 0 || $organizationId <= 0) {
            return '';
        }

        $cacheKey = $organizationId . ':' . $userId;
        if (isset($emailCache[$cacheKey])) {
            return $emailCache[$cacheKey];
        }

        $user = new \dbObject\User();
        if (!$user->load($userId)) {
            $emailCache[$cacheKey] = '';
            return $emailCache[$cacheKey];
        }

        $emailCache[$cacheKey] = self::normalizeInvitationEmail($user->getScopedEmail($organizationId));
        return $emailCache[$cacheKey];
    }

    public function getCreatedByDisplayName(): string
    {
        return $this->getViewerDisplayName((int)$this->get('IDuser'), (int)$this->get('IDorganization'));
    }

    protected function getViewerDisplayName($userId, $organizationId): string
    {
        static $displayNameCache = [];

        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        if ($userId <= 0 || $organizationId <= 0) {
            return '';
        }

        $cacheKey = $organizationId . ':' . $userId;
        if (isset($displayNameCache[$cacheKey])) {
            return $displayNameCache[$cacheKey];
        }

        $link = new \dbObject\UserHolon();
        $link->set('IDuser', $userId);
        $displayNameCache[$cacheKey] = trim((string)$link->getUserDisplayName($organizationId));
        return $displayNameCache[$cacheKey];
    }

    /** Fresh bypasses membership caches; explicitOnly excludes implicit organization/context recipients. */
    public function getEffectiveInvitationTargets($organizationId, ?array $proposedInvitations = null, bool $fresh = false, bool $activeOnly = false, bool $explicitOnly = false): array
    {
        $snapshot = $this->getParameter('meeting_participants');
        if ($proposedInvitations === null && is_array($snapshot)) {
            $targets = $snapshot['targets'];
            if (!$explicitOnly || !empty($targets['hasExplicitInvitations'])) { return $targets; }
            return ['hasExplicitInvitations' => false, 'userIds' => [], 'emails' => [], 'registeredEmails' => []];
        }
        // Upcoming occurrences follow current memberships; closed ones use their snapshot above.
        $activeOnly = $activeOnly || (int)$this->get('IDeventrecurrence') > 0;
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            $organizationId = (int)$this->get('IDorganization');
        }

        $targets = [
            'hasExplicitInvitations' => (int)$this->get('IDdecision_proposal') > 0,
            'userIds' => [],
            'emails' => [],
            'registeredEmails' => [],
        ];

        foreach ($proposedInvitations ?? $this->getInvitations(true) as $invitation) {
            if (!($invitation instanceof \dbObject\EventInvitation)) {
                continue;
            }

            if (\dbObject\EventInvitation::normalizeStatus($invitation->get('status')) === \dbObject\EventInvitation::STATUS_REVOKED) {
                continue;
            }

            $targets['hasExplicitInvitations'] = true;
            $type = \dbObject\EventInvitation::normalizeType($invitation->get('invitation_type'));

            if ($type === \dbObject\EventInvitation::TYPE_USER) {
                $userId = (int)$invitation->get('IDuser');
                if ($userId > 0) {
                    $targets['userIds'][$userId] = $userId;
                }
                continue;
            }

            if ($type === \dbObject\EventInvitation::TYPE_EMAIL) {
                $email = self::normalizeInvitationEmail($invitation->get('email'));
                if ($email !== '') {
                    $targets['emails'][$email] = $email;
                }
                continue;
            }

            if ($type === \dbObject\EventInvitation::TYPE_HOLON) {
                foreach ($this->getInvitationMembershipUserIds((int)$invitation->get('IDholon'), $organizationId, $fresh, $activeOnly) as $userId) {
                    if ($userId > 0) {
                        $targets['userIds'][$userId] = (int)$userId;
                    }
                }
            }
        }

        // Public registrations supplement the invitation scope; they never replace its defaults.
        if (!$explicitOnly && !$targets['hasExplicitInvitations']) {
            $eventHolonId = (int)$this->get('IDholon');
            if ($eventHolonId > 0) {
                foreach ($this->getInvitationMembershipUserIds($eventHolonId, $organizationId, $fresh, $activeOnly) as $userId) {
                    if ($userId > 0) {
                        $targets['userIds'][$userId] = (int)$userId;
                    }
                }
            } elseif (!$this->organizationHasStructureApplication($organizationId)) {
                $targets['userIds'] = $this->getOrganizationMemberUserIds($organizationId);
            }
        }

        foreach (EventPublicRegistration::forEvent((int)$this->getId(), true) as $registration) {
            $email = self::normalizeInvitationEmail($registration->get('email'));
            if ($email !== '') {
                $targets['emails'][$email] = $email;
                $targets['registeredEmails'][$email] = trim((string)$registration->get('name'));
            }
        }
        $targets['userIds'] = array_values($targets['userIds']);
        $targets['emails'] = array_values($targets['emails']);
        return $targets;
    }

    /** Count people once across invited spaces, individual members and public registrations. */
    public function getInvitationCounts(?array $invitations = null): array
    {
        $invitations ??= $this->getInvitations(true)->getArrayCopy();
        $invitations = array_values(array_filter($invitations, static fn($invitation) =>
            $invitation instanceof EventInvitation && (int)$invitation->get('active') === 1
            && EventInvitation::normalizeStatus($invitation->get('status')) !== EventInvitation::STATUS_REVOKED));
        $individualUserIds = [];
        $holonIds = [];
        $invitedEmails = [];
        foreach ($invitations as $invitation) {
            if ($invitation->get('invitation_type') === EventInvitation::TYPE_USER && (int)$invitation->get('IDuser') > 0) {
                $individualUserIds[(int)$invitation->get('IDuser')] = true;
            } elseif ($invitation->get('invitation_type') === EventInvitation::TYPE_HOLON && (int)$invitation->get('IDholon') > 0) {
                $holonIds[(int)$invitation->get('IDholon')] = true;
            } elseif ($invitation->get('invitation_type') === EventInvitation::TYPE_EMAIL) {
                $email = self::normalizeInvitationEmail($invitation->get('email'));
                if ($email !== '') { $invitedEmails[$email] = true; }
            }
        }
        $organizationId = (int)$this->get('IDorganization');
        $targets = $this->getEffectiveInvitationTargets($organizationId, $invitations);
        $holonMemberCounts = [];
        foreach (array_keys($holonIds) as $holonId) {
            $holonMemberCounts[$holonId] = count($this->getInvitationMembershipUserIds($holonId, $organizationId));
        }
        $userIds = array_values(array_unique(array_map('intval', $targets['userIds'])));
        $emails = array_fill_keys($targets['emails'], true);
        foreach ($userIds as $userId) {
            unset($emails[$this->getViewerScopedEmail($userId, $organizationId)]);
        }
        return [
            'total' => count($userIds) + count($emails),
            'holonMemberCounts' => $holonMemberCounts,
            'members' => count($userIds),
            'individualMembers' => count($individualUserIds),
            'invitedEmails' => count($invitedEmails),
            'confirmedRegistrations' => count($targets['registeredEmails']),
        ];
    }

    /** Unsaved personal choices used while reviewing a proposed schedule. */
    private array $proposedTimeBuffers = [];

    public function getTimeBuffers(int $userId): array
    {
        if (isset($this->proposedTimeBuffers[$userId])) { return $this->proposedTimeBuffers[$userId]; }
        if ($userId <= 0 || (int)$this->getId() <= 0) { return [0, 0]; }
        $item = EventTimeBuffer::forUser((int)$this->getId(), $userId);
        return [(int)$item->get('preparation_minutes'), (int)$item->get('closing_minutes')];
    }

    public function setTimeBuffers(int $userId, $preparation, $closing): void
    {
        $this->proposedTimeBuffers[$userId] = [self::validateBufferMinutes($preparation), self::validateBufferMinutes($closing)];
    }

    public function withTimeBuffers(\DateTimeInterface $start, \DateTimeInterface $end, int $userId = 0): array
    {
        [$preparation, $closing] = $this->getTimeBuffers($userId);
        return [\DateTimeImmutable::createFromInterface($start)->modify('-' . $preparation . ' minutes'),
            \DateTimeImmutable::createFromInterface($end)->modify('+' . $closing . ' minutes')];
    }

    public function canEditTimeBuffers(int $userId): bool
    {
        if ($userId <= 0 || (int)$this->getId() <= 0 || !$this->get('active')
            || self::normalizeStatus($this->get('status')) === self::STATUS_CANCELLED) { return false; }
        $member = new UserOrganization();
        return $member->load([['IDorganization', (int)$this->get('IDorganization')], ['IDuser', $userId], ['active', 1]])
            && $this->isInvitedToEvent($userId);
    }

    public function saveTimeBuffers(int $userId, $preparation, $closing): bool
    {
        if (!$this->canEditTimeBuffers($userId)) { return false; }
        $item = EventTimeBuffer::forUser((int)$this->getId(), $userId);
        $item->set('preparation_minutes', self::validateBufferMinutes($preparation));
        $item->set('closing_minutes', self::validateBufferMinutes($closing));
        $result = $item->save();
        if (!is_array($result) || empty($result['status'])) { return false; }
        unset($this->proposedTimeBuffers[$userId]);
        return true;
    }

    /** Calendar intervals have an exclusive end; no personal time without a viewer. */
    public function getBusyInterval(int $userId = 0): ?array
    {
        $start = $this->get('start_at');
        $end = $this->get('end_at');
        if (!$start instanceof \DateTimeInterface) { return null; }
        $start = \DateTimeImmutable::createFromInterface($start);
        $end = $end instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($end) : $start->modify('+1 hour');
        if ($this->get('is_all_day')) {
            $start = $start->setTime(0, 0);
            $end = $end->setTime(0, 0)->modify('+1 day');
        }
        if ($end < $start) { return null; }
        [$start, $end] = $this->withTimeBuffers($start, $end, $userId);
        return $end > $start ? [$start, $end] : null;
    }

    /** The caller must validate the proposed invitations against the current organization. */
    public function checkInvitationAvailability(array $proposedInvitations, ?callable $refreshUserCalendars = null): array
    {
        $organizationId = (int)$this->get('IDorganization');
        $targets = $this->getEffectiveInvitationTargets($organizationId, $proposedInvitations, explicitOnly: true);
        $report = ['conflicts' => [], 'unverified' => [], 'externalCache' => false];
        // Email-only invitations have no calendar to check.
        $userIds = $targets['userIds'];
        $organizationLabels = [];
        $holonLabels = [];
        foreach ($userIds as $userId) {
            if ($userId <= 0) { continue; }
            $interval = $this->getBusyInterval($userId);
            if ($interval === null) { continue; }
            [$start, $end] = $interval;
            $name = $this->getViewerDisplayName($userId, $organizationId);
            $intervals = [];
            try {
                $events = new ArrayEvent();
                $events->loadBusyForUserDateRange($userId, $start, $end);
                foreach ($events as $event) {
                    if ((int)$this->getId() > 0 && (int)$event->getId() === (int)$this->getId()) { continue; }
                    $busy = $event->getBusyInterval($userId);
                    if ($busy === null || $busy[0] >= $end || $busy[1] <= $start) { continue; }
                    $eventOrganizationId = (int)$event->get('IDorganization');
                    $holonId = (int)$event->get('IDholon');
                    if (!array_key_exists($eventOrganizationId, $organizationLabels)) {
                        $organization = new Organization();
                        $organizationLabels[$eventOrganizationId] = $eventOrganizationId > 0 && $organization->load($eventOrganizationId)
                            ? (string)$organization->get('name') : '';
                    }
                    if (!array_key_exists($holonId, $holonLabels)) {
                        $holon = new Holon();
                        $holonLabels[$holonId] = $holonId > 0 && $holon->load($holonId) ? $holon->getDisplayName() : '';
                    }
                    $intervals[] = ['start' => $busy[0], 'end' => $busy[1], 'source' => 'omo',
                        'organization' => $organizationLabels[$eventOrganizationId], 'holon' => $holonLabels[$holonId]];
                }
                $refreshFailed = false;
                if ($refreshUserCalendars !== null) {
                    try {
                        $refreshUserCalendars((int)$userId);
                    } catch (\Throwable $exception) {
                        // Even an unexpected refresh error must not skip the cache.
                        error_log('Calendar availability refresh failed: ' . get_class($exception));
                        $refreshFailed = true;
                    }
                }
                $external = ArrayExternalCalendarEvent::busyIntervalsForUser($userId, $start, $end);
                foreach ($external['intervals'] as [$busyStart, $busyEnd]) {
                    $intervals[] = ['start' => $busyStart, 'end' => $busyEnd, 'source' => 'external',
                        'organization' => '', 'holon' => ''];
                }
                $report['externalCache'] = $report['externalCache'] || $external['hasCalendars'];
                foreach ($external['unavailable'] as [$busyStart, $busyEnd]) {
                    $intervals[] = ['start' => $busyStart, 'end' => $busyEnd, 'source' => 'availability',
                        'organization' => '', 'holon' => ''];
                }
                if ($external['incomplete'] || $refreshFailed) {
                    $report['unverified'][] = ['name' => $name, 'reason' => 'cache'];
                }
            } catch (\Throwable $exception) {
                error_log('Calendar availability check failed: ' . get_class($exception));
                $report['unverified'][] = ['name' => $name, 'reason' => 'storage'];
            }
            // Keep each appointment and its full duration, without exposing titles or descriptions.
            usort($intervals, static fn($a, $b) => [$a['start'], $a['end'], $a['source'], $a['organization'], $a['holon']]
                <=> [$b['start'], $b['end'], $b['source'], $b['organization'], $b['holon']]);
            foreach ($intervals as $busy) {
                if ($busy['start'] >= $end || $busy['end'] <= $start) { continue; }
                $busy['start'] = $busy['start']->format('Y-m-d H:i');
                $busy['end'] = $busy['end']->format('Y-m-d H:i');
                $report['conflicts'][] = ['name' => $name] + $busy;
            }
        }
        return $report;
    }

    public function getNotificationRecipientUserIds(): array
    {
        $targets = $this->getEffectiveInvitationTargets((int)$this->get('IDorganization'));
        return array_values(array_unique(array_filter(array_map('intval', $targets['userIds'] ?? []), static function ($userId) {
            return $userId > 0;
        })));
    }

		public function getInvitationEmailRecipients(int $organizationId = 0): array
		{
			$organizationId = $organizationId > 0 ? $organizationId : (int)$this->get('IDorganization');
			$targets = $this->getEffectiveInvitationTargets($organizationId);
			$recipients = array();

			foreach ((array)($targets['userIds'] ?? array()) as $userId) {
				$userId = (int)$userId;
				$email = $this->getViewerScopedEmail($userId, $organizationId);
				if ($email === '') {
					continue;
				}

				$recipients[$email] = array(
					'email' => $email,
					'display_name' => $this->getViewerDisplayName($userId, $organizationId),
					'user_id' => $userId,
				);
			}

			foreach ((array)($targets['emails'] ?? array()) as $email) {
				$email = self::normalizeInvitationEmail($email);
				if ($email === '' || isset($recipients[$email])) {
					continue;
				}

				$recipients[$email] = array(
					'email' => $email,
					'display_name' => (string)($targets['registeredEmails'][$email] ?? ''),
					'user_id' => 0,
				);
			}

			return array_values($recipients);
		}

    public static function getNotificationLifecycleCandidates($limit = 200, $referenceDateTime = null): array
    {
        $referenceDateTime = $referenceDateTime instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($referenceDateTime)
            : new \DateTimeImmutable('now');
        $events = new \dbObject\ArrayEvent();
        $events->load([
            'where' => [
                ['field' => 'active', 'value' => 1],
                ['field' => 'status', 'op' => '<>', 'value' => self::STATUS_CANCELLED],
                ['field' => 'status', 'op' => '<>', 'value' => self::STATUS_DRAFT],
                ['field' => 'start_at', 'op' => '>=', 'value' => $referenceDateTime->format('Y-m-d H:i:s')],
            ],
            'orderBy' => [
                ['field' => 'start_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
            'limit' => max(1, (int)$limit),
        ]);

        return array_values(array_filter($events->getArrayCopy(), static function ($event) {
            return $event instanceof self;
        }));
    }

    protected function getAttendanceDisplayNameMap($organizationId): array
    {
        $organizationId = (int)$organizationId;
        $map = [
            'users' => [],
            'emails' => [],
        ];

        foreach ($this->getInvitations(true) as $invitation) {
            if (!($invitation instanceof \dbObject\EventInvitation)) {
                continue;
            }

            if (\dbObject\EventInvitation::normalizeStatus($invitation->get('status')) === \dbObject\EventInvitation::STATUS_REVOKED) {
                continue;
            }

            $type = \dbObject\EventInvitation::normalizeType($invitation->get('invitation_type'));
            $displayName = trim((string)$invitation->get('display_name'));

            if ($type === \dbObject\EventInvitation::TYPE_USER) {
                $userId = (int)$invitation->get('IDuser');
                if ($userId > 0 && $displayName !== '' && !isset($map['users'][$userId])) {
                    $map['users'][$userId] = $displayName;
                }
                continue;
            }

            if ($type === \dbObject\EventInvitation::TYPE_EMAIL) {
                $email = self::normalizeInvitationEmail($invitation->get('email'));
                if ($email !== '' && $displayName !== '' && !isset($map['emails'][$email])) {
                    $map['emails'][$email] = $displayName;
                }
            }
        }

        return $map;
    }

    protected function getAttendanceRowsByIdentity(): array
    {
        if ((int)$this->getId() <= 0 || !self::tableExists('resource_attendance')) {
            return [];
        }

        $rows = self::fetchAll(
            "SELECT
                id,
                IDuser,
                email,
                display_name,
                is_present,
                IDuser_checked_by,
                checked_at,
                active
            FROM resource_attendance
            WHERE resource_type = :resource_type
              AND resource_id = :event_id
              AND active = 1",
            [
                'event_id' => (int)$this->getId(),
                'resource_type' => \dbObject\EventAttendance::resourceType(),
            ]
        );

        if (!is_array($rows)) {
            return [];
        }

        $indexedRows = [];
        foreach ($rows as $row) {
            $userId = (int)($row['IDuser'] ?? 0);
            if ($userId > 0) {
                $indexedRows['user:' . $userId] = $row;
                continue;
            }

            $email = self::normalizeInvitationEmail($row['email'] ?? '');
            if ($email !== '') {
                $indexedRows['email:' . $email] = $row;
            }
        }

        return $indexedRows;
    }

    protected function getInvitationAcceptanceByIdentity($organizationId): array
    {
        $accepted = [];
        foreach ($this->getInvitations(true) as $invitation) {
            if (!($invitation instanceof \dbObject\EventInvitation) || \dbObject\EventInvitation::normalizeStatus($invitation->get('status')) === \dbObject\EventInvitation::STATUS_REVOKED) {
                continue;
            }
            $value = $invitation->get('accepted');
            if ($value === null || $value === '') {
                continue;
            }
            $type = \dbObject\EventInvitation::normalizeType($invitation->get('invitation_type'));
            if ($type === \dbObject\EventInvitation::TYPE_USER) {
                $accepted['user:' . (int)$invitation->get('IDuser')] = (bool)$value;
            } elseif ($type === \dbObject\EventInvitation::TYPE_EMAIL) {
                $email = self::normalizeInvitationEmail($invitation->get('email'));
                if ($email !== '') {
                    $accepted['email:' . $email] = (bool)$value;
                }
            } elseif ($type === \dbObject\EventInvitation::TYPE_HOLON && (bool)$value) {
                foreach ($this->getInvitationMembershipUserIds((int)$invitation->get('IDholon'), (int)$organizationId) as $userId) {
                    $accepted['user:' . (int)$userId] = true;
                }
            }
        }
        return $accepted;
    }

    public function getAttendanceEntries($organizationId = 0): array
    {
        $snapshot = $this->getParameter('meeting_participants');
        if (is_array($snapshot)) {
            $entries = $snapshot['entries']; $rows = $this->getAttendanceRowsByIdentity();
            foreach ($entries as &$entry) {
                if (isset($rows[$entry['identityKey']])) { $entry['isPresent'] = !empty($rows[$entry['identityKey']]['is_present']); }
            }
            unset($entry); return $entries;
        }
        $organizationId = (int)$organizationId > 0 ? (int)$organizationId : (int)$this->get('IDorganization');
        if ((int)$this->getId() <= 0 || $organizationId <= 0) {
            return [];
        }

        $targets = $this->getEffectiveInvitationTargets($organizationId);
        $displayNameMap = $this->getAttendanceDisplayNameMap($organizationId);
        $attendanceRows = $this->getAttendanceRowsByIdentity();
        $invitationAcceptance = $this->getInvitationAcceptanceByIdentity($organizationId);
        $entries = [];
        $knownUserEmails = [];

        foreach ((array)($targets['userIds'] ?? []) as $userId) {
            $userId = (int)$userId;
            if ($userId <= 0) {
                continue;
            }

            $displayName = trim((string)($displayNameMap['users'][$userId] ?? ''));
            if ($displayName === '') {
                $displayName = $this->getViewerDisplayName($userId, $organizationId);
            }
            if ($displayName === '') {
                $displayName = 'Utilisateur #' . $userId;
            }

            $email = $this->getViewerScopedEmail($userId, $organizationId);
            if ($email !== '') {
                $knownUserEmails[$email] = true;
            }

            $identityKey = 'user:' . $userId;
            $attendanceRow = $attendanceRows[$identityKey] ?? null;
            $entries[$identityKey] = [
                'identityKey' => $identityKey,
                'userId' => $userId,
                'email' => $email,
                'displayLabel' => $displayName,
                'secondaryLabel' => $email,
                'isPresent' => $attendanceRow !== null
                    ? !empty($attendanceRow['is_present'])
                    : !empty($invitationAcceptance[$identityKey]),
            ];
        }

        foreach ((array)($targets['emails'] ?? []) as $email) {
            $normalizedEmail = self::normalizeInvitationEmail($email);
            if ($normalizedEmail === '' || isset($knownUserEmails[$normalizedEmail])) {
                continue;
            }

            $identityKey = 'email:' . $normalizedEmail;
            $attendanceRow = $attendanceRows[$identityKey] ?? null;
            $displayName = trim((string)($displayNameMap['emails'][$normalizedEmail] ?? ''));
            if ($displayName === '') {
                $displayName = trim((string)($targets['registeredEmails'][$normalizedEmail] ?? ''));
            }
            if ($displayName === '') {
                $displayName = trim((string)($attendanceRow['display_name'] ?? ''));
            }
            if ($displayName === '') {
                $displayName = $normalizedEmail;
            }

            $entries[$identityKey] = [
                'identityKey' => $identityKey,
                'userId' => 0,
                'email' => $normalizedEmail,
                'displayLabel' => $displayName,
                'secondaryLabel' => $displayName !== $normalizedEmail ? $normalizedEmail : '',
                'isPresent' => $attendanceRow !== null
                    ? !empty($attendanceRow['is_present'])
                    : !empty($invitationAcceptance[$identityKey]),
            ];
        }

        $entries = array_values($entries);
        usort($entries, static function (array $left, array $right) {
            return strcmp(
                mb_strtolower((string)($left['displayLabel'] ?? ''), 'UTF-8'),
                mb_strtolower((string)($right['displayLabel'] ?? ''), 'UTF-8')
            );
        });

        return $entries;
    }

    public function setAttendancePresence($organizationId, $checkedByUserId, $identityKey, $isPresent): array
    {
        $organizationId = (int)$organizationId > 0 ? (int)$organizationId : (int)$this->get('IDorganization');
        $checkedByUserId = (int)$checkedByUserId;
        $identityKey = trim((string)$identityKey);

        if ((int)$this->getId() <= 0 || $organizationId <= 0 || $checkedByUserId <= 0 || $identityKey === '') {
            return [
                'status' => false,
                'text' => 'Contexte de présence invalide.',
            ];
        }

        $entries = $this->getAttendanceEntries($organizationId);
        $entry = null;
        foreach ($entries as $candidate) {
            if ((string)($candidate['identityKey'] ?? '') === $identityKey) {
                $entry = $candidate;
                break;
            }
        }

        if (!is_array($entry)) {
            return [
                'status' => false,
                'text' => 'Participant introuvable pour cette réunion.',
            ];
        }

        $attendance = new \dbObject\EventAttendance();
        $lookup = false;
        $userId = (int)($entry['userId'] ?? 0);
        $email = self::normalizeInvitationEmail($entry['email'] ?? '');
        if ($userId > 0) {
            $lookup = $attendance->load([
                ['resource_type', \dbObject\EventAttendance::resourceType()],
                ['resource_id', (int)$this->getId()],
                ['IDuser', $userId],
            ]);
        } elseif ($email !== '') {
            $lookup = $attendance->load([
                ['resource_type', \dbObject\EventAttendance::resourceType()],
                ['resource_id', (int)$this->getId()],
                ['email', $email],
            ]);
        }

        if (!$lookup) {
            $attendance->set('IDevent', (int)$this->getId());
            $attendance->set('IDuser', $userId > 0 ? $userId : null);
            $attendance->set('email', $userId <= 0 && $email !== '' ? $email : null);
        }

        $attendance->set('display_name', trim((string)($entry['displayLabel'] ?? '')) !== '' ? trim((string)$entry['displayLabel']) : null);
        $attendance->set('is_present', !empty($isPresent) ? 1 : 0);
        $attendance->set('IDuser_checked_by', $checkedByUserId);
        $attendance->set('checked_at', new \DateTimeImmutable());
        $attendance->set('active', 1);
        $saveResult = $attendance->save();
        if (is_array($saveResult) && !empty($saveResult['status'])) {
            foreach ($this->getInvitations(true) as $invitation) {
                if ($invitation instanceof \dbObject\EventInvitation && $invitation->getIdentityKey() === $identityKey) {
                    $invitation->set('accepted', !empty($isPresent) ? 1 : 0);
                    $invitation->save();
                    break;
                }
            }
        }
        return $saveResult;
    }

    public function isPersonallyRelevantToViewer($userId, $organizationId = 0): bool
    {
        return $this->isInvitedToEvent($userId, $organizationId);
    }

    public function isDraftVisibleToViewer($userId): bool
    {
        if (self::normalizeStatus($this->get('status')) !== self::STATUS_DRAFT) {
            return true;
        }

        $userId = (int)$userId;
        return $userId > 0 && $userId === (int)$this->get('IDuser');
    }

    public function isVisibleToInvitationViewer($userId, $organizationId = 0, $viewerEmail = ''): bool
    {
        return $this->matchesInvitationViewer($userId, $organizationId, $viewerEmail, false);
    }

    /** Attendance requires an invitation or registration, independently of management visibility. */
    public function isInvitedToEvent($userId, $organizationId = 0, $viewerEmail = ''): bool
    {
        return $this->matchesInvitationViewer($userId, $organizationId, $viewerEmail, true);
    }

    private function matchesInvitationViewer($userId, $organizationId, $viewerEmail, bool $explicitOnly): bool
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;

        if (
            $userId <= 0
            || (int)$this->getId() <= 0
            || (int)$this->get('active') !== 1
            || self::normalizeStatus($this->get('status')) === self::STATUS_CANCELLED
        ) {
            return false;
        }

        if ($organizationId <= 0) {
            $organizationId = (int)$this->get('IDorganization');
        }

        if ($organizationId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
            return false;
        }

        if (!$this->isDraftVisibleToViewer($userId)) {
            return false;
        }

        $targets = $this->getEffectiveInvitationTargets($organizationId, explicitOnly: $explicitOnly);
        if (!$explicitOnly && !$targets['hasExplicitInvitations'] && (int)$this->get('IDholon') <= 0) {
            return true;
        }

        if (in_array($userId, $targets['userIds'], true)) {
            return true;
        }

        $viewerEmail = self::normalizeInvitationEmail($viewerEmail);
        if ($viewerEmail === '') {
            $viewerEmail = $this->getViewerScopedEmail($userId, $organizationId);
        }

        return $viewerEmail !== '' && in_array($viewerEmail, $targets['emails'], true);
    }

    public function getAssociatedDocument()
    {
        $documents = $this->getAssociatedDocuments();
        return count($documents) > 0 ? $documents[0] : null;
    }

    public function buildAssociatedDocumentDetailUrl(int $fallbackHolonId = 0): string
    {
        $document = $this->getAssociatedDocument();
        if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
            return '';
        }

        $organizationId = (int)$document->get('IDorganization');
        $holonId = (int)$document->get('IDholon');
        if ($holonId <= 0) {
            $holonId = max(0, $fallbackHolonId);
        }

        $url = '/omo/api/documents/detail.php?id=' . rawurlencode((string)(int)$document->getId());
        if ($organizationId > 0) {
            $url .= '&oid=' . rawurlencode((string)$organizationId);
        }
        if ($holonId > 0) {
            $url .= '&cid=' . rawurlencode((string)$holonId);
        }

        return $url;
    }

    public function isUpcoming(?\DateTimeInterface $referenceDate = null): bool
    {
        $startAt = $this->get('start_at');
        if (!($startAt instanceof \DateTimeInterface)) {
            return false;
        }

        if (!($referenceDate instanceof \DateTimeInterface)) {
            $timezone = $startAt->getTimezone();
            $referenceDate = $timezone instanceof \DateTimeZone
                ? new \DateTimeImmutable('now', $timezone)
                : new \DateTimeImmutable('now');
        }

        return $startAt > $referenceDate;
    }

    public function isInProgress(?\DateTimeInterface $referenceDate = null): bool
    {
        $startAt = $this->get('start_at');
        $endAt = $this->get('end_at');
        if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
            return false;
        }

        if (!($referenceDate instanceof \DateTimeInterface)) {
            $timezone = $startAt->getTimezone();
            $referenceDate = $timezone instanceof \DateTimeZone
                ? new \DateTimeImmutable('now', $timezone)
                : new \DateTimeImmutable('now');
        }

        return $startAt <= $referenceDate && $endAt >= $referenceDate;
    }

    public function canUserPrepareUpcomingPv(int $userId, int $organizationId = 0, string $viewerEmail = ''): bool
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;

        if ($userId <= 0 || !$this->isUpcoming()) {
            return false;
        }

        if ($organizationId <= 0) {
            $organizationId = (int)$this->get('IDorganization');
        }

        if ($organizationId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
            return false;
        }

        if ($userId === (int)$this->get('IDuser')) {
            return true;
        }

        return $this->isVisibleToInvitationViewer($userId, $organizationId, $viewerEmail);
    }

    /** Numeric European/ISO dates only; never replace dates unrelated to the source meeting. */
    public static function replaceMeetingDateInTitle(string $title, \DateTimeInterface $from, \DateTimeInterface $to): string
    {
        $pattern = '~(?<![\pL\pN./-])(?<a>\d{4}|\d{1,2})(?<sep>[./-])(?<b>\d{1,2})\k<sep>(?<c>\d{4}|\d{1,2})(?!\d|[./-]\d)
            (?:(?<join>T|[ ,]+(?:(?:a|\x{00e0}|de)[ ]+)?)
                (?<hour>[01]?\d|2[0-3])(?:(?<colon>:)(?<minute>[0-5]\d)(?::(?<second>[0-5]\d))?|(?<hsep>[hH])(?<hminute>[0-5]\d)?)(?!\d))?~ux';
        return preg_replace_callback($pattern, static function (array $match) use ($from, $to): string {
            $iso = strlen($match['a']) === 4;
            $year = $iso ? $match['a'] : $match['c'];
            $day = $iso ? $match['c'] : $match['a'];
            if (!in_array(strlen($year), [2, 4], true) || strlen($day) > 2
                || (int)$day !== (int)$from->format('j') || (int)$match['b'] !== (int)$from->format('n')
                || $year !== $from->format(strlen($year) === 4 ? 'Y' : 'y')) { return $match[0]; }
            $nextDay = $to->format(strlen($day) === 2 ? 'd' : 'j');
            $nextMonth = $to->format(strlen($match['b']) === 2 ? 'm' : 'n');
            $nextYear = $to->format(strlen($year) === 4 ? 'Y' : 'y');
            $date = implode($match['sep'], $iso ? [$nextYear, $nextMonth, $nextDay] : [$nextDay, $nextMonth, $nextYear]);
            $oldDate = $match['a'] . $match['sep'] . $match['b'] . $match['sep'] . $match['c'];
            $suffix = substr($match[0], strlen($oldDate));
            if (isset($match['hour']) && $match['hour'] !== '') {
                $minute = $match['minute'] ?? ''; $hminute = $match['hminute'] ?? '';
                $second = $match['second'] ?? '';
                if ((int)$match['hour'] === (int)$from->format('G') && (int)($minute ?: $hminute) === (int)$from->format('i')
                    && ($second === '' || (int)$second === (int)$from->format('s'))) {
                    $suffix = $match['join'] . $to->format(strlen($match['hour']) === 2 ? 'H' : 'G');
                    $suffix .= !empty($match['colon']) ? ':' . $to->format('i') . ($second !== '' ? ':' . $to->format('s') : '')
                        : $match['hsep'] . ($hminute !== '' || $to->format('i') !== '00' ? $to->format('i') : '');
                }
            }
            return $date . $suffix;
        }, $title) ?? $title;
    }

    /** Track generated names separately from manual renames. */
    public function registerDefaultDocumentTitle(Document $document, array $pattern): bool
    {
        if ((int)$document->get('IDevent') !== (int)$this->getId() || (int)$this->getId() <= 0
            || (int)$document->get('IDorganization') !== (int)$this->get('IDorganization')
            || !isset($pattern['before'], $pattern['after'])) { return false; }
        $entry = ['pattern' => $pattern, 'last_title' => (string)$document->get('title')];
        $saved = self::execute("UPDATE event SET parameters = JSON_SET(COALESCE(NULLIF(parameters, ''), '{}'),
            '$.default_document_titles', JSON_SET(COALESCE(JSON_EXTRACT(COALESCE(NULLIF(parameters, ''), '{}'), '$.default_document_titles'), '{}'),
                :document_path, JSON_EXTRACT(:entry, '$'))) WHERE id = :id",
            ['document_path' => '$."' . (int)$document->getId() . '"', 'entry' => json_encode($entry, JSON_THROW_ON_ERROR), 'id' => $this->getId()]);
        return $saved && $this->load((int)$this->getId(), true);
    }

    public function syncAssociatedDocumentEventDate(?\DateTimeInterface $previousStart = null)
    {
        $documents = $this->getAssociatedDocuments();
        if (count($documents) === 0) {
            return [
                'status' => true,
            ];
        }

        $endAt = $this->get('end_at');
        if (!($endAt instanceof \DateTimeInterface)) {
            return [
                'status' => false,
                'text' => "La date de fin de l'événement est invalide.",
            ];
        }

        foreach ($documents as $document) {
            if ((int)$document->get('IDevent') !== (int)$this->getId()) { continue; }
            $titleResult = $document->syncDefaultEventTitle($this, $previousStart);
            if (empty($titleResult['status'])) { return $titleResult; }
            $currentCreatedAt = $document->get('datecreation');
            $nextComparable = $endAt->format('Y-m-d H:i:s');
            $currentComparable = $currentCreatedAt instanceof \DateTimeInterface
                ? $currentCreatedAt->format('Y-m-d H:i:s')
                : trim((string)$currentCreatedAt);

            if ($currentComparable === $nextComparable) {
                continue;
            }

            $document->set('datecreation', \DateTimeImmutable::createFromInterface($endAt));
            $document->set('datemodification', new \DateTimeImmutable());
            $saveResult = $document->save();
            if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
                return [
                    'status' => false,
                    'text' => trim((string)($saveResult['text'] ?? 'Impossible de synchroniser la date du document associé.')),
                ];
            }
        }

        return [
            'status' => true,
        ];
    }

    protected function resolveOrganizationIdFromHolon()
    {
        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return 0;
        }

        $holon = new Holon();
        if (!$holon->load($holonId)) {
            return 0;
        }

        $organizationId = (int)$holon->get('IDorganization');
        if ($organizationId > 0) {
            return $organizationId;
        }

        $rootHolonId = (int)$holon->get('IDholon_org');
        if ($rootHolonId <= 0) {
            return 0;
        }

        $rootHolon = new Holon();
        if (!$rootHolon->load($rootHolonId)) {
            return 0;
        }

        return (int)$rootHolon->get('IDorganization');
    }

    public static function getOrganizationIdByEventId($eventId)
    {
        $eventId = (int)$eventId;
        if ($eventId <= 0) {
            return 0;
        }

        return (int)self::fetchValue(
            'SELECT `IDorganization` FROM `event` WHERE `id` = :event_id LIMIT 1',
            array('event_id' => $eventId)
        );
    }

    public function save()
    {
        $original = (int)$this->getId() > 0 ? self::fetchRow('SELECT title, start_at, end_at FROM event WHERE id = :id', ['id' => $this->getId()]) : null;
        if ((int)$this->getId() > 0 && (int)$this->get('IDeventrecurrence') > 0) {
            if ($original && new \DateTimeImmutable($original['end_at']) < new \DateTimeImmutable()) {
                return ['status' => false, 'text' => 'Les reunions recurrentes passees ne peuvent plus etre modifiees.'];
            }
        }
        $this->set('status', self::normalizeStatus($this->get('status')));

        $timezone = trim((string)$this->get('timezone'));
        $this->set('timezone', $timezone !== '' ? $timezone : null);

        $locationAddress = trim((string)$this->get('locationaddress'));
        $videoMeetingUrl = self::sanitizeVideoMeetingUrl($this->get('videomeetingurl'));
        $locationMode = self::normalizeLocationMode($this->get('locationmode'));
        if ($locationMode === '') {
            $locationMode = self::inferLocationModeFromFields($locationAddress, $videoMeetingUrl);
        }

        $this->set('locationmode', $locationMode !== '' ? $locationMode : null);
        $this->set('locationaddress', $locationAddress !== '' ? $locationAddress : null);
        $this->set('videomeetingurl', $videoMeetingUrl !== '' ? $videoMeetingUrl : null);

        $organizationId = (int)$this->get('IDorganization');
        if ($organizationId <= 0) {
            $organizationId = $this->resolveOrganizationIdFromHolon();
            if ($organizationId > 0) {
                $this->set('IDorganization', $organizationId);
            }
        }

        if ((int)$this->get('IDorganization') <= 0) {
            return [
                'status' => false,
                'text' => 'An event needs an organization.',
            ];
        }

        if ((int)$this->get('IDuser') <= 0) {
            return [
                'status' => false,
                'text' => 'An event needs a creator user.',
            ];
        }

        $startAt = $this->get('start_at');
        $endAt = $this->get('end_at');
        if ($startAt instanceof \DateTimeInterface && $endAt instanceof \DateTimeInterface && $endAt < $startAt) {
            return [
                'status' => false,
                'text' => 'The event end date must be greater than or equal to the start date.',
            ];
        }

        if ($locationMode === self::LOCATION_MODE_IN_PERSON && $locationAddress === '') {
            return [
                'status' => false,
                'text' => 'Une adresse est obligatoire pour un événement en présentiel.',
            ];
        }

        if ($locationMode === self::LOCATION_MODE_VIRTUAL && $videoMeetingUrl === '') {
            return [
                'status' => false,
                'text' => 'Un lien de visio est obligatoire pour un événement virtuel.',
            ];
        }

        if (
            $locationMode === self::LOCATION_MODE_HYBRID
            && ($locationAddress === '' || $videoMeetingUrl === '')
        ) {
            return [
                'status' => false,
                'text' => 'Une adresse et un lien de visio sont obligatoires pour un événement mixte.',
            ];
        }

        $previousStart = $original && !empty($original['start_at']) ? new \DateTimeImmutable($original['start_at']) : null;
        if ($previousStart && $startAt instanceof \DateTimeInterface && (string)$this->get('title') === (string)$original['title']) {
            $this->set('title', self::replaceMeetingDateInTitle((string)$this->get('title'), $previousStart, $startAt));
        }
        $saveResult = parent::save();
        if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
            return $saveResult;
        }

        CalDavCache::invalidateOrganization((int)$this->get('IDorganization'));
        CalDavSyncChange::recordEventChange((int)$this->get('IDorganization'), (int)$this->getId(), 'updated');

        $syncResult = $this->syncAssociatedDocumentEventDate($previousStart);
        if (!is_array($syncResult) || ($syncResult['status'] ?? false) !== true) {
            return $syncResult;
        }

        return $saveResult;
    }

    /** Conditional linking avoids silently moving an event already assigned to another project. */
    public function attachToProject(Project $project): bool
    {
        $projectId = (int)$project->getId();
        if ($projectId <= 0 || !$project->get('active') || $project->isPendingProposal()
            || (int)$project->get('IDorganization') !== (int)$this->get('IDorganization')) { return false; }
        $saved = self::execute('UPDATE event SET IDproject = :project, updated_at = NOW()
            WHERE id = :id AND IDorganization = :organization AND active = 1 AND status <> :cancelled
                AND (IDproject IS NULL OR IDproject = 0 OR IDproject = :current_project)',
            ['project' => $projectId, 'id' => (int)$this->getId(), 'organization' => (int)$project->get('IDorganization'),
                'cancelled' => self::STATUS_CANCELLED, 'current_project' => $projectId]);
        return $saved && $this->load((int)$this->getId(), true) && (int)$this->get('IDproject') === $projectId;
    }

    public function detachFromProject(int $projectId): bool
    {
        return $projectId > 0 && self::execute('UPDATE event SET IDproject = NULL, updated_at = NOW()
            WHERE id = :id AND IDproject = :project', ['id' => (int)$this->getId(), 'project' => $projectId]);
    }

    public function delete()
    {
        if ($this->isPastRecurringMeeting()) { return false; }
        $pdo = self::getPdo(); $ownsTransaction = !$pdo->inTransaction();
        $organizationId = (int)$this->get('IDorganization');
        $eventId = (int)$this->getId();
        try {
            if ($ownsTransaction) { $pdo->beginTransaction(); }
            EventRecurrence::transferReference($this);
            $deleted = parent::delete();
            if ($deleted) {
                CalDavCache::invalidateOrganization($organizationId);
                CalDavSyncChange::recordEventChange($organizationId, $eventId, 'deleted');
            }
            if ($ownsTransaction) {
                if ($deleted) { $pdo->commit(); } else { $pdo->rollBack(); }
            }
            return $deleted;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }
}

?>
