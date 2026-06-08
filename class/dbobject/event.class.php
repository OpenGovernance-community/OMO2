<?php
namespace dbObject;

class Event extends DbObject
{
    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

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
            [['title', 'status', 'timezone'], 'string'],
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

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_DRAFT;
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

        return parent::save();
    }
}

?>
