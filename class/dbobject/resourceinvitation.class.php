<?php
namespace dbObject;

class ResourceInvitation extends DbObject
{
    const TYPE_HOLON = 'holon';
    const TYPE_USER = 'user';
    const TYPE_EMAIL = 'email';

    const STATUS_INVITED = 'invited';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_REVOKED = 'revoked';

    public static function tableName()
    {
        return 'resource_invitation';
    }

    public static function resourceType()
    {
        return 'resource';
    }

    protected static function legacyResourceField()
    {
        return '';
    }

    public static function rules()
    {
        return [
            [['resource_type', 'resource_id', 'invitation_type', 'status'], 'required'],
            [['id', 'resource_id'], 'integer'],
            [['IDholon', 'IDuser'], 'fk'],
            [['email'], 'mail'],
            [['resource_type', 'display_name', 'invitation_type', 'status'], 'string'],
            [['parameters'], 'parameters'],
            [['accepted', 'active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'resource_type' => 'Type de ressource',
            'resource_id' => 'Ressource',
            'IDholon' => 'Holon',
            'IDuser' => 'Utilisateur',
            'email' => 'E-mail',
            'display_name' => 'Nom affiche',
            'invitation_type' => 'Type d invitation',
            'status' => 'Statut',
            'accepted' => 'Accepte',
            'parameters' => 'Parametres',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'resource_type' => 'Identifie le type d objet invite sans imposer de cle etrangere polymorphe.',
            'invitation_type' => 'Precise si l invitation vise un holon, un membre ou une adresse e-mail externe.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'resource_type' => 50,
            'email' => 250,
            'display_name' => 190,
            'invitation_type' => 30,
            'status' => 30,
        ];
    }

    public static function getOrder()
    {
        return 'created_at ASC, id ASC';
    }

    public function get($field)
    {
        if ($field === static::legacyResourceField()) {
            $field = 'resource_id';
        }
        return parent::get($field);
    }

    public function set($field, $value)
    {
        if ($field === static::legacyResourceField()) {
            $field = 'resource_id';
        }
        return parent::set($field, $value);
    }

    public static function getTypeCatalog()
    {
        return [self::TYPE_HOLON => 'Holon', self::TYPE_USER => 'Utilisateur', self::TYPE_EMAIL => 'E-mail'];
    }

    public static function getStatusCatalog()
    {
        return [self::STATUS_INVITED => 'Invite', self::STATUS_ACCEPTED => 'Accepte', self::STATUS_DECLINED => 'Refuse', self::STATUS_REVOKED => 'Revoque'];
    }

    public static function normalizeType($type)
    {
        $type = trim((string)$type);
        return self::isValidType($type) ? $type : self::TYPE_EMAIL;
    }

    public static function isValidType($type)
    {
        return array_key_exists((string)$type, self::getTypeCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_INVITED;
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function findByResourceAndIdentity($resourceId, $type, array $identity)
    {
        $type = self::normalizeType($type);
        $query = 'SELECT * FROM `resource_invitation` WHERE `resource_type` = :resource_type AND `resource_id` = :resource_id AND `invitation_type` = :invitation_type';
        $params = ['resource_type' => static::resourceType(), 'resource_id' => (int)$resourceId, 'invitation_type' => $type];
        if ($type === self::TYPE_HOLON) {
            $query .= ' AND `IDholon` = :target_id';
            $params['target_id'] = (int)($identity['holon_id'] ?? 0);
        } elseif ($type === self::TYPE_USER) {
            $query .= ' AND `IDuser` = :target_id';
            $params['target_id'] = (int)($identity['user_id'] ?? 0);
        } else {
            $query .= ' AND `email` = :email';
            $params['email'] = trim(mb_strtolower((string)($identity['email'] ?? ''), 'UTF-8'));
        }
        $row = self::fetchRow($query . ' ORDER BY `id` DESC LIMIT 1', $params);
        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }
        $item = new static();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public function save()
    {
        $this->set('resource_type', static::resourceType());
        $type = self::normalizeType($this->get('invitation_type'));
        $this->set('invitation_type', $type);
        $this->set('status', self::normalizeStatus($this->get('status')));
        if ($this->get('accepted') === null || $this->get('accepted') === '') {
            if ($this->get('status') === self::STATUS_ACCEPTED) {
                $this->set('accepted', 1);
            } elseif ($this->get('status') === self::STATUS_DECLINED) {
                $this->set('accepted', 0);
            }
        }
        $email = trim(mb_strtolower((string)$this->get('email'), 'UTF-8'));
        $this->set('IDholon', $type === self::TYPE_HOLON ? (int)$this->get('IDholon') : null);
        $this->set('IDuser', $type === self::TYPE_USER ? (int)$this->get('IDuser') : null);
        $this->set('email', $type === self::TYPE_EMAIL ? $email : null);
        $hasTarget = ($type === self::TYPE_HOLON && (int)$this->get('IDholon') > 0)
            || ($type === self::TYPE_USER && (int)$this->get('IDuser') > 0)
            || ($type === self::TYPE_EMAIL && $email !== '');
        if ((int)$this->get('resource_id') <= 0 || !$hasTarget) {
            return ['status' => false, 'text' => 'A resource invitation needs a valid resource and target.'];
        }
        return parent::save();
    }

    public function getIdentityKey()
    {
        $type = self::normalizeType($this->get('invitation_type'));
        if ($type === self::TYPE_HOLON) return 'holon:' . (int)$this->get('IDholon');
        if ($type === self::TYPE_USER) return 'user:' . (int)$this->get('IDuser');
        return 'email:' . trim((string)$this->get('email'));
    }
}
