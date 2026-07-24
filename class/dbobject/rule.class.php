<?php
namespace dbObject;

class Rule extends DbObject
{
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
            [['title', 'intention', 'description', 'review_date', 'expiration_date'], 'required'],
            [['id'], 'integer'],
            [['IDauthority', 'IDholon'], 'fk'],
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
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDauthority' => 'Autorite precise dans laquelle cette regle est definie.',
            'IDholon' => 'Holon auquel une regle locale est directement rattachee.',
            'intention' => 'Pourquoi cette regle a ete creee.',
            'description' => 'Contenu HTML de la regle.',
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
        $intention = trim((string)$this->get('intention'));
        $description = trim((string)$this->get('description'));
        $reviewDate = $this->normalizeDate($this->get('review_date'));
        $expirationDate = $this->normalizeDate($this->get('expiration_date'));

        if ($title === '' || $intention === '' || $description === '' || !$reviewDate || !$expirationDate) {
            return ['status' => false, 'text' => 'A rule requires a title, intention, description, review date and expiration date.'];
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
        $this->set('intention', $intention);
        $this->set('description', $description);
        $this->set('scope', $authorityId > 0 ? self::normalizeScope($this->get('scope')) : self::SCOPE_LOCAL);
        $this->set('review_date', $reviewDate->format('Y-m-d'));
        $this->set('expiration_date', $expirationDate->format('Y-m-d'));

        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);

        return parent::save();
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

    public function canEdit()
    {
        $authority = $this->getAuthority();
        if ($authority instanceof Authority) {
            return $authority->canEdit();
        }

        $holon = $this->getHolon();
        return $holon instanceof Holon && $holon->canEdit();
    }

    public function isReviewDue(\DateTimeInterface $date = null)
    {
        $reviewDate = $this->normalizeDate($this->get('review_date'));
        if (!$reviewDate) {
            return false;
        }

        $date = $date ?: new \DateTimeImmutable('today');
        return $reviewDate <= new \DateTimeImmutable($date->format('Y-m-d'));
    }

    public function isValidAt(\DateTimeInterface $date = null)
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
