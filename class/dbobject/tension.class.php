<?php

namespace dbObject;

class Tension extends DbObject
{
        protected static function uiMessageSourceLang(): array
        {
            return [
                'vous_ne_pouvez_pas_creer_de_tension_dans_ce_contexte' => ['text' => 'Vous ne pouvez pas créer de tension dans ce contexte.', 'context' => 'Validation or action feedback for tension.'],
                'le_titre_est_obligatoire' => ['text' => 'Le titre est obligatoire.', 'context' => 'Validation or action feedback for tension.'],
                'le_titre_doit_contenir_au_maximum_3_mots' => ['text' => 'Le titre doit contenir au maximum 3 mots.', 'context' => 'Validation or action feedback for tension.'],
                'la_description_est_obligatoire' => ['text' => 'La description est obligatoire.', 'context' => 'Validation or action feedback for tension.'],
                'une_tension_doit_etre_rattachee_a_une_organisation' => ['text' => 'Une tension doit être rattachée à une organisation.', 'context' => 'Validation or action feedback for tension.'],
                'une_tension_doit_avoir_un_auteur' => ['text' => 'Une tension doit avoir un auteur.', 'context' => 'Validation or action feedback for tension.'],
            ];
        }

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
            'datecreation' => 'Création',
            'datemodification' => 'Modification',
            'active' => 'Active',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => 'Holon de contexte dans lequel la tension est exprimée.',
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

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        return self::execute("UPDATE tension SET IDuser = :ghost_user_id WHERE IDorganization = :organization_id AND IDuser = :user_id", array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId));
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

        return $holon->resolveOrganizationId();
    }

    public static function canCreateInOrganizationContext(int $organizationId, ?int $requestedHolonId, int $userId): bool
    {
        $organizationId = (int)$organizationId;
        $requestedHolonId = $requestedHolonId !== null ? (int)$requestedHolonId : 0;
        $userId = (int)$userId;

        if ($organizationId <= 0 || $userId <= 0
            || !function_exists('commonUserHasOrganizationAccess')
            || !\commonUserHasOrganizationAccess($userId, $organizationId)) {
            return false;
        }

        $organization = new Organization();
        if (!$organization->load($organizationId)) {
            return false;
        }
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!$rootHolon instanceof Holon) {
            return false;
        }
        $holon = $rootHolon;
        if ($requestedHolonId > 0) {
            $holon = new Holon();
            if (!$holon->load($requestedHolonId)) {
                return false;
            }
        }

        return self::isCreationTarget($holon, $organizationId, (int)$rootHolon->getId())
            && $holon->isAllowed('CAN_CREATE_TENSION', false, $userId);
    }

    protected static function isCreationTarget(Holon $holon, int $organizationId, int $rootHolonId): bool
    {
        return $holon->resolveOrganizationId() === $organizationId
            && (int)$holon->get('IDholon_org') === $rootHolonId
            && (bool)$holon->get('active')
            && (bool)$holon->get('visible')
            && !$holon->get('archived_at')
            && !$holon->isTemplateNode($rootHolonId)
            && ((int)$holon->getId() === $rootHolonId || in_array((int)$holon->get('IDtypeholon'), [1, 2], true));
    }

    public static function loadCreationTargets(int $organizationId, int $userId): ArrayHolon
    {
        $targets = new ArrayHolon();
        $organization = new Organization();
        if ($userId <= 0 || !function_exists('commonUserHasOrganizationAccess')
            || !\commonUserHasOrganizationAccess($userId, $organizationId)
            || !$organization->load($organizationId)) {
            return $targets;
        }
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!$rootHolon instanceof Holon) {
            return $targets;
        }

        $candidates = new ArrayHolon();
        $candidates->loadVisibilityTargetsForOrganization($organizationId);
        $candidates->append($rootHolon);
        $seen = [];
        foreach ($candidates as $holon) {
            $id = (int)$holon->getId();
            if (!isset($seen[$id]) && self::isCreationTarget($holon, $organizationId, (int)$rootHolon->getId())
                && $holon->isAllowed('CAN_CREATE_TENSION', false, $userId)) {
                $targets->append($holon);
                $seen[$id] = true;
            }
        }
        return $targets;
    }

    public function canDeleteInOrganizationContext(int $organizationId, int $userId): bool
    {
        return $organizationId > 0 && $userId > 0 && (int)$this->getId() > 0
            && (int)$this->get('IDorganization') === $organizationId
            && (bool)$this->get('active')
            && function_exists('commonUserHasOrganizationAccess')
            && \commonUserHasOrganizationAccess($userId, $organizationId)
            && ((int)$this->get('IDuser') === $userId
                || (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($userId, $organizationId)));
    }

    public function deleteInOrganizationContext(int $organizationId, int $userId): bool
    {
        if (!$this->canDeleteInOrganizationContext($organizationId, $userId)) {
            return false;
        }

        // Keep references from meeting minutes intact when removing a tension from active lists.
        $this->set('active', 0);
        $this->set('datemodification', date('Y-m-d H:i:s'));
        $result = parent::save();
        return is_array($result) && !empty($result['status']);
    }

    public function createInOrganizationContext(int $organizationId, ?int $holonId, int $userId, array $values = [])
    {
        $organizationId = (int)$organizationId;
        $holonId = $holonId !== null ? (int)$holonId : 0;
        $userId = (int)$userId;

        if (!self::canCreateInOrganizationContext($organizationId, $holonId, $userId)) {
            return [
                'status' => false,
                'text' => self::uiMessage('vous_ne_pouvez_pas_creer_de_tension_dans_ce_contexte'),
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
                'text' => self::uiMessage('le_titre_est_obligatoire'),
            ];
        }

        if (self::countTitleWords($title) > 3) {
            return [
                'status' => false,
                'text' => self::uiMessage('le_titre_doit_contenir_au_maximum_3_mots'),
            ];
        }

        if ($description === '') {
            return [
                'status' => false,
                'text' => self::uiMessage('la_description_est_obligatoire'),
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
                'text' => self::uiMessage('une_tension_doit_etre_rattachee_a_une_organisation'),
            ];
        }

        if ((int)$this->get('IDuser') <= 0) {
            return [
                'status' => false,
                'text' => self::uiMessage('une_tension_doit_avoir_un_auteur'),
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
