<?php
namespace dbObject;

class EventInvitation extends DbObject
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
        return 'event_invitation';
    }

    public static function rules()
    {
        return [
            [['IDevent', 'invitation_type', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDevent', 'IDholon', 'IDuser'], 'fk'],
            [['email'], 'mail'],
            [['display_name', 'invitation_type', 'status'], 'string'],
            [['parameters'], 'parameters'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDevent' => 'Evenement',
            'IDholon' => 'Holon',
            'IDuser' => 'Utilisateur',
            'email' => 'E-mail',
            'display_name' => 'Nom affiche',
            'invitation_type' => 'Type d invitation',
            'status' => 'Statut',
            'parameters' => 'Parametres',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'invitation_type' => 'Precise si l invitation vise un holon, un membre existant ou une adresse e-mail externe.',
            'parameters' => 'Metadonnees additionnelles de l invitation.',
        ];
    }

    public static function attributeLength()
    {
        return [
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

    public static function getTypeCatalog()
    {
        return [
            self::TYPE_HOLON => 'Holon',
            self::TYPE_USER => 'Utilisateur',
            self::TYPE_EMAIL => 'E-mail',
        ];
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_INVITED => 'Invite',
            self::STATUS_ACCEPTED => 'Accepte',
            self::STATUS_DECLINED => 'Refuse',
            self::STATUS_REVOKED => 'Revoque',
        ];
    }

    public static function isValidType($type)
    {
        return array_key_exists((string)$type, self::getTypeCatalog());
    }

    public static function normalizeType($type)
    {
        $type = trim((string)$type);
        return self::isValidType($type) ? $type : self::TYPE_EMAIL;
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_INVITED;
    }

    protected function hasIdentity()
    {
        $type = self::normalizeType($this->get('invitation_type'));
        if ($type === self::TYPE_HOLON) {
            return (int)$this->get('IDholon') > 0;
        }
        if ($type === self::TYPE_USER) {
            return (int)$this->get('IDuser') > 0;
        }
        return trim((string)$this->get('email')) !== '';
    }

    public function save()
    {
        $type = self::normalizeType($this->get('invitation_type'));
        $this->set('invitation_type', $type);
        $this->set('status', self::normalizeStatus($this->get('status')));

        $email = trim((string)$this->get('email'));
        if ($email !== '') {
            $this->set('email', mb_strtolower($email, 'UTF-8'));
        }

        if ($type !== self::TYPE_HOLON) {
            $this->set('IDholon', null);
        }
        if ($type !== self::TYPE_USER) {
            $this->set('IDuser', null);
        }
        if ($type !== self::TYPE_EMAIL) {
            $this->set('email', null);
        }

        if (!$this->hasIdentity()) {
            return [
                'status' => false,
                'text' => 'An event invitation needs a valid target.',
            ];
        }

        return parent::save();
    }

    public function getIdentityKey()
    {
        $type = self::normalizeType($this->get('invitation_type'));
        if ($type === self::TYPE_HOLON) {
            return 'holon:' . (int)$this->get('IDholon');
        }
        if ($type === self::TYPE_USER) {
            return 'user:' . (int)$this->get('IDuser');
        }
        return 'email:' . trim((string)$this->get('email'));
    }
}

?>
