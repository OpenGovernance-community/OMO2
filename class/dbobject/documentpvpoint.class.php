<?php
namespace dbObject;

class DocumentPvPoint extends DbObject
{
    public const TYPE_INFORMATION = 'information';
    public const TYPE_CONSULTATION = 'consultation';
    public const TYPE_DECISION = 'decision';
    public const ITEM_TYPE_POINT = 'point';
    public const ITEM_TYPE_GROUP = 'group';
    public const EDIT_LOCK_TIMEOUT_SECONDS = 120;

    public static function tableName()
    {
        return 'document_pv_point';
    }

    public static function rules()
    {
        return [
            [['IDdocument', 'title', 'pointtype'], 'required'],
            [['id', 'position', 'desired_duration_minutes', 'actual_duration_minutes'], 'integer'],
            [['IDdocument', 'IDparent', 'IDuser_author', 'IDholon_concerned', 'IDuser_modification', 'IDuser_editing'], 'fk'],
            [['title', 'item_type', 'author_email', 'pointtype', 'edit_lock_token'], 'string'],
            [['content'], 'html'],
            [['active', 'is_handled'], 'boolean'],
            [['datecreation', 'datemodification', 'dateedition'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdocument' => 'Document PV',
            'item_type' => 'Type d element',
            'IDparent' => 'Groupe parent',
            'title' => 'Titre',
            'IDuser_author' => 'Auteur',
            'author_email' => 'E-mail auteur externe',
            'IDholon_concerned' => 'Holon concerne',
            'content' => 'Texte HTML',
            'position' => 'Ordre',
            'desired_duration_minutes' => 'Duree souhaitee',
            'actual_duration_minutes' => 'Duree reelle',
            'pointtype' => 'Type',
            'IDuser_modification' => 'Derniere modification',
            'IDuser_editing' => 'Edition en cours',
            'edit_lock_token' => 'Jeton de verrou',
            'is_handled' => 'Traite',
            'active' => 'Actif',
            'dateedition' => 'Date edition',
            'datecreation' => 'Creation',
            'datemodification' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'title' => 'Titre du point a l ordre du jour.',
            'item_type' => 'Distingue un point de contenu d un groupe de classement.',
            'IDparent' => 'Groupe parent direct dans l ordre du jour.',
            'IDuser_author' => 'Membre qui porte ou presente ce point.',
            'author_email' => 'Adresse e-mail de la personne externe qui porte ce point.',
            'IDholon_concerned' => 'Holon principal directement concerne par ce point.',
            'content' => 'Contenu HTML formate du point.',
            'position' => 'Ordre d affichage dans le PV.',
            'desired_duration_minutes' => 'Duree visee en minutes.',
            'actual_duration_minutes' => 'Duree observee en minutes.',
            'pointtype' => 'Nature du point: information, consultation ou decision.',
            'IDuser_modification' => 'Derniere personne ayant change ce point, son ordre ou son statut.',
            'IDuser_editing' => 'Personne qui detient actuellement le verrou d edition.',
            'edit_lock_token' => 'Jeton technique de verrouillage d une session d edition.',
            'is_handled' => 'Indique si le point a deja ete traite en reunion.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 80,
            'item_type' => 20,
            'author_email' => 250,
            'pointtype' => 20,
            'edit_lock_token' => 80,
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function hasPointTable(): bool
    {
        return self::tableExists(self::tableName());
    }

    public static function getPointTypeCatalog(): array
    {
        return [
            self::TYPE_INFORMATION => 'Information',
            self::TYPE_CONSULTATION => 'Consultation',
            self::TYPE_DECISION => 'Decision',
        ];
    }

    public static function normalizePointType($value): string
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getPointTypeCatalog())
            ? $value
            : self::TYPE_INFORMATION;
    }

    public static function normalizeItemType($value): string
    {
        return trim(mb_strtolower((string)$value, 'UTF-8')) === self::ITEM_TYPE_GROUP
            ? self::ITEM_TYPE_GROUP
            : self::ITEM_TYPE_POINT;
    }

    public function isGroup(): bool
    {
        return self::normalizeItemType($this->get('item_type')) === self::ITEM_TYPE_GROUP;
    }

    public static function buildHierarchyPositionLabels(iterable $items): array
    {
        $childrenByParent = [];
        foreach ($items as $item) {
            if (!($item instanceof self) || (int)$item->getId() <= 0) {
                continue;
            }
            $childrenByParent[max(0, (int)$item->get('IDparent'))][] = $item;
        }

        foreach ($childrenByParent as &$siblings) {
            usort($siblings, static function (self $left, self $right): int {
                $positionComparison = (int)$left->get('position') <=> (int)$right->get('position');
                return $positionComparison !== 0
                    ? $positionComparison
                    : (int)$left->getId() <=> (int)$right->getId();
            });
        }
        unset($siblings);

        $labels = [];
        $visited = [];
        $appendLabels = function (int $parentId, string $prefix = '') use (&$appendLabels, &$labels, &$visited, $childrenByParent): void {
            $index = 0;
            foreach ($childrenByParent[$parentId] ?? [] as $item) {
                $itemId = (int)$item->getId();
                if ($itemId <= 0 || isset($visited[$itemId])) {
                    continue;
                }
                $visited[$itemId] = true;
                $index++;
                $label = $prefix === '' ? (string)$index : $prefix . '.' . $index;
                $labels[$itemId] = $label;
                if ($item->isGroup()) {
                    $appendLabels($itemId, $label);
                }
            }
        };
        $appendLabels(0);

        return $labels;
    }

    public static function buildHierarchyPositionLabelsForDocument(int $documentId): array
    {
        $items = new \dbObject\ArrayDocumentPvPoint();
        $items->loadForDocument($documentId, true);
        return self::buildHierarchyPositionLabels($items);
    }

    protected static function resolveNextPositionForDocument(int $documentId, int $parentId = 0): int
    {
        return max(
            1,
            (int)self::fetchValue(
                "SELECT COALESCE(MAX(position), 0) + 1
                FROM document_pv_point
                WHERE IDdocument = :document_id
                  AND COALESCE(IDparent, 0) = :parent_id",
                ['document_id' => $documentId, 'parent_id' => max(0, $parentId)]
            )
        );
    }

    protected static function resolveUserDisplayNameById(int $userId, int $organizationId = 0): string
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        if ($userId <= 0) {
            return '';
        }

        if ($organizationId > 0) {
            $membership = new \dbObject\UserOrganization();
            if ($membership->load([
                ['IDuser', $userId],
                ['IDorganization', $organizationId],
            ])) {
                return trim((string)$membership->getUserDisplayName());
            }
        }

        $user = new \dbObject\User();
        if (!$user->load($userId)) {
            return '';
        }

        $fullName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string)$user->get('username'));
        if ($username !== '') {
            return $username;
        }

