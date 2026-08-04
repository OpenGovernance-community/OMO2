<?php
namespace dbObject;

class Event extends DbObject
{
    const STATUS_DRAFT = 'draft';
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
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDproject', 'IDuser'], 'fk'],
            [['title', 'status', 'timezone', 'locationmode', 'locationaddress', 'videomeetingurl'], 'string'],
            [['description'], 'text'],
            [['parameters'], 'parameters'],
            [['is_all_day', 'active'], 'boolean'],
            [['start_at', 'end_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Cercle ou rôle',
            'IDproject' => 'Projet',
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
            'IDholon' => "Holon optionnel pour rattacher l'événement à un cercle ou à un rôle.",
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

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        return self::execute("UPDATE event SET IDuser = CASE WHEN end_at < NOW() THEN :ghost_user_id ELSE NULL END WHERE IDorganization = :organization_id AND IDuser = :user_id", array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId));
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_DRAFT => [
                'label' => 'Brouillon',
                'description' => "L'événement est encore en préparation.",
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

        return array_values(array_filter($documents->getArrayCopy(), static function ($document) {
            return $document instanceof \dbObject\Document && (int)$document->getId() > 0;
        }));
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

    protected function getInvitationMembershipUserIds($holonId, $organizationId): array
    {
        static $membershipCache = [];

        $holonId = (int)$holonId;
        $organizationId = (int)$organizationId;
        if ($holonId <= 0 || $organizationId <= 0) {
            return [];
        }

        $cacheKey = $organizationId . ':' . $holonId;
        if (isset($membershipCache[$cacheKey])) {
            return $membershipCache[$cacheKey];
        }

        $holon = new \dbObject\Holon();
        if (
            !$holon->load($holonId)
            || !(bool)$holon->get('active')
            || !(bool)$holon->get('visible')
        ) {
            $membershipCache[$cacheKey] = [];
            return $membershipCache[$cacheKey];
        }

        $userIds = $holon->getAssociatedMemberUserIds([
            'organizationId' => $organizationId,
            'skipPermissionFilter' => true,
        ]);

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

    protected function getEffectiveInvitationTargets($organizationId): array
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            $organizationId = (int)$this->get('IDorganization');
        }

        $targets = [
            'hasExplicitInvitations' => false,
            'userIds' => [],
            'emails' => [],
        ];

        foreach ($this->getInvitations(true) as $invitation) {
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
                foreach ($this->getInvitationMembershipUserIds((int)$invitation->get('IDholon'), $organizationId) as $userId) {
                    if ($userId > 0) {
                        $targets['userIds'][$userId] = (int)$userId;
                    }
                }
            }
        }

        if ($targets['hasExplicitInvitations']) {
            $targets['userIds'] = array_values($targets['userIds']);
            $targets['emails'] = array_values($targets['emails']);
            return $targets;
        }

        $eventHolonId = (int)$this->get('IDholon');
        if ($eventHolonId > 0) {
            foreach ($this->getInvitationMembershipUserIds($eventHolonId, $organizationId) as $userId) {
                if ($userId > 0) {
                    $targets['userIds'][$userId] = (int)$userId;
                }
            }
            $targets['userIds'] = array_values($targets['userIds']);
            return $targets;
        }

        if (!$this->organizationHasStructureApplication($organizationId)) {
            $targets['userIds'] = $this->getOrganizationMemberUserIds($organizationId);
        }

        return $targets;
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

    public function isVisibleToInvitationViewer($userId, $organizationId = 0, $viewerEmail = ''): bool
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

        $targets = $this->getEffectiveInvitationTargets($organizationId);
        if (!$targets['hasExplicitInvitations'] && (int)$this->get('IDholon') <= 0) {
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

    public function syncAssociatedDocumentEventDate()
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
            $currentCreatedAt = $document->get('datecreation');
            $nextComparable = $endAt->format('Y-m-d H:i:s');
            $currentComparable = $currentCreatedAt instanceof \DateTimeInterface
                ? $currentCreatedAt->format('Y-m-d H:i:s')
                : trim((string)$currentCreatedAt);

            if ($currentComparable === $nextComparable) {
                continue;
            }

            $document->set('datecreation', \DateTimeImmutable::createFromInterface($endAt));
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

    public function save()
    {
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

        $saveResult = parent::save();
        if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
            return $saveResult;
        }

        $syncResult = $this->syncAssociatedDocumentEventDate();
        if (!is_array($syncResult) || ($syncResult['status'] ?? false) !== true) {
            return $syncResult;
        }

        return $saveResult;
    }
}

?>
