<?php
namespace dbObject;

class DocumentPvPoint extends DbObject
{
    public const TYPE_INFORMATION = 'information';
    public const TYPE_CONSULTATION = 'consultation';
    public const TYPE_DECISION = 'decision';

    public static function tableName()
    {
        return 'document_pv_point';
    }

    public static function rules()
    {
        return [
            [['IDdocument', 'title', 'pointtype'], 'required'],
            [['id', 'position', 'desired_duration_minutes', 'actual_duration_minutes'], 'integer'],
            [['IDdocument', 'IDuser_author', 'IDholon_concerned'], 'fk'],
            [['title', 'pointtype'], 'string'],
            [['content'], 'html'],
            [['active'], 'boolean'],
            [['datecreation', 'datemodification'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdocument' => 'Document PV',
            'title' => 'Titre',
            'IDuser_author' => 'Auteur',
            'IDholon_concerned' => 'Holon concerne',
            'content' => 'Texte HTML',
            'position' => 'Ordre',
            'desired_duration_minutes' => 'Duree souhaitee',
            'actual_duration_minutes' => 'Duree reelle',
            'pointtype' => 'Type',
            'active' => 'Actif',
            'datecreation' => 'Creation',
            'datemodification' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'title' => 'Titre court de trois mots maximum.',
            'IDuser_author' => 'Membre qui porte ou presente ce point.',
            'IDholon_concerned' => 'Holon principal directement concerne par ce point.',
            'content' => 'Contenu HTML formate du point.',
            'position' => 'Ordre d affichage dans le PV.',
            'desired_duration_minutes' => 'Duree visee en minutes.',
            'actual_duration_minutes' => 'Duree observee en minutes.',
            'pointtype' => 'Nature du point: information, consultation ou decision.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 80,
            'pointtype' => 20,
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

    public static function countTitleWords($title): int
    {
        $title = trim((string)$title);
        if ($title === '') {
            return 0;
        }

        $parts = preg_split('/\s+/u', $title);
        if (!is_array($parts)) {
            return 0;
        }

        $parts = array_values(array_filter($parts, static function ($part) {
            return trim((string)$part) !== '';
        }));

        return count($parts);
    }

    protected static function resolveNextPositionForDocument(int $documentId): int
    {
        return max(
            1,
            (int)self::fetchValue(
                "SELECT COALESCE(MAX(position), 0) + 1
                FROM document_pv_point
                WHERE IDdocument = :document_id",
                ['document_id' => $documentId]
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

    public function getPointTypeLabel(): string
    {
        $catalog = self::getPointTypeCatalog();
        $pointType = self::normalizePointType($this->get('pointtype'));
        return (string)($catalog[$pointType] ?? $catalog[self::TYPE_INFORMATION]);
    }

    public function getAuthorDisplayName(int $organizationId = 0): string
    {
        return self::resolveUserDisplayNameById((int)$this->get('IDuser_author'), $organizationId);
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

    public function getRenderedContentForViewer(int $organizationId = 0): string
    {
        $content = trim((string)$this->get('content'));
        if ($content === '') {
            return '';
        }

        $renderer = new \dbObject\Document();
        return $renderer->renderResolvedHtmlForViewer($content, max(0, $organizationId));
    }

    public function buildViewerData(int $organizationId = 0): array
    {
        $position = (int)$this->get('position');

        return [
            'id' => (int)$this->getId(),
            'position' => $position,
            'positionLabel' => $position > 0 ? str_pad((string)$position, 2, '0', STR_PAD_LEFT) : '--',
            'title' => trim((string)$this->get('title')),
            'pointType' => self::normalizePointType($this->get('pointtype')),
            'pointTypeLabel' => $this->getPointTypeLabel(),
            'authorLabel' => $this->getAuthorDisplayName($organizationId),
            'concernedHolonLabel' => $this->getConcernedHolonLabel(),
            'addressedHolons' => $this->getAddressedHolonItems(),
            'tensions' => $this->getTensionItems(),
            'desiredDurationMinutes' => $this->getDurationMinutesValue('desired_duration_minutes'),
            'actualDurationMinutes' => $this->getDurationMinutesValue('actual_duration_minutes'),
            'contentHtml' => $this->getRenderedContentForViewer($organizationId),
        ];
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
        $this->set('content', $content);
        $this->set('pointtype', self::normalizePointType($this->get('pointtype')));
        $this->set('desired_duration_minutes', $desiredDuration);
        $this->set('actual_duration_minutes', $actualDuration);

        if ($title === '') {
            return [
                'status' => false,
                'text' => 'Le titre du point est obligatoire.',
            ];
        }

        if (self::countTitleWords($title) > 3) {
            return [
                'status' => false,
                'text' => 'Le titre du point doit contenir au maximum 3 mots.',
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

        if ($position <= 0) {
            $position = self::resolveNextPositionForDocument($documentId);
            $this->set('position', $position);
        }

        if ((int)$this->get('IDuser_author') <= 0) {
            $this->set('IDuser_author', null);
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
}

?>
