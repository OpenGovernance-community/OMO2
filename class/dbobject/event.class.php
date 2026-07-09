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
            [['IDorganization', 'IDholon', 'IDuser'], 'fk'],
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
            'IDholon' => 'Cercle ou role',
            'IDuser' => 'Createur',
            'title' => 'Titre',
            'description' => 'Description',
            'status' => 'Statut',
            'timezone' => 'Fuseau horaire',
            'locationmode' => 'Format du lieu',
            'locationaddress' => 'Adresse',
            'videomeetingurl' => 'Lien de visio',
            'start_at' => 'Debut',
            'end_at' => 'Fin',
            'is_all_day' => 'Journee entiere',
            'parameters' => 'Parametres',
            'active' => 'Actif',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => 'Holon optionnel pour rattacher l evenement a un cercle ou a un role.',
            'IDuser' => 'Utilisateur qui a cree l evenement.',
            'status' => 'Cycle de vie simple avant l ajout des invitations et reponses.',
            'timezone' => 'Fuseau horaire de reference pour l export agenda.',
            'locationmode' => 'Permet de preciser si la rencontre est en presentiel, en visio ou mixte.',
            'locationaddress' => 'Adresse libre pour les rencontres en presentiel ou mixtes.',
            'videomeetingurl' => 'URL http ou https de la salle de reunion virtuelle.',
            'is_all_day' => 'Indique si l evenement doit etre interprete comme une journee complete.',
            'parameters' => 'Reserve pour les invitations, metadonnees et options futures.',
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

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_DRAFT => [
                'label' => 'Brouillon',
                'description' => 'L evenement est encore en preparation.',
            ],
            self::STATUS_CONFIRMED => [
                'label' => 'Confirme',
                'description' => 'L evenement est planifie et pret a etre diffuse.',
            ],
            self::STATUS_CANCELLED => [
                'label' => 'Annule',
                'description' => 'L evenement est conserve mais ne doit plus etre active.',
            ],
        ];
    }

    public static function getLocationModeCatalog()
    {
        return [
            self::LOCATION_MODE_IN_PERSON => [
                'label' => 'Presentiel',
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

    public function getInvitations($activeOnly = false)
    {
        $items = new \dbObject\ArrayEventInvitation();
        $params = [
            'where' => [
                ['field' => 'IDevent', 'value' => (int)$this->getId()],
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

        return $targets;
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
                'text' => 'La date de fin de l evenement est invalide.',
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
                    'text' => trim((string)($saveResult['text'] ?? 'Impossible de synchroniser la date du document associe.')),
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
                'text' => 'Une adresse est obligatoire pour un evenement en presentiel.',
            ];
        }

        if ($locationMode === self::LOCATION_MODE_VIRTUAL && $videoMeetingUrl === '') {
            return [
                'status' => false,
                'text' => 'Un lien de visio est obligatoire pour un evenement virtuel.',
            ];
        }

        if (
            $locationMode === self::LOCATION_MODE_HYBRID
            && ($locationAddress === '' || $videoMeetingUrl === '')
        ) {
            return [
                'status' => false,
                'text' => 'Une adresse et un lien de visio sont obligatoires pour un evenement mixte.',
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