        return trim((string)$user->get('email'));
    }

    public static function getUserDisplayNameForOrganization(int $userId, int $organizationId = 0): string
    {
        return self::resolveUserDisplayNameById($userId, $organizationId);
    }

    protected static function resolveHolonLabelById(int $holonId): string
    {
        if ($holonId <= 0) {
            return '';
        }

        $holon = new \dbObject\Holon();
        if (!$holon->load($holonId)) {
            return '';
        }

        return trim((string)$holon->getFullDisplayName());
    }

    protected static function resolveDocumentContextCircleId(\dbObject\Document $document): int
    {
        $documentHolonId = (int)$document->get('IDholon');
        if ($documentHolonId <= 0 && method_exists($document, 'getAssociatedEvent')) {
            $event = $document->getAssociatedEvent();
            if ($event instanceof \dbObject\Event) {
                $documentHolonId = (int)$event->get('IDholon');
            }
        }

        if ($documentHolonId <= 0) {
            return 0;
        }

        $holon = new \dbObject\Holon();
        if (!$holon->load($documentHolonId)) {
            return 0;
        }

        $circle = $holon->getContainingCircle(true);
        return $circle instanceof \dbObject\Holon ? (int)$circle->getId() : 0;
    }

    public static function buildConcernedHolonOptionsForDocument(\dbObject\Document $document, int $userId): array
    {
        $userId = (int)$userId;
        $organizationId = (int)$document->get('IDorganization');
        if ($userId <= 0 || $organizationId <= 0) {
            return [];
        }

        $contextCircleId = self::resolveDocumentContextCircleId($document);
        $contextHolon = new \dbObject\Holon();
        $assignments = [];

        if ($contextCircleId > 0 && $contextHolon->load($contextCircleId)) {
            $assignments = $contextHolon->getVisibleRoleAssignmentsForUser($userId, [
                'organizationId' => $organizationId,
                'includeDescendants' => true,
            ]);
        }

        if (count($assignments) === 0) {
            $rootHolon = null;
            $organization = new \dbObject\Organization();
            if ($organization->load($organizationId)) {
                $rootHolon = $organization->getEnabledStructuralRootHolon();
            }

            if ($rootHolon instanceof \dbObject\Holon) {
                $assignments = $rootHolon->getVisibleRoleAssignmentsForUser($userId, [
                    'organizationId' => $organizationId,
                    'includeDescendants' => true,
                ]);
            }
        }

        $options = [];
        foreach ($assignments as $assignment) {
            $holonId = (int)($assignment['holonId'] ?? 0);
            if ($holonId <= 0 || isset($options[$holonId])) {
                continue;
            }

            $label = trim((string)($assignment['name'] ?? ''));
            if ($label === '') {
                $label = self::resolveHolonLabelById($holonId);
            }
            if ($label === '') {
                $label = 'Role #' . $holonId;
            }

            $options[$holonId] = [
                'id' => $holonId,
                'label' => $label,
            ];
        }

        return array_values($options);
    }

    public static function concernedHolonIsAllowedForDocument(\dbObject\Document $document, int $userId, int $holonId): bool
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return true;
        }

        foreach (self::buildConcernedHolonOptionsForDocument($document, $userId) as $option) {
            if ((int)($option['id'] ?? 0) === $holonId) {
                return true;
            }
        }

        return false;
    }

    public function getPointTypeLabel(): string
    {
        $catalog = self::getPointTypeCatalog();
        $pointType = self::normalizePointType($this->get('pointtype'));
        return (string)($catalog[$pointType] ?? $catalog[self::TYPE_INFORMATION]);
    }

    public function getAuthorDisplayName(int $organizationId = 0): string
    {
        $userLabel = self::resolveUserDisplayNameById((int)$this->get('IDuser_author'), $organizationId);
        if ($userLabel !== '') {
            return $userLabel;
        }

        return trim((string)$this->get('author_email'));
    }

    public function getConcernedHolonLabel(): string
    {
        return self::resolveHolonLabelById((int)$this->get('IDholon_concerned'));
    }

    public function getAddressedHolonItems(): array
    {
        if (!self::tableExists('document_pv_point_holon') || !self::tableExists('holon')) {
            return [];
        }

        $rows = self::fetchAll(
            "SELECT
                h.id,
                h.name,
                h.nomcomplet,
                l.position
            FROM document_pv_point_holon l
            INNER JOIN holon h
                ON h.id = l.IDholon
            WHERE l.IDdocument_pv_point = :point_id
              AND COALESCE(h.active, 1) = 1
              AND COALESCE(h.visible, 1) = 1
            ORDER BY COALESCE(l.position, 0) ASC, h.id ASC",
            ['point_id' => (int)$this->getId()]
        );

        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $label = trim((string)($row['nomcomplet'] ?? ''));
            if ($label === '') {
                $label = trim((string)($row['name'] ?? ''));
            }

            if ($label === '') {
                $label = 'Holon #' . (int)($row['id'] ?? 0);
            }

            $items[] = [
                'id' => (int)($row['id'] ?? 0),
                'label' => $label,
            ];
        }

        return $items;
    }

    public function getTensionItems(): array
    {
        if (!self::tableExists('document_pv_point_tension') || !self::tableExists('tension')) {
            return [];
        }

        $rows = self::fetchAll(
            "SELECT
                t.id,
                t.title,
                l.position
            FROM document_pv_point_tension l
            INNER JOIN tension t
                ON t.id = l.IDtension
            WHERE l.IDdocument_pv_point = :point_id
              AND COALESCE(t.active, 1) = 1
            ORDER BY COALESCE(l.position, 0) ASC, t.id ASC",
            ['point_id' => (int)$this->getId()]
        );

        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $label = trim((string)($row['title'] ?? ''));
            if ($label === '') {
                $label = 'Tension #' . (int)($row['id'] ?? 0);
            }

            $items[] = [
                'id' => (int)($row['id'] ?? 0),
                'label' => $label,
            ];
        }

        return $items;
    }

    public function getDurationMinutesValue(string $field): ?int
    {
        $value = $this->get($field);
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int)$value);
    }

    public function isHandled(): bool
    {
        return (int)$this->get('is_handled') === 1;
    }

    public static function getEditLockTimeoutSeconds(): int
    {
        return self::EDIT_LOCK_TIMEOUT_SECONDS;
    }

    public function getEditingUserId(): int
    {
        return (int)$this->get('IDuser_editing');
    }

    public function getModificationUserId(): int
    {
        return (int)$this->get('IDuser_modification');
    }

    public function getEditingLockToken(): string
    {
        return trim((string)$this->get('edit_lock_token'));
    }

    public function getEditingUserDisplayName(int $organizationId = 0): string
    {
        return self::resolveUserDisplayNameById($this->getEditingUserId(), $organizationId);
    }

    public function getModificationUserDisplayName(int $organizationId = 0): string
    {
        return self::resolveUserDisplayNameById($this->getModificationUserId(), $organizationId);
    }

    public function isEditLockActive(?\DateTimeInterface $referenceDate = null): bool
    {
        $editingUserId = $this->getEditingUserId();
        $editingDate = $this->get('dateedition');
        if ($editingUserId <= 0 || !($editingDate instanceof \DateTimeInterface)) {
            return false;
        }

        $referenceTimestamp = $referenceDate instanceof \DateTimeInterface
            ? (int)$referenceDate->getTimestamp()
            : time();

        return ((int)$editingDate->getTimestamp() + self::getEditLockTimeoutSeconds()) >= $referenceTimestamp;
    }

    public function isLockedByOtherSession(int $userId = 0, string $lockToken = ''): bool
    {
        if (!$this->isEditLockActive()) {
            return false;
        }

        $lockToken = trim($lockToken);
        if ($lockToken !== '' && $this->getEditingLockToken() === $lockToken) {
            return false;
        }

        $userId = (int)$userId;
        return $this->getEditingUserId() > 0 && ($userId <= 0 || $this->getEditingUserId() !== $userId);
    }

    public function isEditLockOwnedByUserSession(int $userId, string $lockToken): bool
    {
        $lockToken = trim($lockToken);
        return $userId > 0
            && $lockToken !== ''
            && $this->isEditLockActive()
            && $this->getEditingUserId() === (int)$userId
            && hash_equals($this->getEditingLockToken(), $lockToken);
    }

    protected function buildEditLockConflictResult(int $currentUserId = 0, string $currentLockToken = '', int $organizationId = 0): array
    {
        $editingUserId = $this->getEditingUserId();
        $editingDate = $this->get('dateedition');
        $editingUserName = $this->getEditingUserDisplayName($organizationId);
        $isOwnedByCurrentUser = $currentUserId > 0 && $editingUserId === $currentUserId;
        $isOwnedByCurrentSession = $currentLockToken !== '' && $this->getEditingLockToken() === trim($currentLockToken);
        $message = $isOwnedByCurrentUser
            ? 'Ce point est deja en cours d edition dans une autre session.'
            : 'Ce point est deja en cours d edition.';

        if (!$isOwnedByCurrentUser && $editingUserName !== '') {
            $message .= ' Utilisateur: ' . $editingUserName . '.';
        }

        $message .= ' Reessayez dans quelques minutes.';

        return [
            'status' => false,
            'text' => $message,
            'lock' => [
                'userId' => $editingUserId,
                'userName' => $editingUserName,
                'date' => $editingDate instanceof \DateTimeInterface ? $editingDate : null,
                'isOwnedByCurrentUser' => $isOwnedByCurrentUser,
                'isOwnedByCurrentSession' => $isOwnedByCurrentSession,
                'timeoutSeconds' => self::getEditLockTimeoutSeconds(),
            ],
        ];
    }

    public function touchEditLock(int $organizationId, int $userId, string $lockToken): array
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        $lockToken = trim($lockToken);

        if ((int)$this->getId() <= 0 || $organizationId <= 0 || $userId <= 0 || $lockToken === '') {
            return [
                'status' => false,
                'text' => 'Requete de verrou invalide.',
            ];
        }

        $document = new \dbObject\Document();
        if (
            !$document->load((int)$this->get('IDdocument'))
            || (int)$document->get('IDorganization') !== $organizationId
            || !$document->canUserOpenPvEditor($userId, $organizationId)
        ) {
            return [
                'status' => false,
                'text' => 'Acces refuse.',
            ];
        }

        $now = new \DateTimeImmutable();
        if (
            $this->isEditLockActive($now)
            && $this->getEditingLockToken() !== $lockToken
            && $this->getEditingUserId() !== $userId
        ) {
            return $this->buildEditLockConflictResult($userId, $lockToken, $organizationId);
        }

        $result = self::execute(
            "UPDATE document_pv_point
            SET IDuser_editing = :user_id,
                edit_lock_token = :lock_token,
                dateedition = :editing_date
            WHERE id = :point_id",
            [
                'user_id' => $userId,
                'lock_token' => $lockToken,
                'editing_date' => $now->format('Y-m-d H:i:s'),
                'point_id' => (int)$this->getId(),
            ]
        );
        if (!$result || !$this->load((int)$this->getId())) {
            return [
                'status' => false,
                'text' => 'Impossible de verrouiller ce point.',
            ];
        }

        return [
            'status' => true,
            'text' => 'Verrou d edition actif.',
            'lock' => [
                'userId' => $userId,
                'userName' => $this->getEditingUserDisplayName($organizationId),
                'date' => $now,
                'isOwnedByCurrentUser' => true,
                'isOwnedByCurrentSession' => true,
                'timeoutSeconds' => self::getEditLockTimeoutSeconds(),
            ],
        ];
    }

    public function releaseEditLock(int $userId, string $lockToken = ''): array
    {
        $userId = (int)$userId;
        $lockToken = trim($lockToken);
        if ((int)$this->getId() <= 0 || $userId <= 0) {
            return [
                'status' => false,
                'text' => 'Verrou introuvable.',
            ];
        }

        if (!$this->isEditLockActive()) {
            return [
                'status' => true,
                'text' => 'Aucun verrou a liberer.',
            ];
        }

        if ($this->getEditingUserId() !== $userId) {
            return [
                'status' => true,
                'text' => 'Aucun verrou a liberer.',
            ];
        }

        if ($lockToken !== '' && $this->getEditingLockToken() !== '' && $this->getEditingLockToken() !== $lockToken) {
            return [
                'status' => true,
                'text' => 'Verrou conserve par une autre session.',
            ];
        }

        $result = self::execute(
            "UPDATE document_pv_point
            SET IDuser_editing = NULL,
                edit_lock_token = NULL,
                dateedition = NULL
            WHERE id = :point_id",
            ['point_id' => (int)$this->getId()]
        );
        if (!$result || !$this->load((int)$this->getId())) {
            return [
                'status' => false,
                'text' => 'Impossible de liberer le verrou d edition.',
            ];
        }

        return [
            'status' => true,
            'text' => 'Verrou d edition libere.',
        ];
    }

    public function getRenderedContentForViewer(int $organizationId = 0): string
    {
        $content = trim((string)$this->get('content'));
        if ($content === '') {
            return '';
        }

        $renderer = new \dbObject\Document();
        return $renderer->renderResolvedHtmlForViewer($content, max(0, $organizationId));
    }

    public function buildViewerData(int $organizationId = 0, int $currentUserId = 0, string $currentLockToken = ''): array
    {
        $position = (int)$this->get('position');
        $editingDate = $this->get('dateedition');
        $modificationDate = $this->get('datemodification');
        $isLockActive = $this->isEditLockActive();
        $isLockedByOther = $this->isLockedByOtherSession($currentUserId, $currentLockToken);
        $isLockOwnedByCurrentSession = $isLockActive
            && $currentLockToken !== ''
            && $this->getEditingLockToken() === $currentLockToken;
        $isLockOwnedByCurrentUser = $isLockActive
            && $currentUserId > 0
            && $this->getEditingUserId() === $currentUserId;
        $syncVersion = hash('sha256', (string)json_encode([
            'item_type' => self::normalizeItemType($this->get('item_type')),
            'parent_id' => (int)$this->get('IDparent'),
            'position' => $position,
            'title' => trim((string)$this->get('title')),
            'author_user_id' => (int)$this->get('IDuser_author'),
            'author_email' => trim((string)$this->get('author_email')),
            'concerned_holon_id' => (int)$this->get('IDholon_concerned'),
            'content' => (string)$this->get('content'),
            'desired_duration_minutes' => $this->getDurationMinutesValue('desired_duration_minutes'),
            'actual_duration_minutes' => $this->getDurationMinutesValue('actual_duration_minutes'),
            'pointtype' => self::normalizePointType($this->get('pointtype')),
            'is_handled' => $this->isHandled(),
            'active' => !empty($this->get('active')),
            'modification_user_id' => $this->getModificationUserId(),
        ], JSON_UNESCAPED_SLASHES));

        return [
            'id' => (int)$this->getId(),
            'itemType' => self::normalizeItemType($this->get('item_type')),
            'isGroup' => $this->isGroup(),
            'parentId' => (int)$this->get('IDparent'),
            'position' => $position,
            'positionLabel' => $position > 0 ? str_pad((string)$position, 2, '0', STR_PAD_LEFT) : '--',
            'title' => trim((string)$this->get('title')),
            'pointType' => self::normalizePointType($this->get('pointtype')),
            'pointTypeLabel' => $this->getPointTypeLabel(),
            'authorLabel' => $this->getAuthorDisplayName($organizationId),
            'authorEmail' => trim((string)$this->get('author_email')),
            'concernedHolonId' => (int)$this->get('IDholon_concerned'),
            'concernedHolonLabel' => $this->getConcernedHolonLabel(),
            'addressedHolons' => $this->getAddressedHolonItems(),
            'tensions' => $this->getTensionItems(),
            'desiredDurationMinutes' => $this->getDurationMinutesValue('desired_duration_minutes'),
            'actualDurationMinutes' => $this->getDurationMinutesValue('actual_duration_minutes'),
            'isHandled' => $this->isHandled(),
            'lastModifiedByUserId' => $this->getModificationUserId(),
            'lastModifiedByLabel' => $this->getModificationUserDisplayName($organizationId),
            'lastModifiedAtIso' => $modificationDate instanceof \DateTimeInterface ? $modificationDate->format(DATE_ATOM) : '',
            'lastModifiedAtTimestamp' => $modificationDate instanceof \DateTimeInterface ? (int)$modificationDate->getTimestamp() : 0,
            'syncVersion' => $syncVersion,
            'lock' => [
                'isActive' => $isLockActive,
                'isLockedByOther' => $isLockedByOther,
                'isOwnedByCurrentUser' => $isLockOwnedByCurrentUser,
                'isOwnedByCurrentSession' => $isLockOwnedByCurrentSession,
                'userId' => $this->getEditingUserId(),
                'userLabel' => $this->getEditingUserDisplayName($organizationId),
                'token' => $this->getEditingLockToken(),
                'dateIso' => $editingDate instanceof \DateTimeInterface ? $editingDate->format(DATE_ATOM) : '',
                'timestamp' => $editingDate instanceof \DateTimeInterface ? (int)$editingDate->getTimestamp() : 0,
            ],
            'contentHtml' => $this->getRenderedContentForViewer($organizationId),
        ];
    }

    public function isEditableByUser(int $userId): bool
    {
        $userId = (int)$userId;
        return !$this->isGroup() && $userId > 0 && $userId === (int)$this->get('IDuser_author');
    }

    public function buildEditorData(int $organizationId = 0, int $currentUserId = 0, string $currentLockToken = ''): array
    {
        $data = $this->buildViewerData($organizationId, $currentUserId, $currentLockToken);
        $data['authorUserId'] = (int)$this->get('IDuser_author');
        $data['authorValue'] = (int)$this->get('IDuser_author') > 0
            ? 'user:' . (int)$this->get('IDuser_author')
            : (trim((string)$this->get('author_email')) !== '' ? 'email:' . trim((string)$this->get('author_email')) : '');
        $data['contentRaw'] = trim((string)$this->get('content'));
        $data['isEditable'] = $this->isEditableByUser($currentUserId);
        $data['isLockedByOther'] = !empty($data['lock']['isLockedByOther']);
        $data['canEditNow'] = $data['isEditable'] && !$data['isLockedByOther'];

        return $data;
    }

    public function save()
    {
        $title = trim((string)$this->get('title'));
        $content = \dbObject\PropertyFormat::sanitizeHtml((string)$this->get('content'));
        $documentId = (int)$this->get('IDdocument');
        $position = (int)$this->get('position');
        $desiredDuration = $this->getDurationMinutesValue('desired_duration_minutes');
        $actualDuration = $this->getDurationMinutesValue('actual_duration_minutes');

        $this->set('title', $title);
        $this->set('item_type', self::normalizeItemType($this->get('item_type')));
        $this->set('content', $content);
        $this->set('pointtype', self::normalizePointType($this->get('pointtype')));
        $this->set('desired_duration_minutes', $desiredDuration);
        $this->set('actual_duration_minutes', $actualDuration);
        $this->set('is_handled', $this->isHandled());

        if ($title === '') {
            return [
                'status' => false,
                'text' => 'Le titre du point est obligatoire.',
            ];
        }

        if ($documentId <= 0) {
            return [
                'status' => false,
                'text' => 'Le point doit etre rattache a un document PV.',
            ];
        }

        $document = new \dbObject\Document();
        if (!$document->load($documentId) || !$document->isPvDocument()) {
            return [
                'status' => false,
                'text' => 'Le document cible n est pas un PV valide.',
            ];
        }

        $parentId = (int)$this->get('IDparent');
        if ($parentId > 0) {
            $parent = new self();
            if (!$parent->load($parentId) || !$parent->isGroup() || (int)$parent->get('IDdocument') !== $documentId || $parentId === (int)$this->getId()) {
                $parentId = 0;
            }
        }
        $this->set('IDparent', $parentId > 0 ? $parentId : null);

        if ($position <= 0) {
            $position = self::resolveNextPositionForDocument($documentId, $parentId);
            $this->set('position', $position);
        }

        if ($this->isGroup()) {
            $this->set('IDuser_author', null);
            $this->set('author_email', null);
            $this->set('IDholon_concerned', null);
            $this->set('content', '');
            $this->set('desired_duration_minutes', null);
            $this->set('actual_duration_minutes', null);
            $this->set('pointtype', self::TYPE_INFORMATION);
            $this->set('is_handled', false);
            $this->set('IDuser_editing', null);
            $this->set('edit_lock_token', null);
            $this->set('dateedition', null);
        }

        if ((int)$this->get('IDuser_author') <= 0) {
            $this->set('IDuser_author', null);
        }

        $authorEmail = trim(mb_strtolower((string)$this->get('author_email'), 'UTF-8'));
        if ($authorEmail !== '' && !filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'text' => 'L adresse e-mail de l auteur est invalide.',
            ];
        }
        $this->set('author_email', $authorEmail !== '' ? $authorEmail : null);
        if ($authorEmail !== '') {
            $this->set('IDuser_author', null);
            $this->set('IDholon_concerned', null);
        }

        if ((int)$this->get('IDuser_modification') <= 0) {
            $this->set('IDuser_modification', null);
        }

        if ((int)$this->get('IDuser_editing') <= 0) {
            $this->set('IDuser_editing', null);
        }

        if (trim((string)$this->get('edit_lock_token')) === '') {
            $this->set('edit_lock_token', null);
        }

        if ((int)$this->get('IDholon_concerned') <= 0) {
            $this->set('IDholon_concerned', null);
        }

        if ($this->get('active') === null || $this->get('active') === '') {
            $this->set('active', true);
        }

        $now = date('Y-m-d H:i:s');
        $createdAt = $this->get('datecreation');
        if (
            (int)$this->getId() <= 0
            && !($createdAt instanceof \DateTimeInterface)
            && trim((string)$createdAt) === ''
        ) {
            $this->set('datecreation', $now);
        }
        $this->set('datemodification', $now);

        return parent::save();
    }

    public static function reorderForDocument(int $documentId, array $pointIds): array
    {
        $documentId = (int)$documentId;
        if ($documentId <= 0) {
            return [
                'status' => false,
                'message' => 'Document PV invalide.',
            ];
        }

        $normalizedPointIds = [];
        foreach ($pointIds as $pointId) {
            $pointId = (int)$pointId;
            if ($pointId > 0) {
                $normalizedPointIds[$pointId] = $pointId;
            }
        }
        $normalizedPointIds = array_values($normalizedPointIds);

        $currentRows = self::fetchAll(
            "SELECT id
            FROM document_pv_point
            WHERE IDdocument = :document_id
              AND COALESCE(active, 1) = 1",
            ['document_id' => $documentId]
        );
        if (!is_array($currentRows)) {
            return [
                'status' => false,
                'message' => 'Impossible de charger les points du PV.',
            ];
        }

        $currentPointIds = [];
        foreach ($currentRows as $row) {
            $pointId = (int)($row['id'] ?? 0);
            if ($pointId > 0) {
                $currentPointIds[$pointId] = $pointId;
            }
        }
        $currentPointIds = array_values($currentPointIds);
        sort($currentPointIds);

        $comparisonPointIds = $normalizedPointIds;
        sort($comparisonPointIds);
        if ($comparisonPointIds !== $currentPointIds) {
            return [
                'status' => false,
                'message' => 'La liste des points a reordonner est incomplete.',
            ];
        }

        foreach ($normalizedPointIds as $index => $pointId) {
            $result = self::execute(
                "UPDATE document_pv_point
                SET position = :position,
                    datemodification = :updated_at
                WHERE IDdocument = :document_id
                  AND id = :point_id",
                [
                    'position' => $index + 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'document_id' => $documentId,
                    'point_id' => $pointId,
                ]
            );
            if (!$result) {
                return [
                    'status' => false,
                    'message' => 'Impossible de reordonner les points du PV.',
                ];
            }
        }

        return [
            'status' => true,
        ];
    }

    public static function reorderForDocumentByUser(int $documentId, array $pointIds, int $userId = 0): array
    {
        $userId = (int)$userId;
        $documentId = (int)$documentId;
        if ($documentId <= 0) {
            return [
                'status' => false,
                'message' => 'Document PV invalide.',
            ];
        }

        $normalizedPointIds = [];
        foreach ($pointIds as $pointId) {
            $pointId = (int)$pointId;
            if ($pointId > 0) {
                $normalizedPointIds[$pointId] = $pointId;
            }
        }
        $normalizedPointIds = array_values($normalizedPointIds);

        $currentRows = self::fetchAll(
            "SELECT id
            FROM document_pv_point
            WHERE IDdocument = :document_id
              AND COALESCE(active, 1) = 1",
            ['document_id' => $documentId]
        );
        if (!is_array($currentRows)) {
            return [
                'status' => false,
                'message' => 'Impossible de charger les points du PV.',
            ];
        }

        $currentPointIds = [];
        foreach ($currentRows as $row) {
            $pointId = (int)($row['id'] ?? 0);
            if ($pointId > 0) {
                $currentPointIds[$pointId] = $pointId;
            }
        }
        $currentPointIds = array_values($currentPointIds);
        sort($currentPointIds);

        $comparisonPointIds = $normalizedPointIds;
        sort($comparisonPointIds);
        if ($comparisonPointIds !== $currentPointIds) {
            return [
                'status' => false,
                'message' => 'La liste des points a reordonner est incomplete.',
            ];
        }

        $updatedAt = date('Y-m-d H:i:s');
        foreach ($normalizedPointIds as $index => $pointId) {
            $parameters = [
                'position' => $index + 1,
                'updated_at' => $updatedAt,
                'document_id' => $documentId,
                'point_id' => $pointId,
            ];
            $sql = "UPDATE document_pv_point
                SET position = :position,
                    datemodification = :updated_at";
            if ($userId > 0) {
                $sql .= ",
                    IDuser_modification = :user_id";
                $parameters['user_id'] = $userId;
            }
            $sql .= "
                WHERE IDdocument = :document_id
                  AND id = :point_id";

            $result = self::execute($sql, $parameters);
            if (!$result) {
                return [
                    'status' => false,
                    'message' => 'Impossible de reordonner les points du PV.',
                ];
            }
        }

        return [
            'status' => true,
        ];
    }

    public static function reorderHierarchyForDocumentByUser(int $documentId, array $layout, int $userId): array
    {
        $documentId = (int)$documentId;
        $userId = (int)$userId;
        $document = new \dbObject\Document();
        if ($documentId <= 0 || $userId <= 0 || !$document->load($documentId) || !$document->isPvDocument() || $document->isPvValidated()) {
            return ['status' => false, 'message' => 'Document PV invalide.'];
        }

        $rows = self::fetchAll(
            "SELECT id, COALESCE(IDparent, 0) AS parent_id, position, item_type, IDuser_author
             FROM document_pv_point
             WHERE IDdocument = :document_id
               AND COALESCE(active, 1) = 1
             ORDER BY COALESCE(IDparent, 0) ASC, position ASC, id ASC",
            ['document_id' => $documentId]
        );
        if (!is_array($rows)) {
            return ['status' => false, 'message' => 'Impossible de charger l ordre du jour.'];
        }

        $currentById = [];
        $currentChildren = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $parentId = (int)($row['parent_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $currentById[$id] = [
                'id' => $id,
                'parentId' => $parentId,
                'position' => (int)($row['position'] ?? 0),
                'itemType' => self::normalizeItemType($row['item_type'] ?? ''),
                'authorUserId' => (int)($row['IDuser_author'] ?? 0),
            ];
            $currentChildren[$parentId][] = $id;
        }

        $submittedById = [];
        $submittedChildren = [];
        foreach ($layout as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $id = (int)($entry['id'] ?? 0);
            $parentId = max(0, (int)($entry['parentId'] ?? 0));
            if ($id <= 0 || isset($submittedById[$id])) {
                continue;
            }
            $submittedById[$id] = ['id' => $id, 'parentId' => $parentId];
            $submittedChildren[$parentId][] = $id;
        }

        $currentIds = array_keys($currentById);
        $submittedIds = array_keys($submittedById);
        sort($currentIds);
        sort($submittedIds);
        if ($currentIds !== $submittedIds) {
            return ['status' => false, 'message' => 'La structure de l ordre du jour est incomplete.'];
        }

        foreach ($submittedById as $id => $entry) {
            $parentId = (int)$entry['parentId'];
            if ($parentId === $id || ($parentId > 0 && (!isset($currentById[$parentId]) || $currentById[$parentId]['itemType'] !== self::ITEM_TYPE_GROUP))) {
                return ['status' => false, 'message' => 'Le groupe cible est invalide.'];
            }

            $visited = [$id => true];
            $ancestorId = $parentId;
            while ($ancestorId > 0) {
                if (isset($visited[$ancestorId]) || !isset($submittedById[$ancestorId])) {
                    return ['status' => false, 'message' => 'Les groupes ne peuvent pas former une boucle.'];
                }
                $visited[$ancestorId] = true;
                $ancestorId = (int)$submittedById[$ancestorId]['parentId'];
            }
        }

        $canManage = $document->canUserManagePvDocument($userId);
        if (!$canManage && $document->getPvStage() !== \dbObject\Document::PV_STAGE_PREPARATION) {
            return ['status' => false, 'message' => 'Le deplacement des points personnels est limite a la preparation.'];
        }

        $movableIds = [];
        foreach ($currentById as $id => $item) {
            if ($canManage || ($item['itemType'] === self::ITEM_TYPE_POINT && $item['authorUserId'] === $userId)) {
                $movableIds[$id] = true;
            }
        }
        if (count($movableIds) === 0) {
            return ['status' => false, 'message' => 'Aucun element ne peut etre deplace.'];
        }

        if (!$canManage) {
            $parentIds = array_unique(array_merge(array_keys($currentChildren), array_keys($submittedChildren)));
            foreach ($parentIds as $parentId) {
                $before = array_values(array_filter($currentChildren[$parentId] ?? [], static function (int $id) use ($movableIds): bool {
                    return !isset($movableIds[$id]);
                }));
                $after = array_values(array_filter($submittedChildren[$parentId] ?? [], static function (int $id) use ($movableIds): bool {
                    return !isset($movableIds[$id]);
                }));
                if ($before !== $after) {
                    return ['status' => false, 'message' => 'Vous ne pouvez deplacer que vos propres points.'];
                }
            }
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return ['status' => false, 'message' => 'Connexion a la base impossible.'];
        }

        $startedTransaction = false;
        try {
            $startedTransaction = !$pdo->inTransaction();
            if ($startedTransaction) {
                $pdo->beginTransaction();
            }

            $updatedAt = date('Y-m-d H:i:s');
            foreach ($submittedChildren as $parentId => $childIds) {
                foreach (array_values($childIds) as $index => $id) {
                    $nextParentId = (int)$parentId;
                    $nextPosition = $index + 1;
                    if (
                        (int)$currentById[$id]['parentId'] === $nextParentId
                        && (int)$currentById[$id]['position'] === $nextPosition
                    ) {
                        continue;
                    }

                    $result = self::execute(
                        "UPDATE document_pv_point
                         SET IDparent = :parent_id,
                             position = :position,
                             IDuser_modification = :user_id,
                             datemodification = :updated_at
                         WHERE IDdocument = :document_id
                           AND id = :item_id",
                        [
                            'parent_id' => $nextParentId > 0 ? $nextParentId : null,
                            'position' => $nextPosition,
                            'user_id' => $userId,
                            'updated_at' => $updatedAt,
                            'document_id' => $documentId,
                            'item_id' => $id,
                        ]
                    );
                    if ($result === false) {
                        throw new \RuntimeException('pv_agenda_hierarchy_update_failed');
                    }
                }
            }

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Impossible de sauver la structure de l ordre du jour.'];
        }

        return ['status' => true];
    }
}

?>
