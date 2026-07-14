<?php
namespace dbObject;

class ResourceAttendance extends DbObject
{
    public static function tableName()
    {
        return 'resource_attendance';
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
            [['resource_type', 'resource_id'], 'required'],
            [['id', 'resource_id'], 'integer'],
            [['IDuser', 'IDuser_checked_by'], 'fk'],
            [['email'], 'mail'],
            [['resource_type', 'display_name'], 'string'],
            [['is_present', 'active'], 'boolean'],
            [['checked_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'resource_type' => 'Type de ressource',
            'resource_id' => 'Ressource',
            'IDuser' => 'Utilisateur',
            'email' => 'E-mail',
            'display_name' => 'Nom affiche',
            'is_present' => 'Present',
            'IDuser_checked_by' => 'Coche par',
            'checked_at' => 'Date de pointage',
            'active' => 'Actif',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeLength()
    {
        return ['resource_type' => 50, 'email' => 250, 'display_name' => 190];
    }

    public static function getOrder()
    {
        return 'display_name ASC, email ASC, id ASC';
    }

    public function get($field)
    {
        return parent::get($field === static::legacyResourceField() ? 'resource_id' : $field);
    }

    public function set($field, $value)
    {
        return parent::set($field === static::legacyResourceField() ? 'resource_id' : $field, $value);
    }

    protected function normalizeEmailValue(): string
    {
        $email = trim(mb_strtolower((string)$this->get('email'), 'UTF-8'));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    public function getIdentityKey(): string
    {
        if ((int)$this->get('IDuser') > 0) {
            return 'user:' . (int)$this->get('IDuser');
        }
        return 'email:' . $this->normalizeEmailValue();
    }

    public function save()
    {
        $this->set('resource_type', static::resourceType());
        $normalizedEmail = $this->normalizeEmailValue();
        $this->set('email', (int)$this->get('IDuser') > 0 ? null : ($normalizedEmail !== '' ? $normalizedEmail : null));
        if ((int)$this->get('resource_id') <= 0 || ((int)$this->get('IDuser') <= 0 && $normalizedEmail === '')) {
            return ['status' => false, 'text' => 'A presence row requires a resource and a user or email.'];
        }
        return parent::save();
    }
}

