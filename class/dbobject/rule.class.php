<?php
namespace dbObject;

class Rule extends DbObject
{
	protected $preserveImportedAuditMetadata = false;
	protected $auditUserIdOverride = null;

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_DESCENDANTS = 'descendants';
    public const SCOPE_LOCAL = 'local';

    public static function tableName()
    {
        return 'rule';
    }

    public static function rules()
    {
        return [
            [['title', 'description', 'review_date', 'expiration_date'], 'required'],
            [['id'], 'integer'],
            [['IDauthority', 'IDholon', 'IDuser_creation', 'IDuser_modification'], 'fk'],
            [['title', 'scope'], 'string'],
            [['intention', 'description'], 'html'],
            [['review_date', 'expiration_date'], 'date'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDauthority' => 'Domaine d autorite',
            'IDholon' => 'Holon local',
            'title' => 'Titre',
            'intention' => 'Intention',
            'description' => 'Descriptif',
            'scope' => 'Portee',
            'review_date' => 'Date de requestionnement',
            'expiration_date' => 'Date d echeance',
            'created_at' => 'Date de creation',
            'updated_at' => 'Date de modification',
            'IDuser_creation' => 'Creee par',
            'IDuser_modification' => 'Modifiee par',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDauthority' => 'Autorite precise dans laquelle cette regle est definie.',
            'IDholon' => 'Holon auquel une regle locale est directement rattachee.',
            'intention' => 'Pourquoi cette regle a ete creee.',
            'description' => 'Contenu HTML simple de la regle: texte, mise en forme, listes et liens.',
            'scope' => 'Global, descendants ou uniquement le contexte local.',
            'review_date' => 'Date a laquelle la regle doit etre requestionnee.',
            'expiration_date' => 'Apres cette date, la regle n est plus valide.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 255,
            'scope' => 20,
        ];
    }

    public static function attributeValues()
    {
        return [
            'scope' => [
                [self::SCOPE_GLOBAL, 'Globale'],
                [self::SCOPE_DESCENDANTS, 'Descendante'],
                [self::SCOPE_LOCAL, 'Locale'],
            ],
        ];
    }

    public static function getOrder()
    {
        return 'expiration_date ASC, review_date ASC, id ASC';
    }

    public static function sanitizeContentHtml($html)
    {
        $html = is_scalar($html) ? (string)$html : '';
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><blockquote>';
        return PropertyFormat::sanitizeHtml(strip_tags($html, $allowedTags));
    }

    public static function scopes()
    {
        return [self::SCOPE_GLOBAL, self::SCOPE_DESCENDANTS, self::SCOPE_LOCAL];
    }

    public static function normalizeScope($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::scopes(), true) ? $value : self::SCOPE_LOCAL;
    }

    public function save()
    {
        $authorityId = (int)$this->get('IDauthority');
        $holonId = (int)$this->get('IDholon');
        $title = trim((string)$this->get('title'));
        $intention = self::sanitizeContentHtml($this->get('intention'));
        $description = self::sanitizeContentHtml($this->get('description'));
        $reviewDate = $this->normalizeDate($this->get('review_date'));
        $expirationDate = $this->normalizeDate($this->get('expiration_date'));

        if ($title === '' || $description === '' || !$reviewDate || !$expirationDate) {
            return ['status' => false, 'text' => 'A rule requires a title, description, review date and expiration date.'];
        }

        if (($authorityId <= 0 && $holonId <= 0) || ($authorityId > 0 && $holonId > 0)) {
            return ['status' => false, 'text' => 'A rule must be attached either to an authority or to one holon.'];
        }

        if ($reviewDate > $expirationDate) {
            return ['status' => false, 'text' => 'The review date cannot be after the expiration date.'];
        }

        if ($authorityId > 0) {
            $authority = new Authority();
            if (!$authority->load($authorityId)) {
                return ['status' => false, 'text' => 'The selected authority does not exist.'];
            }
            $this->set('IDauthority', $authorityId);
            $this->set('IDholon', null);
        } else {
            $holon = new Holon();
            if (!$holon->load($holonId)) {
                return ['status' => false, 'text' => 'The selected holon does not exist.'];
            }
            $this->set('IDauthority', null);
            $this->set('IDholon', $holonId);
        }

        $this->set('title', $title);
        $this->set('intention', $intention !== '' ? $intention : null);
        $this->set('description', $description);
        $this->set('scope', $authorityId > 0 ? self::normalizeScope($this->get('scope')) : self::SCOPE_LOCAL);
        $this->set('review_date', $reviewDate->format('Y-m-d'));
        $this->set('expiration_date', $expirationDate->format('Y-m-d'));

        $now = new \DateTime();
        $currentUserId = $this->auditUserIdOverride !== null
            ? (int)$this->auditUserIdOverride
            : (function_exists('commonGetCurrentUserId') ? (int)\commonGetCurrentUserId() : (int)($_SESSION['currentUser'] ?? 0));
        if ((int)$this->getId() <= 0) {
            if (!$this->preserveImportedAuditMetadata && !($this->get('created_at') instanceof \DateTimeInterface)) {
                $this->set('created_at', $now);
            }
            if (!$this->preserveImportedAuditMetadata && (int)$this->get('IDuser_creation') <= 0 && $currentUserId > 0) {
                $this->set('IDuser_creation', $currentUserId);
            }
        }
        if (!$this->preserveImportedAuditMetadata || !($this->get('updated_at') instanceof \DateTimeInterface)) {
            $this->set('updated_at', $now);
        }
        if (!$this->preserveImportedAuditMetadata && $currentUserId > 0) {
            $this->set('IDuser_modification', $currentUserId);
        }

        return parent::save();
    }

