<?php
namespace dbObject;

class EventAttendance extends DbObject
{
    public static function tableName()
    {
        return 'event_attendance';
    }

    public static function rules()
    {
        return [
            [['IDevent'], 'required'],
            [['id'], 'integer'],
            [['IDevent', 'IDuser', 'IDuser_checked_by'], 'fk'],
            [['email'], 'mail'],
            [['display_name'], 'string'],
            [['is_present', 'active'], 'boolean'],
            [['checked_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDevent' => 'Evenement',
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

    public static function attributeDescriptions()
    {
        return [
            'IDuser' => 'Utilisateur present ou absent pour cet evenement.',
            'email' => 'E-mail utilise quand la presence ne correspond pas a un utilisateur interne.',
            'display_name' => 'Nom libre memorise pour afficher la liste de presence.',
            'is_present' => 'Indique si la personne a ete marquee comme presente.',
            'IDuser_checked_by' => 'Utilisateur qui a modifie le dernier etat de presence.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'email' => 250,
            'display_name' => 190,
        ];
    }

    public static function getOrder()
    {
        return 'display_name ASC, email ASC, id ASC';
    }

    protected function normalizeEmailValue(): string
    {
        $email = trim(mb_strtolower((string)$this->get('email'), 'UTF-8'));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    protected function hasIdentity(): bool
    {
        return (int)$this->get('IDuser') > 0 || $this->normalizeEmailValue() !== '';
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
        $normalizedEmail = $this->normalizeEmailValue();
        if ((int)$this->get('IDuser') > 0) {
            $this->set('email', null);
        } elseif ($normalizedEmail !== '') {
            $this->set('email', $normalizedEmail);
        } else {
            $this->set('email', null);
        }

        if (!$this->hasIdentity()) {
            return [
                'status' => false,
                'text' => 'A presence row requires a user or an email.',
            ];
        }

        return parent::save();
    }
}

