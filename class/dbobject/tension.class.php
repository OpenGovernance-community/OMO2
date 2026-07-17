<?php

namespace dbObject;

class Tension extends DbObject
{
    public static function tableName()
    {
        return 'tension';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'IDuser', 'title', 'description'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser'], 'fk'],
            [['title'], 'string'],
            [['description'], 'text'],
            [['active'], 'boolean'],
            [['datecreation', 'datemodification'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Holon',
            'IDuser' => 'Auteur',
            'title' => 'Titre',
            'description' => 'Description',
            'datecreation' => 'Creation',
            'datemodification' => 'Modification',
            'active' => 'Active',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => 'Holon de contexte dans lequel la tension est exprimee.',
            'title' => 'Titre court de trois mots maximum.',
            'description' => 'Description libre en texte simple.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 80,
        ];
    }

    public static function getOrder()
    {
        return 'datecreation DESC, id DESC';
    }

    public static function countTitleWords($title)
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

    protected static function normalizePlainText($value)
    {
        $value = strip_tags((string)$value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
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

        return (int)$holon->get('IDorganization');
    }

    public static function canCreateInOrganizationContext(int $organizationId, ?int $requestedHolonId, int $userId): bool
    {
        $organizationId = (int)$organizationId;
        $requestedHolonId = $requestedHolonId !== null ? (int)$requestedHolonId : 0;
        $userId = (int)$userId;

        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        if ($requestedHolonId > 0) {
            $holon = new Holon();
            if (!$holon->load($requestedHolonId) || (int)$holon->get('IDorganization') !== $organizationId) {
                return false;
            }
        }

        return function_exists('commonUserHasOrganizationAccess')
            ? \commonUserHasOrganizationAccess($userId, $organizationId)
            : false;
    }

    public function createInOrganizationContext(int $organizationId, ?int $holonId, int $userId, array $values = [])
    {
        $organizationId = (int)$organizationId;
        $holonId = $holonId !== null ? (int)$holonId : 0;
        $userId = (int)$userId;

        if (!self::canCreateInOrganizationContext($organizationId, $holonId, $userId)) {
            return [
                'status' => false,
                'text' => 'Vous ne pouvez pas creer de tension dans ce contexte.',
            ];
        }

        $title = trim((string)($values['title'] ?? ''));
        $description = self::normalizePlainText($values['description'] ?? '');

        $this->set('IDorganization', $organizationId);
        $this->set('IDholon', $holonId > 0 ? $holonId : null);
        $this->set('IDuser', $userId);
        $this->set('title', $title);
        $this->set('description', $description);
        $this->set('active', 1);

        return $this->save();
    }

    public function save()
    {
        $title = trim((string)$this->get('title'));
        $description = self::normalizePlainText($this->get('description'));

        $this->set('title', $title);
        $this->set('description', $description);

        if ($title === '') {
            return [
                'status' => false,
                'text' => 'Le titre est obligatoire.',
            ];
        }

        if (self::countTitleWords($title) > 3) {
            return [
                'status' => false,
                'text' => 'Le titre doit contenir au maximum 3 mots.',
            ];
        }

        if ($description === '') {
            return [
                'status' => false,
                'text' => 'La description est obligatoire.',
            ];
        }

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
                'text' => 'Une tension doit etre rattachee a une organisation.',
            ];
        }

        if ((int)$this->get('IDuser') <= 0) {
            return [
                'status' => false,
                'text' => 'Une tension doit avoir un auteur.',
            ];
        }

        $now = date('Y-m-d H:i:s');
        if ((int)$this->getId() <= 0 && trim((string)$this->get('datecreation')) === '') {
            $this->set('datecreation', $now);
        }
        $this->set('datemodification', $now);

        return parent::save();
    }
}

?>