    public function applyGovernanceState(array $state, $auditUserId = 0)
    {
        $this->auditUserIdOverride = max(0, (int)$auditUserId);
        foreach ([
            'IDauthority',
            'IDholon',
            'title',
            'intention',
            'description',
            'scope',
            'review_date',
            'expiration_date',
        ] as $field) {
            if (array_key_exists($field, $state)) {
                $this->set($field, $state[$field]);
            }
        }
        try {
            return $this->save();
        } finally {
            $this->auditUserIdOverride = null;
        }
    }

    public static function findDefinedInHolon($holonId)
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return [];
        }
        $rows = self::fetchAll(
            'SELECT rule_item.*
             FROM `rule` rule_item
             LEFT JOIN `authority` authority_item ON authority_item.`id` = rule_item.`IDauthority`
             WHERE rule_item.`IDholon` = :local_holon_id
                OR authority_item.`IDholon` = :authority_holon_id
             ORDER BY rule_item.`title` ASC, rule_item.`id` ASC',
            [
                'local_holon_id' => $holonId,
                'authority_holon_id' => $holonId,
            ]
        );
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || !isset($row['id'])) {
                continue;
            }
            $item = new self();
            $item->loadFromArray($row);
            $item->setId((int)$row['id']);
            $items[] = $item;
        }
        return $items;
    }

    public static function loadForGovernanceApplication($ruleId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `rule` WHERE `id` = :rule_id FOR UPDATE',
            ['rule_id' => (int)$ruleId]
        );
        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }
        $rule = new self();
        $rule->loadFromArray($row);
        $rule->setId((int)$row['id']);
        return $rule;
    }

    public function preserveImportedAuditMetadata($preserve = true)
    {
        $this->preserveImportedAuditMetadata = (bool)$preserve;
    }

    public function getAuthority()
    {
        $authorityId = (int)$this->get('IDauthority');
        if ($authorityId <= 0) {
            return null;
        }

        $authority = new Authority();
        return $authority->load($authorityId) ? $authority : null;
    }

    public function getHolon()
    {
        $holonId = (int)$this->get('IDholon');
        if ($holonId > 0) {
            $holon = new Holon();
            return $holon->load($holonId) ? $holon : null;
        }

        $authority = $this->getAuthority();
        return $authority instanceof Authority ? $authority->getHolon() : null;
    }

    public function getCreatedByUser()
    {
        $user = new User();
        return $user->load((int)$this->get('IDuser_creation')) ? $user : null;
    }

    public function getUpdatedByUser()
    {
        $user = new User();
        return $user->load((int)$this->get('IDuser_modification')) ? $user : null;
    }

    public function canEdit()
    {
        $authority = $this->getAuthority();
        if ($authority instanceof Authority) {
            return $authority->canEdit();
        }

        $holon = $this->getHolon();
        return $holon instanceof Holon && $holon->canEdit();
    }

    public function isReviewDue(?\DateTimeInterface $date = null)
    {
        $reviewDate = $this->normalizeDate($this->get('review_date'));
        if (!$reviewDate) {
            return false;
        }

        $date = $date ?: new \DateTimeImmutable('today');
        return $reviewDate <= new \DateTimeImmutable($date->format('Y-m-d'));
    }

    public function isValidAt(?\DateTimeInterface $date = null)
    {
        $expirationDate = $this->normalizeDate($this->get('expiration_date'));
        if (!$expirationDate) {
            return false;
        }

        $date = $date ?: new \DateTimeImmutable('today');
        return new \DateTimeImmutable($date->format('Y-m-d')) <= $expirationDate;
    }

    protected function normalizeDate($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return new \DateTimeImmutable($value->format('Y-m-d'));
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

?>
